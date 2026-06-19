<?php

// Script parameters
$param = array('batch' => 10, 'print' => true, 'provider'=>'open', 'loops'=>10, 'direction'=>'forward', 'shard'=>false, 'max_tokens'=>2048*2,
		'table' => "gridimage_named", 'reason'=>false, 'ai_model'=>'google/gemma-4-26b-a4b-it', 'save'=>false, 'encode'=>false, 'sleep'=>false);

chdir(__DIR__);
require "./_scripts.inc.php";
require_once "3rdparty/llm-providers.inc.php";
require_once "geograph/llmbatchprocessor.class.php";

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

// Initialize the generalized handler
$processor = new LLMBatchProcessor($db, $param, "name-extract", true);

#########################################################
// 0. Define the rows to process

$processor->setQueryCallback(function($where, $order, $param) {
    return "SELECT gi.gridimage_id, user_id, title, comment, tags, gi.grid_reference, place, county, country
            FROM gridimage_search gi INNER JOIN images_place_joined USING (gridimage_id)
            LEFT JOIN {$param['table']} r USING (gridimage_id)
            WHERE " . implode(" AND ", $where) . " AND r.gridimage_id IS NULL ORDER BY $order LIMIT " . (int)$param['batch'];
});

#########################################################
// 1. Callback to handle text prompt compilation

$processor->setFormatPromptCallback(function($fields) {
    $user = "Data to audit:\n\n";
    $user .= "Title: " . latin1_to_utf8($fields['title']) . "\n";
    if (!empty($fields['comment'])) {
        $user .= "Description: " . preg_replace('/\s+/', ' ', latin1_to_utf8($fields['comment'])) . "\n";
    }

    $contexts = [];
    if (!empty($fields['tags'])) {
        $tags = [];
        foreach (explode("?", $fields['tags']) as $tag) {
            if (strpos($tag, 'type:') === 0 || strpos($tag, 'camera:') === 0) continue;
            if (strpos($tag, 'top:') === 0) {
                $contexts[] = str_replace('top:', '', $tag);
            } else {
                $tags[] = trim($tag);
            }
        }
        if (!empty($tags)) {
            $user .= "Tags: " . implode('; ', $tags) . "\n";
        }
    }

    $user .= "\n\nContextual Data:\n";
    $user .= "Grid Ref: " . $fields['grid_reference'] . "\n";
    $user .= "Context County: " . latin1_to_utf8($fields['county']) . "\n";
    $user .= "Context Country: " . latin1_to_utf8($fields['country']) . "\n";
    if (!empty($contexts)) {
        $user .= "Geographical Contexts: [" . strtolower(implode(']; [', $contexts)) . "]\n";
    }

    return $user;
});

###############################################
// 2. Callback to process decoded JSON response metrics back into your database

$processor->setSaveDataCallback(function($json, $fields, $param) use ($db) {

    // Resilient fix for LLMs wrapping the result in an accidental double outer array [[ ... ]]
    // If it's a double array, it will have exactly 1 item, and its first child will be indexed at 0
    if (is_array($json) && count($json) === 1 && isset($json[0][0]) && is_array($json[0])) {
        $json = $json[0];
    }

    if (empty($json))
        $json = array(array('formal_name' => 'none', 'category' => 'other'));

    foreach ($json as $result) {
        $updates = $result;
        $updates['gridimage_id'] = $fields['gridimage_id'];
        $updates['ai_model']     = $param['ai_model'];

        $sql = 'INSERT INTO ' . $param['table'] . ' SET `' . implode('` = ?,`', array_keys($updates)) . '` = ?';

        $db->Execute($sql, array_values($updates)) or die("$sql\n\n" . $db->ErrorMsg() . "\n");
    }
});

###############################################
// Run the core workflow engine

$processor->run();

