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
$kml->filename = "Geograph-Layer.kml";

$i=(!empty($_GET['i']))?intval($_GET['i']):'';

if (empty($_SERVER['HTTP_USER_AGENT']) 
			|| $_SERVER['HTTP_USER_AGENT'] == 'ArcGIS Client Using WinInet' 
			|| $_SERVER['HTTP_USER_AGENT'] == '-' //Google Maps (Mapplet)
			|| strpos($_SERVER['HTTP_USER_AGENT'],"BROKEN-Kml-Google;") === 0 ) { //Google Maps NEW 
	
	$NetworkLink = $kml->addChild('NetworkLink');
	$NetworkLink->setItem('name','Geograph NetworkLink');
$NetworkLink->setItemCDATA('description',<<<END_HTML
<table bgcolor="#000066" border="0"><tr bgcolor="#000066"><td bgcolor="#000066">
<a href="http://{$_SERVER['HTTP_HOST']}/"><img src="http://{$_SERVER['HTTP_HOST']}/templates/basic/img/logo.gif" height="74" width="257"/></a>
</td></tr></table>

<p><i>The Geograph British Isles project aims to collect geographically representative photographs and information for every square kilometre of the UK and the Republic of Ireland, and you can be part of it.</i></p>

<p><b>Join us now at: <a href="http://{$_SERVER['HTTP_HOST']}/">{$_SERVER['HTTP_HOST']}</a></b>, and read more about the <a href="http://{$_SERVER['HTTP_HOST']}/kml.php">Google intergration</a>.</p>
END_HTML
);
	$NetworkLink->setItem('open',0);

	$UrlTag = $NetworkLink->useUrl("http://{$_SERVER['HTTP_HOST']}/earth.php?simple=1".($i?"&i=$i":''));
	$NetworkLink->setItem('visibility',0);

	$UrlTag->setItem('viewRefreshMode','onStop');
	$UrlTag->setItem('viewRefreshTime',4);
	$UrlTag->setItem('viewFormat','BBOX=[bboxWest],[bboxSouth],[bboxEast],[bboxNorth]&amp;LOOKAT=[lookatLon],[lookatLat],[lookatRange],[lookatTilt],[lookatHeading],[horizFov],[vertFov]');

	$LookAt = $NetworkLink->addChild('LookAt');
	$LookAt->setItem('longitude',-2.968);
	$LookAt->setItem('latitude',55.031);
	$LookAt->setItem('altitude',0);
	$LookAt->setItem('range',1253729);
	$LookAt->setItem('heading',0);
	$LookAt->setItem('tilt',0);

} elseif ($m[2] == 3) { //GE 3
	$NetworkLink = $kml->addChild('NetworkLink');
	$NetworkLink->setItem('name','Geograph NetworkLink');
	$NetworkLink->setItemCDATA('description',"Please upgrade to Google Earth Version 4 to take advantage latest Superlayer");
	$NetworkLink->setItem('open',0);

	$UrlTag = $NetworkLink->useUrl("http://{$_SERVER['HTTP_HOST']}/earth.php?simple=1".($i?"&i=$i":''));
	$NetworkLink->setItem('visibility',0);

	$UrlTag->setItem('viewRefreshMode','onStop');
	$UrlTag->setItem('viewRefreshTime',4);
	$UrlTag->setItem('viewFormat','BBOX=[bboxWest],[bboxSouth],[bboxEast],[bboxNorth]&amp;LOOKAT=[lookatLon],[lookatLat],[lookatRange],[lookatTilt],[lookatHeading],[horizFov],[vertFov]');

} elseif ($m[2] == 4 || isset($_GET['download'])) { 
	
	if ($i) {
		$cache_file = "kml/$i/geograph.kmz";
	} else {
		$cache_file = "kml/geograph.kmz";
	} 
	
	$mtime = filemtime($cache_file);


	//use the filename as a hash
	customCacheControl($mtime,$cache_file);	

	Header("Content-Type: application/vnd.google-earth.kmz+xml; charset=utf-8; filename=geograph.kmz");
	Header("Content-Disposition: attachment; filename=\"geograph$i.kmz\"");
	
	header('Content-length: '.filesize($cache_file));

	readfile($cache_file);
	exit;

} else { 
	$networklink = new kmlNetworkLink(null,'Geograph SuperLayer');

$desc = <<<END_HTML
<table bgcolor="#000066" border="0"><tr bgcolor="#000066"><td bgcolor="#000066">
<a href="http://{$_SERVER['HTTP_HOST']}/"><img src="http://{$_SERVER['HTTP_HOST']}/templates/basic/img/logo.gif" height="74" width="257"/></a>
</td></tr></table>

<p><i>The Geograph British Isles project aims to collect geographically representative photographs and information for every square kilometre of the UK and the Republic of Ireland, and you can be part of it.</i></p>

<p>Click on the Camera Icon or Thumbnails to view a bigger image, and follow the link to view the full resolution image on the geograph website.</p>
END_HTML;

if ($i) {
	require_once('geograph/searchcriteria.class.php');
	require_once('geograph/searchengine.class.php');
	$engine = new SearchEngine($i);
	$desc .= "<p>Displaying results for search for images<i>".htmlentities2($engine->criteria->searchdesc)."</i></p>";
} else {
$desc .= <<<END_HTML
<p>This SuperLayer allows full access to the thousends of images contributed to Geograph since March 2005, the view starts depicting a coarse overview of the current coverage, zooming in reveals more detail until pictures themselves become visible.</p>

<p>This SuperLayer will automatically update, but by design is not realtime, so can take a number of weeks for new pictures to become available in the SuperLayer.</p>
END_HTML;
}

$desc .= <<<END_HTML
<p><b>Join us now at: <a href="http://{$_SERVER['HTTP_HOST']}/">{$_SERVER['HTTP_HOST']}</a></b></p>
END_HTML;

$networklink->setItemCDATA('description',$desc);
$networklink->setItem('Snippet','move...scroll...rotate...tilt, to view the Geograph Archive...');

	$UrlTag = $networklink->useUrl("http://{$_SERVER['HTTP_HOST']}/kml-superlayer.php?download".($i?"&i=$i":''));
	$UrlTag->setItem('refreshMode','onInterval');
	$UrlTag->setItem('refreshInterval',60*60*24);
	$kml->addChild($networklink);
} 

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
