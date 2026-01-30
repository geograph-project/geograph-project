<?php

$param = array('provider'=>'open', 'limit'=>1, 'sleep'=>0, 'translit'=>false);

chdir(__DIR__);
require "./_scripts.inc.php";

require_once "3rdparty/llm-providers.inc.php"; // Provides getLLMResponse and other functions

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

###################################################################

$table = "article_parsed"; //were SAVES the result!
$pkey = 'article_id';

$query = "SELECT article_id, url, a.title, content
FROM article a
LEFT JOIN article_parsed USING (article_id)
WHERE a.title LIKE '%symbol%' and a.title NOT like '%quiz%' AND parsed_id IS NULL AND a.approved > 0
LIMIT {$param['limit']}";

define('QUIET', 1); //llm-providers by default outputs some text!

//system prompt
$prompt = $db->getOne("SELECT content FROM ai_prompt WHERE active=1 AND prompt_name = 'article-symbol-extractor'");

###################################################################


if (true) { //make while!

	$results = $db->getAll($query);
	print "$query;\n";

	print "Got ".count($results)." from $table\n";

	###################################################################

	$a = 0;
	foreach ($results as $row) {
		print "Article: {$row['title']}\n";
		$user = latin1_to_utf8($row['content']);

		print "Result: ";
		//$result = getLLMResponse($prompt, $user, $param['provider'], $model = 'gpt-oss-safeguard-20b'); //not stricky a safeguarding task, but it does quite well for classfication, as trained to only provide answer (reasoning, is in the dedidated reasoning reply!
		$result = callOpenRouter($prompt, $user, $max_tokens = 2048*6, $model = 'gpt-oss-safeguard-20b'); //calling callOpenRouter directly allows speciging max_tokens!

		if (!empty($GLOBALS['reasoning']))
	        	print "Reasoning: {$GLOBALS['reasoning']}\n";

		print "$result\n";

		$json = json_decode(trim($result,"`json \t\n\r"), TRUE);

		if ($json === null) {
			file_put_contents("fail_article_{$row['article_id']}_reason.txt", $GLOBALS['reasoning']);
			file_put_contents("fail_article_{$row['article_id']}_reply.txt", $result);
			echo "JSON Error: " . json_last_error_msg();
			die("unable to parse json {$row['title']} [{$row['article_id']}]\n");
		} elseif (count($json) === 0) {
		    // This is a valid article with no data
		    echo "Article {$row['article_id']} processed: No symbols found (Intro/Overview page??).\n";
		    $json[] = array('symbol'=>'none','note'=>'No symbols found in article');
		}

		//should be: [ {"category": "...", "symbol":"...", "gridimage_id":id, "grid_reference": "...", "note": "..."} ]

		foreach ($json as $item) {
			$updates = array();
			foreach($item as $key => $value)
				//todo, check a valid key! (incase AI gets it worng!
				$updates[$key] = $value;

			$updates['title'] = $row['title']; //duplicate this, just in case
			$updates[$pkey] = $row[$pkey];

			$db->Execute($sql = "INSERT INTO $table SET `".implode('` = ?,`',array_keys($updates))."` = ?", array_values($updates));
			$a += $db->Affected_Rows();
		}

		print "Rows= $a\n".str_repeat('-',80)."\n\n";

		if (!empty($param['sleep']))
			sleep($param['sleep']);
	}
}

