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

if (strpos($_SERVER['HTTP_USER_AGENT'],'http:') > -1) {
	header("HTTP/1.0 401 Forbidden");
	print "401 forbidden";
	exit;
}

if ($_SERVER['HTTP_HOST'] == 'www.geograph.ie') {
	header("HTTP/1.0 401 Forbidden");
	if (!empty($_SERVER['HTTP_REFERER'])) {
		$url = htmlentities(str_replace('www.geograph.ie','www.geograph.org.uk',$_SERVER['HTTP_REFERER']));
	} else {
		$url = "http://www.geograph.org.uk/";
	}
	print "These maps do not function on <b>geograph.ie</b> site. Please use <a href=\"$url\" onclick=\"window.opener.location.href = this.href;window.close()\">www.geograph.org.uk</a> website.";
	exit;
}


if (!empty($_SERVER['HTTP_REFERER']) && !preg_match('/^https?:\/\/(www|m|schools)\.geograph\.(org\.uk|ie)\//',$_SERVER['HTTP_REFERER'])	) {

	header("HTTP/1.0 401 Forbidden");
        print "<h3>Access Denied</h3>" ;

	print "<p><b>This popup is for internal use of Geograph Project websites</b>. <br><br>Make your own with the <a href=\"http://www.ordnancesurvey.co.uk/oswebsite/web-services/os-openspace/index.html\">OS OpenSpace API</a>.</p>";

        if (!empty($_GET['gridref']) && preg_match('/^\w{2}\s?\d+\s?\d*$/',$_GET['gridref'])) {
                print "<p>However you may still be able to view <a href=\"/gridref/".urlencode($_GET['gridref'])."\">our Gridsquare Browse page</a>.</p>";
        }

        exit;
}

##########################################################

/*
if (!empty($_GET['gridref']) && preg_match('/^[A-Z]{1,2}\s*\d+\s*\d$/',$_GET['gridref'])) {
	header("Location: /mapper/combined.php?mobile=1&gridref=".preg_replace('/\s+/','',$_GET['gridref']));
} else {
	print "404";
}

exit;
*/

##########################################################


require_once('geograph/global.inc.php');
require_once('geograph/gridimage.class.php');
require_once('geograph/gridsquare.class.php');
require_once('geograph/token.class.php');
require_once('geograph/gazetteer.class.php');

init_session_or_cache(3600*3, 900); //cache publically, and privately

if (0 && !isset($_SESSION['user'])) {
	header("HTTP/1.0 403 Forbidden");
        print "<h3>Access Denied</h3>" ;

	print "<p>This popup is for internal use of Geograph Project websites. Make your own with the <a href=\"http://www.ordnancesurvey.co.uk/oswebsite/web-services/os-openspace/index.html\">OS OpenSpace API</a>.</p>";

        if (!empty($_GET['gridref']) && preg_match('/^\w{2}\s?\d+\s?\d*$/',$_GET['gridref'])) {
                print "<p>However you may still be able to view <a href=\"/gridref/".urlencode($_GET['gridref'])."\">our Gridsquare Browse page</a>.</p>";
        }

        exit;
}


$smarty = new GeographPage;

pageMustBeHTTPS();


$template='showmap.tpl';
$cacheid='';

$square=new GridSquare;

/*
if (!$USER->hasPerm("basic")) {
	$smarty->assign('error', "unable to access page");
	$smarty->display($template,$cacheid);
	exit;
}*/

$gridref = $_GET['gridref'];

$grid_given=false;
$grid_ok=false;

if (!empty($gridref))
{
	$grid_given=true;
	$grid_ok=$square->setByFullGridRef($gridref,true);
	
	//preserve inputs in smarty	
	if ($grid_ok)
	{
		$smarty->assign('gridref', stripslashes($gridref));
	}
	else
	{
		$smarty->assign('error', $square->errormsg);
		//preserve the input at least
		$smarty->assign('gridref', stripslashes($gridref));
	}	
} else {
	if (!empty($photographer_gridref)) {
		$grid_ok=$square->setByFullGridRef($photographer_gridref,true);
		if ($grid_ok) {
			$square->natspecified = false;
			$square->nateastings = intval($square->nateastings/1000)*1000;
			$square->natnorthings = intval($square->natnorthings/1000)*1000;
			$square->natgrlen = '4';
			$gridref = $square->grid_reference;
		} else {
			$smarty->assign('error', $square->errormsg);
			$smarty->display($template,$cacheid);
			exit;
		}
	} else {
		$smarty->assign('error', "No Grid Reference Found");
		$smarty->display($template,$cacheid);
		exit;
	}
}




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
	
	if ($square->reference_index==1) {//HACK alart
		$rastermap->service = 'Leaflet'; 
		$rastermap->inline = true;
		$rastermap->enable_os = true;
		$rastermap->width = 350;
	}
	
	$rastermap->addLatLong($lat,$long);
	
	$smarty->assign('gridref', strtoupper($gridref));
	
	if (!empty($photographer_gridref))
	{
		$psquare=new GridSquare;
		$pgrid_given=true;
		$pgrid_ok=$psquare->setByFullGridRef($photographer_gridref,true);
		if (!empty($psquare->nateastings)) {
				$rastermap->addViewpoint($psquare->nateastings,$psquare->natnorthings,$psquare->natgrlen);
			$smarty->assign_by_ref('photographer_gridref', $photographer_gridref);
		} 
	}	
	if (isset($view_direction) && strlen($view_direction) && $view_direction != -1) {
		$rastermap->addViewDirection(intval($view_direction));
		$smarty->assign_by_ref('view_direction', $view_direction);
	}	
	
	$smarty->assign_by_ref('rastermap', $rastermap);	
} 

$smarty->display($template,$cacheid);


