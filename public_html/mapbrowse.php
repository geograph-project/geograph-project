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


/* 
Some notes to help thrash out the design...


inputs

we want to allow flexibility, but under our control, in particular the
alignment of the origin of the map

x=internal eastings
y=internal northings

z=zoom level
	0= 0.3 pixels per km (allows entire map to fit in 400x400)
	1= 4 pixels per km (100km grid square in 400x400)
	2= 40 pixels per km (10km grid square in 400x400)

zoom level 0
    x and y not required, we're showing the full map
    
zoom level 1
    if we allow x and y as params, we run the risk of generating and caching
    maps we don't want - what we can do is check that the x%100 and y%100
    is aligned with either the UK or Irish grids, and if not, realign
    the coords with the UK grid

zoom level 2
    again, we're looking for alignment

Another technique might to forget all that and employ a technique where
the information is hash secured?


once we've filtered the inputs, we can pass them to our super flexible 
mapping library


processing

for zoom level 0, we'll just ask for a 400x400 image with a origin
that centres the land mass nicely at a scale factor of 0.3

for zoom level 1+2, we'll request 4 maps at 200x200

initially, we'll probably do the hard work on the server side
but it would be nice if the class made it possible to build a
more client side oriented browser


as well as an map class for abstracting and generating a 
single image, we also need a class for coordinating an 
array of images

$mosaic=new GeographMapMosaic;
$mosaic->setOrigin($x,$y); //internal origin
$mosaic->setMosaicSize(400,400); //target image size
$mosaic->setScale(4);//determine how much map we'll see
$mosaic->setMosaicFactor(2);

//it might be nice if these parameters can be encoded  into a
//token
$token=$mosaic->getToken();
$mosaic->setToken($token);



//other methods can control whether gridlines are plotted etc

//returns 2d array of GeographMap instances which
//can be passed to smarty - note that the images
//don't have to rendered at this time - this can be
//done dynamically by the image urls
$images=$mosaic->getImageArray();

this lets a smarty template easily render a visual - we'll
need to make it an image map which sends the token back
to the server - each image will need it's own token




class should be able  tell us
 - if we can pan left,right,up down and what the params are
 - grid reference for any pixel on the map
 
$gridref=$mosiac->getGridRef($x, $y)

//get pan url (if possible), .e.g =-1,0 to pan left
$url=$mosaic->getPanUrl($xdir,$ydir)

//get a url to zoom out
$url=$mosaic->getZoomOut()


*/


require_once('geograph/global.inc.php');
require_once('geograph/map.class.php');
require_once('geograph/mapmosaic.class.php');
init_session();


$smarty = new GeographPage;



//initialise mosaic
$mosaic=new GeographMapMosaic;
if (isset($_GET['t']))
	$mosaic->setToken($_GET['t']);

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
$template='mapbrowse.tpl';
$cacheid=$token;

//regenerate?
if (!$smarty->is_cached($template, $cacheid))
{
	//get the image array
	$images =& $mosaic->getImageArray();
	$smarty->assign_by_ref('mosaic', $images);
	
	//navigation urls
	$smarty->assign('url_zoomout', $mosaic->getZoomOutUrl());
	$smarty->assign('url_north', $mosaic->getPanUrl(0, 1));
	$smarty->assign('url_south', $mosaic->getPanUrl(0, -1));
	$smarty->assign('url_west', $mosaic->getPanUrl(-1, 0));
	$smarty->assign('url_east', $mosaic->getPanUrl(1, 0));
	
}


$smarty->display($template, $cacheid);

	
?>
