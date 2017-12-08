<?php
/**
 * $Project: GeoGraph $
 * $Id: busyday_users.php 2176 2006-04-27 23:42:06Z barryhunter $
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

$u = (isset($_GET['u']) && is_numeric($_GET['u']))?intval($_GET['u']):0;
$bymonth = isset($_GET['time']) && $_GET['time'] === 'month' ? 1 : 0;
$byweek = isset($_GET['time']) && $_GET['time'] === 'week' ? 1 : 0;
$byyear = isset($_GET['time']) && $_GET['time'] === 'year' ? 1 : 0;
$showall = isset($_GET['showall']) && $_GET['showall'] === '1' && $USER->hasPerm("admin") ? 1 : 0;

$timelist = array('' => 'by day', 'week' => 'by week', 'month' => 'by month', 'year' => 'by year');

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*24; //24hr cache

if ($byyear) {
	$timestr = 'year';
	$timeparam = 'year';
	$smarty->cache_lifetime = 3600*24*7; //7 day cache
	$imgwhere = '1';
	$tickwhere = '1';
	$imgdate = 'year(submitted)';
	$tickdate = 'year(updated)';
} elseif ($bymonth) {
	$timestr = 'month';
	$timeparam = 'month';
	$smarty->cache_lifetime = 3600*24*2; //2 day cache
	$imgwhere = 'submitted > date_sub(date(now()),interval 730 day)';
	$tickwhere = 'updated > date_sub(date(now()),interval 730 day)';
	#$imgdate = "CONCAT_WS('-',year(submitted),month(submitted))";
	#$tickdate = "CONCAT_WS('-',year(updated),month(updated))";
	$imgdate = "DATE_FORMAT(submitted, '%Y-%m')";
	$tickdate = "DATE_FORMAT(updated, '%Y-%m')";
} elseif ($byweek) {
	$timestr = 'week';
	$timeparam = 'week';
	$imgwhere = 'submitted > date_sub(date(now()),interval 140 day)';
	$tickwhere = 'updated > date_sub(date(now()),interval 140 day)';
	#$imgdate = "CONCAT_WS('/',year(submitted),week(submitted))";
	#$tickdate = "CONCAT_WS('/',year(updated),week(updated))";
	$imgdate = "DATE_FORMAT(submitted, '%Y/%v')";
	$tickdate = "DATE_FORMAT(updated, '%Y/%v')";
} else {
	$timestr = 'day';
	$timeparam = '';
	$imgwhere = 'submitted > date_sub(date(now()),interval 24 day)';
	$tickwhere = 'updated > date_sub(date(now()),interval 24 day)';
	$imgdate = 'date(submitted)';
	$tickdate = 'date(updated)';
}

$cidparam = $timestr;

if ($showall) {
	if ($byyear || $bymonth || $byweek) {
		$imgwhere = '1';
		$tickwhere = '1';
	}
	$cidparam .= 'all';
	$extra = array('showall' => '1');
	$smarty->assign_by_ref('extra',$extra);
}

if (isset($_GET['output']) && $_GET['output'] == 'csv') {
	$table = (isset($_GET['table']) && is_numeric($_GET['table']))?intval($_GET['table']):0;
	$smarty->assign('whichtable',$table);
	
	$template='statistics_tables_csv.tpl';
	# let the browser know what's coming
	$paramstr = $timestr;
	if ($u) {
		$paramstr .= '.' . $u;
	}
	header("Content-type: application/octet-stream");
	header("Content-Disposition: attachment; filename=\"".basename($_SERVER['SCRIPT_NAME'],'.php').".$table.$paramstr.csv\"");

	$cacheid='statistics|admin_turnaround|'.$cidparam.'.'.$table.'|'.$u;
} else {
	$template='statistics_tables.tpl';
	
	$cacheid='statistics|admin_turnaround|'.$cidparam.'|'.$u;
}

if (!$smarty->is_cached($template, $cacheid))
{

	$db=GeographDatabaseConnection();
	if (!$db) die('Database connection failed');  
	 $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	
	if (!empty($u)) {
		$crit1 = "and moderator_id = ".$u;
		$crit2 = "and moderator_id = ".$u;
		$smarty->assign('u', $u);

		$profile=new GeographUser($u);
		$smarty->assign_by_ref('profile', $profile);
		$title = " for ".($profile->realname);
	} else {
		$title = '';
	}

	$tables = array();
	
	###################

	$table = array();
	
		$table['title'] = "Image Moderating".$title;

		$table['headnote'] = "We actully only have data for the time between submisison and <i>last</i> moderation, so remoderation at a later date will cause artificially long times.";
		
		$table['table']=$db->GetAll("
		select $imgdate as `Date Submitted`,count(*) as Images,count(distinct moderator_id) as `Moderators`,min(unix_timestamp(moderated)-unix_timestamp(submitted))/3600 as Shortest,avg(unix_timestamp(moderated)-unix_timestamp(submitted))/3600 as `Average Hours`,(avg(unix_timestamp(moderated)-unix_timestamp(submitted))+stddev(unix_timestamp(moderated)-unix_timestamp(submitted))*2)/3600 as `at least 75% within`,max(unix_timestamp(moderated)-unix_timestamp(submitted))/3600 as Longest from gridimage where $imgwhere and moderated > 0 $crit1 group by $imgdate
		" );

		$table['total'] = count($table);


	$tables[] = $table;

	###################

	$table = array();
	
		$table['title'] = "Ticket Moderating".$title;

		$table['headnote'] = "Excludes deferred and 'self closed' tickets";

		$table['table']=$db->GetAll("
		select $tickdate as `Date Closed`,count(*) as `Tickets`,count(distinct moderator_id) as `Moderators`, min(unix_timestamp(updated)-unix_timestamp(suggested))/3600 as Shortest,avg(unix_timestamp(updated)-unix_timestamp(suggested))/3600 as `Average Hours`,(avg(unix_timestamp(updated)-unix_timestamp(suggested))+stddev(unix_timestamp(updated)-unix_timestamp(suggested))*2)/3600 as `at least 75% within`,max(unix_timestamp(updated)-unix_timestamp(suggested))/3600 as Longest from gridimage_ticket where $tickwhere and status = 'closed' and moderator_id > 0 and user_id != moderator_id and deferred = 0 $crit2 group by $tickdate
		" );

		$table['total'] = count($table);

		$table['footnote'] = "<br/>The last fews days figures will be lower, as there is probably a number of open tickets.<br/><br/>Note: All Hour values are decimal, not imperial hours and minutes";

	$tables[] = $table;

	###################
	
	$smarty->assign_by_ref('tables', $tables);
	
	$smarty->assign('headnote','"at least 75% within" column is only an estimate based on <a href="http://en.wikipedia.org/wiki/Standard_deviation#Chebyshev.27s_inequality">Chebyshev\'s inequality</a>');
		
	$smarty->assign('h2title','Admin Turnaround');
	
} else {
	if ($u) {
		$profile=new GeographUser($u);
		$smarty->assign_by_ref('profile', $profile);
		$smarty->assign_by_ref('u', $u);
	}
}

$smarty->assign("filter",2);
$smarty->assign("nosort",1);
$smarty->assign('timeparam', $timeparam);
$smarty->assign_by_ref('timelist', $timelist);
$smarty->display($template, $cacheid);

	
?>
