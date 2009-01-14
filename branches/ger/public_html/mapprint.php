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
require_once('geograph/gridsquare.class.php');
require_once('geograph/gridimage.class.php');
require_once('geograph/map.class.php');
require_once('geograph/mapmosaic.class.php');

init_session();
if (isset($_GET['inner'])) {
	$template='mapprint_inner.tpl';
} else {
	$template='mapprint.tpl';
}

$smarty = new GeographPage;


//initialise mosaic
$mosaic=new GeographMapMosaic;


if (isset($_GET['t']))
	$mosaic->setToken($_GET['t']);

//get token, we'll use it as a cache id
$token=$mosaic->getToken();

//regenerate html?
$cacheid='mapprint|'.$token;

$smarty->cache_lifetime = 3600*24; //24hr cache

//regenerate?
if (!$smarty->is_cached($template, $cacheid))
{
	$overview=new GeographMapMosaic('overview');

	if ($mosaic->type_or_user == -1) {
		$smarty->assign('depth', 1);
		$overview->type_or_user = -1;
	}

	//assign overview to smarty
	if ($mosaic->type_or_user > 0) {
		$overview->type_or_user = $mosaic->type_or_user;
		$profile=new GeographUser($mosaic->type_or_user);
		$smarty->assign('realname', $profile->realname);
		$smarty->assign('user_id', $mosaic->type_or_user);
	}
	
	if ($mosaic->pixels_per_km >= 40) { 
		//largeoverview
		$overview->setScale(1);
		list ($x,$y) = $mosaic->getCentre();
		$overview->setCentre($x,$y); //does call setAlignedOrigin
	}
	$overview->assignToSmarty($smarty, 'overview');
	$smarty->assign('marker', $overview->getBoundingBox($mosaic));
	
	//assign main map to smarty

	$mosaic->assignToSmarty($smarty, 'mosaic');
		
	$smarty->assign('gridref', $mosaic->getGridRef(-1,-1));
	$smarty->assign('mapwidth', round($mosaic->image_w /$mosaic->pixels_per_km ) );

	//we need this to know if the map can be zoomed out
	$smarty->assign('token_zoomout', $mosaic->getZoomOutToken());
	
	
}

$smarty->display($template, $cacheid);

	
?>
