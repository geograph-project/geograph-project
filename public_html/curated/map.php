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

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


if (!empty($_GET['group'])) {
	$where= array('active=1');
	$table = "curated1";

	$where[] = "`group` = ".$db->Quote($_GET['group']);
} else {
	$where= array('active > 1');
	$table = "curated";
}

if (isset($_GET['label']))
	$where[] = "`label` = ".$db->Quote($_GET['label']);
if (isset($_GET['region']))
	$where[] = "`region` = ".$db->Quote(($_GET['region']=='unspecified')?'':$_GET['region']);
if (!empty($_GET['caption']))
	$where[] = "`caption` = ".$db->Quote($_GET['caption']);
if (!empty($_GET['feature']))
	$where[] = "`feature` = ".$db->Quote($_GET['feature']);

$where = implode(" AND ",$where);
$ids = $db->getCol("SELECT gridimage_id
		FROM $table c INNER JOIN gridimage_search g USING (gridimage_id) WHERE $where ORDER BY label, region, active desc, curated_id desc");

if (!empty($ids)) {
	$str = '';
	if (isset($_COOKIE['markedImages']) && !empty($_COOKIE['markedImages'])) {
		$str = $_COOKIE['markedImages'];
		foreach ($ids as $id)
			if (!preg_match('/\b'.$id.'\b/',$str))
				$str .= ",$id";
	} else {
		$str = implode(',',$ids);
	}

	//setcookie('markedImages', $str, time()+3600*24*10,'/');
	//setcookie urlencodes the string, and setrawcookie discards cookie if contains comma!
	//markedImages= ... ; expires=Mon, 26-Jun-2017 12:32:10 GMT; Max-Age=864000; path=/

	$age = 3600*24*10;
	//Wdy, DD-Mon-YYYY HH:MM:SS GMT
	$date = date('D, j-M-Y H:i:s', time()+$age)." GMT";

	header("Set-Cookie: markedImages=$str; expires=$date; Max-Age=$age; path=/");
}

header("Location: /browser/#!/marked=1/display=map");
