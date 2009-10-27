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
require_once('geograph/gridsquare.class.php');
require_once('geograph/gridimage.class.php');
require_once('geograph/map.class.php');
require_once('geograph/mapmosaic.class.php');

if (isset($_GET['map']))
{
	require_once('geograph/image.inc.php');
					
	//render and return a map with token $_GET['map'];
	$map=new GeographMap;
	if (isset($_GET['refresh']) && $_GET['refresh'] == 2 && (init_session() || true) && $USER->hasPerm('admin'))
		$map->caching=false;
	$map->setToken($_GET['map']);
	$map->returnImage();
	exit;
	
}


init_session();
$template='mapbrowse.tpl';

$smarty = new GeographPage;

customGZipHandlerStart();

//initialise mosaic
$mosaic=new GeographMapMosaic;
$overview=new GeographMapMosaic('overview');

if (isset($_GET['o']))
	$overview->setToken($_GET['o']);
	
if (isset($_GET['t'])) {
	$mosaic->setToken($_GET['t']);
} else {
	if ($overview->type_or_user) {
		$mosaic->type_or_user = $overview->type_or_user;
	}
}

if (preg_match('/\?([0-9]+),([0-9]+)$/',$_SERVER['QUERY_STRING'],$matchs)) {
	$_GET['x']=$matchs[1];
	$_GET['y']=$matchs[2];
}

if (isset($_GET['mine']) && $USER->hasPerm("basic")) {
	$mosaic->type_or_user = $USER->user_id;
} elseif (isset($_GET['user']) && isValidRealName($_GET['user'])) {
	if ($_GET['user'] == $USER->nickname) {
		$uid=$USER->user_id;
	} else {
		$profile=new GeographUser();
		$profile->loadByNickname($_GET['user']);
		$uid=$profile->user_id;
	}
	if ($uid) {
		$mosaic->type_or_user = $uid;
	}
} elseif (isset($_GET['u'])) {
	if (!empty($_GET['u'])) {
		$mosaic->type_or_user = max(0,intval($_GET['u']));
	} else {
		$mosaic->type_or_user = 0;
	}
} elseif (isset($_GET['depth'])) {
	if ($_GET['depth']) {
		$smarty->assign('depth', 1);
		$mosaic->type_or_user = -1;
		$overview->type_or_user = -1;
	} else {
		$mosaic->type_or_user = 0;
		$overview->type_or_user = 0;
	}
} elseif ($mosaic->type_or_user == -1) {
	$smarty->assign('depth', 1);
	$overview->type_or_user = -1;
}


//are we zooming in on an image map? we'll have a url like this
//i and j give the index of the mosaic image
//http://geograph.elphin/mapbrowse.php?t=token&i=0&j=0&zoomin=?275,199
if (isset($_GET['zoomin']))
{
	//get click coordinate, or use centre point if not supplied

	$x=isset($_GET['x'])?intval($_GET['x']):round(($mosaic->image_w/$mosaic->mosaic_factor)/2);
	$y=isset($_GET['y'])?intval($_GET['y']):round(($mosaic->image_h/$mosaic->mosaic_factor)/2);

	
	//get the image index
	$i=intval($_GET['i']);
	$j=intval($_GET['j']);
	
	//handle the zoom
	$mosaic->zoomIn($i, $j, $x, $y);	
}

if (isset($_GET['center']))
{
	//extract x and y click coordinate from imagemap
	$x=isset($_GET['x'])?intval($_GET['x']):round(($overview->image_w/$mosaic->mosaic_factor)/2);
	$y=isset($_GET['y'])?intval($_GET['y']):round(($overview->image_h/$mosaic->mosaic_factor)/2);
	

	//get the image index
	$i=intval($_GET['i']);
	$j=intval($_GET['j']);
	
	//get click coordinate on overview, use it to centre the main map
	list($intx, $inty)=$overview->getClickCoordinates($i, $j, $x, $y);	
	
	$zoomindex = array_search($overview->pixels_per_km,$overview->scales);
	$scale = $overview->scales[$zoomindex+1];
	$mosaic->setScale($scale);
	$mosaic->setMosaicFactor(2);
	$mosaic->setCentre($intx, $inty);	
	
}

if (isset($_GET['recenter']))
{
	//extract x and y click coordinate from imagemap
	$x=isset($_GET['x'])?intval($_GET['x']):round(($overview->image_w/$mosaic->mosaic_factor)/2);
	$y=isset($_GET['y'])?intval($_GET['y']):round(($overview->image_h/$mosaic->mosaic_factor)/2);
	
	
	//get the image index
	$i=intval($_GET['i']);
	$j=intval($_GET['j']);
	
	//get click coordinate on overview, use it to centre the main map
	list($intx, $inty)=$overview->getClickCoordinates($i, $j, $x, $y);	
	$mosaic->setCentre($intx, $inty);	
	
}

