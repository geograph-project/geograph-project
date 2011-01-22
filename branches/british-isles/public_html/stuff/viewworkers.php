<?php
/**
 * $Project: GeoGraph $
 * $Id: viewps.php 2295 2006-07-05 12:15:49Z barryhunter $
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

$USER->mustHavePerm("basic");

print "<script src=\"/geograph.js\"></script>";
print "<script src=\"/sorttable.js\"></script>";

$db = GeographDatabaseConnection(true);

$task = isset($_GET['task'])?$_GET['task']:'yahoo_terms';
if (!in_array($task,array('yahoo_terms','carrot2'))) {
	die('ERROR:invalid task');
}

$tabs = array(
'all'=>'All Time',
'24'=>'Last 24 Hours',
'alive'=>'Last Contact',
'jobs'=>'Job Breakdown'
);

print "<p>Tab: | ";
foreach ($tabs as $key => $value) {
	if (isset($_GET['tab']) && $_GET['tab'] == $key) {
		print " <b>$value</b> | ";
	} else {
		print " <a href=\"?tab=$key&task=$task\">$value</a> | ";
	}
}
print "</p>";

if (isset($_GET['tab']) && $_GET['tab'] == 'all') {

dump_sql_table("
select team,sum(terms) as terms,sum(images) as images,count(*) as jobs 
from at_home_job inner join at_home_worker using(at_home_worker_id) 
where task = '$task'
group by team with rollup",'All Time');

} elseif (isset($_GET['tab']) && $_GET['tab'] == '24') {

dump_sql_table("select * from (
select at_home_worker_id as worker,team,max(last_contact) as last_contact,count(*) as jobs,sum(terms) as terms,sum(images) as images 
from at_home_job inner join at_home_worker using(at_home_worker_id)
where last_contact > date_sub(now(),interval 24 hour) and task = '$task'
group by at_home_worker_id) as i
order by last_contact desc,worker",'Last 24 Hours');

} elseif (isset($_GET['tab']) && $_GET['tab'] == 'alive') {

dump_sql_table("select at_home_worker_id as worker,team,max(last_contact) as last_contact
from at_home_job inner join at_home_worker using(at_home_worker_id)
where at_home_job.sent > date_sub(now(),interval 2 day) and task = '$task'
group by at_home_worker_id order by last_contact desc,worker",'Last Contact');

} elseif (isset($_GET['tab']) && $_GET['tab'] == 'joblist') {

dump_sql_table("select at_home_job_id as job,team,at_home_worker_id as wor,images,sent,completed,
if(last_gridimage_id>0,end_gridimage_id-last_gridimage_id,'?') as togo,repeat('*',if(images>5000,images div (100*((end_gridimage_id-start_gridimage_id+1)/5000)), images div 100)) as progress 
from at_home_job left join at_home_worker using(at_home_worker_id)
where task = '$task' limit 1000",'Lob List');

} else {

	if ($task == 'yahoo_terms') {

dump_sql_table("
select if(sent='0000-00-00 00:00:00','Outstanding',if(completed='0000-00-00 00:00:00','In Progress','Finished')) as 'Status',
count(*) as Jobs,count(distinct if(at_home_worker_id=0,null,at_home_worker_id)) as Workers,
sum(end_gridimage_id-start_gridimage_id+1) as Images,
sum(terms) as 'Terms Found', 
sum(images) as 'Processed Images' 
from at_home_job 
where task = '$task'
group by (sent='0000-00-00 00:00:00'),(completed='0000-00-00 00:00:00')",'Job Breakdown');

#print "<p>Note: not all jobs are of equal size. Mostly 5000 images, but some are bigger for use by the more powerful clients. Also some clients can work on multiple jobs at once.</p>";

	} else {

dump_sql_table("
select if(sent='0000-00-00 00:00:00','Outstanding',if(completed='0000-00-00 00:00:00','In Progress','Finished')) as 'Status',
count(*) as 'Jobs/Squares',count(distinct if(at_home_worker_id=0,null,at_home_worker_id)) as Workers,
sum(terms) as 'Clusters Found', 
sum(images) as 'Processed Images' 
from at_home_job 
where task = '$task' and created > date_sub(now(),interval 90 day)
group by (sent='0000-00-00 00:00:00'),(completed='0000-00-00 00:00:00')",'Job Breakdown - jobs created in last 90 days');
		
	}
}

function dump_sql_table($sql,$title,$autoorderlimit = false) {
	$result = mysql_query($sql.(($autoorderlimit)?" order by count desc limit 25":'')) or die ("Couldn't select photos : $sql " . mysql_error() . "\n");
	
	$row = mysql_fetch_array($result,MYSQL_ASSOC);

	print "<H3>$title</H3>";
	
	print "<TABLE border='1' cellspacing='0' cellpadding='2' class=\"report sortable\" id=\"photolist\"><THEAD><TR>";
	foreach ($row as $key => $value) {
		print "<TH>$key</TH>";
	}
	print "</TR></THEAD><TBODY>";
	$keys = array_keys($row);
	$first = $keys[0];
	do {
		print "<TR>";
		$align = "left";
                if (is_null($row[$first])) {
                        $row['team'] = '-EVERYONE-';
		}
 		foreach ($row as $key => $value) {
			print "<TD ALIGN=$align>".htmlentities($value)."</TD>";
			$align = "right";
		}
		print "</TR>";
	} while ($row = mysql_fetch_array($result,MYSQL_ASSOC));
	print "</TR></TBODY></TABLE>";
}

	
?>
