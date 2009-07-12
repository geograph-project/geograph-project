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


$rssfile=$_SERVER['DOCUMENT_ROOT']."/rss/article-{$format}-".md5(serialize($_GET)).".$extension";


$rss = new UniversalFeedCreator();
if (empty($_GET['refresh'])) 
	$rss->useCached($format,$rssfile);

$rss->title = 'Geograph Articles'; 
$rss->link = "http://{$_SERVER['HTTP_HOST']}/article/";
 
	
$rss->description = "Recently updated articles on Geograph British Isles"; 

if (!empty($_GET['admin'])) {
	$sql_where = "licence = 'none' or approved = 0";
	$rss->title = 'Geograph Pending Articles'; 
	$rss->syndicationURL = "http://{$_SERVER['HTTP_HOST']}/article/syndicator.php?format=$format&amp;admin=1";

} elseif (!empty($_GET['revdocs'])) {
	$sql_where = "approved > 0 && article_cat_id = 7"; #Geograph Project - Information Documents
	$rss->syndicationURL = "http://{$_SERVER['HTTP_HOST']}/article/syndicator.php?format=$format&amp;revdocs=1";

} elseif (!empty($_GET['revisions'])) {
	$sql_where = "approved > -1";
	$rss->syndicationURL = "http://{$_SERVER['HTTP_HOST']}/article/syndicator.php?format=$format&amp;revisions=1";

} else {
	$sql_where = "licence != 'none' and approved > 0";
	$rss->syndicationURL = "http://{$_SERVER['HTTP_HOST']}/article/feed/recent.$format_extension";

}

if ($format == 'KML' || $format == 'GeoRSS' || $format == 'GPX') {
	require_once('geograph/conversions.class.php');
	require_once('geograph/gridsquare.class.php');
	$conv = new Conversions;

	$rss->geo = true;
}

$db=NewADOConnection($GLOBALS['DSN']);
	

$sql="select article_id, article.article_cat_id, category_name, article.user_id, url, title, extract, licence, publish_date, approved, update_time, create_time, realname, article.gridsquare_id
	from article 
		inner join user using (user_id)
		left join article_cat on (article.article_cat_id = article_cat.article_cat_id)
	where $sql_where
	order by update_time desc
	limit 50";

$recordSet = &$db->Execute($sql);
while (!$recordSet->EOF)
{
	$item = new FeedItem();
	
	$version = $db->getOne("
		select count(*)
		from article_revisions
		where article_id = {$recordSet->fields['article_id']}
		group by article_id");
	
	
	$item->title = $recordSet->fields['title'];
	
	if (!empty($_GET['revisions']) || !empty($_GET['revdocs'])) {
		$realname = $db->getOne("
			select realname
			from article_revisions
				left join user on (article_revisions.modifier = user.user_id)
			where article_id = {$recordSet->fields['article_id']} and update_time = '{$recordSet->fields['update_time']}'");
	
		$item->title .= " [rev #$version by $realname]";
		$recordSet->fields['url'] = "history.php?page={$recordSet->fields['url']}";
		
		$item->author = $realname;
		$item->date = strtotime($recordSet->fields['update_time']);
	} else {
		$item->author = $recordSet->fields['realname'];
		$item->date = strtotime($recordSet->fields['publish_date']);
	}

	//htmlspecialchars is called on link so dont use &amp;
	$item->link = "http://{$_SERVER['HTTP_HOST']}/article/{$recordSet->fields['url']}";
	
	$item->guid = $item->link."?v=$version";

	
	$description = $recordSet->fields['extract'];
	if (strlen($description) > 160)
		$description = substr($description,0,157)."...";
	$item->description = $description;
	$item->category = $recordSet->fields['category_name'];

	if (($format == 'KML' || $format == 'GeoRSS' || $format == 'GPX') && $recordSet->fields['gridsquare_id']) {
		$gridsquare = new GridSquare;
		$grid_ok=$gridsquare->loadFromId($recordSet->fields['gridsquare_id']);

		if ($grid_ok)
			list($item->lat,$item->long) = $conv->gridsquare_to_wgs84($gridsquare);
	
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