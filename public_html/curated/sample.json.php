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

$sph = GeographSphinxConnection('sphinxql',true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

########################################################################
// Overall Filters for ALL queries

$query1 = '';
$where1 = array();
//$query1 .= " @types Geograph"; //todo, should be at end really!

if (!empty($_GET['context']))
	$query1 .= " @tags ".$sph->Quote("\"top ".$_GET['context']."\"");

if (!empty($_GET['geo'])) {
        $bits = explode(',',$_GET['geo']);
	//https://stackoverflow.com/questions/5217348/how-do-i-convert-kilometres-to-degrees-in-geodjango-geos
	$d = ($bits[2]/1000)/40000*360;
	$where1[] = sprintf("wgs84_lat BETWEEN %.8f AND %.8f", deg2rad($bits[0]-$d), deg2rad($bits[0]+$d));
	$where1[] = sprintf("wgs84_long BETWEEN %.8f AND %.8f", deg2rad($bits[1]-$d), deg2rad($bits[1]+$d));
	//TODO, we could do fancy spherical maths here, but for now, just do basic BBOX style query !?!
}

########################################################################
// do a group by query on each demension

$dimensions = array('group','label','region','decade');

$data = array();
foreach ($dimensions as $dimension) {
	$sql1 = "SELECT `$dimension`,count(*) as images FROM curated1 WHERE";
	$sql2 = "GROUP BY `$dimension` ORDER BY `$dimension` ASC LIMIT 1000";
	$where = $where1;
	$query = '';
	$where[] = "`$dimension` != ''";
	foreach ($dimensions as $dimension2) {
		if (!empty($_GET[$dimension2]) && $dimension2 != $dimension && $_GET[$dimension2] != 'Group By')
			//$where[] = "`$dimension2` = ".$sph->Quote($_GET[$dimension2]);
			$query = "@$dimension2 ".$_GET[$dimension2];
	}
	if (!empty($query1) || !empty($query))
		$where[] = "MATCH(".$sph->Quote(trim($query.' '.$query1)).")";
	if (empty($where)) $where[] = "sequence>1"; //sphinx cant just do 'true;
	$data[$dimension] = $sph->getAssoc($sqls[] = $sql1." ".implode(" AND ",$where)." ".$sql2);
}

########################################################################
// setup where filters for fetching images.

$where = $where1;
$filtered = 0;
foreach ($dimensions as $dimension2) {
	if (!empty($_GET[$dimension2]) && $_GET[$dimension2] != 'Group By') {
		//$where[] = "`$dimension2` = ".$sph->Quote($_GET[$dimension2]);
		$query1 .= " @$dimension2 ".$_GET[$dimension2];
		$filtered++;
	}
}
if (!empty($query1))
	$where[] = "MATCH(".$sph->Quote($query1).")";
if (empty($where)) $where[] = "sequence>1";

########################################################################
// lookup the actual images (which MAY be a group by query!

$cols = "`".implode("`,`",$dimensions)."`";
$sql1 = "SELECT id,user_id,realname,title,grid_reference,takenday,$cols,larger,wgs84_lat,wgs84_long, if(larger='',0,1) as has_larger, if(types = '_SEP_ Geograph _SEP_',1,0) as is_geo
	FROM curated1
	WHERE ";
$sql2 = "ORDER BY has_larger DESC, is_geo DESC, sequence ASC LIMIT 20";

if (!empty($_GET['region']) && $_GET['region'] == 'Group By') {

	$sql2 = "GROUP BY `region` ORDER BY `region` ASC"; //nolimit! (under 20 rows!)
	$data['sectioner'] = 'region';

	//so it can be selected!
	$data['region']['Group By'] = '?';

} elseif (!$filtered) { //no dimension filters!
	$sql2 = "GROUP BY `group` ORDER BY `group` ASC"; //nolimit! (under 20 rows!)
	$data['sectioner'] = 'group';
} elseif ($filtered === 1 && !empty($_GET['group'])) { //only filtered by group!
	$sql2 = "GROUP BY `label` ORDER BY `label` ASC LIMIT 1000";
	$data['sectioner'] = 'label';
} else {
	if (!empty($data['region'])) {
		$data['region']['Group By'] = '?';
	}
}

$data['images'] = $sph->getAll($sqls[] = $sql1." ".implode(" AND ",$where)." ".$sql2);

if ($sph->ErrorNo())
	$data['error'] = $sph->ErrorMsg();

if (!empty($data['images'])) {
	foreach ($data['images'] as $idx => &$row) {
		//convert sphinx column names to mysql names
		$row['gridimage_id'] = $row['id'];
		if (preg_match('/(\d{4})(\d{2})(\d{2})/',$row['takenday'],$m))
			$row['imagetaken'] = $m[1].'-'.$m[2].'-'.$m[3];
		$row['largest'] = intval($row['larger']); //larger is a (sorted!) list of bigger sizes, latest is expeced to be a number.
		if (!empty($data['sectioner']))
			$row['section'] = $row[$data['sectioner']];
		//would need to convert lat/long, to degrees, but currently unsused
		$image = new GridImage;
                $image->fastInit($row);
                $data['images'][$idx]['thumbnail'] = $image->getThumbnail(213,160,true);
	}
}

########################################################################

//$data['sqls'] = $sqls;
header('Access-Control-Allow-Origin: *');
customExpiresHeader(3600*24,true);
outputJSON($data);




