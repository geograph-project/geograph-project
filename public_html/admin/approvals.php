<?php
/**
 * $Project: GeoGraph $
 * $Id: conversion.php 5502 2009-05-13 14:18:23Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 BArry Hunter (geo@barryhunter.co.uk)
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

$USER->hasPerm("director") || $USER->mustHavePerm("moderator");

$smarty->display('_std_begin.tpl');

$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

##############################

if (!empty($_POST['status'])) {
	$user_id = intval($USER->user_id);
	foreach($_POST['status'] as $moderation_id => $result) {
		$moderation_id = intval($moderation_id);
		$result = $db->Quote($result);
		$sql = "UPDATE moderation SET moderation_status = $result, moderator_id = $user_id, moderated = NOW() WHERE moderation_id = $moderation_id";
		$db->Execute($sql);

		if ($result == "'flagged'") {
			$row = $db->getRow("SELECT * FROM moderation WHERE moderation_id = $moderation_id");
			$url = "https://www.geograph.org.uk/admin/approvals.php?status=flagged&source=".urlencode($row['source'])."&moderation_id=$moderation_id";
			$content  = $row['source']." Content has been flagged for attention by {$USER->realname}\n\n";
			$content .= "See: $url\n\n";
			$content .= "Reference: {$row['url']}\n\n";
			mail_wrapper('approvals@geograph.org.uk','[Geograph] Flagged Content #'.$moderation_id, $content);
		}
	}
}

##############################

if (!empty($_GET['stats'])) {
	$data = $db->getAll("select source,event_type,count(*),max(event_date)
			,sum(moderation_status='pending') as pending,sum(moderation_status='flagged') as flagged,sum(moderation_status='approved') as approved
		 from moderation group by source,event_type");
	print "<table>";
		print "<tr><th>".implode("</th><th>",array_map('htmlentities',array_keys($data[0])))."</th></tr>";
	foreach($data as $row) {
		print "<tr><td>".implode("</td><td align=right>",array_map('htmlentities',$row))."</td>";
		if (!empty($row['source']))
			print "<td><a href=\"?source={$row['source']}\">view</a>";
	}
	print "</table><hr>";
}

##############################

$where = array();
$where['status'] = "moderation_status = 'pending'";

//todo, use $_GET['moderation_id'] - perhaps show all flagged at same time, or at least all with the same user_id!

if (!empty($_GET['source']) && preg_match('/^\w+$/',$_GET['source']))
	$where['source'] = "source = ".$db->Quote($_GET['source']);

if (!empty($_GET['status']) && preg_match('/^\w+$/',$_GET['status']))
	$where['status'] = "moderation_status = ".$db->Quote($_GET['status']);

##############################

$links = array(
	'status=pending'=>'Pending',
	'status=flagged'=>'Flagged',
	'status=approved'=>'Approved',
	'stats=1'=>'Statistics',
);

print '<div class="tabHolder" style="max-width:940px">';
foreach ($links as $link => $name) {
        if ($link == $_SERVER['QUERY_STRING']) {
                if (!empty($_GET)) { //having the link is useful to return to "homepage"
                        print "<a class=tabSelected href=?$link>$name</a> ";
                } else {
                        print "<a class=tabSelected>$name</a> ";
                }
        } else {
                print "<a class=tab href=?$link>$name</a> ";
        }
}
print '</div>';

##############################

	print "<div class=interestBox>";
        print "<h2>Content to Review</h2>";
	print "</div>";

	$size = 30;
	$where = implode(' AND ',$where);
	$list = $db->getAll("SELECT m.*, user.realname, images, modd.realname AS mod_realname
	 FROM moderation m LEFT JOIN user USING (user_id) LEFT JOIN user_stat USING (user_id)
		LEFT JOIN user modd ON (modd.user_id = moderator_id)
	 WHERE $where ORDER BY event_date DESC LIMIT $size"); //perhaps should be asc?

##############################

	if (count($list)) {
		//if (!function_exists('smarty_modifier_truncate'))
		//	require_once("smarty/libs/plugins/modifier.truncate.php");

		print '<div class="grid-container">';

		//<div class="grid-item header">Header 1</div>

                foreach ($list as $idx => $row) {
			print '<div class="grid-item">';
				print '<div class="date">';
				print formatMySQLDateByResolution($row['event_date']);
				print '</div>';

			print "<a href=\"".htmlentities($row['url'])."\">";
			print "<b>".htmlentities($row['title'])."</b>";
			print "</a>";
			if (strpos($row['title'],'...') !== FALSE)
				print " [TRUNCATED]";

			if ($row['user_id']) {
				print "<span class=nowrap>";
				if ($row['title']!=$row['realname'])
					print " by <a href=\"/profile/{$row['user_id']}\">".htmlentities($row['realname'])."</a>";
				if (!$row['images']) {
					print " [new user]";
				} else {
					print " [".intval($row['images'])."]";
				}
				print "</span>";
			}
			if (!empty($row['moderated'])) {
				print "<br><br><i>{$row['moderation_status']} by ".htmlentities($row['mod_realname']).", ".formatMySQLDateByResolution($row['moderated'])."</i>";
			}

			print '</div>';

			print '<div class="grid-item">';
			print $row['source'];
			if ($row['event_type'] != 'creation')
				print "/".$row['event_type'];
			if (!empty($row['media_url'])) {
				$url = htmlentities($row['media_url']);
				print "<br><a href=\"$url\">";
				if (preg_match('/\.(jpe?g|gif|png|webp)$/',$row['media_url'])) {
					print "<img src=\"$url\">";
				} else {
					print htmlentities(basename($row['media_url']));
				}
				print "</a>";
			}
			print '</div>';

			print '<div class="grid-item">';
				print "<form method=post>"; //for now each is a seperate form submission!
			print "<button type=submit name=status[{$row['moderation_id']}] value=approved>Looks Safe</button>";
			print "<button type=submit name=status[{$row['moderation_id']}] value=flagged>Flag!</button>";
				print "</form>";
			print '</div>';
                }
		print '</div>';

		if (count($list) == $size) {
			$offset+=$size;
			print "<div class=interestBox><a href=?o=$offset>More...</a></div>";
		}

	} else {
		print "Nothing to display.";
	}
?>

<style>


.grid-container {
	max-width:940px;
	 box-sizing:border-box;

    display: grid;
    /* Defines 3 columns:
       - The first column takes up remaining space (`1fr`).
       - The second column has a fixed width of 150px.
       - The third column has a fixed width of 100px. */
    grid-template-columns: 1fr 150px 100px;
    gap: 1px; /* Small gap to visually separate cells, mimicking table borders */
    border: 1px solid #ccc; /* Outer border for the "table" */
    background-color: #eee; /* Background for the gaps/borders */
}

