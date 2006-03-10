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
require_once('geograph/feedcreator.class.php');

/*
We'll let you see RSS summaries without logging in

if ( (!empty($_GET['topic']) && is_numeric($_GET['topic'])) || (!empty($_GET['forum']) && is_numeric($_GET['forum']))) {
	init_session();
	$USER->basicAuthLogin();
	//if got past must be logged in
}
*/

$valid_formats=array('RSS0.91','RSS1.0','RSS2.0','MBOX','OPML','ATOM','ATOM0.3','HTML','JS','PHP');
if (!empty($_GET['forum']) && $_GET['forum'] == 5 && empty($_GET['topic']) )
	$valid_formats=array_merge($valid_formats,array('KML','GeoRSS'));


$format="RSS1.0";
if (!empty($_GET['format']) && in_array($_GET['format'], $valid_formats))
	$format=$_GET['format'];


$extension = ($format == 'KML')?'kml':'xml';

if (!empty($_GET['topic']) && is_numeric($_GET['topic'])) {
	$rssfile=$_SERVER['DOCUMENT_ROOT']."/rss/discuss-t{$_GET['topic']}-{$format}.$extension";
} elseif (!empty($_GET['forum']) && is_numeric($_GET['forum'])) {
	$rssfile=$_SERVER['DOCUMENT_ROOT']."/rss/discuss-f{$_GET['forum']}-{$format}-".(($_GET['sortBy'] == 1)?1:0).".$extension";
} else {
	$rssfile=$_SERVER['DOCUMENT_ROOT']."/rss/discuss-{$format}-".((!empty($_GET['sortBy']) && $_GET['sortBy'] == 1)?1:0).".$extension";
}

$rss = new UniversalFeedCreator();
$rss->useCached($format,$rssfile);

$db=NewADOConnection($GLOBALS['DSN']);

