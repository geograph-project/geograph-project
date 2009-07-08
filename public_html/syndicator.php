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
	
	
$valid_formats=array('RSS0.91','RSS1.0','RSS2.0','MBOX','OPML','ATOM','ATOM0.3','HTML','JS','PHP','KML','BASE','GeoRSS','GeoPhotoRSS','GPX','TOOLBAR','MEDIA');

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


/**
 * We are building a text search for the first time
 */
if (isset($_GET['q']) || !empty($_GET['location'])) {
	if (!empty($_GET['lat']) && !empty($_GET['lon'])) {
		$_GET['location'] = $_GET['lat'].','.$_GET['lon'];
	}
	if (!empty($_GET['BBOX'])) {
		//this is treated special later...
		$_GET['location'] = '(anywhere)';
	}
	if (!empty($_GET['location'])) {
		if (!empty($_GET['text'])) {
			$q=trim($_GET['text']).' near '.trim($_GET['location']);
		} elseif (!empty($_GET['q'])) {
			$q=trim($_GET['q']).' near '.trim($_GET['location']);
		} else {
			$q='near '.trim($_GET['location']);
		}
	} else {
		$q=trim($_GET['q']);
	}
	//temporally redirect piclens full-text search directly to sphinx
	if (isset($_GET['source']) && ($_GET['source'] == 'piclens' || $_GET['source'] == 'fist') ) {
		$sphinx = new sphinxwrapper($q);
		
		//gets a cleaned up verion of the query (suitable for filename etc) 
		$cacheid = $sphinx->q;
		
		$sphinx->pageSize = $pgsize = 15;
		
		$sphinx->processQuery();
		
		$pg = (!empty($_GET['page']))?intval(str_replace('/','',$_GET['page'])):0;
		if (empty($pg) || $pg < 1) {$pg = 1;}
		
		$ids = $sphinx->returnIds($pg,'_images');
		unset($q);
	} else {
		$cacheid = getTextKey();
		$pg = 1;
		
		//$q is used below
	}
} elseif (isset($_GET['text'])) {
	$cacheid = getTextKey();
	$pg = 1;
	
	$q = $_GET['text'].' near (anywhere)';

} 

$opt_expand = (!empty($_GET['expand']) && $format != 'KML')?1:0;

if (isset($cacheid)) {
	$rssfile=$_SERVER['DOCUMENT_ROOT']."/rss/$cacheid-{$pg}-{$format}{$opt_expand}.$extension";
	$rss_timeout = 3600;
} elseif (isset($_GET['i']) && is_numeric($_GET['i'])) {
	$pg = (!empty($_GET['page']))?intval(str_replace('/','',$_GET['page'])):1;
	$rssfile=$_SERVER['DOCUMENT_ROOT']."/rss/{$_GET['i']}-{$pg}-{$format}{$opt_expand}.$extension";
	$rss_timeout = 3600;
} elseif (isset($_GET['u']) && is_numeric($_GET['u'])) {
	$rssfile=$_SERVER['DOCUMENT_ROOT']."/rss/u{$_GET['u']}-{$format}{$opt_expand}.$extension";
	$rss_timeout = 1800;
} else {
	$rssfile=$_SERVER['DOCUMENT_ROOT']."/rss/{$format}{$opt_expand}.$extension";
	$rss_timeout = 900;
}

$rss = new UniversalFeedCreator(); 
$rss->useCached($format,$rssfile,$rss_timeout); 
$rss->title = 'Geograph Germany'; 
$rss->link = "http://{$_SERVER['HTTP_HOST']}/";


/**
 * Create a query the first time round!
 */
if (isset($q)) {
	require_once('geograph/searchcriteria.class.php');
	require_once('geograph/searchengine.class.php');
	require_once('geograph/searchenginebuilder.class.php');
	
	$engine = new SearchEngineBuilder('#'); 
	$engine->searchuse = "syndicator";
	$_GET['i'] = $engine->buildSimpleQuery($q,$CONF['default_search_distance'],false,isset($_GET['u'])?$_GET['u']:0);

	if (function_exists('symlink') && isset($cacheid)) {
		//create a link so cache can be access as original query(cacheid) or directly via its 'i' number later...
		symlink($_SERVER['DOCUMENT_ROOT']."/rss/$cacheid-{$pg}-{$format}{$opt_expand}.$extension",
		        $_SERVER['DOCUMENT_ROOT']."/rss/{$_GET['i']}-{$pg}-{$format}{$opt_expand}.$extension");
	}

	if (!empty($engine->errormsg) && !empty($_GET['fatal'])) {
		die('error: '.$engine->errormsg);
	}
	if (isset($engine->criteria) && $engine->criteria->is_multiple) {
		die('error: unable to identify a unique location');
	}
}


