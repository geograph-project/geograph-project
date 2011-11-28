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

if (!$USER->hasPerm("basic")) {
	$smarty->display('static_submit_intro.tpl');
	exit;
}

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

$levels = array(0.3, 1.0, 4.0, 40.0);
$tilesize = 200;
$xmin = array();
$ymin = array();
$xmax = array();
$ymax = array();
$latmix = array();
$lonmin = array();
$latmax = array();
$lonmax = array();
$grids = array();
$x0 = array();
$y0 = array();
$areanames = array();
$ymax0 = null;
foreach($CONF['gmris'] as $ri) {
	$grids[$ri] = $CONF['gmgrid'][$ri];
	$xmin[$ri] = $CONF['xrange'][$ri][0];
	$ymin[$ri] = $CONF['yrange'][$ri][0];
	$xmax[$ri] = $CONF['xrange'][$ri][1]+1;
	$ymax[$ri] = $CONF['yrange'][$ri][1]+1;
	$latmin[$ri] = $CONF['gmlatrange'][$ri][0];
	$latmax[$ri] = $CONF['gmlatrange'][$ri][1];
	$lonmin[$ri] = $CONF['gmlonrange'][$ri][0];
	$lonmax[$ri] = $CONF['gmlonrange'][$ri][1];
	$x0[$ri] = $CONF['origins'][$ri][0];
	$y0[$ri] = $CONF['origins'][$ri][1];
	$areanames[$ri] = $CONF['references'][$ri];
	if (is_null($ymax0) || $ymax[$ri] > $ymax0)
		$ymax0 = $ymax[$ri];
}
$kmpertile = array();
$yflip = array();
$cyflip = null;
foreach($levels as $pixperkm) {
	$kpt = floor($tilesize/$pixperkm);
	$kmpertile[] = $kpt;
	if (is_null($cyflip) || $cyflip % $kpt)
		$cyflip = ceil($ymax0/$kpt) * $kpt;
	$yflip[] = $cyflip;
}
$smarty->assign('ris', $CONF['gmris']);
$smarty->assign('ridefault', $CONF['gmridefault']);
$smarty->assign('latmin', $latmin);
$smarty->assign('latmax', $latmax);
$smarty->assign('lonmin', $lonmin);
$smarty->assign('lonmax', $lonmax);
$smarty->assign('xmin', $xmin);
$smarty->assign('xmax', $xmax);
$smarty->assign('ymin', $ymin);
$smarty->assign('ymax', $ymax);
$smarty->assign('grids', $grids);
$smarty->assign('x0', $x0);
$smarty->assign('y0', $y0);
$smarty->assign('areanames', $areanames);
$smarty->assign('ts', $tilesize);
$smarty->assign('pixperkm', $levels);
$smarty->assign('kmpertile', $kmpertile);
$smarty->assign('yflip', $yflip);

$smarty->display('omap.tpl',$cacheid);

?>
