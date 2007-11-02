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

init_session();

$smarty = new GeographPage;

customGZipHandlerStart();

//initialise map
$map=new GeographMap;

if (isset($_GET['t'])) {
	$ok = $map->setToken($_GET['t']);
	if (!$ok)
		die("Invalid Token");
} else {
	die("Missing Token");
}


if ($map->pixels_per_km != 40 && $map->pixels_per_km != 4)
	die("Invalid Parameter");

if (isset($_GET['mine']) && $USER->hasPerm("basic")) {
	$map->type_or_user = $USER->user_id;
} elseif (isset($_GET['u'])) {
	if (!empty($_GET['u'])) {
		$map->type_or_user = max(0,intval($_GET['u']));
	} else {
		$map->type_or_user = 0;
	}
} elseif (isset($_GET['depth'])) {
	if ($_GET['depth']) {
	
		$smarty->assign('depth', 1);
		$map->type_or_user = -1;
	} else {
		$map->type_or_user = 0;
	}
} 

$template=($map->pixels_per_km == 4)?(($map->type_or_user == -1)?'mapsheet100kdepth.tpl':'mapsheet100k.tpl'):'mapsheet.tpl';


//get token, we'll use it as a cache id
$token=$map->getToken();


//regenerate html?

$cacheid='mapsheet|'.$token;

if ($map->pixels_per_km == 4)
	$smarty->cache_lifetime = 3600*24; //24hr cache

if (isset($_GET['gridref_from']) && preg_match('/^[a-zA-Z]{1,2}\d{4}$/',$_GET['gridref_from'])) {
	$smarty->assign('gridref_from', $_GET['gridref_from']);
	$cacheid.='.'.$_GET['gridref_from'];
}

//regenerate?
if (!$smarty->is_cached($template, $cacheid))
{
	dieUnderHighLoad();
	
	if ($map->type_or_user > 0) {
		$profile=new GeographUser($map->type_or_user);
		$smarty->assign('realname', $profile->realname);
		$smarty->assign('user_id', $map->type_or_user);
	}
	
	//assign main map to smarty
	$smarty->assign_by_ref('map', $map);
	
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$grid=&$map->getGridArray();
	$smarty->assign_by_ref('grid', $grid);
	
	$first = current($grid);
	$second = current($first);
	$ri = $second['reference_index'];
	
	$letterlength = 3 - $ri; #todo should this be auto-realised by selecting a item from gridprefix? (or a grid_reference)

	$smarty->assign('ofe', $letterlength + 1);
	$smarty->assign('ofn', $letterlength + 3);
	
	//assign all the other useful stuff
	$gr = $map->getGridRef(-1,-1);
	$smarty->assign('gridref', $gr);
	
	if ($map->pixels_per_km == 4) {
		$starte = substr($gr,$letterlength,2);
		$starte = intval($starte) - 50;
		if ($starte < 0)
			$starte += 100;
		$startn = substr($gr,$letterlength+2,2);
		$startn = intval($startn) + 50;
		if ($startn > 100)
			$startn -= 100;
		$smarty->assign('starte', $starte);
		$smarty->assign('startn', $startn-1);
	} else {
		$mosaic = new GeographMapMosaic;
		$mosaic->setToken($_GET['t'],true);	
		$smarty->assign('mosaic_token', $mosaic->getToken());
		$smarty->assign('token_north', $map->getPanToken(0, 1));
		$smarty->assign('token_south', $map->getPanToken(0, -1));
		$smarty->assign('token_west', $map->getPanToken(-1, 0));
		$smarty->assign('token_east', $map->getPanToken(1, 0));
	
	}
}


$smarty->display($template, $cacheid);

	
?>
