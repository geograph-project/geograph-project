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
require_once('geograph/gridimage.class.php');
require_once('geograph/gridsquare.class.php');
require_once('geograph/imagelist.class.php');
	
	
$valid_formats=array('RSS0.91','RSS1.0','RSS2.0','MBOX','OPML','ATOM','ATOM0.3','HTML','JS','PHP','KML','BASE','GeoRSS','GeoPhotoRSS','GPX','TOOLBAR');

if (isset($_GET['extension']) && !isset($_GET['format']))
{
	$_GET['format'] = strtoupper($_GET['extension']);
	$_GET['format'] = str_replace('GEO','Geo',$_GET['format']);
	$_GET['format'] = str_replace('PHOTO','Photo',$_GET['format']);
}

$format="GeoRSS";
if (isset($_GET['format']) && in_array($_GET['format'], $valid_formats))
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

if (isset($_GET['text'])) {
	require_once('geograph/searchcriteria.class.php');
	require_once('geograph/searchengine.class.php');
	require_once('geograph/searchenginebuilder.class.php');
	
	$engine = new SearchEngineBuilder('#'); 
 	$_GET['i'] = $engine->buildSimpleQuery($_GET['text'].' near (anywhere)',30,false,isset($_GET['u'])?$_GET['u']:0);
} elseif (isset($_GET['q']) || !empty($_GET['location'])) {
	require_once('geograph/searchcriteria.class.php');
	require_once('geograph/searchengine.class.php');
	require_once('geograph/searchenginebuilder.class.php');
	
	if (!empty($_GET['lat']) && !empty($_GET['lon'])) {
		$_GET['location'] = $_GET['lat'].','.$_GET['lon'];
	} 
	if (!empty($_GET['location'])) {
		if (!empty($_GET['q'])) {
			$q=trim($_GET['q']).' near '.trim($_GET['location']);
		} else {
			$q=trim($_GET['q']);
		}
	} elseif (!empty($_GET['BBOX'])) {
		$q=trim($_GET['q']).' near (anywhere)';
	} else {
		$q=trim($_GET['q']);
	}
	
	
	$engine = new SearchEngineBuilder('#'); 
 	$_GET['i'] = $engine->buildSimpleQuery($q,30,false,isset($_GET['u'])?$_GET['u']:0);
 	
 	if (!empty($engine->errormsg) && !empty($_GET['fatal'])) {
 		die('error: '.$engine->errormsg);
 	}
 	if (isset($engine->criteria) && $engine->criteria->is_multiple) {
 		die('error: unable to identify a unique location');
 	}
} elseif (false && !empty($_GET['u'])) {
	//no need to use this now getImagesByUser works for lat/long
	require_once('geograph/searchcriteria.class.php');
	require_once('geograph/searchengine.class.php');
	require_once('geograph/searchenginebuilder.class.php');
	
	$_GET['user_id'] = $_GET['u']; 
	$_GET['orderby'] = 'gridimage_id'; 
	$_GET['reverse_order_ind'] = 1; 
	$sortorders = array('gridimage_id'=>'Date Submitted');

	
	$engine = new SearchEngineBuilder('#'); 
 	$_GET['i'] = $engine->buildAdvancedQuery($_GET,false);
}

if (isset($_GET['i']) && is_numeric($_GET['i'])) {
	$pg = (!empty($_GET['page']))?intval(str_replace('/','',$_GET['page'])):0;
	$rssfile=$_SERVER['DOCUMENT_ROOT']."/rss/{$_GET['i']}-{$pg}-{$format}.$extension";
} elseif (isset($_GET['u']) && is_numeric($_GET['u'])) {
	$rssfile=$_SERVER['DOCUMENT_ROOT']."/rss/u{$_GET['u']}-{$format}.$extension";
} else {
	$rssfile=$_SERVER['DOCUMENT_ROOT']."/rss/{$format}.$extension";
}

$rss = new UniversalFeedCreator(); 
$rss->useCached($format,$rssfile); 
$rss->title = 'Geograph British Isles'; 
$rss->link = "http://{$_SERVER['HTTP_HOST']}";
 


