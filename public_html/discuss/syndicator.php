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

if ( (isset($_GET['topic']) && is_numeric($_GET['topic'])) || (isset($_GET['forum']) && is_numeric($_GET['forum']))) {
	init_session();
	$USER->basicAuthLogin();
	//if got past must be logged in	
}
	
$valid_formats=array('RSS0.91','RSS1.0','RSS2.0','MBOX','OPML','ATOM','ATOM0.3','HTML','JS');

$format="RSS1.0";
if (isset($_GET['format']) && in_array($_GET['format'], $valid_formats))
{
	$format=$_GET['format'];
}

if (isset($_GET['topic']) && is_numeric($_GET['topic'])) {
	$rssfile=$_SERVER['DOCUMENT_ROOT']."/rss/discuss-t{$_GET['topic']}-{$format}.xml";
} elseif (isset($_GET['forum']) && is_numeric($_GET['forum'])) {
	$rssfile=$_SERVER['DOCUMENT_ROOT']."/rss/discuss-f{$_GET['forum']}-{$format}-".(($_GET['sortBy'] == 1)?1:0).".xml";	
} else {
	$rssfile=$_SERVER['DOCUMENT_ROOT']."/rss/discuss-{$format}-".(($_GET['sortBy'] == 1)?1:0).".xml";
}

$rss = new UniversalFeedCreator(); 
$rss->useCached($rssfile); 
 
$db=NewADOConnection($GLOBALS['DSN']);

if (isset($_GET['topic']) && is_numeric($_GET['topic'])) {

	$rss->link = "http://{$_SERVER['HTTP_HOST']}/discuss/?action=vthread&amp;topic={$_GET['topic']}"; 

		$title = $db->GetOne("select topic_title from `geobb_topics` where `topic_id` = {$_GET['topic']}");
	
	$rss->title = "Geograph.co.uk Forum :: $title"; 
	$rss->syndicationURL = "http://{$_SERVER['HTTP_HOST']}/discuss/syndicator.php?format=$format&amp;topic=".$_GET['topic'];
	
$sql="SELECT * 
FROM `geobb_posts` 
WHERE `topic_id` = {$_GET['topic']}
ORDER BY `post_time` ASC";
	$recordSet = &$db->Execute($sql);
	while (!$recordSet->EOF) 
	{	
		$item = new FeedItem(); 
		
		//create a nice short snippet for title
		$title = preg_replace('/^<i>[^<]+<\/i>([\n\r]*<br>)?([\n\r]*<br>)?([\n\r]*<br>)?/','',$recordSet->fields['post_text']); 
		$title = preg_replace('/<br\\/?>.+$/s','',$title); 
		$title = preg_replace('/<[^>]+>/','',$title); 
		$title = preg_replace('/<[^>]+>/','',$title);
		if (strlen($title) > 75) 
			$title = substr($title,0,72)."..."; 
		
		$item->title = $title; 
		
		//send description if more verbose than the snippet title
		if ($title != $recordSet->fields['post_text'])
			$item->description = $recordSet->fields['post_text'];
			
		$item->date = strtotime($recordSet->fields['post_time']); 
		//$item->source = "http://{$_SERVER['HTTP_HOST']}/discuss/?action=vthread&amp;topic={$_GET['topic']}"; 
		$item->author = $recordSet->fields['poster_name']; 

		$rss->addItem($item);
		
		$recordSet->MoveNext();
	}
} else {
	if (isset($_GET['forum']) && is_numeric($_GET['forum'])) {
		$USER->basicAuthLogin();
		//if got past must be logged in
		$rss->link = "http://{$_SERVER['HTTP_HOST']}/discuss/?action=vtopic&amp;forum={$_GET['forum']}"; 
	
		$title = " :: ".$db->GetOne("select forum_name from `geobb_forums` where `forum_id` = {$_GET['forum']}");
		
		$sql_where = "WHERE geobb_topics.forum_id={$_GET['forum']}";
	}
	$rss->link = "http://{$_SERVER['HTTP_HOST']}/discuss/";

	if ($_GET['sortBy'] == 1) {
		$rss->title = "Geograph.co.uk Forum $title :: Latest Topics"; 
		$rss->description = 'Latest Geograph Topics'; 
		$rss->syndicationURL = "http://{$_SERVER['HTTP_HOST']}/discuss/syndicator.php?format=$format&amp;sortBy=1";
		$sql_order= "geobb_topics.topic_id DESC";
	} else {
		$rss->title = "Geograph.co.uk Forum $title :: Latest Discussions"; 
		$rss->description = 'Latest Geograph Discussions'; 
		$rss->syndicationURL = "http://{$_SERVER['HTTP_HOST']}/discuss/syndicator.php?format=$format";
		$sql_order= "`topic_last_post_id` DESC";
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
		$item->link = "http://{$_SERVER['HTTP_HOST']}/discuss/?action=vthread&amp;topic={$recordSet->fields['topic_id']}"; 
		$item->description = $recordSet->fields['post_text']; 
		$item->date = strtotime($recordSet->fields['post_time']); 
		//$item->source = "http://{$_SERVER['HTTP_HOST']}/discuss/"; 
		$item->author = $recordSet->fields['poster_name']; 

		$rss->addItem($item);
		
		$recordSet->MoveNext();
	}
}


//now output the result
#header("Content-Type:text/xml");

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past 
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); 
                                                    // always modified 
header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1 
header("Cache-Control: post-check=0, pre-check=0", false); 
header("Pragma: no-cache");          



$rss->saveFeed($format, $rssfile); 

?>