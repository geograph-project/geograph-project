<?php
/**
 * $Project: GeoGraph $
 * $Id: browse.php 2865 2007-01-05 14:24:01Z barry $
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
require_once('geograph/gridimage.class.php');
require_once('geograph/gridsquare.class.php');
require_once('geograph/token.class.php');
require_once('geograph/gazetteer.class.php');

init_session();


$smarty = new GeographPage;

$square=new GridSquare;

if (!$USER->hasPerm("basic") || empty($_GET['t']) || (!$_SESSION['gridsquare'] && !$USER->hasPerm("moderator"))) {
	$smarty->display('no_permission.tpl');
	
	exit;
}


$token=new Token;
if ($token->parse($_GET['t']))
{
	if ($token->hasValue("g"))
	{
		$gridref = $token->getValue("g");
		
		if ($token->hasValue("p"))
		{
			$photographer_gridref = $token->getValue("p");
		}
		
		if ($token->hasValue("v"))
		{
			$view_direction = $token->getValue("v");
		}
		
	}
}

$grid_given=false;
$grid_ok=false;
$pgrid_given=false;
$pgrid_ok=false;

if (!empty($gridref))
{
	$grid_given=true;
	$grid_ok=$square->setByFullGridRef($gridref,true);
	
	//preserve inputs in smarty	
	if ($grid_ok)
	{
		$smarty->assign('gridrefraw', stripslashes($gridref));
	}
	else
	{
		//preserve the input at least
		$smarty->assign('gridref', stripslashes($gridref));
	}	
} else {
	$smarty->display('no_permission.tpl');
	exit;
}



$template='submit_popup.tpl';
$cacheid='';

if ($grid_ok) {
	
	//geotag the page	
	require_once('geograph/conversions.class.php');
	$conv = new Conversions;
	list($lat,$long) = $conv->gridsquare_to_wgs84($square);
	$smarty->assign('lat', $lat);
	$smarty->assign('long', $long);
	$smarty->assign_by_ref('square', $square);
	
	//lets add an rastermap too
	$rastermap = new RasterMap($square,false,$square->natspecified,true);
	$rastermap->addLatLong($lat,$long);
	
	$smarty->assign_by_ref('gridref', $gridref);
	
	if (!empty($photographer_gridref))
	{
		$psquare=new GridSquare;
		$pgrid_given=true;
		$pgrid_ok=$psquare->setByFullGridRef($photographer_gridref,true);
		if (!empty($psquare->nateastings)) {
				$rastermap->addViewpoint($psquare->nateastings,$psquare->natnorthings,$psquare->natgrlen);
			$smarty->assign_by_ref('photographer_gridref', $photographer_gridref);
		} 
		if (!empty($view_direction) && $view_direction != -1) {
			$rastermap->addViewDirection(intval($view_direction));
			$smarty->assign_by_ref('view_direction', $view_direction);
		}	
	}	
	
	$smarty->assign_by_ref('rastermap', $rastermap);
	
	$gaz = new Gazetteer();
			
	$places = $gaz->findListByNational($square->reference_index,$square->nateastings,$square->natnorthings,($square->reference_index==1)?2000:5000);	
	$smarty->assign_by_ref('places', $places);
	
} 

$smarty->display($template,$cacheid);

	
?>
