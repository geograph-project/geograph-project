<?php

/**
 * $Project: GeoGraph $
 * $Id: syndicator.php 8878 2018-10-22 17:18:22Z barry $
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

if (@$_SERVER['HTTP_USER_AGENT'] == "PlingsImageGetter" || @$_GET['q'] == ',') {
	header('HTTP/1.0 403 Forbidden');
	header('Cache-Control: max-age=2592000');
	header("Status: 403 Forbidden");
	exit;
} elseif(isset($_GET['key']) && $_GET['key'] == '[apikey]' && strpos($_SERVER['HTTP_USER_AGENT'],'LWP') === 0) {
        header('HTTP/1.0 403 Forbidden');
	header('Cache-Control: max-age=2592000');
	header("Status: 403 Forbidden");
        exit;
}
if (strpos(@$_SERVER['HTTP_USER_AGENT'], 'archive.org_bot')!==FALSE) {
     header('HTTP/1.0 403 Forbidden');
     exit;
}

if (empty($_GET['key']))
	$_SERVER['HTTPS'] = 'on'; //cheeky, but forces generation of https:// urls :)
elseif ($_GET['key'] == 'c743e78c04')
	$_GET['new'] = 1; //as a test, lets start redirecting certain high-volumn users to the new engine!

require_once('geograph/global.inc.php');

rate_limiting('syndicator.php');

//if (empty($_GET['format']) && strpos($_SERVER['HTTP_USER_AGENT'], 'Googlebot')!==FALSE) {
//	//lets start moving search engine bots. but Sitemaps use format=ATOM, so exclude them!
//	pageMustBeHTTP();
//}

require_once('geograph/feedcreator.class.php');
require_once('geograph/gridimage.class.php');
require_once('geograph/gridsquare.class.php');
require_once('geograph/imagelist.class.php');


###################################################################
// step -1 - log in refis

if (!empty($CONF['redis_host']))
{
        if (empty($redis)) {
                $redis = new Redis();
                $redis->connect($CONF['redis_host'], $CONF['redis_port']);
        }
        if (!empty($CONF['redis_api_db']))
                $redis->select($CONF['redis_api_db']);

	$bits = array();
	$bits[] = @$_GET['key'];
	$bits[] = getRemoteIP();
	if (!empty($_SERVER['HTTP_REFERER']) && strlen($_SERVER['HTTP_REFERER']) > 2) {
		$ref = @parse_url($_SERVER['HTTP_REFERER']);
		$bits[] = $ref['host'];
	} else {
		$bits[] = '';
	}
	if (!empty($_SERVER['HTTP_USER_AGENT'])) {
		$bits[] = preg_replace('/[^\w]+/','_',$_SERVER['HTTP_USER_AGENT']);
	}

	$identy = implode('|',$bits);

	#hIncrBy($key, $field, $increment)
	$redis->hIncrBy('syndicator',$identy,1);
	$redis->hIncrBy('s|'.$identy,date("Y-m-d H"),1);
}

###################################################################
// step 0 - validate the format requested

$valid_formats=array('RSS0.91','RSS1.0','RSS2.0','MBOX','OPML','ATOM','ATOM0.3','HTML','JS','PHP','KML','BASE','GeoRSS','GeoPhotoRSS','GPX','TOOLBAR','MEDIA','JSON');

if (isset($_GET['extension']) && !isset($_GET['format']))
{
	$_GET['format'] = strtoupper($_GET['extension']);
	$_GET['format'] = str_replace('_','.',$_GET['format']);
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

$format_extension = strtolower(str_replace('.','_',$format));

###################################################################
// step 1 - figure out a cache token, to potentially short circuit further processing

if (!empty($_GET['tag'])) {
	if (strpos($_GET['tag'],':') !== false) {
		$_GET['text'] = 'tags:"'.str_replace(':',' ',trim($_GET['tag'])).'"';
	} elseif (strpos($_GET['tag'],' ') !== false) {
		$_GET['text'] = 'tags:"'.trim($_GET['tag']).'"';
	} else {
		$_GET['text'] = 'tags:'.trim($_GET['tag']);
	}
} elseif (!empty($_GET['top'])) {
	$_GET['text'] = 'tags:"top '.trim($_GET['top']).'"';
}
if (isset($_GET['q']) || !empty($_GET['location'])) {
	if (!empty($_GET['lat']) && !empty($_GET['lon'])) {
		$_GET['location'] = $_GET['lat'].','.$_GET['lon'];
	}
	if (!empty($_GET['BBOX'])) {
		//this is treated special later...
		$_GET['location'] = '(anywhere)';
	}
	if (!empty($_GET['location'])) {
		if (preg_match('/^-+\d+$/',$_GET['location'])) {
			header('HTTP/1.0 403 Forbidden');
			print '{error:"invalid location"}';
			exit;
		}
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
	} else {
		$cacheid = getTextKey();
		$pg = 1;

		//$q is used below
	}
} elseif (isset($_GET['text'])) {
	$cacheid = getTextKey();
	$pg = 1;

	$q = $_GET['text'].' near (anywhere)';

} elseif (!empty($_GET['orderby']) && $_GET['orderby'] == 'sequence') {
	$_GET['i'] = 56711675;
}

$opt_expand = (!empty($_GET['expand']) && $format != 'KML')?1:0;

if (isset($cacheid)) {
	$rssfile=$_SERVER['DOCUMENT_ROOT']."/rss/{$CONF['template']}/$cacheid-{$pg}-{$format}{$opt_expand}.$extension";
	$rss_timeout = 3600*6;
} elseif (isset($_GET['i']) && is_numeric($_GET['i'])) {
	$pg = (!empty($_GET['page']))?intval(str_replace('/','',$_GET['page'])):1;
	$rssfile=$_SERVER['DOCUMENT_ROOT']."/rss/{$CONF['template']}/{$_GET['i']}-{$pg}-{$format}{$opt_expand}.$extension";
	$rss_timeout = 3600*6;
} elseif (isset($_GET['u']) && is_numeric($_GET['u'])) {
	$rssfile=$_SERVER['DOCUMENT_ROOT']."/rss/{$CONF['template']}/u{$_GET['u']}-{$format}{$opt_expand}.$extension";
	$rss_timeout = 1800*6;
} else {
	$rssfile=$_SERVER['DOCUMENT_ROOT']."/rss/{$CONF['template']}/{$format}{$opt_expand}.$extension";
	$rss_timeout = 900;
}

if (isset($_GET['callback'])) {
	$callback=preg_replace('/[^\w$]+/','',$_GET['callback']);
	$rssfile=preg_replace('/\.(\w+)/',".$callback.\\1",$rssfile);
}

$rss = new UniversalFeedCreator();
if (empty($_GET['refresh']))
	$rss->useCached($format,$rssfile,$rss_timeout); //if cached available, it returned and no more done below!

if ($CONF['template'] == 'ireland') {
	$rss->title = 'Geograph Ireland';
} else {
	$rss->title = 'Geograph Britain and Ireland';
}
if ($CONF['template'] == 'api') {
	$rss->link = "{$CONF['CONTENT_HOST']}/";
} else {
	$rss->link = "{$CONF['SELF_HOST']}/";
}
$baselink = $rss->link;

###################################################################
// step 2 - build query object

/**
 * A nearby query no longer done via search engine
 */
