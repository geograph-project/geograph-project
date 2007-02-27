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
require_once('geograph/kmlfile2.class.php');

init_session();

$kml = new kmlFile();
$document = $kml->addChild('Document');


$point = new kmlPoint(rand(5900,6000)/100,rand(100,200)/100);

$placemark = $document->addChild(new kmlPlacemark_Photo(null,'test',$point));

$placemark->setTimeStamp(date('Y-m-d'));
	
$placemark->useImageAsIcon("http://".$_SERVER['HTTP_HOST']."/templates/basic/img/guide1.jpg");

$point2 = new kmlPoint(rand(5900,6000)/100,rand(100,200)/100);

$placemark->addPhotographerPoint($point2);



if (isset($_GET['download'])) {
	$kml->outputKML();
	exit;
} 

print "<a href=?download>Open in Google Earth</a><br/>";
print "<textarea rows=35 style=width:100%>";
print $kml->returnKML();
print "</textarea>";


exit;

print "<pre>";
print_r($kml);
?>
