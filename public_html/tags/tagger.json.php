<?php
/**
 * $Project: GeoGraph $
 * $Id: tags.json.php 7071 2011-02-04 00:39:05Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2011 Barry Hunter (geo@barryhunter.co.uk)
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



#header("HTTP/1.0 204 No Content");
#header("Status: 204 No Content");
#header("Content-Length: 0");

#header('Content-type: application/json');

if (!empty($_GET['upload_id'])) {

	$gid = crc32($_GET['upload_id'])+4294967296;
	$gid += $USER->user_id * 4294967296;
	$_GET['gridimage_id'] = sprintf('%0.0f',$gid);
}

if (!empty($USER->user_id) && !empty($_GET['tag']) && !empty($_GET['gridimage_id']) && preg_match('/^\d+$/',$_GET['gridimage_id'])) {

	$db = GeographDatabaseConnection(false);
	$u = array();
	$u['tag'] = $_GET['tag'];
	$bits = explode(':',$u['tag']);
	if (count($bits) > 1) {
		$u['prefix'] = trim($bits[0]);
		$u['tag'] = $bits[1];
	}
	$u['tag'] = trim(preg_replace('/[ _]+/',' ',$u['tag']));

	if ($u['prefix'] == 'id' && preg_match('/^(\d+)$/',$u['tag'],$m)) {
		$tag_id = $m[1];
	} else {
		$tag_id = $db->getOne("SELECT tag_id FROM `tag` WHERE `tag` = ".$db->Quote($u['tag'])." AND `prefix` = ".$db->Quote($u['prefix']));
	}

	if (empty($tag_id)) {
	
		if (empty($_GET['status'])) {
			//no need to delete a tag never created!
			exit;
		}
	
		//need to create it!

		$u['user_id'] = $USER->user_id;

		$db->Execute('INSERT INTO tag SET created=NOW(),`'.implode('` = ?, `',array_keys($u)).'` = ?',array_values($u));

		$tag_id = mysql_insert_id();
	}

	$u = array();

	$u['tag_id'] = $tag_id;
	$u['user_id'] = $USER->user_id;
	
	$ids = array($_GET['gridimage_id']);
	
	foreach ($ids as $gid) {
		$u['gridimage_id'] = $gid;
		
		if ($_GET['status'] == 0) { 
			unset($u['status']);
			
			$db->Execute('DELETE FROM gridimage_tag WHERE `'.implode('` = ? AND `',array_keys($u)).'` = ?',array_values($u));
		
		} elseif ($_GET['status'] == -1) { 
			unset($u['status']);
			
			$db->Execute('DELETE FROM gridimage_tag WHERE `'.implode('` = ? AND `',array_keys($u)).'` = ?',array_values($u));
			
			unset($u['user_id']);
			$db->Execute('INSERT INTO tagornot_archive SELECT * FROM tagornot WHERE `'.implode('` = ? AND `',array_keys($u)).'` = ?',array_values($u));
			$db->Execute('DELETE FROM tagornot WHERE `'.implode('` = ? AND `',array_keys($u)).'` = ?',array_values($u));
			$db->Execute('INSERT tagornot SET created=NOW(),`'.implode('` = ? , `',array_keys($u)).'` = ?',array_values($u));
			
			$u['user_id'] = $USER->user_id;
			$db->Execute('INSERT INTO tag_dispute_log SET created=NOW(),`'.implode('` = ? , `',array_keys($u)).'` = ?',array_values($u));
	
		} else {
			$u['status'] = 1;
			if ($_GET['status'] == 2 && ($gid > 4294967296 || $db->getOne("SELECT gridimage_id FROM gridimage WHERE gridimage_id = $gid AND user_id = {$USER->user_id}"))) {
				$u['status'] = 2;
			}
	
			$db->Execute('REPLACE INTO gridimage_tag SET created=NOW(),`'.implode('` = ?, `',array_keys($u)).'` = ?',array_values($u));
			
			if ($u['status'] == 2 && $gid < 4294967296) {
				$smarty = new GeographPage;

				//clear any caches involving this photo
				$ab=floor($gid/10000);
				$smarty->clear_cache(null, "img$ab|{$gid}");
			}
		}
	}
}

