<?php
/**
 * $Project: GeoGraph $
 * $Id$
 *
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Paul Dixon (paul@elphin.com)
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

if (isset($_GET['id']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'http://geourl.org/bot')!==FALSE) ) {
	//die as quickly as possible with the minimum html (with the approval of geourl owner)
	$db = GeographDatabaseConnection(true);

	$row =& $db->getRow("select gridimage_id,wgs84_lat,wgs84_long,title,grid_reference from gridimage_search where gridimage_id=".intval($_GET['id']) );

	if ($row['wgs84_lat']) {
		$title = htmlentities($row['title']."::".$row['grid_reference']);

		print "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\"/>\n";
		print "<title>$title</title>\n";
		print "<meta name=\"ICBM\" content=\"{$row['wgs84_lat']}, {$row['wgs84_long']}\"/>\n";
		print "<meta name=\"DC.title\" content=\"Geograph::$title\"/>\n";
		print "<a href=\"http://{$_SERVER['HTTP_HOST']}/photo/{$row['gridimage_id']}\">View image page</a>";
	} elseif ($row['gridimage_id']) {
		header("HTTP/1.0 500 Server Error");
		header("Status: 500 Server Error");
		print "<title>Lat/Long not available, try again later</title>";
		print "<a href=\"http://{$_SERVER['HTTP_HOST']}/photo/{$row['gridimage_id']}\">View image page</a>";
	} else {
		header("HTTP/1.0 404 Not Found");
		header("Status: 404 Not Found");
		print "<title>Image no longer available</title>";
	}
	exit;
} elseif ((strpos($_SERVER["REQUEST_URI"],'/photo/') === FALSE && isset($_GET['id'])) || strlen($_GET['id']) !== strlen(intval($_GET['id']))) {
	//keep urls nice and clean - esp. for search engines!
	header("HTTP/1.0 301 Moved Permanently");
	header("Status: 301 Moved Permanently");
	header("Location: /photo/".intval($_GET['id']));
	print "<a href=\"http://{$_SERVER['HTTP_HOST']}/photo/".intval($_GET['id'])."\">View image page</a>";
	exit;
}


require_once('geograph/gridimage.class.php');
require_once('geograph/gridsquare.class.php');
require_once('geograph/mapmosaic.class.php');
require_once('geograph/rastermap.class.php');

init_session();

if (isset($_GET['style'])) {
	$USER->getStyle();
	if (isset($_GET['id'])) {
		$_SESSION['setstyle'] = 1;
		header("HTTP/1.0 301 Moved Permanently");
		header("Status: 301 Moved Permanently");
		header("Location: /photo/".intval($_GET['id']));
		exit;
	}
	header("Location: /");
	exit;
}

customGZipHandlerStart();

$smarty = new GeographPage;

if ($CONF['template']=='archive') {
	dieUnderHighLoad(1.5);
}

$template='view.tpl';

$cacheid=0;

if ($smarty->caching) {
	$smarty->caching = 2; // lifetime is per cache
	$smarty->cache_lifetime = 3600*3; //3hour cache
}

$image=new GridImage;

if (isset($_GET['id']))
{
	$image->loadFromId(intval($_GET['id']));
	
	if (!empty($CONF['use_insertionqueue']) && isset($image->unavailable)) {
	
		header("HTTP/1.1 503 Service Unavailable");
		$smarty->display("image_notready.tpl");
	
		exit;
	}
	
	
	$isowner=($image->user_id==$USER->user_id)?1:0;
	$ismoderator=$USER->hasPerm('moderator')?1:0;

	$ab=floor($_GET['id']/10000);

	$cacheid="img$ab|{$_GET['id']}|{$isowner}_{$ismoderator}";

	if (isset($_GET['expand'])) {
		$cacheid .= "E";
		$smarty->assign('expand',1);
		$CONF['global_thumb_limit'] = 4;
	}

	//is the image rejected? - only the owner and administrator should see it
	if ($image->moderation_status=='rejected')
	{
		if ($isowner||$ismoderator)
		{
			//ok, we'll let it lie...
		}
		else
		{
			$db = GeographDatabaseConnection(true);			
			if ($to = $db->getOne("SELECT destination FROM gridimage_redirect WHERE gridimage_id = ".intval($_GET['id']))) {
		                header("HTTP/1.0 301 Moved Permanently");
                		header("Status: 301 Moved Permanently");
		                header("Location: /photo/".intval($to));
                		exit;
			}

			//clear the image
			$image=new GridImage;
			$cacheid=0;
			$rejected = true;
		}
	}
}

//do we have a valid image?
if ($image->isValid())
{
	if ($image->grid_square->reference_index == 1 
		&& $_SERVER['HTTP_HOST'] == 'www.geograph.ie' &&  
			((stripos($_SERVER['HTTP_USER_AGENT'], 'http')!==FALSE) ||
			(stripos($_SERVER['HTTP_USER_AGENT'], 'bot')!==FALSE)) ) {
		header("HTTP/1.0 301 Moved Permanently");
		header("Status: 301 Moved Permanently");
		header("Location: http://www.geograph.org.uk/photo/".intval($_GET['id']));
		exit;
	} elseif ($image->grid_square->reference_index == 2 && $_SERVER['HTTP_HOST'] != 'www.geograph.ie' && $CONF['template']!='archive') {
		$smarty->assign("ireland_prompt",1);
	}

	//what style should we use?
	$style = $USER->getStyle();
	
	//when this image was modified
	$mtime = strtotime($image->upd_timestamp);

	//page is unqiue per user (the profile and links)
	$hash = $cacheid.'.'.$USER->user_id;

	//if they have just just changed the style dont allow sending a 304 :) (of course can still exploit the smarty cache)
	if (!empty($_SESSION['setstyle'])) {
		unset($_SESSION['setstyle']);
	} else {
		//can't use IF_MODIFIED_SINCE for logged in users as has no concept as uniqueness
		customCacheControl($mtime,$hash,($USER->user_id == 0));
	}


	if ( (stripos($_SERVER['HTTP_USER_AGENT'], 'http')===FALSE) &&
	    (stripos($_SERVER['HTTP_USER_AGENT'], 'bot')===FALSE) &&
	    (strpos($_SERVER['HTTP_USER_AGENT'], 'Web Preview')===FALSE) && 
        (stripos($_SERVER['HTTP_USER_AGENT'], 'Magnus')===FALSE) &&
	    empty($_SESSION['photos'][$image->gridimage_id]) &&
	    $CONF['template']!='archive')
	{
		if (empty($db) || $db->readonly) 
			$db = GeographDatabaseConnection(false);
		
		$db->Query("INSERT LOW_PRIORITY INTO gridimage_log VALUES({$image->gridimage_id},1,0,now()) ON duplicate KEY UPDATE hits=hits+1");
		@$_SESSION['photos'][$image->gridimage_id]++;
	} else {
		$smarty->assign('is_bot',true);
	}

	$ref = @parse_url($_SERVER['HTTP_REFERER']);
	if (!empty($ref['query'])) {
		$ref_query = array();
		parse_str($ref['query'], $ref_query);
		
		if (strpos($ref['host'],'images.google.') === 0 && !empty($ref_query['prev'])) {
			$ref = @parse_url('http://'.$ref['host'].urldecode($ref_query['prev']));
			parse_str($ref['query'], $ref_query);
		}
	}

	if (!empty($CONF['sphinx_host']) 
		&& count($ref_query) > 0
		&& ( $intersect = array_intersect(array('q','query','qry','search','su','searchfor','s','qs','p','key','buscar','w'),array_keys($ref_query)) )
		&& ( $key = @array_shift($intersect) )
		&& !is_numeric($ref_query[$key])
		&& ($q = trim(preg_replace('/\b(geograph|photo|image|picture|site:[\w\.-]+|inurl:[\w\.-]+)s?\b/','',$ref_query[$key] )) )
		&& strlen($q) > 3 ) {
		
		if ($m[1] == 'prev' && preg_match('/\b(q|query|qry)=([\w%\+\.\(\)\"\':]+)(\&|$)/',$q,$m)) {
			$q = trim(urldecode($m[2]));
		}
		
		$smarty->assign("search_keywords",$q);
		
		$mkey = $image->grid_reference.' '.$q;
		$info =& $memcache->name_get('sn',$mkey);
		
		if (!empty($info)) {
			list($count,$when) = $info;
			
			$smarty->assign("search_count",$count);
		} else {
			$sphinx = new sphinxwrapper($mkey);
			
			$sphinx->processQuery();

			$count = $sphinx->countMatches('_images');
			
			$smarty->assign("search_count",$count);
			
			//fails quickly if not using memcached!
			$info = array($count,time());
			$memcache->name_set('sn',$mkey,$info,$memcache->compress,$memcache->period_med);
			
		}
	}

	$smarty->assign('maincontentclass', 'content_photo'.$style);

	if (!$smarty->is_cached($template, $cacheid))
	{
		if ($CONF['template']!='archive') {
			if (empty($db)) {
				$db = GeographDatabaseConnection(true);
			}

			$image->hits = $db->getOne("SELECT hits+hits_archive FROM gridimage_log WHERE gridimage_id = {$image->gridimage_id}");
		}

		$image->assignToSmarty($smarty);
		
		$image->loadSnippets();
		$image->loadCollections();
	} else {
		$smarty->assign_by_ref("image",$image); //we dont need the full assignToSmarty
	}

	$buckets = array('Closeup',
	'Arty',
	'Informative',
	'Aerial',
	'Telephoto',
	'Landscape',
	'Wideangle',
	'Indoor',
	'Gone',
	'People',
	'Temporary',
	'Life',
	'Subterranean', 
	'Transport');
	$smarty->assign_by_ref('buckets',$buckets);

} elseif (!empty($rejected)) {
	header("HTTP/1.0 410 Gone");
	header("Status: 410 Gone");
} else {
	header("HTTP/1.0 404 Not Found");
	header("Status: 404 Not Found");
}

function smarty_function_hidekeywords($input) {
	if (preg_match('/(^|[\n\r\s]+)Keywords?[\s:]([^\n\r>]+)$/i',$input,$m)) {
		if (empty($GLOBALS['image']->keywords)) {
			$GLOBALS['image']->keywords = array();
		}
		$GLOBALS['image']->keywords[] = $m[2];
		return preg_replace('/([\r\n]*<br \/>)+$/','',preg_replace('/(^|[\n\r\s]+)Keywords?[\s:][^\n\r>]+$/i','',$input));
	} else {
		return $input;
	}
}
$smarty->register_modifier("hidekeywords", "smarty_function_hidekeywords");

$smarty->display($template, $cacheid);


