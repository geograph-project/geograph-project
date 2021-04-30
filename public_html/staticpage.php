<?php
/**
 * $Project: GeoGraph $
 * $Id: staticpage.php 8514 2017-08-13 16:10:49Z barry $
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

if (strpos($_SERVER['REQUEST_URI'],'apple-touch-icon') !== FALSE) {
	//there are a lot of these, no point rendering the whole HTML page
	header("HTTP/1.0 404 Not Found");
	header("Status: 404 Not Found");
	exit;
}

require_once('geograph/global.inc.php');

//get page from request
$page=isset($_GET['page'])?$_GET['page']:'404';


$seconds = ($page == 'sitemap')?1800:(3600*3);

//init_session();
init_session_or_cache($seconds, 900); //cache publically, and privately


//do we trust it? like hell we do! alphanumerics only please!
if (!preg_match('/^[a-z0-9_]+$/' , $page))
{
	$page='404';
}

//next, we want to be sure you can only view pages intended for static viewing
$template='static_'.$page.'.tpl';

//lets be sure it exists...
$smarty = new GeographPage;




if (!$smarty->templateExists($template) || $page=='404') {
	$template='static_404.tpl';
} else {
	pageMustBeHTTPS();
}

customGZipHandlerStart();

$mtime = $smarty->templateDate($template);

if ($mtime) {
	//page is unqiue per user (the profile and links)
	$hash = $USER->user_id;

	//can't use IF_MODIFIED_SINCE for logged in users as has no concept as uniqueness
	customCacheControl($mtime,$hash,($USER->user_id == 0));
}


$smarty->assign("api_host",$CONF['API_HOST']);
if (!empty($CONF['google_maps_api3_key']))
	$smarty->assign('google_maps_api3_key',$CONF['google_maps_api3_key']);


if ($template == 'static_terms.tpl' && isset($_SERVER['HTTP_REFERER']) && preg_match('/\/photo\/(\d+)/',$_SERVER['HTTP_REFERER'],$m)) {

	 $smarty->assign('gridimage_id',intval($m[1]));

} elseif ($template == 'static_sitemap.tpl' && !$smarty->is_cached($template)) {

	$remote = file_get_contents("http://www.geograph.org/links/sitemap2.php?ajax&experimental=N&internal=Y&depreciated=N&site=www.geograph.org.uk");

	if (empty($remote) || strlen($remote) < 512) {
		if ($memcache->valid) {
			$mkey = $_SERVER['HTTP_HOST'];
			$remote = $memcache->name_get('links',$mkey);
		}
	}

	if ($memcache->valid) {
		$mkey = $_SERVER['HTTP_HOST'];
		$memcache->name_set('links',$mkey,$remote,$memcache->compress,$memcache->period_long*2);
	}

        $remote = str_replace('"?ajax=&amp;','"http://www.geograph.org/links/sitemap.php?',$remote);
        $remote = str_replace('"/links/sitemap2.php','"http://www.geograph.org/links/sitemap.php',$remote);

        if (preg_match('/(<h4 class="title">5 Newest Links<\/h4>.*?)<h4 class="title">List of all links by Category<\/h4>/s',$remote,$m)) {
                $smarty->assign('newlinks',str_replace('</a>','</a><br>',$m[1]));
                $remote = str_replace($m[0],'',$remote);
        }

	if ($_SERVER['HTTP_HOST'] == 'staging.geograph.org.uk') {
		$remote = str_replace("www.geograph.org.uk",'staging.geograph.org.uk',$remote);
	}


	$smarty->assign('content',$remote);
}

$smarty->assign('hid', dechex($_SERVER['REQUEST_TIME']));

$smarty->display($template);

