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
$template='mapbrowse2.tpl';

$smarty = new GeographPage;

customGZipHandlerStart();

//initialise mosaic
if (!isset($_GET['mt']))
	$_GET['mt'] = 't';

#trigger_error("XXX", E_USER_NOTICE);
$mosaic=new GeographMapMosaic; // 'full'
#trigger_error("YYY", E_USER_NOTICE);
$overview=new GeographMapMosaic;
#trigger_error("ZZZ", E_USER_NOTICE);

if (isset($_GET['o']))
	if (!$overview->setToken($_GET['o']))
		unset($_GET['o']);
	
if (isset($_GET['t']))
	if (!$mosaic->setToken($_GET['t']))
		unset($_GET['t']);

if (isset($_GET['t'])) {
	if (!$mosaic->tilesize) {
		$_GET['mt'] = '';
	} elseif (!$mosaic->mercator) {
		$_GET['mt'] = 't';
	} else {
		$_GET['mt'] = 'tm';
	}
} elseif (isset($_GET['o'])) {
	$mosaic->type_or_user = $overview->type_or_user;
	if (!$overview->tilesize) {
		$_GET['mt'] = '';
	} elseif (!$overview->mercator) {
		$_GET['mt'] = 't';
	} else {
		$_GET['mt'] = 'tm';
	}
}
if (!isset($_GET['t'])) {
	if ($_GET['mt'] == 'tm') {
		$mosaic->setPreset('full_tm');
		#trigger_error("aaa", E_USER_NOTICE);
	} elseif($_GET['mt'] == 't') {
		$mosaic->setPreset('full_t');
		#trigger_error("AAA", E_USER_NOTICE);
	} else {
		$mosaic->setPreset('full');
		#trigger_error("AaA", E_USER_NOTICE);
	}
}
if (!isset($_GET['o'])) {
	if ($_GET['mt'] == 'tm') {
		$overview->setPreset('overview_tm');
		#trigger_error("bbb", E_USER_NOTICE);
	} elseif($_GET['mt'] == 't') {
		$overview->setPreset('overview_t');
		#trigger_error("BBB", E_USER_NOTICE);
	} else {
		$overview->setPreset('overview');
		#trigger_error("BbB", E_USER_NOTICE);
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
	//get the image index
	$i=intval($_GET['i']);
	$j=intval($_GET['j']);

	//get click coordinate, or use centre point if not supplied
	if (isset($_GET['x']) && isset($_GET['y'])) {
		$x = intval($_GET['x']);
		$y = intval($_GET['y']);
	} else {
		list($x, $y) = $mosaic->getTileCentre($i, $j);
	}

	//handle the zoom
	$mosaic->zoomIn($i, $j, $x, $y);	
}

if (isset($_GET['center']))
{
	//get the image index
	$i=intval($_GET['i']);
	$j=intval($_GET['j']);

	//extract x and y click coordinate from imagemap
	if (isset($_GET['x']) && isset($_GET['y'])) {
		$x = intval($_GET['x']);
		$y = intval($_GET['y']);
	} else {
		list($x, $y) = $overview->getTileCentre($i, $j);
	}

	//get click coordinate on overview, use it to centre the main map
	list($intx, $inty)=$overview->getClickCoordinates($i, $j, $x, $y);	
	
	if ($overview->mercator) {
		$level = min($overview->level + 1, count($mosaic->scales) - 1); #FIXME
		$scale = $mosaic->scales[$level];
	} else {
		$zoomindex = 1;
		foreach($mosaic->scales as $level => $pixperkm) {
			if ($pixperkm > $overview->pixels_per_km && $pixperkm > 1-.0001) {
				$zoomindex = $level;
				break;
			}
		}
		$scale = $mosaic->scales[$zoomindex];
	}
	$mosaic->recenter($intx, $inty, $scale, 2);
}

if (isset($_GET['recenter']))
{
	//get the image index
	$i=intval($_GET['i']);
	$j=intval($_GET['j']);

	//extract x and y click coordinate from imagemap
	if (isset($_GET['x']) && isset($_GET['y'])) {
		$x = intval($_GET['x']);
		$y = intval($_GET['y']);
	} else {
		list($x, $y) = $overview->getTileCentre($i, $j);
	}

	//get click coordinate on overview, use it to centre the main map
	list($intx, $inty)=$overview->getClickCoordinates($i, $j, $x, $y);
	#trigger_error("_____$intx $inty    $i,$j $x,$y", E_USER_NOTICE);
	$mosaic->recenter($intx, $inty);
}

if (isset($_GET['gridref']) && preg_match('/^[!a-zA-Z]{1,3}\d{4}$/',$_GET['gridref'])) {
	$gridsquare=new GridSquare;
	$grid_ok=$gridsquare->setByFullGridRef($_GET['gridref'],false,true);
	$gridref_param=$_GET['gridref'];
	if ($grid_ok) {
		$mosaic->recenter($gridsquare->x, $gridsquare->y, null, null, true, true);
	}
} else {
	$gridref_param='';
	$grid_ok=false;
}

#if ($mosaic->pixels_per_km > 40) {#FIXME?
#	$mosaic->pixels_per_km = 40;
#	$mosaic->image_w /= 2;
#	$mosaic->image_h /= 2;
#}

//get token, we'll use it as a cache id
$token=$mosaic->getToken();


//regenerate html?
$cacheid='mapbrowse2|'.$token;
if (!empty($gridref_param) && !$gridref_ok) {
	$cacheid.='|'.$gridref_param;
}

$smarty->cache_lifetime = 3600*24; //24hr cache

if (isset($_GET['gridref_from']) && preg_match('/^[!a-zA-Z]{1,3}\d{4}$/',$_GET['gridref_from'])) {
	$smarty->assign('gridref_from', $_GET['gridref_from']);
}
$smarty->assign('gridref_param', $gridref_param);
$smarty->assign('gridref_ok', $grid_ok);

//regenerate?
if (!$smarty->is_cached($template, $cacheid))
{
	if ($_GET['mt'] == 'tm') {
		$overview->setPreset('overview_tm');
		#trigger_error("ccc", E_USER_NOTICE);
	} elseif($_GET['mt'] == 't') {
		$overview->setPreset('overview_t');
		#trigger_error("CCC", E_USER_NOTICE);
	} else {
		$overview->setPreset('overview');
		#trigger_error("CcC", E_USER_NOTICE);
	}
	
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
	
	list ($x,$y) = $mosaic->getCentre();
	$overview->recenter($x, $y, $overview->scales[$mosaic->level]);
	#if (/*$overview->mercator && $mosaic->pixels_per_km >= 12 ||*/ !$overview->mercator && $mosaic->pixels_per_km == 40) { #FIXME
	#	//largeoverview
	#	$mosaic->fillGridMap(true); //true = for imagemap FIXME
	#}
	#{
	#	//set it back incase we come from a largeoverview
	#	#FIXME ?
	#	if ($overview->tilesize) {
	#		$overview->initTiles($overview->tilesize,0,-10,$overview->image_w,$overview->image_h,0.13);
	#		#$overview->initTiles($overview->tilesize,0,-10,$overview->image_w,$overview->image_h,0);
	#	} else {
	#		$overview->setScale(0.13);
	#		$overview->setOrigin(0,-10);		
	#	}
	#}
	
	
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
	
#trigger_error("xxx", E_USER_NOTICE);
	$mosaic->assignToSmarty($smarty, 'mosaic');
#trigger_error("yyy", E_USER_NOTICE);

	if ($mosaic->mercator) {
		$centrey = $mosaic->map_y + ($mosaic->image_h / $mosaic->pixels_per_unit)/2;
		$fac = M_PI / 262144.;
		$lat_rad = 2*atan(exp($centrey*$fac)) - M_PI/2; // see lev19_to_wgs84() [ConversionsLatLong]
		$r_km = 6378.137;
		$circ = 2*M_PI*$r_km*cos($lat_rad); # circumference of circle of latitude == 2^level tiles == tilesize*2^level pixels
		$smarty->assign('mapwidth', round($circ/$mosaic->tilesize/pow(2,$mosaic->pixels_per_km)*$mosaic->image_w));
	} else {
		$smarty->assign('mapwidth', round($mosaic->image_w / $mosaic->pixels_per_km));
	}

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
