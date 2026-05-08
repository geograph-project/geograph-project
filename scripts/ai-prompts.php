<?php

// Script parameters
$param = array('offset'=>0, 'batch' => 10, 'print' => true, 'provider'=>'open', 'ai_model' => '',
		'table' => "ai_responce", 'reason'=>true, 'save'=>false, 'example'=>false);

chdir(__DIR__);
require "./_scripts.inc.php";

#########################################################

require_once "3rdparty/llm-providers.inc.php"; // Provides getLLMResponse and other functions (and now classify_tags_batch)

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

#########################################################

if (!empty($param['example'])) {
	//todo, should be checking the time, not just 'active'!
	$row = $db->getRow("  SELECT * FROM {$param['table']} r INNER JOIN ai_prompt p ON (r.prompt_name = p.prompt_name) WHERE p.active = 1
	 AND user1 LIKE '%image_url%'
	 ORDER BY length(content)+length(response)
	 LIMIT 1");

	//SYSTEM
	$prompt = $row['content'];
	if (!empty($row['example'])) {
		$decode = json_decode($row['example'], TRUE);
		if (is_array($decode)) {
			foreach($decode as $key => $value)
				$prompt = str_replace($key,$value, $prompt);
		} else {
			die("unable to replace?\n");
		}
	}
	$messages = [
	        ['role' => 'system', 'content' => $prompt]
	];

	//USER
	if ($row['column_name']) {
		//the response records the prompt column!
		$user = $row[$row['column_name']];

		//user may be json messages list (for image request), but have to be careful as it could itself be an json encoded payload!
		if (preg_match('/^[\[\{]+/',$user)) {
			$decode = json_decode($user, TRUE);
			if (!empty($decode[0]['type'])) { //should find actual message lists. 
				$user = $decode;
			}
		}

        	$messages[] = ['role' => 'user', 'content' => $user];
	}

	//RESPONSE
		//todo,we could encourage NOT wrapping in json block?
		//$json = trim($result,"`json \t\n\r");
	$messages[] = ['role' => 'assistant', 'content' => $row['response']];

	//best to stick with unix newlines
	//todo, maybe make sure utf8??
	foreach($messages as &$message)
		if (is_string($message['content']))
			$message['content'] = str_replace("\r\n","\n", $message['content']);

	print json_encode($messages, JSON_PRETTY_PRINT);
	exit;
}

#########################################################

if (empty($param['ai_model'])) {
	print "\n";
	$data = $db->getAll("select ai_model,count(*),round(avg(length(response))) as avglength,max(updated) from ai_responce group by ai_model");
	foreach ($data as $row) {
		$cmd = "php $argv[0] --config={$param['config']} --ai_model={$row['ai_model']}";

		unset($row['ai_model']);
		print implode("\t",$row);
		print "\t$cmd\n";
	}
	print "\n";
	exit;
}

#########################################################

    if (empty($where))
	$where[] = 1;

    print "-- WHERE ".implode(" AND ", $where)."\n";

    $sql = "SELECT p.*
	FROM ai_prompt p
	LEFT JOIN {$param['table']} r ON (r.prompt_name = p.prompt_name AND r.ai_model = '{$param['ai_model']}')
	WHERE LENGTH(user1) > 10 AND p.active = 1 AND r.prompt_name IS NULL";

    $rs = $db->Execute($sql);

    if ($rs->EOF) {
	print "$sql;\n";
        //break;
	exit;
    }

#########################################################

while (!$rs->EOF) {
    $row = $rs->fields;

    foreach(range(1,4) as $i) {
	if (empty($row['user'.$i]))
		continue;
	if (strlen($row['user'.$i]) == 65535)
		continue; //probably truncated, has to be skipped!

	$prompt = $row['content'];
	//todo if (!empty($row['example'])) str_replace
	if (!empty($row['example'])) {
		$decode = json_decode($row['example'], TRUE);
		if (is_array($decode)) {
			foreach($decode as $key => $value)
				$prompt = str_replace($key,$value, $prompt);
		} else {
			die("unable to replace?\n");
		}
	}


	$user = $row['user'.$i];
	if (preg_match('/^[\[\{]+/',$user)) {
		$decode = json_decode($user, TRUE); //needs to be an actual array to pass to getLLMResponse, even though it will call json_encode on it to send to API!)
		//but still need to check it actually a list of message fragments
		//eg a user prompt, could still be JSON, but needs sending as a string for the LLM to decode, not the API!
		if (!empty($decode[0]['type'])) { //should find actual message lists. 
			$user = $decode;

			if ( ($decode[0]['type'] == 'image' || $decode[1]['type'] == 'image' || $decode[0]['type'] == 'image_url' || $decode[1]['type'] == 'image_url')
//			&& ( strpos($param['ai_model'],'google/') !== 0 || $param['provider'] != 'open')) {
			&& ( strpos($param['ai_model'],'-oss-') !== FALSE || $param['provider'] != 'open')) {
				//at the moment, only google models, via openrouter support images (at least in the ones we use!
				print "Skipping {$row['prompt_name']} as image prompt\n";
				continue;
			}
		}
	}


        if ($param['print']) {
		print "$prompt\n\n";
        	print_r($user);
		print "\n\n";exit;
        }

	print "{$row['prompt_name']} - user$i\n";

	$start=microtime(true);
	$response = getLLMResponse($prompt, $user, $param['provider'], $param['ai_model'], 2048*8);
	$end=microtime(true);

	print "RESPONSE: $response\n\n";

	if ($param['reason'] && !empty($GLOBALS['reasoning']))
	         print "Reasoning: {$GLOBALS['reasoning']}\n";

	if (empty($response))
		die("Aborting, not attempting any more tests\n");

#########################################################

	$updates = array();

	$updates['prompt_name'] = $row['prompt_name'];
	$updates['column_name'] = 'user'.$i;

	$updates['ai_model'] = $param['ai_model'];
	$updates['provider'] = $param['provider'];

	$updates['response'] = $response;

	if ($param['reason'])
		$updates['reasoning'] = $GLOBALS['reasoning'] ?? '';

	$updates['timing'] = $end - $start;

	$db->Execute($sql = 'INSERT INTO '.$param['table'].' SET `'.implode('` = ?,`',array_keys($updates)).'` = ?'.
                 ' ON DUPLICATE KEY UPDATE `'.implode('` = ?,`',array_keys($updates)).'` = ?',
		array_merge(array_values($updates),array_values($updates))) or die("$sql\n\n".$db->ErrorMsg()."\n");

#########################################################

	print "\n\n".str_repeat('~',80)."\n\n";
    }

    $rs->MoveNext();
}

#########################################################

