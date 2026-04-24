<?php

// Script parameters
$param = array('offset'=>0, 'limit' => 1, 'print' => false, 'provider'=>'open', 'keyword' => '%listed%building%',
		'table' => "spc_primary_subject", 'reason'=>true, 'id'=>false, 'ai'=>false, 'encode'=>false); //encode param is jus a test!

chdir(__DIR__);
require "./_scripts.inc.php";

#########################################################

require_once "3rdparty/llm-providers.inc.php"; // Provides getLLMResponse and other functions (and now classify_tags_batch)

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

#########################################################

//$aiModel = // e.g., "google/gemini-2.0-flash-001" or "openai/gpt-4o-mini"

$aiModel = "openrouter/free"; //in theory, it will route image requests too! (seems to route to gemma, which is getting ratelimted!)

$aiModel = "nvidia/nemotron-nano-12b-v2-vl:free";
$aiModel = "mistralai/mistral-small-3.1-24b-instruct";
$aiModel = "google/gemini-2.5-flash-lite";
//	$aiModel = "google/gemma-3-27b-it";

if (!empty($param['ai']))
	$aiModel = $param['ai'];


$model = "tag_cluster"; //this is OUR model label! (for the system prompt)

#########################################################

//PROTOTPE for one 'precluster'


$data = $db->getAssoc("select tagtext,`count` from tag_stat where tagtext like ".$db->Quote($param['keyword']));

###################################################

    $prompt = $db->getOne("SELECT content FROM ai_prompt WHERE active=1 AND prompt_name = '$model'");

    if ($param['print']) {
        print "$prompt\n";
    }

$done = 0;
//foreach ($labels as $place => $rows) {

	$user = json_encode($data);

    if ($param['print']) {
        print "$user\n";
	if ($param['print'] == 2)
		exit;
    }

	$response = callOpenRouter($prompt, $user, 512*16, $aiModel); //so can specify max_toksn

	print "RESPONSE: $response\n\n";

		 if ($param['reason'] && !empty($GLOBALS['reasoning']))
		         print "Reasoning: {$GLOBALS['reasoning']}\n";

	$json = json_decode(trim($response,"`json \t\n\r"), TRUE);


###################################################
exit;

	if (!empty($json)) {
		foreach($json as $row) {
			for($q=1;$q<count($row);$q++) {
				$updates = array();

		                $updates['canonical_subject'] = $row[0];
		                $updates['place'] = preg_replace('/^(Near |Former )/','',$place);
		                $updates['primary_subject'] = $row[$q];
				$updates['ai_model'] = $aiModel; //we probably going to evolve this over time!

        		        $db->Execute($sql = 'INSERT IGNORE INTO '.$param['table'].' SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',
                        		 array_values($updates)) or die("$sql\n\n".$db->ErrorMsg()."\n");
			}
		}
	}
	$done++;
	if ($done == $param['limit'])
		exit;
//}

