<?php
/**
 * $Project: GeoGraph $
 * $Id: statistics.php 5607 2009-07-09 16:26:03Z hansjorg $
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

if (isset($_GET['by']) && preg_match('/^\w+$/' , $_GET['by'])) {
	header("Location:http://{$_SERVER['HTTP_HOST']}/statistics/breakdown.php?".$_SERVER['QUERY_STRING']);
	exit;
}

require_once('geograph/global.inc.php');
//require_once('geograph/mapmosaic.class.php');
init_session();

$smarty = new GeographPage;

$template='statistics_regions.tpl';

$level = -1;
$cid = -1;
if (count($CONF['hier_statlevels'])) {
	if (isset($_GET['level']) && $_GET['level'] != '') {
		$level = intval($_GET['level']);
		if (!in_array($level, $CONF['hier_statlevels'])) {
			$level = -1;
		}
	} elseif (isset($_GET['region']) &&  preg_match('/^\d+_\d+$/',$_GET['region'])) {
		list($level,$cid) = explode('_',$_GET['region']);
		$level = intval($level);
		$cid = intval($cid);
		if (!in_array($level, $CONF['hier_statlevels']) || $level == max($CONF['hier_statlevels'])) {
			$level = -1;
			$cid = -1;
		}
	}
	if ($level == -1) {
		$level = min($CONF['hier_statlevels']);
	}
}

$cacheid="statistics|regionsi|$level|$cid";

if ($smarty->caching) {
	$smarty->caching = 2; // lifetime is per cache
	$smarty->cache_lifetime = 3600/4; //15min cache
}

if (!$smarty->is_cached($template, $cacheid))
{
	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');  
	#$db->debug = true;
	

	require_once('geograph/gridsquare.class.php');


	$regionname = '';
	$linkify = false;
	if (count($CONF['hier_statlevels'])) {
		if ($cid == -1) { // every region of the given level
			$sql = "select name,level,community_id,squares_total,images_total,squares_submitted,tenk_total,geographs_submitted,tenk_submitted,images_thisweek from loc_hier left join loc_hier_stat using (level,community_id) where level=$level order by name";
			$hstats = $db->GetAll($sql);
			if ($hstats === false)
				$hstats = array();
			else if ($level != max($CONF['hier_statlevels']))
				$linkify = true;
		} else { // every region one level below the given level inside the region given by level&cid
			$nextlevel = max($CONF['hier_statlevels']);
			foreach ($CONF['hier_statlevels'] as $curlevel) {
				if ($curlevel > $level && $curlevel < $nextlevel)
					$nextlevel = $curlevel;
			}
			$regioninfo = $db->getRow("select name,contains_cid_min,contains_cid_max from loc_hier where level=$level and community_id=$cid");
			if ($regioninfo !== false and count($regioninfo)) {
				$sql = "select name,level,community_id,squares_total,images_total,squares_submitted,tenk_total,geographs_submitted,tenk_submitted,images_thisweek from loc_hier left join loc_hier_stat using (level,community_id) where level=$nextlevel and community_id between {$regioninfo['contains_cid_min']} and {$regioninfo['contains_cid_max']} order by name";
				$hstats = $db->GetAll($sql);
				if ($hstats === false)
					$hstats = array();
				else {
					$regionname = $regioninfo['name'];
					if ($nextlevel != max($CONF['hier_statlevels']))
						$linkify = true;
				}
			} else {
				$hstats = array();
			}
		}
	} else {
		$hstats = array();
	}


	foreach ($hstats as &$row) {
		$level=$row['level'];
		$shortname = $row['name'];
		$prefix = '';
		$prefixes = $CONF['hier_prefix']; #array(5=>"Regierungsbezirk", 6=>"Region", 7=>"Kreis");
		if (isset($prefixes[$level])) {
			$curpref = $prefixes[$level].' ';
			$preflen = strlen($curpref);
			if (strlen($shortname) >= $preflen && substr($shortname, 0, $preflen) == $curpref) {
				$prefix = $prefixes[$level];
				$shortname = substr($shortname, $preflen);
			}
		}
		$row += array('prefix' => $prefix);
		$row += array('shortname' => $shortname);

		$sqtotal = $row['squares_total'];
		$percentage = $sqtotal == 0 ? 0.0 : $row['squares_submitted'] / $sqtotal * 100;
		$row += array('percent' => $percentage);
		$percentage = $sqtotal == 0 ? 0.0 : $row['geographs_submitted'] / $sqtotal * 100;
		$row += array('geopercent' => $percentage);
	}

	function compare_shortname($a, $b)
	{
		return strnatcmp($a['shortname'], $b['shortname']);
	}
	usort($hstats, 'compare_shortname');
	$smarty->assign("hstats", $hstats);
	$smarty->assign("linkify", $linkify);
	$smarty->assign("regionname", $regionname);
} 


$smarty->display($template, $cacheid);

?>
