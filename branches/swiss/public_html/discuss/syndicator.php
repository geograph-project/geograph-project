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
if (!empty($_GET['forum']) && $_GET['forum'] == $CONF['forum_gridsquare'] && empty($_GET['topic']) )
	$valid_formats=array_merge($valid_formats,array('KML','GeoRSS','GPX'));

if (isset($_GET['extension']) && !isset($_GET['format']))
{
	$_GET['format'] = strtoupper($_GET['extension']);
	$_GET['format'] = str_replace('GEO','Geo',$_GET['format']);
	$_GET['format'] = str_replace('PHOTO','Photo',$_GET['format']);
}

if ((!empty($_GET['forum']) && $_GET['forum'] == $CONF['forum_gridsquare']) || isset($_GET['gridref'])) {
	$format="GeoRSS";
} else {
	$format="RSS1.0";
}
if (!empty($_GET['format']) && in_array($_GET['format'], $valid_formats))
{
	$format=$_GET['format'];
}

$opt_expand = (!empty($_GET['expand']))?1:0;
$opt_sortBy = (!empty($_GET['sortBy']) && $_GET['sortBy'] == 1)?1:0;
$opt_first  = (!empty($_GET['first']))?1:0;
$opt_noLimit  = (!empty($_GET['nolimit']))?1:0;
$opt_when = (isset($_GET['when']) && preg_match('/^\d{4}(-\d{2}|)(-\d{2}|)$/',$_GET['when']))?$_GET['when']:'';
$opt_gridref = (isset($_GET['gridref']) && preg_match('/^([A-Z]{1,3})(\d\d)(\d\d)$/',$_GET['gridref']))?$_GET['gridref']:'';


$extension = ($format == 'KML')?'kml':'xml';

if (!empty($_GET['topic']) && is_numeric($_GET['topic'])) {
	$rssfile=$_SERVER['DOCUMENT_ROOT']."/rss/discuss-t{$_GET['topic']}-{$format}-{$opt_expand}-{$opt_noLimit}-{$opt_when}.$extension";
} elseif (!empty($_GET['gridref']) && preg_match('/^([A-Z]{1,3})(\d\d)(\d\d)$/',$_GET['gridref'])) {
	$rssfile=$_SERVER['DOCUMENT_ROOT']."/rss/discuss-f{$_GET['gridref']}-{$format}-{$opt_sortBy}-{$opt_first}-{$opt_when}.$extension";
	$_GET['forum'] = $CONF['forum_gridsquare'];
} elseif (!empty($_GET['forum']) && is_numeric($_GET['forum'])) {
	$rssfile=$_SERVER['DOCUMENT_ROOT']."/rss/discuss-f{$_GET['forum']}-{$format}-{$opt_sortBy}-{$opt_first}-{$opt_when}.$extension";
} else {
	$rssfile=$_SERVER['DOCUMENT_ROOT']."/rss/discuss-{$format}-{$opt_sortBy}-{$opt_first}-{$opt_when}.$extension";
}

$rss = new UniversalFeedCreator();
$rss->useCached($format,$rssfile);

$db=NewADOConnection($GLOBALS['DSN']);

