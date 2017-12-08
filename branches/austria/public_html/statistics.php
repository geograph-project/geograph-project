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

if (isset($_GET['by']) && preg_match('/^\w+$/' , $_GET['by'])) {
	header("Location:http://{$_SERVER['HTTP_HOST']}/statistics/breakdown.php?".$_SERVER['QUERY_STRING']);
	exit;
}

require_once('geograph/global.inc.php');
require_once('geograph/mapmosaic.class.php');
init_session();

$smarty = new GeographPage;

$template='statistics.tpl';
$cacheid='statistics|main';

if ($smarty->caching) {
	$smarty->caching = 2; // lifetime is per cache
	$smarty->cache_lifetime = 3600*3; //3hr cache
}

$smarty->assign_by_ref('references',$CONF['references_all']);		
$smarty->assign_by_ref('references_real',$CONF['references']);		

if ($CONF['lang'] == 'de')
	$bys = array('status' => 'Klassifizierung','class' => 'Kategorie','takenyear' => 'Aufnahmejahr','taken' => 'Aufnahmemonat','myriad' => '100km-Quadrat','user' => 'Teilnehmer');
else
	$bys = array('status' => 'Classification','class' => 'Category','takenyear' => 'Date Taken (Year)','taken' => 'Date Taken (Month)','myriad' => 'Myriad','user' => 'Contributor');
foreach ($CONF['hier_statlevels'] as $level) {
	$idx = 'level'.$level;
	if (isset($CONF['hier_names'][$level])) {
		$name = $CONF['hier_names'][$level];
	} else {
		$name = $idx;
	}
	$bys[$idx] = $name;
}
$smarty->assign_by_ref('bys',$bys);

$u = (isset($_GET['u']) && is_numeric($_GET['u']))?intval($_GET['u']):0;

	//bare minimum for the dynamic section
	if ($u) {
		$profile=new GeographUser($u);
		$smarty->assign_by_ref('profile', $profile);
		$smarty->assign_by_ref('u', $u);
	}

if (!$smarty->is_cached($template, $cacheid))
{
	$db=GeographDatabaseConnection();
	#$db->debug = true;
	

	require_once('geograph/gridsquare.class.php');

	if (count($CONF['hier_statlevels'])) {
		$smarty->assign('hasregions',true);
		$smarty->assign('regionlistlevel',$CONF['hier_listlevel']);
		$sql = "select name,level,community_id from loc_hier where level in (".implode(",",$CONF['hier_statlevels']).") order by level,name";
		$regions = $db->GetAll($sql);
		if ($regions === false)
			$regions = array();
	} else {
		$regions = array();
	}
	$smarty->assign("regions", $regions);

	$smarty->assign('users_submitted',  $db->GetOne("select count(*)-1 from user_stat"));
	
	$smarty->assign('users_thisweek',  $db->GetOne("select count(*) from user where rights>0 and (unix_timestamp(now())-unix_timestamp(signup_date))<604800"));

	$smarty->assign("images_ftf",  $db->GetOne("select points from user_stat where user_id = 0"));


	//lets add an overview map too
	$overview=new GeographMapMosaic('overview');
	$overview->assignToSmarty($smarty, 'overview');

	$stats=array();
	foreach ($CONF['references_all'] as $ri => $rname) {
		$stats[$ri] = array();
	}
	foreach ($CONF['references'] as $ri => $rname) {
		$letterlength = $CONF['gridpreflen'][$ri];

		# sum(permit_photographs>0) as squares_supp <- not needed as we currently have percent_land>=1 if permit_photographs is set
		$newstats = $db->CacheGetRow(3*3600,"select 
			count(*) as squares_total,
			sum(permit_geographs>0) as squares_geo,
			sum(imagecount) as images_total,
			sum(imagecount > 0) as squares_submitted,
			count(distinct concat(substring(grid_reference,1,".($letterlength+1)."),substring(grid_reference,".($letterlength+3).",1))) as tenk_total
		from gridsquare 
		where reference_index = $ri and percent_land > 0");
		$stats[$ri] = array_merge($stats[$ri], $newstats);

		$newstats = $db->CacheGetRow(3*3600,"select 
			count(*) as geographs_submitted,
			count(distinct substring(grid_reference,1,$letterlength)) as grid_submitted,
			count(distinct concat(substring(grid_reference,1,".($letterlength+1)."),substring(grid_reference,".($letterlength+3).",1))) as tenk_submitted,
			avg( x ) as x,
			avg( y ) as y
		from gridsquare 
		where reference_index = $ri and percent_land > 0 and has_geographs > 0");
		$stats[$ri] = array_merge($stats[$ri], $newstats);

		$stats[$ri] += array('images_thisweek' => $db->CacheGetOne(3*3600,"select count(*) from gridimage_search where reference_index = $ri and (unix_timestamp(now())-unix_timestamp(submitted))<604800"));

		$stats[$ri] += array("grid_total" => $db->CacheGetOne(24*3600,"select count(*) from gridprefix where reference_index = $ri and landcount > 0"));

		$censquare = new GridSquare;
		$ok = $censquare->loadFromPosition(intval($stats[$ri]['x']),intval($stats[$ri]['y']));

		if ($ok) {
			$stats[$ri] += array("centergr" => $censquare->grid_reference);

			//find a possible place within 35km
			$stats[$ri] += array("place" => $censquare->findNearestPlace(35000));
			$stats[$ri] += array("marker" => $overview->getSquarePoint($censquare));
		} else {
			$stats[$ri] += array("centergr" => 'unknown');
		}
	}
	foreach (array('images_total','images_thisweek','squares_total','squares_geo','squares_submitted','tenk_total','tenk_submitted','geographs_submitted','grid_submitted','grid_total') as $name) {
		$sum = 0;
		foreach ($CONF['references'] as $ri => $rname) {
			$sum += $stats[$ri][$name];
		}
		$stats[0] += array($name => $sum);
	}
	foreach ($CONF['references_all'] as $ri => $rname) {
		$sqtotal = $stats[$ri]['squares_total'];
		$percentage = $sqtotal == 0 ? 0.0 : $stats[$ri]['squares_submitted'] / $sqtotal * 100;
		$stats[$ri] += array('percent' => $percentage);
		$sqgeo = $stats[$ri]['squares_geo'];
		$percentage = $sqgeo == 0 ? 0.0 : $stats[$ri]['geographs_submitted'] / $sqgeo * 100;
		$stats[$ri] += array('percent_geo' => $percentage);
	}
	foreach (array('images_total','images_thisweek','squares_total','squares_geo','squares_submitted','tenk_total','tenk_submitted','geographs_submitted','grid_submitted','grid_total','centergr', 'place', 'marker','percent','percent_geo') as $name) {
		$smarty_array = array();
		foreach ($CONF['references_all'] as $ri => $rname) {
			if (array_key_exists($name,$stats[$ri])) {
				$smarty_array[$ri] = $stats[$ri][$name];
			}
		}
		$smarty->assign($name,$smarty_array);
	}
} 


$smarty->display($template, $cacheid);

	
?>
