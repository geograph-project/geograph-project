<?php

require_once("3rdparty/s3vectors.inc.php"); //defines queryS3Vectors

//todo, move to global config!
$CONF['embed_api'] = 'http://python-embed.dev.svc.cluster.local:8000';
$CONF['s3_vector_bucket'] = 'geograph-vector-bucket';

//for now leave the indexName hardcoded (similarly the manticore index name!)

###################################################

//parse a custom syntax, return a single vector for the query
function getTextEmbeddingFromQuery(string $query): array
{
	require_once("3rdparty/vector.class.php"); //provides EmbeddingVector - needed for vector math

    $parts = explode('-', $query, 2);
    $positivePart = trim($parts[0]);
    $negativePart = isset($parts[1]) ? trim($parts[1]) : '';

    $finalVector = null;

        // Process positive part
        if (!empty($positivePart)) {
            $finalVector = processVectorPart($positivePart);
	}

        // Process negative part
        if (!empty($negativePart)) {
            $negativeVector = processVectorPart($negativePart);
            if ($negativeVector) {
		if (empty($finalVector)) {
			//not sure, but seems we could support JUST negative!
	                $finalVector = new EmbeddingVector(array_fill(0, 512, 0.0));
		}
                $finalVector = $finalVector->subtract($negativeVector);
            }
        }

	if (!empty($finalVector)) {
		//return raw float array!
		return $finalVector->getNormalizedVector();
	}

    return $finalVector;
}

    /**
     * Processes a single part of the query (positive or negative) to create a vector.
     * This is a helper method to avoid code duplication in parseQueryAndGetVector.
     *
     * @param string $part The query string part.
     * @return EmbeddingVector|null The resulting vector object or null on failure.
     */
    function processVectorPart(string $part): ?EmbeddingVector
    {
        $vector = null;

        // Use preg_match_all to find all image IDs
        preg_match_all('/id:(\d+)/', $part, $matches, PREG_SET_ORDER);

        // Aggregate vectors for all found images
        if (!empty($matches)) {
            foreach ($matches as $match) {
                $imageId = (int)$match[1];
                $imageVector = new EmbeddingVector(getImageEmbeddingById($imageId));

                if ($vector === null) {
                    $vector = $imageVector;
                } else {
                    $vector = $vector->add($imageVector);
                }
            }

            // Remove all 'id:...' parts from the string to get the remaining text
            $textPart = trim(preg_replace('/id:(\d+)/', '', $part));
            if (!empty($textPart)) {
                $textVector = new EmbeddingVector(getTextEmbedding($textPart));
                if ($vector === null) {
                    $vector = $textVector;
                } else {
                    $vector = $vector->add($textVector);
                }
            }
        } else {
            // No image IDs found, process the entire part as text
            if (!empty($part)) {
                $vector = new EmbeddingVector(getTextEmbedding($part));
            }
        }
        return $vector;
    }

//copied from _getLabelVectorValueList
function getTextEmbeddingWrapper($label) {
	global $db;
	if (empty($db))
		$db = GeographDatabaseConnection(false);

        if (preg_match('/^id:(\d+)$/',$label,$m) || preg_match('/\/photo\/(\d+)$/',$label,$m)) {
                //todo, in concept we COULD do both, and use vector->add() ?
                return getImageEmbeddingById(intval($m[1]));
        }
        $quoted = $db->Quote($label);
        $binary = $db->getOne("SELECT embeddings FROM label_embedding WHERE label = $quoted");

        if (empty($binary)) {
            // If not found in DB, try to get it from the embedding API
            $r = getTextEmbedding($label);
            if (!empty($r) && is_array($r) && count($r) > 0) { // Check if API returned a valid non-empty array
                // Optionally, save $r to DB here for future use
		//remember to check $db->readonly
                // $db->Execute("INSERT INTO label_embedding (label, embeddings) VALUES ($quoted, ?)", [pack('g*', ...$r)]);
                return $r;
            }
            error_log('Unable to get/encode query vector for label: ' . $label . ' from DB or API.');
            return []; // Return empty array instead of die() or null
        }

        return array_values(unpack('g*', $binary));
}

//copied from _getImageVectorValueList - really should be here (not specific to imagelist)
// in general should be used in preference to getImageEmbedding, as that wont use gridimage_embedding table!
function getImageEmbeddingById($id, $type = 'image') {
	global $db;
	if (empty($db))
		$db = GeographDatabaseConnection(false);

        $type = $db->Quote($type);
        $binary = $db->getOne("SELECT embeddings FROM gridimage_embedding WHERE gridimage_id = ".intval($id)." AND type=$type");
        if (empty($binary)) {
		//todo call getImageEmbedding!
		$image=new GridImage($id, true);
		if ($image->isValid() || $image->moderation_status != 'rejected') {
			$vector = getImageEmbedding($image);
			if ($vector) {
				//todo, save to gridimage_embedding!
				//remember to check $db->readonly
				return $vector;
			}
		}

            error_log('No embedding found for image ID: ' . $id . ' and type: ' . $type);
            return []; // Return empty array consistently on not found
        }
        return array_values(unpack('g*', $binary));
}

