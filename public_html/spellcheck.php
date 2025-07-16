<?php
require_once('geograph/global.inc.php');

// The client-side spell checker will send a POST request with a JSON payload.
$request = json_decode(file_get_contents('php://input'), true);

// Get the list of words to check from the request.
$words = $request['words'];

// Create a database connection.
$db = NewADOConnection($DSN);

// Query the database for the words.
$sql = "SELECT word FROM dictionary WHERE word IN ('" . implode("','", $words) . "')";
$result = $db->GetAll($sql);

// Create a response object.
$response = array();

// Create a set of the words found in the dictionary.
$found_words = array();
foreach ($result as $row) {
    $found_words[] = $row['word'];
}

// For each word, check if it was found in the dictionary.
foreach ($words as $word) {
    if (!in_array($word, $found_words)) {
        // If the word was not found, add it to the response with an empty array of suggestions.
        $response[$word] = array();
    }
}

// Set the content type to JSON and send the response.
header('Content-Type: application/json');
echo json_encode($response);
