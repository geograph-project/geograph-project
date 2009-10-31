<?php
/**
 * $Project: GeoGraph $
 * $Id: overtime.php 3514 2007-07-10 21:09:55Z barry $
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

$u = (isset($_GET['u']) && is_numeric($_GET['u']))?intval($_GET['u']):0;

$cacheid='statistics|overtime_ticket'.isset($_GET['month']).isset($_GET['week']).$u;

if (!$smarty->is_cached($template, $cacheid))
{
	//lets hobble this!
	header("HTTP/1.1 503 Service Unavailable");
	$smarty->assign('searchq',stripslashes($_GET['q']));
	$smarty->display('function_disabled.tpl');
	exit;

	require_once('geograph/gridimage.class.php');
	require_once('geograph/gridsquare.class.php');
	require_once('geograph/imagelist.class.php');

        $db = GeographDatabaseConnection(true);

	$column = 'suggested';  
	
	if (isset($_GET['week'])) {
		$from_date = "date(min($column))";
		$group_date = "yearweek($column,1)";
	} else {
		$length = isset($_GET['month'])?10:7;  //month=0 means daily ;-0

		$from_date = "substring( $column, 1, $length )";
		$group_date = "substring( $column, 1, $length )";
	}
	
	$title = "Breakdown of Tickets by $title Date";
	
	$where = array();
	
	if (!empty($u)) {
		$where[] = "gi.user_id=".$u;
		$smarty->assign('u', $u);

		$profile=new GeographUser($u);
		$smarty->assign_by_ref('profile', $profile);
		$title .= " for ".($profile->realname);
	} else {
		$columns_sql .= ", count( DISTINCT gi.user_id ) AS `Different Contributors`";
	}
	
	$where[] = "t.user_id != gi.user_id";
	
	if (count($where))
		$where_sql = " WHERE ".join(' AND ',$where);
		
		
	 $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$table=$db->GetAll("SELECT 
	$from_date AS `Date`, 
	count( * ) AS `Tickets`, 
	count( distinct gi.gridimage_id ) AS `Images`,
	sum(type='minor')/count( * )*100 AS `Percentage Minor`,
	count( DISTINCT t.user_id ) AS `Different Suggestors`
	$columns_sql
FROM gridimage_ticket t INNER JOIN gridimage gi USING (gridimage_id)
$where_sql
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
