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

$smarty = new GeographPage;

$USER->mustHavePerm("basic");

$smarty->display('_std_begin.tpl');

$db = GeographDatabaseConnection(false); //the job creation/update statements are not replication safe, so need to use master

print "<script src=\"".smarty_modifier_revision("/sorttable.js")."\"></script>";

$task = isset($_GET['task'])?$_GET['task']:'yahoo_terms';
if (!in_array($task,array('yahoo_terms','carrot2'))) {
	die('ERROR:invalid task');
}

$tabs = array(
'all'=>'All Time Teams',
'recent'=>'Recent Teams',
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

$merge = $db->getOne("SHOW TABLES LIKE 'at_home_job_merge'")?'_merge':'';

if (isset($_GET['tab']) && $_GET['tab'] == 'coverage') {
	$max = $db->getOne("SELECT MAX(gridimage_id) FROM gridimage_search");
	$data = $db->getAssoc("SELECT CONCAT(start_gridimage_id,'-',IF(repeat_of>0,1,0)) as k,end_gridimage_id,count(*) as jobs,sum(images) as images,sum(terms) as terms,at_home_job_id
		from at_home_job$merge
		where task = '$task'
		group by k,end_gridimage_id
		order by null");

	$c = array(0,0,0);
	print "<table cellspacing=0 border=1 cellpadding=3>";
	foreach (range(1,$max,5000) as $s) {
		$c[2]++;
		print "<tr><th align=right>$s</th>";
		$im = 0;
		$end = 0;
		foreach (range(0,1) as $t) {
			$k = "$s-$t";
			if (isset($data[$k])) {
				$c[$t]++;
				$end = max($end,$data[$k]['end_gridimage_id']);
				if ($data[$k]['end_gridimage_id'] == $s+4999)
					$data[$k]['end_gridimage_id'] = '';
				else
					$last = $data[$k];

				print "<td align=right>{$data[$k]['end_gridimage_id']}</td>";
				print "<td align=right style=color:gray>{$data[$k]['jobs']}</td>";
				print "<td ".($data[$k]['images']<($t?100:2000)?' style="background-color:pink"':'')." align=right>{$data[$k]['images']}</td>";
				print "<td align=right style=color:silver>{$data[$k]['terms']}</td>";
				$im += $data[$k]['images'];
			} else {
				print "<td></td><td></td><td></td><td></td>";
				if ($t == 1 && !empty($_GET['sql'])) {
					if ($s < $last['end_gridimage_id']) {
						$sql = "INSERT INTO at_home_job SET created=NOW(),start_gridimage_id=$s,end_gridimage_id=$s+4999,repeat_of={$last['at_home_job_id']},task = '$task'";
						print "$sql;<br>";
					}
				}
			}
		}
		print "<td align=right>$im</td>";
		$p = sprintf('%0.1f',($im/($end-$s+1))*100);
		print "<td ".($p<50?' style="background-color:pink"':'')." align=right>$p%</td>";
	}
	print "<tr><td>{$c[2]}</td><td></td><td>{$c[0]}</td><td></td><td></td><td></td><td>{$c[1]}</td><td></td><td></td><td></td><td></td>";
	print "</table>";
	print "$max (latest gridimage_id)";

} elseif (isset($_GET['tab']) && $_GET['tab'] == 'all') {

	dump_sql_table("
		select team,sum(terms) as terms,sum(images) as images,count(*) as jobs
		from at_home_job$merge inner join at_home_worker using(at_home_worker_id)
		where task = '$task'
		group by team with rollup",'All Time');

} elseif (isset($_GET['tab']) && $_GET['tab'] == 'recent') {

	dump_sql_table("
		select team,sum(terms) as terms,sum(images) as images,count(*) as jobs
		from at_home_job inner join at_home_worker using(at_home_worker_id)
		where last_contact > date_sub(now(),interval 7 day) and task = '$task'
		group by team with rollup",'Last 7 Days');

} elseif (isset($_GET['tab']) && $_GET['tab'] == 'created') {
        dump_sql_table("select substring(created,1,13),count(*),sum(completed = 0) from at_home_job where task = '$task' group by substring(created,1,13) desc limit 40", "jobs created");

} elseif (isset($_GET['tab']) && $_GET['tab'] == '24') {

	dump_sql_table("select * from (
		select at_home_worker_id as worker,team,max(last_contact) as last_contact,count(*) as jobs,sum(terms) as terms,sum(images) as images
		from at_home_job inner join at_home_worker using(at_home_worker_id)
		where last_contact > date_sub(now(),interval 24 hour) and task = '$task'
		group by at_home_worker_id) as i
		order by last_contact desc,worker",'Last 24 Hours');

} elseif (isset($_GET['tab']) && $_GET['tab'] == 'alive2') {
	$USER->mustHavePerm("admin");

	dump_sql_table("select at_home_worker_id as worker,team,max(last_contact) as last_contact, count(*) as jobs,sum(terms) as terms,sum(images) as images,
		if(user_agent like 'Geograph-At-Home%','Standalone Client','Browser Client') as Client,inet6_ntoa(ip) as ip
                from at_home_job inner join at_home_worker using(at_home_worker_id)
                where at_home_job.sent > date_sub(now(),interval 2 day) and task = '$task'
                group by at_home_worker_id order by last_contact desc,worker",'Last Contact');

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

$smarty->display('_std_end.tpl');

function dump_sql_table($sql,$title,$autoorderlimit = false) {
	global $db;
	$recordSet = $db->Execute($sql.(($autoorderlimit)?" order by count desc limit 25":'')) or die ("Couldn't select photos : $sql " . $db->ErrorMsg() . "\n");

	print "<H3>$title</H3>";

        if ($recordSet->EOF) {
                print "0 rows";
                return;
        }

	$row = $recordSet->fields;

	print "<TABLE border='1' cellspacing='0' cellpadding='2' class=\"report sortable\" id=\"photolist\"><THEAD><TR>";
	foreach ($row as $key => $value) {
		print "<TH>$key</TH>";
	}
	print "</TR></THEAD><TBODY>";
	$keys = array_keys($row);
	$first = $keys[0];
	while (!$recordSet->EOF) {
		$row = $recordSet->fields;

		print "<TR>";
		$align = "left";
                if (is_null($row[$first])) {
                        $row['team'] = '-EVERYONE-';
		}
 		foreach ($row as $key => $value) {
			if ($key == 'grid_reference') {
				print "<TD ALIGN=$align><a href=\"/gridref/$value?by=cluster\">".htmlentities($value)."</a></TD>";
			} else {
				print "<TD ALIGN=$align>".htmlentities($value)."</TD>";
			}
			$align = "right";
		}
		if (!empty($row['ip'])) {
			print "<td>".gethostbyaddr($row['ip'])."</TD>";
		}
		print "</TR>";
                $recordSet->MoveNext();
	}

	print "</TR></TBODY></TABLE>";
}

