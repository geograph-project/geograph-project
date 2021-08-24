<?php
/**
 * $Project: GeoGraph $
 * $Id: conversion.php 5502 2009-05-13 14:18:23Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 BArry Hunter (geo@barryhunter.co.uk)
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

$smarty = new GeographPage;
$USER->mustHavePerm("basic");



if (!empty($_POST['ids'])) {

	$str = preg_replace('/[\w:\/\.]*\/(\d{6,7})_\w{8}(_\w+)?\.jpg/','$1',$_POST['ids']); //replace any thumbnail urls with just the id.
        $str = trim(preg_replace('/[^\d]+/',' ',$str));
	$done = 0;
	$ids = explode(' ',$str);


	$db = GeographDatabaseConnection(false);

        $updates = array();
	$updates['user_id'] = intval($USER->user_id);

	if (count($ids) == 13) {
		//if user uses the same image for cover, then we need to record in cover_image (gridimage_calendar, can't store repeat images!)
		foreach ($ids as $idx => $id) {
			if ($idx > 0 && $id == $ids[0]) {
				$updates['cover_image'] = $ids[0];
				unset($ids[0]);
				break;
			}
		}
	}

	$db->Execute('INSERT IGNORE INTO calendar SET created = NOW(),`'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));

	$updates['calendar_id'] = $db->Insert_ID();
	if (isset($updates['cover_image']))
		unset($updates['cover_image']);

        foreach ($ids as $id) {
		if ($row = $db->getRow("SELECT gridimage_id,user_id,grid_reference,title,realname,imagetaken FROM gridimage_search WHERE gridimage_id = ".intval($id))) {
			foreach ($row as $key => $value)
				$updates[$key] = $value;
			$updates['sort_order'] = $done + ((count($ids) == 12)?1:0);

			$db->Execute($sql = 'INSERT IGNORE INTO gridimage_calendar SET created = NOW(),`'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates))
				or  die("$sql\n".$db->ErrorMsg()."\n\n");

			$done+=$db->Affected_Rows();
		} else {
			print "Unable to find ".htmlentities($id)."<hr>";
		}
	}

	print "<p>$done image(s) added. Thank you.</p>";
	header("Location: edit.php?id={$updates['calendar_id']}");
	exit;
}

$smarty->display('calendar_start.tpl');




