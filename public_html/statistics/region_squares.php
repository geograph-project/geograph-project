<?php
/**
 * $Project: GeoGraph $
 * $Id: most_geographed.php 5607 2009-07-09 16:26:03Z hansjorg $
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
require_once('geograph/mapmosaic.class.php');
init_session();

$smarty = new GeographPage;


$level = -1;
$cid = -1;

#$types = array('nophoto', 'nogeograph');
if (isset($_GET['type']) && $_GET['type'] === 'nophoto') {
	$type = 'nophoto';
	$title = 'Undocumented squares';
	$where = 'gs.imagecount < 1';
} else {
	$type = 'nogeograph';
	$title = 'Missing geosquares';
	$where = 'gs.has_geographs < 1';
}

if (count($CONF['hier_statlevels'])) {
	if (isset($_GET['region']) && preg_match('/^\d+_\d+$/' , $_GET['region'])) {
		list($level,$cid) = explode('_',$_GET['region']);
		$level = intval($level);
		$cid = intval($cid);
		#if (!in_array($level, $CONF['hier_statlevels']) || $level == max($CONF['hier_statlevels'])) {
		if ($level !== $CONF['hier_listlevel']) {
			$level = -1;
			$cid = -1;
		}
	}
}

$template='statistics_region_squares.tpl';
$cacheid='statistics|region_squares.'.$type.'.'.$level.'.'.$cid;

if ($smarty->caching) {
	$smarty->caching = 2; // lifetime is per cache
	$smarty->cache_lifetime = 3600*2; //2hr cache
}

if (!$smarty->is_cached($template, $cacheid))
{
	$db=GeographDatabaseConnection();
	if (!$db) die('Database connection failed');  
	#$db->debug = true;

	if (count($CONF['hier_statlevels'])) {
		if ($level < 0) {
			/* get list of regions */
			$listlevel = $CONF['hier_listlevel'];
			#$sql = "select community_id,name from loc_hier where level=$listlevel";
			$sql = "select community_id,name,squares_total,squares_submitted,squares_geo,geographs_submitted from loc_hier left join loc_hier_stat using (level,community_id) where level=$listlevel";
			$regions = $db->GetAll($sql);

			$prefixes = $CONF['hier_prefix']; #array(5=>"Regierungsbezirk", 6=>"Region", 7=>"Kreis");
			foreach ($regions as &$row) {
				$shortname = $row['name'];
				$prefix = '';
				if (isset($prefixes[$listlevel])) {
					$curpref = $prefixes[$listlevel].' ';
					$preflen = strlen($curpref);
					if (strlen($shortname) >= $preflen && substr($shortname, 0, $preflen) === $curpref) {
						$prefix = $prefixes[$listlevel];
						$shortname = substr($shortname, $preflen);
					}
				}
				$row += array('prefix' => $prefix);
				$row += array('shortname' => $shortname);
				$row += array('squares_mis' => $row['squares_total']-$row['squares_submitted']);
				$row += array('geosquares_mis' => $row['squares_geo']-$row['geographs_submitted']);
			}
			unset($row);
			function compare_regions($a, $b)
			{
				#global $order, $ordersgn;
				$order = 'shortname';
				$ordersgn = +1;
				return $ordersgn * strnatcmp($a[$order], $b[$order]);
			}
			usort($regions, 'compare_regions');
			$smarty->assign('regions',$regions);
			$smarty->assign('listlevel',$listlevel);
		} else {
			/* get squares */
			$sql = "select gs.grid_reference
				from gridsquare_percentage gp inner join gridsquare gs on (gp.gridsquare_id = gs.gridsquare_id)
				where gp.level=$level and gp.community_id=$cid and gp.percent>0 and $where
				order by gs.grid_reference
				limit 50";
			$squares = $db->GetAll($sql);
			$hiername = $db->GetOne("select name from loc_hier where level=$level and community_id=$cid");
			$smarty->assign('squares',$squares);
			$smarty->assign('squaretitle',$title);
			$smarty->assign('regionname',$hiername);
		}
	} else {
		$listlevel = -1;
		$regions = array();
		$smarty->assign('regions',$regions);
		$smarty->assign('listlevel',$listlevel);
	}
	$smarty->assign('level',$level);
}

$smarty->display($template, $cacheid);

?>
