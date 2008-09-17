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

dieUnderHighLoad(4);

customGZipHandlerStart();

$square=new GridSquare;


$template='gridmap.tpl';



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
		

		//lets add an rastermap too
		$rastermap = new RasterMap($square,false,$square->natspecified);
		$rastermap->service = 'Google';
		$rastermap->addLatLong($lat,$long);
		$smarty->assign_by_ref('rastermap', $rastermap);
	
		
		$blocks = array();
		
		$db=NewADOConnection($GLOBALS['DSN']);
		if (!$db) die('Database connection failed');  
		
		
		$rows = $db->cacheGetAll(3600,"SELECT 
		nateastings,natnorthings,viewpoint_eastings,viewpoint_northings,count(*) as c
		from gridimage
		where gridsquare_id = {$square->gridsquare_id} and moderation_status in ('geograph','accepted') 
			and nateastings > 0 and viewpoint_eastings > 0 and viewpoint_grlen != '4'
		group by nateastings DIV 100,natnorthings DIV 100,viewpoint_eastings DIV 100, viewpoint_northings DIV 100");
		
		function quant($i) {
			return (floor($i/100)*100)+50;
		}
		
		foreach ($rows as $row) {
			list($lat1,$long1) = $conv->national_to_wgs84(quant($row['nateastings']),quant($row['natnorthings']),$square->reference_index);
			list($lat2,$long2) = $conv->national_to_wgs84(quant($row['viewpoint_eastings']),quant($row['viewpoint_northings']),$square->reference_index);
			
			
			$code = "map.addOverlay(new GPolygon([
							new GLatLng($lat1,$long1),
							new GLatLng($lat2,$long2)
						], \"#FF0000\", 1, 0.7, \"#00FF00\", 0.5));\n";
			$blocks[] = $code;
			
			$code = "
			var iconOptions = {};
			iconOptions.width = 16;
			iconOptions.height = 16;
			iconOptions.primaryColor = \"#FF0000\";
			iconOptions.label = \"{$row['c']}\";
			iconOptions.labelSize = 0;
			iconOptions.labelColor = \"#000000\";
			iconOptions.shape = \"circle\";
			var icon = MapIconMaker.createFlatIcon(iconOptions);";
			$blocks[] = $code;
			
			$code = "map.addOverlay(new Gmarker(new GLatLng($lat2,$long2), icon));\n";
			$blocks[] = $code;
			
		}
		
		
		$smarty->assign_by_ref('blocks', $blocks);
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
