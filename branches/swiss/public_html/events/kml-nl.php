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

preg_match('/(kh_|GoogleEarth)\w+\/\w+(\d).(\d)/',$_SERVER['HTTP_USER_AGENT'],$m);

$kml = new kmlFile();
$kml->filename = "Geograph-Events.kml";


$NetworkLink = $kml->addChild('NetworkLink');
$NetworkLink->setItem('name','Geograph Events');
$NetworkLink->setItemCDATA('description',"Upcoming Geograph Events");
$NetworkLink->setItem('open',1);

$UrlTag = $NetworkLink->useUrl("http://{$_SERVER['HTTP_HOST']}/events/feed.kml");
$NetworkLink->setItem('visibility',1);
$UrlTag->setItem('refreshMode','onInterval');
$UrlTag->setItem('refreshInterval',3600);

if (isset($_GET['debug'])) {
	print "<a href=?download>Open in Google Earth</a><br/>";
	print "<textarea rows=35 style=width:100%>";
	print $kml->returnKML();
	print "</textarea>";
} else {
	$kml->outputKML();
}
exit;


?>
