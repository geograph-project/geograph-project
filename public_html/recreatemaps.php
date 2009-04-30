<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Barry Hunter (geo@barryhunter.co.uk)
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
require_once('geograph/map.class.php');
require_once('geograph/mapmosaic.class.php');
require_once('geograph/image.inc.php');
init_session();

$smarty = new GeographPage;

$db = NewADOConnection($GLOBALS['DSN']);

if (isset($_POST['inv']))
{
	$square=new GridSquare;
	$grid_ok=$square->setGridRef($_POST['gridref']);
		
	if ($grid_ok) {
		
		$user_id = intval($_POST['user_id']);
		
		$x = $square->x;
		$y = $square->y;

		require_once('geograph/mapmosaic.class.php');
		$mosaic = new GeographMapMosaic;
		$mosaic->expirePosition($x,$y,$user_id);

		$smarty->assign('gridref', $_POST['gridref'] );
	} else {
		$smarty->assign('errormsg',$square->errormsg);
	}
} 
	
$smarty->assign('invalid_maps',  $db->GetOne("select count(*) from mapcache where age > 0 and type_or_user >= 0"));

$smarty->display('recreatemaps.tpl');

	
?>
