<?php
/**
 * $Project: GeoGraph $
 * $Id: browse.php 4205 2008-03-05 22:28:36Z barry $
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
require_once('geograph/gridimage.class.php');
require_once('geograph/gridsquare.class.php');
require_once('geograph/imagelist.class.php');
require_once('geograph/map.class.php');
require_once('geograph/mapmosaic.class.php');
require_once('geograph/rastermap.class.php');

init_session();


$smarty = new GeographPage;

dieUnderHighLoad(0.5);

#customGZipHandlerStart();

$square=new GridSquare;


$template='near.tpl';

$USER->mustHavePerm("basic");

//we can be passed a gridreference as gridsquare/northings/eastings 
//or just gridref. So lets initialise our grid square
$grid_given=false;
$grid_ok=false;


//set by grid components?
if (isset($_GET['p']))
{	
	$grid_given=true;
	//p=900y + (900-x);
	$p = intval($_GET['p']);
	$x = ($p % 900);
	$y = ($p - $x) / 900;
	$x = 900 - $x;
	$grid_ok=$square->loadFromPosition($x, $y, true);
	$grid_given=true;
	$smarty->assign('gridrefraw', $square->grid_reference);
}

//set by grid components?
elseif (isset($_GET['setpos']))
{	
	$grid_given=true;
	$grid_ok=$square->setGridPos($_GET['gridsquare'], $_GET['eastings'], $_GET['northings']);
	$smarty->assign('gridrefraw', $square->grid_reference);
}

//set by grid ref?
elseif (isset($_GET['gridref']) && strlen($_GET['gridref']))
{
	$grid_given=true;
	$grid_ok=$square->setByFullGridRef($_GET['gridref']);
		
	//preserve inputs in smarty	
	if ($grid_ok)
	{
		$smarty->assign('gridrefraw', stripslashes($_GET['gridref']));
	}
	else
	{
		//preserve the input at least
		$smarty->assign('gridref', stripslashes($_GET['gridref']));
	}	
}

$cacheid='';



//process grid reference
if ($grid_given)
{
	$square->rememberInSession();

	//now we see if the grid reference is actually available...
	if ($grid_ok)
	{
		$smarty->assign_by_ref('square', $square);
		$smarty->assign('gridref', $square->grid_reference);
		$smarty->assign('gridsquare', $square->gridsquare);
		$smarty->assign('eastings', $square->eastings);
		$smarty->assign('northings', $square->northings);
		$smarty->assign('x', $square->x);
		$smarty->assign('y', $square->y);
		
	

		//geotag the page	
		require_once('geograph/conversions.class.php');
		$conv = new Conversions;
		list($lat,$long) = $conv->gridsquare_to_wgs84($square);
		$smarty->assign('lat', $lat);
		$smarty->assign('long', $long);
		$smarty->assign_by_ref('square', $square);
		
		list($latdm,$longdm) = $conv->wgs84_to_friendly($lat,$long);
		$smarty->assign('latdm', $latdm);
		$smarty->assign('longdm', $longdm);
		
		$smarty->assign('el', ($long > 0)?'E':'W');
		$smarty->assign('nl', ($lat > 0)?'N':'S');
		
		$places = array();
		if ($square->reference_index == 1) {
			$places[] = $square->findNearestPlace(75000,'OS');
			$places[] = $square->findNearestPlace(75000,'OS250');
			$places[] = $square->findNearestPlace(75000,'hist');
			$places[] = $square->findNearestPlace(75000,'towns');
		}
		$places[] = $square->findNearestPlace(75000,'geonames');
		
		
		$smarty->assign_by_ref('places', $places);
		
	}
	else
	{
		$smarty->assign('errormsg', $square->errormsg);	
		

	}
}
else
{
	//no square specifed - populate with remembered values
	$smarty->assign('gridsquare', $_SESSION['gridsquare']);
	$smarty->assign('eastings', $_SESSION['eastings']);
	$smarty->assign('northings', $_SESSION['northings']);
	
}



$smarty->display($template,$cacheid);

	
?>
