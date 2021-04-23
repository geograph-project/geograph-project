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

if (empty($param)) { //special support so this script can be 'include()ed'.
	$param = array('table' => 'gblakes', 'debug'=>0);

	chdir(__DIR__);
	require "./_scripts.inc.php";

	$db = GeographDatabaseConnection(false);
}


                        require_once('geograph/conversions.class.php');
                        $conv = new Conversions;


	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$c=0;
$sql = "SELECT auto_id,geometry_x,geometry_y FROM {$param['table']} WHERE region ='' LIMIT 10000";
$recordSet = $db->Execute($sql);
if ($recordSet->RecordCount()) {

	if (!empty($param['debug']))
		print "Start = {$recordSet->fields['auto_id']}: ";

	while (!$recordSet->EOF) {
		list($lat,$long) = $conv->national_to_wgs84($recordSet->fields['geometry_x'],$recordSet->fields['geometry_y'], 1);

		$region = getRegion($lat,$long);
		$sql = "UPDATE {$param['table']} SET region = '$region' WHERE auto_id = {$recordSet->fields['auto_id']}";
		$db->Execute($sql);
		$recordSet->MoveNext();
		$c++;
		if (!empty($param['debug']) && !($c%100)) print "$c. ";
	}
}

if (!empty($param['debug']))
	print "Done $c\n";

$recordSet->Close();







function getRegion($lat,$lng) {
static $raw = "
54.7246202,-6.6357422,Northern Ireland
55.2661055,-8.4375688,Republic of Ireland
53.4488793,-7.3278438,Republic of Ireland
54.1173828,-9.6569824,Republic of Ireland
52.4493141,-7.5476074,Republic of Ireland
51.773762,-4.0649546,Wales
53.0747225,-3.8562916,Wales
50.9722649,-3.4277344,South West England
50.7091992,-2.1648831,South West England
50.6963913,0.9563003,South East England
52.4157494,-1.658972,Central England
52.1953086,0.978101,East Anglia
53.6084303,-0.4164043,North East England
54.3613576,-3.0871582,North West England
53.6117969,-2.944417,North West England
55.7589074,-3.5046942,East Scotland
55.8371938,-5.2299845,West Scotland
54.9650017,-1.4941406,North East England
54.0554321,-3.5543452,Isle of Man
55.091299,-4.350421,West Scotland
50.7364551,-0.9887695,South East England
";

//53.9062094,-4.152609,Isle of Man

static $points = array();
if (empty($points)) {
	foreach(explode("\n",$raw) as $line)
		if (preg_match('/(-?\d+\.?\d*),(-?\d+\.?\d*),(\w.*)/',$line,$m))
			$points[] = $m;
}
	$dist = 9999999999999;
	$best = null;
	foreach ($points as $point) {
		$d = pow($lat-$point[1],2) + pow($lng-$point[2],2);
		if ($d < $dist) {
			$dist = $d;
			$best = $point[3];
		}
	}
	return $best;
}