if (isset($_GET['i']) && is_numeric($_GET['i'])) {
	require_once('geograph/searchcriteria.class.php');
	require_once('geograph/searchengine.class.php');
		
		$pg = (!empty($_GET['page']))?intval(str_replace('/','',$_GET['page'])):0;
		if (empty($pg) || $pg < 1) {$pg = 1;}
		
	$images = new SearchEngine($_GET['i']);
	
	$rss->description = "Images".$images->criteria->searchdesc; 
	$rss->syndicationURL = "http://{$_SERVER['HTTP_HOST']}/syndicator.php?format=$format&amp;i=".$_GET['i'].(($pg>1)?"&amp;page=$pg":'');
	
	$images->Execute($pg);
	
	$images->images = &$images->results;
	
} elseif (isset($_GET['u']) && is_numeric($_GET['u'])) {
	$profile=new GeographUser($_GET['u']);
	$rss->description = 'Latest Images by '.$profile->realname; 
	$rss->syndicationURL = "http://{$_SERVER['HTTP_HOST']}/syndicator.php?format=$format&amp;u=".$_GET['u'];


	//lets find some recent photos
	$images=new ImageList();
	$images->getImagesByUser($_GET['u'],array('accepted', 'geograph'), 'gridimage_id desc', 15, false);
} else {
	$rss->description = 'Latest Images'; 
	$rss->syndicationURL = "http://{$_SERVER['HTTP_HOST']}/syndicator.php?format=$format";


	//lets find some recent photos
	$images=new ImageList(array('accepted', 'geograph'), 'gridimage_id desc', 15);
}


$cnt=count($images->images);

$linkprefix = (empty($CONF['enable_cluster']))?"http://".$_SERVER['HTTP_HOST']:'';

//create some feed items
for ($i=0; $i<$cnt; $i++)
{
	
	$item = new FeedItem(); 
	$item->guid = $images->images[$i]->gridimage_id;
	$item->title = $images->images[$i]->grid_reference." : ".$images->images[$i]->title; 
	$item->link = "http://{$_SERVER['HTTP_HOST']}/photo/{$images->images[$i]->gridimage_id}";
	if (isset($images->images[$i]->dist_string) || isset($images->images[$i]->imagetakenString)) {
		$item->description = $images->images[$i]->dist_string.($images->images[$i]->imagetakenString?' Taken: '.$images->images[$i]->imagetakenString:'')."<br/>".$images->images[$i]->comment; 
		$item->descriptionHtmlSyndicated = true;
	} else {
		$item->description = $images->images[$i]->comment; 
	}
	if (!empty($images->images[$i]->imagetaken) && strpos($images->images[$i]->imagetaken,'-00') === FALSE) {
		$item->imageTaken = $images->images[$i]->imagetaken;
	}
	
	$item->date = strtotime($images->images[$i]->submitted); 
	$item->source = "http://{$_SERVER['HTTP_HOST']}/profile/".$images->images[$i]->user_id; 
	$item->author = $images->images[$i]->realname; 
	     
	     if ($format == 'KML' || $format == 'GeoRSS' || $format == 'GeoPhotoRSS' || $format == 'GPX') {
	     	$item->lat = $images->images[$i]->wgs84_lat;
	     	$item->long = $images->images[$i]->wgs84_long;
	     	if ($format == 'KML') {
	     		$details = $images->images[$i]->getThumbnail(120,120,2);
	     		if (!empty($details['server'])) {
	     			$item->thumb = $details['server'].$details['url']; 
				$item->thumbTag = $details['html']; 				
	     		} else {
				$item->thumb = $linkprefix.$details['url']; 
				$item->thumbTag = preg_replace('/\/photos\/.*\.jpg/',$item->thumb,$details['html']); 
			}
	       	} elseif ($format == 'GeoPhotoRSS')
	     		$item->thumb = $linkprefix.$images->images[$i]->getThumbnail(120,120,true); 
	     } elseif ($format == 'BASE') {
	     	$item->thumb = $linkprefix.$images->images[$i]->getThumbnail(120,120,true); 
	     } elseif ($format == 'PHP') {
	     	$item->thumb = $images->images[$i]->getThumbnail(120,120,true); 
	     } elseif ($format == 'TOOLBAR') {
	     	ob_start();
	     	imagejpeg($images->images[$i]->getSquareThumb(16));
	     	$item->thumbdata = ob_get_contents();
	     	ob_end_clean();
	     }
	     
	//<license rdf:resource="http://creativecommons.org/licenses/by-sa/2.0/" />
	     
	    $item->licence = "&copy; Copyright <i class=\"attribution\">".htmlspecialchars($images->images[$i]->realname)."</i> and licensed for reuse under this <a rel=\"license\" href=\"http://creativecommons.org/licenses/by-sa/2.0/\">Creative Commons Licence</a>";
	     
	
    $rss->addItem($item); 
	

}


header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past 
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); 
                                                    // always modified 
header("Cache-Control: must-revalidate");  // HTTP/1.1 
header("Cache-Control: post-check=0, pre-check=0", false); 
header("Pragma: no-cache");          



$rss->saveFeed($format, $rssfile); 

?>