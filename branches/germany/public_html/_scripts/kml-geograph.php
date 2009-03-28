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

if ( ($_SERVER['REMOTE_ADDR'] != $_SERVER['SERVER_ADDR']) &&
     (strpos($_SERVER['HTTP_X_FORWARDED_FOR'],$CONF['server_ip']) !== 0) )  //begins with
{
	init_session();
        $USER->mustHavePerm("admin");
}

$db=NewADOConnection($GLOBALS['DSN']);
if (!$db) die('Database connection failed');  

require_once('geograph/conversions.class.php');
$conv = new Conversions;

$html = '';
$kml = new kmlFile();
$stylefile = "http://{$CONF['KML_HOST']}/kml/style.kmz";

$folder = $kml->addChild('Document');
$folder->setItem('name','Geograph SuperLayer');

$date = date(DATE_RFC850);

$folder->setItemCDATA('description',<<<END_HTML
<table bgcolor="#000066" border="0"><tr bgcolor="#000066"><td bgcolor="#000066">
<a href="http://{$_SERVER['HTTP_HOST']}/"><img src="http://{$_SERVER['HTTP_HOST']}/templates/basic/img/logo.gif" height="74" width="257"/></a>
</td></tr></table>

<p><i>The Geograph British Isles project aims to collect geographically representative photographs and information for every square kilometre of the UK and the Republic of Ireland, and you can be part of it.</i></p>

<p>This SuperLayer allows full access to the thousends of images contributed to Geograph since March 2005, the view starts depicting a coarse overview of the current coverage, zooming in reveals more detail until pictures themselves become visible.</p>

<p>Click on the Camera Icon or Thumbnails to view a bigger image, and follow the link to view the full resolution image on the geograph website.</p>

<p><b>Join us now at: <a href="http://{$_SERVER['HTTP_HOST']}/">{$_SERVER['HTTP_HOST']}</a></b></p>

<p>This SuperLayer will automatically update, but by design is not realtime, so can take a number of weeks for new pictures to become available in the SuperLayer.</p>

<p><i>This Layer Last Updated: {$date}</i></p>

END_HTML
);
$folder->setItem('Snippet','move...scroll...rotate...tilt, to view the Geograph Archive...');

$circles = new kmlPrimative('Folder');
$circles->setItem('name','Myriad Coverages');

$links = new kmlPrimative('Folder');
$links->setItem('name','Next Level...');


$names = $db->getAssoc("select prefix,title from gridprefix");


foreach ($CONF['references'] as $ri => $rname) {
	$letterlength = $CONF['gridpreflen'][$ri];

	$origin = $db->CacheGetRow(100*24*3600,"select origin_x,origin_y from gridprefix where reference_index=$ri and origin_x > 0 order by origin_x,origin_y limit 1");


	$most = $db->GetAll("select 
	grid_reference,x,y,avg(x) as avgx,avg(y) as avgy,
	substring(grid_reference,1,$letterlength) as hunk_square,
	sum(has_geographs) as geograph_count,
	sum(percent_land >0) as land_count,
	(sum(has_geographs) * 100 / sum(percent_land >0)) as percentage
	from gridsquare 
	where reference_index = $ri 
	group by hunk_square 
	having geograph_count > 0 
	order by hunk_square");

	foreach($most as $id=>$entry) 
	{
		#if ($entry['land_count'] < 100) {
		#	//100% = 10k
		#	$radius = $entry['percentage'] * 100;
		#} else {
		#	//100% = 50k
		#	$radius = $entry['percentage']/100 * 50000;
		#}
		
		
		// 	100km x 100km = 10000
		$ratio = $entry['land_count'] / 10000;
		
		//100% = 50k
		$radius = $entry['percentage']/100 * $ratio * 50000;
		
		#print "<h3>{$entry['hunk_square']}</h3>";
		#print "'land_count'= {$entry['land_count']}<BR>";
		#print "'percentage'= {$entry['percentage']}<BR>";
		#print "ratio=$ratio<BR>";
		#print "radius=$radius<BR>";
		
		
		$x = $entry['avgx'];
		$y = $entry['avgy'];


		list($wgs84_lat,$wgs84_long) = $conv->internal_to_wgs84($x,$y,$ri);

		$point = new kmlPoint($wgs84_lat,$wgs84_long);			

		$placemark = new kmlPlacemark_Circle($entry['hunk_square'],$entry['hunk_square'],$point,$radius);
		$placemark->setItem('description',$entry['percentage'].'%');
		$placemark->useHoverStyle('c1');		
		$circles->addChild($placemark);


		$x = ( intval(($entry['x'] - $origin['origin_x'])/100)*100 ) +  $origin['origin_x'];
		$y = ( intval(($entry['y'] - $origin['origin_y'])/100)*100 ) +  $origin['origin_y'];

		list($south,$west) = $conv->internal_to_wgs84($x,$y,$ri);
		list($north,$east) = $conv->internal_to_wgs84($x+100,$y+100,$ri);

		$Region = $placemark->addChild('Region');
		$Region->setBoundary($north,$south,$east,$west);
		$Region->setLod(10,600);
		$Region->setFadeExtent(50,200);


		$networklink = new kmlNetworkLink(null,$entry['hunk_square']);
		$file = getKmlFilepath($kml->extension,2,$square,$entry['hunk_square']);
		$UrlTag = $networklink->useUrl("http://".$CONF['KML_HOST'].$file);
		$html .= getHtmlLink($file,$entry['hunk_square'],'in Myriad'," (".$names[$entry['hunk_square']].")");
		if (!isset($_GET['debug'])) {
			if (isset($_GET['newonly'])) {
				$db->Execute("insert ignore into kmlcache set `url` = 'myriad.php?gr={$entry['hunk_square']}',filename='$file',`level` = 2,`rendered` = 0");
			} else {
				$db->Execute("replace into kmlcache set `url` = 'myriad.php?gr={$entry['hunk_square']}',filename='$file',`level` = 2,`rendered` = 0");
			}
		}
		$UrlTag->setItem('viewRefreshMode','onRegion');
		
		$links->addChild($networklink);

		$Region2 = clone $Region;
		$Region2->setLod(450,-1);
		$Region2->setFadeExtent(100,0);
		$networklink->addChild($Region2);
	}	
}

$folder->addChild($circles);
$folder->addChild($links);


kmlPageFooter($kml,$square,$gr,'geograph.php',1,$html);



?>
