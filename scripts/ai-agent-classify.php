<?php

// Script parameters
$param = array('limit' => 1, 'print' => true, 'provider'=>'aws', 'save'=>false, 'direction' => 'forward', 'restart'=>false,
		'table' => "agents_all_time", 'reason'=>true, 'ai'=>'gpt-oss-safeguard-20b', 'encode'=>false);

chdir(__DIR__);
require "./_scripts.inc.php";

#########################################################

require_once "3rdparty/llm-providers.inc.php"; // Provides getLLMResponse and other functions (and now classify_tags_batch)

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

#########################################################

$prompt_name = "agent-classify";

    $prompt = $db->getOne("SELECT content FROM ai_prompt WHERE active=1 AND prompt_name = '$prompt_name'");

    if ($param['print']) {
        print "$prompt\n";
    }

#########################################################

if (empty($param['restart'])) //set to true to ignore
        $last_id = $db->getOne("SELECT last_id FROM labeller_progress WHERE model = '$prompt_name' AND direction = '{$param['direction']}'");

#########################################################

$done = 0;
$id2tid = array();

while (true) {
	$where = array();
	$where[] = "fragment IS NULL";
	$where[] = "purpose IS NULL";
	$where[] = "device IS NULL"; //the LLM may not find a fragment, but most rows get a device!

	$where[] = "bot = 1";
//	$where[] = "bot=0 AND useragent NOT like 'Mozilla/%'";
//	$where[] = "useragent like 'Mozilla/%' AND useragent NOT like '%KHTML%' AND useragent NOT like '%Firefox%' AND  useragent NOT like '%MSIE%'";
	

	if ($param['direction'] == 'forward') {
	        if (!empty($last_id)) $where['last'] = "id > $last_id";
        	$order = "id ASC";
	} else {
        	if (!empty($last_id)) $where['last'] = "id < $last_id";
	        else $last_id = 999999999;
        	$order = "id DESC";
	}


	print implode(" AND ",$where)."\n";
	print "\n".str_repeat('~',80)." Last ID: $last_id\n\n";


	$data = $db->getAll("select id, useragent
	 from {$param['table']}
	 where ".implode(" and ",$where)."
	 order by $order limit 20");

	if (empty($data))
		break;

#########################################################

//	$user = json_encode($data);
	//Gemini suggested sending XML not json. It better for multiple row data.
	$user = "Agents:\n";
	//<ticket_id>1899573</ticket_id> <ticket_date>2026-05-23</ticket_date> <ticket_text>Auto-generated ticket, as a result of Moderation. Rejecting this image because: windscreen reflection </ticket_text>
	foreach($data as $idx => $row) {
		$id = $idx+1; //also sending a short ID, is better (less likely to halliinsuate IDs)
		$id2tid[$id] = $row['id'];
		$text = htmlentities(latin1_to_utf8($row['useragent']));
		$user .= json_encode(array('id'=>$id,'useragent'=>$text))."\n";
		print "$text\n";
	}
        if ($param['direction'] == 'forward') {
                $last_id = max($last_id, $row['id']);
        } else {
                $last_id = min($last_id, $row['id']);
        }

        if ($param['save']) {
                save_user_prompt($prompt_name, $user);
        }

    if ($param['print']) {
        print "$user\n";
//	if ($param['print'] != 2)
		exit;
    }

	$response = getLLMResponse($prompt, $user, $param['provider'], $param['ai'], 512*16*4); //the model does a lot of internal reasoning!

	print "RESPONSE: $response\n\n";

		 if ($param['reason'] && !empty($GLOBALS['reasoning']))
		         print "Reasoning: {$GLOBALS['reasoning']}\n";

	$json = json_decode(trim($response,"`json \t\n\r"), TRUE);

###################################################

	if (!empty($json)) {
		foreach($json as $row) {
			$updates = array();
/*   {
    "id": 1,
    "fragment": "PurityBot",
    "version": "PurityBot/1.0",
    "name": "PurityBot",
    "website": "https://puri.li/",
    "url": "https://puri.li/bot",
    "email": null,
    "headless": null,
    "purpose": "research-crawler",
    "device": "bot"
  }, */
	                $updates['fragment'] = $row['fragment'];
	                $updates['version'] = $row['version'];
	                $updates['name'] = $row['name'];
	                $updates['website'] = $row['website'];
	                $updates['url'] = $row['url'];
	                $updates['email'] = $row['email'];
	                $updates['headless'] = $row['headless'];
	                $updates['purpose'] = $row['purpose'];
	                $updates['device'] = $row['device'];

			//$updates['ai_model'] = $param['ai'];

			//need to find the orignal id
			$where = "id = ".intval( $id2tid[$row['id']]);
			$db->Execute($sql = 'UPDATE '.$param['table'].' SET `'.implode('` = ?,`',array_keys($updates)).'` = ? WHERE '.$where,
				 array_values($updates)) or die("$sql\n\n".$db->ErrorMsg()."\n");
		}
	}

	$done++;
	if ($done == $param['limit'])
		break;
}

#########################################################

                if (!empty($last_id)) {
                        $db->Execute("INSERT INTO labeller_progress (model, last_id, direction) VALUES ('$prompt_name', $last_id, '{$param['direction']}')
                                ON DUPLICATE KEY UPDATE last_id = VALUES(last_id)");
                }


/*

Primary.geograph_live>update agents_all_time set fragment = 'SpreadTrum' where fragment = 'Opera' and useragent = 'Opera/9.80 (SpreadTrum; Opera Mini/4.4.34868/191.396; U; en) Presto/2.12.4
23 Version/12.16';

Primary.geograph_live>update agents_all_time set fragment = NULL where fragment = 'Mozilla' and useragent = 'Mozilla';

Primary.geograph_live>update agents_all_time set fragment = 'Mozlila' where fragment = 'Mozilla' and useragent = 'Mozlila/5.0 (Linux; Android 7.0; SM-G892A Build/NRD90M; wv) AppleWebKit/537
.36 (KHTML, like Gecko) Version/4.0 Chrome/60.0.3112.107 Mobile Safari/537.36';

Primary.geograph_live>update agents_all_time set fragment = 'seo-jungle.com' where fragment = 'Crawler' and useragent = 'Crawler/0.1 (+https://seo-jungle.com/bot)';

Primary.geograph_live>update agents_all_time set fragment = 'spider.cloud' where fragment = 'Spider' and useragent = 'Mozilla/5.0 (compatible; Spider/2.0; +https://spider.cloud)';
Query OK, 1 row affected (0.007 sec)

Primary.geograph_live>select device,fragment,useragent from agents_all_time where fragment in ('crawler', 'bot', 'spider', 'browser', 'agent', 'archiver', 'scraper', 'mozilla');
+--------+----------+-----------------------------------+
| device | fragment | useragent                         |
+--------+----------+-----------------------------------+
| bot    | Spider   | Spider                            |
| bot    | crawler  | Mozilla/5.0 (compatible; crawler) |
| bot    | bot      | Mozilla/5.0 (compatible; bot/1.0) |
| bot    | Crawler  | Crawler test                      |
+--------+----------+-----------------------------------+
4 rows in set (0.004 sec)

Primary.geograph_live>update agents_all_time set fragment = 'Crawler test' where fragment = 'Crawler' and useragent = 'Crawler test';

Primary.geograph_live>update agents_all_time set fragment = null where fragment in ('crawler', 'bot', 'spider', 'browser', 'agent', 'archiver', 'scraper', 'mozilla');

*/

