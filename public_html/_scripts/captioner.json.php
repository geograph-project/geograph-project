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

if (empty($_GET['model'])) {
        die('{"error":"No model"}');
}

customNoCacheHeader(); //otherwise cloudflare might cache!

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$db = GeographDatabaseConnection(false);

	$json = file_get_contents('php://input');
	$data = json_decode($json,true);

	foreach($data as $image) {
		print "{$image['gridimage_id']}: ";
		$updates = array();
		$updates['gridimage_id'] = intval($image['gridimage_id']); //remember that gridimage_id is the primary key
		$updates['model'] = $image['model'];
		$updates['type'] = $image['type'];
		$updates['caption'] = $image['caption'];
		$updates['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';

		if (isset($_GET['unique_number']))
			$updates['user_agent'] = $_GET['unique_number']."/".$updates['user_agent'];

		// | gridimage_id | filename | aesthetic | technical |
		$db->Execute('REPLACE INTO gridimage_caption SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
		print $db->Affected_Rows();
		print "\n";
	}

} else {
	$limit = 50;
	$limit = rand(40,60); //to 'desync' multiple clients!
	if (!empty($_GET['limit']))
		$limit = min(100,intval($_GET['limit']));

	$sleep = ceil(sqrt($limit));

	$imagelist=new ImageList;
	$db = $imagelist->_getDb(false);

	if (!empty($_GET['offset'])) {
		$limit = intval($_GET['offset']).",$limit";
	} else {
		$w = array();
		$w[] = "model = ".$db->Quote($_GET['model']);
		//$w[] = "ipaddr = INET6_ATON('".getRemoteIP()."')";
		$w[] = "unique_key = md5(concat_ws(';',".$db->Quote(getRemoteIP()).",".intval($_GET['unique_number'] ?? 0)."))";

		$offset = $db->getOne("SELECT `offset` FROM labeler_agent WHERE ".implode(' AND ',$w)." AND updated > date_sub(now(),interval 24 hour)");
		if (is_null($offset) || strlen($offset) == 0) { //offset="0" is a valid offset!
			$offsets = explode(',',$db->getOne("SELECT GROUP_CONCAT(`offset`) FROM labeler_agent WHERE ".implode(' AND NOT ',$w)." AND updated > date_sub(now(),interval 24 hour)"));
			$offset = 0;
			while (in_array("$offset",$offsets,true))
				$offset+=100;

                        $w[] = "ipaddr = INET6_ATON('".getRemoteIP()."')";
                        if (!empty($_GET['unique_number'])) {
                                $w[] = "unique_number = ".intval($_GET['unique_number']);
                        }

			$w[] = "`offset` = $offset";
			$db->Execute($sql = "INSERT INTO labeler_agent SET ".implode(',',$w)." ON DUPLICATE KEY UPDATE ".array_pop($w).", updated = NOW()");

		}
		$limit = "$offset,$limit";
	}


	$join = $cols = '';

	if (!empty($_GET['large'])) {
	        $cols .= ", original_width";
        	$join .= "inner join gridimage_size using (gridimage_id)";
	}

	$model = $db->Quote($_GET['model']);

if ($_GET['model'] == 'md3') {
	//c should have all required cols! (even original_width, for 'large')
	$sql = "select c.* from tmp_caption_md3 c
                left join gridimage_caption using (gridimage_id)
		where caption is null
		limit $limit";
	$last = 0;
} else {
	//$last_id = 0;
	$last_id = $db->getOne("SELECT last_id FROM labeller_progress WHERE model = $model and direction = 'forward'") ?? 0;
	$update_last_id = true;

	$sql = "select gridimage_id,user_id,title $cols
		from gridimage
		left join gridimage_caption using (gridimage_id)
		$join
		where caption is null and moderation_status > 2 AND gridimage_id > $last_id
		order by gridimage_id
		limit $limit";
}

	$imagelist->_getImagesBySql($sql);

	if (count($imagelist->images)) {
		foreach ($imagelist->images as $i => $image) {
			$last_id = max($last_id, $image->gridimage_id);

			if (isset($_GET['large']) && $imagelist->images[$i]->original_width) {
				$imagelist->images[$i]->original = $imagelist->images[$i]->_getOriginalpath();

				//the original is missing!!?
				if ($image->gridimage_id == 29 || $image->gridimage_id == 5378158 || $image->gridimage_id == 1401219)
					$imagelist->images[$i]->original = str_replace('original','1024x1024',$imagelist->images[$i]->original);

			} else {
				$imagelist->images[$i]->fullpath = $imagelist->images[$i]->_getFullpath();

				if (basename($imagelist->images[$i]->fullpath) == 'error.jpg') {
					$db = GeographDatabaseConnection(false);
					//$db->Execute("DELETE FROM gridimage_caption WHERE gridimage_id = {$image->gridimage_id}");

					//we used to delete the failed rows, but but here should insert a record so can ne skipped
					$db->Execute('REPLACE INTO gridimage_caption SET gridimage_id=?, model=?, caption=""', array($image->gridimage_id, $_GET['model']) );

					unset($imagelist->images[$i]);
					$deleted=1;
					continue;
				}
			}


			foreach (get_object_vars($image) as $key => $value) {
				if (empty($value))
					unset($imagelist->images[$i]->{$key});
			}
		}

		if (!empty($deleted))
			$imagelist->images = array_values($imagelist->images); //get sequential keys back!

                //if configured can load thumbnails directly from r2 instead (avoids clobbering proxy cache, with lots of little used files) - Sippy still involved!
                if (!empty($CONF['r2_dev_photo_endpoint']))
                        $CONF['STATIC_HOST'] = $CONF['r2_dev_photo_endpoint'];


		$data = array('prefix'=>$CONF['STATIC_HOST'],'sleep'=>$sleep,'rows'=>$imagelist->images);
		outputJSON($data); //passed by ref

		if (!empty($last_id) && !empty($update_last_id)) {
			$db->Execute("INSERT INTO labeller_progress (model, last_id, direction) VALUES ($model, $last_id, 'forward')
				ON DUPLICATE KEY UPDATE last_id = VALUES(last_id)");
		}
	} else {
		print "[]";
	}
}
