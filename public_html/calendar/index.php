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

$year = date('Y')+1; // we currently working on next years calendar

$smarty->assign('year', $year);

if (date('Y-m-d') > '2022-10-10' && !in_array($USER->user_id, array(3,9181,11141,135767)) ) {
	$smarty->assign('closed',true);
}

####################################

if (!$USER->hasPerm("basic") || !empty($_GET['best'])) {

	$imagelist=new ImageList;

	if (false) {
		//this is NOT normal rows, but gridimage_calendar has enough rows, that it works! (at least to get thumbnails!)
		$sql = "SELECT * FROM gridimage_calendar
	        	LEFT JOIN gridimage_size using (gridimage_id)
		        WHERE calendar_id = {$row['calendar_id']} ORDER BY sort_order";
	} else {
		$sql = "SELECT * FROM gridimage_search INNER JOIN gridimage_query USING (gridimage_id)
			WHERE query_id = 165587901
			ORDER BY month(imagetaken)";
	}
	$imagelist->_getImagesBySql($sql);

	$smarty->assign_by_ref('images', $imagelist->images);

	$smarty->display('calendar_bestof.tpl');
	exit;
}

####################################

$USER->mustHavePerm("basic");

$db = GeographDatabaseConnection(false);

if (!empty($_GET['delete'])) {
	$calendar_id = intval($_GET['delete']);
        $db->Execute("UPDATE calendar SET status = 'deleted' WHERE calendar_id = $calendar_id AND user_id = {$USER->user_id}");
}

####################################

$list = $db->getAll("SELECT * FROM calendar WHERE user_id = {$USER->user_id} AND year = '$year' AND status > 1");

$smarty->assign_by_ref('list', $list);

$smarty->display('calendar.tpl');




