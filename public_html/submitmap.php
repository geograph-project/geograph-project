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

$smarty->display('submitmap.tpl',$cacheid);

?>
