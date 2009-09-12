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

$USER->mustHavePerm("admin");

$smarty = new GeographPage;

$db = GeographDatabaseConnection(true);


	$smarty->display('_std_begin.tpl');
	
	if ($_GET['recent']) {
		$sql = "SELECT CONCAT('<a href=\"/search.php?i=',id,'\">',searchclass,'</a>') AS searchclass, searchdesc, realname, crt_timestamp FROM queries LEFT JOIN user USING(user_id) ORDER BY crt_timestamp DESC LIMIT 100";	
		dump_sql_table($sql,'Last 100 Querys',false);
	} elseif ($_GET['special']) {
		$sql = "SELECT CONCAT('<a href=\"/search.php?i=',id,'\">',searchdesc,'</a>') AS searchdesc, realname, crt_timestamp FROM queries LEFT JOIN user USING(user_id) WHERE searchclass = 'Special' ORDER BY crt_timestamp DESC LIMIT 100";	
		dump_sql_table($sql,'Last 100 Special',false);
	} else {
	$sql = "select searchq,count(distinct user_id) as users,count(*) as count from queries where crt_timestamp > date_sub(now(), interval 1 day) group by searchq";	
	dump_sql_table($sql,'Last 1 day Query Strings');
	
	print "<HR/><H2>Search Stats for the past 7 Days</H2>";
	
	$datecrit = "crt_timestamp > date_sub(now(), interval 7 day)";
	
	$sql = "select searchclass,count(distinct user_id) as users,count(*) as count from queries where $datecrit group by searchclass";	
	dump_sql_table($sql,'Most Used Search Class');
	
	$sql = "select searchq,count(distinct user_id) as users,count(*) as count from queries where $datecrit group by searchq";	
	dump_sql_table($sql,'Most Used Query Strings');
	
	$sql = "select realname,count(*) as count from queries left join user using(user_id) where $datecrit group by queries.user_id";	
	dump_sql_table($sql,'Most Active Users');
	
	$sql = "select limit1,realname,count(distinct queries.user_id) as users,count(*) as count from queries left join user on(limit1=user.user_id) where $datecrit group by limit1";	
	dump_sql_table($sql,'Most Active Searched on User');

	$sql = "select limit2,count(distinct user_id) as users,count(*) as count from queries where $datecrit group by limit2";	
	dump_sql_table($sql,'Most Used Classification');

	$sql = "select limit3,count(distinct user_id) as users,count(*) as count from queries where $datecrit group by limit3";	
	dump_sql_table($sql,'Most Used Image Category');
	
	$sql = "select limit4,count(distinct user_id) as users,count(*) as count from queries where $datecrit group by limit4";	
	dump_sql_table($sql,'Most Used Reference Index');

	$sql = "select limit5,count(distinct user_id) as users,count(*) as count from queries where $datecrit group by limit5";	
	dump_sql_table($sql,'Most Used Grid Square');

	$sql = "select displayclass,count(distinct user_id) as users,count(*) as count from queries where $datecrit group by displayclass";	
	dump_sql_table($sql,'Most Used View Class');

	$sql = "select orderby,count(distinct user_id) as users,count(*) as count from queries where $datecrit group by orderby";	
	dump_sql_table($sql,'Most Used Order');		
	
	print "<HR/><H2>Search Stats for whole time</H2>";
		
	$sql = "select searchclass,count(distinct user_id) as users,count(*) as count from queries group by searchclass";	
	dump_sql_table($sql,'Most Used Search Class');

	$sql = "select searchq,count(distinct user_id) as users,count(*) as count from queries group by searchq";	
	dump_sql_table($sql,'Most Used Query Strings');
	}
	
	$smarty->display('_std_end.tpl');
	exit;
	

function dump_sql_table($sql,$title,$autoorderlimit = true) {
	
	$result = mysql_query($sql.(($autoorderlimit)?" order by count desc limit 25":'')) or die ("Couldn't select photos : $sql " . mysql_error() . "\n");
	
	$row = mysql_fetch_array($result,MYSQL_ASSOC);

	print "<H3>$title</H3>";
	
	print "<TABLE border='1' cellspacing='0' cellpadding='2'><TR>";
	foreach ($row as $key => $value) {
		print "<TH>$key</TH>";
	}
	print "</TR><TR>";
	foreach ($row as $key => $value) {
		print "<TD>$value</TD>";
	}
	print "</TR>";
	while ($row = mysql_fetch_array($result,MYSQL_ASSOC)) {
		print "<TR>";
		foreach ($row as $key => $value) {
			print "<TD>$value</TD>";
		}
		print "</TR>";
	}
	print "</TR></TABLE>";
}

	
?>
