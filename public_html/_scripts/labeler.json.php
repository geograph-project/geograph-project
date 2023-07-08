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
		$updates['gridimage_id'] = intval($image['image_id']); //remember that gridimage_id is used as (part of!) unique key
		$updates['filename'] = $image['image_id'];
		$updates['model'] = @$_GET['model'];
		$updates['label'] = $image['label'];
		$updates['score'] = $image['score'];

		$db->Execute('REPLACE INTO gridimage_group SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
		print $db->Affected_Rows();
		print "\n";
	}

} else {
	####################

	$limit = 50;
	$limit = rand(40,60); //to 'desync' multiple clients!
	if (!empty($_GET['limit']))
		$limit = min(100,intval($_GET['limit']));


	$sleep = ceil(sqrt($limit));

	if (!empty($_GET['offset']))
		$limit = intval($_GET['offset']).",$limit";

	####################

	$where = array();
	$where[] = "seq_id IS null";

	if (empty($_GET['all'])) {
		if (empty($_GET['model']) || $_GET['model'] == 'type') {
			$where[] = "moderation_status = 'accepted'"; //todo, we might want to also check geos? eg spot potential long-distance/cross grid
			$where[] = "tags NOT like '%type:%'";
			$_GET['model'] = 'type';
		} elseif ($_GET['model'] == 'top') {
			$where[] = "tags NOT like '%top:%'";
		} elseif ($_GET['model'] == 'subject') {
			$where[] = "tags NOT like '%subject:%'";
		} elseif ($_GET['model'] == 'class') {
			$where[] = "imageclass=''";
		}
	}

	####################

	$imagelist=new ImageList;
	$qmod = $imagelist->_getDb()->Quote($_GET['model']);
	$where = implode(" AND ",$where);

	$sql = "select gi.gridimage_id,user_id,original_width
		from gridimage_search gi
		inner join gridimage_size using (gridimage_id)
		left join gridimage_label l on (l.gridimage_id = gi.gridimage_id and `model` = $qmod)
		where $where
		limit $limit";

	$imagelist->_getImagesBySql($sql);

	####################

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
	}
}
