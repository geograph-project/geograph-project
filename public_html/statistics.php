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
init_session();

$smarty = new GeographPage;

$by = $_GET['by'];
$ri = intval($_GET['ri']);
$u = intval($_GET['u']);
$order = $_GET['order'];

$template='statistics.tpl';
$cacheid='statistics|'.$by.'_'.$ri.'_'.$u.'_'.$order;

if (!$smarty->is_cached($template, $cacheid))
{
	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');  
	#$db->debug = true;
	$smarty->assign('references',array(1 => 'Great Britain',2 => 'Ireland'));
	

	$bys = array('type' => 'Image Type','class' => 'Category','gridsq' => 'Grid Square');

	$smarty->assign_by_ref('bys',$bys);
	
	$smarty->assign('by', $by);
	
	
	if ($by) {
		$smarty->assign('title', $bys[$by]);
		if (!$ri)
			$ri = 1;
		$smarty->assign('ri', $ri);
		$letterlength = 3 - $ri; #should this be auto-realised by selecting a item from gridprefix?
		
		if ($by == 'type') {
			$sql_group = $sql_fieldname = 'moderation_status';
		} else if ($by == 'class') {
			$sql_group = $sql_fieldname = 'imageclass';
		} else if ($by == 'gridsq') {
			$sql_group = $sql_fieldname = "SUBSTRING(grid_reference,1,$letterlength)";
		}
		
		$link .= "by=$by&amp;ri=$ri";
		
		if ($u) {
			$user_crit = " and user_id = $u";
			$link = "&amp;u=$u";
			$smarty->assign_by_ref('u', $u);
		}
		$smarty->assign_by_ref('link', $link);
		
		
		
		if (strpos($order,'2') !== FALSE) {
			$sql_dir = " DESC";
		} else {
		    $no .= "2";
		}
		$smarty->assign_by_ref('no', $no);
		
		if (strpos($order,'c') !== FALSE) {
			$sql_order = "ORDER BY c $sql_dir";
		} else {
			$sql_order = "ORDER BY field $sql_dir";
		}
		
$sql = "select 
$sql_fieldname as field,
count(distinct(gridimage_id)) as c 
from gridimage inner join gridsquare using (gridsquare_id) 
where reference_index = $ri $user_crit
$andwhere
group by $sql_group 
$sql_order";
		
		$breakdown=$db->GetAll($sql);
		
		foreach($breakdown as $idx=>$entry) {
			$total += $breakdown[$idx]['c'];
		}
		$totalperc = 100 /$total;
		
		foreach($breakdown as $idx=>$entry)
		{
			$breakdown[$idx]['per'] = sprintf("%.2f",$breakdown[$idx]['c'] * $totalperc);
			if (!$breakdown[$idx]['field'])
				$breakdown[$idx]['field'] = "<i>-unspecified-</i>";
		}
		
		if ($by == 'type') {
			$friendly = array('rejected' => 'Rejected', 'pending' => 'Pending', 'accepted' => 'Supplemental', 'geograph' => 'Geograph');
			foreach($breakdown as $idx=>$entry) {
				$breakdown[$idx]['field'] = $friendly[$breakdown[$idx]['field']];
			}
		}
		
		$smarty->assign_by_ref('total', $total);
		$smarty->assign_by_ref('breakdown', $breakdown);
	} else {

		

		$smarty->assign('users_submitted',  $db->GetOne("select count(distinct user_id) from gridimage"));
		$smarty->assign('users_total',  $db->GetOne("select count(*) from user where rights>0"));
		$smarty->assign('users_thisweek',  $db->GetOne("select count(*) from user where rights>0 and (unix_timestamp(now())-unix_timestamp(signup_date))<604800"));

		$smarty->assign("images_ftf",  $db->GetOne("select count(*) from gridimage where ftf = 1"));


		foreach (array(1,2) as $ri) {
			$letterlength = 3 - $ri; #should this be auto-realised by selecting a item from gridprefix?
			
			$smarty->assign("images_total_$ri",  $db->GetOne("select count(*) from gridimage inner join gridsquare using (gridsquare_id) where reference_index = $ri"));
			$smarty->assign("images_thisweek_$ri",  $db->GetOne("select count(*) from gridimage inner join gridsquare using (gridsquare_id) where reference_index = $ri and (unix_timestamp(now())-unix_timestamp(submitted))<604800"));
			
			$smarty->assign("squares_total_$ri",  $db->CacheGetOne(100*24*3600,"select count(*) from gridsquare where reference_index = $ri and percent_land > 0"));
			$smarty->assign("squares_submitted_$ri",  $db->GetOne("select count(*) from gridsquare where reference_index = $ri and imagecount > 0"));

			$smarty->assign("geographs_submitted_$ri",  $db->GetOne("select count(*) from gridsquare where reference_index = $ri and has_geographs > 0"));

			$smarty->assign("grid_total_$ri",  $db->CacheGetOne(100*24*3600,"select count(*) from gridprefix where reference_index = $ri and landcount > 0"));
			$smarty->assign("grid_submitted_$ri",  $db->GetOne("select count(distinct substring(grid_reference,1,$letterlength)) from gridimage inner join gridsquare using (gridsquare_id) where reference_index = $ri"));
		}
	}
	$smarty->assign("gentime",date("D, d M Y H:i:s"));
}


$smarty->display($template, $cacheid);

	
?>
