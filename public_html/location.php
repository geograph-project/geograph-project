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

if (strpos(@$_SERVER['HTTP_USER_AGENT'], 'archive.org_bot')!==FALSE) {
	header('HTTP/1.0 403 Forbidden');
	exit;
}

foreach(array('p','setpos','setpos2','grid_reference','gridref','eastings','northings','gridsquare') as $key)
	if (!empty($_REQUEST[$key]) && !preg_match('/^[\w \.>+]*$/',$_REQUEST[$key])) {
	     header('HTTP/1.0 451 Unavailable For Legal Reasons');
	     exit;
	}

require_once('geograph/global.inc.php');
require_once('geograph/gridimage.class.php');
require_once('geograph/gridsquare.class.php');
require_once('geograph/imagelist.class.php');
require_once('geograph/map.class.php');
require_once('geograph/mapmosaic.class.php');
require_once('geograph/rastermap.class.php');

//block bots from going crazy crawling lots of pages
if (!appearsToBePerson()) { //this will only catch identiable bots
       rate_limiting('browse.php', 5, true); //lets share the quota with browse.php!
}

init_session();


$smarty = new GeographPage;

dieUnderHighLoad(4);

customGZipHandlerStart();
customExpiresHeader(360,false,true);


$square=new GridSquare;


$template='location.tpl';
if (!empty($_GET['test'])) {
        if ($USER->registered) {
                if (empty($USER->stats))
                        $USER->getStats();
                $smarty->assign('stats',$USER->stats);
        }
	$template='location_test.tpl';
}

if (isset($_GET['getamap'])) {
	$smarty->assign('getamap', 1);
}

//we can be passed a gridreference as gridsquare/northings/eastings
//or just gridref. So lets initialise our grid square
$grid_given=false;
$grid_ok=false;

$smarty->assign('prefixes', $square->getGridPrefixes());
$smarty->assign('kmlist', $square->getKMList());

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
        //nginx seems to have reencoded the + in the URL as %2B by the time reaches PHP, so reading QUERY_STRING gets %2B, which is then decoded as + (not space!
        $_GET['gridref'] = str_replace('+',' ',$_GET['gridref']);

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
		pageMustBeHTTPS();

		$smarty->assign_by_ref('square', $square);
		$smarty->assign('gridref', $square->grid_reference);
		$smarty->assign('gridsquare', $square->gridsquare);
		$smarty->assign('eastings', $square->eastings);
		$smarty->assign('northings', $square->northings);
		$smarty->assign('x', $square->x);
		$smarty->assign('y', $square->y);
		$smarty->assign('place', $square->findNearestPlace(135000));
    
		//geotag the page
		require_once('geograph/conversions.class.php');
		$conv = new Conversions;
		list($lat,$long) = $conv->gridsquare_to_wgs84($square);
		$smarty->assign('lat', $lat);
		$smarty->assign('long', $long);
		$smarty->assign_by_ref('square', $square);
		$smarty->assign('intergrated_layers',     $CONF['intergrated_layers'][$square->reference_index]);
		$smarty->assign('intergrated_zoom',       $CONF['intergrated_zoom'][$square->reference_index]);
		$smarty->assign('intergrated_zoom_centi', $CONF['intergrated_zoom_centi'][$square->reference_index]);

		list($latdm,$longdm) = $conv->wgs84_to_friendly($lat,$long);
		$smarty->assign('latdm', $latdm);
		$smarty->assign('longdm', $longdm);

		$smarty->assign('el', ($long > 0)?'E':'W');
		$smarty->assign('nl', ($lat > 0)?'N':'S');
		$smarty->assign('lat_abs', abs($lat));
		$smarty->assign('long_abs', abs($long));

		//get a token to show a suroudding geograph map
		$mosaic=new GeographMapMosaic;
		$smarty->assign('map_token', $mosaic->getGridSquareToken($square));

		if (!empty($CONF['forums'])) {
			$square->assignDiscussionToSmarty($smarty);
		}

		//look for images from here...
		$sphinx = new sphinxwrapper();
		if ($viewpoint_count = $sphinx->countImagesViewpoint($square->getNatEastings(),$square->getNatNorthings(),$square->reference_index,$square->grid_reference)) {
			$smarty->assign('viewpoint_count', $viewpoint_count);
			$smarty->assign('viewpoint_query', $sphinx->q);
		}

		if ($square->natspecified && $square->natgrlen >= 6) {
			$conv = new Conversions('');
			list($gr6,$len) = $conv->national_to_gridref(
				$square->getNatEastings(),
				$square->getNatNorthings(),
				6,
				$square->reference_index,false);
			$smarty->assign('gridref6', $gr6);
		}

		if ($square->reference_index == 1) {
                        if (preg_match('/(SC.*|NX3.0.|NX4.0)/',$square->grid_reference)) {
                                //isle of man uses a different origin!
				$dblock = sprintf("GB-%d-%d",(intval(($square->getNatEastings()+2000)/4000)*4000)-2000,(intval(($square->getNatNorthings()+1000)/3000)*3000)-1000);
                        } else {
				$dblock = sprintf("GB-%d-%d",intval($square->getNatEastings()/4000)*4000,intval($square->getNatNorthings()/3000)*3000);
			}
			$smarty->assign('dblock', $dblock);
		} elseif ($square->reference_index == 2 && $square->getNatNorthings() >= 300000) {
			$dblock = sprintf("NI-%d-%d",intval($square->getNatEastings()/4000)*4000,intval($square->getNatNorthings()/3000)*3000);
			$smarty->assign('dblock', $dblock);
		}

		//lets add an overview map too
		$overview=new GeographMapMosaic('largeoverview');
		$overview->setCentre($square->x,$square->y); //does call setAlignedOrigin
		$smarty->assign('marker', $overview->getSquarePoint($square));

		//lets add an rastermap too
		$rastermap = new RasterMap($square,false,$square->natspecified);
		$rastermap->addLatLong($lat,$long);
		$smarty->assign_by_ref('rastermap', $rastermap);

		$overview->assignToSmarty($smarty, 'overview');

		$smarty->assign('hectad', $hectad = $square->gridsquare.intval($square->eastings/10).intval($square->northings/10));
		$db = GeographDatabaseConnection(true);
		$smarty->assign('hectad_row',$db->getRow("SELECT * FROM hectad_stat WHERE geosquares >= landsquares AND hectad = '$hectad' AND largemap_token != '' LIMIT 1"));

		if (!empty($_GET['taken'])) {
			$smarty->assign('image_taken',$_GET['taken']);
		}
		if (!empty($_GET['title'])) {
			$smarty->assign('title',$_GET['title']);
		}
		if (!empty($_GET['id'])) {
			$smarty->assign('id',intval($_GET['id']));
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



$smarty->display($template,$cacheid);

