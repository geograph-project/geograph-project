<?php
/**
 * $Project: GeoGraph $
 * $Id: gpx.php 9113 2020-07-13 01:19:39Z hansjorg $
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


#$smarty = new GeographPage;

$template='gpx.tpl';
$cacheid = '';

if (isset($_GET['id']))  {
	init_session();
	$smarty = new GeographPage;

	$image=new GridImage;
	
	$ok = $image->loadFromId($_GET['id'],true);

	if ($ok) {
		//todo non functionional!
		$template='gpx_download_gpx.tpl';
		$cacheid = $image->gridimage_id;
		
		//regenerate?
		if (!$smarty->is_cached($template, $cacheid))
		{
			$searchdesc = "squares within {$d}km of {$square->grid_reference} ".(($_REQUEST['type'] == 'with')?'with':'without')." photographs";


			$sql = "SELECT grid_reference,x,y,imagecount as imgcount $sql_fields
			FROM gridsquare gs
			WHERE $sql_where
			ORDER BY $sql_order";

			$db=GeographDatabaseConnection();

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
		header("Content-Disposition: attachment; filename=\"Geograph{$image->gridimage_id}.gpx\"");
		customExpiresHeader(3600*24*14,true);
		
		$smarty->display($template, $cacheid);
		exit;
		
		
		exit;
	} else {
		
	}
}		


	$db=GeographDatabaseConnection();

	$rlevel=-2;
	$rcid=-1;
	$has_region = false;
	if (isset($_REQUEST['submit'])) {
		$d=(!empty($_REQUEST['distance']))?min(100,intval(stripslashes($_REQUEST['distance']))):5;
				
		$type=(isset($_REQUEST['type']))?stripslashes($_REQUEST['type']):'few';
		$uid = isset($_REQUEST['user']) ? intval($_REQUEST['user']) : 0;
		if (isset($_REQUEST['region']) &&  preg_match('/^\d+_\d+$/',$_REQUEST['region'])) {
			list($rlevel,$rcid) = explode('_',$_REQUEST['region']);
			$rlevel = intval($rlevel);
			$rcid = intval($rcid);
			$has_region = in_array($rlevel, $CONF['hier_statlevels']);
		}
		if ($uid) {
			switch($type) {
				case 'with': $typename = 'with'; $having = 'imgcount>0'; $crit = '1'; break;
				case 'few': $typename = 'with few'; $having = 'imgcount<2'; $crit = '(gs.percent_land > 0 || gs.imagecount > 1)'; break;
				default: $type = $typename = 'without'; $having = 'imgcount=0'; $crit = 'gs.percent_land > 0'; break;
			}
			$imgcount = "sum(IFNULL(moderation_status,'') in ('accepted','geograph')) as imgcount";
			$join = "left join gridimage_search gi on (gi.gridsquare_id=gs.gridsquare_id and gi.user_id='$uid')";
			$group = "group by gs.gridsquare_id";
			$having = 'HAVING '.$having;
		} else {
			switch($type) {
				case 'with': $typename = 'with'; $crit = 'gs.imagecount>0'; break;
				case 'few': $typename = 'with few'; $crit = 'gs.imagecount<2 and (percent_land > 0 || gs.imagecount>1)'; break;
				default: $type = $typename = 'without'; $crit = 'gs.imagecount=0 and percent_land > 0'; break;
			}
			$imgcount = "gs.imagecount as imgcount";
			$join = "";
			$group = "";
			$having = "";
			#$uidwhere = "";
		}
		if ($has_region) {
			$join = "inner join gridsquare_percentage gpr on (gs.gridsquare_id=gpr.gridsquare_id and gpr.percent > 0 and gpr.level=$rlevel and gpr.community_id=$rcid) ".$join;
		}

		$no_grid=false;
		$grid_ok=false;
		$square=new GridSquare;
		if (!empty($_REQUEST['ll']) && preg_match("/\b(-?\d+\.?\d*)[, ]+(-?\d+\.?\d*)\b/",$_REQUEST['ll'],$ll)) {
			$conv = new Conversions;
			list($x,$y,$reference_index) = $conv->wgs84_to_internal($ll[1],$ll[2]);
			$grid_ok=$square->loadFromPosition($x, $y, true);
		} elseif ($has_region && empty($_REQUEST['gridref'])) {
			$no_grid=true;
		} else {
			$grid_ok=$square->setByFullGridRef($_REQUEST['gridref']);
		}
		
		if ($grid_ok||$no_grid)
		{
			$smarty = new GeographPage;
				
			$template='gpx_download_gpx.tpl';
			$cacheid = ($no_grid?'_':$square->grid_reference).'-'.($type).'-'.($no_grid?'_':$d).'-'.($uid).'-'.($rlevel).'_'.($rcid);
		
			//regenerate?
			if (/*true ||*/ !$smarty->is_cached($template, $cacheid))
			{
				$searchdesc = "squares".($no_grid?'':" within {$d}km of {$square->grid_reference}")." $typename photographs";
				if ($uid) {
					$profile = new GeographUser($uid);
					$searchdesc .= " by user {$profile->realname} [#$uid]";
				}
				if ($has_region) {
					$rname = $db->GetOne("select name from loc_hier where level=$rlevel and community_id=$rcid");
					$searchdesc .= " in region {$rname} [$rlevel/$rcid]";
				}
				/*$smarty->caching = 0;*/
				trigger_error(" $cacheid $searchdesc ", E_USER_WARNING);
				if ($no_grid) {
					$sql_where = $crit;
					$sql_order = ' gs.x,gs.y ';
				} else {
					$x = $square->x;
					$y = $square->y;
					
					$sql_where = $crit.' and ';

					$left=$x-$d;
					$right=$x+$d;
					$top=$y+$d;
					$bottom=$y-$d;

					$rectangle = "'POLYGON(($left $bottom,$right $bottom,$right $top,$left $top,$left $bottom))'";

					$sql_where .= "MBRIntersects(ST_GeomFromText($rectangle),gs.point_xy)";
					
					//shame cant use dist_sqd in the next line!
					$sql_where .= " and ((gs.x - $x) * (gs.x - $x) + (gs.y - $y) * (gs.y - $y)) < ".($d*$d); // HAVING?

					$sql_fields .= ", ((gs.x - $x) * (gs.x - $x) + (gs.y - $y) * (gs.y - $y)) as dist_sqd";
					$sql_order = ' dist_sqd ';
				}

				
				$sql = "SELECT gs.grid_reference,gs.x,gs.y,$imgcount $sql_fields
				FROM gridsquare gs $join
				WHERE $sql_where
				$group $having
				ORDER BY $sql_order";
				trigger_error(" $sql ", E_USER_WARNING);
				
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
			init_session();
			$smarty = new GeographPage;

			//preserve the input at least
			$smarty->assign('gridref', stripslashes($_REQUEST['gridref']));
			$smarty->assign('distance', $d);
			$smarty->assign('type', $type);
		
			$smarty->assign('errormsg', $square->errormsg);	
			$smarty->assign('uid', $uid);
		}
		
				
	
		
	} else {
		init_session();
		$smarty = new GeographPage;

		$smarty->assign('distance', 5);
		$smarty->assign('type', 'without');
		if (isset($_REQUEST['gridref'])) {
			$smarty->assign('gridref', stripslashes($_REQUEST['gridref']));
		}
	}
	$smarty->assign('distances', array(1,3,5,10,15,20,30,50,75,100));
	if (count($CONF['hier_statlevels'])) {
		$sql = "select name,level,community_id from loc_hier where level in (".implode(",",$CONF['hier_statlevels']).") order by level,name";
		$regions = $db->GetAll($sql);
		if ($regions === false)
			$regions = array();
	} else {
		$regions = array();
	}
	$smarty->assign("regions", $regions);
	$smarty->assign("rlevel", $has_region?$rlevel:-2);
	$smarty->assign("rcid",   $has_region?$rcid:-1);
	#$smarty->assign("curregion", $has_region?$rlevel.'_'.$rcid:'');
		
//lets find some recent photos
new RecentImageList($smarty);



$smarty->display($template, $cacheid);

	
?>
