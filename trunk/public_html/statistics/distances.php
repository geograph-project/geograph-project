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

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*24; //24hr cache

if (isset($_GET['output']) && $_GET['output'] == 'csv') {
	$template='statistics_table_csv.tpl';
	# let the browser know what's coming
	header("Content-type: application/octet-stream");
	header("Content-Disposition: attachment; filename=\"".basename($_SERVER['SCRIPT_NAME'],'.php').".csv\"");

} else {
	$template='statistics_table.tpl';
}

$ri = (isset($_GET['ri']) && is_numeric($_GET['ri']))?intval($_GET['ri']):0;

$u = (isset($_GET['u']) && is_numeric($_GET['u']))?intval($_GET['u']):0;

$date = (isset($_GET['date']) && preg_match('/^\d{4}(-\d{2}|)(-\d{2}|)$/',$_GET['date']))?$_GET['date']:'2005-12';


$cacheid='statistics|distances'.$date.'.'.$ri.'.'.$u;

if (!$smarty->is_cached($template, $cacheid))
{
	dieUnderHighLoad();
	
	require_once('geograph/gridimage.class.php');
	require_once('geograph/gridsquare.class.php');
	require_once('geograph/imagelist.class.php');

	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');  
	
	$title = "Distances Travelled in a Day";

	$where = array();

	if (!empty($u)) {
		$where[] = 'user_id='.$u;
		$smarty->assign('u', $u);

		$profile=new GeographUser($u);
		$smarty->assign_by_ref('profile', $profile);
		$title .= ' for '.($profile->realname);
	} else {
		$columns_sql = "CONCAT('<a href=\"/profile/',user_id,'\">',realname,'</a>') as User,";
	}
	
	if ($ri) {
		$where[] = "reference_index = $ri";
		$where2 = "and reference_index = $ri";
		$smarty->assign('ri',$ri);
		$title .= " in ".$CONF['references_all'][$ri];
	} else {
		$where2 = "";
	}
	
	if (!empty($date)) {
		if (strlen($date)==10) {
			$where[] = "imagetaken='$date'";
		} else {
			$where[] = "imagetaken LIKE '$date%'";
			$where[] = "imagetaken not like '%-00%'";
			$columns_sql .= 'imagetaken as `Date`,';
		}
		$smarty->assign('date', $date);
		if ($date)
			$title .= (strlen($date)==10?' on ':' in ').getFormattedDate($date);
	} else {
		$where[] = "imagetaken not like '%-00%'";
		$columns_sql .= 'imagetaken as `Date`,';
	}
	$where[] = "moderation_status = 'geograph'";
	$where[] = "ftf =1";
		
	if (count($where))
		$where_sql = " WHERE ".join(' AND ',$where);
		
		
	 $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$table=$db->GetAll("SELECT 
	$columns_sql
	count( * ) AS `Images`
	,user_id,imagetaken
	FROM `gridimage_search` $where_sql
	GROUP BY imagetaken,user_id 
	HAVING `Images` > 1" );
	
	foreach($table as $idx=>$entry)
	{
		$rows =$db->getAll("SELECT
		x,y,gridimage_id
		FROM gridimage_search
		WHERE moderation_status = 'geograph' 
		and ftf =1 
		and user_id = {$entry['user_id']}
		and imagetaken = '{$entry['imagetaken']}'
		$where2
		ORDER BY gridimage_id");
		
		$last = false;
		$total = 0;
		$longest_sq = 0;
		$done = array();
		foreach ($rows as $id => $row) {
			if ($last) {
				$dist_sq = pow($row['x'] - $last['x'],2) + pow($row['y'] - $last['y'],2);
				$total += sqrt($dist_sq);
			} else {
				$last = $row;
			}
			foreach ($rows as $id2 => $row2) {
				if ($id != $id2 && !isset($done["$id2.$id"])) {
					$dist_sq = pow($row['x'] - $row2['x'],2) + pow($row['y'] - $row2['y'],2);
					if ($longest_sq < $dist_sq) {
						$longest_sq = $dist_sq;
						$longest_ids = array($row['gridimage_id'],$row2['gridimage_id']);
					}
					$done["$id.$id2"]=1;
				}
			}
		}
		$table[$idx]['Greatest Seperation (km)'] = number_format(sqrt($longest_sq));
		$table[$idx]['Greatest Seperation Images'] = "<a href=\"/photo/".implode("\">.</a> <a href=\"/photo/",$longest_ids)."\">.</a>";
		$table[$idx]['Distance Travelled (km)'] = number_format($total);
		

	}
	
	function cmp($a,$b) {
		if ($a['Greatest Seperation (km)'] == $b['Greatest Seperation (km)'])
			return 0;
		return ($a['Greatest Seperation (km)'] > $b['Greatest Seperation (km)'])?-1:1;
	}
	uasort($table,"cmp");
	
	$i=0;
	foreach($table as $idx=>$entry)
	{
		if ($i > 100 || $entry['Distance Travelled (km)'] == 0) {
			unset($table[$idx]);		
		} else {
			if ($entry['Date'])
				$table[$idx]['Date'] = getFormattedDate($entry['Date']);
			unset($table[$idx]['user_id']);
			unset($table[$idx]['imagetaken']);	
			$i++;
		}
	}
	$table = array_merge($table);
	$smarty->assign_by_ref('table', $table);
	
	$smarty->assign("h2title",$title);
	$smarty->assign("total",count($table));
#	$smarty->assign_by_ref('references',$CONF['references_all']);	
	
	$extra = array();
	foreach (array('month','week','date') as $key) {
		if (isset($_GET[$key])) {
			$extra[$key] = $_GET[$key];
		}
	}
	$smarty->assign_by_ref('extra',$extra);	
}
	$smarty->assign('filter',2);	


$smarty->display($template, $cacheid);

	
?>
