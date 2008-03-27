<?php
/**
 * $Project: GeoGraph $
 * $Id: export.csv.php 2805 2006-12-30 12:03:55Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2008 Barry Hunter (geo@barryhunter.co.uk)
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
 **/
 
require_once('geograph/global.inc.php');
init_session();

require_once("3rdparty/xmlHandler.class.php");

$smarty = new GeographPage;

//you must be logged in to request changes
$USER->mustHavePerm("basic");

$template='puploader.tpl';
$cacheid='';

if(!empty($_POST['rss'])) {
	$xh = new xmlHandler();
	$nodeNames = array("PHOTO:THUMBNAIL", "PHOTO:IMGSRC", "TITLE");
	$xh->setElementNames($nodeNames);
	$xh->setStartTag("ITEM");
	$xh->setVarsDefault();
	$xh->setXmlParser();
	$xh->setXmlData(stripslashes($_POST['rss']));
	$pData = $xh->xmlParse();
	
	$smarty->assign_by_ref('pData', $pData);
	
	
	
	
	#foreach($pData as $e) {
	#	$titles[] = $e['title'];
	#	$previews[] = $e['photo:thumbnail']."?size=-96";
	#	$uploads[] = $e['photo:imgsrc']."?size=640";
	#}



}

$smarty->display($template, $cacheid);

?>
