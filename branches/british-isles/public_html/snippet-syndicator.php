<?php
/**
 * $Project: GeoGraph $
 * $Id$
 *
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2009 Barry Hunter (geo@barryhunter.co.uk)
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
	$_GET['format'] = str_replace('_','.',$_GET['format']);
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
	$crit = "and wgs84_lat != 0 and grid_reference != ''";
} elseif ($format == 'GPX') {
	$extension = 'gpx';
	$crit = "and wgs84_lat != 0 and grid_reference != ''";
} else {
	$extension = 'xml';
	$crit = '';
}

$format_extension = strtolower(str_replace('.','_',$format));


$rssfile=$_SERVER['DOCUMENT_ROOT']."/rss/{$CONF['template']}/snippet-{$format}.$extension";


$rss = new UniversalFeedCreator();
if (empty($_GET['refresh'])) 
	$rss->useCached($format,$rssfile);

$rss->title = 'Shared Descriptions'; 
$rss->link = "http://{$_SERVER['HTTP_HOST']}/snippets.php";
 
	
$rss->description = "Recent Shared descriptions"; 

	$rss->syndicationURL = "http://{$_SERVER['HTTP_HOST']}/snippet-syndicator.php?format=$format";



if ($format == 'KML' || $format == 'GeoRSS' || $format == 'GPX') {
	require_once('geograph/conversions.class.php');
	require_once('geograph/gridsquare.class.php');
	$conv = new Conversions;

	$rss->geo = true;	
} 

$db=NewADOConnection($GLOBALS['DSN']);
	

$sql="select snippet_id,title,comment,created,wgs84_lat,wgs84_long,realname
from snippet
	left join user using (user_id)
where enabled = 1 $crit
order by snippet_id desc
limit 96";

$recordSet = &$db->Execute($sql);
while (!$recordSet->EOF)
{
	$item = new FeedItem();
	
	$item->title = $recordSet->fields['title'];

	//htmlspecialchars is called on link so dont use &amp;
	$item->link = "http://{$_SERVER['HTTP_HOST']}/snippet.php?id={$recordSet->fields['snippet_id']}";
	
	$item->guid = $item->link;

	
	$description = $recordSet->fields['comment'];
	if (strlen($description) > 160)
		$description = substr($description,0,157)."...";
		
	$item->description = $description;
	$item->date = strtotime($recordSet->fields['created']);
	$item->author = $recordSet->fields['realname'];
	
	if ($recordSet->fields['wgs84_lat'] != 0 || $recordSet->fields['wgs84_long'] != 0) {
		list($item->lat,$item->long) = array($recordSet->fields['wgs84_lat'],$recordSet->fields['wgs84_long']);
	}
		
	$rss->addItem($item);
	
	$recordSet->MoveNext();
}


$rss->saveFeed($format, $rssfile);

?>