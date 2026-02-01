<?php
/**
 * $Project: GeoGraph $
 * $Id: viewps.php 2302 2006-07-05 12:15:49Z barryhunter $
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

$USER->hasPerm("director") || $USER->mustHavePerm("admin");

$db = NewADOConnection($GLOBALS['DSN']);

#################################################

$smarty->display('_std_begin.tpl');

print "<script src=\"".smarty_modifier_revision("/sorttable.js")."\"></script>";

$c1 = $c2 = $c3 = $cf = array();
$Y = date('Y');
foreach (range($Y-3,$Y-1) as $year) {
	$plus = $year+1;
	$c1[] = "sum(post_time between '$year-02-01' and '$plus-01-31 23:59:59') as 'in$year'";
	$c2[] = "sum(ts between '$year-02-01' and '$plus-01-31 23:59:59') as 'in$year'";
	$c3[] = "sum(month between '$year-02' and '$plus-01') as 'in$year'";

	$cf[] = "sum(in$year>0) as 'in$year'";
}

print "<p>'period' is defined 1st Feb to 31 Jan of the next year";

print "<p>All stats on this page are now calculated by looking the data of the acvitiy, not the signup date of the user";

#####################

$sql = "create temporary table user_posters select poster_id as user_id,count(*) as posts, ".implode(",",$c1)." from geobb_posts group by poster_id";
$db->Execute($sql);

$sql = "select age_group,count(*) as total,".implode(",",$cf)."  from user inner join user_posters using (user_id) where rights like '%basic%'
 group by age_group with rollup";

dump_sql_table($sql, 'Active Forum POSTERS in period');

#####################

$sql = "create temporary table user_viewers select user_id,count(*) as posts, ".implode(",",$c2)." from geobb_lastviewed group by user_id";
$db->Execute($sql);

$sql = "select age_group,count(*) as total,".implode(",",$cf)."  from user inner join user_viewers using (user_id) where rights like '%basic%'
 group by age_group with rollup";

dump_sql_table($sql, 'Active Forum VIEWERS in period');

#####################

$sql = "create temporary table user_subs select user_id,sum(images) as posts, ".implode(",",$c3)." from user_date_stat where type = 'submitted' and month != '' and images>0 group by user_id";
$db->Execute($sql);

$sql = "select age_group,count(*) as total,".implode(",",$cf)."  from user inner join user_subs using (user_id) where rights like '%basic%'
 group by age_group with rollup";

dump_sql_table($sql, 'Active COntributors in period');

#####################

$sql = "select year(signup_date) as year, count(*) total,  sum(age_group=11) '11 and under',sum(age_group=18) as '18 and under' from user where rights like '%basic%' group by year";

dump_sql_table($sql, 'Grouping all registered users by signup year');

print "This last table grouping by CALENDAR YEAR. Also reminder the age group is just when the last set it, its not updated as user ages.";

$smarty->display('_std_end.tpl');

#################################################


function dump_sql_table($sql,$title) {
	global $db;

	$recordSet = $db->Execute($sql) or die ("Couldn't select photos : $sql " . $db->ErrorMsg() . "\n");

	print "<H3>$title</H3>";
	if ($recordSet->EOF)
		return;

	$row = $recordSet->fields;

	print "<TABLE border='1' cellspacing='0' cellpadding='4' bordercolor=#eee  class='report sortable' id='photolist'><THEAD><TR>";
	foreach ($row as $key => $value) {
		print "<TH>$key</TH>";
	}
	print "</TR></THEAD>";
	print "<TBODY>";
	$last = null;
	while (!$recordSet->EOF) {
		$row = $recordSet->fields;

		print "<TR>";
		$align = "left";
		foreach ($row as $key => $value) {
			$align = is_numeric($value)?"right":"left";
			print "<TD ALIGN=$align>".htmlentities($value)."</TD>";
		}
		print "</TR>";

		$recordSet->MoveNext();
	}

	print "</TBODY></TABLE>";

	return $recordSet->RecordCount();
}