/**
 * A full-text query
 */
if (isset($sphinx)) {
	$rss->description = "Images, matching ".$sphinx->qoutput; 
	if ($sphinx->resultCount) {
		$rss->description .= " ({$sphinx->resultCount} in total)";
	}
	$rss->syndicationURL = "http://{$_SERVER['HTTP_HOST']}/syndicator.php?q=".urlencode($sphinx->q).(($pg>1)?"&amp;page=$pg":'')."&amp;format=".($format).((isset($_GET['source']))?"&amp;source={$_GET['source']}":'');

	if ($format == 'MEDIA') {
		$rss->link =  "http://{$_SERVER['HTTP_HOST']}/search.php?q=".urlencode($sphinx->$q).(($pg>1)?"&amp;page=$pg":'');
		if ($pg>1) {
			$prev = $pg - 1;
			$rss->prevURL = "http://{$_SERVER['HTTP_HOST']}/syndicator.php?q=".urlencode($sphinx->q).(($prev>1)?"&amp;page=$prev":'')."&amp;format=".($format).((isset($_GET['source']))?"&amp;source={$_GET['source']}":'');
		}
		
		$offset = ($pg -1)* $pgsize;
		if ($pg < 10 && $offset < 250 && $sphinx->numberOfPages > $pg) {
			$next = $pg + 1;
			$rss->nextURL = "http://{$_SERVER['HTTP_HOST']}/syndicator.php?q=".urlencode($sphinx->q).(($next>1)?"&amp;page=$next":'')."&amp;format=".($format).((isset($_GET['source']))?"&amp;source={$_GET['source']}":'');
		}
		$rss->icon = "http://{$CONF['STATIC_HOST']}/templates/basic/img/logo.gif";
	}

	//lets find some photos
	$images=new ImageList();
	$images->getImagesByIdList($ids);

/**
 * runs a canned search (possibly created above)
 */
} elseif (isset($_GET['i']) && is_numeric($_GET['i'])) {
	require_once('geograph/searchcriteria.class.php');
	require_once('geograph/searchengine.class.php');
		
		$pg = (!empty($_GET['page']))?intval(str_replace('/','',$_GET['page'])):1;
		if (empty($pg) || $pg < 1) {$pg = 1;}
		
	$images = new SearchEngine($_GET['i']);
	
	$rss->description = "Images".$images->criteria->searchdesc; 
	$rss->syndicationURL = "http://{$_SERVER['HTTP_HOST']}/feed/results/".$_GET['i'].(($pg>1)?"/$pg":'').".".strtolower($format);
	
	$images->Execute($pg);
	if ($images->resultCount) {
		$rss->description .= " ({$images->resultCount} in total)";
	}
	
	if ($format == 'MEDIA') {
		$rss->link =  "http://{$_SERVER['HTTP_HOST']}/search.php?i=".$_GET['i'].(($pg>1)?"&amp;page=$pg":'');
		if ($pg>1) {
			$prev = $pg - 1;
			$rss->prevURL = "http://{$_SERVER['HTTP_HOST']}/feed/results/".$_GET['i'].(($prev>1)?"/$prev":'').".".strtolower($format);
		}
		$pgsize = $images->criteria->resultsperpage;
			
		if (!$pgsize) {$pgsize = 15;}
		
		$offset = ($pg -1)* $pgsize;
		if ($pg < 10 && $offset < 250 && $images->numberOfPages > $pg) {
			$next = $pg + 1;
			$rss->nextURL = "http://{$_SERVER['HTTP_HOST']}/feed/results/".$_GET['i'].(($next>1)?"/$next":'').".".strtolower($format);
		}
		$rss->icon = "http://{$CONF['STATIC_HOST']}/templates/basic/img/logo.gif";
	} 
	
	$images->images = &$images->results;

/**
 * A user specific feed
 */
} elseif (isset($_GET['u']) && is_numeric($_GET['u'])) {
	$profile=new GeographUser($_GET['u']);
	$rss->description = 'Latest Images by '.$profile->realname; 
	$rss->syndicationURL = "http://{$_SERVER['HTTP_HOST']}/profile/".intval($_GET['u'])."/feed/recent.".strtolower($format);


	//lets find some recent photos
	$images=new ImageList();
	$images->getImagesByUser($_GET['u'],array('accepted', 'geograph'), 'gridimage_id desc', 15, false);
	
/**
 * general feed of all images
 */
} else {
	$rss->description = 'Latest Images'; 
	$rss->syndicationURL = "http://{$_SERVER['HTTP_HOST']}/feed/recent.".strtolower($format);

	//lets find some recent photos
	$images=new ImageList(array('accepted', 'geograph'), 'gridimage_id desc', 15);
}


