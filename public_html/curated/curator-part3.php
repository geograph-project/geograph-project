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
customNoCacheHeader();

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

if (!empty($_POST['action'])) {

        $updates = array();
        $updates['user_id'] = intval($USER->user_id);
        $updates['gridimage_id'] = intval($_POST['gridimage_id']);

	$plus = 0;
	if ($_POST['action'] == 'skip') {
		$_POST['tags'] = array('Skip');
	} else if ($_POST['action'] == 'looksok') {
		$_POST['tags'] = array('Verified');
	} else if ($_POST['action'] == 'errors') {
		$_POST['tags'] = array('Errors');
	}

	//Then add/enable the ticked ones!
        foreach ($_POST['tags'] as $key => $tag) {
		$updates['tag'] = str_replace('top:','',$tag);
	        $db->Execute('INSERT INTO curated_tag SET `'.implode('` = ?,`',array_keys($updates)).'` = ?  ON DUPLICATE KEY UPDATE status=1', array_values($updates));
		$plus+=$db->Affected_Rows();
	}

	print "$plus added.";
}

$sql = "SELECT gi.gridimage_id, gi.user_id, title, grid_reference, gi.tags, GROUP_CONCAT(one.tag SEPARATOR '?') as done
FROM gridimage_search gi
INNER JOIN curated_tag one ON (one.gridimage_id = gi.gridimage_id AND one.status = 1)
LEFT JOIN curated_tag two ON (two.gridimage_id = gi.gridimage_id AND two.status = 1 AND two.tag IN ('Skip','Verified','Errors'))
WHERE two.gridimage_id IS NULL AND gi.user_id != {$USER->user_id}
GROUP BY gi.gridimage_id
HAVING done LIKE '%Checked%'
ORDER BY RAND()
LIMIT 1";

$row = $db->getRow($sql);
if (!empty($row)) {
         $image = new GridImage();
         $image->fastInit($row);

	$tagarray = array();
	//as well as the curated ones!
	foreach (explode('?',$row['done']) as $tag) {
		$tagarray[str_replace('top:','',$tag)] = 1;
	}
	$smarty->assign_by_ref('tagarray', $tagarray);

	$smarty->assign_by_ref('image', $image);

	$tags = new Tags;
	$tags->assignPrimarySmarty($smarty);

} else {
	die("no images found, perhaps need to select some in step 1?");
}

$smarty->display('curated_curator_part3.tpl');
