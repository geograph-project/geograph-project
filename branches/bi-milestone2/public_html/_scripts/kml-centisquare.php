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


preg_match('/^([A-Z]{1,2})(\d\d)(\d|_)(\d\d)(\d|_)$/',strtoupper($gr),$matches);
if ($matches[3] == '_') {
	$sql_where = " and nateastings = 0";
	$gr2 = $matches[1].$matches[2].$matches[4];
} else {
	$sql_where = " and nateastings != 0";//to stop XX0XX0 matching 4fig GRs
	$sql_where .= " and ((nateastings div 100) mod 10) = ".$matches[3];
	$sql_where .= " and ((natnorthings div 100) mod 10) = ".$matches[5];
	
	$gr2 = $gr;
}
	

$square=new GridSquare;
$grid_ok=$square->setByFullGridRef($gr2);


$html = '';
$kml = new kmlFile();
$kml->atom = true;
$stylefile = "http://{$CONF['KML_HOST']}/kml/style.kmz";

$folder = $kml->addChild('Document');
$folder->setItem('name',"$gr :: Geograph SuperLayer");

	
	$sql_where = "gridsquare_id={$square->gridsquare_id}".$sql_where;
	
	

	$photos = $db->GetAll($sql = "select 
	gridimage_id,title,gi.realname as credit_realname,if(gi.realname!='',gi.realname,user.realname) as realname,user.user_id,comment,nateastings,natnorthings,natgrlen,view_direction
	from gridimage gi
		 inner join user using(user_id)
	where $sql_where
	order by null");


	foreach($photos as $id=>$entry) 
	{
		if ($entry['nateastings']) {
			if ($entry['natgrlen'] == 6) {
				$entry['nateastings']+=50;
				$entry['natnorthings']+=50;
			}
			list($wgs84_lat,$wgs84_long) = $conv->national_to_wgs84($entry['nateastings'],$entry['natnorthings'],$square->reference_index);
		} else {
			list($wgs84_lat,$wgs84_long) = $conv->internal_to_wgs84($square->x,$square->y,$square->reference_index);
		}
		$point = new kmlPoint($wgs84_lat,$wgs84_long);			

		$placemark = new kmlPlacemark_Photo($entry['gridimage_id'],$square->grid_reference.' :: '.$entry['title'],$point);
		$placemark->useHoverStyle();

		$image=new GridImage;
		$image->fastInit($entry);

			$placemark->useCredit($image->realname,"http://{$_SERVER['HTTP_HOST']}/photo/".$image->gridimage_id);
			$html .= getHtmlLinkP($placemark->link,$square->grid_reference.' :: '.$entry['title'].' by '.$image->realname);
			$linkTag = "<a href=\"".$placemark->link."\">";

			$details = $image->getThumbnail(120,120,2);
			$thumb = $details['server'].$details['url']; 
			$thumbTag = $details['html'];
			
			$description = $linkTag.$thumbTag."</a><br/>".GeographLinks($image->comment)." (".$linkTag."view full size</a>)"."<br/><br/> &copy; Copyright <a title=\"view user profile\" href=\"http://".$_SERVER['HTTP_HOST'].$image->profile_link."\">".$image->realname."</a> and licensed for reuse under this <a rel=\"license\" href=\"http://creativecommons.org/licenses/by-sa/2.0/\">Creative Commons Licence</a><br/><br/>";

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
	}



kmlPageFooter($kml,$square,$gr,'centisquare.php',7,$html);


?>
