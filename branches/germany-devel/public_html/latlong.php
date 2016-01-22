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
include_messages('latlong');

init_session();

## TODO -> german

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
	} else if ($_GET['datum'] == 'german32') {
		$latlong = $conv->utm_to_wgs84($_GET['e'],$_GET['n']);
	} else if ($_GET['datum'] == 'german33') {
		$latlong = $conv->utm_to_wgs84($_GET['e'],$_GET['n'],33);
	} else if ($_GET['datum'] == 'german31') {
		$latlong = $conv->utm_to_wgs84($_GET['e'],$_GET['n'],31);
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
	$reE = '(?P<Edeg>[0-9]+([.,][0-9]*)?)\s*�(\s*(?P<Emin>[0-9]+([.,][0-9]*)?)\s*\')?(\s*(?P<Esec>[0-9]+([.,][0-9]*)?)\s*")?';
	$reN = '(?P<Ndeg>[0-9]+([.,][0-9]*)?)\s*�(\s*(?P<Nmin>[0-9]+([.,][0-9]*)?)\s*\')?(\s*(?P<Nsec>[0-9]+([.,][0-9]*)?)\s*")?';
	$reEn = '(?P<Edeg>[0-9]+([.,][0-9]*)?)(\s*�\s*(?P<Emin>[0-9]+([.,][0-9]*)?)(\s*\'\s*(?P<Esec>[0-9]+([.,][0-9]*)?))?)?';
	$reNn = '(?P<Ndeg>[0-9]+([.,][0-9]*)?)(\s*�\s*(?P<Nmin>[0-9]+([.,][0-9]*)?)(\s*\'\s*(?P<Nsec>[0-9]+([.,][0-9]*)?))?)?';
	$reEdir = '(?P<Edir>[WOEwoe])';
	$reNdir = '(?P<Ndir>[NSns])';
	if (!empty($_GET['multimap']) && preg_match_all("/\(([\+\-]*\d{1,3}\.\d+)\)/",$_GET['multimap'],$matchs) == 2) {
		list ($_GET['lat'],$_GET['long']) = $matchs[1];
	} else if (isset($_GET['multimap'])
	        &&(preg_match('/^\s*'.$reEdir.'\s*'.$reE.'\s*'.$reNdir.'\s*'.$reN.'\s*$/' , $_GET['multimap'], $matches) == 1
	         ||preg_match('/^\s*'.$reNdir.'\s*'.$reN.'\s*'.$reEdir.'\s*'.$reE.'\s*$/' , $_GET['multimap'], $matches) == 1
	         ||preg_match('/^\s*'.$reE.'\s*'.$reEdir.'\s*'.$reN.'\s*'.$reNdir.'\s*$/' , $_GET['multimap'], $matches) == 1
	         ||preg_match('/^\s*'.$reN.'\s*'.$reNdir.'\s*'.$reE.'\s*'.$reEdir.'\s*$/' , $_GET['multimap'], $matches) == 1
	         ||preg_match('/^\s*'.$reEdir.'\s*'.$reEn.'\s*'.$reNdir.'\s*'.$reNn.'\s*$/' , $_GET['multimap'], $matches) == 1
	         ||preg_match('/^\s*'.$reNdir.'\s*'.$reNn.'\s*'.$reEdir.'\s*'.$reEn.'\s*$/' , $_GET['multimap'], $matches) == 1
	         ||preg_match('/^\s*'.$reEn.'\s*'.$reEdir.'\s*'.$reNn.'\s*'.$reNdir.'\s*$/' , $_GET['multimap'], $matches) == 1
	         ||preg_match('/^\s*'.$reNn.'\s*'.$reNdir.'\s*'.$reEn.'\s*'.$reEdir.'\s*$/' , $_GET['multimap'], $matches) == 1
	)) {
	//if (preg_match('/^\s*'.$reEdir.'\s*'.$reE.'\s*'.$reNdir.'\s*'.$reN.'\s*$/' , $teststr, $matches) == 1) {
		#echo "  E: {$matches['Edir']} {$matches['Edeg']} {$matches['Emin']} {$matches['Esec']}\n";
		#echo "  N: {$matches['Ndir']} {$matches['Ndeg']} {$matches['Nmin']} {$matches['Nsec']}\n";
		$_GET['lat']   = str_replace(',','.',$matches['Ndeg']);
		$_GET['latm']  = str_replace(',','.',$matches['Nmin']);
		$_GET['lats']  = str_replace(',','.',$matches['Nsec']);
		$_GET['ns']    = (strtoupper($matches['Ndir']) == 'S') ? 'S' : 'N';
		$_GET['long']  = str_replace(',','.',$matches['Edeg']);
		$_GET['longm'] = str_replace(',','.',$matches['Emin']);
		$_GET['longs'] = str_replace(',','.',$matches['Esec']);
		$_GET['ew']    = (strtoupper($matches['Edir']) == 'W') ? 'W' : 'E';
	} else if (!empty($_GET['gke']) && !empty($_GET['gkn'])) {
		#FIXME better checking?
		$gke = floatval($_GET['gke']);
		$gkn = floatval($_GET['gkn']);
		$ll = $conv->gk_to_wgs84($gke, $gkn);
		$_GET['lat']   = $ll[0];
		$_GET['long']  = $ll[1];
		$_GET['latm']  = 0;
		$_GET['longm'] = 0;
		$_GET['lats']  = 0;
		$_GET['longs'] = 0;
		$_GET['ew']    = 'E';
		$_GET['ns']    = 'N';
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
        
	} else if ($_GET['datum'] == 'german32') {
		$en = $conv->wgs84_to_utm($_GET['lat'],$_GET['long']);
	} else if ($_GET['datum'] == 'german33') {
		$en = $conv->wgs84_to_utm($_GET['lat'],$_GET['long'], 33);
	} else if ($_GET['datum'] == 'german31') {
		$en = $conv->wgs84_to_utm($_GET['lat'],$_GET['long'], 31);
	} else {
		$enr = $conv->wgs84_to_national($_GET['lat'],$_GET['long'],$_GET['usehermert']);
		@list($e,$n,$reference_index) = $enr;
		if ($reference_index == 1) {
			$en = array($e,$n);
			$_GET['datum'] = "osgb36";
		} else if ($reference_index == 2) {
			$en = array($e,$n);
			$_GET['datum'] = "irish";
		} else if ($reference_index == 3) {
			$en = array($e,$n);
			$_GET['datum'] = "german32";
		} else if ($reference_index == 4) {
			$en = array($e,$n);
			$_GET['datum'] = "german33";
		} else if ($reference_index == 5) {
			$en = array($e,$n);
			$_GET['datum'] = "german31";
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
		$smarty->assign('errormgs', $MESSAGES['latlong']['outside_area']);
	}
	if (isset($_GET['lat']) && isset($_GET['long'])) {
		$smarty->assign('lat', $_GET['lat']);
		$smarty->assign('long', $_GET['long']);
		$conv->wgs84_to_friendly_smarty_parts($_GET['lat'],$_GET['long'],$smarty);
		list ($gke, $gkn) = $conv->wgs84_to_gk($_GET['lat'],$_GET['long'],-1);
		$smarty->assign('gke', $gke);
		$smarty->assign('gkn', $gkn);
	}
}
$smarty->assign('datum', $_GET['datum']);
$smarty->assign('usehermert', $_GET['usehermert']);

$smarty->display($template, $cacheid);

	
?>
