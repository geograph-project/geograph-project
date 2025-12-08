<?php

$param = array('provider'=>'open', 'limit'=> 10);

chdir(__DIR__);
require "./_scripts.inc.php";

require_once "3rdparty/llm-providers.inc.php"; // Provides getLLMResponse and other functions

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

###################################################################

$prompt = $db->getOne("SELECT content FROM ai_prompt WHERE active=1 AND prompt_name = 'profile-name-test'");

$few = $db->getCol("select concat(moderation_status,': ',title) from moderation where source = 'user' and moderation_status != 'pending' group by title order by moderation_status limit 100");

$prompt = str_replace('{few}',implode("\n",$few),$prompt);

//print $prompt;
//exit;

###################################################################

$query = "select moderation_id,user_id,moderation_status,title, realname from moderation inner join user using (user_id)
	 where source = 'user' AND ai_class IS NULL LIMIT ".$param['limit'];

$results = $db->getAll($query);

print "Got ".count($results)."\n";

###################################################################

define('QUIET', 1); //llm-providers by default outputs some text!

foreach ($results as $row) {

	$user = latin1_to_utf8($row['realname']);

	print "UserID: {$row['user_id']}\n";
	print "Message: {$row['title']}\n"; //short version!
	print "Human: {$row['moderation_status']}\n";
	print "Result: ";

        $r = getLLMResponse($prompt, $user, $param['provider'], $model = 'gpt-oss-safeguard-20b');

	print_r($r);
	print "\n";
	print "\n".str_repeat('-',80)."\n\n";

	if (!empty($r)) {
		$result = $db->Quote($r);
		$sql = "UPDATE moderation SET ai_class = $result, updated=updated WHERE moderation_id = {$row['moderation_id']}";
		$db->Execute($sql);
	}

}

