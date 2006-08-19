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
	//$map->caching=false;
	$map->setToken($_GET['map']);
	$map->returnImage();
	exit;
	
}


init_session();
$template='mapbrowse.tpl';

$smarty = new GeographPage;


//initialise mosaic
$mosaic=new GeographMapMosaic;
$overview=new GeographMapMosaic('overview');

if (isset($_GET['o']))
	$overview->setToken($_GET['o']);
	
if (isset($_GET['t']))
	$mosaic->setToken($_GET['t']);

if (preg_match('/\?([0-9]+),([0-9]+)$/',$_SERVER['QUERY_STRING'],$matchs)) {
	$_GET['x']=$matchs[1];
	$_GET['y']=$matchs[2];
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
	$mosaic->setScale($mosaic->scales[1]);
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


//get token, we'll use it as a cache id
$token=$mosaic->getToken();


//regenerate html?
$cacheid='mapbrowse|'.$token;

$smarty->cache_lifetime = 3600*24; //24hr cache

if (isset($_GET['gridref_from']) && preg_match('/^[a-zA-Z]{1,2}\d{4}$/',$_GET['gridref_from'])) {
	$smarty->assign('gridref_from', $_GET['gridref_from']);
}

//regenerate?
if (!$smarty->is_cached($template, $cacheid))
{
	//assign overview to smarty
	$overview->assignToSmarty($smarty, 'overview');
	$smarty->assign('marker', $overview->getBoundingBox($mosaic));
	
	//assign main map to smarty

	$mosaic->assignToSmarty($smarty, 'mosaic');
	
	//assign all the other useful stuff
	
	//build template image map
	//experimental - for use with alternative template 
	if (false)

	{
		$template='mapbrowse2.tpl';
		$rect=array();

		$mapgranulatity=10;

		$mapsize=$mosaic->image_w/$mapgranulatity;
		$tilegranulatity=$mapgranulatity/$mosaic->mosaic_factor;



		for($x=0;$x<$tilegranulatity;$x++)
		{
			for($y=0;$y<$tilegranulatity;$y++)
			{
				$x1=$x*$mapsize;
				$y1=$y*$mapsize;

				$x2=$x1+$mapsize;
				$y2=$y1+$mapsize;

				$clickx=$x1+$mapsize/2;
				$clicky=$y1+$mapsize/2;

				$rect[$x][$y]=array($x1,$y1,$x2,$y2,$clickx,$clicky);		
			}
		}
		$smarty->assign_by_ref('imgmap', $rect);
	}
	
	if ($gridref = $mosaic->getGridRef(-1,-1)) {
		$smarty->assign('gridref', $gridref);
		if ($mosaic->pixels_per_km == 40 && preg_match('/(\w+\d)5(\d)5/',$gridref,$m)) {
			$smarty->assign('hectad', $hectad = $m[1].$m[2]);
			$db=NewADOConnection($GLOBALS['DSN']);
			$smarty->assign_by_ref('hectad_row',$db->getRow("select * from hectad_complete where hectad_ref = '$hectad' limit 1"));
		}
	}
	$smarty->assign('mapwidth', round($mosaic->image_w /$mosaic->pixels_per_km ) );
	
	$smarty->assign('token_zoomin', $mosaic->getZoomInToken());
	$smarty->assign('token_zoomout', $mosaic->getZoomOutToken());
	$smarty->assign('token_north', $mosaic->getPanToken(0, 1));
	$smarty->assign('token_south', $mosaic->getPanToken(0, -1));
	$smarty->assign('token_west', $mosaic->getPanToken(-1, 0));
	$smarty->assign('token_east', $mosaic->getPanToken(1, 0));
	
	
			
	//no big unless you are zoomed in
	if ($mosaic->pixels_per_km >=4)
	{
	#	$smarty->assign('token_big', $mosaic->getBigToken());
	}
}

$smarty->display($template, $cacheid);

	
?>
