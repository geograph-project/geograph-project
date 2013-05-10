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
	$db = NewADOConnection($GLOBALS['DSN']);

	$row =& $db->getRow("select gridimage_id,wgs84_lat,wgs84_long,title,title2,grid_reference from gridimage_search where gridimage_id=".intval($_GET['id']) );

	if ($row['wgs84_lat']) {
		$title = combineTexts($row['title'], $row['title2']);
		$title = htmlentities($title."::".$row['grid_reference']);

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
} elseif (isset($_GET['id']) && (strpos($_SERVER["REQUEST_URI"],'/photo/') === FALSE || strlen($_GET['id']) !== strlen(intval($_GET['id'])))) {
	//keep urls nice and clean - esp. for search engines!
	header("HTTP/1.0 301 Moved Permanently");
	header("Status: 301 Moved Permanently");
	header("Location: /photo/".intval($_GET['id']));
	print "<a href=\"http://{$_SERVER['HTTP_HOST']}/photo/".intval($_GET['id'])."\">View image page</a>";
	exit;
} elseif (!isset($_GET['id']) && isset($_GET['searchid']) && (strpos($_SERVER["REQUEST_URI"],'/browse/') === FALSE || strlen($_GET['searchid']) !== strlen(intval($_GET['searchid'])) || strlen($_GET['searchidx']) !== strlen(intval($_GET['searchidx'])) )) {
	header("HTTP/1.0 301 Moved Permanently");
	header("Status: 301 Moved Permanently");
	header("Location: /results/browse/".intval($_GET['searchid'])."/".intval($_GET['searchidx']));
	print "<a href=\"http://{$_SERVER['HTTP_HOST']}/results/browse/".intval($_GET['searchid'])."/".intval($_GET['searchidx'])."\">View image page</a>";
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

$template='view.tpl';

$cacheid=0;

if ($smarty->caching) {
	$smarty->caching = 2; // lifetime is per cache
	$smarty->cache_lifetime = 3600*3; //3hour cache
}

if (isset($_GET['searchid']) && preg_match('/^\s*[1-9][0-9]*\s*$/', $_GET['searchid'])) {
	$searchid = intval($_GET['searchid']);
	if (isset($_GET['searchidx']) && preg_match('/^\s*[0-9]+\s*$/', $_GET['searchidx'])) {
		$searchidx = intval($_GET['searchidx']);
	} else {
		$searchidx = 0;
	}
} else {
	$searchid = 0;
	$searchidx = 0;
}
if ($searchid) {
	$haveimgid = isset($_GET['id']) && $_GET['id'] !== '0';
	if ($_SESSION['cursearch_id'] == $searchid && ($haveimgid || $_SESSION['cursearch_minidx'] <= $searchidx && $searchidx < $_SESSION['cursearch_maxidx'])) { // FIXME expire result?
		if (!$haveimgid) {
			$_GET['id'] = $_SESSION['cursearch_imageids'][$searchidx - $_SESSION['cursearch_minidx']];
		}
		$pgsize = $_SESSION['cursearch_pgsize'];
		$pg = floor($searchidx / $pgsize) + 1;
	} else {
		require_once('geograph/searchcriteria.class.php');
		require_once('geograph/searchengine.class.php');
		$engine = new SearchEngine($searchid);
		if (empty($engine->criteria)) {
			$searchid = 0;
			$searchidx = 0;
		} else {
			$pgsize = $engine->criteria->resultsperpage;
			if (!$pgsize) {
				$pgsize = 15;
			}
			$pg = floor($searchidx / $pgsize) + 1;
			if (!$haveimgid) {
				$residx = $searchidx % $pgsize;
				$engine->Execute($pg);
				if (count($engine->results) <= $residx) {  /* not found => go to first image */
					$searchidx = 0;
					$residx = 0;
					$pg = 1;
					$engine->Execute(1);
				}
				if (count($engine->results) > $residx) {
					$_GET['id'] = $engine->results[$residx]->gridimage_id;
					$_SESSION['cursearch_id'] = $searchid;
					$_SESSION['cursearch_pgsize'] = $pgsize;
					$_SESSION['cursearch_minidx'] = ($pg - 1) * $pgsize;
					$_SESSION['cursearch_maxidx'] = $_SESSION['cursearch_minidx'] + count($engine->results);
					$_SESSION['cursearch_imageids'] = array();
					foreach ($engine->results as &$resimage) {
						$_SESSION['cursearch_imageids'][] = $resimage->gridimage_id;
					}
					unset ($resimage);
				}
			}
		}
	}
	/*if (isset($_GET['id'])) {
		header("HTTP/1.0 301 Moved Permanently");
		header("Status: 301 Moved Permanently");
		header("Location: /photo/".intval($_GET['id'])."?searchid=$searchid&searchidx=$searchidx");
		exit;
	} else {
		header("Location: /");
		exit;
	}*/
	if (isset($_GET['id'])) {
		$smarty->assign('canonicalreq', '/photo/'.$_GET['id']);
	}
}

$image=new GridImage;

if (isset($_GET['id']))
{
	$image->loadFromId(intval($_GET['id']));
	$isowner=($image->user_id==$USER->user_id)?1:0;
	$ismoderator=$USER->hasPerm('moderator')?1:0;
	$isregistered=$USER->registered?1:0;

	$ab=floor($_GET['id']/10000);

	$cacheid="img$ab|{$_GET['id']}|{$isowner}_{$ismoderator}_{$isregistered}";

	//is the image accepted? - otherwise, only the owner and administrator should see it
	if (!$isowner&&!$ismoderator) {
		if ($image->moderation_status=='rejected') {
			//clear the image
			$image=new GridImage;
			$cacheid=0;
			$rejected = true;
		} elseif ($image->moderation_status=='pending') {
			//clear the image
			$image=new GridImage;
			$cacheid=0;
			$pending = true;
		}
	}
}

//do we have a valid image?
if ($image->isValid())
{
	//what style should we use?
	$style = $USER->getStyle();
	$cacheid.=$style;

	$smarty->assign("searchid", $searchid);
	$smarty->assign("searchidx", $searchidx);
	if ($searchid) {
		$smarty->assign("searchpg", $pg);
	}

	//when this image was modified
	$mtime = strtotime($image->upd_timestamp);
	#$image->loadFromId(intval($_GET['id']));
	#$isowner=($image->user_id==$USER->user_id)?1:0;
	#trigger_error("sids: " . implode(', ', array_keys($image->grid_square->services)), E_USER_NOTICE);
	
	if (isset($_GET['sid']) && isset($image->grid_square->services[intval($_GET['sid'])])) {
		$sid = intval($_GET['sid']);
		#trigger_error("sid: g: " . $sid, E_USER_NOTICE);
	} elseif (count($image->grid_square->services) != 0) {
		$sids = array_keys($image->grid_square->services);
		$sid = $sids[0];
		#trigger_error("sid: s: " . $sid, E_USER_NOTICE);
	} else {
		$sid = -1;
		#trigger_error("sid: x: " . $sid, E_USER_NOTICE);
	}
	$cacheid.="_s:$sid";

	$map_suffix = get_map_suffix();
	$cacheid .= $map_suffix;

	//page is unqiue per user (the profile and links)
	$hash = $cacheid.'.'.$USER->user_id;

	//can't use IF_MODIFIED_SINCE for logged in users as has no concept as uniqueness
	customCacheControl($mtime,$hash,($USER->user_id == 0));

	if (!empty($CONF['sphinx_host']) 
		&& stripos($_SERVER['HTTP_REFERER'],$CONF['CONTENT_HOST']) === FALSE 
		&& stripos($_SERVER['HTTP_REFERER'],$_SERVER['HTTP_HOST']) === FALSE
		&& preg_match('/\b(q|query|qry|search|su|searchfor|s|qs|p|key|buscar|w)=([\w%\+\.\(\)\"\':]+)(\&|$)/',$_SERVER['HTTP_REFERER'],$m) 
		&& !is_numeric($m[2])
		&& ($q = trim(preg_replace('/\b(geograph|photo|image|picture|site:[\w\.-]+|inurl:[\w\.-]+)s?\b/','',urldecode($m[2]) )) )
		&& strlen($q) > 3 ) {
		
		$smarty->assign("search_keywords",$q);
		
		$mkey = $image->grid_reference.' '.$q;
		$info =& $memcache->name_get('sn',$mkey);
		
		if (!empty($info)) {
			list($count,$when) = $info;
			
			$smarty->assign("search_count",$count);
			
			$smarty->assign_by_ref("image",$image); //we dont need the full assignToSmarty
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
	if ($USER->registered) {
		$smarty->assign_by_ref('vote', $image->getVotes($USER->user_id));
		$smarty->assign('imageid', $image->gridimage_id);
	}

	if (!$smarty->is_cached($template, $cacheid))
	{
		$notes =& $image->getNotes(array('visible'));

		$smarty->assign('maincontentclass', 'content_photo'.$style);
		$smarty->assign("sid",$sid);
		$smarty->assign_by_ref("notes",$notes);

		$image->assignToSmarty($smarty, $sid, $map_suffix);
	}
} elseif (!empty($rejected)) {
	header("HTTP/1.0 410 Gone");
	header("Status: 410 Gone");
} elseif (!empty($pending)) {
	header("HTTP/1.0 403 Forbidden");
	header("Status: 403 Forbidden");
} else {
	header("HTTP/1.0 404 Not Found");
	header("Status: 404 Not Found");
}

function smarty_function_hidekeywords($input) {
	return preg_replace('/(^|[\n\r\s]+)(Keywords?[\s:][^\n\r>]+)$/','<span class="keywords">$2</span>',$input);
}
$smarty->register_modifier("hidekeywords", "smarty_function_hidekeywords");

$smarty->display($template, $cacheid);


?>
