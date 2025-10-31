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

if (!empty($_POST['delete_user'])) {
	$sql = "UPDATE user SET rights = REPLACE(rights,'basic','deleted') WHERE user_id = ".intval($_POST['delete_user']);
	$db->Execute($sql);
}

##############################

if (!empty($_POST['status'])) {
	$user_id = intval($USER->user_id);
	foreach($_POST['status'] as $moderation_id => $result) {
		$moderation_id = intval($moderation_id);

		if ($result == 'extreme') {
			$result = 'flagged';
			$_POST['extreme'] = true;
		}

		$row = $db->getRow("SELECT * FROM moderation WHERE moderation_id = $moderation_id");

		//check BEFORE update, if there has been recent reports - so can skip sending multiple reports for the same user.
		if ($result == 'flagged') {
			if (!empty($row['user_id']))
				$recent = $db->getOne("SELECT COUNT(*) FROM moderation
					WHERE moderation_status = 'flagged' AND user_id = {$row['user_id']} AND moderated > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
		}

		$qresult = $db->Quote($result);
		if (!empty($_POST['extreme']))
			$qresult .= ", `extreme` = ".$db->Quote($_POST['extreme']);
		$sql = "UPDATE moderation SET moderation_status = $qresult, moderator_id = $user_id, moderated = NOW() WHERE moderation_id = $moderation_id";
		$db->Execute($sql);

		if ($result == 'flagged' && empty($recent)) {
			$url = "https://www.geograph.org.uk/admin/approvals.php?status=flagged&source=".urlencode($row['source'])."&moderation_id=$moderation_id";
			$content  = $row['source']." Content has been flagged for attention by {$USER->realname}\n\n";
			$content .= "See: $url\n\n";
			$content .= "Reference: {$row['url']}\n\n";

			$content .= "Title: {$row['title']}\n\n";
			if (!empty($row['content']))
				$content .= "Content: {$row['content']}\n\n";
			mail_wrapper('approvals@geograph.org.uk','[Geograph] Flagged Content #'.$moderation_id, $content);
		}

		//TODO this should be dynamic from 'sources/' - but hardcoded to test.
		//note this sends ALL results, upto them what they do with it!
		if ($row['source'] == 'media' || $row['source'] == 'speculative') {
			//_moderation.php?source=$source&foreign_id=$foreign_id&event_type=$event_type&event_date=$event_date&moderation_status=$moderation_status
	                $bits = array();
			$bits['source'] = $row['source'];
			$bits['foreign_id'] = $row['foreign_id'];
			$bits['event_type'] = $row['event_type'];
			$bits['event_date'] = $row['event_date'];
			$bits['moderation_status'] = $result;
                	$bits['hash'] = substr(hash_hmac('md5', date('Y-m-d'), $CONF['r2_endpoint']),0,10);
        	        $raw = file_get_contents("https://media.geograph.org.uk/_moderation.php?".http_build_query($bits));
			if (trim($raw) != 'ok') {
				die("WARNING: Unable to sync request with remote server");
			}
		}
	}

	if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
		header("HTTP/1.0 204 No Content");
		header("Status: 204 No Content");
		header("Content-Length: 0");
		exit;
	}
}

##############################

if (!empty($_GET['preview_user'])) {
	$user = $db->getRow("SELECT * FROM user LEFT JOIN user_stat USING (user_id) WHERE user_id = ".intval($_GET['preview_user']));

	print "<h3>Preview for user - links are not clickable</h3>";
	print "<table cellspacing=0 cellpadding=3 border=1 bordercolor=#eee style=max-width:60em>";
	$keys = explode(',', 'user_id,realname,nickname,email,rights,website,about_yourself,message_sig,signup_date,images');
	foreach ($keys as $key) {
		print "<tr><th>$key</th>";
		print "<td>";
		if ($key == 'email') {
			if ($user['public_email'])
				print htmlentities($user[$key])." <i>- Displayed publicallly!</i>";
			else
				print "<span style=color:gray>".htmlentities(preg_replace('/(\w{3})\w+/','$1...',$user[$key]))." <i>- address not displayed</i></span>";
		} elseif ($key == 'about_yourself') {
			if (!$user['public_about'])
				print "<i>NOT displayed publically</i><span style=color:gray>";
			print "<pre style=\"white-space:pre-wrap;\">".htmlentities($user[$key])."</pre>";
		} else {
			print htmlentities($user[$key]);
		}
	}

	print "</table>";
	if (empty($user['images']) && (!empty($user['website']) || !empty($user['about_yourself'])))
		print "<i>No images submitted - profile will not display link or the about text</p>";

	$smarty->display('_std_end.tpl');
	exit;
}

