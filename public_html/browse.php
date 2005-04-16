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
require_once('geograph/gridimage.class.php');
require_once('geograph/gridsquare.class.php');
require_once('geograph/imagelist.class.php');
require_once('geograph/map.class.php');
require_once('geograph/mapmosaic.class.php');

init_session();




$smarty = new GeographPage;
//$browser=new GridBrowser;
$square=new GridSquare;

$smarty->assign('prefixes', $square->getGridPrefixes());
$smarty->assign('kmlist', $square->getKMList());






//we can be passed a gridreference as gridsquare/northings/eastings 
//or just gridref. So lets initialise our grid square
$grid_given=false;
$grid_ok=false;



//set by grid components?
if (isset($_GET['setpos']))
{	
	$grid_given=true;
	$grid_ok=$square->setGridPos($_GET['gridsquare'], $_GET['eastings'], $_GET['northings']);

	//preserve inputs in smarty
	$smarty->assign('gridsquare', $square->gridsquare);
	$smarty->assign('eastings', $square->eastings);
	$smarty->assign('northings', $square->northings);
	$smarty->assign('gridref', $square->grid_reference);
	
}
//set by grid ref?
elseif (isset($_GET['gridref']) && strlen($_GET['gridref']))
{
	$grid_given=true;
	$grid_ok=$square->setGridRef($_GET['gridref']);
	
	//preserve inputs in smarty
	
	if ($grid_ok)
	{
		$smarty->assign('gridref', $square->grid_reference);
		$smarty->assign('gridsquare', $square->gridsquare);
		$smarty->assign('eastings', $square->eastings);
		$smarty->assign('northings', $square->northings);
	}
	else
	{
		//preserve the input at least
		$smarty->assign('gridref', stripslashes($_GET['gridref']));
	
	}
	
}

//process grid reference
if ($grid_given)
{
	$square->rememberInSession();
	

	

	//now we see if the grid reference is actually available...
	if ($grid_ok)
	{
		//store details the browser manager has figured out
		$smarty->assign('showresult', 1);
		$smarty->assign('imagecount', $square->imagecount);
		
		//is this just a closest match?
		if (is_object($square->nearest))
		{
			$smarty->assign('nearest_distance', $square->nearest->distance);
			$smarty->assign('nearest_gridref', $square->nearest->grid_reference);
		
		}
		
		//otherwise, lets gether the info we need to display some thumbs
		if ($square->imagecount)
		{
			$images=$square->getImages();
			$smarty->assign_by_ref('images', $images);
		}
	
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

//lets find some recent photos
$recent=new ImageList(array('pending', 'accepted', 'geograph'), 'submitted desc', 5);
$recent->assignSmarty($smarty, 'recent');

//lets add an overview map too
$overview=new GeographMapMosaic('overview');
$overview->assignToSmarty($smarty, 'overview');


$smarty->display('browse.tpl');

	
?>
