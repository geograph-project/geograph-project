<?php

$param = array('provider'=>'open', 'limit'=>50, 'print'=>1, 'ai_model'=>'gpt-oss-safeguard-20b');

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
ORDER BY rand(42)
LIMIT {$param['limit']}";

//define('QUIET', 1); //llm-providers by default outputs some text!

###################################################################

$results = $db->getAll($query);

$chunks = array_chunk($results, 50);

foreach($chunks as $idx => $results) {
	$iplus = $idx+1;
	if (file_exists("$filepath.$iplus.txt")) {
		print "already done $idx\n";
		continue;
	}

	print "Got ".count($results)." from $table\n";

	###################################################################
	//need to read the current file on each loop!

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
    2. CREATE: If an article doesn't fit ANY existing category, create a NEW, specific category
       based on UK Geography/Curriculum standards (e.g., 'Industrial Archaeology' or 'Coastal Landforms'),
       and following style of existing categories.
    3. MERGE: If you find yourself creating a category that is 90% similar to an existing one, merge them instead.
    4. UPDATE: You may modify the [description] on ongoing basis to encompass the full scope of articles you are seeing.
       If an article is a 'textbook example', and different to existing [examples], add it to [examples] (max 5 per category)
    5. OUTPUT REQUIREMENT: Return just the full updated taxonomy list in JSON. Nothing else.

END;

	###################################################################

	$user = "Articles to classify:\n\n";

	foreach ($results as $row) {
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

	$json = trim($result,"`json \t\n\r");

	if (!empty($result) && strlen($json) >= strlen($current_taxonomy)) {

		file_put_contents("$filepath.$iplus.txt", "$json\n");
	} else {
		file_put_contents("$filepath.$iplus.failed", $result);
		exit;
	}
}