##############################

if (!empty($_GET['stats'])) {
	$data = $db->getAll("select source,event_type,count(*),max(event_date)
			,sum(moderation_status='pending') as pending,sum(moderation_status='flagged') as flagged,sum(moderation_status='approved') as approved
		 from moderation group by source,event_type");

	print "<h2>Monitored Content</h2>";
	print "<table cellspacing=0 cellpadding=4 border=1 bordercolor=#eee>";
		print "<tr><th>".implode("</th><th>",array_map('htmlentities',array_keys($data[0])))."</th></tr>";
	foreach($data as $row) {
		//print "<tr><td>".implode("</td><td align=right>",array_map('htmlentities',$row))."</td>";
		print "<tr>";
		foreach($row as $key => $value) {
			if ($key == 'pending' || $key == 'flagged' || $key == 'approved') {
				if ($value)
					print "<td align=right><a href=\"?source={$row['source']}&status=$key\">$value</a>";
				else
					print "<td>";
			} elseif (is_numeric($value)) {
				print "<td align=right>".floatval($value);
			} else {
				print "<td>".htmlentities($value);
			}
		}
	}
	print "</table>";

	$smarty->display('_std_end.tpl');
	exit;
}

##############################

if (!empty($_GET['ai'])) {

	$where = array("ai_class is not null");

	if (!empty($_GET['source']) && preg_match('/^\w+$/',$_GET['source']))
		$where['source'] = "source = ".$db->Quote($_GET['source']);
	else
		$where['source'] = "source = 'user_about'";

	if (!empty($_GET['miss']))
		$where['miss'] = "moderation_status != ai_class";

	if (!empty($_GET['status']) && preg_match('/^\w+$/',$_GET['status']))
		$where['status'] = "moderation_status = ".$db->Quote($_GET['status']);

	if (!empty($_GET['class']) && preg_match('/^\w+$/',$_GET['class']))
		$where['ai'] = "ai_class = ".$db->Quote($_GET['class']);

	$where = implode(' AND ',$where);
	$data = $db->getAll("
	select user_id,moderation_status as status,ai_class,title as content from moderation where $where order by ai_class limit 250
	");

	print "<h2>AI Reviwewed Content (sample)</h2>";
	print "<p>The <tt>status</tt> is human reviewer result, the <tt>ai_class</tt> is the AI prediction. Can click column headers to reorder rows. Sample of upto 250 results";

	print "<script src=\"".smarty_modifier_revision("/sorttable.js")."\"></script>";

	print "<table cellspacing=0 cellpadding=4 border=1 bordercolor=#eee class=\"report sortable\" id=\"photolist\"><THEAD>";
		print "<tr><th>".implode("</th><th>",array_map('htmlentities',array_keys($data[0])))."</th></tr>";
	print "</THEAD><TBODY>";
	foreach($data as $row) {
		print "<tr>";
		foreach($row as $key => $value) {
			if (is_numeric($value)) {
				print "<td align=right>".floatval($value);
			} else {
				print "<td>".htmlentities($value);
			}
		}
	}
	print "</table>";

	$smarty->display('_std_end.tpl');
	exit;
}

##############################

if (!empty($_GET['ai2'])) {

$comments = array(
    'normal'      => 'A **safe and potentially relevant** link.',
    'personal'    => 'A **safe, personal** website (e.g., a hobby blog).',
    'unrelated'   => '**Safe**, but the topic is **not related** to Geograph or the location.',
    'unsafe'      => 'Site appears **unsafe** (e.g., explicit content, security risk, or malware).',
    'mismatch'    => 'Content **doesn\'t match the URL** (likely a domain was bought by spammers).',
    'holding'     => 'The page is now a **static placeholder** (e.g., "Coming Soon" or a parked domain).',
    'spam'        => 'Site appears to be **low-quality marketing or link-dropping spam**.',
    'gone'        => 'The **specific page no longer exists** (returns a "Not Found" error).',
    'unknown'     => 'Unable to determine (e.g., site unclear, or got blocked).',
    'failed'      => 'The **website is completely offline** (server refusal or timeout).',
    '' => 'pending',
);

	$rows = $db->getAssoc("select ai_assessment,count(*),title as example from moderation_all inner join user_stat using (user_id) group by ai_assessment");
	print "<table cellspacing=0 cellpadding=4 border=1>";
	foreach ($comments as $key => $value) {
		print "<tr>";
		print "<td>$key";
		print "<td>".str_replace('**','',$value);
		print "<td>$key";
		if (!empty($rows[$key]))
			print "<td align=right>".implode("<td>",array_map('htmlentities',$rows[$key]))."</td>";
	}
	print "</table><br><br>";


	$where = array("ai_assessment is not null");

	if (!empty($_GET['source']) && preg_match('/^\w+$/',$_GET['source']))
		$where['source'] = "source = ".$db->Quote($_GET['source']);
	elseif (empty($_GET['table']))
		$where['source'] = "source = 'user_website'";

	if (!empty($_GET['miss']))
		$where['miss'] = "moderation_status != ai_class";

	if (!empty($_GET['status']) && preg_match('/^\w+$/',$_GET['status']))
		$where['status'] = "moderation_status = ".$db->Quote($_GET['status']);

	if (!empty($_GET['class']) && preg_match('/^\w+$/',$_GET['class']))
		$where['ai'] = "ai_assessment = ".$db->Quote($_GET['class']);

	$where = implode(' AND ',$where);

	if (!empty($_GET['table']) && $_GET['table'] == 'gridimage_link') {

	$data = $db->getAll("
	select gridimage_id,content_id,ai_assessment,HTTP_Status,url as content from gridimage_link where $where limit 250
	");

	} else {

	$data = $db->getAll("
	select user_id,moderation_status as status,ai_class,ai_assessment,title as content from moderation_all where $where order by ai_class limit 250
	");

	}

	print "<h2>AI Reviewed <u>Content</u> (sample)</h2>";
	print "<p>The <tt>status</tt> is human reviewer result, the <tt>ai_assessment</tt> is the AI assessment of the CONTENT of the URL. Can click column headers to reorder rows. Sample of upto 250 results";

	print "<script src=\"".smarty_modifier_revision("/sorttable.js")."\"></script>";

	print "<table cellspacing=0 cellpadding=4 border=1 bordercolor=#eee class=\"report sortable\" id=\"photolist\"><THEAD>";
		print "<tr><th>".implode("</th><th>",array_map('htmlentities',array_keys($data[0])))."</th></tr>";
	print "</THEAD><TBODY>";
	foreach($data as $row) {
		print "<tr>";
		foreach($row as $key => $value) {
			if (is_numeric($value)) {
				print "<td align=right>".floatval($value);
			} elseif ($key == 'ai_assessment') {
				$color = 'gray';
				if ($value == 'unsafe' || $value == 'spam' || $value == 'mismatch')
					$color = 'red;font-weight:bold';
				elseif ($value == 'personal' || $value == 'normal')
					$color = 'green';
				elseif ($value == 'unrelated')
					$color = 'black';
				print "<td style=color:$color>".htmlentities($value);
			} else {
				print "<td>".htmlentities($value);
			}
		}
	}
	print "</table>";

	$smarty->display('_std_end.tpl');
	exit;
}

##############################

if (!empty($_GET['deleted'])) {

	$data = $db->getAll("
SELECT
  u.user_id, u.realname,
  SUM(gi.moderation_status != 'rejected') AS images,
  SUM(a.approved > 0) AS articles,
  SUM(s.enabled > 0) AS snippets,
  SUM(b.approved > 0) AS blog,
  SUM(t.id>0) AS trips,
  SUM(p.post_id>0) AS posts,
  SUM(m.moderation_status != 'flagged') AS media
FROM user u
LEFT JOIN gridimage gi USING (user_id)
LEFT JOIN article a USING (user_id)
LEFT JOIN snippet s USING (user_id)
LEFT JOIN blog b USING (user_id)
LEFT JOIN geotrips t ON (t.uid = u.user_id)
LEFT JOIN geobb_posts p ON (p.poster_id = u.user_id)
LEFT JOIN moderation m ON (m.user_id = u.user_id AND m.source IN ('media', 'speculative'))
WHERE FIND_IN_SET('deleted', u.rights)
GROUP BY u.user_id
ORDER BY NULL
LIMIT 100");

	print "<h2>Deleted users - checking for NON-taken down content</h2>";
	print "<p>A zero would show they DO have items, but they ARE all taken down. blank means none. Zero or blank is GOOD";
	print "<p>To be clear this is checking the actual content, not counting reports";
	print "<table cellspacing=0 cellpadding=4 border=1 bordercolor=#eee>";
		print "<tr><th>".implode("</th><th>",array_map('htmlentities',array_keys($data[0])))."</th></tr>";
	foreach($data as $row) {
		print "<tr><td>".implode("</td><td align=right>",array_map('htmlentities',$row))."</td>";
	}
	print "</table>";

	print "<p>Note: 'posts' counts all forum posts, including GSD and gallery posts. 'media' counts both media, and speciualtive, rather than two colums";
	print "<p>For technical reasons sitemap links, and faq items not currently included in this table";

	$smarty->display('_std_end.tpl');
	exit;
}

##############################

$where = array();
$where['status'] = "moderation_status = 'pending'";
$order = "event_date DESC";
$size = 30;

if (!empty($_GET['order']))
	$order = "user_id desc";

if (!empty($_GET['moderation_id'])) {
	$moderation_id = intval($_GET['moderation_id']);
	$row = $db->getRow("SELECT * FROM moderation WHERE moderation_id = $moderation_id");
	if (!empty($row['user_id'])) {
		print "<h2>Note: Showing all reports for user_id #".intval($row['user_id'])."</h2>";
		//at the moment, ignore the other filters - even though present!
		$where['status'] = "m.user_id = ".$row['user_id'];
	} else {
		//in unlikly event no id, just use status
		$where['status'] = "moderation_status = ".$db->Quote($_GET['status']);
	}
	$order = "(moderation_id = $moderation_id) DESC, $order"; //make sure it first!
	$size = 300;

} else {
	if (!empty($_GET['source']) && preg_match('/^\w+$/',$_GET['source']))
		$where['source'] = "source = ".$db->Quote($_GET['source']);

	if (!empty($_GET['status']) && preg_match('/^\w+$/',$_GET['status'])) {
		$where['status'] = "moderation_status = ".$db->Quote($_GET['status']);
		if ($_GET['status'] != 'pending')
			$size = 300;
	}
}

if (!empty($_GET['limit'])) {
	$size = min(1000, intval($_GET['limit']));
}


##############################

$links = array(
	'status=pending'=>'Pending by Date',
	'status=pending&order=user'=>'Pending by User',
	'status=flagged&order=user'=>'Flagged',
	'status=flagged&limit=1000&summary=1'=>'Flagged by User',
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

print "<a href=\"https://media.geograph.org.uk/files/b3e3e393c77e35a4a3f3cbd1e429b5dc/Additional_pages_moderation_testing_5_Aug_2025.pdf\" class=about target=_blank>Help Document</a>";


print '</div>';

##############################

$offset = 0;

	print "<div class=interestBox>";
	if (!empty($_GET['status']) && $_GET['status'] != 'pending') {
	        print "<h2>Additional Content, Status = ".htmlentities($_GET['status'])."</h2>";
	} else {
	        print "<h2>Additional Content to Review</h2>";
	}
	print "<i>Note: there is no need to process in any particular order</i>";
	print "</div>";
	print "<br>";

	$where = implode(' AND ',$where);
	$list = $db->getAll("SELECT m.*, user.realname, images, modd.realname AS mod_realname, user.rights
	 FROM moderation m LEFT JOIN user USING (user_id) LEFT JOIN user_stat USING (user_id)
		LEFT JOIN user modd ON (modd.user_id = moderator_id)
	 WHERE $where ORDER BY $order LIMIT $size");

##############################

	if (count($list)) {
		if (!empty($_GET['summary'])) {
			$mat = array();
			$cols = array();
			$rows = array();
			foreach($list as $idx => $row) {
				if ($uid = $row['user_id']) {
					@$mat[$uid][$row['source']] = $row['moderation_status'];
					$rows[$uid] = $row;
					@$cols[$row['source']]++;
				}
			}
			print "<table cellspacing=0 cellpadding=3 border=1 bordercolor=#eee style=\"position:relative\">";
				print "<tr style=\"position:sticky;top:0;background-color:#eee\">";
				print "<td>";
				print "<td>";
				print "<td>";
				foreach($cols as $source => $count) {
					print "<th>$source";
				}
			foreach($mat as $uid => $data) {
				$row = $rows[$uid];
				print "<tr>";
                                $row['url'] = "?preview_user=".intval($row['user_id']); //the actual profile may not show all details!

				print "<td><a name=\"uid{$row['user_id']}\">{$row['user_id']}";
				print "<td><a href=\"{$row['url']}\" target=preview-window>".htmlentities($row['realname'])."</a>";

				print "<td>";
					if (strpos($row['rights'],'basic') !== FALSE) {
						print "<form method=post action=\"#uid{$row['user_id']}\">";
						print "<input type=hidden name=delete_user value=".intval($row['user_id']).">";
						print "<button type=submit name=delete>DELETE USER</button>";
						print "</form>";
					} else {
						print "user deleted";
					}

				foreach($cols as $source => $count) {
					print "<td align=center>";
					if (!empty($data[$source])) {
						print htmlentities($data[$source]);
					}
				}
				if (!empty($row['moderated'])) {
					print "<td><i>{$row['moderation_status']} by ".htmlentities($row['mod_realname']).", ".formatMySQLDateByResolution($row['moderated'])."</i>";
				}
			}
			print "</table>";
			$smarty->display('_std_end.tpl');

			exit;
		}


		//if (!function_exists('smarty_modifier_truncate'))
		//	require_once("smarty/libs/plugins/modifier.truncate.php");

		print '<div class="grid-container">';

		$last = null;
                foreach ($list as $idx => $row) {
			if (!empty($_GET['order']) && $row['user_id'] != $last) {
				print "<div class=\"grid-item header\"><a name=\"uid{$row['user_id']}\">".htmlentities($row['realname'])."</div>";

				$last = $row['user_id'];
			}
			$className = "row{$row['moderation_id']}";

			#############################################################

			print "<div class=\"grid-item main-cell $className\">";
				print '<div class="date">';
				print formatMySQLDateByResolution($row['event_date']);
				print '</div>';
			if (preg_match('/^user/',$row['source']) && !empty($row['user_id']))
				$row['url'] = "?preview_user=".intval($row['user_id']); //the actual profile may not show all details!
			$row['url'] = str_replace('view.php?','view.php?login=true&',$row['url']); //encourage media server to request login!
			print "<a href=\"".htmlentities($row['url'])."\" target=preview-window>";
			print "<b>".htmlentities($row['title'])."</b>";
			print "</a>";
			if (strpos($row['title'],'...') !== FALSE)
				print " [TRUNCATED]";

			if ($row['user_id']) {
				print "<span class=nowrap>";
				if ($row['title']!=$row['realname'])
					print " by <a href=\"/profile/{$row['user_id']}\" target=preview-window>".htmlentities($row['realname'])."</a>";
				if (!$row['images']) {
					print " [new user]";
				} else {
					print " [".intval($row['images'])."]";
				}
				if (strpos($row['rights'],'basic') === FALSE) {
					print "<span style=color:brown>&middot <b>User deleted</b>.</span>";
				}
				print "</span>";
			}
			if (!empty($row['moderated'])) {
				print "<br><br><i>{$row['moderation_status']} by ".htmlentities($row['mod_realname']).", ".formatMySQLDateByResolution($row['moderated'])."</i>";
			}
			if ($row['source'] == 'user' && $row['user_id'] && $row['moderation_status'] == 'flagged') {
				if (strpos($row['rights'],'basic') !== FALSE) {
					print "<form method=post action=\"#uid{$row['user_id']}\">";
					print "<input type=hidden name=delete_user value=".intval($row['user_id']).">";
					print "<button type=submit name=delete>DELETE USER</button>";
					print "</form>";
				}
			}

			print '</div>';

			#############################################################

			print "<div class=\"grid-item $className\">";
			print $row['source'];
			if ($row['event_type'] != 'creation')
				print "/".$row['event_type'];
			if (!empty($row['media_url'])) {
				$url = htmlentities($row['media_url']);
				print "<br><a href=\"$url\" target=preview-window>";
				if (preg_match('/\.(jpe?g|gif|png|webp)$/',$row['media_url'])) {
					$url = str_replace(".org.uk/",".org.uk/preview.php/",$url); //needs to be able to see the preview!
					print "<img src=\"$url\">";
				} else {
					print htmlentities(basename($row['media_url']));
				}
				print "</a>";
			}
			print '</div>';

			#############################################################

			print "<div class=\"grid-item $className\">";
				print "<form method=post class=\"ajax-form $className\">"; //for now each is a seperate form submission!
			print "<button type=submit name=status[{$row['moderation_id']}] value=approved>Looks Safe</button>";
			print "<button type=submit name=status[{$row['moderation_id']}] value=flagged>Flag!</button>";
			print "<button type=submit name=status[{$row['moderation_id']}] value=extreme>Alarm!</button>";
				print "</form>";
			print '</div>';

			#############################################################
                }
		print '</div>';

		if (count($list) == $size && (empty($_GET['status']) || $_GET['status'] == 'pending')) {
			$_GET['offset'] = $offset+$size;
			$query = htmlentities(http_build_query($_GET));
			print "<div class=interestBox><a href=?$query>More...</a></div>";
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
    padding: 6px;
    border-bottom: 1px solid #ddd; /* Inner borders for cells */
    text-align: center;
}

.grid-item.header {
    grid-column: 1 / -1;
    background-color: #e0e0e0;
    font-weight: bold;
    padding: 10px;
    text-align: center;
}

/* Make the first column (1st, 4th, 7th, 10th... grid item) left-aligned */
.grid-item.main-cell {
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
.grid-container button[value=extreme] {
	background-color:red;
	color:white;
}
.grid-container button[name=delete] {
	background-color:pink;
}

</style>

<script>
const forms = document.querySelectorAll('.ajax-form');

forms.forEach(form => {
  form.addEventListener('submit', async function(event) { //Must be 'async'
    event.preventDefault();

    const formData = new FormData(form);

    // Get the button that was clicked to submit the form
    const submitter = event.submitter;

    // Add the submitter's name and value to the FormData
    if (submitter && submitter.name && submitter.value) {
        formData.append(submitter.name, submitter.value);
    }

    let classNameToRemove = '';
    // Iterate through the list of classes on the form
    form.classList.forEach(className => {
      // Find the class that isn't 'ajax-form'
      if (className !== 'ajax-form') {
        classNameToRemove = className;
        return;
      }
    });

    // Send the request with the updated formData
    fetch(form.action, {
      method: form.method,
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    .then(response => {
      if (response.status === 204) {

        if (classNameToRemove) {
          const elementsToRemove = document.querySelectorAll('.' + classNameToRemove);
          elementsToRemove.forEach(element => {
            element.remove();
          });
        }

      } else if (response.ok) {
        return response.json();
      } else {
        console.error('Submission failed with status:', response.status);
      }
    })
    .catch(error => {
      console.error('An error occurred:', error);
    });
  });
});

</script>
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