if (!empty($q) && preg_match("/\b(-?\d+\.?\d*)[, ]+(-?\d+\.?\d*)\b/",$q,$ll) && (empty($_GET['groupby']) || $_GET['groupby']=='scenti') && !empty($_GET['new'])) {

	//the new system builds+fetches in one function! (below)


/**
 * the sphinx object already created
 */
} elseif (isset($sphinx)) {
	$sphinx->pageSize = $pgsize = 15;

	$sphinx->processQuery();

	$pg = (!empty($_GET['page']))?intval(str_replace('/','',$_GET['page'])):0;
	if (empty($pg) || $pg < 1) {$pg = 1;}

	$ids = $sphinx->returnIds($pg,'_images');

/**
 * Create a query the first time round!
 */
} elseif (isset($q)) {
	require_once('geograph/searchcriteria.class.php');
	require_once('geograph/searchengine.class.php');
	require_once('geograph/searchenginebuilder.class.php');

	$engine = new SearchEngineBuilder('#');
	$engine->searchuse = "syndicator";
	$_GET['i'] = $engine->buildSimpleQuery($q,!empty($_GET['distance'])?min(20,floatval($_GET['distance'])):$CONF['default_search_distance'],false,isset($_GET['u'])?$_GET['u']:0);

	if (!empty($GLOBALS['memcache']) && $GLOBALS['memcache']->valid && isset($cacheid) && !empty($_GET['i'])) {
		$target = "symlink:".$_SERVER['DOCUMENT_ROOT']."/rss/{$CONF['template']}/$cacheid-{$pg}-{$format}{$opt_expand}.$extension";
		$GLOBALS['memcache']->name_set('rss',
			$_SERVER['DOCUMENT_ROOT']."/rss/{$CONF['template']}/{$_GET['i']}-{$pg}-{$format}{$opt_expand}.$extension",
			$target,$GLOBALS['memcache']->compress,$rss_timeout);

	} elseif (function_exists('symlink') && isset($cacheid) && !empty($_GET['i'])) {
		//create a link so cache can be access as original query(cacheid) or directly via its 'i' number later...
		symlink($_SERVER['DOCUMENT_ROOT']."/rss/{$CONF['template']}/$cacheid-{$pg}-{$format}{$opt_expand}.$extension",
		        $_SERVER['DOCUMENT_ROOT']."/rss/{$CONF['template']}/{$_GET['i']}-{$pg}-{$format}{$opt_expand}.$extension");
	}

	if (!empty($engine->errormsg) && !empty($_GET['fatal'])) {
		die('error: '.$engine->errormsg);
	}
	if (isset($engine->criteria) && $engine->criteria->is_multiple) {
		die('error: unable to identify a unique location');
	}
}

