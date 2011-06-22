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


if (!empty($_GET['q']) && empty($_GET['auth'])) {
	header("HTTP/1.1 503 Service Unavailable");
	print "Feature Disabled - <a href=\"http://www.geograph.org.uk/contact.php\">please contact us</a>";
	exit;
}

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

$extension = ($format == 'KML')?'kml':'xml';

$format_extension = strtolower(str_replace('.','_',$format));

$rssfile=$_SERVER['DOCUMENT_ROOT']."/rss/{$CONF['template']}/blog-{$format}-".md5(serialize($_GET)).".$extension";


$rss = new UniversalFeedCreator();
if (empty($_GET['refresh'])) 
	$rss->useCached($format,$rssfile);

$rss->title = 'Geograph Blog'; 
$rss->link = "http://{$_SERVER['HTTP_HOST']}/blog/";
 
	
$rss->description = "Recently updated Blog Entries on Geograph British Isles"; 

if ($format == 'KML') {
	$rss->description .= ". <a href=\"{$rss->link}\">View Blog Homepage</a> or <a href=\"http://{$_SERVER['HTTP_HOST']}/\">Geograph Homepage</a>";
}


$rss->syndicationURL = "http://{$_SERVER['HTTP_HOST']}/blog/feed.$format_extension";


if ($format == 'KML' || $format == 'GeoRSS' || $format == 'GPX') {
	require_once('geograph/conversions.class.php');
	require_once('geograph/gridsquare.class.php');
	$conv = new Conversions;

	$rss->geo = true;
}

$db = GeographDatabaseConnection(true);
	
$limit = (isset($_GET['nolimit']))?1000:50;


// --------------
		
	if ($format == 'KML') {
		$where = "gridsquare_id > 0";
	} else {
		$where = 1;
	}
	
	
$sql="select blog_id,blog.user_id,title,content,blog.updated,blog.created,realname,gridsquare_id
	from blog
		left join user using (user_id)
	where approved = 1 and published < now() and $where
	order by updated desc
	limit $limit";

$recordSet = &$db->Execute($sql);
while (!$recordSet->EOF)
{
	$item = new FeedItem();
	
	$item->title = $recordSet->fields['title'];

	//htmlspecialchars is called on link so dont use &amp;
	$item->link = "http://{$_SERVER['HTTP_HOST']}/blog/entry.php?id=".$recordSet->fields['blog_id'];
	
	$description = $recordSet->fields['content'];
#	if (strlen($description) > 160)
#		$description = substr($description,0,157)."...";
	$item->description = $description;
	$item->date = strtotime($recordSet->fields['created']);
	$item->author = $recordSet->fields['realname'];
	if ($format == 'KML') {
		$item->description .= " by ".$recordSet->fields['realname'];
	}	
	if (!empty($rss->geo) && $recordSet->fields['gridsquare_id']) {
		$gridsquare = new GridSquare;
		$grid_ok=$gridsquare->loadFromId($recordSet->fields['gridsquare_id']);

		if ($grid_ok)
			list($item->lat,$item->long) = $conv->gridsquare_to_wgs84($gridsquare);
	
	}

	$rss->addItem($item);

	$recordSet->MoveNext();
}



header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
                                                    // always modified
header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");



$rss->saveFeed($format, $rssfile);