.grid-item {
    background-color: #f9f9f9;
    padding: 10px;
    border: 1px solid #ddd; /* Inner borders for cells */
    text-align: center;
}

.grid-item.header {
    background-color: #e0e0e0;
    font-weight: bold;
    padding: 10px;
    text-align: center;
}

/* Make the first column (1st, 4th, 7th, 10th... grid item) left-aligned */
.grid-item:nth-child(3n + 1) {
    text-align: left;
}

.grid-container .date {
	float:right;
}
.grid-container img {
        max-width:150px;
        max-height:150px;
        width: auto;
        height: auto;
}

.grid-container button {
	margin-bottom:8px;
	white-space:nowrap;
}

.grid-container button[value=approved] {
	background-color:lightgreen;
}
.grid-container button[value=flagged] {
	background-color:pink;
}

</style>

<?

$smarty->display('_std_end.tpl');



/** * Formats a MySQL datetime string based on its age relative to the current time. * * @param string $mysql_datetime A MySQL datetime string (e.g., '2020-09-20 13:03:26'). * @param 
 string $timezone Optional. The timezone to use for date comparisons. Defaults to 'UTC'. * @return string The formatted date/time string. */
function formatMySQLDateByResolution(string $mysql_datetime, string $timezone = 'UTC'): string {
    // Create DateTime objects for the input date and the current time, both in the specified timezone.
    try { $date = new DateTime($mysql_datetime, new DateTimeZone($timezone)); $now = new DateTime('now', new DateTimeZone($timezone));
    } catch (Exception $e) {
        // Handle invalid date string or timezone
        error_log("Error creating DateTime object: " . $e->getMessage()); return $mysql_datetime; // Return original on error
    }
    $diff = $now->getTimestamp() - $date->getTimestamp(); // Difference in seconds
    // Calculate time thresholds in seconds
    $ten_minutes = 10 * 60; $one_day = 24 * 60 * 60; $one_month = 30 * 24 * 60 * 60; // Approximate month for simplicity
    // --- Resolution Logic --- If within the last 10 minutes: show exact time with seconds (e.g., "13:03:26")
    if ($diff >= 0 && $diff <= $ten_minutes) { return $date->format('H:i:s')." today";
    }
    // If today (same day, ignoring time): show just the time (e.g., "13:03") This check needs to be precise: same year, same month, same day
    if ($date->format('Y-m-d') === $now->format('Y-m-d')) { return $date->format('H:i')." today";
    }
    if ($date->format('Y-m-d') === date('Y-m-d',time()-3600*24)) { return $date->format('H:i')." yesterday";
    }
    // If this month (same year, same month): show just the day (e.g., "20") Note: This needs to be 'this month' relative to the current date, not just within a 30-day window.
    if ($date->format('Y-m') === $now->format('Y-m')) { return $date->format('jS M'); // 'j' for day without leading zeros
    }
    // If older: show full date (e.g., "2020-09-20")
    return $date->format('Y-m-d');
}
