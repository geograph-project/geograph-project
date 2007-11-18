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
$overview=new GeographMapMosaic('overview');

if (isset($_GET['t'])) {
	$mosaic->setToken($_GET['t']);
}

$smarty = new GeographPage;

if (isset($_REQUEST['inner'])) {
	$template = 'mapper_iframe.tpl';
} else {
	$template = 'mapper.tpl';
}

$token=$mosaic->getToken();


//regenerate html?
$cacheid='mapper|'.$token;

if (!$smarty->is_cached($template, $cacheid))
{
	require_once('geograph/conversions.class.php');
	$conv = new Conversions();
	
	$mapw=($mosaic->image_w/$mosaic->pixels_per_km)/2;
	
	list($e,$n) = $conv->internal_to_national($mosaic->map_x+$mapw,$mosaic->map_y+$mapw,1);	
	
	$smarty->assign('e',$e);
	$smarty->assign('n',$n);
	
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
