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

$date = (isset($_GET['date']) && ctype_lower($_GET['date']))?$_GET['date']:'submitted';

$myriad = (isset($_GET['myriad']) && ctype_upper($_GET['myriad']))?$_GET['myriad']:'';


$cacheid='statistics|overtime'.isset($_GET['month']).isset($_GET['week']).$date.'.'.$ri.'.'.$u.'.'.$myriad;

if (!$smarty->is_cached($template, $cacheid))
{
	require_once('geograph/gridimage.class.php');
	require_once('geograph/gridsquare.class.php');
	require_once('geograph/imagelist.class.php');

        $db = GeographDatabaseConnection(true);

	$column = ($date == 'taken')?'imagetaken':'submitted';  
	
	if (isset($_GET['week'])) {
		$from_date = "date(min($column))";
		$group_date = "yearweek($column,1)";
	} else {
		$length = isset($_GET['month'])?10:7;  //month=0 means daily ;-0

		$from_date = "substring( $column, 1, $length )";
		$group_date = "substring( $column, 1, $length )";
	}
	$title = ($date == 'taken')?'Taken':'Submitted'; 
	$title = "Breakdown of Images by $title Date";
	
	$where = array();
	
	if ($date == 'taken') {
		$where[] = "$column not like '%-00%'";
	}
	
	if ($myriad) {
		$where[] = "grid_reference like '$myriad%'";
		$title = "in myriad '$myriad'";
	}
	
	if (!empty($ri)) {
		$where[] = "reference_index=".$ri;
		$smarty->assign('ri', $ri);
	

		$letterlength = 3 - $ri; #should this be auto-realised by selecting a item from gridprefix?
		$columns_sql .= ", count( DISTINCT SUBSTRING(grid_reference,1,$letterlength)) as `Different Myriads`";
		
		$columns_sql .= ", count( DISTINCT concat(substring(grid_reference,1,".($letterlength+1)."),substring(grid_reference,".($letterlength+3).",1)) ) as `Different Hectads`";
		
	} else {
		$columns_sql .= ", count( DISTINCT SUBSTRING(grid_reference,1,3 - reference_index)) as `Different Myriads`";
		$columns_sql .= ", count( DISTINCT concat(substring(grid_reference,1,3 - reference_index),substring(grid_reference,6 - reference_index,1)) ) as `Different Hectads`";
	}	 
	

	if (!empty($u)) {
		$where[] = "user_id=".$u;
		$smarty->assign('u', $u);

		$profile=new GeographUser($u);
		$smarty->assign_by_ref('profile', $profile);
		$title .= " for ".($profile->realname);
	} else {
		$columns_sql .= ", count( DISTINCT user_id ) AS `Different Users`";
	}
	if (count($where))
		$where_sql = " WHERE ".join(' AND ',$where);
		
		
	 $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$table=$db->GetAll("SELECT 
	$from_date AS `Date`, 
	count( * ) AS `Images`, 
	sum( moderation_status = 'geograph' ) AS `Geographs`, 
	sum( ftf =1 ) AS `Points Awarded`, 
	count( * ) / count( DISTINCT grid_reference ) AS `Depth`,
	count( DISTINCT grid_reference ) AS `Different Gridsquares`
	$columns_sql
	, count( DISTINCT imageclass ) AS `Different Categories`
	
FROM `gridimage_search` $where_sql
GROUP BY $group_date" );

	if (!isset($_GET['output']) || $_GET['output'] != 'csv') 
	{
		foreach($table as $idx=>$entry)
		{
			$table[$idx]['Date'] = getFormattedDate($table[$idx]['Date']);
		}
	}
	
	$smarty->assign_by_ref('table', $table);
	
	$smarty->assign("h2title",$title);
	$smarty->assign("total",count($table));
	$smarty->assign_by_ref('references',$CONF['references_all']);	
	
	if ($date == 'submitted') {
		$smarty->assign("footnote","<p><a href=\"http://www.swivel.com/data_sets/show/1009608\" target=\"_blank\">Graphs compiled from this data</a></p>");
	}
	
	$extra = array();
	foreach (array('month','week','date') as $key) {
		if (isset($_GET[$key])) {
			$extra[$key] = $_GET[$key];
		}
	}
	$smarty->assign_by_ref('extra',$extra);	
} else {
	if ($u) {
		$profile=new GeographUser($u);
		$smarty->assign_by_ref('profile', $profile);
		$smarty->assign_by_ref('u', $u);
	}
}
$smarty->assign("filter",2);

$smarty->display($template, $cacheid);

	
?>
