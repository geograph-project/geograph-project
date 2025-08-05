<?php
// label-vectors.json.php

require_once('../../libs/geograph/global.inc.php');
require_once('../../libs/geograph/vectors.inc.php');

// Set headers for JSON response and CORS
header('Content-Type: application/json');
//header('Access-Control-Allow-Origin: *');
customExpiresHeader(3600*24);

// Input parameters
$labels_str = isset($_GET['labels']) ? trim($_GET['labels']) : '';
$vector_base64 = $_GET['vector'] ?? null;
$image_id = isset($_GET['image_id']) ? intval($_GET['image_id']) : null;
$text_label = $_GET['text_label'] ?? null;
$k = isset($_GET['k']) ? intval($_GET['k']) : 10;
$return_vector = isset($_GET['return_vector']) ? filter_var($_GET['return_vector'], FILTER_VALIDATE_BOOLEAN) : false;

// Handle original functionality for 'labels' parameter
if (!empty($labels_str)) {
    $labels = array_map('trim', explode(',', $labels_str));
    $labels = array_filter($labels);

    if (empty($labels)) {
        echo json_encode(['error' => 'No valid labels provided after trimming.']);
        exit;
    }

    $response = [];
    foreach ($labels as $label) {
        $vector = getTextEmbeddingWrapper($label);
        if (!empty($vector)) {
            $binary_vector = pack('g*', ...$vector);
            $response[$label] = base64_encode($binary_vector);
        } else {
            $response[$label] = null;
        }
    }
    echo json_encode($response);
    exit;
}


$query_vector = null;

// Determine the query vector based on the input
if (!empty($vector_base64)) {
    $binary_vector = base64_decode($vector_base64, true);
    if ($binary_vector !== false) {
        // Unpack the binary data into an array of floats
        $query_vector = array_values(unpack('g*', $binary_vector));
    } else {
        echo json_encode(['error' => 'Invalid base64 vector provided.']);
        exit;
    }
} elseif (!empty($image_id)) {
    $query_vector = getImageEmbeddingById($image_id);
} elseif (!empty($text_label)) {
    $query_vector = getTextEmbeddingWrapper($text_label);
}

// If no query vector could be determined, exit
if (empty($query_vector)) {
    // Check if it was because no input was given
    if (empty($vector_base64) && empty($image_id) && empty($text_label)) {
        echo json_encode(['error' => 'No input provided. Please specify a vector, image_id, or text_label.']);
    } else {
        echo json_encode(['error' => 'Could not determine a query vector for the given input.']);
    }
    exit;
}

// At this point, $query_vector holds the vector to be used for the KNN search.

$results = [];

if (!empty($CONF['s3_vector_bucket'])) {
    // Use S3Vectors for KNN search
    $queryPayload = [
        'vectorBucketName' => $CONF['s3_vector_bucket'],
        'indexName' => 'label-clip', // As per getZeroShotLabels
        'queryVector' => ['float32' => $query_vector],
        'topK' => $k,
        'returnDistance' => true,
        'returnMetadata' => true,
    ];
    $s3_results = queryS3Vectors($queryPayload);
    if ($s3_results['http_code'] == 200) {
        $results = $s3_results['vectors'];
    } else {
        echo json_encode(['error' => 'S3Vectors query failed.', 'details' => $s3_results]);
        exit;
    }
} else {
    // Use Manticore for KNN search
    $manticore_results = getKNNResults($query_vector, $k, 'label_embedding');
    if ($manticore_results['http_code'] == 200) {
        $results = $manticore_results['vectors'];
    } else {
        echo json_encode(['error' => 'Manticore query failed.', 'details' => $manticore_results]);
        exit;
    }
}

// Format the final response
$final_results = [];
foreach ($results as $result) {
    $label = $result['metadata']['label'] ?? 'unknown';
    $item = [
        'label' => $label,
        'distance' => $result['distance']
    ];

    if ($return_vector) {
        // Fetch and encode the vector for this label
        $vector = getTextEmbeddingWrapper($label);
        if (!empty($vector)) {
            $binary_vector = pack('g*', ...$vector);
            $item['vector'] = base64_encode($binary_vector);
        } else {
            $item['vector'] = null;
        }
    }
    $final_results[] = $item;
}

echo json_encode(['status' => 'success', 'results' => $final_results]);

?>