if (!empty($_GET['topic']) && is_numeric($_GET['topic'])) {

	#$rss->link = "http://{$_SERVER['HTTP_HOST']}/discuss/topic{$_GET['topic']}";
	$rss->link = "http://{$_SERVER['HTTP_HOST']}/discuss/?action=vthread&amp;topic={$_GET['topic']}";

		list($title,$forrom) = $db->GetOne("select topic_title,forum_id from `geobb_topics` where `topic_id` = {$_GET['topic']}");

	$rss->title = "Geograph.org.uk Forum :: $title :: Latest Posts";
	$rss->syndicationURL = "http://{$_SERVER['HTTP_HOST']}/discuss/syndicator.php?format=$format&amp;topic=".$_GET['topic'];

	$posts = $db->getOne("SELECT COUNT(*) FROM `geobb_posts` WHERE `topic_id` = {$_GET['topic']}");

	$perpage = ($forrom == 6)?10:30;

	$hash = $posts % $perpage;
	$page = floor($posts / $perpage);

$sql="SELECT *
FROM `geobb_posts`
WHERE `topic_id` = {$_GET['topic']}
ORDER BY `post_time` DESC
LIMIT 15";
	$recordSet = &$db->Execute($sql);
	while (!$recordSet->EOF)
	{
		$item = new FeedItem();

		//htmlspecialchars is called on link so dont use &amp;
		//we cant get the #12 as we dont know how many posts in total (or which page is on)
		$item->link = "http://{$_SERVER['HTTP_HOST']}/discuss/?action=vthread&topic={$_GET['topic']}&page=$page#$hash";

		//create a nice short snippet for title
		$title = preg_replace('/^<i>[^<]+<\/i>([\n\r]*<br>)?([\n\r]*<br>)?([\n\r]*<br>)?/','',$recordSet->fields['post_text']);
		$title = preg_replace('/<br\\/?>.+$/s','',$title);
		$title = preg_replace('/<[^>]+>/','',$title);
		$title = preg_replace('/<[^>]+>/','',$title);
		if (strlen($title) > 75)
			$title = substr($title,0,72)."...";
		$title = strip_tags(GeographLinks($title));

		$item->title = $title;

		//send description if more verbose than the snippet title
		if ($title != $recordSet->fields['post_text'])
			$item->description = GeographLinks($recordSet->fields['post_text']);

		$item->date = strtotime($recordSet->fields['post_time']);
		//$item->source = "http://{$_SERVER['HTTP_HOST']}/discuss/?action=vthread&amp;topic={$_GET['topic']}";
		$item->author = $recordSet->fields['poster_name'];

		$rss->addItem($item);

		$recordSet->MoveNext();

		if ($hash == 1) {
			$hash = $perpage;
			$page--;
		} else {
			$hash--;
		}
	}
} else {
	if (!empty($_GET['forum']) && is_numeric($_GET['forum'])) {

		//no need to login for RSS
		//$USER->basicAuthLogin();

		$rss->link = "http://{$_SERVER['HTTP_HOST']}/discuss/?action=vtopic&amp;forum={$_GET['forum']}";
		$synd = "&amp;forum={$_GET['forum']}";
		$title = ' :: '.$db->GetOne("select forum_name from `geobb_forums` where `forum_id` = {$_GET['forum']}");

		$sql_where = "WHERE geobb_topics.forum_id={$_GET['forum']}";

		if ($format == 'KML' || $format == 'GeoRSS') {
			require_once('geograph/conversions.class.php');
			require_once('geograph/gridsquare.class.php');
			$conv = new Conversions;
		}
	} else {
		$title = '';
		$synd = '';
		$rss->link = "http://{$_SERVER['HTTP_HOST']}/discuss/";
		$sql_where = 'WHERE geobb_topics.forum_id!=5'; //we exclude Grid Ref discussions...
	}

	if (!empty($_GET['sortBy']) && $_GET['sortBy'] == 1) {
		$rss->title = "Geograph.org.uk Forum $title :: Latest Topics";
		$rss->description = 'Latest Geograph Topics';
		$rss->syndicationURL = "http://{$_SERVER['HTTP_HOST']}/discuss/syndicator.php?format=$format&amp;sortBy=1".$synd;
		$sql_order= 'geobb_topics.topic_id DESC';
	} else {
		$rss->title = "Geograph.org.uk Forum $title :: Latest Discussions";
		$rss->description = 'Latest Geograph Discussions';
		$rss->syndicationURL = "http://{$_SERVER['HTTP_HOST']}/discuss/syndicator.php?format=$format".$synd;
		$sql_order= '`topic_last_post_id` DESC';
	}

$sql="SELECT *
FROM `geobb_topics`
INNER JOIN geobb_posts ON(topic_last_post_id=geobb_posts.post_id)
$sql_where
ORDER BY $sql_order
LIMIT 30";

	$recordSet = &$db->Execute($sql);
	while (!$recordSet->EOF)
	{
		$item = new FeedItem();
		$item->title = $recordSet->fields['topic_title'];
		
		//htmlspecialchars is called on link so dont use &amp;
		$item->link = "http://{$_SERVER['HTTP_HOST']}/discuss/?action=vthread&topic={$recordSet->fields['topic_id']}";
		$description = preg_replace('/^<i>[^<]+<\/i>([\n\r]*<br>)?([\n\r]*<br>)?([\n\r]*<br>)?/','',$recordSet->fields['post_text']);
		if (strlen($description) > 160)
			$description = substr($description,0,157)."...";
		$item->description = GeographLinks($description);
		$item->date = strtotime($recordSet->fields['post_time']);
		//$item->source = "http://{$_SERVER['HTTP_HOST']}/discuss/";
		$item->author = $recordSet->fields['poster_name'];

		if ($format == 'KML' || $format == 'GeoRSS') {
			$gridsquare = new GridSquare;
			$grid_ok=$gridsquare->setGridRef($recordSet->fields['topic_title']);

			if ($grid_ok)
				list($item->lat,$item->long) = $conv->gridsquare_to_wgs84($gridsquare);
		}

		$rss->addItem($item);

		$recordSet->MoveNext();
	}
}


header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
                                                    // always modified
header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");



$rss->saveFeed($format, $rssfile);

?>