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
init_session();

$data = array();
customNoCacheHeader();

if (!empty($_POST['confirm'])) {
	$db = GeographDatabaseConnection(false);

	$updates = array();
	$updates['tag'] = $_GET['tag'];

	$done = 0;
	if (!empty($_GET['add'])) {
		$updates['user_id'] = $USER->user_id;
		foreach (explode(',',$_GET['ids']) as $id) {
        	        $updates['gridimage_id'] = intval($id);

	                $db->Execute('INSERT INTO curated_tag SET `'.implode('` = ?,`',array_keys($updates)).'` = ?  ON DUPLICATE KEY UPDATE status=1', array_values($updates));
        	        $done+=$db->Affected_Rows();
	        }
	} elseif (!empty($_GET['remove'])) {
		foreach (explode(',',$_GET['ids']) as $id) {
        	        $updates['gridimage_id'] = intval($id);
			$db->Execute('UPDATE curated_tag SET status = 0 WHERE `'.implode('` = ? AND `',array_keys($updates)).'` = ?', array_values($updates));
        	        $done+=$db->Affected_Rows();
		}
	}
	$data['done'] = $done;

} else {
	$imagelist=new ImageList;
	$db = $imagelist->_getDB(true);

	$where = array();
	$where[] = "tag = ".$db->Quote($_GET['tag']??'');
	$where[] = "status = 1";

	$where = implode(' AND ', $where);
	$sql = "select gridimage_id,title,realname,gi.user_id
	 from gridimage_search gi inner join curated_tag t using (gridimage_id)
	 where $where order by (gi.user_id = $USER->user_id) DESC, gridimage_id DESC limit 100";

	$imagelist->_getImagesBySql($sql);

	if (count($imagelist->images)) {
		//Note, deliberately mimics output of api-facetql.php!
		$data['rows'] = array();
		$data['meta'] = array('total'=>count($imagelist->images));
			//total_found would need more work!

		foreach ($imagelist->images as $i => $image) {
			$row = array();
			$row['id'] = $image->gridimage_id;
			$row['user_id'] = $image->user_id;
			$row['title'] = latin1_to_utf8($image->title);
			$row['realname'] = latin1_to_utf8($image->realname);
			$row['hash'] = $image->_getAntiLeechHash();
			$data['rows'][] = $row;
		}
	} else {
		$data["error"] = "no results";
	}
}

outputJSON($data);

