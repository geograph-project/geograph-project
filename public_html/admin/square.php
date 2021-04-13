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

$USER->mustHavePerm("moderator");

$smarty = new GeographPage;


if (!empty($_GET['list'])) {
	$db = GeographDatabaseConnection(true);

	$sql = "select gridimage_id,grid_reference,max(ftf) as max,count(*) as count from gridimage_search where ftf > 0 group by grid_reference having count!=max order by null";
	dump_sql_table($sql);
	exit;
} elseif (!empty($_GET['list2'])) {
	$db = GeographDatabaseConnection(true);

	$sql = "select gridimage_id,grid_reference,sum(ftf=1) as f1,sum(ftf=2) as f2,sum(ftf=3) as f3,sum(ftf=4) as f4 from gridimage_search group by grid_reference having f4 > 0 and (f1!=1 OR f2!=1 OR f3!=1) order by null";
	dump_sql_table($sql);
	exit;
}



$db = GeographDatabaseConnection(false);

if (!empty($_GET['gr'])) {
	$gsid = $db->getOne("SELECT gridsquare_id FROM gridsquare WHERE grid_reference = ".$db->Quote($_GET['gr']));
} elseif (!empty($_GET['id'])) {
	$gsid = $db->getOne("SELECT gridsquare_id FROM gridimage WHERE gridimage_id = ".$db->Quote($_GET['id']));
}

if (empty($gsid)) {
	die("no gr");
}

$crit = "gridsquare_id = $gsid";

if (!empty($_GET['geo']))
	$crit .= " AND moderation_status = 'geograph'";
if (!empty($_GET['ftf']))
        $crit .= " AND ftf > 0";


if (!empty($_POST['image'])) {
	$current = $db->getAssoc("SELECT gridimage_id,ftf FROM gridimage WHERE $crit");

	$sqls = array();

	foreach ($_POST['image'] as $key => $value)
		if ($value != $current[$key]['ftf']) {
			$sqls[] = "UPDATE gridimage SET ftf=$value,upd_timestamp=upd_timestamp WHERE gridimage_id = $key";
			$sqls[] = "UPDATE gridimage_search SET ftf=$value,upd_timestamp=upd_timestamp WHERE gridimage_id = $key";
		}

	if (empty($_POST['confirm'])) {
		print "<pre>";
		print_r($sqls);
		print "</pre>";
	} else
		foreach ($sqls as $sql)
			$db->Execute($sql);
}


print "<form method=post>";

$e = ",TO_DAYS(REPLACE(imagetaken,'-00','-01')) AS tpoint";

$e .= ",upd_timestamp";

dump_sql_table("SELECT gridimage_id,title,user_id,submitted,moderation_status,ftf,seq_no,moderated,moderator_id,points,imagetaken $e FROM gridimage WHERE $crit ORDER BY seq_no",'Photos in Square '.$gsid);

if ($USER->hasPerm('admin'))
	print "<input type=submit><input type=checkbox name=confirm>Confirm?</form>";


/* todo, look for move outs???
mysql> select gridimage_id,suggested,submitted,oldvalue,newvalue from gridimage gi inner join gridimage_ticket_merge t using (gridimage_id) inner join gridimage_ticket_item i using (gridimage_ticket_id) where gi.user_id = 3612 AND field = 'grid_reference' AND oldvalue LIKE 'NY%81%79%';

Also could look at moderations specifically?
SELECT * FROM moderation_log WHERE ...
(should definitly look at in square images, or at least ones that involve geo->nongeo, but may need to geos moved OUT of square as above)
... if using image_ids from moveout qury, need to verify the GRs to make sure really, the LIKE caluse in inexact!
*/

if (!empty($_GET['log']))
	dump_sql_table("SELECT l.* FROM moderation_log FROM moderation_log l INNER JOIN gridimage USING (gridimage_id) WHERE $crit ORDER BY moderation_log_id",'log file');


################################################

function dump_sql_table($sql,$title,$autoorderlimit = false) {
	global $USER, $db;

	$recordSet = $db->Execute($sql.(($autoorderlimit)?" order by count desc limit 25":'')) or die ("Couldn't select photos : $sql " . $db->ErrorMsg() . "\n");

	print "<H3>$title</H3>";

	$row = $recordSet->fields;

	print "<TABLE border='1' cellspacing='0' cellpadding='2'><TR>";
	foreach ($row as $key => $value) {
		print "<TH>$key</TH>";
	}
	print "</TR>";
	$d= array();
	$last = 0;
	$buckets = array();  $five_years_in_days = 365*5;
	while (!$recordSet->EOF) {
		$row = $recordSet->fields;

		if (isset($row['tpoint'])) {
			if ($row['moderation_status'] == 'geograph' && !preg_match('/^0000/',$row['imagetaken'])) {
				$days = $row['tpoint'];
		                $point = 1;
		                if (count($buckets)) {
                		        foreach ($buckets as $test) {
                                		if (abs($test-$days) < $five_years_in_days) {
		                                        $point = 0;
                		                        break; //no point still checking...
                		                }
		                        }
		                }
				$buckets[] = $days;
				$row['tpoint'] = $point;
				if ($point xor preg_match('/tpoint/',$row['points']))
					$row['tpoint'] .= " - DIFF";
			} else {
				$row['tpoint'] = null;
			}
		}
		if (!empty($_GET['first'])) {
			if (isset($d[$row['user_id']]) && empty($row['ftf'])) //lets NOT skip ftf>0 as they may be mistakes!
				continue;
			if (!isset($d[$row['user_id']]))
				print "<tr style=background-color:lightgreen>";
			else
				print "<TR>";
		} else
			print "<TR>";

		$d[$row['user_id']]=1;
		$align = "left";
		foreach ($row as $key => $value) {
			if ($key == 'ftf' && $USER->hasPerm('admin')) {
				if ($row['ftf']) {
					$bg = ($row['ftf'] != ($last+1))?'background-color:pink':'';
					$last = $row['ftf'];
				}
				print "<TD><INPUT TYPE=TEXT SIZE=3 NAME=\"image[{$row['gridimage_id']}]\" VALUE=\"$value\" STYLE=\"$bg\"/></TD>";
			} else
				print "<TD ALIGN=$align>".htmlentities($value)."</TD>";
			$align = "right";
		}
		if (!empty($row['grid_reference'])) {
			print "<td><a href=?gr={$row['grid_reference']}&amp;geo=1&amp;first=1 target=squarer>View</a></td>";
		}
		print "</TR>";
		$recordSet->MoveNext();
	}
	print "</TR></TABLE>";
}

