<?php

$param = array('provider'=>'open', 'table'=>'moderation_all', 'limit'=>10, 'sleep'=>0);

chdir(__DIR__);
require "./_scripts.inc.php";

require_once "3rdparty/llm-providers.inc.php"; // Provides getLLMResponse and other functions

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

###################################################################

$table = $param['table'];

$pkey = "moderation_id"; //primary key

$status = "ai_assessment IN ('mismatch','normal','personal','other','unrelated')";
//$status = "ai_assessment IN ('mismatch')";
//$status = "ai_assessment IN ('gone')";
//$status = "website like 'http://flickr.com/%'";

$status .= " AND page_title IS NULL";

//$query, NEEDS `$pkey`, `website` (other columns optional)
$query = "SELECT $pkey, user.website AS website, user_id, moderation_status FROM $table inner join user using (user_id)
	 inner join user_stat using (user_id)
		  LEFT JOIN fetch_domain fd ON (domain = substring_index(website,'/',3))
	  WHERE source = 'user_website' AND $status AND ( fd.domain IS NULL OR fd.last < DATE_SUB(NOW(), INTERVAL 1 DAY) )
	  ORDER BY user_id DESC LIMIT ".$param['limit'];

####################

$results = $db->getAll($query);

print "$query;\n";

print "Got ".count($results)." from $table\n";

###################################################################

define('QUIET', 1); //llm-providers by default outputs some text!

$prompt = $db->getOne("SELECT content FROM ai_prompt WHERE active=1 AND prompt_name = 'website-content-summary'");


foreach ($results as $row) {

	$url = latin1_to_utf8($row['website']);
	$domain = substring_index($row['website'],'/',3);
	if (!empty($done[$domain]))
		continue;

	//save to remove from query
	$db->Execute("INSERT INTO fetch_domain SET domain = ?, first=NOW() ON DUPLICATE KEY UPDATE fetches=fetches+1", array($domain));
	//but also stop duplicates in THIS run
	$done[$domain]=1;

	///////////////////////////////

	print "Fetching $url";
	$result = fetchJinaContent($url);
	if ($result['success']) {
		print " success:\n";
		$user = $result['content'];
		print "$user\n"; //for debug
	} else {
		print " failed\n";
		print_r($result); //for debug
		continue;
	}

	print "\n".str_repeat('-',80)."\n\n";

	///////////////////////////////

	foreach ($row as $key=>$value)
		if ($key != $pkey) //probably doesnt mean much!
			print "$key: $value\n";

	///////////////////////////////

	print "Result: ";

        $result = getLLMResponse($prompt, $user, $param['provider'], $model = 'gpt-oss-20b');

	print_r($result);

	print "\n";
	print "\n".str_repeat('-',80)."\n\n";

	//$update = "UPDATE $table SET ai_assessment=? , updated=updated WHERE $pkey = ?";
	//$db->Execute($update, array($result, $row[$pkey]));

	//Result: {"page-title":"Paul","page-description":"Profile page of user Paul on Flickr, displaying his photo gallery and user details.","site-title":"Flickr","site-description":"A photo sharing and hosting platform where users can upload, share, and view images.","comment":""}

	$json = json_decode(trim($result,"`json \t\n\r"), TRUE);

	$updates = array();
	if (empty($json) || !isset($json['page-title'])) {
		$updates['page_title'] = ''; //so no longer null!;
		$updates['comment'] = 'failed to decode json';
	} else {
		$updates['page_title'] = $json['page-title'];
		$updates['page_desc'] = $json['page-description'];
		$updates['site_title'] = $json['site-title'];
		$updates['site_desc'] = $json['site-description'];
		$updates['comment'] = $json['comment'];
	}

	$update = "UPDATE $table SET updated=updated, `".implode('` = ?,`',array_keys($updates))."` = ? WHERE $pkey = ".$row[$pkey];
	$db->Execute($update, array_values($updates));

	///////////////////////////////

	if (!empty($param['sleep']))
		sleep($param['sleep']);
}

##########################################

function substring_index($url,$delimiter,$count) {
	$segments = explode($delimiter, $url, $count+1);
	$extracted_segments = array_slice($segments, 0, $count);
	return implode($delimiter, $extracted_segments);
}

