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


//initialise mosaic
$mosaic=new GeographMapMosaic;
if (isset($_GET['t']))
	$mosaic->setToken($_GET['t']);

if (isset($_GET['expireAll']) && $USER->hasPerm('admin'))
{
	$mosaic->expireAll($_GET['expireAll']?true:false);
	$smarty->clear_cache(null, 'mapbrowse');
}



//cache graphics files?
$mosaic->enableCaching($CONF['smarty_caching']);


//are we zooming in on an image map? we'll have a url like this
//i and j give the index of the mosaic image
//http://geograph.elphin/mapbrowse.php?t=token&i=0&j=0&zoomin=?275,199
if (isset($_GET['zoomin']))
{
	//extract x and y click coordinate from imagemap
	$bits=explode(',', substr($_GET['zoomin'],1));
	$x=intval($bits[0]);
	$y=intval($bits[1]);
	
	//get the image index
	$i=intval($_GET['i']);
	$j=intval($_GET['j']);
	
	//handle the zoom
	$mosaic->zoomIn($i, $j, $x, $y);	
	
	
}


//get token, we'll use it as a cache id
$token=$mosaic->getToken();


//regenerate html?
$cacheid='mapbrowse|'.$token;

//regenerate?
if (!$smarty->is_cached($template, $cacheid))
{
	//get the image array
	$images =& $mosaic->getImageArray();
	$smarty->assign_by_ref('mosaic', $images);
	
	//for debugging pass the entire mosaic object
	//if ($CONF['smarty_debugging'])
		$smarty->assign_by_ref('mosaicobj', $mosaic);
	
	
	$smarty->assign('mosaic_width', $mosaic->image_w);
	$smarty->assign('mosaic_height', $mosaic->image_h);
	$smarty->assign('token', $token);
	
	//navigation urls
	$smarty->assign('token_zoomout', $mosaic->getZoomOutToken());
	$smarty->assign('token_north', $mosaic->getPanToken(0, 1));
	$smarty->assign('token_south', $mosaic->getPanToken(0, -1));
	$smarty->assign('token_west', $mosaic->getPanToken(-1, 0));
	$smarty->assign('token_east', $mosaic->getPanToken(1, 0));
	
}


$smarty->display($template, $cacheid);

	
?>
