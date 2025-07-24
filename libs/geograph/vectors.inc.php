<?php

require_once("3rdparty/s3vectors.inc.php"); //defines queryS3Vectors

//todo, move to global config!
$CONF['embed_api'] = 'http://python-embed.dev.svc.cluster.local:8000';
$CONF['s3_vector_bucket'] = 'geograph-vector-bucket';

//for now leave the indexName hardcoded (similarly the manticore index name!)

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

    // Initialize a cURL session
    $ch = curl_init();

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($postData)
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

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
    curl_close($ch);
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
function getImageEmbedding($image) {
    // 1. Get image URL and grab the .jpg
    $url = $image->_getFullpath(false, true);
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

    // Initialize a cURL session
    $ch = curl_init();

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($postData)
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

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
    curl_close($ch);
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
function getKNNResults(array $vector, int $limit = 30, string $index_name = 'label_embedding', string $vector_name = 'label_vector'): array {
    global $rt; // Assuming $rt is a global Manticore client object

    if (!isset($rt) || !is_object($rt) || !method_exists($rt, 'getAll')) {
        error_log('getKNNResults: Manticore client ($rt) is not properly initialized.');
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
                    error_log('getKNNResults: Manticore row missing expected keys (id, label, distance): ' . json_encode($row));
                }
            }
        } else {
            error_log('getKNNResults: Manticore getAll did not return an array.');
            $results = ['http_code' => 500, 'vectors' => []];
        }
    } catch (Exception $e) {
        error_log('getKNNResults: Manticore query error: ' . $e->getMessage());
        $results = ['http_code' => 500, 'vectors' => []];
    }

    return $results;
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
function getZeroShotLabels($image, $limit = 30) {
    global $CONF;

    // 1. Get image embedding using the dedicated function
    $vector = getImageEmbedding($image);
    if (empty($vector)) {
        error_log('getZeroShotLabels: Failed to obtain image vector from getImageEmbedding.');
        return ['http_code' => 500, 'vectors' => []];
    }

    // This will hold the final results in the S3Vector format
    $results = [];

    // 2. Decide whether to use S3Vectors or Manticore (KNN) based on configuration
    if (!empty($CONF['s3_vector_bucket'])) {
        $queryPayload = [
            'vectorBucketName' => $CONF['s3_vector_bucket'],
            'indexName' => 'label-clip', // Hardcoded index name for S3Vectors
            'queryVector' => ['float32' => $vector],
            'topK' => $limit,
            'returnDistance' => true,
            'returnMetadata' => true, // Changed to true to get metadata from S3Vectors as per expected output
        ];

        // Ensure queryS3Vectors function is defined and handles its own errors
        if (function_exists('queryS3Vectors')) {
            $s3vec_raw_response = queryS3Vectors($queryPayload);
            // Validate S3Vector response format to ensure consistency
            if (isset($s3vec_raw_response['http_code']) && is_array($s3vec_raw_response['vectors'])) {
                $results = $s3vec_raw_response;
            } else {
                error_log('getZeroShotLabels: S3Vectors query failed or returned malformed response.');
                $results = ['http_code' => 500, 'vectors' => []];
            }
        } else {
            error_log('getZeroShotLabels: queryS3Vectors function is not defined.');
            $results = ['http_code' => 500, 'vectors' => []];
        }
    } else {
        // Use the getKNNResults function for Manticore if S3 bucket is not configured
        $results = getKNNResults($vector, $limit);
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
    echo "HTTP Status Code: " . ($results['http_code'] ?? 'N/A') . "\n";

    if (!empty($results['vectors']) && is_array($results['vectors'])) {
        echo "Parsed Results:\n";
        foreach ($results['vectors'] as $i => $vector) {
            echo "Result " . ($i + 1) . ":\n";
            echo "  Key: " . ($vector['key'] ?? 'N/A') . "\n";
            if (isset($vector['distance'])) {
                echo "  Distance: " . $vector['distance'] . "\n";
            } else {
                echo "  Distance: N/A\n";
            }
            if (!empty($vector['metadata'])) {
	            echo "  Metadata: " . json_encode($vector['metadata']) . "\n";
            } else {
                echo "  Metadata: N/A\n";
            }
            echo "---\n";
        }
    } else {
        echo "No vectors found for the query or an error occurred.\n";
    }
}
