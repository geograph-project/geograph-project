<?php
/**
 * $Project: GeoGraph $
 * $Id$
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
require_once('geograph/gridsquare.class.php');
require_once('geograph/gridimage.class.php');
require_once('geograph/map.class.php');
require_once('geograph/mapmosaic.class.php');

init_session();
$template='mapsheet.tpl';

$smarty = new GeographPage;


//initialise mosaic
$map=new GeographMap;

if (isset($_GET['t']))
	$map->setToken($_GET['t']);



//get token, we'll use it as a cache id
$token=$map->getToken();


//regenerate html?

$cacheid='mapsheet|'.$token;

//regenerate?
if (!$smarty->is_cached($template, $cacheid))
{
	//assign main map to smarty
	$smarty->assign_by_ref('map', $map);
	
	$grid=&$map->getGridArray();
	$smarty->assign_by_ref('grid', $grid);
	
	$ri = $grid[0][0]['reference_index'];
	
	$letterlength = 3 - $ri; #todo should this be auto-realised by selecting a item from gridprefix? (or a grid_reference)

	$smarty->assign('ofe', $letterlength + 1);
	$smarty->assign('ofn', $letterlength + 3);
	
	//assign all the other useful stuff
	$smarty->assign('gridref', $map->getGridRef(-1,-1));
	
	
}


$smarty->display($template, $cacheid);

	
?>
