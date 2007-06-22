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

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*24; //24hr cache

$template='statistics_tables.tpl';

$cacheid='statistics|admin_turnaround';

if (!$smarty->is_cached($template, $cacheid))
{

	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');  
	 $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	
	$tables = array();
	
	###################

	$table = array();
	
		$table['title'] = "Image Moderating";

		$table['headnote'] = "We actully only have data for the time between submisison and <i>last</i> moderation, so remoderation at a later date will cause artificially long times.";
		
		$table['table']=$db->GetAll("
		select date(submitted) as `Date Submitted`,count(*) as Images,count(distinct moderator_id) as `Moderators`,min(unix_timestamp(moderated)-unix_timestamp(submitted))/3600 as Shortest,avg(unix_timestamp(moderated)-unix_timestamp(submitted))/3600 as `Average Hours`,(avg(unix_timestamp(moderated)-unix_timestamp(submitted))+stddev(unix_timestamp(moderated)-unix_timestamp(submitted))*2)/3600 as `at least 75% within`,max(unix_timestamp(moderated)-unix_timestamp(submitted))/3600 as Longest from gridimage where submitted > date_sub(date(now()),interval 24 day) and moderated > 0 group by date(submitted)
		" );

		$table['total'] = count($table);


	$tables[] = $table;

	###################

	$table = array();
	
		$table['title'] = "Ticket Moderating";

		$table['headnote'] = "Excludes deferred and 'self closed' tickets";

		$table['table']=$db->GetAll("
		select date(updated) as `Date Closed`,count(*) as `Tickets`,count(distinct moderator_id) as `Moderators`, min(unix_timestamp(updated)-unix_timestamp(suggested))/3600 as Shortest,avg(unix_timestamp(updated)-unix_timestamp(suggested))/3600 as `Average Hours`,(avg(unix_timestamp(updated)-unix_timestamp(suggested))+stddev(unix_timestamp(updated)-unix_timestamp(suggested))*2)/3600 as `at least 75% within`,max(unix_timestamp(updated)-unix_timestamp(suggested))/3600 as Longest from gridimage_ticket where updated > date_sub(date(now()),interval 24 day) and status = 'closed' and moderator_id > 0 and user_id != moderator_id and deferred = 0 group by date(updated)
		" );

		$table['total'] = count($table);

		$table['footnote'] = "<br/>The last fews days figures will be lower, as there is probably a number of open tickets.<br/><br/>Note: All Hour values are decimal, not imperial hours and minutes";

	$tables[] = $table;

	###################
	
	$smarty->assign_by_ref('tables', $tables);
	
	$smarty->assign('headnote','"at least 75% within" column is only an estimate based on <a href="http://en.wikipedia.org/wiki/Standard_deviation#Chebyshev.27s_inequality">Chebyshev\'s inequality</a>');
		
	$smarty->assign('h2title','Admin Turnaround');
	
} 

$smarty->assign("nosort",1);
$smarty->display($template, $cacheid);

	
?>
