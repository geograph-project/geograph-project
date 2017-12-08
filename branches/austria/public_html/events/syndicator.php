<?php
/**
 * $Project: GeoGraph $
 * $Id: syndicator.php 3052 2007-02-08 13:57:25Z barry $
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
require_once('geograph/feedcreator.class.php');

$valid_formats=array('RSS0.91','RSS1.0','RSS2.0','MBOX','OPML','ATOM','ATOM0.3','HTML','JS','PHP');

	$valid_formats=array_merge($valid_formats,array('KML','GeoRSS','GPX'));

if (isset($_GET['extension']) && !isset($_GET['format']))
{
	$_GET['format'] = strtoupper($_GET['extension']);
	$_GET['format'] = str_replace('GEO','Geo',$_GET['format']);
	$_GET['format'] = str_replace('PHOTO','Photo',$_GET['format']);
}

$format="GeoRSS";
if (!empty($_GET['format']) && in_array($_GET['format'], $valid_formats))
{
	$format=$_GET['format'];
}

if ($format == 'KML') {
	if (!isset($_GET['simple']))
		$_GET['simple'] = 1; //default to on
	$extension = (empty($_GET['simple']))?'kml':'simple.kml';
} elseif ($format == 'GPX') {
	$extension = 'gpx';
} else {
	$extension = 'xml';
}

$rssfile=$_SERVER['DOCUMENT_ROOT']."/rss/events-{$format}-".empty($_GET['admin']).".$extension";


$rss = new UniversalFeedCreator();
if (empty($_GET['refresh'])) 
	$rss->useCached($format,$rssfile);

$rss->title = 'Geograph Events'; 
$rss->link = "http://{$_SERVER['HTTP_HOST']}/article/";
 
	
$rss->description = "Upcoming Geograph British Isles Events"; 

if (empty($_GET['admin'])) {
	$isadmin= 0;
	$rss->syndicationURL = "http://{$_SERVER['HTTP_HOST']}/events/syndicator.php?format=$format";

} else {
	$isadmin= 1;
	$rss->title = 'Geograph Pending Articles'; 
	$rss->syndicationURL = "http://{$_SERVER['HTTP_HOST']}/article/syndicator.php?format=$format&amp;admin=1";
}

if ($format == 'KML' || $format == 'GeoRSS' || $format == 'GPX') {
	require_once('geograph/conversions.class.php');
	require_once('geograph/gridsquare.class.php');
	$conv = new Conversions;

	$rss->geo = true;
}

$db=GeographDatabaseConnection();
	

$sql="select geoevent.*,
	realname,
	(event_time > now()) as future,
	grid_reference,x,y
from geoevent 
	inner join user using (user_id)
	inner join gridsquare using (gridsquare_id)
where (approved = 1 and event_time > now())
	or ($isadmin and approved != -1)
order by event_time desc";

$recordSet = $db->Execute($sql);
while (!$recordSet->EOF)
{
	$item = new FeedItem();
	
	$item->title = date('d/m/Y: ',strtotime($recordSet->fields['created'])).$recordSet->fields['title'];

	//htmlspecialchars is called on link so dont use &amp;
	$item->link = "http://{$_SERVER['HTTP_HOST']}/events/event.php?id={$recordSet->fields['geoevent_id']}";
	
	$item->guid = $item->link;

	
	$description = $recordSet->fields['description'];
	if (strlen($description) > 160)
		$description = substr($description,0,157)."...";
		
	$description .= "<br/><br/><a href=\"{$item->link}\">More details of this event</a>";	
	$item->description = $description;
	$item->date = strtotime($recordSet->fields['created']);
	$item->author = $recordSet->fields['realname'];
	
	if (($format == 'KML' || $format == 'GeoRSS' || $format == 'GPX') && $recordSet->fields['gridsquare_id']) {
		$gridsquare = new GridSquare;
		$grid_ok=$gridsquare->loadFromId($recordSet->fields['gridsquare_id']);

		if ($grid_ok)
			list($item->lat,$item->long) = $conv->internal_to_wgs84($recordSet->fields['x'],$recordSet->fields['y']);
	
		$rss->addItem($item);
	} elseif ($format != 'KML') {
		$rss->addItem($item);
	}

	$recordSet->MoveNext();
}



header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
                                                    // always modified
header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");



$rss->saveFeed($format, $rssfile);

?>
