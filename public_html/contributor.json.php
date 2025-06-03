<?php
/**
 * GeoGraph Contributor JSON Endpoint for Select2
 *
 * Outputs contributor data in JSON format suitable for Select2.
 * Based on the search logic from finder/contributors.php
 */

require_once('../libs/geograph/global.inc.php'); // Adjusted path for being in public_html

// No session needed for this JSON endpoint usually, but global.inc.php might start one.
// If it causes issues, investigate if session_write_close() is needed early.

$results_array = array();
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if (!empty($q)) {
    // Initialize Sphinx wrapper
    $sphinx = new sphinxwrapper($q);
    // It's good practice to set a reasonable page size for autocomplete
    $sphinx->pageSize = 15; // Or another suitable limit for autocomplete suggestions

    // Process the query (this might internally call returnIds or prepare for it)
    // Depending on sphinxwrapper's exact behavior, direct call to returnIds might be what's needed.
    // The original finder/contributors.php calls processQuery() then returnIds().
    $sphinx->processQuery();
    $ids = $sphinx->returnIds(1, 'user'); // Get page 1 of results from 'user' index

    if (!empty($ids)) {
        $db = GeographDatabaseConnection(true); // Read-only connection

        // Sanitize IDs just in case, though Sphinx should return integers
        $sanitized_ids = array_map('intval', $ids);
        $id_list_string = implode(",", $sanitized_ids);

        if (!empty($id_list_string)) { // Ensure string is not empty after sanitization
            // Fetch user details from the database
            // Using ADODB_FETCH_ASSOC for easier handling
            $prev_fetch_mode = $db->SetFetchMode(ADODB_FETCH_ASSOC);

            $sql = "SELECT user.user_id, user.nickname, user.realname
                    FROM user
                    WHERE user.user_id IN ({$id_list_string})
                    ORDER BY FIELD(user.user_id, {$id_list_string})"; // Maintain Sphinx relevance order

            $contributors_db = $db->GetAll($sql);
            $db->SetFetchMode($prev_fetch_mode);

            if ($contributors_db) {
                foreach ($contributors_db as $row) {
                    $text = $row['realname'];
                    if (!empty($row['nickname'])) {
                        $text .= " (" . $row['nickname'] . ")";
                    }
                    $text .= " [" . $row['user_id'] . "]";

                    $results_array[] = array(
                        'id'   => $row['user_id'], // Standard to use the actual ID for 'id'
                        'text' => $text
                    );
                }
            }
        }
    }
}

// Set content type header to application/json
header('Content-Type: application/json');
echo json_encode(array('results' => $results_array)); // Select2 expects results under a 'results' key for AJAX
exit;

?>
