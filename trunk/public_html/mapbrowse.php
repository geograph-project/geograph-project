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
	//render and return a map with token $_GET['map'];
	$map=new GeographMap;
	$map->setToken($_GET['map']);
	$map->returnImage();
	exit;
	
}


init_session();
$template='mapbrowse.tpl';

$smarty = new GeographPage;


$overview=new GeographMapMosaic('overview');
$overview->enableCaching($CONF['smarty_caching']);

//initialise mosaic
$mosaic=new GeographMapMosaic;
if (isset($_GET['t']))
	$mosaic->setToken($_GET['t']);

if (isset($_GET['expireAll']) && $USER->hasPerm('admin'))
{
	$mosaic->expireAll($_GET['expireAll']?true:false);
	$smarty->clear_cache(null, 'mapbrowse');
	

	//redirect to prevent page refreshes of this url

	header("Location:http://{$_SERVER['HTTP_HOST']}/mapbrowse.php");
	exit;
}



//cache graphics files?
$mosaic->enableCaching($CONF['smarty_caching']);


//are we zooming in on an image map? we'll have a url like this
//i and j give the index of the mosaic image
//http://geograph.elphin/mapbrowse.php?t=token&i=0&j=0&zoomin=?275,199
if (isset($_GET['zoomin']))
{
	//extract x and y click coordinate from imagemap
	if (strlen($_GET['zoomin']))
	{
		$bits=explode(',', substr($_GET['zoomin'],1));
		$x=intval($bits[0]);
		$y=intval($bits[1]);
	}
	else
	{
		//href followed without a mouse click - use center
		$x=round(($mosaic->image_w/$mosaic->mosaic_factor)/2);
		$y=round(($mosaic->image_h/$mosaic->mosaic_factor)/2);
	}
	
	//get the image index
	$i=intval($_GET['i']);
	$j=intval($_GET['j']);
	
	//handle the zoom
	$mosaic->zoomIn($i, $j, $x, $y);	
}

if (isset($_GET['center']))
{
	//extract x and y click coordinate from imagemap
	if (strlen($_GET['center']))
	{
		$bits=explode(',', substr($_GET['center'],1));
		$x=intval($bits[0]);
		$y=intval($bits[1]);
	}
	else
	{
		//href followed without a mouse click - use center
		$x=round(($overview->image_w/$overview->mosaic_factor)/2);
		$y=round(($overview->image_h/$overview->mosaic_factor)/2);
	}
	
	//get the image index
	$i=intval($_GET['i']);
	$j=intval($_GET['j']);
	
	//get click coordinate on overview, use it to centre the main map
	list($intx, $inty)=$overview->getClickCoordinates($i, $j, $x, $y);	
	$mosaic->setScale($mosaic->scales[1]);
	$mosaic->setMosaicFactor(2);
	$mosaic->setCentre($intx, $inty);	
	
}

if (isset($_GET['recenter']))
{
	//extract x and y click coordinate from imagemap
	if (strlen($_GET['recenter']))
	{
		$bits=explode(',', substr($_GET['recenter'],1));
		$x=intval($bits[0]);
		$y=intval($bits[1]);
	}
	else
	{
		//href followed without a mouse click - use center
		$x=round(($overview->image_w/$overview->mosaic_factor)/2);
		$y=round(($overview->image_h/$overview->mosaic_factor)/2);
	}
	
	//get the image index
	$i=intval($_GET['i']);
	$j=intval($_GET['j']);
	
	//get click coordinate on overview, use it to centre the main map
	list($intx, $inty)=$overview->getClickCoordinates($i, $j, $x, $y);	
	$mosaic->setCentre($intx, $inty);	
	
}


//get token, we'll use it as a cache id
$token=$mosaic->getToken();


//regenerate html?
$is_admin=$USER->hasPerm('admin')?1:0;
$cacheid='mapbrowse|'.$token.'_'.$is_admin;

//regenerate?
if (!$smarty->is_cached($template, $cacheid))
{
	//setup the overview variables
	$overviewimages =& $overview->getImageArray();
	$smarty->assign_by_ref('overview', $overviewimages);
	$smarty->assign('overview_width', $overview->image_w);
	$smarty->assign('overview_height', $overview->image_h);
	$smarty->assign('overview_token', $overview->getToken());
	
	//calculate the position of the markerbox
	$smarty->assign('marker', $overview->getBoundingBox($mosaic));
	
	
	

	//get the image array
	$images =& $mosaic->getImageArray();
	$smarty->assign_by_ref('mosaic', $images);
	
	$smarty->assign('gridref', $mosaic->getGridRef(-1,-1));
	
	
	//for debugging pass the entire mosaic object
	//if ($CONF['smarty_debugging'])
		$smarty->assign_by_ref('mosaicobj', $mosaic);
	
	
	$smarty->assign('mosaic_width', $mosaic->image_w);
	$smarty->assign('mosaic_height', $mosaic->image_h);
	$smarty->assign('token', $token);
	
	//navigation urls
	$smarty->assign('token_zoomin', $mosaic->getZoomInToken());
	$smarty->assign('token_zoomout', $mosaic->getZoomOutToken());
	$smarty->assign('token_north', $mosaic->getPanToken(0, 1));
	$smarty->assign('token_south', $mosaic->getPanToken(0, -1));
	$smarty->assign('token_west', $mosaic->getPanToken(-1, 0));
	$smarty->assign('token_east', $mosaic->getPanToken(1, 0));
	
}


$smarty->display($template, $cacheid);

	
?>
