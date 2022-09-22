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

$USER->hasPerm("director") || ($USER->user_id == 93) || ($USER->user_id == 20032) || $USER->mustHavePerm("admin");

$db = NewADOConnection($GLOBALS['DSN']);

if (!empty($_GET['t'])) {
	$value = $db->getOne("SELECT Message FROM sns_message WHERE TimeStamp = ".$db->Quote($_GET['t']));
	print "<pre>";
	foreach (explode("\n",print_r(json_decode($value,true),true)) as $line) {
		if (empty($line) || preg_match('/^\s*([(){}]|Array)\s*$/',$line))
			continue;
		print htmlentities($line)."\n";
	}
	//print htmlentities(print_r(json_decode($value,true),true));
	exit;
}

#################################################

if (empty($_GET))
	$_GET['grouped'] = 1;

$where = array();

$limit = 50;
if (!empty($_GET['limit']))
	$limit = intval($_GET['limit']);

#################################################

$filters = array();
$filters['notificationType'] = array('Bounce','Complaint');
$filters['bounceType'] = array('Permanent','Transient');
$filters['SubType'] = array('OnAccountSuppressionList','!OnAccountSuppressionList');
$filters['user_id'] = 'text';
$filters['email'] = 'text';
$filters['subject'] = 'text';

//$filters[''] = array('','');

print "<form method=get>";
foreach ($filters as $name => $rows) {
	if ($rows == 'text') {
		print "<input type=search name=$name value=\"".htmlentities(@$_GET[$name])."\" onkeyup=\"if (event.key == 'Enter') {this.form.submit(); }\" title=$name size=10>";
		if (!empty($_GET[$name])) {
			if ($name == 'email') {
				$where[] = "JSON_VALUE(Message,'$.mail.destination[0]') = ".$db->Quote($_GET[$name]);
			} elseif ($name == 'subject') {
				$where[] = "JSON_VALUE(Message,'$.mail.commonHeaders.subject') = ".$db->Quote($_GET[$name]);
			} else {
				$where[] = "user.$name LIKE ".$db->Quote($_GET[$name]);
			}
		}
	} else {
		print "<select name=$name onchange=this.form.submit() title=$name>";
		print "<option></option>";
		foreach ($rows as $value) {
			printf('<option value="%s"%s>%s</option>',$value, (@$_GET[$name] == $value)?' selected':'', $value);
			if(@$_GET[$name] == $value) {
				if (preg_match('/^!(\w+)/',$value,$m)) {
					$where[] = "Message NOT LIKE ".$db->Quote("%{$name}\":\"{$m[1]}\"%");
				} else {
					$where[] = "Message LIKE ".$db->Quote("%{$name}\":\"{$value}\"%");
				}
			}
		}
		print "</select>";
	}
}

$checked = empty($_GET['recent'])?'':' checked';
print "<input type=checkbox name=recent$checked onclick=this.form.submit()>Recent Only";
if (!empty($_GET['recent'])) {
	$where[] = "TimeStamp > DATE(DATE_SUB(NOW(),INTERVAL 7 DAY))";
}

$checked = empty($_GET['active'])?'':' checked';
print "<input type=checkbox name=active$checked onclick=this.form.submit()>Active";
if (!empty($_GET['active'])) {
	$where[] = "submitted > DATE(DATE_SUB(NOW(),INTERVAL 6 month))";
}

$checked = empty($_GET['grouped'])?'':' checked';
print "<input type=checkbox name=grouped$checked onclick=this.form.submit()>Grouped";
if (!empty($_GET['grouped'])) {
	$group = "JSON_VALUE(Message,'$.mail.destination[0]'), JSON_VALUE(Message,'$.notificationType')";
} else {
	$group = "1"; //timestamp!
}

print "</form>";

#################################################

$where[] = "Type = 'Notification'";
$where[] = "JSON_VALUE(Message,'$.mail.destination[0]') is not null";
$where[] = "Message NOT like '%@simulator.amazonses.com%'";

$sql = "select TimeStamp,
CONCAT_WS(', ',
	JSON_VALUE(Message,'$.notificationType'),
	JSON_VALUE(Message,'$.bounce.bounceType'),
	NULLIF(JSON_VALUE(Message,'$.bounce.bounceSubType'),'General'),
	JSON_VALUE(Message,'$.complaint.complaintType'),
	NULLIF(JSON_VALUE(Message,'$.complaint.complaintSubType'),'null'),
	JSON_VALUE(Message,'$.complaint.complaintFeedbackType')) as type,
JSON_VALUE(Message,'$.mail.destination[0]') as `to`,
count(*),
user.user_id, if (rights LIKE '%basic%','confirmed','not verified') as rights, date(signup_date) as signup,
date(submitted) as last_image,
JSON_VALUE(Message,'$.mail.commonHeaders.subject') as `subject`,
JSON_VALUE(Message,'$.bounce.bouncedRecipients[0].diagnosticCode') as diagnosticCode
from sns_message
	left join user on (user.email = JSON_VALUE(Message,'$.mail.destination[0]'))
	left join user_stat using (user_id)
	left join gridimage_search on (gridimage_id = last)
where ".implode(" AND ",$where)."
group by $group
order by TimeStamp DESC
LIMIT $limit";

#################################################

//JSON_VALUE(Message,'$.mail.commonHeaders.replyTo[0]') as `reply`,



$count = dump_sql_table($sql,"recent bounces/complaints");

if ($count == $limit) {
	print "Last $limit Results";
} else {
	print "All $count Results";
}


function dump_sql_table($sql,$title,$autoorderlimit = false) {
	global $db;

	$recordSet = $db->Execute($sql.(($autoorderlimit)?" order by count desc limit 25":'')) or die ("Couldn't select photos : $sql " . $db->ErrorMsg() . "\n");

	print "<H3>$title</H3>";
	if ($recordSet->EOF)
		return;

	$row = $recordSet->fields;

	print "<TABLE border='1' cellspacing='0' cellpadding='2'><TR>";
	foreach ($row as $key => $value) {
		if ($key != 'diagnosticCode' && $key != 'subject')
			print "<TH>$key</TH>";
	}
	print "<td>View</td>";
	print "</TR>";
	while (!$recordSet->EOF) {
		$row = $recordSet->fields;

		print "<TR style=background-color:#eee;font-weight:bold>";
		$align = "left";
		foreach ($row as $key => $value) {
			$align = is_numeric($value)?"right":"left";
			if ($key != 'diagnosticCode' && $key != 'subject')
				print "<TD ALIGN=$align>".htmlentities($value)."</TD>";
		}
		print "<td><a href=\"?t={$row['TimeStamp']}\">View</a> / <a href=\"?email=".urlencode($row['to'])."\">Others</a></td>";
		print "</TR>";

		if (!empty($row['subject']))
			print "<tr><td colspan=9>".htmlentities($row['subject']);

		if (!empty($row['diagnosticCode']))
			print "<tr><td colspan=9 style=font-size:0.8em>".htmlentities($row['diagnosticCode']);
		$recordSet->MoveNext();
	}

	print "</TR></TABLE>";

	return $recordSet->RecordCount();
}

