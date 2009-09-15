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
require_once('geograph/gridimage.class.php');
require_once('geograph/gridsquare.class.php');
require_once('geograph/map.class.php');

if (!isLocalIPAddress())
{
	init_session();
        $USER->mustHavePerm("admin");
}

$db = GeographDatabaseConnection(false);

require_once('geograph/conversions.class.php');
$conv = new Conversions;

$gr = $_GET['gr'];

$html = '';
$kml = new kmlFile();
$stylefile = "http://{$CONF['KML_HOST']}/kml/style.kmz";

$folder = $kml->addChild('Document');
$folder->setItem('name',"$gr :: Geograph SuperLayer");

$links = new kmlPrimative('Folder');
$links->setItem('name','Next Level...');
	
	
$prefix = $db->GetRow('select * from gridprefix where prefix='.$db->Quote($gr).' limit 1');	

$left=$prefix['origin_x'];
$right=$prefix['origin_x']+$prefix['width']-1;
$top=$prefix['origin_y']+$prefix['height']-1;
$bottom=$prefix['origin_y'];

$rectangle = "'POLYGON(($left $bottom,$right $bottom,$right $top,$left $top,$left $bottom))'";

$sql_where = "CONTAINS(GeomFromText($rectangle),point_xy)";

$sql_where .= ' and reference_index = '.$prefix['reference_index'].' ';



$letterlength = 3 - $prefix['reference_index']; #should this be auto-realised by selecting a item from gridprefix?

$sql_column = "concat(substring(grid_reference,1,$letterlength),substring(grid_reference,$letterlength+1,1) div 2 * 2,substring(grid_reference,$letterlength+3,1) div 2 * 2)";

$most = $db->GetAll("select 
grid_reference,x,y,avg(x) as avgx,avg(y) as avgy,
$sql_column as hunk_square,
sum(has_geographs) as geograph_count,
sum(imagecount) as images,
sum(percent_land >0) as land_count,
(sum(has_geographs) * 100 / sum(percent_land >0)) as percentage
from gridsquare 
where $sql_where
group by hunk_square 
having geograph_count > 0 
order by hunk_square");


foreach($most as $id=>$entry) 
{
	#if ($entry['land_count'] < 10) {
	#	//100% = 1k
	#	$radius = $entry['percentage'] * 10;
	#} else {
	#	//100% = 10k
	#	$radius = $entry['percentage'] * 100;
	#}
	
	// 	20km x 20km = 400
	$ratio = $entry['land_count'] / 400;

	//100% = 10k
	$radius = $entry['percentage']/100 * $ratio * 10000;
	
	
	$x = $entry['avgx'];
	$y = $entry['avgy'];


	list($wgs84_lat,$wgs84_long) = $conv->internal_to_wgs84($x,$y,$ri);

	$point = new kmlPoint($wgs84_lat,$wgs84_long);			

	$placemark = new kmlPlacemark_Circle(null,$entry['hunk_square'],$point,$radius);
	$placemark->setItem('description',$entry['percentage'].'%');
	$placemark->useHoverStyle('c2');
	$folder->addChild($placemark);


	$x = ( intval(($entry['x'] - $prefix['origin_x'])/20)*20 ) +  $prefix['origin_x'];
	$y = ( intval(($entry['y'] - $prefix['origin_y'])/20)*20 ) +  $prefix['origin_y'];

	list($south,$west) = $conv->internal_to_wgs84($x,$y,$ri);
	list($north,$east) = $conv->internal_to_wgs84($x+20,$y+20,$ri);

	$Region = $placemark->addChild('Region');
	$Region->setBoundary($north,$south,$east,$west);
	$Region->setLod(100,650);
	$Region->setFadeExtent(50,150);



	$networklink = new kmlNetworkLink(null,$entry['hunk_square']);
	$file = getKmlFilepath($kml->extension,3,$square,$entry['hunk_square']);
	$UrlTag = $networklink->useUrl("http://".$CONF['KML_HOST'].$file);
	$html .= getHtmlLink($file,$entry['hunk_square'],'in tile'," (at least {$entry['images']} images)");
	if (!isset($_GET['debug'])) {
		if (isset($_GET['newonly'])) {
			$db->Execute("insert ignore into kmlcache set `url` = 'tile.php?gr={$entry['hunk_square']}',filename='$file',`level` = 3,`rendered` = 0");
		} else {
			$db->Execute("replace into kmlcache set `url` = 'tile.php?gr={$entry['hunk_square']}',filename='$file',`level` = 3,`rendered` = 0");
		}
	}
	
	$UrlTag->setItem('viewRefreshMode','onRegion');
	$links->addChild($networklink);

	$Region2 = clone $Region;
	$Region2->setLod(450,-1);
	$Region2->setFadeExtent(10,0);
	$networklink->addChild($Region2);
}	

$folder->addChild($links);


kmlPageFooter($kml,$square,$gr,'myriad.php',2,$html);


?>