###################################################################
// step 3 - fetch images

/**
 * run a streamlined 'nearby' query
 */
if (!empty($q) && preg_match("/\b(-?\d+\.?\d*)[, ]+(-?\d+\.?\d*)\b/",$q,$ll) && (empty($_GET['groupby']) || $_GET['groupby'] == 'scenti') && !empty($_GET['new'])) {

	$rss->description = "Images Nearby";

        require_once('geograph/imagelist.class.php');
        $images = new ImageList();

        //this is a designed to emulate searchenginebuilder->buildSimpleQuery() - but MUCH more lightweight
        $images->buildSimpleQuery($q,!empty($_GET['distance'])?min(20,floatval($_GET['distance'])):$CONF['default_search_distance'],false,isset($_GET['u'])?$_GET['u']:0);
        //its both build and execute, so engine->Execute(1) is **already** run!

	if ($images->resultCount) {
		$rss->description .= " ({$images->resultCount} in total)";
	}

/**
 * A full-text query
 */
} elseif (isset($sphinx)) {
	$rss->description = "Images, matching ".$sphinx->qoutput;
	if ($sphinx->resultCount) {
		$rss->description .= " ({$sphinx->resultCount} in total)";
	}
	$rss->syndicationURL = $baselink."syndicator.php?q=".urlencode($sphinx->q).(($pg>1)?"&amp;page=$pg":'')."&amp;format=".($format).((isset($_GET['source']))?"&amp;source={$_GET['source']}":'');

	if ($format == 'MEDIA') {
		$rss->link =  $baselink."search.php?q=".urlencode($sphinx->q).(($pg>1)?"&amp;page=$pg":'');
		if ($pg>1) {
			$prev = $pg - 1;
			$rss->prevURL = $baselink."syndicator.php?q=".urlencode($sphinx->q).(($prev>1)?"&amp;page=$prev":'')."&amp;format=".($format).((isset($_GET['source']))?"&amp;source={$_GET['source']}":'');
		}

		$offset = ($pg -1)* $pgsize;
		if ($pg < 50 && $offset < 1000 && $sphinx->numberOfPages > $pg) {
			$next = $pg + 1;
			$rss->nextURL = $baselink."syndicator.php?q=".urlencode($sphinx->q).(($next>1)?"&amp;page=$next":'')."&amp;format=".($format).((isset($_GET['source']))?"&amp;source={$_GET['source']}":'');
		}
		$rss->icon = "{$CONF['STATIC_HOST']}/templates/basic/img/logo.gif";
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
	$rss->syndicationURL = $baselink."feed/results/".$_GET['i'].(($pg>1)?"/$pg":'').".$format_extension";

	$images->Execute($pg);
	if ($images->resultCount) {
		$rss->description .= " ({$images->resultCount} in total)";
	}


if (isset($_GET['php_profile']) && class_exists('Profiler',false)) {
        Profiler::render();
       print "<br><br>Results = ".count($images->results);
       exit;
}

	if (!empty($images->error)) {

		$item = new FeedItem();
		$item->title = $images->error;
		$item->description = "Unfortunatly it doesn't appear the search was processed, this is most likly a invalid combination of search terms.";
		if ($engine->error != "Syntax Error") {
			$item->description = " But could also be a temporarlly issue so you could try again in a little while.";
		}
		$item->date = time();
		$item->author = $rss->title;

		$rss->addItem($item);

		$rss->saveFeed($format, $rssfile);

		exit;
	}

	if ($format == 'MEDIA' || $format == 'JSON') {
		$rss->link =  $baselink."search.php?i=".$_GET['i'].(($pg>1)?"&amp;page=$pg":'');
		if ($pg>1) {
			$prev = $pg - 1;
			$rss->prevURL = $baselink."feed/results/".$_GET['i'].(($prev>1)?"/$prev":'').".$format_extension";
		}
		$pgsize = $images->criteria->resultsperpage;

		if (!$pgsize) {$pgsize = 15;}

		$offset = ($pg -1)* $pgsize;
		if ($pg < 10 && $offset < 250 && $images->numberOfPages > $pg) {
			$next = $pg + 1;
			$rss->nextURL = $baselink."feed/results/".$_GET['i'].(($next>1)?"/$next":'').".$format_extension";
		}
		$rss->icon = "{$CONF['STATIC_HOST']}/templates/basic/img/logo.gif";
	}

	$images->images = &$images->results;

/**
 * A user specific feed
 */
} elseif (isset($_GET['u']) && is_numeric($_GET['u'])) {
	$profile=new GeographUser($_GET['u']);
	$rss->description = 'Latest Images by '.$profile->realname;
	$rss->syndicationURL = $baselink."profile/".intval($_GET['u'])."/feed/recent.$format_extension";


	//lets find some recent photos
	$images=new ImageList();
	$images->getImagesByUser($_GET['u'],array('accepted', 'geograph'), 'gridimage_id desc', 15, false);

/**
 * general feed of all images
 */
} else {
	$rss->description = 'Latest Images';
	$rss->syndicationURL = $baselink."feed/recent.$format_extension";

	//lets find some recent photos
	$images=new ImageList(array('accepted', 'geograph'), 'gridimage_id desc', 15);
}

###################################################################
// Step 4 - render feed

$cnt=empty($images->images)?0:count($images->images);

$geoformat = ($format == 'KML' || $format == 'GeoRSS' || $format == 'GeoPhotoRSS' || $format == 'GPX' || $format == 'MEDIA' || $format == 'JSON');
$photoformat = ($format == 'KML' || $format == 'GeoPhotoRSS' || $format == 'BASE' || $format == 'MEDIA' || $format == 'JSON');


//create some feed items
for ($i=0; $i<$cnt; $i++)
{
	$item = new FeedItem();
	$item->title = $images->images[$i]->grid_reference." : ".$images->images[$i]->title;
	$item->guid = $item->link = $baselink."photo/{$images->images[$i]->gridimage_id}";
	if (isset($images->images[$i]->dist_string) || isset($images->images[$i]->imagetakenString)) {
		@$item->description = $images->images[$i]->dist_string.($images->images[$i]->imagetakenString?' Taken: '.$images->images[$i]->imagetakenString:'');
		if ($item->description) {
			$item->description .= "<br/>";
		}
		$item->description .= $images->images[$i]->comment;
		$item->descriptionHtmlSyndicated = true;
	} else {
		$item->description = $images->images[$i]->comment;
	}
	if (!empty($images->images[$i]->imagetaken) && strpos($images->images[$i]->imagetaken,'-00') === FALSE) {
		$item->imageTaken = $images->images[$i]->imagetaken;
	}

	if (!empty($images->images[$i]->upd_timestamp))
		$item->dateUpdated = strtotime($images->images[$i]->upd_timestamp);
	$item->date = strtotime($images->images[$i]->submitted);
	$item->source = $baselink.preg_replace('/^\//','',$images->images[$i]->profile_link);
	$item->author = $images->images[$i]->realname;
	if (!empty($images->images[$i]->tags))
		$item->tags = $images->images[$i]->tags;

	if ($geoformat) {
		$item->lat = $images->images[$i]->wgs84_lat;
		$item->long = $images->images[$i]->wgs84_long;
	}
	if ($photoformat) {
		//todo, this should be internal to imagelist!
		if (!empty($images->db)) $images->images[$i]->db =&$images->db;
		$details = $images->images[$i]->getThumbnail(120,120,2);
		$item->thumb = $details['server'].$details['url'];
		$item->thumbTag = $details['html'];
		if (!empty($images->images[$i]->imageclass))
			$item->category = $images->images[$i]->imageclass;
		if (!empty($images->images[$i]->tags))
			$item->tags = $images->images[$i]->tags;

		if ($format == 'MEDIA') {
			$item->title .= " by ".$images->images[$i]->realname;
			//$item->content = str_replace('s0.','s0cdn.',$images->images[$i]->_getFullpath(true,true));
			if ($opt_expand) {
				$title=$images->images[$i]->grid_reference.' : '.htmlentities2($images->images[$i]->title).' by '.htmlentities2($images->images[$i]->realname);
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
		$title=$images->images[$i]->grid_reference.' : '.htmlentities2($images->images[$i]->title).' by '.htmlentities2($images->images[$i]->realname);
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
	foreach (array('text','q','location','BBOX','lat','lon','u','perpage','distance','orderby','groupby','new') as $k) {
		$t .= "|".(empty($_GET[$k])?'':$_GET[$k]);
	}
	return md5($t);
}

