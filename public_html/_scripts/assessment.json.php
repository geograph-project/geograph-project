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


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$db = GeographDatabaseConnection(false);

	$json = file_get_contents('php://input');
	$data = json_decode($json,true);

	foreach($data as $image) {
		print "{$image['image_id']}: ";
		$updates = array();
		$updates['gridimage_id'] = intval($image['image_id']); //remember that gridimage_id is the primary key
		$updates['filename'] = $image['image_id'];
		$updates['aesthetic'] = $image['aesthetic'] ?? NULL;
		$updates['technical'] = $image['technical'] ?? NULL;

	//=sqrt(pow(aesthetic-5.095,2)+pow(technical-5.102,2))
		$updates['distance'] = sqrt(pow($updates['aesthetic']-5.095,2)+pow($image['technical']-5.102,2));

		// | gridimage_id | filename | aesthetic | technical |
		$db->Execute('REPLACE INTO assessment SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
		print $db->Affected_Rows();
		print "\n";
	}

} else {
	$limit = 50;
	$limit = rand(40,60); //to 'desync' multiple clients!
	if (!empty($_GET['limit']))
		$limit = min(100,intval($_GET['limit']));

	$sleep = ceil(sqrt($limit));

	if (!empty($_GET['offset'])) {
		$limit = intval($_GET['offset']).",$limit";
	} else {
		$db = $imagelist->_getDb(false);
		$w = array();
		$w[] = "model = 'assessment'";
		$w[] = "ipaddr = INET6_ATON('".getRemoteIP()."')";

		$offset = $db->getOne("SELECT `offset` FROM labeler_agent WHERE ".implode(' AND ',$w)." AND updated > date_sub(now(),interval 24 hour)");
		if (is_null($offset) || strlen($offset) == 0) { //offset="0" is a valid offset!
			$offsets = explode(',',$db->getOne("SELECT GROUP_CONCAT(`offset`) FROM labeler_agent WHERE ".implode(' AND NOT ',$w)." AND updated > date_sub(now(),interval 24 hour)"));
			$offset = 0;
			while (in_array("$offset",$offsets,true))
				$offset+=100;
			$w[] = "`offset` = $offset";
			$db->Execute($sql = "INSERT INTO labeler_agent SET ".implode(',',$w)." ON DUPLICATE KEY UPDATE ".array_pop($w).", updated = NOW()");

		}
		$limit = "$offset,$limit";
	}

	$imagelist=new ImageList;

	$join = $cols = '';

	if (!empty($_GET['large'])) {
	        $cols .= ", original_width";
        	$join .= "inner join gridimage_size using (gridimage_id)";
	}

	$sql = "select gridimage_id,user_id $cols
		from gridimage
		inner join assessment using (gridimage_id)
		$join
		where aesthetic IS NULL OR technical IS NULL
		limit $limit";

	$imagelist->_getImagesBySql($sql);

	if (count($imagelist->images)) {
		foreach ($imagelist->images as $i => $image) {
			if ($imagelist->images[$i]->original_width && isset($_GET['large'])) {
				$imagelist->images[$i]->original = $imagelist->images[$i]->_getOriginalpath();

				//the original is missing!!?
				if ($image->gridimage_id == 29 || $image->gridimage_id == 5378158 || $image->gridimage_id == 1401219)
					$imagelist->images[$i]->original = str_replace('original','1024x1024',$imagelist->images[$i]->original);

			} else {
				$imagelist->images[$i]->fullpath = $imagelist->images[$i]->_getFullpath();

				if (basename($imagelist->images[$i]->fullpath) == 'error.jpg') {
					$db = GeographDatabaseConnection(false);
					$db->Execute("DELETE FROM assessment WHERE gridimage_id = {$image->gridimage_id}");

					//todo, delete from assessment where gridimage_id = as will never be updated anyway

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

		$data = array('prefix'=>$CONF['STATIC_HOST'],'sleep'=>$sleep,'rows'=>$imagelist->images);
		outputJSON($data); //passed by ref
	} else {
		print "[]";
	}
}