/**
 * Retrieves text embeddings from an API.
 * Encapsulates the cURL logic for calling the text embedding service.
 *
 * @param string $inputText The text string for which to get embeddings.
 * @return array An array of floats representing the text embedding vector on success,
 * or an empty array on failure (e.g., API error, invalid response).
 */
function getTextEmbedding($inputText) {
    global $CONF;
    $apiUrl = $CONF['embed_api'].'/text';

    // The data to send in the request body as a JSON string
    $postData = json_encode(['text' => $inputText]);
    if ($postData === false) {
        error_log('get_text_embeddings: Failed to JSON encode postData.');
        return [];
    }

static $ch;

if (empty($ch)) {

    // Initialize a cURL session
    $ch = curl_init();

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
}

    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($postData)
    ]);

    // Execute the cURL request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    $embedding_vector = [];

    // Check for cURL errors
    if (curl_errno($ch)) {
        error_log('get_text_embeddings: cURL error: ' . curl_error($ch));
    } elseif ($http_code !== 200) {
        // Handle non-200 HTTP responses from the embedding API
        error_log('get_text_embeddings: API returned HTTP status ' . $http_code . ': ' . $response);
    } else {
        // Decode the JSON response
        $decoded_response = json_decode($response, true);
        
        // Check for JSON decoding errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('get_text_embeddings: JSON decode error: ' . json_last_error_msg() . ' for response: ' . $response);
        } elseif (!is_array($decoded_response)) {
            error_log('get_text_embeddings: API response was not an array of floats as expected: ' . $response);
        } else {
            $embedding_vector = $decoded_response;
        }
    }

    // Close the cURL session
//    curl_close($ch); -- keep it as now stored statically for reuse
    return $embedding_vector;
}

/**
 * Retrieves image embeddings from an API.
 * Handles downloading the image from its URL and calling the image embedding service.
 *
 * @param object $image An object representing the image, expected to have a _getFullpath method.
 * @return array An array of floats representing the image embedding vector on success,
 * or an empty array on failure (e.g., download error, API error, invalid response).
 */
function getImageEmbedding($image, $use_ai_thumb = false, $check_exists = false) {
    // 1. Get image URL and grab the .jpg
    if ($use_ai_thumb) {
        $url = $image->getAIThumbnail('fullpath', $check_exists);
    } else {
        $url = $image->_getFullpath($check_exists, true);
    }
    if (empty($url)) {
        error_log('get_image_embedding: Image URL is empty.');
        return [];
    }

    $image_bytes = @file_get_contents($url);

    if ($image_bytes === false) {
        error_log('get_image_embedding: Failed to download image from ' . $url);
        return [];
    }

    // 2. Get embeddings from our embed-api (pass image data!)
    global $CONF;
    $apiUrl = $CONF['embed_api'].'/image';

    // The data to send in the request body as a JSON string
    $postData = json_encode(['image' => base64_encode($image_bytes)]);
    if ($postData === false) {
        error_log('get_image_embedding: Failed to JSON encode postData.');
        return [];
    }

static $ch;

if (empty($ch)) {

    // Initialize a cURL session
    $ch = curl_init();

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
}

    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($postData)
    ]);

    // Execute the cURL request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    $vector = [];

    // Check for cURL errors
    if (curl_errno($ch)) {
        error_log('get_image_embedding: cURL error to embed-api: ' . curl_error($ch));
    } elseif ($http_code !== 200) {
        // Handle non-200 HTTP responses
        error_log('get_image_embedding: embed-api returned HTTP status ' . $http_code . ': ' . $response);
    } else {
        // Decode the JSON response
        $decoded_response = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('get_image_embedding: JSON decode error from embed-api: ' . json_last_error_msg());
        } elseif (!is_array($decoded_response)) {
            error_log('get_image_embedding: API response was not an array of floats as expected: ' . $response);
        } else {
            $vector = $decoded_response;
        }
    }

    // Close the cURL session
//    curl_close($ch);-- keep it as now stored statically for reuse
    return $vector;
}

/**
 * Fetches K-Nearest Neighbors (KNN) results from a Manticore Search index.
 * Results are formatted to mimic the S3Vectors API response structure.
 *
 * @param array $vector The query vector (array of floats).
 * @param int $limit The maximum number of nearest neighbors to return.
 * @param string $index_name The name of the Manticore index to query (default: 'label_embedding').
 * @param string $vector_name The name of the vector attribute in the Manticore index (default: 'label_vector').
 * @return array An array structured like the S3Vectors API response, or a default empty structure on failure.
 * Format: Array('http_code' => int, 'vectors' => Array(0 => Array('key' => ..., 'metadata' => ..., 'distance' => ...), ...))
 */
