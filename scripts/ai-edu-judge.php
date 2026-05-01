<?php

// Script parameters
$param = array('offset'=>0, 'batch' => 10, 'print' => false, 'provider'=>'open', 'direction'=>'forward',
		'table' => "curated_judge", 'reason'=>true, 'ai'=>"google/gemma-4-26b-a4b-it");

chdir(__DIR__);
require "./_scripts.inc.php";

#########################################################

require_once "3rdparty/llm-providers.inc.php"; // Provides getLLMResponse and other functions (and now classify_tags_batch)

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

#########################################################

$aiModel = $param['ai'];

$model = "edu-judge"; //this is OUR model label!

#########################################################

$where = array();

$where[] = "cosine < 0.8";

#########################################################

$last_id = $db->getOne("SELECT last_id FROM labeller_progress WHERE model = '$model' AND direction = '{$param['direction']}'");

        if ($param['direction'] == 'forward') {
            if (!empty($last_id)) $where['last'] = "gi.gridimage_id > $last_id";
            $order = "gi.gridimage_id ASC";
        } else {
            if (!empty($last_id)) $where['last'] = "gi.gridimage_id < $last_id";
            else $last_id = 99999999;
            $order = "gi.gridimage_id DESC";
        }

#########################################################

	$where[] = "c.active=1 and c.user_id = 23277 and c.score = 10";

    if (empty($where))
	$where[] = 1;

    print "-- WHERE ".implode(" AND ", $where)."\n";

        $sql = "SELECT gi.gridimage_id, gi.user_id, title, comment,  stack,name,feature_tag,critical_feature
	FROM gridimage_search gi INNER JOIN curated1 c USING (gridimage_id)
		INNER JOIN curated_label l ON (l.name = c.label)
	LEFT JOIN {$param['table']} r ON (r.gridimage_id = gi.gridimage_id and r.label = c.label)
	WHERE " . implode(" AND ", $where) . " AND r.gridimage_id IS NULL ORDER BY $order LIMIT ".$param['batch'];

    $rs = $db->Execute($sql);

    if ($rs->EOF) {
        //break;
        print "$sql;\n\n";
	exit;
    }

############################################################

    $prompt = $db->getOne("SELECT content FROM ai_prompt WHERE active=1 AND prompt_name = '$model'");

    if ($param['print']) {
	print "$prompt\n";
    }

    while (!$rs->EOF) {
        $image=new GridImage;
        $image->fastInit($rs->fields);

	$path = $image->getSquareThumbnail(224,224,'fullpath');

	if (basename($path) == 'error.jpg') {
		print_r($rs->fields);
		print "faile\n";
		exit;
	}

###################################################

	$prompt2 = str_replace('{{stack}}',$image->stack,$prompt);
	$prompt2 = str_replace('{{name}}',$image->name,$prompt2);
	$prompt2 = str_replace('{{feature_tag}}',$image->feature_tag,$prompt2);
	$prompt2 = str_replace('{{critical_feature}}',$image->critical_feature,$prompt2);

	if ($param['print']) {
		print "$prompt2\n";
	}

###################################################

	$userText = "Title: ".latin1_to_utf8($image->title)."\n";
	if (!empty($image->comment)) {
	    $userText .= "Description: ".latin1_to_utf8($image->comment)."\n";
	}

###################################################

	//for a image request, send structured (callOpenRouter will call json encode which copes!)
	$user = [ [ 'type' => 'text', 'text' => $userText ],
		  [ 'type' => 'image_url', 'image_url' => [ 'url' => $path ] ]
		];

	if ($param['print']) {
		print_r($user);
		exit;
	}

	print "ID: ".intval($rs->fields['gridimage_id'])."\n";
	print "Label: ".htmlentities($rs->fields['name'])."\n";
	print "Title: ".htmlentities($rs->fields['title'])."\n";
	print "Image: ".$path."\n";

	$response = callOpenRouter($prompt2, $user, 512, $aiModel); //so can specify max_toksn

	print "RESPONSE: $response\n\n";

		 if ($param['reason'] && !empty($GLOBALS['reasoning']))
		         print "Reasoning: {$GLOBALS['reasoning']}\n";

	$json = json_decode(trim($response,"`json \t\n\r"), TRUE);

###################################################

	if (!empty($json)) {
		$updates = array();
		$updates['reasoning'] = $json['reasoning'];
		$updates['accuracy'] = $json['scores']['accuracy'];
		$updates['prominence'] = $json['scores']['prominence'];
		$updates['validity'] = $json['scores']['prominence'];
		$updates['is_gold_standard'] = $json['is_gold_standard']?1:0;

                $updates['gridimage_id'] = $rs->fields['gridimage_id'];
                $updates['label'] = $rs->fields['name'];
                $updates['ai_model'] = $aiModel;

                 $db->Execute($sql = 'INSERT INTO '.$param['table'].' SET `'.implode('` = ?,`',array_keys($updates)).'` = ?'.
                          ' ON DUPLICATE KEY UPDATE `'.implode('` = ?,`',array_keys($updates)).'` = ?',
                         array_merge(array_values($updates),array_values($updates))) or die("$sql\n\n".$db->ErrorMsg()."\n");
	}

###################################################

        if ($param['direction'] == 'forward') {
                $last_id = max($last_id, $rs->fields['gridimage_id']);
        } else {
                $last_id = min($last_id, $rs->fields['gridimage_id']);
        }

	print "\n\n".str_repeat('~',80)."\n\n";
        $rs->MoveNext();
    }

###################################################

                if (!empty($last_id)) {
                        $db->Execute("INSERT INTO labeller_progress (model, last_id, direction) VALUES ('$model', $last_id, '{$param['direction']}')
                                ON DUPLICATE KEY UPDATE last_id = VALUES(last_id)");
                }

