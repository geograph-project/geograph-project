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
init_session();


$conv = new ConversionsLatLong;

$smarty = new GeographPage;

$template='stuff_conversion.tpl';
$cacheid='';


if ($_GET['To']) { //to lat/long
	if ($_GET['datum'] == 'osgb36') {
		$latlong = $conv->osgb36_to_wgs84($_GET['e'],$_GET['n']);
	} else if ($_GET['datum'] == 'irish') {
		list($usec, $sec) = explode(' ',microtime());
		$querytime_before = ((float)$usec + (float)$sec);
		for($q =0;$q<200;$q++)
        	$latlong = $conv->irish_to_wgs84($_GET['e'],$_GET['n'],$_GET['usehermert']);
		$latlong = $conv->irish_to_wgs84($_GET['e'],$_GET['n'],$_GET['usehermert']);
		
		
		list($usec, $sec) = explode(' ',microtime());
		$querytime_after = ((float)$usec + (float)$sec);
				
        $smarty->assign('querytime', "200 conversions took ".number_format($querytime_after - $querytime_before,4)." Seconds");
	} else {
		//todo: make an educated guess - basically if could be irish then use that otherwise gb ?!? - probably not...
	}
	if (count($latlong)) {
		$smarty->assign('lat', $latlong[0]);
		$smarty->assign('long', $latlong[1]);
		
		$smarty->assign('e', $_GET['e']);
		$smarty->assign('n', $_GET['n']);
	} 
} else if ($_GET['From']) { //from lat/long
	if ($_GET['datum'] == 'osgb36') {
		$en = $conv->wgs84_to_osgb36($_GET['lat'],$_GET['long']);
	} else if ($_GET['datum'] == 'irish') {
		list($usec, $sec) = explode(' ',microtime());
        $querytime_before = ((float)$usec + (float)$sec);
        for($q =0;$q<200;$q++)
        	$en = $conv->wgs84_to_irish($_GET['lat'],$_GET['long'],$_GET['usehermert']);
        $en = $conv->wgs84_to_irish($_GET['lat'],$_GET['long'],$_GET['usehermert']);
        
		list($usec, $sec) = explode(' ',microtime());
		$querytime_after = ((float)$usec + (float)$sec);
		
        $smarty->assign('querytime', "200 conversions took ".number_format($querytime_after - $querytime_before,4)." Seconds");
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
	if (count($en)) {
		$smarty->assign('e', $en[0]);
		$smarty->assign('n', $en[1]);
		
		$smarty->assign('lat', $_GET['lat']);
		$smarty->assign('long', $_GET['long']);
	}

}

$smarty->assign('datum', $_GET['datum']);
$smarty->assign('usehermert', $_GET['usehermert']);

$smarty->display($template, $cacheid);

	
?>
