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
//require_once('geograph/gridbrowser.class.php');
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
if (isset($_GET['gridsquare']))
{
	$grid_given=true;
	$grid_ok=$square->setGridPos($_GET['gridsquare'], $_GET['eastings'], $_GET['northings']);
}

if (isset($_GET['gridref']))
{
	$grid_given=true;
	$grid_ok=$square->setGridRef($_GET['gridref']);
}


//process grid reference
if ($grid_given)
{
	$square->rememberInSession();
	

	//preserve inputs in smarty
	$smarty->assign('gridsquare', $square->gridsquare);
	$smarty->assign('eastings', $square->eastings);
	$smarty->assign('northings', $square->northings);
	$smarty->assign('gridref', $square->gridref);
	

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
			$smarty->assign('nearest_gridref', $square->nearest->gridref);
		
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

$smarty->display('browse.tpl');

	
?>
