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
$kml->atom = true;
$stylefile = "http://{$CONF['KML_HOST']}/kml/style.kmz";

$folder = $kml->addChild('Document');
$folder->setItem('name',"$gr :: Geograph SuperLayer");


$links = new kmlPrimative('Folder');
$links->setItem('name','Next Level...');
	

	
$prefix = $db->GetRow('select * from gridprefix where prefix='.$db->Quote($square->gridsquare).' limit 1');	

$left=$prefix['origin_x']+intval($square->eastings/5)*5;
$right=$left+5-1;
$bottom=$prefix['origin_y']+intval($square->northings/5)*5;
$top=$bottom+5-1;

$rectangle = "'POLYGON(($left $bottom,$right $bottom,$right $top,$left $top,$left $bottom))'";

$sql_where = "CONTAINS(GeomFromText($rectangle),point_xy)";


$photos = $db->GetAll("select 
gridimage_id,grid_reference,title,imagecount,user_id,realname,x,y,view_direction,realname,
wgs84_lat,wgs84_long
from gridimage_kml 
where $sql_where
order by null");


foreach($photos as $id=>$entry) 
{
	if ($entry['imagecount'] == 1) {
		$point = new kmlPoint($entry['wgs84_lat'],$entry['wgs84_long']);			

		$placemark = new kmlPlacemark_Photo($entry['gridimage_id'],$entry['grid_reference'].' :: '.$entry['title'],$point);
		
		$placemark->useHoverStyle();

		$image=new GridImage;
		$image->fastInit($entry);
		
			$placemark->useCredit($image->realname,"http://{$_SERVER['HTTP_HOST']}/photo/".$image->gridimage_id);

			$linkTag = "<a href=\"".$placemark->link."\">";
			$thumb = "http://".$_SERVER['HTTP_HOST'].$image->getThumbnail(120,120,true); 
			$thumbTag = preg_replace('/\/photos\/.*\.jpg/',$thumb,$image->getThumbnail(120,120)); 

			$description = $linkTag.$thumbTag."</a><br/>".GeographLinks($image->comment)." (".$linkTag."view full size</a>)"."<br/><br/> &copy; Copyright <a title=\"view user profile\" href=\"http://{$_SERVER['HTTP_HOST']}/profile/".$image->user_id."\">".$image->realname."</a> and licensed for reuse under this <a rel=\"license\" href=\"http://creativecommons.org/licenses/by-sa/2.0/\">Creative Commons Licence</a><br/><br/>";

			$placemark->setItemCDATA('description',$description);

			//yes that is uppercase S!
			$placemark->setItemCDATA('Snippet',strip_tags($description));

			$placemark->useImageAsIcon($thumb);
			
			
		if (strlen($entry['view_direction']) && $entry['view_direction'] != -1) {
			$placemark->addViewDirection($entry['view_direction']);
		} 
		
			
		
		$Region = $placemark->addChild('Region');
		$Region->setPoint($point,0.01);

		$delta = $entry['gridimage_id']%30;
		$Region->setLod(1200+($delta*$delta),-1);
		$Region->setFadeExtent(100,0);
		$folder->addChild($placemark);
	} else {
		$x = $entry['x'];
		$y = $entry['y'];

		list($south,$west) = $conv->internal_to_wgs84($x,$y,$ri);
		list($north,$east) = $conv->internal_to_wgs84($x+1,$y+1,$ri);

		$networklink = new kmlNetworkLink(null,$entry['grid_reference']);
		$file = getKmlFilepath($kml->extension,6,$square,$entry['grid_reference']);
		$UrlTag = $networklink->useUrl("http://".$CONF['KML_HOST'].$file);
		if (!isset($_GET['debug'])) {
			if (isset($_GET['newonly'])) {
				$db->Execute("insert ignore into kmlcache set `url` = 'square.php?gr={$entry['grid_reference']}',filename='$file',`level` = 6,`rendered` = 0");
			} else {
				$db->Execute("replace into kmlcache set `url` = 'square.php?gr={$entry['grid_reference']}',filename='$file',`level` = 6,`rendered` = 0");
			}
		}

		$UrlTag->setItem('viewRefreshMode','onRegion');
		$links->addChild($networklink);

		$Region2 = $networklink->addChild('Region');
		$Region2->setBoundary($north,$south,$east,$west);
		$Region2->setLod(450,-1);
		$Region2->setFadeExtent(100,0);	
	}
}






$folder->addChild($links);


kmlPageFooter($kml,$square,$gr,'mosaic.php',5);



?>
