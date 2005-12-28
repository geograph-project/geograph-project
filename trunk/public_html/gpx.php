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
require_once('geograph/searchcriteria.class.php');
require_once('geograph/searchengine.class.php');
require_once('geograph/imagelist.class.php');
require_once('geograph/gridsquare.class.php');
require_once('geograph/gridimage.class.php');

init_session();

$smarty = new GeographPage;

$template='gpx.tpl';
$cacheid = '';

if (isset($_GET['id']))  {
	$image=new GridImage;
	
	$ok = $image->loadFromId($_GET['id'],true);

	if ($ok) {
		header("Content-type: application/octet-stream");
		header("Content-Disposition: attachment; filename=\"Geograph{$image->gridimage_id}.gpx\"");
		header("Cache-Control: Public");
		header("Expires: ".date("D, d M Y H:i:s",mktime(0,0,0,date('m'),date('d')+14,date('Y')) )." GMT");
		
		//todo output gpx
		$cacheid = $image->gridimage_id;
		
		exit;
	} else {
		
	}
}		


	
	if (isset($_REQUEST['submit'])) {
		$square=new GridSquare;
		$grid_ok=$square->setByFullGridRef($_REQUEST['gridref']);
		
		if ($grid_ok)
		{
			$d = intval(stripslashes($_REQUEST['distance']));
				
			$template='gpx_download_gpx.tpl';
			$cacheid = $square->grid_reference.'-'.($_REQUEST['type'] == 'with').'-'.($d);
		
			//regenerate?
			if (!$smarty->is_cached($template, $cacheid))
			{
				$searchdesc = "squares within {$d}km of {$square->grid_reference} ".(($_REQUEST['type'] == 'with')?'with':'without')." photographs";
				
				$x = $square->x;
				$y = $square->y;
				
				$sql_where = "imagecount ".(($_REQUEST['type'] == 'with')?'>':'=')." 0 and ";
				
				$sql_where .= sprintf('x BETWEEN %d and %d AND y BETWEEN %d and %d',$x-$d,$x+$d,$y-$d,$y+$d);
				//shame cant use dist_sqd in the next line!
				$sql_where .= " and ((gs.x - $x) * (gs.x - $x) + (gs.y - $y) * (gs.y - $y)) < ".($d*$d);

				$sql_fields .= ", ((gs.x - $x) * (gs.x - $x) + (gs.y - $y) * (gs.y - $y)) as dist_sqd";
				$sql_order = ' dist_sqd ';

				
				$sql = "SELECT grid_reference,x,y,imagecount $sql_fields
				FROM gridsquare gs
				WHERE $sql_where
				ORDER BY $sql_order";
				
				$db=NewADOConnection($GLOBALS['DSN']);
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
			header("Cache-Control: Public");
			header("Expires: ".date("D, d M Y H:i:s",mktime(0,0,0,date('m'),date('d')+14,date('Y')) )." GMT");

			$smarty->display($template, $cacheid);
			exit;
		}
		else
		{
			//preserve the input at least
			$smarty->assign('gridref', stripslashes($_REQUEST['gridref']));
			$smarty->assign('distance', stripslashes($_REQUEST['distance']));
			$smarty->assign('type', stripslashes($_REQUEST['type']));
		
			$smarty->assign('errormsg', $square->errormsg);	
		}
		
				
	
		
	} else {
		$smarty->assign('distance', 5);
		$smarty->assign('type', 'without');
		if (isset($_REQUEST['gridref'])) {
			$smarty->assign('gridref', stripslashes($_REQUEST['gridref']));
		}
	}
	$smarty->assign('distances', array(1,3,5,10,15,20));
		
//lets find some recent photos
new RecentImageList($smarty);



$smarty->display($template, $cacheid);

	
?>
