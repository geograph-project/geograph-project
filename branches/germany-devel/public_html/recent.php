<?php
/**
 * $Project: GeoGraph $
 * $Id: recent.php 3905 2007-11-07 19:20:11Z geograph $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2007 Barry Hunter (geo@barryhunter.co.uk)
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
require_once('geograph/imagelist.class.php');
init_session();

if (!empty($_GET['style'])) {
	$USER->getStyle();
	if (!empty($_SERVER['QUERY_STRING'])) {
		$query = preg_replace('/style=(\w+)/','',$_SERVER['QUERY_STRING']);
		header("HTTP/1.0 301 Moved Permanently");
		header("Status: 301 Moved Permanently");
		header("Location: /search.php?".$query);
		exit;
	}
	header("Location: /recent.php");
	exit;
}

$smarty = new GeographPage;

customGZipHandlerStart();

$template='recent.tpl';
$cacheid=rand(1,5); //so we get a selection of homepages

//what style should we use?
$style = $USER->getStyle();
$cacheid.=$style;


if ($smarty->caching) {
	$smarty->caching = 2; // lifetime is per cache
	$smarty->cache_lifetime = 600; //10min cache
}

//regenerate?
if (!$smarty->is_cached($template, $cacheid))
{
	$smarty->assign('maincontentclass', 'content_photo'.$style);
	
	//lets find some recent photos
	new RecentImageList($smarty);
}


$smarty->display($template, $cacheid);

	
?>
