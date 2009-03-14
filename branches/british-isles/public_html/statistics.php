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

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*3; //3hr cache

$smarty->assign_by_ref('references',$CONF['references_all']);		

$bys = array('status' => 'Classification','class' => 'Category','takenyear' => 'Date Taken','gridsq' => 'Myriad');
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
	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');  
	#$db->debug = true;
	

	require_once('geograph/gridsquare.class.php');


	$smarty->assign('users_submitted',  $db->GetOne("select count(*)-1 from user_stat"));
	
	$smarty->assign('users_thisweek',  $db->GetOne("select count(*) from user where rights>0 and (unix_timestamp(now())-unix_timestamp(signup_date))<604800"));

	$smarty->assign("images_ftf",  $db->GetOne("select points from user_stat where user_id = 0"));


	//lets add an overview map too
	$overview=new GeographMapMosaic('overview');
	$overview->assignToSmarty($smarty, 'overview');
	
	foreach (array(1,2) as $ri) {
		$letterlength = 3 - $ri; #should this be auto-realised by selecting a item from gridprefix?


		$smarty->assign("images_thisweek_$ri",  $db->CacheGetOne(3*3600,"select count(*) from gridimage_search where reference_index = $ri and (unix_timestamp(now())-unix_timestamp(submitted))<604800"));

		$stats= $db->CacheGetRow(3*3600,"select 
			count(*) as squares_total,
			sum(imagecount) as images_total,
			sum(imagecount > 0) as squares_submitted,
			count(distinct concat(substring(grid_reference,1,".($letterlength+1)."),substring(grid_reference,".($letterlength+3).",1))) as tenk_total
		from gridsquare 
		where reference_index = $ri and percent_land > 0");

		$stats += $db->CacheGetRow(3*3600,"select 
			count(*) as geographs_submitted,
			count(distinct substring(grid_reference,1,$letterlength)) as grid_submitted,
			count(distinct concat(substring(grid_reference,1,".($letterlength+1)."),substring(grid_reference,".($letterlength+3).",1))) as tenk_submitted,
			avg( x ) as x,
			avg( y ) as y
		from gridsquare 
		where reference_index = $ri and percent_land > 0 and has_geographs > 0");

		foreach ($stats as $key => $value) {
			$smarty->assign("{$key}_$ri",$value);
		}

		$smarty->assign("grid_total_$ri",  $db->CacheGetOne(24*3600,"select count(*) from gridprefix where reference_index = $ri and landcount > 0"));


		$censquare = new GridSquare;
		$ok = $censquare->loadFromPosition(intval($stats['x']),intval($stats['y']));

		if ($ok) {
			$smarty->assign("centergr_$ri",$censquare->grid_reference);

			//find a possible place within 35km
			$smarty->assign("place_$ri", $censquare->findNearestPlace(35000));
			$smarty->assign("marker_$ri", $overview->getSquarePoint($censquare));
		} else {
			$smarty->assign("centergr_$ri",'unknown');
		}
	}
	foreach (array('images_total','images_thisweek','squares_total','squares_submitted','tenk_total','tenk_submitted','geographs_submitted') as $name) {
		$smarty->assign($name.'_both',$smarty->get_template_vars($name.'_1')+$smarty->get_template_vars($name.'_2'));
	}
	foreach (array('both','1','2') as $name) {
		$smarty->assign('percent_'.$name,sprintf("%.3f",$smarty->get_template_vars('squares_submitted_'.$name)/$smarty->get_template_vars('squares_total_'.$name)*100));
	}
} 


$smarty->display($template, $cacheid);

	
?>
