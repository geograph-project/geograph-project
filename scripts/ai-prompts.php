<?php

// Script parameters
$param = array('print' => true, 'provider'=>'open', 'ai_model' => '', 'encode'=>false,
		'table' => "ai_responce", 'reason'=>true, 'example'=>false, 'simple'=>false);

chdir(__DIR__);
require "./_scripts.inc.php";

#########################################################

require_once "3rdparty/llm-providers.inc.php"; // Provides getLLMResponse and other functions (and now classify_tags_batch)

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

#########################################################

if (empty($param['ai_model'])) {
	print "\n";
	$data = $db->getAll("select ai_model,count(*),format_percent(sum(is_gold_standard),count(*),1) as gold, round(avg(length(response))) as avglength,round(avg(timing),3) as timing, max(created) as last from ai_responce group by ai_model order by last desc");
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

$sql = "SELECT unpivoted.* FROM (
    SELECT prompt_name, content, example, active, 'user1' AS target_column, user1 AS prompt_text FROM ai_prompt
    UNION ALL
    SELECT prompt_name, content, example, active, 'user2' AS target_column, user2 AS prompt_text FROM ai_prompt
    UNION ALL
    SELECT prompt_name, content, example, active, 'user3' AS target_column, user3 AS prompt_text FROM ai_prompt
    UNION ALL
    SELECT prompt_name, content, example, active, 'user4' AS target_column, user4 AS prompt_text FROM ai_prompt
) unpivoted
LEFT JOIN ai_responce r ON (r.prompt_name = unpivoted.prompt_name AND r.column_name = unpivoted.target_column AND r.ai_model = '{$param['ai_model']}')
WHERE unpivoted.active = 1 AND LENGTH(unpivoted.prompt_text) > 10 AND LENGTH(unpivoted.prompt_text) < 65535 AND r.prompt_name IS NULL
ORDER BY LENGTH(prompt_text)+LENGTH(content)";
//there was some long prompts got truncated, as stored in TEXT, need to exclude for now, until they can be recreated.

    $rs = $db->Execute($sql);

    if ($rs->EOF) {
	print "$sql;\n";
        //break;
	exit;
    }

#########################################################

$color = "\033[32m"; //actylly green!
$yellow= "\033[33m";
$white = "\033[0m";

while (!$rs->EOF) {
        $row = $rs->fields;

	$prompt = $row['content'];

	if (strpos($param['ai_model'],'diffusiongemma') !== FALSE && preg_match('/-test$/', $row['prompt_name']) && strpos($prompt,'JSON') === FALSE) {
		//the diffusion models doesnt work on these prompts, as they request a one word answer

		//add this to the prompt to force a bigger reply! (so can populate a token canvas)
		$prompt .= "\n\nAnswer in the following JSON format. Do not provide just the label; you must include your reasoning:\n";
		$prompt .= '{"reasoning": "...", "classification": "..."}'."\n";

                //$rs->MoveNext();
                //continue;
	}

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

	$enc = mb_detect_encoding($prompt, 'UTF-8, ISO-8859-15, ASCII');
	if ($enc == 'ISO-8859-15')
		$prompt = latin1_to_utf8($prompt);



	$user = $row['prompt_text'];
	if (preg_match('/^[\[\{]+/',$user)) {

		$decode = json_decode($user, TRUE); //needs to be an actual array to pass to getLLMResponse, even though it will call json_encode on it to send to API!)
		//but still need to check it actually a list of message fragments
		//eg a user prompt, could still be JSON, but needs sending as a string for the LLM to decode, not the API!


    switch (json_last_error()) {
        case JSON_ERROR_NONE:       //echo ' - No errors';
        break;
        case JSON_ERROR_DEPTH:      echo ' - Maximum stack depth exceeded';        break;
        case JSON_ERROR_STATE_MISMATCH: echo ' - Underflow or the modes mismatch'; break;
        case JSON_ERROR_CTRL_CHAR:  echo ' - Unexpected control character found';        break;
        case JSON_ERROR_SYNTAX:     echo ' - Syntax error, malformed JSON';        break;
        case JSON_ERROR_UTF8:       echo ' - Malformed UTF-8 characters, possibly incorrectly encoded';        break;
        default:            echo ' - Unknown error';        break;
    }
if (json_last_error() !== JSON_ERROR_NONE)
	die("{$row['prompt_name']} - {$row['target_column']} FAILED\n");

		if (!empty($decode[0]['type'])) { //should find actual message lists. 
			$user = $decode;

			if (@ ($decode[0]['type'] == 'image' || $decode[1]['type'] == 'image' || $decode[0]['type'] == 'image_url' || $decode[1]['type'] == 'image_url')
//			&& ( strpos($param['ai_model'],'google/') !== 0 || $param['provider'] != 'open')) {
			&& ( strpos($param['ai_model'],'-oss-') !== FALSE || !in_array($param['provider'],array('open','nvidia','moondream')) || strpos($param['ai_model'],'micro') )) {
				//at the moment, only google models, via openrouter support images (at least in the ones we use!
				print "Skipping {$row['prompt_name']} as image prompt\n";
			        $rs->MoveNext();
				continue;
			} elseif ($param['provider'] == 'moondream' && (empty($decode[1]['type']) || $decode[1]['type'] != 'image_url')) {
				print "Skipping {$row['prompt_name']} as text prompt\n";
			        $rs->MoveNext();
				continue;
			}

		        if ($param['encode'] && !empty($decode[1]['image_url']['url'])) {
		                //cloudflare blocks requests without UA!)
		                ini_set("user_agent","Internal Request");

		                // 1. Fetch by URL (path is URL)
		                $imageData = file_get_contents($decode[1]['image_url']['url']);

		                print "imageData = ".strlen($imageData)." bytes\n";

		                // 2. Encode to Base64
		                $base64Image = base64_encode($imageData);

		                // 3. Determine MIME type (usually image/jpeg for your geophotos)
		                $mimeType = 'image/jpeg';

		                // 4. Construct the Data URI
		                $user[1]['image_url']['url'] = "data:{$mimeType};base64,{$base64Image}";
		        }

		} elseif ($param['provider'] == 'moondream') {
			print "Skipping {$row['prompt_name']} as text prompt\n";
	        	$rs->MoveNext();
			continue;
		}

	} elseif ($param['provider'] == 'moondream') {
		print "Skipping {$row['prompt_name']} as text prompt\n";
	        $rs->MoveNext();
		continue;
	}

	print "{$row['prompt_name']} - {$row['target_column']} [".(is_array($user)?'json':'string')."]...\n";

        if ($param['print']) {
		print "$prompt\n\n";
        	print_r($user);
		print "\n\n";exit;
        }

	$start=microtime(true);
	$response = getLLMResponse($prompt, $user, $param['provider'], $param['ai_model'], 2048*8);
	$end=microtime(true);

	print "RESPONSE: $yellow$response$white\n\n";

	if ($param['reason'] && !empty($GLOBALS['reasoning']))
	         print "Reasoning: $color{$GLOBALS['reasoning']}$white\n";

	if (empty($response)) {
		if ($param['ai_model'] == 'google/diffusiongemma-26b-a4b-it') {
                                print "Skipping {$row['prompt_name']} as empty\n";
                                $rs->MoveNext();
                                continue;
		}

		die("Aborting, not attempting any more tests\n");
	}

#########################################################

	$updates = array();

	$updates['prompt_name'] = $row['prompt_name'];
	$updates['column_name'] = $row['target_column'];

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


        $rs->MoveNext();
}

#########################################################

