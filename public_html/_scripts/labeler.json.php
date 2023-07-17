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

if (!empty($_GET['models'])) {
	$db = GeographDatabaseConnection(true);
	$data = $db->getAll("select model,model_download,model_dir,folder,grouper,images from dataset where model_download != '' and model != ''");
        outputJSON($data);
	exit;

} elseif (!empty($_GET['training'])) {
	$db = GeographDatabaseConnection(true);
	$data = $db->getAll("select folder,src_format,imagesize,src_download,grouper,images,model_dir from dataset where src_download != '' and `grouper` != ''");
        outputJSON($data);
	exit;
}

####################################

if (empty($_GET['model'])) {
	die('{"error":"No model"}');
} //todo, aos check it a valid model!
	//select model from dataset where model = _GET[model] AND model_download != ''

if ($_GET['model'] == 'auto') {
	//auto is a special value, meaning the server is free to choose, but it should choose from downloadable models, as need to fetch from one of the models being submitted

	if ($_SERVER['REQUEST_METHOD'] == 'POST')
		die('{"error":"Something went horribly wrong"}');

	$_GET['model'] = 'type'; //for now, hardcoded!
	//todo... $_GET['model'] = db->getOne("SELECT model FROM dataset where model_download != '' and model != '' ORDER BY RAND()");
}

####################################

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$db = GeographDatabaseConnection(false);

	$json = file_get_contents('php://input');
	$data = json_decode($json,true);

	//might as well do updates all in one transaction
	$db->Execute('start transaction');

	foreach($data as $image) {
		print "{$image['image_id']}: ";
		$updates = array();
		$updates['gridimage_id'] = intval($image['image_id']); //remember that gridimage_id is used as (part of!) unique key
		$updates['model'] = $_GET['model'];
		$updates['label'] = $image['label'];
		$updates['score'] = $image['score'];
		$updates['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';

		$db->Execute('REPLACE INTO gridimage_label SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
		print $db->Affected_Rows();
		print "\n";
	}

	$db->Execute('commit');

####################################

} else {
	$imagelist=new ImageList;
	####################

	$cols = ',realname';
	$join = '';
	$where = array();
	$where[] = "l.seq_id IS null"; //not already labled
	$limit = 50;
	$limit = rand(40,60); //to 'desync' multiple clients!
	if (!empty($_GET['limit']))
		$limit = min(250,intval($_GET['limit']));

	$sleep = ceil(sqrt($limit));

	if (!empty($_GET['offset'])) {
		$limit = intval($_GET['offset']).",$limit";
	} else {
		$db = $imagelist->_getDb(false);
		$w = array();
		$w[] = "model = ".$db->Quote($_GET['model']);
		$w[] = "ipaddr = INET6_ATON('".getRemoteIP()."')";

		$offset = $db->getOne("SELECT offset FROM labeler_agent WHERE ".implode(' AND ',$w)." AND updated > date_sub(now(),interval 24 hour)");
		if (is_null($offset) || strlen($offset) == 0) { //offset="0" is a valid offset!
			$offsets = explode(',',$db->getOne("SELECT GROUP_CONCAT(offset) FROM labeler_agent WHERE ".implode(' AND NOT ',$w)." AND updated > date_sub(now(),interval 24 hour)"));
			$offset = 0;
			while (in_array("$offset",$offsets,true))
				$offset+=100;
			$w[] = "offset = $offset";
			$db->Execute($sql = "INSERT INTO labeler_agent SET ".implode(',',$w)." ON DUPLICATE KEY UPDATE ".array_pop($w));

		}
		$limit = "$offset,$limit";
	}

	if (!empty($_GET['large'])) {
		$cols .= ", original_width";
		$join .= "inner join gridimage_size using (gridimage_id)";
	}

	####################

	if (empty($_GET['all'])) {
		if ($_GET['model'] == 'type') {
			$where[] = "moderation_status = 'accepted'"; //todo, we might want to also check geos? eg spot potential long-distance/cross grid
			$where[] = "tags NOT like '%type:%'";
		} elseif ($_GET['model'] == 'top') {
			$where[] = "tags NOT like '%top:%'";
		} elseif ($_GET['model'] == 'subject') {
			$where[] = "tags NOT like '%subject:%'";
		} elseif ($_GET['model'] == 'city') {
			//for the moment, the city dataset we wanting to retest the images used for training.
			//... later will ahve to get this data from sphinx or sphinx_placename
			$join .= " inner join gridimage_label_training t on (t.gridimage_id = gi.gridimage_id and folder = 'geograph_visiondata015')";
		} elseif ($_GET['model'] == 'class') {
			$where[] = "imageclass=''";
		}
	}

	if (!empty($_GET['user_id']))
		$where[] = "gi.user_id = ".intval($_GET['user_id']);

	if (empty($_GET['recent']) && empty($_GET['all'])) {
		//only recent users!
		$join .= "inner join user_stat using (user_id)";
		$where[] = "last > 7300000";
	}

	####################

	$qmod = $imagelist->_getDb()->Quote($_GET['model']);
	$where = implode(" AND ",$where);

	if (!empty($_GET['recent'])) {
		$sql = "select gi.gridimage_id,user_id $cols
		from gridimage gi
		$join
		left join gridimage_label l on (l.gridimage_id = gi.gridimage_id and `model` = $qmod)
		where $where
		order by gridimage_id desc
		limit $limit";
	} else {
		$sql = "select gi.gridimage_id,user_id $cols
		from gridimage_search gi
		$join
		left join gridimage_label l on (l.gridimage_id = gi.gridimage_id and `model` = $qmod)
		where $where
		limit $limit";
	}


	$imagelist->_getImagesBySql($sql);

	####################

	if (count($imagelist->images)) {
		foreach ($imagelist->images as $i => $image) {
			if (empty($_GET['full'])) {
				$imagelist->images[$i]->fullpath = $image->getSquareThumbnail(224,224,'path');

			} elseif (!empty($imagelist->images[$i]->original_width) && isset($_GET['large'])) {
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
