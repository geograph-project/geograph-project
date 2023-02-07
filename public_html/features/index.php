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

//$USER->mustHavePerm("basic");

####################################

$template = 'features_index.tpl';
$cacheid = '';
$smarty->caching = 0; //just for now! while datasets are in flux

if (!$smarty->is_cached($template, $cacheid)) {
	$db = GeographDatabaseConnection(true);

	$list = $db->getAll("SELECT f.*,count(feature_item_id) as `rows`,format(sum(gridimage_id>0)/count(feature_item_id)*100,1) as percent
		 FROM feature_type f INNER JOIN feature_item i USING (feature_type_id,status)
		 WHERE status = 1 AND licence != 'none' GROUP BY feature_type_id");

	$smarty->assign_by_ref('list', $list);
}

$smarty->display($template, $cacheid);




