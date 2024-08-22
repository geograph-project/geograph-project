<?php
/**
 * $Project: GeoGraph $
 * $Id: gpx.php 7525 2011-12-10 15:50:49Z barry $
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

foreach(array('distance','gridref','submit','type') as $key)
	if (!empty($_REQUEST[$key]) && !preg_match('/^[\w \.>]*$/',$_REQUEST[$key])) {
	     header('HTTP/1.0 451 Unavailable For Legal Reasons');
	     exit;
	}

require_once('geograph/global.inc.php');
require_once('geograph/searchcriteria.class.php');
require_once('geograph/searchengine.class.php');
require_once('geograph/imagelist.class.php');
require_once('geograph/gridsquare.class.php');
require_once('geograph/gridimage.class.php');

init_session();

$smarty = new GeographPage;

$template='gpx.tpl';
$cacheid = '';

$types = array(
	'all'=>'regardless of',
	'with'=>'with',
	'few'=>'with few',
	'nogeos'=>'without geograph',
	'norecent'=>'with no recent',
	'without'=>'with no'
);

	if (isset($_REQUEST['submit'])) {
		$d=(!empty($_REQUEST['distance']))?min(100,intval(stripslashes($_REQUEST['distance']))):5;

		$type=(isset($_REQUEST['type']))?stripslashes($_REQUEST['type']):'few';
		switch($type) {
			case 'all': $crit = 'percent_land>0'; $d = min($d,10); break;
			case 'withgeos': $crit = 'has_geographs>0'; break;
			case 'with': $crit = 'imagecount>0'; break;
			case 'few': $crit = 'imagecount<2 and (percent_land > 0 || imagecount>1)'; break;
			case 'nogeos': $crit = 'has_geographs=0 and percent_land > 0'; break;
			case 'recent': $crit = 'percent_land > 0 and has_recent=1'; break;
			case 'norecent': $crit = 'percent_land > 0 and has_recent=0'; break;
			default: $type = 'without'; $crit = 'imagecount=0 and percent_land > 0'; break;
		}
		$typename = $types[$type];

		$square=new GridSquare;
		if (!empty($_REQUEST['ll']) && preg_match("/\b(-?\d+\.?\d*)[, ]+(-?\d+\.?\d*)\b/",$_REQUEST['ll'],$ll)) {
			$conv = new Conversions;
			list($x,$y,$reference_index) = $conv->wgs84_to_internal($ll[1],$ll[2]);
			$grid_ok=$square->loadFromPosition($x, $y, true);
		} else {
			$grid_ok=$square->setByFullGridRef($_REQUEST['gridref']);
		}

		if ($grid_ok)
		{
			$template='gpx_download_gpx.tpl';
			$cacheid = $square->grid_reference.'-'.($type).'-'.($d);

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

				//shame cant use dist_sqd in the next line!
				$sql_where .= " and ((gs.x - $x) * (gs.x - $x) + (gs.y - $y) * (gs.y - $y)) < ".($d*$d);

				$sql_fields .= ", ((gs.x - $x) * (gs.x - $x) + (gs.y - $y) * (gs.y - $y)) as dist_sqd";
				$sql_order = ' dist_sqd ';

				$sql = "SELECT grid_reference,x,y,imagecount $sql_fields
					FROM gridsquare gs
					WHERE $sql_where
					ORDER BY $sql_order";

				$db=GeographDatabaseConnection(false);
				if (!$db) die('Database connection failed');

				$data = $db->getAll($sql);

				require_once('geograph/conversions.class.php');
				$conv = new Conversions;
				foreach ($data as $q => $row) {
					list($data[$q]['lat'],$data[$q]['long']) = $conv->internal_to_wgs84($row['x'],$row['y']);
				}

				$smarty->assign_by_ref('data', $data);
				$smarty->assign_by_ref('searchdesc', $searchdesc);
			}

			header("Content-type: application/octet-stream");
			header("Content-Disposition: attachment; filename=\"Geograph-$cacheid.gpx\"");
			customExpiresHeader(3600*24*14,true);

			$smarty->display($template, $cacheid);
			exit;
		}
		else
		{
			//preserve the input at least
			$smarty->assign('gridref', stripslashes($_REQUEST['gridref']));
			$smarty->assign('distance', $d);
			$smarty->assign('type', $type);

			$smarty->assign('errormsg', $square->errormsg);
		}

	} else {
		$smarty->assign('distance', 5);
		$smarty->assign('type', 'without');
		if (isset($_REQUEST['gridref'])) {
			$smarty->assign('gridref', stripslashes($_REQUEST['gridref']));
		}
	}
	$smarty->assign('distances', array(1,3,5,10,15,20,30,50,75,100));
	$smarty->assign_by_ref('types', $types);



$smarty->display($template, $cacheid);


