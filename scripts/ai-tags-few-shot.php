<?php

// Script parameters
$param = array('offset'=>0, 'batch' => 10, 'print' => false, 'provider'=>'open', 'loops'=>10, 'fatal'=>true);

chdir(__DIR__);
require "./_scripts.inc.php";

#########################################################

require_once "3rdparty/llm-providers.inc.php"; // Provides getLLMResponse and other functions (and now classify_tags_batch)

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

echo "Starting tag classification process in batches...\n";

// Query conditions for selecting tags to classify
$where = array();
$where[] = "classification IS NULL";
$where[] = "status=1";
//$where[] = "prefix in ('top','subject','type','bucket')";
$where[] = "prefix NOT in ('camera','milestoneid')";
$order = "tag_id ASC";

#########################################################

// Main script logic: process tags in batches
$offset = $param['offset'];
$loops = 0;
while (true) {
    echo "Fetching batch $loops from offset {$offset}...\n";

    $sql = "SELECT tag_id, IF(prefix NOT in ('','top','subject','type','bucket','category','term'), CONCAT(prefix,':',tag), tag) AS tagtext FROM tag WHERE " . implode(" AND ", $where) . " ORDER BY {$order} LIMIT {$param['batch']} OFFSET {$offset}";
    $rs = $db->Execute($sql);

    if ($rs->EOF) {
        break;
    }

    $tags_to_classify = [];
    while (!$rs->EOF) {
        $tags_to_classify[] = ['tag_id' => $rs->fields['tag_id'], 'tag' => latin1_to_utf8(preg_replace('/ s\b/', 's', $rs->fields['tagtext']))];
        $rs->MoveNext();
    }

    $response = classify_tags_batch($tags_to_classify, $param['provider'], $param['print']);

    $classified_tags = null;
    if (is_string($response)) {
        $classified_tags = json_decode($response, true);

	    if (empty($classified_tags)) {

print "FAILED JSON EXTRACT, trying line by line!\n";
var_dump($response);

		//the LLM may make 'typo' in creating JSON, but might still be SOME usable data
		$classified_tags = array();
		//try decoding line by line - some will fail! but still extract the valid json lines
		foreach(explode("\n",$response) as $line) {
			if (preg_match('/^\s*\{.+\},?\s*$/',$line)) {
				$d = json_decode(trim($line,' ,'), true);
				if (!empty($d) && is_array($d) && !empty($d['class'])) {
					$classified_tags[] = $d;
				}
			}
		}
print_r($classified_tags);
	    }
    }


    if (empty($classified_tags)) {
        echo "Failed to classify batch at offset {$offset}. Skipping.\n";

	if ($param['fatal']) {
		var_dump($response);
		exit;
	}

        $offset += $param['batch'];
        continue;
    }

    $db->BeginTrans();
    $tags_processed_in_batch = 0;

    foreach ($classified_tags as $classified_item) {
        $original_tag_id = null;
        foreach ($tags_to_classify as $original_tag_item) {
            if ($original_tag_item['tag'] === $classified_item['tag']) {
                $original_tag_id = $original_tag_item['tag_id'];
                break;
            }
        }

        if ($original_tag_id) {
            $classification = trim($classified_item['class'], '[]');
            echo "Updating tag_id: {$original_tag_id} -> \"{$classified_item['tag']}\" with Classification: {$classification}\n";
            $update_sql = "UPDATE tag SET classification = ?, updated=updated WHERE tag_id = ?";
            $db->Execute($update_sql, [$classification, $original_tag_id]);
            $tags_processed_in_batch++;
        } else {
            error_log("Could not find original tag_id for classified tag: " . $classified_item['tag']);
        }
    }

    $db->CommitTrans();

    echo "Batch $loops of {$tags_processed_in_batch} tags processed successfully.\n";

    //if ($tags_processed_in_batch < $param['batch']) { --this aborts if a few failed tags
    if (count($tags_to_classify) < $param['batch']) { //-- really just want to abort if the 'last' small batch
        break;
    }

    $loops++;
    if ($loops == $param['loops']) {
	break;
    }
    //this is was a mstake by code generator - should only increment offset on failure
//    $offset += $param['batch'];
}

echo "All tags have been classified. Script finished.\n";
$db->Close();


