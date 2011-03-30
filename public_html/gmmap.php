<?php
/**
 * $Project: GeoGraph $
 * $Id: faq.php 15 2005-02-16 12:23:35Z lordelph $
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
init_session();

$smarty = new GeographPage;

#if (!$USER->hasPerm("basic")) {
#	$smarty->display('static_submit_intro.tpl');
#	exit;
#}

$smarty->assign('google_maps_api_key',$CONF['google_maps_api_key']);

if (isset($_REQUEST['inner'])) {
	$cacheid = 'iframe';
	$smarty->assign('inner',1);
} else {
	$cacheid = '';
}

if (isset($_REQUEST['picasa'])) {
	$cacheid .= 'picasa';
	$smarty->assign('picasa',1);
} elseif (isset($_REQUEST['submit2'])) {
	$cacheid .= 'submit2';
	$smarty->assign('submit2',1);
} elseif (isset($_REQUEST['submit'])) {
} else {
	$cacheid .= 'ext';
	$smarty->assign('ext',1);
}

$zoom = -1;
$op = -1;
$opr = -1;
$lat = 90; $lon = 0;
$mlat = 90; $mlon = 0;
$type = '';# "m" map, "k" satellite, "h" hybrid, "p" terrain, "g" geograph, "og" osm+g
if (isset($_GET['ll']) && preg_match('/^[\d.]+,[\d.]+$/', $_GET['ll'])) {
	list($lat,$lon) = explode(',',$_GET['ll']);
	$lat = floatval($lat);
	$lon = floatval($lon);
}
if (isset($_GET['mll']) && preg_match('/^[\d.]+,[\d.]+$/', $_GET['mll'])) {
	list($mlat,$mlon) = explode(',',$_GET['mll']);
	$mlat = floatval($mlat);
	$mlon = floatval($mlon);
}
if (isset($_GET['z']) && is_numeric($_GET['z'])) {
	$zoom = intval($_GET['z']);
}
if (isset($_GET['o']) && is_numeric($_GET['o'])) {
	$op = floatval($_GET['o']);
	if ($op < 0 || $op > 1)
		$op = -1;
}
if (isset($_GET['or']) && is_numeric($_GET['or'])) {
	$opr = floatval($_GET['or']);
	if ($opr < 0 || $opr > 1)
		$opr = -1;
}
if (isset($_GET['t']) && in_array($_GET['t'], array('m', 'k', 'h', 'p', 'g', 'og'))) {
	$type = $_GET['t'];
}
$smarty->assign('iniz',    $zoom);
$smarty->assign('initype', $type);
$smarty->assign('inio',    $op);
$smarty->assign('inior',   $opr);
$smarty->assign('inilat',  $lat);
$smarty->assign('inilon',  $lon);
$smarty->assign('inimlat', $mlat);
$smarty->assign('inimlon', $mlon);

if (!empty($_REQUEST['grid_reference'])) 
{
	$square=new GridSquare;

	$ok= $square->setByFullGridRef($_REQUEST['grid_reference']);

	if ($ok) {
		$smarty->assign('grid_reference', $grid_reference = $_REQUEST['grid_reference']);
		$smarty->assign('success', 1);
	} else {
		$smarty->assign('errormsg', $square->errormsg);	
	}
} 

$smarty->assign('lat0',   $CONF['gmcentre'][0]);
$smarty->assign('lon0',   $CONF['gmcentre'][1]);
$smarty->assign('latmin', $CONF['gmlatrange'][0][0]);
$smarty->assign('latmax', $CONF['gmlatrange'][0][1]);
$smarty->assign('lonmin', $CONF['gmlonrange'][0][0]);
$smarty->assign('lonmax', $CONF['gmlonrange'][0][1]);

$smarty->display('gmmap.tpl',$cacheid);

?>
