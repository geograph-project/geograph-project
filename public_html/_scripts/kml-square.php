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

$db=NewADOConnection($GLOBALS['DSN']);
if (!$db) die('Database connection failed');  

require_once('geograph/conversions.class.php');
$conv = new Conversions;

$gr = $_GET['gr'];

$square=new GridSquare;
$grid_ok=$square->setByFullGridRef($gr);


$html = '';
$kml = new kmlFile();
$kml->atom = true;
$stylefile = "http://{$CONF['KML_HOST']}/kml/style.kmz";

$folder = $kml->addChild('Document');
$folder->setItem('name',"$gr :: Geograph SuperLayer");

if ($square->imagecount > 20) {
	$sql_where = "gridsquare_id={$square->gridsquare_id}";
	$ri = $square->reference_index;
	

	$links = new kmlPrimative('Folder');
	$links->setItem('name','Next Level...');


	$most = $db->GetAll("select 
	gridimage_id,nateastings,natnorthings,count(*) as c
	from gridimage 
	where $sql_where
	group by nateastings div 100,natnorthings div 100 
	order by null");
			
	foreach($most as $id=>$entry) 
	{
		if ($entry['nateastings']) {
			$e = ($e2 = intval($entry['nateastings']/100))*100;
			$n = ($n2 = intval($entry['natnorthings']/100))*100;
			
			$e2 = $e2 % 10;
			$n2 = $n2 % 10;
			
			list($wgs84_lat,$wgs84_long) = $conv->national_to_wgs84($e+50,$n+50,$ri);
	
			$entry['hunk_square'] = $square->gridsquare.$square->eastings.$e2.$square->northings.$n2;
			$name = "{$entry['hunk_square']} :: {$entry['c']} images";
		} else {
			//should be only one of these!
			
			$e = $square->getNatEastings();
			$n = $square->getNatNorthings();
			
			list($wgs84_lat,$wgs84_long) = $conv->national_to_wgs84($e,$n,$ri);
			
			$entry['hunk_square'] = $square->gridsquare.$square->eastings.'_'.$square->northings.'_';
			$name = "{$square->grid_reference} :: {$entry['c']} images";
		}
		
		
		$c = ($entry['c']>20)?20:(($entry['c']>4)?10:$entry['c']);
		
		$point = new kmlPoint($wgs84_lat,$wgs84_long);			

		$placemark = new kmlPlacemark($entry['hunk_square'],$name,$point);
		
		$placemark->useHoverStyle('p'.$c);
		$folder->addChild($placemark);
		
		
		
		list($south,$west) = $conv->national_to_wgs84($e,$n,$ri);
		list($north,$east) = $conv->national_to_wgs84($e+100,$n+100,$ri);
		
		$delta = $entry['gridimage_id']%10;
		
		$Region = $placemark->addChild('Region');
		$Region->setBoundary($north,$south,$east,$west);
		$Region->setLod(10+$delta,600);
		$Region->setFadeExtent(50,200);
		

		$networklink = new kmlNetworkLink(null,$name);
		$file = getKmlFilepath($kml->extension,7,$square,$entry['hunk_square']);
		$UrlTag = $networklink->useUrl("http://".$CONF['KML_HOST'].$file);
		$html .= getHtmlLink($file,$entry['hunk_square']);
		if (!isset($_GET['debug'])) {
			if (isset($_GET['newonly'])) {
				$db->Execute("insert ignore into kmlcache set `url` = 'centisquare.php?gr={$entry['hunk_square']}',filename='$file',`level` = 7,`rendered` = 0");
			} else {
				$db->Execute("replace into kmlcache set `url` = 'centisquare.php?gr={$entry['hunk_square']}',filename='$file',`level` = 7,`rendered` = 0");
			}
		}

		$UrlTag->setItem('viewRefreshMode','onRegion');
		$links->addChild($networklink);

		$Region2 = clone $Region;
		$Region2->setLod(500,-1);
		$Region2->setFadeExtent(100,0);
		$networklink->addChild($Region2);
	}

	$folder->addChild($links);

} else {
	$point_xy = "'POINT({$square->x} {$square->y})'";

	$sql_where = "x={$square->x} and y={$square->y}";


	$photos = $db->GetAll($sql = "select 
	gridimage_id,grid_reference,title,title2,x,y,credit_realname,realname,user_id,comment,
	wgs84_lat,wgs84_long
	from gridimage_search 
	where $sql_where
	order by null");

		$sql_where = "gridsquare_id={$square->gridsquare_id}";

		$directions = $db->GetAssoc("select 
		gridimage_id,view_direction
		from gridimage 
		where $sql_where and view_direction > -1
		order by null");


	foreach($photos as $id=>$entry) 
	{
		$point = new kmlPoint($entry['wgs84_lat'],$entry['wgs84_long']);
		if (empty($entry['title2']))
			$title = $entry['title'];
		elseif (empty($entry['title']))
			$title = $entry['title2'];
		else
			$title = $entry['title'] . ' (' . $entry['title2'] . ')';

		$placemark = new kmlPlacemark_Photo($entry['gridimage_id'],$entry['grid_reference'].' :: '.$title,$point);
		$placemark->useHoverStyle();

		$image=new GridImage;
		$image->fastInit($entry);

			$placemark->useCredit($image->realname,"http://{$_SERVER['HTTP_HOST']}/photo/".$image->gridimage_id);
			$html .= getHtmlLinkP($placemark->link,$entry['grid_reference'].' :: '.$title.' by '.$image->realname);
			$linkTag = "<a href=\"".$placemark->link."\">";

			$details = $image->getThumbnail(120,120,2);
			$thumb = $details['server'].$details['url']; 
			$thumbTag = $details['html'];

			$description = $linkTag.$thumbTag."</a><br/>".GeographLinks($image->comment)." (".$linkTag."view full size</a>)"."<br/><br/> &copy; Copyright <a title=\"view user profile\" href=\"http://".$_SERVER['HTTP_HOST'].$image->profile_link."\">".$image->realname."</a> and licensed for reuse under this <a rel=\"license\" href=\"http://creativecommons.org/licenses/by-sa/2.0/\">Creative Commons Licence</a><br/><br/>";

			$placemark->setItemCDATA('description',$description);

			//yes that is uppercase S!
			$placemark->setItemCDATA('Snippet',strip_tags($description));

			$placemark->useImageAsIcon($thumb);

			if (isset($directions[$image->gridimage_id])) {
				$placemark->addViewDirection($directions[$image->gridimage_id]);
			} 

		$Region = $placemark->addChild('Region');
		$Region->setPoint($point,0.01);

		$delta = $entry['gridimage_id']%30;
		$Region->setLod(1200+($delta*$delta),-1);
		$Region->setFadeExtent(100,0);
		
		$folder->addChild($placemark);
	}

}


kmlPageFooter($kml,$square,$gr,'square.php',6,$html);



?>
