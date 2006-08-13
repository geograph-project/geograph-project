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

$rss->description = "Images".$images->criteria->searchdesc; 
$rss->syndicationURL = "http://{$_SERVER['HTTP_HOST']}/syndicator.php?format=$format&amp;i=".$_GET['i'].(($pg>1)?"&amp;page=$pg":'');

$images->Execute($pg);

//create some feed items
foreach ($images->results as $img)
{
	$item = new FeedItem();
	$item->title = $img->grid_reference." : ".$img->title;
	$item->link = "http://{$_SERVER['HTTP_HOST']}/photo/{$img->gridimage_id}";
	if ($img->dist_string || $img->imagetakenString) {
		$item->description = $img->dist_string.($img->imagetakenString?' Taken: '.$img->imagetakenString:'')."<br/>".$img->comment; 
		$item->descriptionHtmlSyndicated = true;
	} else {
		$item->description = $img->comment;
	}
	$item->date = strtotime($img->submitted);
	$item->source = "http://{$_SERVER['HTTP_HOST']}/";
	$item->author = $img->realname;
	     
	$item->lat = $img->wgs84_lat;
	$item->long = $img->wgs84_long;
	$item->thumb = "http://".$_SERVER['HTTP_HOST'].$img->getThumbnail(120,120,true);
	
	$rss->addItem($item);
}

header("Content-type: application/vnd.google-earth.kml+xml");
header("Content-Disposition: attachment; filename=\"geograph.kml\"");

//we store in var so can be by reference
$feed =& $rss->createFeed($format); 
echo $feed;

?>