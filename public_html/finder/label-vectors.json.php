<?php
// api-label-vectors.php

// Basic setup
if (!isset($ABORT_GLOBAL_EARLY)) {
    $ABORT_GLOBAL_EARLY = true;
}
require_once('../libs/geograph/global.inc.php');
require_once(ROOT_PATH . '/libs/geograph/vectors.inc.php');

// Set headers for JSON response and CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Get labels from the 'labels' GET parameter
$labels_str = isset($_GET['labels']) ? trim($_GET['labels']) : '';

if (empty($labels_str)) {
    echo json_encode(['error' => 'No labels provided.']);
    exit;
}

// Split the comma-separated string into an array of labels
$labels = array_map('trim', explode(',', $labels_str));
$labels = array_filter($labels); // Remove any empty elements

if (empty($labels)) {
    echo json_encode(['error' => 'No valid labels provided after trimming.']);
    exit;
}

$response = [];

foreach ($labels as $label) {
    // Get the vector for the current label
    $vector = getTextEmbeddingWrapper($label);

    if (!empty($vector) && is_array($vector)) {
        // Pack the vector into a binary string using the 'g' format for compatibility
        // with the JavaScript library.
        $binary_vector = pack('g*', ...$vector);
        // Base64 encode the binary data to safely transmit it in JSON
        $response[$label] = base64_encode($binary_vector);
    } else {
        // If no vector is found, return null for that label
        $response[$label] = null;
    }
}

// Return the JSON response
echo json_encode($response);
