<?php
require_once('geograph/global.inc.php');
init_session();

if (!$USER->registered) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

$db = NewADOConnection($GLOBALS['DSN']);
if (!$db) die('Database connection failed');

// Get last modified time of searches
$lastMod = $db->GetOne("SELECT UNIX_TIMESTAMP(MAX(use_timestamp))
    FROM queries
    WHERE user_id = {$USER->user_id}");

// Check if-modified-since header
if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
    $ifModifiedSince = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
    if ($lastMod <= $ifModifiedSince) {
        header('HTTP/1.1 304 Not Modified');
        exit;
    }
}

// Set last modified header
header('Last-Modified: '.gmdate('D, d M Y H:i:s', $lastMod).' GMT');

$limit = isset($_GET['all']) ? "limit 1000" : "limit 12";

$recentsearchs = $db->GetAll("
    SELECT queries.id,favorite,searchdesc,`count`,use_timestamp,
           searchclass,searchq,displayclass,resultsperpage 
    FROM queries
    LEFT JOIN queries_count using (id)
    WHERE user_id = {$USER->user_id} 
    AND searchuse = 'search'
    ORDER BY use_timestamp DESC,id DESC $limit");

// De-duplicate searches
$seen = array();
foreach ($recentsearchs as $i => $row) {
    $key = "{$row['searchdesc']},{$row['searchq']},{$row['displayclass']},{$row['resultsperpage']}";
    if (isset($seen[$key])) {
        unset($recentsearchs[$i]);
    } else {
        $seen[$key] = true;
	//emulate {$obj.searchdesc|escape:"html"|regex_replace:"/^, /":""|regex_replace:"/(, in [\w ]+ order)/":'</a><small>$1</small>'}
	$recentsearchs[$i]['searchdesc'] = latin1_to_utf8(preg_replace('/(, in [\w ]+ order)/','</a><small>\1</small>', preg_replace('/^, /','', htmlentities($row['searchdesc']))));
        if ($row['searchq'] == "inner join gridimage_query using (gridimage_id) where query_id = ".$row['id']) {
            $recentsearchs[$i]['edit'] = 1;
        }
    }
}

header('Content-Type: application/json');
echo json_encode(array_values($recentsearchs), JSON_PARTIAL_OUTPUT_ON_ERROR);
