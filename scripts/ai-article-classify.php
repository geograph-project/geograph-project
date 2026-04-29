<?php

$param = array('provider'=>'open', 'limit'=>50, 'print'=>1, 'ai_model'=>'gpt-oss-safeguard-20b', 'idx'=>12, 'table'=>'article_ai');

chdir(__DIR__);
require "./_scripts.inc.php";

require_once "3rdparty/llm-providers.inc.php"; // Provides getLLMResponse and other functions

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$filepath = __DIR__."/../ai-schema/current-article-cluster"; //adds .1.txt etc!

###################################################################

$table = "article";
$pkey = 'article_id';

$query = "SELECT article_id, url, a.title, extract
FROM article a inner join article_cat using (article_cat_id)
WHERE approved>0 AND licence != 'none' AND type='info'
ORDER BY article_id
LIMIT {$param['limit']}";

//define('QUIET', 1); //llm-providers by default outputs some text!

###################################################################

$results = $db->getAll($query);

$chunks = array_chunk($results, 50);

foreach($chunks as $results) {

	print "Got ".count($results)." from $table\n";

	###################################################################

//this is the file to LOAD
$idx = $param['idx'];

	if (!file_exists("$filepath.$idx.txt"))
		die("Missing $filepath.$idx.txt\n");

	$current_taxonomy = file_get_contents("$filepath.$idx.txt");

$prompt = <<<END

    You are an expert curriculum specialist for Geograph.org.uk.
    Current Taxonomy: {$current_taxonomy}

    Task:
    1. CLASSIFY: Assign each article into the best category from the Taxonomy.
       Do not classify based on the 'Location' (e.g., 'Scotland' or 'London').
       Classify based on the 'Subject' or 'Feature' (e.g., 'Castles' or 'Urban Landmarks').
    2. OUTPUT REQUIREMENT: Return just the classified articles, in JSON. Nothing Else.

    [{article_id:1234, category:"Human Geography: Historical & Cultural: Cemeteries"},...]

END;

	###################################################################

	$user = "Articles to classify:\n\n";

	foreach ($results as $row) {
		$user .= "article_id: ".intval($row['article_id'])."\n";
		$user .= "title: ".latin1_to_utf8($row['title'])."\n";
		if (!empty($row['extract']))
			$user .=  "extract: ".latin1_to_utf8($row['extract'])."\n";
		$user .= "\n";
	}

if ($param['print']) {
	print "$prompt\n\n$user\n";
	exit;
}

	//$result = getLLMResponse($prompt, $user, $param['provider'], $model = 'gpt-oss-safeguard-20b'); //not stricky a safeguarding task, but it does quite well for classfication, as trained to only provide answer (reasoning, is in the dedidated reasoning reply!
	$result = callOpenRouter($prompt, $user, $max_tokens = 2048*6*3, $param['ai_model']); //calling callOpenRouter directly allows speciging max_tokens!

	if (!empty($GLOBALS['reasoning']))
        	print "Reasoning: {$GLOBALS['reasoning']}\n";

	print "$result\n";

	$json = json_decode(trim($result,"`json \t\n\r"),TRUE);


	foreach($json as $row) {
		//going to be better to store seperate
		$bits = explode(':', $row['category'], 2);

                $updates = array();
                $updates['article_id'] = $row['article_id'];
                $updates['pillar'] = trim($bits[0]);
                $updates['category'] = trim($bits[1]);
                $updates['ai_model'] = $param['ai_model'];

                $db->Execute($sql = 'INSERT INTO '.$param['table'].' SET `'.implode('` = ?,`',array_keys($updates)).'` = ?'.
                         ' ON DUPLICATE KEY UPDATE `'.implode('` = ?,`',array_keys($updates)).'` = ?',
                        array_merge(array_values($updates),array_values($updates))) or die("$sql\n\n".$db->ErrorMsg()."\n");
	}
}

