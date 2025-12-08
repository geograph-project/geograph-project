<?php

$param = array('provider'=>'open', 'limit'=> 10, "table" => "moderation");

chdir(__DIR__);
require "./_scripts.inc.php";

if ($param['provider'] == 'open' && empty($CONF['openrouter_api_key']))
	exit; //silently die for now

require_once "3rdparty/llm-providers.inc.php"; // Provides getLLMResponse and other functions

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


$prompt = $db->getOne("SELECT content FROM ai_prompt WHERE active=1 AND prompt_name = 'profile-about-test'");

$table = $param['table'];


$query = "select moderation_id,user_id,moderation_status,title,about_yourself from $table inner join user using (user_id)
	 where source = 'user_about' AND ai_class IS NULL LIMIT ".$param['limit'];

$results = $db->getAll($query);

define('QUIET', 1); //llm-providers by default outputs some text!

foreach ($results as $row) {

	$user = latin1_to_utf8($row['about_yourself']);

	print "UserID: {$row['user_id']}\n";
	print "Message: {$row['title']}\n"; //short version!
	if ($row['moderation_status'] != 'pending')
		print "Human: {$row['moderation_status']}\n";
	print "Result: ";

        $r = getLLMResponse($prompt, $user, $param['provider'], $model = 'gpt-oss-safeguard-20b');

	print_r($r);
	print "\n";
	if (!empty($GLOBALS['reasoning']))
		print "Reasoning: {$GLOBALS['reasoning']}\n";
	print "\n".str_repeat('-',80)."\n\n";

	if (!empty($r)) {
		$result = $db->Quote($r);
		$sql = "UPDATE $table SET ai_class = $result, updated=updated WHERE moderation_id = {$row['moderation_id']}";
		$db->Execute($sql);
	}

}

