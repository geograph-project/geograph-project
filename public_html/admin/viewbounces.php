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
	print htmlentities(print_r(json_decode($value,true),true));
	exit;
}



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
where Type = 'Notification' and JSON_VALUE(Message,'$.mail.destination[0]') is not null
and Message NOT like '%@simulator.amazonses.com%'

group by JSON_VALUE(Message,'$.mail.destination[0]'), JSON_VALUE(Message,'$.notificationType')
order by TimeStamp DESC

LIMIT 50";

//JSON_VALUE(Message,'$.mail.commonHeaders.replyTo[0]') as `reply`,



dump_sql_table($sql,"recent bounces/complaints");




function dump_sql_table($sql,$title,$autoorderlimit = false) {
	$result = mysql_query($sql.(($autoorderlimit)?" order by count desc limit 25":'')) or die ("Couldn't select photos : $sql " . mysql_error() . "\n");

	$row = mysql_fetch_array($result,MYSQL_ASSOC);

	print "<H3>$title</H3>";

	print "<TABLE border='1' cellspacing='0' cellpadding='2'><TR>";
	foreach ($row as $key => $value) {
		if ($key != 'diagnosticCode' && $key != 'subject')
			print "<TH>$key</TH>";
	}
	print "<td>View</td>";
	print "</TR>";
	do {
		print "<TR style=background-color:#eee;font-weight:bold>";
		$align = "left";
		foreach ($row as $key => $value) {
			$align = is_numeric($value)?"right":"left";
			if ($key != 'diagnosticCode' && $key != 'subject')
				print "<TD ALIGN=$align>".htmlentities($value)."</TD>";
		}
		print "<td><a href=\"?t={$row['TimeStamp']}\">View</a></td>";
		print "</TR>";

		if (!empty($row['subject']))
			print "<tr><td colspan=9>".htmlentities($row['subject']);

		if (!empty($row['diagnosticCode']))
			print "<tr><td colspan=9 style=font-size:0.8em>".htmlentities($row['diagnosticCode']);

	} while ($row = mysql_fetch_array($result,MYSQL_ASSOC));
	print "</TR></TABLE>";
}

