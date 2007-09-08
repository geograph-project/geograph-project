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


$cacheid='statistics|overtimeuser'.isset($_GET['month']).isset($_GET['week']);


if (!$smarty->is_cached($template, $cacheid))
{
	require_once('geograph/gridimage.class.php');
	require_once('geograph/gridsquare.class.php');
	require_once('geograph/imagelist.class.php');

	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');  
	$column = 'signup_date';
	if (isset($_GET['week'])) {
		$from_date = "date(min($column))";
		$group_date = "yearweek($column,1)";
	} else {
		$length = isset($_GET['month'])?10:7;  //month=0 means daily ;-0

		$from_date = "substring( $column, 1, $length )";
		$group_date = "substring( $column, 1, $length )";
	}
	
	$title = "Breakdown of User Signups over Time";
			
	 $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$table=$db->GetAll("
	select 
	$from_date as `Date` ,
	count(*) as `Signups`,
	sum((select gridimage_id 
		from gridimage_search gi 
		where gi.user_id = user.user_id
		limit 1) is not NULL) as `Who later Contribute`
	from user 
	where rights <> ''  
	group by $group_date
	" );
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
	foreach (array('month') as $key) {
		if (isset($_GET[$key])) {
			$extra[$key] = $_GET[$key];
		}
	}
	$smarty->assign_by_ref('extra',$extra);	
}

$smarty->display($template, $cacheid);

	
?>
