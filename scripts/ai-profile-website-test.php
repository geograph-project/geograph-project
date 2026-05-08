<?php

$param = array('provider'=>'open', 'limit'=> 10, 'save'=>false);

chdir(__DIR__);
require "./_scripts.inc.php";

if ($param['provider'] == 'open' && empty($CONF['openrouter_api_key']))
	exit; //silently die for now

require_once "3rdparty/llm-providers.inc.php"; // Provides getLLMResponse and other functions

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

###################################################################

$prompt_name = "profile-website-test"; //our intrnal name, not the AI model!

$prompt = $db->getOne("SELECT content FROM ai_prompt WHERE active=1 AND prompt_name = '$prompt_name'");

$few = $db->getCol("select concat(moderation_status,': ',title) from moderation where source = 'user_website' and moderation_status != 'pending' group by title order by moderation_status limit 100");

$prompt = str_replace('{few}',implode("\n",$few),$prompt);

//print $prompt;
//exit;

###################################################################

$query = "select moderation_id,user_id,moderation_status,title, website from moderation inner join user using (user_id)
	 where source = 'user_website' AND ai_class IS NULL LIMIT ".$param['limit'];

$query = "($query) UNION ALL (select moderation_id,user_id,moderation_status,title, media_url as website from moderation
	where source = 'link' and ai_class IS NULL LIMIT {$param['limit']})";

$results = $db->getAll($query);

print "Got ".count($results)."\n";

###################################################################

define('QUIET', 1); //llm-providers by default outputs some text!

foreach ($results as $row) {

	$user = latin1_to_utf8($row['website']);

	if ($param['save']) {
		$examples = array(
                        '{few}' => implode("\n",$few),
		);
		save_user_prompt($prompt_name, $user, $examples);
	}

	print "UserID: {$row['user_id']}\n";
	print "URL: {$row['website']}\n";
	print "Message: {$row['title']}\n"; //short version!
	print "Human: {$row['moderation_status']}\n";
	print "Result: ";

        $r = getLLMResponse($prompt, $user, $param['provider'], 'gpt-oss-safeguard-20b');

	print_r($r);
	print "\n";
	print "\n".str_repeat('-',80)."\n\n";

	if (!empty($r)) {
		$result = $db->Quote($r);
		$sql = "UPDATE moderation SET ai_class = $result, updated=updated WHERE moderation_id = {$row['moderation_id']}";
		$db->Execute($sql);
	}

}

