<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 BArry Hunter (geo@barryhunter.co.uk)
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
require_once('geograph/conversions.class.php');
require_once('geograph/conversionslatlong.class.php');
require_once('geograph/gridsquare.class.php');
require_once('geograph/mapmosaic.class.php');

init_session();


$conv = new ConversionsLatLong;

$smarty = new GeographPage;

$template='latlong.tpl';
$cacheid='';

if (!isset($_GET['usehermert']))
	$_GET['usehermert'] = 1;
if (!isset($_GET['datum']))
	$_GET['datum'] = '-';

if (!empty($_GET['To'])) { //to lat/long
	if ($_GET['datum'] == 'osgb36') {
		$latlong = $conv->osgb36_to_wgs84($_GET['e'],$_GET['n']);
	} else if ($_GET['datum'] == 'irish') {
		$latlong = $conv->irish_to_wgs84($_GET['e'],$_GET['n'],$_GET['usehermert']);
	} else {
		//todo: make an educated guess - basically if could be irish then use that otherwise gb ?!? - probably not...
	}
	if (count($latlong)) {
		$smarty->assign('lat', $latlong[0]);
		$smarty->assign('long', $latlong[1]);
		
		$smarty->assign('e', $_GET['e']);
		$smarty->assign('n', $_GET['n']);
	} 
} elseif (!empty($_GET['From'])) { //from lat/long
	if (!empty($_GET['multimap']) && preg_match_all("/\(([\+\-]*\d{1,3}\.\d+)\)/",$_GET['multimap'],$matchs) == 2) {
		list ($_GET['lat'],$_GET['long']) = $matchs[1];
	}
	
		if (!empty($_GET['latm']))
			$_GET['lat'] += $_GET['latm']/60;
		if (!empty($_GET['lats']))
			$_GET['lat'] += $_GET['lats']/3600;
		if (!empty($_GET['ns']) && $_GET['ns'] == 'S')
			$_GET['lat'] *= -1;

		if (!empty($_GET['longm']))
			$_GET['long'] += $_GET['longm']/60;
		if (!empty($_GET['longs']))
			$_GET['long'] += $_GET['longs']/3600;
		if (!empty($_GET['ew']) && $_GET['ew'] == 'W')
			$_GET['long'] *= -1;


	if ($_GET['datum'] == 'osgb36') {
		$en = $conv->wgs84_to_osgb36($_GET['lat'],$_GET['long']);
	} else if ($_GET['datum'] == 'irish') {
		$en = $conv->wgs84_to_irish($_GET['lat'],$_GET['long'],$_GET['usehermert']);
        
	} else {
		list($e,$n,$reference_index) = $conv->wgs84_to_national($_GET['lat'],$_GET['long'],$_GET['usehermert']);
		if ($reference_index == 1) {
			$en = array($e,$n);
			$_GET['datum'] = "osgb36";
		} else if ($reference_index == 2) {
			$en = array($e,$n);
			$_GET['datum'] = "irish";
		}
	}
	if (isset($en) && count($en)) {
		list ($gridref,$len) = $conv->national_to_gridref($en[0],$en[1],0,$reference_index);
		
		$square = new GridSquare;
		if($square->setByFullGridRef($gridref)) {
			//find a possible place
			$smarty->assign('place', $square->findNearestPlace(135000));	

			//lets add an overview map too
			$overview=new GeographMapMosaic('largeoverview');
			$overview->setCentre($square->x,$square->y); //does call setAlignedOrigin
			
			$overview->assignToSmarty($smarty, 'overview');
			$smarty->assign('marker', $overview->getSquarePoint($square));

			//get a token to show a suroudding geograph map
			$mosaic=new GeographMapMosaic;
			$smarty->assign('map_token', $mosaic->getGridSquareToken($square));
		}
		
		$smarty->assign('gridref', $gridref);
		
		list ($gridref,$len) = $conv->national_to_gridref($en[0],$en[1],4,$reference_index);
		$smarty->assign('gridref4', $gridref);
		
		
		
		$smarty->assign('e', $en[0]);
		$smarty->assign('n', $en[1]);
		
	} else {
		$smarty->assign('errormgs', 'This location does not appear to be in the British Isles');
	}
	$smarty->assign('lat', $_GET['lat']);
	$smarty->assign('long', $_GET['long']);
	$conv->wgs84_to_friendly_smarty_parts($_GET['lat'],$_GET['long'],$smarty);
}
$smarty->assign('datum', $_GET['datum']);
$smarty->assign('usehermert', $_GET['usehermert']);

$smarty->display($template, $cacheid);

	
?>
