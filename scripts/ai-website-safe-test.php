<?php

// Refactored and tidied script for tag classification using Cloudflare's Workers AI with the gpt-oss-120b model.
// This version removes redundant code and focuses solely on the specified model.

// Script parameters
$param = array('provider'=>'open', 'table'=>'moderation_all', 'limit'=>10, 'sleep'=>0);

chdir(__DIR__);
// Required files and database connection
require "./_scripts.inc.php";

require_once "3rdparty/llm-providers.inc.php"; // Provides getLLMResponse and other functions

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

###################################################################

$table = $param['table'];

$pkey = "moderation_id"; //primary key

//$query, NEEDS `$pkey`, `website` (other columns optional)
$query = "SELECT $pkey, user.website AS website, user_id, moderation_status FROM $table inner join user using (user_id)
	 inner join user_stat using (user_id)
	  WHERE source = 'user_website' AND ai_assessment IS NULL ORDER BY user_id DESC LIMIT ".$param['limit'];

####################

if ($param['table'] == 'gridimage_link') {
	$table = "gridimage_link"; //table with ai_assessment
	$pkey = "gridimage_link_id"; //primary key

	$query = "SELECT $pkey, url AS website FROM $table
		  LEFT JOIN fetch_domain fd ON (domain = substring_index(url,'/',3))
		WHERE `parent_link_id` = 0 and `next_check` < '2040-01-01' AND HTTP_Status_final IN (200) AND is_internal = 0
		  AND ai_assessment IS NULL AND ( fd.domain IS NULL OR fd.last < DATE_SUB(NOW(), INTERVAL 1 DAY) )
		ORDER BY $pkey DESC
		LIMIT ".$param['limit'];
	//for now concentrate on 200 OK, may still be worth checking some others, ege 503, or 403 as they imply transient, or that our existing bot blocked!
}

####################

$results = $db->getAll($query);

print "$query;\n";

print "Got ".count($results)." from $table\n";

###################################################################
// NEW, expanded prompt!

define('QUIET', 1); //llm-providers by default outputs some text!

$assess_prompt = file_get_contents("../ai-schema/website-safe-test.txt");
$content_prompt = file_get_contents("../ai-schema/website-content-summary.txt");

foreach ($results as $row) {

	$url = latin1_to_utf8($row['website']);
	$domain = substring_index($row['website'],'/',3);
	if (!empty($done[$domain]))
		continue;

	//save to remove from query
	$db->Execute("INSERT INTO fetch_domain SET domain = ?, first=NOW() ON DUPLICATE KEY UPDATE fetches=fetches+1", array($domain));
	//but also stop duplicates in THIS run
	$done[$domain]=1;

	////////////////////

	print "Fetching $url";
	$result = fetchJinaContent($url);
	if ($result['success']) {
		print " success:\n";
		$user = $result['content'];

		print "$user\n"; //for debug

	} else {
		print " failed\n";
		print_r($result); //for debug

		$result = 'failed';
		$db->Execute($update, array($result, $row[$pkey]));
		continue;
	}

	print "\n".str_repeat('-',80)."\n\n";

	////////////////////

	foreach ($row as $key=>$value)
		if ($key != $pkey) //probably doesnt mean much!
			print "$key: $value\n";

	$updates = array();

	////////////////////
	// Grab summary of the content

	print "Summary: ";
	$result = getLLMResponse($content_prompt, $user, $param['provider'], $model = 'gpt-oss-20b');
	print "$result\n";

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

	////////////////////
	// give an assessment

	print "Result: ";
        $result = getLLMResponse($assess_prompt, $user, $param['provider'], $model = 'gpt-oss-safeguard-20b');
	print "$result\n";

	//the safeguard model trained to only respond with result, no commentry :) (reasoning is seperate)
	if (empty($result))
		$updates['ai_assessment'] = 'unknown'; //to stop is repeatedly retrying an unknown URL!
	else
		$updates['ai_assessment'] = $result;

	////////////////////

        $update = "UPDATE $table SET updated=updated, `".implode('` = ?,`',array_keys($updates))."` = ? WHERE $pkey = ".$row[$pkey];
        $db->Execute($update, array_values($updates));

	print "\n".str_repeat('-',80)."\n\n";

	if (!empty($param['sleep']))
		sleep($param['sleep']);
}

##########################################

function substring_index($url,$delimiter,$count) {
	$segments = explode($delimiter, $url, $count+1);
	$extracted_segments = array_slice($segments, 0, $count);
	return implode($delimiter, $extracted_segments);
}