if (isset($_GET['gridref']) && preg_match('/^[a-zA-Z]{1,3}\d{4}$/',$_GET['gridref'])) {
	$gridsquare=new GridSquare;
	$grid_ok=$gridsquare->setByFullGridRef($_GET['gridref'],false,true);
	$gridref_param=$_GET['gridref'];
	if ($grid_ok)
		$mosaic->setCentre($gridsquare->x,$gridsquare->y,/*true*/false, true);
} else {
	$gridref_param='';
	$grid_ok=false;
}

if ($mosaic->pixels_per_km > 40) {
	$mosaic->pixels_per_km = 40;
	$mosaic->image_w /= 2;
	$mosaic->image_h /= 2;
}

//get token, we'll use it as a cache id
$token=$mosaic->getToken();


//regenerate html?
$cacheid='mapbrowse|'.$token;
if (!empty($gridref_param) && !$gridref_ok) {
	$cacheid.='|'.$gridref_param;
}

$smarty->cache_lifetime = 3600*24; //24hr cache

if (isset($_GET['gridref_from']) && preg_match('/^[a-zA-Z]{1,3}\d{4}$/',$_GET['gridref_from'])) {
	$smarty->assign('gridref_from', $_GET['gridref_from']);
}
$smarty->assign('gridref_param', $gridref_param);
$smarty->assign('gridref_ok', $grid_ok);

//regenerate?
if (!$smarty->is_cached($template, $cacheid))
{
	$overview->setPreset('overview');
	
	//assign overview to smarty
	if ($mosaic->type_or_user > 0) {
		$profile=new GeographUser($mosaic->type_or_user);
		
		if (count($profile->stats) == 0) {
			$profile->getStats();
		}
		
		//the map is only useful for people with images!
		if ( !empty($profile->stats['images']) ) {
			$smarty->assign('realname', $profile->realname);
			$smarty->assign('nickname', $profile->nickname);
			$smarty->assign('user_id', $mosaic->type_or_user);
		
			$overview->type_or_user = $mosaic->type_or_user;
		} else {
			$mosaic->type_or_user = 0;
		}
	}
	
	if ($mosaic->pixels_per_km == 40) { 
		//largeoverview
		$overview->setScale(1);
		list ($x,$y) = $mosaic->getCentre();
		$overview->setCentre($x,$y); //does call setAlignedOrigin
		
		#$mosaic->fillGridMap(true); //true = for imagemap
		
	} else {
		//set it back incase we come from a largeoverview
		$overview->setScale(0.13);
		$overview->setOrigin(0,-10);		
	}
	
	
	$overview->assignToSmarty($smarty, 'overview');
	$smarty->assign('marker', $overview->getBoundingBox($mosaic));
	
	
	//assign all the other useful stuff
		
	if ($gridref = $mosaic->getGridRef(-1,-1)) {
		$smarty->assign('gridref', $gridref);
		if ($mosaic->pixels_per_km == 40 && preg_match('/([A-Z]+\d)5(\d)5$/',$gridref,$m)) {
			$smarty->assign('hectad', $hectad = $m[1].$m[2]);
			$db=NewADOConnection($GLOBALS['DSN']);
			$smarty->assign_by_ref('hectad_row',$db->getRow("select * from hectad_complete where hectad_ref = '$hectad' limit 1"));
		}
	}
	
	//assign main map to smarty
	
	$mosaic->assignToSmarty($smarty, 'mosaic');
	
	
	$smarty->assign('mapwidth', round($mosaic->image_w /$mosaic->pixels_per_km ) );
	
	$smarty->assign('token_zoomin', $mosaic->getZoomInToken());
	$smarty->assign('token_zoomout', $mosaic->getZoomOutToken());
	$smarty->assign('token_north', $mosaic->getPanToken(0, 1));
	$smarty->assign('token_south', $mosaic->getPanToken(0, -1));
	$smarty->assign('token_west', $mosaic->getPanToken(-1, 0));
	$smarty->assign('token_east', $mosaic->getPanToken(1, 0));
	
	/*$square=new GridSquare;
	$smarty->assign('prefixes', $square->getGridPrefixes());
	$smarty->assign('kmlist', $square->getKMList());*/
	
			
	//no big unless you are zoomed in
	if ($mosaic->pixels_per_km >=4)
	{
	#	$smarty->assign('token_big', $mosaic->getBigToken());
	}
} else {
	$smarty->assign('mosaic_token', $mosaic->getToken());
}

$smarty->display($template, $cacheid);

	
?>
