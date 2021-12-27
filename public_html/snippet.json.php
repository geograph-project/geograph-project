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

if (!empty($USER->registered) && !empty($_GET['snippet_id']) && !empty($_GET['gridimage_id']) && preg_match('/^\d+$/',$_GET['gridimage_id'])) {

	$db = GeographDatabaseConnection(false);

	if (!is_numeric($_GET['snippet_id'])) {

		if (empty($_GET['status'])) {
			//no need to delete a snippet never created!
			exit;
		}

		//need to create it!
		$u = array();
		$u['title'] = $_GET['title']; //its creating a new one!
		$u['user_id'] = $USER->user_id;
		$u['enabled'] = 1;

		//spatical columns dont support empty as such. NULL doesnt work
		$point = "'POINT(0 0)'";

		$db->Execute('INSERT INTO snippet SET created=NOW(),point_en=GeomFromText('.$point.'),`'.implode('` = ?, `',array_keys($u)).'` = ?',array_values($u));

		$snippet_id = $db->Insert_ID();
	} else {
		$snippet_id = intval($_GET['snippet_id']);
	}

	$u = array();

	$u['snippet_id'] = $snippet_id;
	$u['user_id'] = $USER->user_id;

	$ids = array($_GET['gridimage_id']);

	foreach ($ids as $gid) {
		$u['gridimage_id'] = $gid;

		if ($_GET['status'] == 0) {

			$db->Execute('DELETE FROM gridimage_snippet WHERE `'.implode('` = ? AND `',array_keys($u)).'` = ?',array_values($u));

		} else {

			$db->Execute('INSERT INTO gridimage_snippet SET created=NOW(),`'.implode('` = ?, `',array_keys($u)).'` = ?',array_values($u));

			if ($gid < 4294967296) {
				$smarty = new GeographPage;

				//clear any caches involving this photo
				$ab=floor($gid/10000);
				$smarty->clear_cache(null, "img$ab|{$gid}");

				$smarty->clear_cache("snippet.tpl", $gid);

				$memcache->name_delete('sd', $gid);
			}
		}
	}
}

