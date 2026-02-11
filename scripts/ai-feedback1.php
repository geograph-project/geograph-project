<?php

$param = array('provider'=>'open', 'table'=>'moderation_all', 'column'=>'moreabout', 'limit'=>1, 'sleep'=>0);

chdir(__DIR__);
require "./_scripts.inc.php";

require_once "3rdparty/llm-providers.inc.php"; // Provides getLLMResponse and other functions

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

###################################################################

$table = $param['table'];

$pkey = "auto_id"; //primary key

//$query, NEEDS `$pkey`, `website` (other columns optional)
$query = "SELECT t.$pkey, {$param['column']}
	FROM {$table} t
	LEFT JOIN survey_parsed p ON (p.$pkey = t.$pkey AND p.src_table = '$table' AND p.src_col = '{$param['column']}')
	WHERE p.result_id IS NULL AND LENGTH({$param['column']}) > 5
	LIMIT ".$param['limit'];

$results = $db->getAll($query);

print "$query;\n";

print "Got ".count($results)." from $table\n";

###################################################################
// NEW, expanded prompt!

define('QUIET', 1); //llm-providers by default outputs some text!

$prompt = $db->getOne("SELECT content FROM ai_prompt WHERE active=1 AND prompt_name = 'feedback1'");

foreach ($results as $row) {

	print "\n".str_repeat('-',80)."\n\n";

	////////////////////

	foreach ($row as $key=>$value)
		if ($key != $pkey) //probably doesnt mean much!
			print "$key: $value\n";

	$updates = array();

	////////////////////

	$user =  latin1_to_utf8($row[$param['column']]);

	print "Result: ";
	$result = getLLMResponse($prompt, $user, $param['provider'], $model = 'gpt-oss-safeguard-20b'); //not strickyl a safeguarding task, but it does quite well for classfiication, as trained to onnly profile answer (reasoning, is in teh dedidated reeasoning reply!
	print "$result\n";

	$json = json_decode(trim($result,"`json \t\n\r"), TRUE);

        $updates = array();
	$updates['src_table'] = $table;
	$updates['auto_id'] = $row['auto_id'];
	$updates['src_col'] = $param['column'];

	if (empty($json) || !isset($json['sentiment'])) {
                $updates['sentiment'] = '';
                $updates['type'] = '';
	        $updates['phrase'] = 'failed to decode json';

	        $update = "INSERT INTO survey_parsed SET `".implode('` = ?,`',array_keys($updates))."` = ?";
	        $db->Execute($update, array_values($updates));
        } else {
		$updates['sentiment'] = $json['sentiment'];

		if (empty($json['actionable_items'])) {
			$updates['type'] = '';
	                $updates['phrase'] = 'none';

		        $update = "INSERT INTO survey_parsed SET `".implode('` = ?,`',array_keys($updates))."` = ?";
		        $db->Execute($update, array_values($updates));
		}
		else foreach($json['actionable_items'] as $r) {
                	$updates['type'] = $r['type'];
                	$updates['phrase'] = $r['extracted_phrase'];

		        $update = "INSERT INTO survey_parsed SET `".implode('` = ?,`',array_keys($updates))."` = ?";
		        $db->Execute($update, array_values($updates));
	        }
	}

	print "\n".str_repeat('-',80)."\n\n";

	if (!empty($param['sleep']))
		sleep($param['sleep']);
}

