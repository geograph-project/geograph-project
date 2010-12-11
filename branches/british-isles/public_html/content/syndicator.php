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


if (!empty($_GET['q']) && empty($_GET['scope']) && empty($_GET['auth'])) {
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

$rssfile=$_SERVER['DOCUMENT_ROOT']."/rss/{$CONF['template']}/content-{$format}-".md5(serialize($_GET)).".$extension";


$rss = new UniversalFeedCreator();
if (empty($_GET['refresh'])) 
	$rss->useCached($format,$rssfile);

$rss->title = 'Geograph Content'; 
$rss->link = "http://{$_SERVER['HTTP_HOST']}/content/";
 
	
$rss->description = "Recently updated content on Geograph British Isles"; 

if ($format == 'KML') {
	$rss->description .= ". <a href=\"{$rss->link}\">View Collections Homepage</a> or <a href=\"http://{$_SERVER['HTTP_HOST']}/\">Geograph Homepage</a>";
}


$rss->syndicationURL = "http://{$_SERVER['HTTP_HOST']}/content/feed/recent.$format_extension";


if ($format == 'KML' || $format == 'GeoRSS' || $format == 'GPX') {
	require_once('geograph/conversions.class.php');
	require_once('geograph/gridsquare.class.php');
	$conv = new Conversions;

	$rss->geo = true;
}

$db = GeographDatabaseConnection(true);
	
$limit = (isset($_GET['nolimit']))?1000:50;


// --------------
	
	$where = array();
	
	if (!empty($_GET['q'])) {
		$q=trim($_GET['q']);
		
		if (!empty($_GET['page'])) {
			$pg = intval($_GET['page']);
		} else {
			$pg = 1;
		}
		
		$sphinx = new sphinxwrapper($q);
		$sphinx->pageSize = $pgsize = $limit;
		
		if (preg_match('/\bp(age|)(\d+)\s*$/',$q,$m)) {
			$pg = intval($m[2]);
			$sphinx->q = preg_replace('/\bp(age|)\d+\s*$/','',$sphinx->q);
		}
		
		$rss->title .= " matching [ ".htmlentities($sphinx->q)." ]";
		
		#$sphinx->processQuery();
		
		//if ((isset($CONF['forums']) && empty($CONF['forums'])) || $USER->user_id == 0 ) {
			$sphinx->qoutput = $sphinx->q;
			$sphinx->q .= " @source -themed";
		//}
		
		$ids = $sphinx->returnIds($pg,'content_stemmed');
		
		if (count($ids)) {
			$where[] = "content_id IN(".join(",",$ids).")";
		} else {
			$where[] = "0";
		}
		$resultCount = $sphinx->resultCount;
		
		$rss->title .= " ($resultCount results)";
		
		// --------------
	} elseif ($format == 'KML') {
		$where[] = "gridsquare_id > 0";
	}
	
	
	if (!empty($_GET['scope'])) {
		$filters = array();
		if (is_array($_GET['scope'])) {
			$s = $_GET['scope'];
		} else {
			$s = explode(',',$_GET['scope']);
		}
		foreach ($s as $scope) {
			switch($scope) {
				case 'blog':
				case 'trip':
				case 'article':
				case 'gallery':
				case 'themed':
				case 'help':
				case 'snippet':
				case 'portal':
				case 'user':
				case 'category':
				case 'other':
					$filters['source'][] = $scope;
					break;
				case 'info':
				case 'document':
					$filters['type'][] = $scope;
					break;
			}
		}
		foreach ($filters as $key => $value) {
			if (!empty($value)) {
				$where[] = "content.$key IN ('".implode("','",$value)."')";
			}
		}
	} else {
		$where[] = "source IN ('article', 'gallery', 'help', 'blog')";
	}
	
	if (!empty($_GET['user_id']) && preg_match('/^\d+$/',$_GET['user_id'])) {
		$where[] = "content.user_id = {$_GET['user_id']}";
		$profile=new GeographUser($_GET['user_id']);
		$rss->title = "By ".($profile->realname);	
	}
	
	$where = implode(' AND ',$where);
	
$sql="select content.content_id,content.user_id,url,title,extract,updated,created,realname,content.source,gridsquare_id
	from content 
		left join user using (user_id)
	where $where
	order by updated desc
	limit $limit";

$recordSet = &$db->Execute($sql);
while (!$recordSet->EOF)
{
	$item = new FeedItem();
	
	$item->title = $recordSet->fields['title'];

	//htmlspecialchars is called on link so dont use &amp;
	if (strpos($recordSet->fields['url'],'/')===0) {
		$item->link = "http://{$_SERVER['HTTP_HOST']}{$recordSet->fields['url']}";
	} else {
		$item->link = $recordSet->fields['url'];
	}
	
	$description = $recordSet->fields['extract'];
	if (strlen($description) > 160)
		$description = substr($description,0,157)."...";
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


