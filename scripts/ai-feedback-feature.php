<?php

$param = array('provider'=>'open', 'table'=>'moderation_all', 'column'=>'moreabout', 'limit'=>25, 'sleep'=>0, 'translit'=>false);

chdir(__DIR__);
require "./_scripts.inc.php";

require_once "3rdparty/llm-providers.inc.php"; // Provides getLLMResponse and other functions

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

###################################################################

$table = "survey_parsed";
$pkey = 'result_id';
$query = "SELECT result_id, phrase FROM survey_parsed WHERE feature IS NULL AND phrase != 'none' ORDER BY RAND() LIMIT {$param['limit']}";

define('QUIET', 1); //llm-providers by default outputs some text!

//system prompt
$prompt = $db->getOne("SELECT content FROM ai_prompt WHERE active=1 AND prompt_name = 'feedback-feature'");

###################################################################

if (true) { //make while!

	$results = $db->getAll($query);

	print "$query;\n";

	print "Got ".count($results)." from $table\n";

	###################################################################

	$input = array();
	$lookup = array();
	foreach ($results as $row) {

		print "\n".str_repeat('-',80)."\n\n";

		////////////////////

		foreach ($row as $key=>$value)
			if ($key != $pkey) //probably doesnt mean much!
				print "$key: $value\n";

		$clean = preg_replace('/\s+/',' ',$row['phrase']);

		if ($param['translit'])
			//keep it simple! (althoug intended to use only after main run has completed without it!
			$clean = trim(translit_to_ascii($clean, "UTF-8"));
		else
			//todo, maybe its actually utf8 already
			$clean = trim(latin1_to_utf8($clean));


		$input[] = "* $clean\n";
		$lookup[$clean] = $row[$pkey];
	}

	###################################################################

	$user = "Phrases:\n\n".implode("\n", $input);

	print "Result: ";
	$result = getLLMResponse($prompt, $user, $param['provider'], $model = 'gpt-oss-safeguard-20b'); //not stricky a safeguarding task, but it does quite well for classfication, as trained to only provide answer (reasoning, is in the dedidated reasoning reply!


if (!empty($GLOBALS['reasoning']))
        print "Reasoning: {$GLOBALS['reasoning']}\n";

	print "$result\n";

	$json = json_decode(trim($result,"`json \t\n\r"), TRUE);

	//should be: [ { "phrase": "...", "feature": "..." }, ]

	foreach ($json as $item) {
		$updates = array();
		$clean = trim($item['phrase']);
		if (isset($lookup[$clean])) {
			$idx = $lookup[$clean];
			$updates['feature'] = trim($item['feature']);

			$where = "$pkey = $idx"; //make sure doesnt contain ?

			$db->Execute($sql = "UPDATE $table SET `".implode('` = ?,`',array_keys($updates))."` = ? WHERE $where", array_values($updates));
		} else {
			print "unable to find Idx for $clean\n";
		}
	}

	print "\n".str_repeat('-',80)."\n\n";


	if (!empty($param['sleep']))
		sleep($param['sleep']);
}