if (!empty($_GET['topic']) && is_numeric($_GET['topic'])) {

	#$rss->link = "http://{$_SERVER['HTTP_HOST']}/discuss/topic{$_GET['topic']}";
	$rss->link = "http://{$_SERVER['HTTP_HOST']}/discuss/?action=vthread&amp;topic={$_GET['topic']}";

		list($title,$forrom) = $db->GetRow("select topic_title,forum_id from `geobb_topics` where `topic_id` = {$_GET['topic']}");

	$rss->title = "Geograph.org.uk Forum :: $title :: Latest Posts";
	$rss->syndicationURL = "http://{$_SERVER['HTTP_HOST']}/discuss/syndicator.php?format=$format&amp;topic=".$_GET['topic'];

	$posts = $db->getOne("SELECT COUNT(*) FROM `geobb_posts` WHERE `topic_id` = {$_GET['topic']}");

	$perpage = ($forrom == $CONF['forum_submittedarticles'] || $forrom == $CONF['forum_gallery'])?10:30;

	$hash = $posts % $perpage;
	$page = floor($posts / $perpage);
	
	$andwhere = $opt_when?" AND post_time > '$opt_when'":'';
	
$sql="SELECT *
FROM `geobb_posts`
WHERE `topic_id` = {$_GET['topic']} $andwhere
ORDER BY `post_time` DESC";
if (!$opt_noLimit) {
	$sql .= " LIMIT 15";
}
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
			$item->description = GeographLinks($recordSet->fields['post_text'],$opt_expand);

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

		if ($format == 'KML' || $format == 'GeoRSS' || $format == 'GPX') {
			require_once('geograph/conversions.class.php');
			require_once('geograph/gridsquare.class.php');
			$conv = new Conversions;
		}
	} else {
		$title = '';
		$synd = '';
		$rss->link = "http://{$_SERVER['HTTP_HOST']}/discuss/";
		$sql_where = "WHERE geobb_topics.forum_id NOT IN ({$CONF['forum_gridsquare']},{$CONF['forum_gallery']})"; //we exclude Grid Ref discussions and Gallery...
	}
	$sql_where .= $opt_when?" AND post_time > '$opt_when'":'';
	
	if ($opt_first) {
		$rss->title = "Geograph.org.uk Forum $title :: New Topics";
		$rss->desciption = 'New Geograph Topics';
		$rss->syndicationURL = "http://{$_SERVER['HTTP_HOST']}/discuss/syndicator.php?format=$format&amp;first=1".$synd;
		$sql_order= 'geobb_topics.topic_id DESC';
		$sql_where .= " GROUP BY geobb_topics.topic_id";
		$sql_join = "geobb_topics.topic_id=geobb_posts.topic_id";
	} elseif ($opt_sortBy) {
		$rss->title = "Geograph.org.uk Forum $title :: Latest Topics";
		$rss->description = 'Latest Geograph Topics';
		$rss->syndicationURL = "http://{$_SERVER['HTTP_HOST']}/discuss/syndicator.php?format=$format&amp;sortBy=1".$synd;
		$sql_order= 'geobb_topics.topic_id DESC';
		$sql_join = "topic_last_post_id=geobb_posts.post_id";
	} else {
		$rss->title = "Geograph.org.uk Forum $title :: Latest Discussions";
		$rss->description = 'Latest Geograph Discussions';
		$rss->syndicationURL = "http://{$_SERVER['HTTP_HOST']}/discuss/syndicator.php?format=$format".$synd;
		$sql_order= '`topic_last_post_id` DESC';
		$sql_join = "topic_last_post_id=geobb_posts.post_id";
	}

	if ($opt_gridref) {
		$rss->description .= " near ".$_REQUEST['gridref'];
		$rss->syndicationURL .= "$amp;gridref=".$_REQUEST['gridref'];
	
		$sql_where = " INNER JOIN gridsquare AS gs ON(topic_title = grid_reference) ".$sql_where;
		
		$square=new GridSquare;
		$grid_ok=$square->setByFullGridRef($_REQUEST['gridref']);
		
		if (!$grid_ok) {
			die("invalid gridsquare");
		}
		
		$d = 20;
		$x = $square->x;
		$y = $square->y;
		
		$left=$x-$d;
		$right=$x+$d-1;
		$top=$y+$d-1;
		$bottom=$y-$d;

		$rectangle = "'POLYGON(($left $bottom,$right $bottom,$right $top,$left $top,$left $bottom))'";

		$sql_where .= " AND CONTAINS(GeomFromText($rectangle),point_xy)";
		if ($d < 50) {
			//shame cant use dist_sqd in the next line!
			$sql_where .= " and ((gs.x - $x) * (gs.x - $x) + (gs.y - $y) * (gs.y - $y)) < ".($d*$d);
		}
		
	} 
	

$sql="SELECT *
FROM `geobb_topics`
INNER JOIN geobb_posts ON ($sql_join)
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

		if ($format == 'KML' || $format == 'GeoRSS' || $format == 'GPX') {
			$gridsquare = new GridSquare;
			$grid_ok=$gridsquare->setGridRef($recordSet->fields['topic_title']);

			if ($grid_ok)
				list($item->lat,$item->long) = $conv->gridsquare_to_wgs84($gridsquare);
		}

		$rss->addItem($item);

		$recordSet->MoveNext();
	}
}


customExpiresHeader(3600,true); //we cache it for an hour anyway! 
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); 
      


$rss->saveFeed($format, $rssfile);

?>
