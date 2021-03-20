<?php
/**
 * $Project: GeoGraph $
 * $Id: submissions.php 6368 2010-02-13 19:45:59Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2011 Barry Hunter (geo@barryhunter.co.uk)
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
require_once('geograph/imagelist.class.php');
init_session();


$smarty = new GeographPage;

$USER->mustHavePerm("basic");

dieUnderHighLoad(1);

customGZipHandlerStart();

if (empty($CONF['forums'])) {
	$template = "static_404.tpl";
	$smarty->display($template);
	exit;
}

$smarty->assign("page_title",'Disussion Threads');

//we dont use smarty caching because the page is so big!
$smarty->display("_std_begin.tpl",md5($_SERVER['PHP_SELF']));
print '<link rel="stylesheet" type="text/css" title="Monitor" href="'.smarty_modifier_revision("/discuss/bb_default_style.css").'" media="screen" />';

$db = GeographDatabaseConnection(false);

if (!empty($_GET['post_id'])) {
	$post_id = intval($_GET['post_id']);
	if (!empty($_GET['vote'])) {
		$vote = intval($_GET['vote']);
		$user_id = intval($USER->user_id);
		$row = $db->getRow("SELECT post_id FROM geobb_promoted WHERE post_id = $post_id");
		if (empty($row)) {
			$db->Execute("INSERT INTO geobb_promoted SET post_id = $post_id, user_id = $user_id, created = NOW()");
		} else {
			$votes = $db->getOne("SELECT SUM(vote) FROM geobb_promoted_log WHERE post_id = $post_id AND user_id != $user_id");
			$votes+=$vote;
			if ($votes < 0) $votes=0;
			$updates = "votes = $votes";
			if ($votes == 2) $updates .= ", vote_second = IF(vote_second<'1000-00-00',NOW(),vote_second)";
			if ($votes == 5) $updates .= ", vote_fifth = IF(vote_fifth<'1000-00-00',NOW(),vote_fifth)";
			if ($votes ==10) $updates .= ", vote_tenth = IF(vote_tenth<'1000-00-00',NOW(),vote_tenth)";

			$db->Execute("UPDATE geobb_promoted SET $updates WHERE post_id = $post_id");
		}
		$db->Execute("INSERT INTO geobb_promoted_log SET post_id = $post_id, user_id = $user_id, vote = $vote, created = NOW() ON DUPLICATE KEY UPDATE vote = $vote");

	} else {
		print "<a href=\"?post_id=$post_id&amp;vote=1\">Click here to confirm your vote</a>";
		$smarty->display("_std_end.tpl");
		exit;
	}
}


$where = '1';
$order = 'votes/(datediff(now(),created)+1) DESC';
$limit = '40';
$extra = '';

if (empty($_GET['order'])) $_GET['order'] =''; //just to avoid a warning!
if (empty($_GET['forum'])) $_GET['forum'] =''; //just to avoid a warning!

?><div class="interestBox" style="float:right">
<form method=get>
Order by: <select name="order" onchange="this.form.submit()">
	<option value="">Default</option>
	<? $array = array('votes'=>'Votes','a.created'=>'Added','post_id'=>'Post Created','t.topic_id'=>'Thread Created');
	foreach ($array as $key =>$value) {
		printf('<option value="%s"%s>%s</option>',$key,($key==$_GET['order'])?' selected':'',$value);
		if ($key == $_GET['order']) {
			$order = "$key DESC";
			$extra .= "&amp;order=$key";
		}
	} ?>
	</select><br/>
Forum(s): <select name="forum" onchange="this.form.submit()">
	<option value="">All</option>
	<option value="." <?
		if ('.' == $_GET['forum']) {
			print ' selected';
			$showIds = $USER->getForumOption('show','',false);
	                if (!empty($showIds)) {
        	                $where .= " AND t.forum_id IN ($showIds)";
			}
                        $extra .= "&amp;forum=.";
                }
	?>>Your Selected</option>
        <? $rows = $db->getAll("select forum_id,forum_name from geobb_forums");
        foreach ($rows as $idx =>$row) {
                printf('<option value="%s"%s>%s</option>',$row['forum_id'],($row['forum_id']==$_GET['forum'])?' selected':'',$row['forum_name']);
                if ($row['forum_id'] == $_GET['forum']) {
                        $where .= " AND t.forum_id = {$row['forum_id']}";
                        $extra .= "&amp;forum={$row['forum_id']}";
                }
        } ?>
        </select><br/>
	<input type=checkbox name=read value=1 <? if (!empty($_GET['read'])) { echo 'checked'; } ?> onclick="this.form.submit()"/> Include Read posts
</form>
</div>
<style>
.caption4 a {
	color:yellow;
	text-decoration:none;
	font-size:large;
	margin-left:10px;
}
.caption1 div.clipper {
	height:100px;
	overflow:hidden;
	text-overflow: ellipsis;
}
</style>
<?

if (empty($_GET['read'])) {
	$where = "(l.last_post_id < p.post_id OR l.topic_id IS NULL) AND l.muted != 1";
} else {
	$extra .= "&amp;read=1";
}

$order .= ", post_id DESC";

$rows = $db->getAll("SELECT a.post_id, t.topic_id, topic_title, post_text, poster_name, post_time, t.forum_id, votes, last_post_id, posts_count
FROM geobb_promoted a INNER JOIN geobb_posts p USING (post_id) INNER JOIN geobb_topics t USING (topic_id)
LEFT JOIN geobb_lastviewed l ON(t.topic_id = l.topic_id AND l.user_id = $USER->user_id)
WHERE $where ORDER BY $order LIMIT $limit");

print "<h2>Promoted <a href=\"/discuss/\">Discussion</a> Posts</h2>";
print "<p>Add posts to this list by clicking the 'Promote this post' when hovering over the post in question.</p>";
print "<table class=forums>";
$last = -1;
foreach ($rows as $idx => $row) {
	if ($last != $row['topic_id']) {
		print "<tr><td colspan=2 class=caption4>";
		print "<div style=\"float:right;position:relative\">{$row['posts_count']} Posts</div>";
		print "<big><a href=\"/discuss/index.php?&amp;action=vthread&amp;forum={$row['forum_id']}&amp;topic={$row['topic_id']}\">{$row['topic_title']}</a></big></td></tr>";
		$last = $row['topic_id'];
	}
	$f = ($idx%2)+1;

	print "<tr class=tbCel$f><td valign=top class=caption1><b>".htmlentities($row['poster_name'])."</b><br/>";
	print "{$row['post_time']}<br/></td>";
	print "<td valign=top class=caption1><div class=clipper>{$row['post_text']}</div><br/><br/></td>"; //its already coded as html
	print "</tr>";

        print "<tr class=tbCel$f><td valign=top class=caption1>";
	if (!empty($_GET['read'])) {
		if (empty($row['last_post_id']) || $row['last_post_id'] < $row['post_id']) {
			print "<img src=https://s1.geograph.org.uk/discuss/img/topic_updated.gif> Unread";
		}
	}
	print "<br/><br/></td>";
        print "<td valign=top class=caption1>";
	print "<div style=\"float:right;position:relative\">{$row['votes']} <a href=\"?post_id={$row['post_id']}&amp;vote=1$extra\">Vote Up</a> <a href=\"?post_id={$row['post_id']}&amp;vote=-1$extra\">Vote Down</a></div>";
	print "<a href=\"/discuss/index.php?&amp;action=vpost&amp;forum={$row['forum_id']}&amp;topic={$row['topic_id']}&amp;post={$row['post_id']}\"><b>View Post in Thread</b></a></td>";
        print "</tr>";
}
print "</table>";

?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<script>
$(function(){
	$('div.clipper').each(function( index ) {
		var that = $(this);
		if (that.prop('scrollHeight') > 100) {
			var ele = $('<a>');
			ele.text('expand...').prop('href','#');
			that.after(ele);
			ele.click(function() {
				that.css('height','inherit');
				$(this).remove();
				return false;
			});
		} else {
			that.css('height','inherit');
		}
	});
});
</script>
<?

$smarty->display("_std_end.tpl");



