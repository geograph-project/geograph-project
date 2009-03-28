<?php
/**
 * $Project: GeoGraph $
 * $Id: xmas.php 4028 2008-01-03 21:54:06Z barry $
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
require_once('geograph/gridsquare.class.php');
require_once('geograph/gridimage.class.php');
require_once('geograph/map.class.php');
require_once('geograph/mapmosaic.class.php');
require_once('geograph/image.inc.php');
init_session();

$smarty = new GeographPage;

$map=new GeographMap;
$map->setOrigin(0,-10);
$map->setImageSize(1200,1700);
$map->setScale(1.3);

$map->type_or_user = -60;
 
$target=$_SERVER['DOCUMENT_ROOT'].$map->getImageFilename();


$template='stuff_thumbnail.tpl';
$cacheid=$map->type_or_user * -1;

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*7*24; //7 day cache (as search can be cached - and we manually refreshed anyway


if (!empty($_GET['refresh']) && $USER->hasPerm("admin")) {
	unlink($target);
	$map->_renderMap();
	$smarty->clear_cache($template, $cacheid);
}

//regenerate?
if (!$smarty->is_cached($template, $cacheid)) {

	$imagemap = file_get_contents($target.".html");
	$smarty->assign_by_ref("imagemap",$imagemap);
	
	$smarty->assign_by_ref("map",$map);
	
	$smarty->assign("imageupdate",filemtime($target));
}


$smarty->display($template, $cacheid);

	
?>
