<?php
/**
 * $Project: GeoGraph $
 * $Id: search.php 2403 2006-08-16 15:55:41Z barry $
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
require_once('geograph/rastermap.class.php');
init_session();

$smarty = new GeographPage;

$template = "squares.tpl";
$cacheid = '';

$max = (isset($_GET['s']) && isset($_GET['p']))?50:30;

$d=(!empty($_REQUEST['distance']))?min($max,intval(stripslashes($_REQUEST['distance']))):5;
		
$type=(isset($_REQUEST['type']))?stripslashes($_REQUEST['type']):'few';
switch($type) {
	case 'with': $typename = 'with'; $crit = 'imagecount>0'; break;
	case 'few': $typename = 'with few'; $crit = 'imagecount<2 and (percent_land > 0 || imagecount>1)'; break;
	default: $type = $typename = 'without'; $crit = 'imagecount=0 and percent_land > 0'; break;
}
	
$smarty->assign('d', $d);
$smarty->assign('type', $type);
			

$square=new GridSquare;
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
} elseif (isset($_REQUEST['gridref'])) {
	$grid_ok=$square->setByFullGridRef($_REQUEST['gridref']);
}

if ($grid_ok)
{
	$cacheid = 'squares|'.$square->grid_reference.'-'.$type.'-'.($d).'-'.isset($_GET['s']);

	//regenerate?
	if (!$smarty->is_cached($template, $cacheid))
	{
		$searchdesc = "squares within {$d}km of {$square->grid_reference} $typename photographs";

		$x = $square->x;
		$y = $square->y;

		$sql_where = $crit.' and ';

		$left=$x-$d;
		$right=$x+$d;
		$top=$y+$d;
		$bottom=$y-$d;

		$rectangle = "'POLYGON(($left $bottom,$right $bottom,$right $top,$left $top,$left $bottom))'";

		$sql_where .= "CONTAINS(GeomFromText($rectangle),point_xy)";
		
		if (empty($_GET['s'])) {
			//shame cant use dist_sqd in the next line!
			$sql_where .= " and ((gs.x - $x) * (gs.x - $x) + (gs.y - $y) * (gs.y - $y)) < ".($d*$d);
		}

		$sql_fields .= ", ((gs.x - $x) * (gs.x - $x) + (gs.y - $y) * (gs.y - $y)) as dist_sqd";
		$sql_order = ' dist_sqd ';


		$sql = "SELECT grid_reference as id,grid_reference,x,y,imagecount $sql_fields
		FROM gridsquare gs
		WHERE $sql_where
		ORDER BY $sql_order
		LIMIT 250"; ##limt just to make sure

		$db=NewADOConnection($GLOBALS['DSN']);
		if (!$db) die('Database connection failed');  

		$data = $db->getAssoc($sql);

		if (($CONF['use_gazetteer'] == 'OS' || $CONF['use_gazetteer'] == 'OS250') && $square->reference_index == 1) {
			$square->getNatEastings();
			$gaz = new Gazetteer();
			$places = $gaz->findListByNational($square->reference_index,$square->nateastings,$square->natnorthings,$d*1000);	

			foreach ($places as $i => $place) {
				if (isset($data[$place['grid_reference']]) && !isset($data[$place['grid_reference']]['place'])) {
					$place['distance'] = 0;
					$place['reference_name'] = $CONF['references'][$square->reference_index];
					$data[$place['grid_reference']]['place'] = $place;
				}
			}
		}


		$smarty->assign_by_ref('data', $data);
		$smarty->assign_by_ref('searchdesc', $searchdesc);

		//geotag the page	
		require_once('geograph/conversions.class.php');
		$conv = new Conversions;
		list($lat,$long) = $conv->gridsquare_to_wgs84($square);
		$smarty->assign('lat', $lat);
		$smarty->assign('long', $long);
		$smarty->assign_by_ref('square', $square);

		//get a token to show a suroudding geograph map
		$mosaic=new GeographMapMosaic;
		$smarty->assign('map_token', $mosaic->getGridSquareToken($square));

		//lets add an overview map too
		$overview=new GeographMapMosaic('largeoverview');
		$overview->setCentre($square->x,$square->y); //does call setAlignedOrigin

		#$smarty->assign('marker', $overview->getSquarePoint($square));
		//actully lets make it the approximate size!
		$mosaic=new GeographMapMosaic('overview');
		$mosaic->setMosaicSize($d*2,$d*2);
		$mosaic->setScale($overview->pixels_per_km);
		#$mosaic->setCentre($square->x,$square->y); //does call setAlignedOrigin which we dont want!	
		$mosaic->setOrigin($square->x-$d,$square->y-$d);
		$smarty->assign('marker', $overview->getBoundingBox($mosaic));

		$overview->assignToSmarty($smarty, 'overview');
	} 
} 


$smarty->display($template, $cacheid);

	
?>
