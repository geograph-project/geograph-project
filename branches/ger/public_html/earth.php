<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Barry Hunter (geo@barryhunter.co.uk)
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
	
if (!isset($_GET['BBOX'])) {
	die("This page provides a feed for Google Earth"); 
}
if (empty($_GET['i']) || !intval($_GET['i'])) {
	$_GET['i'] = 1522;
}

dieUnderHighLoad(2,'earth_unavailable.tpl');

	list($west,$south,$east,$north) = explode(',',trim(str_replace('e ','e+',$_GET['BBOX'])));
	
	$span = min($north - $south,$east - $west);
	
	if (($span > 7) || !$span) {
		$smarty = new GeographPage;
		header("Content-type: application/vnd.google-earth.kml+xml");
		$smarty->display("earth_outsidearea.tpl");
		exit;
	}
	
	$long = (($east - $west)/2) + $west;
	$lat = (($north - $south)/2) + $south;

	$ire = ($lat > 51.2 && $lat < 55.73 && $long > -12.2 && $long < -4.8);
	$uk = ($lat > 49 && $lat < 62 && $long > -9.5 && $long < 2.3);
	
	if (!$ire && !$uk) {
		$smarty = new GeographPage;
		header("Content-type: application/vnd.google-earth.kml+xml");
		$smarty->display("earth_outsidearea.tpl");
		exit;
	}
		
require_once('geograph/feedcreator.class.php');
require_once('geograph/gridimage.class.php');
require_once('geograph/gridsquare.class.php');
require_once('geograph/imagelist.class.php');

$format = 'KML';

$rss = new UniversalFeedCreator(); 
$rss->title = 'Geograph British Isles'; 
$rss->link = "http://{$_SERVER['HTTP_HOST']}";


require_once('geograph/searchcriteria.class.php');
require_once('geograph/searchengine.class.php');

	$pg = $_GET['page'];
	if ($pg == '' or $pg < 1) {$pg = 1;}

$images = new SearchEngine($_GET['i']);
$images->criteria->resultsperpage = min(100,$images->criteria->resultsperpage);

$rss->description = "Images".$images->criteria->searchdesc; 
$rss->syndicationURL = "http://{$_SERVER['HTTP_HOST']}/syndicator.php?format=$format&amp;i=".$_GET['i'].(($pg>1)?"&amp;page=$pg":'');

$images->Execute($pg);

if (count($images->results)) {

	//create some feed items
	foreach ($images->results as $img)
	{
		$item = new FeedItem();
		$item->guid = $img->gridimage_id;
		$item->title = $img->grid_reference." : ".$img->title;
		$item->link = "http://{$_SERVER['HTTP_HOST']}/photo/{$img->gridimage_id}";
		if (!empty($img->dist_string) || !empty($img->imagetakenString)) {
			$item->description = $img->dist_string.((!empty($img->imagetakenString))?' Taken: '.$img->imagetakenString:'')."<br/>".$img->comment; 
			$item->descriptionHtmlSyndicated = true;
		} else {
			$item->description = $img->comment;
		}
		if (!empty($img->imagetaken) && strpos($img->imagetaken,'-00') === FALSE) {
			$item->imageTaken = $img->imagetaken;
		}

		$item->date = strtotime($img->submitted);
		$item->source = "http://{$_SERVER['HTTP_HOST']}/";
		$item->author = $img->realname;

		$item->lat = $img->wgs84_lat;
		$item->long = $img->wgs84_long;
		$details = $img->getThumbnail(120,120,2);

		$item->thumb = $details['server'].$details['url']; 
		$item->thumbTag = $details['html'];
		$item->licence = "&copy; Copyright <i class=\"attribution\">".htmlspecialchars($img->realname)."</i> and licensed for reuse under this <a rel=\"license\" href=\"http://creativecommons.org/licenses/by-sa/2.0/\">Creative Commons Licence</a>";

		$rss->addItem($item);
	}
}

customExpiresHeader(86400,true);
header("Content-type: application/vnd.google-earth.kml+xml");
header("Content-Disposition: attachment; filename=\"geograph.kml\"");

//we store in var so can be by reference
$feed =& $rss->createFeed($format); 
echo $feed;

?>
