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

$_SESSION['large'] = 1; //trick to ensure the phtoo page displays a large image!

$smarty = new GeographPage;

$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

//$count = $db->cacheGetOne(86400,"select count(*) from curated1 inner join gridimage_search using (gridimage_id) inner join gridimage_size using (gridimage_id) where active>0 and GREATEST(original_width,original_height) > 1000 and `group` = 'Geography and Geology'");
$count = $db->getOne("SELECT SUM(larger) FROM curated1_stat");
$smarty->assign('imagecount', $count);


if (!empty($_GET['dev'])) {
	$smarty->display('curated_sample_dev.tpl');
} else
	$smarty->display('curated_sample.tpl');

