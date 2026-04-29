<?php

$param = array('provider'=>'open', 'limit'=>50, 'print'=>1, 'ai_model'=>'gpt-oss-safeguard-20b', 'idx'=>11);

chdir(__DIR__);
require "./_scripts.inc.php";

require_once "3rdparty/llm-providers.inc.php"; // Provides getLLMResponse and other functions

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$filepath = __DIR__."/../ai-schema/current-article-cluster"; //adds .1.txt etc!

###################################################################

$idx = $param['idx'];
$iplus = $idx+1;

	###################################################################

	$current_taxonomy = file_get_contents("$filepath.$idx.txt");

$prompt = <<<END
    You are a Senior Curriculum Architect for Geograph.org.uk

    TASK: Reorganize the provided Geographic categories into a
    consistent, hierarchical taxonomy using ONLY these Primary Pillars:

    1. Physical Geography (Land, Water, Climate)
    2. Human Geography: Built Environment (Architecture, Planning, Settlements)
    3. Human Geography: Economic & Infrastructure (Transport, Energy, Industry)
    4. Human Geography: Historical & Cultural (Heritage, Conflict, Traditions)
    5. Mapping, Navigation & Fieldwork (OS Skills, Local Studies, Naming)
    6. Photography & Meta-Content (Competitions, Diariess, Rights)
    7. Natural History & Science (Wildlife, Phenology, Botany)

    REQUIREMENTS:
    1. Every category from the input MUST be mapped to one Pillar.
    2. Format the names consistently as "Pillar: Sub-Category".
    3. Merge redundant categories (e.g., 'Military Buildings' and 'Military Fortifications').
    4. Update the 'description' to be a synthesis of any merged categories.
    5. Output the final list as a structured JSON Dictionary. Nothing else.

END;

	###################################################################

	$user = $current_taxonomy;

if ($param['print']) {
	print "$prompt\n\n$user\n";
	exit;
}

	//$result = getLLMResponse($prompt, $user, $param['provider'], $model = 'gpt-oss-safeguard-20b'); //not stricky a safeguarding task, but it does quite well for classfication, as trained to only provide answer (reasoning, is in the dedidated reasoning reply!
	$result = callOpenRouter($prompt, $user, $max_tokens = 2048*6*3, $param['ai_model']); //calling callOpenRouter directly allows speciging max_tokens!

	if (!empty($GLOBALS['reasoning']))
        	print "Reasoning: {$GLOBALS['reasoning']}\n";

	print "$result\n";

	$json = trim($result,"`json \t\n\r");

	if (!empty($result) && strlen($json) >= strlen($current_taxonomy)) {

		file_put_contents("$filepath.$iplus.txt", "$json\n");
	} else {
		file_put_contents("$filepath.$iplus.failed", $result);
		exit;
	}


