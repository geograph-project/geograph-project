<?php

/**
 * $Project: GeoGraph $
 * $Id: submissions.php 6417 2010-03-04 22:14:53Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2007 Barry Hunter (geo@barryhunter.co.uk)
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

require_once('geograph/global.inc.php');
require_once('geograph/imagelist.class.php');

####################################

if (empty($_GET['model'])) {
	die('{"error":"No model"}');
} //todo, aos check it a valid model!
	//select model from dataset where model = _GET[model] AND model_download != ''

customNoCacheHeader(); //otherwise cloudflare might cache!

####################################

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	if ($_GET['model'] == 'auto')
		die('{"error":"Something went horribly wrong"}');

	$db = GeographDatabaseConnection(false);

	$json = file_get_contents('php://input');
	$data = json_decode($json,true);

	//might as well do updates all in one transaction
	$db->Execute('start transaction');

	foreach($data as $image) {
		print intval($image['id']).": ";
		$updates = array();
		$updates['id'] = intval($image['id']);
		$updates['model'] = $_GET['model'];
		$updates['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
		if (!empty($image['embeddings'])) {
			if (!empty($image['label']))
				$updates['label'] = $image['label'];
			//$updates['type'] = $image['type'];
			$updates['embeddings'] = base64_decode($image['embeddings']);
			//might seem odd to use INSERT ... ON DUPLICATE KEY UPDATE ... but want to allow for adding new rows directly?
			$db->Execute('INSERT INTO label_embedding SET `'.implode('` = ?,`',array_keys($updates)).'` = ?'.
				' ON DUPLICATE KEY UPDATE `'.implode('` = ?,`',array_keys($updates)).'` = ?',
				array_merge(array_values($updates),array_values($updates))) or die("$sql\n\n".$db->ErrorMsg()."\n");
		}
		print $db->Affected_Rows();
		print "\n";
	}

	$db->Execute('commit');

####################################

} else {
	$db = GeographDatabaseConnection(false);

	####################

	$where = array();
	$where[] = "embeddings IS null"; //not already labled
	$limit = 50;
	$limit = rand(40,60); //to 'desync' multiple clients!
	if (!empty($_GET['limit'])) {
		$limit = (!empty($_GET['comment']) || !empty($_GET['title'])) ? 1000 : 250;
		$limit = min($limit,intval($_GET['limit']));
	}
	$sleep = ceil(sqrt($limit));

	if (!empty($_GET['offset'])) {
		$limit = intval($_GET['offset']).",$limit";
	} else {
		$w = array();
		$w[] = "model = ".$db->Quote($_GET['model']);
		$w[] = "unique_key = md5(concat_ws(';',".$db->Quote(getRemoteIP()).",".intval($_GET['unique_number'] ?? 0)."))";

		$offset = $db->getOne("SELECT `offset` FROM labeler_agent WHERE ".implode(' AND ',$w)." AND updated > date_sub(now(),interval 24 hour)");
		if (is_null($offset) || strlen($offset) == 0) { //offset="0" is a valid offset!
			$offsets = explode(',',$db->getOne("SELECT GROUP_CONCAT(`offset`) FROM labeler_agent WHERE ".implode(' AND NOT ',$w)." AND updated > date_sub(now(),interval 24 hour)"));
			$offset = 0;
			while (in_array("$offset",$offsets,true))
				$offset+=200; //should be 50*number-of-clients, but chicken and egg, dont know how many clients will be

			$w[] = "ipaddr = INET6_ATON('".getRemoteIP()."')";
                        if (!empty($_GET['unique_number'])) {
                                $w[] = "unique_number = ".intval($_GET['unique_number']);
                        }
			$w[] = "`offset` = $offset"; //must be last itme!
			$db->Execute($sql = "INSERT INTO labeler_agent SET ".implode(',',$w)." ON DUPLICATE KEY UPDATE ".array_pop($w).", updated = NOW()");
		}
		$limit = "$offset,$limit";
	}

	if (!empty($_GET['label'])) {
		array_shift($where); //remove the IS NULL
		$where[] = "label = ".$db->Quote($_GET['label']);
		$limit = 1; //remove the auto-offset
	}

	####################

	$qmod = $db->Quote($_GET['model']);
	$where[] = "model = $qmod";
	$where = implode(" AND ",$where);

	$sql = "select id,label
		from label_embedding
		where $where
		limit $limit";

	if (!empty($_GET['ddd']))
		die("$sql;\n");

	####################

	$rows = $db->getAll($sql);

	if (!empty($rows)) {
		foreach ($rows as &$row) {
			$enc = mb_detect_encoding($row['label'], 'UTF-8, ISO-8859-15, ASCII');
                        if ($enc == 'ISO-8859-15' || strpos($row['label'],'&#')!==FALSE)
				$row['label'] = latin1_to_utf8($row['label']);
		}
		unset($row);

		$data = array('prefix'=>$CONF['STATIC_HOST'],'sleep'=>$sleep,'rows'=>$rows);
		outputJSON($data); //passed by ref
	}
}