$cnt=count($images->images);

$geoformat = ($format == 'KML' || $format == 'GeoRSS' || $format == 'GeoPhotoRSS' || $format == 'GPX' || $format == 'MEDIA');
$photoformat = ($format == 'KML' || $format == 'GeoPhotoRSS' || $format == 'BASE' || $format == 'MEDIA');

//create some feed items
for ($i=0; $i<$cnt; $i++)
{
	
	$item = new FeedItem(); 
	$item->title = $images->images[$i]->grid_reference." : ".$images->images[$i]->title; 
	$item->guid = $item->link = "http://{$_SERVER['HTTP_HOST']}/photo/{$images->images[$i]->gridimage_id}";
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
	$item->source = "http://".$_SERVER['HTTP_HOST'].$images->images[$i]->profile_link;
	$item->author = $images->images[$i]->realname;

	if ($geoformat) {
		$item->lat = $images->images[$i]->wgs84_lat;
		$item->long = $images->images[$i]->wgs84_long;
	}
	if ($photoformat) {
		$details = $images->images[$i]->getThumbnail(120,120,2);
		$item->thumb = $details['server'].$details['url']; 
		$item->thumbTag = $details['html'];
		
		if ($format == 'MEDIA') {
			$item->content = $images->images[$i]->_getFullpath(true,true); 
			if ($opt_expand) {
				$title=$this->grid_reference.' : '.htmlentities2($images->images[$i]->title).' by '.htmlentities2($images->images[$i]->realname);
				$item->description = '<a href="'.$item->link.'" title="'.$title.'">'.$images->images[$i]->getThumbnail(120,120).'</a><br/>'. $item->description;
				$item->descriptionHtmlSyndicated = true;
			}
		}
	} elseif ($format == 'PHP') {
		$item->thumb = $images->images[$i]->getThumbnail(120,120,true); 
	} elseif ($format == 'TOOLBAR') {
		ob_start();
		imagejpeg($images->images[$i]->getSquareThumb(16));
		$item->thumbdata = ob_get_clean();
	} elseif ($opt_expand) {
		$title=$this->grid_reference.' : '.htmlentities2($images->images[$i]->title).' by '.htmlentities2($images->images[$i]->realname);
		$item->description = '<a href="'.$item->link.'" title="'.$title.'">'.$images->images[$i]->getThumbnail(120,120).'</a><br/>'. $item->description;
		$item->descriptionHtmlSyndicated = true;
	}

	//<license rdf:resource="http://creativecommons.org/licenses/by-sa/2.0/" />

	if ($format == 'KML') {
		$item->licence = "&copy; Copyright <i class=\"attribution\">".htmlspecialchars($images->images[$i]->realname)."</i> and licensed for reuse under this <a rel=\"license\" href=\"http://creativecommons.org/licenses/by-sa/2.0/\">Creative Commons Licence</a>";
	} else {
		$item->licence = "http://creativecommons.org/licenses/by-sa/2.0/";
	}

	$rss->addItem($item);
}

//these are outputed by the rss class now!
#customExpiresHeader($rss_timeout,true); //we cache it for a while anyway! 
#header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); 

$rss->saveFeed($format, $rssfile); 

########################

/**
 * Build a unique key for this search - critically ignore the apikey and other bogus parameters
 */
function getTextKey() {
	$t = '';
	foreach (array('text','q','location','BBOX','lat','lon','u') as $k) {
		$t .= "|".(empty($_GET[$k])?'':$_GET[$k]);
	}
	return md5($t);
}

?>
