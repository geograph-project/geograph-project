<?php

// Script parameters
$param = array('offset'=>0, 'batch' => 10, 'print' => false, 'provider'=>'open', 'direction'=>'forward', 'restart'=>false, 'save'=>false,
		'table' => "curated_judge", 'reason'=>true, 'ai_model'=>"google/gemma-4-26b-a4b-it", 'single' =>false, 'max_tokens' => 512);

chdir(__DIR__);
require "./_scripts.inc.php";

#########################################################

require_once "3rdparty/llm-providers.inc.php"; // Provides getLLMResponse and other functions (and now classify_tags_batch)

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


$model_label = "edu-judge"; // Internal progress monitoring key name

// Initialize handler: Multi-modal vision enabled (imagePrompt = true)
$processor = new LLMBatchProcessor($db, $param, $model_label, true);

#########################################################
// 0. Define rows to process

$processor->setQueryCallback(function(&$where, $order, $param) use ($db) {
    $factor = 100;

    // 1. Handle auto-targeting mode for labels needing samples
    if (!empty($param['single'])) {
        if ($param['single'] == 'auto') {
            $param['single'] = $db->getOne("SELECT label,
                COUNT(c.gridimage_id) AS total, SUM(is_gold_standard) AS gold,
                SUM(j.gridimage_id IS NULL) AS todo,
                SUM(j.updated > DATE_SUB(NOW(), INTERVAL 24 HOUR)) AS recent,
                AVG(IF(j.updated > DATE_SUB(NOW(), INTERVAL 24 HOUR), is_gold_standard, NULL)) AS percent
                FROM curated1 c LEFT JOIN curated_judge j USING (label, gridimage_id)
                WHERE cosine IS NOT NULL AND gen > 0
                GROUP BY label
                HAVING (gold > 0 OR (total - todo < 20))
                   AND todo > 0
                   AND NOT (recent > 12 AND percent = 0)
                ORDER BY gold ASC LIMIT 1");
        }
        $where[] = "c.label = " . $db->Quote($param['single']);
        $factor *= 2; // double resolution scale factor for target matching
    }

    $where[] = "c.active = 1 AND c.user_id = 23277";

    return "SELECT gi.gridimage_id, gi.user_id, title, comment, stack, name, feature_tag, critical_feature, cosine
            FROM gridimage_search gi 
            INNER JOIN curated1 c USING (gridimage_id)
            INNER JOIN curated_label l ON (l.name = c.label)
            LEFT JOIN {$param['table']} r ON (r.gridimage_id = gi.gridimage_id AND r.label = c.label)
            WHERE " . implode(" AND ", $where) . " AND r.gridimage_id IS NULL
            GROUP BY name, ROUND(cosine * $factor)
            ORDER BY $order LIMIT " . (int)$param['batch'];
});

#########################################################
// 1. Callback to handle text prompt compilation

$processor->setFormatPromptCallback(function($fields) use ($param, $model_label) {
    // Elegant workaround for row template tags: Feed them cleanly at the top of the user block
    $userText = "### Context Variables\n";
    $userText .= "Target Entity Name: " . latin1_to_utf8($fields['name']) . "\n";
    $userText .= "Target Entity Stack: " . latin1_to_utf8($fields['stack']) . "\n";
    $userText .= "Feature Tag Reference: " . latin1_to_utf8($fields['feature_tag']) . "\n";
    $userText .= "Critical Target Feature: " . latin1_to_utf8($fields['critical_feature']) . "\n\n";

    $userText .= "### Image Meta Context\n";
    $userText .= "Title: " . latin1_to_utf8($fields['title']) . "\n";
    if (!empty($fields['comment'])) {
        $userText .= "Description: " . latin1_to_utf8($fields['comment']) . "\n";
    }

    // Retain debugging diagnostics prints
    print "ID: " . intval($fields['gridimage_id']) . "\n";
    print "Label: " . htmlentities($fields['name']) . "\n";
    print "Title: " . htmlentities($fields['title']) . "\n";

    // Legacy prompt library save integration hook compatibility support
    if (!empty($param['save'])) {
        $examples = array(
            '{{stack}}'            => $fields['stack'],
            '{{name}}'             => $fields['name'],
            '{{feature_tag}}'      => $fields['feature_tag'],
            '{{critical_feature}}' => $fields['critical_feature']
        );
        save_user_prompt($model_label, $userText, $examples);
    }

    return $userText;
});

#########################################################
// 2. Callback to handle saving the deeply nested decoded JSON scores

$processor->setSaveDataCallback(function($json, $fields, $param) use ($db) {
    if (empty($json)) {
        return;
    }

    $updates = array();
    $updates['reasoning']        = $json['reasoning'] ?? '';
    $updates['accuracy']         = $json['scores']['accuracy'] ?? 0;
    $updates['prominence']       = $json['scores']['prominence'] ?? 0;
    $updates['validity']         = $json['scores']['validity'] ?? 0;
    $updates['is_gold_standard'] = !empty($json['is_gold_standard']) ? 1 : 0;

    $updates['gridimage_id']     = $fields['gridimage_id'];
    $updates['label']            = $fields['name'];
    $updates['ai_model']         = $param['ai_model'];

    $columns = array_keys($updates);
    $sql = 'INSERT INTO ' . $param['table'] . ' SET `' . implode('` = ?,`', $columns) . '` = ?' .
           ' ON DUPLICATE KEY UPDATE `' . implode('` = ?,`', $columns) . '` = ?';

    $payload = array_merge(array_values($updates), array_values($updates));
    $db->Execute($sql, $payload) or die("$sql\n\n" . $db->ErrorMsg() . "\n");
});

#########################################################
// Run engine workflow pipeline

$processor->run();

