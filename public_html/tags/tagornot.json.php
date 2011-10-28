<?php
/**
 * $Project: GeoGraph $
 * $Id: tags.json.php 7424 2011-09-22 21:37:52Z barry $
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


if (!empty($_GET['callback'])) {
	header('Content-type: text/javascript');
} else {
	header('Content-type: application/json');
}

init_session();
if (!$USER->registered) {
	die("{error: 'not logged in'}");
}

$db = GeographDatabaseConnection(false);

$sql = array();

if (!empty($_GET['tag_id'])) {

	if (!empty($_GET['gridimage_id'])) {

		if (!empty($_GET['doit']) && $_GET['doit'] == 1) {
			$u = array();

			$u['tag_id'] = intval($_GET['tag_id']);
			$u['user_id'] = $USER->user_id;
			$u['gridimage_id'] = intval($_GET['gridimage_id']);
			
			$u['status'] = 1;

			$db->Execute('REPLACE INTO gridimage_tag SET created=NOW(),`'.implode('` = ?, `',array_keys($u)).'` = ?',array_values($u));
		}
		
		$w = array();
		
		$w['user_id'] = $USER->user_id;
		$w['tag_id'] = intval($_GET['tag_id']);
		$w['gridimage_id'] = intval($_GET['gridimage_id']);
			
		if (!empty($_GET['doit']) && $_GET['doit'] == -1) {
			##$db->Execute("UPDATE tagornot SET user_ids = REPLACE(user_ids,'|{$w['user_id']}|','') WHERE tag_id = {$w['tag_id']} AND gridimage_id = {$w['gridimage_id']}");
		} else {
			$db->Execute("UPDATE tagornot SET user_ids = CONCAT(user_ids,'|{$w['user_id']}|'),done=done+1 WHERE tag_id = {$w['tag_id']} AND gridimage_id = {$w['gridimage_id']}");
		}
	}

	$sql['columns'] = "tagornot_id,gridimage_id,tag_id,title,user_id,realname,grid_reference,comment,imageclass";
	
        $sql['tables'] = array();
	$sql['tables']['tn'] = 'tagornot';
	$sql['tables']['gi'] = "INNER JOIN `gridimage_search` USING (gridimage_id)";

        $sql['wheres'] = array();
        $sql['wheres'][] = "tag_id = ".intval($_GET['tag_id']);
        $sql['wheres'][] = "done=0";
#        $sql['wheres'][] = "user_ids NOT LIKE '%|{$USER->user_id}|%'";
        $sql['wheres'][] = "user_ids = ''";
        
        $sql['limit'] = '1';
}

$query = sqlBitsToSelect($sql);

$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
$data = $db->getRow($query);

if (!empty($data['tagornot_id']))
	$db->Execute("UPDATE tagornot SET user_ids = CONCAT(user_ids,'|{$USER->user_id}|') WHERE tagornot_id = {$data['tagornot_id']}");
	
if (!empty($data['gridimage_id'])) {
	$image = new Gridimage();
	$image->gridimage_id = $data['gridimage_id'];
	$image->user_id = $data['user_id'];
	$data['image'] = $image->_getFullpath(true,true);	
} else {
	die("{error: 'no more images'}");
}

########

if (!empty($_GET['callback'])) {
        $callback = preg_replace('/[^\w\.-]+/','',$_GET['callback']);
        echo "{$callback}(";
}

require_once '3rdparty/JSON.php';
$json = new Services_JSON();
print $json->encode($data);

if (!empty($_GET['callback'])) {
        echo ");";
}