function getManticoreKNNResults(array $vector, int $limit = 30, string $index_name = 'label_embedding', string $vector_name = 'label_vector'): array {
    global $rt; // Assuming $rt is a global Manticore client object

    if (!isset($rt) || !is_object($rt) || !method_exists($rt, 'getAll')) {
        error_log('getManticoreKNNResults: Manticore client ($rt) is not properly initialized.');
        return ['http_code' => 500, 'vectors' => []];
    }

    $results = ['http_code' => 200, 'vectors' => []];

    // Convert the vector array to a comma-separated string for SQL
    // Ensure the vector is flat, numeric array
    $str = "(" . implode(',', array_map('floatval', $vector)) . ")";

    // Manticore SQL query to get KNN results.
    // 'id' and 'label' are assumed to be columns in your specified index.
    // KNN_DIST() is the distance, lower is better.
    $sql = "SELECT id, label, KNN_DIST() AS distance FROM $index_name WHERE KNN($vector_name, $limit, $str) LIMIT $limit";

    try {
        $manticore_raw_results = $rt->getAll($sql);
        if (is_array($manticore_raw_results)) {
            foreach ($manticore_raw_results as $row) {
                // Ensure required keys exist from the SQL query result
                if (isset($row['id']) && isset($row['label']) && isset($row['distance'])) {
                    $results['vectors'][] = [
                        'key' => (string)$row['id'],
                        'metadata' => [
                            'label' => (string)$row['label'],
                        ],
                        'distance' => (float)$row['distance'],
                    ];
                } else {
                    error_log('getManticoreKNNResults: Manticore row missing expected keys (id, label, distance): ' . json_encode($row));
                }
            }
        } else {
            error_log('getManticoreKNNResults: Manticore getAll did not return an array.');
            $results = ['http_code' => 500, 'vectors' => []];
        }
    } catch (Exception $e) {
        error_log('getManticoreKNNResults: Manticore query error: ' . $e->getMessage());
        $results = ['http_code' => 500, 'vectors' => []];
    }

    return $results;
}

function getKNNResults(array $vector, int $limit = 30, string $index_name = 'label_embedding', string $vector_name = 'label_vector'): array {
    global $CONF;

    if (!empty($CONF['s3_vector_bucket'])) {
        $queryPayload = [
            'vectorBucketName' => $CONF['s3_vector_bucket'],
            'indexName' => 'label-clip', // Hardcoded for now
            'queryVector' => ['float32' => $vector],
            'topK' => $limit,
            'returnDistance' => true,
            'returnMetadata' => true,
        ];
        return queryS3Vectors($queryPayload);
    } else {
        return getManticoreKNNResults($vector, $limit, $index_name, $vector_name);
    }
}


/**
 * Retrieves zero-shot labels for a given image.
 * This function utilizes either an S3Vectors service or a local Manticore KNN index
 * to find relevant labels based on image embeddings.
 *
 * @param object $image An object representing the image, expected to have a _getFullpath method.
 * @param int $limit The maximum number of labels to return.
 * @return array An array structured like the S3Vectors API response,
 * Format: Array('http_code' => int, 'vectors' => Array(0 => Array('key' => ..., 'metadata' => ..., 'distance' => ...), ...))
 * Returns a default empty structure on failure.
 */
function getZeroShotLabels($image, $limit = 30, $src = false) {
    global $CONF, $memcache;

	$mkey = $image->gridimage_id.$src??'';
	$results = $memcache->name_get('zero',$mkey);
        if (is_array($results)) { //could be empty array!
		return $results;
	}


    // 1. Get image embedding using the dedicated function
    $vector = getImageEmbedding($image);
    if (empty($vector)) {
        error_log('getZeroShotLabels: Failed to obtain image vector from getImageEmbedding.');
        return ['http_code' => 500, 'vectors' => []];
    }

    // This will hold the final results in the S3Vector format
    $results = [];

    // 2. Decide whether to use S3Vectors or Manticore (KNN) based on configuration
    $results = getKNNResults($vector, $limit);

    if ($results['http_code'] == 200) {
         $memcache->name_set('zero',$mkey,$results,$memcache->compress,$memcache->period_med);
    }

    // 3. The $results variable should now hold the data in the desired S3Vector-like format.
    return $results;
}

/**
 * Dumps a summary of vector search results for debugging purposes.
 * Expects results in the S3Vectors API-like format.
 *
 * @param array $results The results array, expected to contain 'http_code' and 'vectors' keys.
 */
function dumpVectors($results) {
    echo "\n--- Final Results Summary ---\n";
    echo "HTTP Status Code: " . ($results['http_code'] ?? '??') . "\n";

    if (!empty($results['vectors']) && is_array($results['vectors'])) {
        echo "Parsed Results:\n";
        foreach ($results['vectors'] as $i => $vector) {
            echo "Result " . ($i + 1) . ":\n";
            echo "  Key: " . ($vector['key']) . "\n";
            if (isset($vector['distance'])) {
                echo "  Distance: " . $vector['distance'] . "\n";
            }
            if (!empty($vector['metadata'])) {
	            echo "  Metadata: " . json_encode($vector['metadata']) . "\n";
            }
            echo "---\n";
        }
    } else {
        echo "No vectors found for the query or an error occurred.\n";
    }
}
