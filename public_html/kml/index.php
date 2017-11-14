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
$kml->filename = "Geograph-Layer-Collection.kml";


$folder = $kml->addChild('Folder');
$folder->setItem('name','Geograph in Google Earth');

$folder->addChild('Style')->addChild('ListStyle')->setItem('listItemType','radioFolder');


$folder->setItemCDATA('description',<<<END_HTML
<table bgcolor="#000066" border="0"><tr bgcolor="#000066"><td bgcolor="#000066">
<a href="http://{$_SERVER['HTTP_HOST']}/"><img src="http://{$_SERVER['HTTP_HOST']}/templates/basic/img/logo.gif" height="74" width="257"/></a>
</td></tr></table>

<p><i>The Geograph British Isles project aims to collect geographically representative photographs and information for every square kilometre of the UK and the Republic of Ireland, and you can be part of it.</i></p>

<p><b>Join us now at: <a href="http://geo.hlipp.de/">geo.hlipp.de</a></b>, and read more about the <a href="http://geo.hlipp.de/kml.php">Google Earth intergration</a>.</p>
END_HTML
);
$folder->setItem('Snippet','Explore Geograph Content...');

$nufolder = $folder->addChild('Folder');
$nufolder->setItem('name','None');
$nufolder->setItem('open',1);
$nufolder->setItem('visibility',1);
	
	
	

$networklink = new kmlNetworkLink(null,'Geograph SuperLayer');
$networklink->setItemCDATA('description',<<<END_HTML
<p>This SuperLayer allows full access to the thousends of images contributed to Geograph since March 2005, the view starts depicting a coarse overview of the current coverage, zooming in reveals more detail until pictures themselves become visible.</p>

<p>Click on the Camera Icon or Thumbnails to view a bigger image, and follow the link to view the full resolution image on the geograph website.</p>

<p>This SuperLayer will automatically update, but by design is not realtime, so can take a number of weeks for new pictures to become available in the SuperLayer.</p>
END_HTML
);
$networklink->setItem('Snippet','move...scroll...rotate...tilt, to view the Geograph Archive...');
$UrlTag = $networklink->useUrl("http://geo.hlipp.de/kml-superlayer.php?download");
$UrlTag->setItem('refreshMode','onInterval');
$UrlTag->setItem('refreshInterval',60*60*24);
$networklink->setItem('visibility',0);
$networklink->setItem('open',0);
$folder->addChild($networklink);





$NetworkLink = $folder->addChild('NetworkLink');
$NetworkLink->setItem('name','Geograph NetworkLink');
$NetworkLink->setItemCDATA('description',"View-Based NetworkLink to view many images - always show recent images, for Google Earth Version 4+ the SuperLayer recommended instead");
$NetworkLink->setItem('open',0);
$NetworkLink->setItem('visibility',0);
$UrlTag = $NetworkLink->useUrl("http://geo.hlipp.de/earth.php?simple=1");
$UrlTag->setItem('viewRefreshMode','onStop');
$UrlTag->setItem('viewRefreshTime',4);
$UrlTag->setItem('viewFormat','BBOX=[bboxWest],[bboxSouth],[bboxEast],[bboxNorth]&amp;LOOKAT=[lookatLon],[lookatLat],[lookatRange],[lookatTilt],[lookatHeading],[horizFov],[vertFov]');





$networklink = new kmlNetworkLink(null,'Recent Images');
$networklink->setItemCDATA('description',"15 recently submitted images");
$UrlTag = $networklink->useUrl("http://geo.hlipp.de/feed/recent.kml");
$UrlTag->setItem('refreshMode','onInterval');
$UrlTag->setItem('refreshInterval',60*60);
$networklink->setItem('visibility',0);
$networklink->setItem('open',0);
$folder->addChild($networklink);


$networklink = new kmlNetworkLink(null,'Recent Articles');
$UrlTag = $networklink->useUrl("http://geo.hlipp.de/article/feed/recent.kml");
$UrlTag->setItem('refreshMode','onInterval');
$UrlTag->setItem('refreshInterval',60*60);
$networklink->setItem('visibility',0);
$networklink->setItem('open',0);
$folder->addChild($networklink);


$networklink = new kmlNetworkLink(null,'Recent Grid Square Discussions');
$UrlTag = $networklink->useUrl("http://geo.hlipp.de/discuss/feed/forum5.kml"); // FIXME forum id?
$UrlTag->setItem('refreshMode','onInterval');
$UrlTag->setItem('refreshInterval',60*60);
$networklink->setItem('visibility',0);
$networklink->setItem('open',0);
$folder->addChild($networklink);





$networklink = new kmlNetworkLink(null,'3D Coverage Graph :: Points');
$networklink->setItemCDATA('description',"Geograph Points breakdown by <a href=\"/help/squares\">Hectad</a>s");
$UrlTag = $networklink->useUrl("http://{$CONF['KML_HOST']}/kml/hectads-points.kmz");
$networklink->setItem('visibility',0);
$networklink->setItem('open',0);
$folder->addChild($networklink);


$networklink = new kmlNetworkLink(null,'3D Coverage Graph :: Images');
$networklink->setItemCDATA('description',"Images Submitted breakdown by <a href=\"/help/squares\">Hectad</a>s");
$UrlTag = $networklink->useUrl("http://{$CONF['KML_HOST']}/kml/hectads-images.kmz");
$networklink->setItem('visibility',0);
$networklink->setItem('open',0);
$folder->addChild($networklink);


$nufolder = $folder->addChild('Folder');
$nufolder->setItem('name','3D Coverage Animation');
$nufolder->setItemCDATA('description',"Please visit the <a href=\"http://www.geograph.org.uk/discuss/index.php?&action=vthread&forum=2&topic=4415\">forum for more information</a>."); //FIXME
$nufolder->setItem('open',0);
$nufolder->setItem('visibility',0);



if (isset($_GET['debug'])) {
	print "<a href=?download>Open in Google Earth</a><br/>";
	print "<textarea rows=30 style=width:100%>";
	print $kml->returnKML(empty($_GET['simple']));
	print "</textarea>";
	exit;
} 


$kml->outputKML(empty($_GET['simple']));
exit;


?>
