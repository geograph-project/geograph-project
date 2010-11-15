<?php
/**
 * $Project: GeoGraph $
 * $Id: faq.php 15 2005-02-16 12:23:35Z lordelph $
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
require_once('geograph/mapmosaic.class.php');

init_session();

$mosaic=new GeographMapMosaic;

$mosaic->pixels_per_km = 40;

if (isset($_GET['random'])) {
	$db=NewADOConnection($GLOBALS['DSN']);
	
	$count = $db->cacheGetOne(86400,"SELECT COUNT(*) FROM gridsquare WHERE reference_index=1 AND percent_land = 100");
	
	$offset = rand(0,$count);

	$str = $db->getOne("SELECT AsText(point_xy) FROM gridsquare WHERE reference_index=1 AND percent_land = 100 AND gridsquare_id > $offset"); //limit 1 is added automaticallu

	preg_match('/\((\d+) (\d+)\)/',$str,$m);
;
	if ($str && $m[1]) {
		$mapw=($mosaic->image_w/$mosaic->pixels_per_km)/2;
		$mosaic->setOrigin($m[1]-$mapw,$m[2]-$mapw);
	}
	$token=$mosaic->getToken();
	$cacheid='mapper|'.$token;
} elseif (isset($_GET['t'])) {
	$mosaic->setToken($_GET['t']);
	
	$token=$mosaic->getToken();
	$cacheid='mapper|'.$token;
} elseif (isset($_GET['lat'])) {
	require_once('geograph/conversions.class.php');
	$conv = new Conversions();

	list($x,$y) = $conv->national_to_internal($_GET['lon'],$_GET['lat'],1);	
	
	if (isset($_GET['zoom'])) {
		switch($_GET['zoom']) {
			case 0: $mosaic->pixels_per_km =  4; break;
			case 1: $mosaic->pixels_per_km = 40; break;//there isnt a direct equiv
			case 2: $mosaic->pixels_per_km = 40; break;
			case 3: $mosaic->pixels_per_km = 80; break;
			default: die("invalid zoom");
		} 
	} else {
		//legacy support for no zoom specified
	} 
	
	$mapw=($mosaic->image_w/$mosaic->pixels_per_km)/2;
	$mosaic->setOrigin($x-$mapw,$y-$mapw);
	
	$token=$mosaic->getToken();
	$cacheid='mapper|'.$token;
} else {
	$token=$mosaic->getToken();
	$cacheid='mapper';
}

$smarty = new GeographPage;

if (isset($_REQUEST['centi'])) {
	$smarty->assign('centi',1);
	$smarty->assign('extra','centi');
	$cacheid.='|centi';
} 

if (isset($_REQUEST['scenic'])) {
	$smarty->assign('scenic',1);
	$smarty->assign('extra','scenic');
	$cacheid.='|scenic';
} 

if (isset($_REQUEST['recent'])) {
	$smarty->assign('recent',1);
	$smarty->assign('extra','recent');
	$cacheid.='|recent';
} 

if (isset($_REQUEST['full'])) {
        $template = 'mapper_full.tpl';
} elseif (isset($_REQUEST['inner'])) {
	$template = 'mapper_iframe.tpl';
} else {
	$template = 'mapper.tpl';
}


if (!$smarty->is_cached($template, $cacheid))
{
	require_once('geograph/conversions.class.php');
	$conv = new Conversions();
	
	$mapw=($mosaic->image_w/$mosaic->pixels_per_km)/2;
	
	list($e,$n) = $conv->internal_to_national($mosaic->map_x+$mapw,$mosaic->map_y+$mapw,1);	
	
	$smarty->assign('e',$e-500); //remove centering
 	$smarty->assign('n',$n-500);
	
	switch($mosaic->pixels_per_km) {
		case  1: $z = 0; break;//there isnt a direct equiv
		case  4: $z = 0; break;
	//	case ??: $z = 1; break;
		case 40: $z = 2; break;
		case 80: $z = 3; break;
		default: $z = ($mosaic->pixels_per_km<1)?0:2;
	} 
	$smarty->assign('z',$z);
	
	$smarty->assign('token',$token);
}
if (isset($_SESSION['maptt']) || isset($_REQUEST['inner'])) {
	
} else {
	// as we doing in session no need to save.
	$tt = new ThrottleToken('',false);

	if ($USER->hasPerm('admin') || $USER->hasPerm('moderator')) {
		$tt->uses = 500;
	} elseif ($USER->hasPerm('basic')) {
		$tt->uses = 250;
	} else {
		$tt->uses = 50;
	}

	$_SESSION['maptt'] = $tt;
}

$smarty->assign('content_host',$CONF['CONTENT_HOST']);
$smarty->assign('tile_host',$CONF['TILE_HOST']);


$smarty->display($template, $cacheid);

	
?>
