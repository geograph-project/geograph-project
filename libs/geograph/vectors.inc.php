<?php

require_once("3rdparty/s3vectors.inc.php"); //defines queryS3Vectors

//todo, move to global config!
$CONF['embed_api'] = 'http://python-embed16.dev.svc.cluster.local:8000';
$CONF['s3_vector_bucket'] = 'geograph-vector-bucket';

//for now leave the indexName hardcoded (similarly the manticore index name!)

###################################################

//parse a custom syntax, return a single vector for the query
function getTextEmbeddingFromQuery(string $query, $model = 'clip'): array
{
	require_once("3rdparty/vector.class.php"); //provides EmbeddingVector - needed for vector math

    $parts = explode(' - ', $query, 2);
    $positivePart = trim($parts[0]);
    $negativePart = isset($parts[1]) ? trim($parts[1]) : '';

    $finalVector = null;

        // Process positive part
        if (!empty($positivePart)) {
            $finalVector = processVectorPart($positivePart, $model);
	}

        // Process negative part
        if (!empty($negativePart)) {
            $negativeVector = processVectorPart($negativePart, $model);
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
    function processVectorPart(string $part, $model = 'clip'): ?EmbeddingVector
    {
        $vector = null;

        // Use preg_match_all to find all image IDs
        preg_match_all('/id:(\d+)/', $part, $matches, PREG_SET_ORDER);

        // Aggregate vectors for all found images
        if (!empty($matches)) {
            foreach ($matches as $match) {
                $imageId = (int)$match[1];
                $imageVector = new EmbeddingVector(getImageEmbeddingById($imageId, 'image', $model));

                if ($vector === null) {
                    $vector = $imageVector;
                } else {
                    $vector = $vector->add($imageVector);
                }
            }

            // Remove all 'id:...' parts from the string to get the remaining text
            $textPart = trim(preg_replace('/id:(\d+)/', '', $part));
            if (!empty($textPart)) {
                $textVector = new EmbeddingVector(getTextEmbedding($textPart, $model));
                if ($vector === null) {
                    $vector = $textVector;
                } else {
                    $vector = $vector->add($textVector);
                }
            }
        } else {
            // No image IDs found, process the entire part as text
            if (!empty($part)) {
                $vector = new EmbeddingVector(getTextEmbedding($part, $model));
            }
        }
        return $vector;
    }

//copied from _getLabelVectorValueList
// NOTE only supports clip, and intended for use with known labels
function getTextEmbeddingWrapper($label, $model = 'clip') {
	global $db;
	if (empty($db))
		$db = GeographDatabaseConnection(false);

        if (preg_match('/^id:(\d+)$/',$label,$m) || preg_match('/\/photo\/(\d+)$/',$label,$m)) {
                //todo, in concept we COULD do both, and use vector->add() ?
                return getImageEmbeddingById(intval($m[1]), 'image', $model);
        }
        $quoted = $db->Quote($label);
        $binary = $db->getOne("SELECT embeddings FROM label_embedding WHERE label = $quoted AND model = '$model'");

        if (empty($binary)) {
            // If not found in DB, try to get it from the embedding API
            $r = getTextEmbedding($label, $model);
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

/**
 * Retrieves text embeddings for a bulk of labels.
 *
 * This function fetches embeddings for a given list of labels (either IDs or text strings).
 * It first attempts to retrieve the embeddings from the local `label_embedding` table.
 * For any labels not found or without an embedding, it falls back to the `getTextEmbedding()`
 * function to fetch them from a remote API. New embeddings are then stored in the database
 * for future use.
 *
 * @param array $labels An array of label IDs (int) or text labels (string).
 * @param bool|null $is_id A boolean to explicitly specify if `$labels` are IDs. If null, autodetects.
 * @return array An associative array mapping each label/ID to its embedding vector (array of floats).
 *               Labels for which an embedding could not be found will have an empty array.
 */
function getLabelVectors(array $labels, $is_id = null, $model = 'clip'): array
{
    global $db;
    if (empty($db)) {
        $db = GeographDatabaseConnection(false);
    }

    if (empty($labels)) {
        return [];
    }

    if ($is_id === null) {
        $is_id = is_numeric(reset($labels));
    }

    $results = array_fill_keys($labels, []);
    $column_to_query = $is_id ? 'id' : 'label';

    $placeholders = implode(',', array_fill(0, count($labels), '?'));
    $sql = "SELECT id, label, embeddings FROM label_embedding WHERE $column_to_query IN ($placeholders) AND model = '$model'";

    $rs = $db->Execute($sql, $labels);

    $db_results = [];
    if ($rs) {
        while (!$rs->EOF) {
            $db_results[] = $rs->fields;
            $rs->MoveNext();
        }
    }

    $text_labels_to_fetch_api = [];
    $map_text_label_to_id = [];

    foreach ($db_results as $row) {
        $id = $row['id'];
        $text_label = $row['label'];
        $map_text_label_to_id[$text_label] = $id;

        $key = $is_id ? $id : $text_label;

        if (!empty($row['embeddings'])) {
            $results[$key] = array_values(unpack('g*', $row['embeddings']));
        } else {
            $text_labels_to_fetch_api[] = $text_label;
        }
    }

    if (!$is_id) {
        $found_text_labels = array_column($db_results, 'label');
        $missing_text_labels = array_diff($labels, $found_text_labels);
        $text_labels_to_fetch_api = array_merge($text_labels_to_fetch_api, $missing_text_labels);
    }

    $text_labels_to_fetch_api = array_unique($text_labels_to_fetch_api);

    foreach ($text_labels_to_fetch_api as $text_label) {
        $vector = getTextEmbedding($text_label, $model);

        if (empty($vector)) {
            continue;
        }

        // Update results array
        if ($is_id) {
            if (isset($map_text_label_to_id[$text_label])) {
                $id = $map_text_label_to_id[$text_label];
                if (isset($results[$id])) {
                    $results[$id] = $vector;
                }
            }
        } else {
            $results[$text_label] = $vector;
        }

        // Update database
        if (!$db->readonly) {
            $packedVector = pack('g*', ...$vector);
            if (isset($map_text_label_to_id[$text_label])) {
                // UPDATE
                //$db->Execute("UPDATE label_embedding SET embeddings = ? WHERE id = ?", [$packedVector, $map_text_label_to_id[$text_label]]);
            } else {
                // INSERT
                $db->Execute("INSERT INTO label_embedding (label, embeddings, $model) VALUES (?, ?)", [$text_label, $packedVector, $model]);
            }
        }
    }

    return $results;
}

##################################################

//copied from _getImageVectorValueList - really should be here (not specific to imagelist)
// in general should be used in preference to getImageEmbedding, as that wont use gridimage_embedding table!
function getImageEmbeddingById($id, $type = 'image', $model = 'clip') {
	global $db;
	if (empty($db))
		$db = GeographDatabaseConnection(false);

        $type = $db->Quote($type);
	if ($model == 'pe') {
		$binary = $db->getOne("SELECT embeddings FROM gridimage_embedding_1024 WHERE gridimage_id = ".intval($id)." AND type=$type AND model = '$model'");
	} else {
	        $binary = $db->getOne("SELECT embeddings FROM gridimage_embedding WHERE gridimage_id = ".intval($id)." AND type=$type AND model = '$model'");
	}
        if (empty($binary)) {
		//todo call getImageEmbedding!
		$image=new GridImage($id, true);
		if ($image->isValid() || $image->moderation_status != 'rejected') {
			if ($type == 'image')
				$vector = getImageEmbedding($image, false, false, $model);
			else
				$vector = getTextEmbedding($image->title, $model);
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
function getTextEmbedding($inputText, $model = 'clip') {
    global $CONF;
    $apiUrl = $CONF['embed_api'].'/text';

// TODO, temporary bodge!!
	//titan is a AWS bedrock model, while have python able to use it, we dont have PHP yet. Nor have it wrapped in our embed API
	if ($model == 'titan') {
                $cmd = "python3 /var/www/geograph/scripts/vector-cmd6.py -m titan get-embedding --text ".escapeshellarg($inputText);
                $raw = `$cmd`;
                if (!empty($raw)) {
                        $vector = json_decode($raw, TRUE);
                        if (!empty($vector) && count($vector) == 512) {
				return $vector;
			}
		}
	}


    // The data to send in the request body as a JSON string
    $postData = json_encode(['text' => $inputText, "model" => $model]);
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
function getImageEmbedding($image, $use_ai_thumb = false, $check_exists = false, $model = 'clip') {
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
    $postData = json_encode(['image' => base64_encode($image_bytes), 'model'=>$model]);
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

##################################################

/**
 * Fetches K-Nearest Neighbors (KNN) results from a Manticore Search index.
 * Results are formatted to mimic the S3Vectors API response structure.
 *
 * @param array $vector The query vector (array of floats).
 * @param int $limit The maximum number of nearest neighbors to return.
 * @param string $index_name The name of the Manticore index to query (default: 'label_embedding').
 * @return array An array structured like the S3Vectors API response, or a default empty structure on failure.
 * Format: Array('http_code' => int, 'vectors' => Array(0 => Array('key' => ..., 'metadata' => ..., 'distance' => ...), ...))
 */
function getManticoreKNNResults(array $vector, int $limit = 30, string $index_name = 'label_embedding', array $where = []): array {
    global $rt; // Assuming $rt is a global Manticore client object

    $results = ['http_code' => 500, 'vectors' => []];

    if (!isset($rt) || !is_object($rt) || !method_exists($rt, 'getAll')) {
        error_log('getManticoreKNNResults: Manticore client ($rt) is not properly initialized.');
        return $results;
    }

    // Convert the vector array to a comma-separated string for SQL
    // Ensure the vector is flat, numeric array
    $str = "(" . implode(',', array_map('floatval', $vector)) . ")";
	$vector_name = 'label_vector';

	if (empty($where))
		$where = array();

    $where[] = "KNN($vector_name, $limit, $str)";

    // Manticore SQL query to get KNN results.
    // 'id' and 'label' are assumed to be columns in your specified index.
    // KNN_DIST() is the distance, lower is better.
    $where = implode(' AND ', $where);
    $sql = "SELECT id, label, KNN_DIST() AS distance FROM $index_name WHERE $where LIMIT $limit";

    try {
        $manticore_raw_results = $rt->getAll($sql);
        if (is_array($manticore_raw_results)) {
            $results['http_code'] = 200;
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
        }
    } catch (Exception $e) {
        error_log('getManticoreKNNResults: Manticore query error: ' . $e->getMessage());
    }

    return $results;
}

function getKNNResults(array $vector, int $limit = 30, string $index_name = 'label_embedding', $src = false): array {
    global $CONF;

	//convert manticore index to s3index
	$mapped = array( // Hardcoded for now
		'image_embedding' => 'image-clip', //although s3 idnex is 'image' only! (not the title of the image)
		'image_embedding_1024' => 'image-pe',
		'label_embedding' => 'label-clip',
		'label_embedding_mpnet' => 'label-mpnet',
		'tags_embedding' => 'tags-clip',
		'tags_embedding_pe' => 'tags-pe',
		'tags_embedding_mpnet' => 'tags-mpnet',
	);

    if (empty($mapped[$index_name]))
	die("unknown index for $index_name");

    if (!empty($CONF['s3_vector_bucket'])) { //or maybe chould checked $mapped
	global $filesystem;

	if (empty($filesystem))
		$filesystem = new FileSystem(); //sets up S3 configuation automagically - needed for s3Vectors!

        $queryPayload = [
            'vectorBucketName' => $CONF['s3_vector_bucket'],
            'indexName' => $mapped[$index_name],
            'queryVector' => ['float32' => $vector],
            'topK' => $limit,
            'returnDistance' => true,
            'returnMetadata' => true,
        ];
        if (!empty($src))
		$queryPayload['filter'] = array('src'=>$src);
        return queryS3Vectors($queryPayload);

    } else {
	$where = array();
	if (!empty($src) && ctype_alpha($src)) {
		$limit *= 5; //need to oversample!
		$where[] = "src = '$src'";
	}
        return getManticoreKNNResults($vector, $limit, $index_name, $where);
    }
}

#######################################

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

//NOTE! this is really just a test function, should probably use getZeroShotTags now.
function getZeroShotLabels($image, $limit = 30, $src = false, $model = 'clip') {
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
	if ($model == 'pe') {
	    $results = getKNNResults($vector, $limit, 'label_embedding_1024', $src);
	} else {
	    $results = getKNNResults($vector, $limit, 'label_embedding', $src);
	}

    if ($results['http_code'] == 200) {
         $memcache->name_set('zero',$mkey,$results,$memcache->compress,$memcache->period_med);
    }

    // 3. The $results variable should now hold the data in the desired S3Vector-like format.
    return $results;
}

/**
 * Generates a list of zero-shot tags for an image using a specified embedding model.
 *
 * This function takes an image and generates a list of relevant tags based on a zero-shot model.
 * It allows for customization of the input source, the model used, and the namespace for the tags.
 *
 * @param object $image the image to analyze.
 * @param int $limit The maximum number of tags to return. Defaults to 10.
 * @param string $input The source of the vector ('image', 'title', or 'comment'). Determines the
 * input context for the embedding model. Defaults to 'title'.
 * @param string $model The embedding model to use for generating the tags. Defaults to 'clip'.
 * @param string $prefix The tag namespace or prefix to be used for the returned tags. Defaults to 'top'.
 * @return array A list of zero-shot tags, formatted as S3Vector resultset (even if not using s3vector index).
 */
function getZeroShotTags($image, $limit = 10, $input = 'title', $model = 'clip', $prefix = 'top') {
	global $db;
	$results = array('vectors' => array());

	//clipthelandscape uses precomputed lables
	if ($model == 'clipthelandscape') {
		if ($input != 'image')
			die("CLIPthelandscape can currently computed for image as input to the model. Text is possible but not implemented");

		if ($prefix != 'top')
			die("CLIPthelandscape can only predict top/context tags");

		//... note using 'clip' is deliberate - the 'CLIP' processor that is ALSO using clipthelandscape to create labels.
		$rows = $db->getAll($sql = "SELECT label,score FROM gridimage_label WHERE model='clip' AND gridimage_id = {$image->gridimage_id}");
		foreach ($rows as $idx => $row) {
                    $results['vectors'][] = [
                        'key' => (string)$idx, //fake id!
                        'metadata' => [
                            'tagtext' => (string)$row['label'],
                        ],
                        'distance' => 1-$row['score'], //score is inverse of distance
                    ];
		}

	//just make easy to compare orginal tags with zero shot!
	} elseif ($model == 'original') {

		$rows = $db->getAll("SELECT tag_id,tag FROM tag_public WHERE prefix='$prefix' AND gridimage_id = {$image->gridimage_id}");
		foreach ($rows as $idx => $row) {
                    $results['vectors'][] = [
                        'key' => (string)$row['tag_id'],
                        'metadata' => [
                            'tagtext' => (string)$row['tag'],
                        ]
                    ];
		}

	} elseif ($model == 'clip') {
		if (!in_array($input, array('image', 'title'))) //technically COULD use comment/description, but unlikly to work!
			die("input not supported for CLIP");

		$vector = getImageEmbeddingById($image->gridimage_id, $input, $model); //supprts both image and title

			//src works as top/subject/prefix anyway!
		$results = getKNNResults($vector, $limit, 'tags_embedding', $prefix);

	} elseif ($model == 'pe') {
		if (!in_array($input, array('image', 'title'))) //technically COULD use comment/description, but unlikly to work!
			die("input not supported for PE");

		$vector = getImageEmbeddingById($image->gridimage_id, $input, $model); //supprts both image and title

			//src works as top/subject/prefix anyway!
		$results = getKNNResults($vector, $limit, 'tags_embedding_pe', $prefix);

	} elseif ($model == 'mpnet') {
		if (!in_array($input, array('title', 'comment')))
			die("input not supported for mpnet");

			//not using using getTextEmbeddingWrapper as for clip only
		$vector = getTextEmbedding($image->{$input}, $model); // can do other models

			//getKNNResults can now do other models, automatically knows what s3vectors index to use!
		$results = getKNNResults($vector, $limit, 'tags_embedding_mpnet', $prefix);

	} else {
		die("unknown model");
	}

	return $results;
}

###########################################

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




//not specific to vectors really, but mostly used with fectors for now!

function rerank_places(&$items, $query) {

        $input = strtolower(trim($query)); //so can be case insensitive
        $query_len = strlen($input);

        foreach($items as $idx => &$row) {
                $bits = explode('/',strtolower($row['name'])); //just name, not country/country

                //see if any part is an exact match - evem bilingual, we want to promote exact matches, over prefix matches
                if (in_array($input, $bits)) {
                        $row['pdist'] = 0; // Perfect match
                        continue;
                }

                $row['pdist'] = 1+levenshtein($input, substr($bits[0].', '.strtolower($row['localities']), 0, $query_len));
                //bilingual name like "Ammanford/Rhydaman"
                if (isset($bits[1]) && $bits[1] != strtolower($row['gr'])) {
                        $row['pdist'] = min($row['pdist'],
                                1+levenshtein($input, substr($bits[1].', '.strtolower($row['localities']), 0, $query_len))
                        );
                }
        }
        unset($row);

    // Sort by 'pdist' (Levenshtein distance) ascending, then by 'distance' ascending
    usort($items, function($a, $b) {
        $pdist_cmp = $a['pdist'] <=> $b['pdist'];

        if ($pdist_cmp === 0) {
            return $a['distance'] <=> $b['distance'];
        }

        return $pdist_cmp;
    });

}

function rerank_items(&$items, $key, $query) {

        $input = strtolower(trim($query)); //so can be case insensitive
        $query_len = strlen($input);

        foreach($items as $idx => &$row) {
		$row['idx'] = $idx; //todo weight?

		$lower = strtolower($row[$key]);
		if ($lower == $input) {
			$row['pdist'] = 0;
                        continue;
                }
                $row['pdist'] = 1+levenshtein($input, substr($lower, 0, $query_len));
        }
        unset($row);

    // Sort by 'pdist' (Levenshtein distance) ascending, then by original order
    usort($items, function($a, $b) {
        $pdist_cmp = $a['pdist'] <=> $b['pdist'];

        if ($pdist_cmp === 0) {
		 return $a['idx'] <=> $b['idx'];
        }

        return $pdist_cmp;
    });

}

//todo, gemini suggests RRF sorting, not tried yet!
