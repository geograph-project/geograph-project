<?php
/**
 * $Project: GeoGraph $
 * $Id: conversion.php 2960 2007-01-15 14:33:27Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2007 Barry Hunter (geo@barryhunter.co.uk)
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
require_once('geograph/kmlfile.class.php');

init_session();

$kml = new kmlFile();
$document = $kml->addChild('Document');
$document->setItem('name','geotest');
$document->addHoverStyle();

$folder = $document->addChild('Folder');
$folder->setItem('name','geotest');

foreach (range(1,2) as $i) {
	$point = new kmlPoint(rand(-9000,9000)/100,rand(-18000,18000)/100);
	$placemark = $folder->addChild(new kmlPlacemark(null,'test'.$i,$point));
	$placemark->makeFloating();
	$placemark->useHoverStyle();
	$placemark->setTimeStamp(date('Y-m-d'));
	#$placemark->useImageAsIcon('bla.png');
	#or $placemark->makeFloating()->useHoverStyle()->useImageAsIcon('bla.png');
}

if (!isset($_GET['nolinks'])) {
	foreach (range(3,4) as $i) {
		$networklink = $folder->addChild(new kmlNetworkLink(null,'test'.$i,$_SERVER['SCRIPT_URI']."?download&nolinks"));
	}
}

if (isset($_GET['download'])) {
	$kml->outputKML(empty($_GET['simple']));
	exit;
} 

print "<a href=?download>Open in Google Earth</a><br/>";
print "<textarea rows=30 style=width:100%>";
print $kml->returnKML(empty($_GET['simple']));
print "</textarea>";


exit;

print "<pre>";
print_r($kml);
?>
