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

if (isset($_GET['by']) && preg_match('/^\w+$/' , $_GET['by']))
	$by = $_GET['by'];
if (isset($_GET['ri']) && preg_match('/^[0-9]+$/' , $_GET['ri']))
	$ri = intval($_GET['ri']);
if (isset($_GET['u']) && preg_match('/^[0-9]+$/' , $_GET['u']))
	$u = intval($_GET['u']);
if (isset($_GET['order']) && preg_match('/^\w+$/' , $_GET['order']))
	$order = $_GET['order'];

$template='statistics_breakdown.tpl';
$cacheid='statistics|'.$by.'_'.$ri.'_'.$u.'_'.$order;

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*24; //24hr cache

$smarty->assign_by_ref('references',$CONF['references']);	

$bys = array('status' => 'Status','class' => 'Category','takenyear' => 'Date Taken (Year)','taken' => 'Date Taken (Month)','gridsq' => 'Grid Square');
$smarty->assign_by_ref('bys',$bys);

$smarty->assign('by', $by);

if (!$smarty->is_cached($template, $cacheid))
{
	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');  
	#$db->debug = true;
	

		
	if (!$ri)
		$ri = 1;
	$smarty->assign('ri', $ri);
	$letterlength = 3 - $ri; #should this be auto-realised by selecting a item from gridprefix?

	$title .= " in ".$CONF['references'][$ri];
	$andwhere = " and moderation_status <> 'rejected'";
	if ($by == 'status') {
		$sql_group = $sql_fieldname = "CONCAT(moderation_status,ELT(ftf+1, '',' (ftf)'))";
	} else if ($by == 'class') {
		$sql_group = $sql_fieldname = 'imageclass';
		$smarty->assign('linkprefix', "/search.php?".($u?"u=$u&amp;":'')."reference_index=$ri&amp;imageclass=");
	} else if ($by == 'gridsq') {
		$smarty->assign('linkprefix', "/search.php?".($u?"u=$u&amp;":'')."gridsquare=");
		$sql_group = $sql_fieldname = "SUBSTRING(grid_reference,1,$letterlength)";
	} else if ($by == 'taken') {
		$smarty->assign('linkpro', 1);
		$sql_group = $sql_fieldname = "SUBSTRING(imagetaken,1,7)";
	} else if ($by == 'takenyear') {
		$smarty->assign('linkpro', 1);
		$sql_group = $sql_fieldname = "SUBSTRING(imagetaken,1,4)";
	} else if ($by == 'count') {
		$sql_group = $sql_fieldname = "imagecount";
	} else {
		$by = 'status';
		$sql_group = $sql_fieldname = 'moderation_status';
		$andwhere = ''; #do want to see rejected in this query!
	}

	$smarty->assign('title', $bys[$by]);

	$title = "Breakdown of Photos by ".$bys[$by];

	$link .= "by=$by&amp;ri=$ri";

	if ($u) {
		$user_crit = " and user_id = $u";
		$link .= "&amp;u=$u";
		$smarty->assign_by_ref('u', $u);


		$profile=new GeographUser($u);
		$smarty->assign_by_ref('profile', $profile);
		$title .= " for ".($profile->realname);
	} 
	$smarty->assign_by_ref('link', $link);
	$smarty->assign_by_ref('h2title', $title);



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
	if ($total > 0) {
		$totalperc = 100 /$total;

		foreach($breakdown as $idx=>$entry)
		{
			$breakdown[$idx]['per'] = sprintf("%.2f",$breakdown[$idx]['c'] * $totalperc);
		}

		if ($by == 'status') {
			$friendly = array('rejected' => 'Rejected', 'pending' => 'Pending', 'geograph (ftf)' => 'Geograph (First)', 'accepted' => 'Supplemental', 'geograph' => 'Geograph');
			foreach($breakdown as $idx=>$entry) {
				$breakdown[$idx]['field'] = $friendly[$breakdown[$idx]['field']];
			}
		} elseif ($by == 'takenyear') {
			foreach($breakdown as $idx=>$entry) {
				$y = $breakdown[$idx]['field'];

				$breakdown[$idx]['link'] = "/search.php?".($u?"u=$u&amp;":'')."reference_index=$ri&amp;taken_endYear=$y&amp;taken_startYear=$y&amp;orderby=imagetaken&amp;do=1";
				if ($y < 100) {
					$breakdown[$idx]['field'] = ''; //ie unspecified!
				}
			}
		} else if ($by == 'taken') {
			foreach($breakdown as $idx=>$entry) {
				list($y,$m)=explode('-', $breakdown[$idx]['field']);
				
				$breakdown[$idx]['link'] = "/search.php?".($u?"u=$u&amp;":'')."reference_index=$ri&amp;taken_endMonth=$m&amp;taken_endYear=$y&amp;taken_startMonth=$m&amp;taken_startYear=$y&amp;orderby=imagetaken&amp;do=1";
			
				if ($m>0) {
					//well, it saves having an array of months...
					$t=strtotime("2000-$m-01");
					if ($y > 0) {
						$breakdown[$idx]['field']=strftime("%B", $t)." $y";
					} else {
						$breakdown[$idx]['field']=strftime("%B", $t);
					}
				} elseif ($y > 0) {
					$breakdown[$idx]['field']=$y;
				} else {
					$breakdown[$idx]['field'] = ''; //ie unspecified!
				}
				
			}
		}
	}

	$smarty->assign_by_ref('total', $total);
	$smarty->assign_by_ref('breakdown', $breakdown);
		
	$smarty->assign('generation_time', time());
} else {
	//bare minimum for the dynamic section
	if ($u) {
		$profile=new GeographUser($u);
		$smarty->assign_by_ref('profile', $profile);
		$smarty->assign_by_ref('u', $u);
	}
}


$smarty->display($template, $cacheid);

	
?>
