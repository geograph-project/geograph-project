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

if ( ($_SERVER['REMOTE_ADDR'] != $_SERVER['SERVER_ADDR']) &&
     ($_SERVER['HTTP_X_FORWARDED_FOR']!=$CONF['server_ip']))
{
	init_session();
        $USER->mustHavePerm("admin");
}

$db=NewADOConnection($GLOBALS['DSN']);
if (!$db) die('Database connection failed');  

require_once('geograph/conversions.class.php');
$conv = new Conversions;

$gr = $_GET['gr'];

$square=new GridSquare;
$grid_ok=$square->setByFullGridRef($gr);


$kml = new kmlFile();
$folder = $kml->addChild('Document');
$folder->addHoverStyle('p1',1,1.1,'cam1.png;cam1h.png',"http://".$CONF['KML_HOST'].'/kml/images/');
$folder->addHoverStyle('p2',1,1.1,'cam2.png;cam2h.png',"http://".$CONF['KML_HOST'].'/kml/images/');
$folder->addHoverStyle('p3',1,1.1,'cam3.png;cam3h.png',"http://".$CONF['KML_HOST'].'/kml/images/');
$folder->addHoverStyle('p4',1,1.1,'cam4.png;cam4h.png',"http://".$CONF['KML_HOST'].'/kml/images/');
$folder->addHoverStyle('p10',1,1.1,'cam10.png;cam10h.png',"http://".$CONF['KML_HOST'].'/kml/images/');
$folder->addHoverStyle('p20',1,1.1,'cam20.png;cam20h.png',"http://".$CONF['KML_HOST'].'/kml/images/');
$folder->setItem('name',"$gr :: Geograph SuperLayer");


$links = new kmlPrimative('Folder');
$links->setItem('name','Next Level...');

	
$prefix = $db->GetRow('select * from gridprefix where prefix='.$db->Quote($square->gridsquare).' limit 1');	

$left=$prefix['origin_x']+intval($square->eastings/20)*20;
$right=$left+20-1;
$bottom=$prefix['origin_y']+intval($square->northings/20)*20;
$top=$bottom+20-1;

$rectangle = "'POLYGON(($left $bottom,$right $bottom,$right $top,$left $top,$left $bottom))'";

$sql_where = "CONTAINS(GeomFromText($rectangle),point_xy)";


$photos = $db->GetAll("select 
gridimage_id,grid_reference,title,imagecount,view_direction,
wgs84_lat,wgs84_long
from gridimage_kml 
where $sql_where and tile = 1
order by null");


foreach($photos as $id=>$entry) 
{
	$point = new kmlPoint($entry['wgs84_lat'],$entry['wgs84_long']);			

	if ($entry['imagecount']==1) {
		$placemark = new kmlPlacemark(null,$entry['grid_reference'].' :: '.$entry['title'],$point);
		$placemark->setItem('description',"http://{$_SERVER['HTTP_HOST']}/photo/{$entry['gridimage_id']}");
		$placemark->useHoverStyle('p1');

		if ($entry['view_direction'] != -1) {
			$Style = $placemark->addChild('Style');
			$IconStyle = $Style->addChild('IconStyle');
			$IconStyle->setItem('heading',$entry['view_direction']);
		}
	} else {
		$placemark = new kmlPlacemark(null,$entry['grid_reference'].' :: '.$entry['imagecount'].' images e.g. '.$entry['title'],$point);
		$placemark->setItem('description',"http://{$_SERVER['HTTP_HOST']}/gridref/{$entry['grid_reference']}");
		$c = ($entry['imagecount']>20)?20:(($entry['imagecount']>4)?10:$entry['imagecount']);
		$placemark->useHoverStyle('p'.$c);
	}
	
	$Region = $placemark->addChild('Region');
	$Region->setPoint($point,0.01);
	
	$delta = $entry['gridimage_id']%30;
	$Region->setLod(50+$delta,1300+($delta*$delta));
	$Region->setFadeExtent(10,100);
	$folder->addChild($placemark);
}




$letterlength = 3 - $prefix['reference_index']; #should this be auto-realised by selecting a item from gridprefix?

$sql_column = "concat(substring(grid_reference,1,$letterlength+1),substring(grid_reference,$letterlength+3,1))";

$most = $db->GetAll("select 
grid_reference,x,y,
$sql_column as hunk_square,
sum(has_geographs) as geograph_count,
sum(percent_land >0) as land_count,
(sum(has_geographs) * 100 / sum(percent_land >0)) as percentage
from gridsquare 
where $sql_where
group by hunk_square 
having geograph_count > 0 
order by hunk_square");


foreach($most as $id=>$entry) 
{
	$x = ( intval(($entry['x'] - $prefix['origin_x'])/10)*10 ) +  $prefix['origin_x'];
	$y = ( intval(($entry['y'] - $prefix['origin_y'])/10)*10 ) +  $prefix['origin_y'];

	list($south,$west) = $conv->internal_to_wgs84($x,$y,$ri);
	list($north,$east) = $conv->internal_to_wgs84($x+10,$y+10,$ri);


	$networklink = new kmlNetworkLink(null,$entry['hunk_square']);
	$file = getKmlFilepath($kml->extension,4,$square,$entry['hunk_square']);
	$UrlTag = $networklink->useUrl("http://".$CONF['KML_HOST'].$file);
	if (!isset($_GET['debug'])) {
		if (isset($_GET['newonly'])) {
			$db->Execute("insert ignore into kmlcache set `url` = 'hectad.php?gr={$entry['hunk_square']}',filename='$file',`level` = 4,`rendered` = 0");
		} else {
			$db->Execute("replace into kmlcache set `url` = 'hectad.php?gr={$entry['hunk_square']}',filename='$file',`level` = 4,`rendered` = 0");
		}
	}

	$UrlTag->setItem('viewRefreshMode','onRegion');
	$links->addChild($networklink);

	$Region2 = $networklink->addChild('Region');
	$Region2->setBoundary($north,$south,$east,$west);
	$Region2->setLod(450,1500);
	$Region2->setFadeExtent(100,100);
}

$folder->addChild($links);

kmlPageFooter($kml,$square,$gr,'tile.php',3);

?>
