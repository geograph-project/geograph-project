<?php
/**
 * $Project: GeoGraph $
 * $Id: faq.php 15 2005-02-16 12:23:35Z lordelph $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2007 Barry Hunter (geo@barryhunter.co.uk)
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

if (empty($CONF['forums'])) {
	$smarty = new GeographPage;
        $smarty->display('static_404.tpl');
        exit;
}

init_session();

$smarty = new GeographPage;

if (empty($_GET['api'])) {
$USER->mustHavePerm('basic');

$smarty->display('_std_begin.tpl');


?>
<script src="/sorttable.js"></script>
<?
}

	$db=GeographDatabaseConnection(false);
	$prev_fetch_mode = $ADODB_FETCH_MODE;
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	if (!empty($_GET['method']) && $_GET['method'] == 'POST')
		$_POST = $_GET; //bodge alert!

        if (!empty($_POST['lookup'])) {
                $id = $db->getOne("SELECT systemtask_id FROM systemtask WHERE status = 'active' AND title = ".$db->Quote($_POST['lookup']));
                if (empty($id))
                        die("unable to find task\n");
                $_POST['id'] = array($id);
        }


if (!empty($_POST['title'])) {
	$db->Execute("INSERT INTO systemtask SET title = ".$db->Quote($_POST['title']).
		", user_id = {$USER->user_id}, created = NOW()");
	header("Location: ?");
	exit;

} elseif (!empty($_GET['next'])) {
	if (!empty($_POST['id'])) {
		$id = intval($_POST['id']);
		$when = 'NOW()';
		$event = '';
		if (!empty($_POST['spotcheck'])) {
			$db->Execute("UPDATE systemtask SET `spotcheck` = $when WHERE status = 'active' AND systemtask_id = $id");
			$event = 'spotcheck';
		}
		if (!empty($_POST['thorough'])) {
			$db->Execute("UPDATE systemtask SET `thorough` = $when WHERE status = 'active' AND systemtask_id = $id");
			$event = 'thorough';
		}
		if (!empty($event)) {
			$db->Execute("INSERT INTO systemtask_log SET systemtask_id = $id, `when` = $when ".
        		        ", user_id = {$USER->user_id}, created = NOW(), `event` = ".$db->Quote($event));
		}
		if (!empty($_POST['skip'])) {
			$event = 'skip';
			$db->Execute("INSERT INTO systemtask_log SET systemtask_id = $id, `when` = $when ".
        		        ", user_id = {$USER->user_id}, created = NOW(), `event` = ".$db->Quote($event));
		}
		if (!empty($_POST['assign'])) {
			$db->Execute("UPDATE systemtask SET user_id = ".intval($USER->user_id)." WHERE systemtask_id = $id");
			$event = 'assign';
			$db->Execute("INSERT INTO systemtask_log SET systemtask_id = $id, `when` = $when ".
        		        ", user_id = {$USER->user_id}, created = NOW(), `event` = ".$db->Quote($event));
		}
	}

	//delete old skips on allocated tasks. (ie so skips on your own tasks expire!) - note the user_id is used in the join using!
	$db->execute("delete systemtask_log.* from  systemtask inner join systemtask_log using (systemtask_id,user_id) where `event` ='skip' and systemtask_log.created < date_sub(now(),interval 14 day)");

	$where = array();
	$where[] = "status = 'active'";
	$where[] = "systemtask_log_id is null";
	$bits[] = "spotcheck < date_sub(now(),interval 10 day) and t.user_id = {$USER->user_id}";
	$bits[] = "spotcheck < date_sub(now(),interval 90 day) and t.user_id != {$USER->user_id} and comment != ''";
	$bits[] = "spotcheck IS NULL and comment != ''";
	$where[] = "((".implode(") OR (",$bits)."))";

	$row = $db->getRow($sql = "select t.* from systemtask t left join systemtask_log l on (t.systemtask_id = l.systemtask_id and l.user_id = {$USER->user_id} and `event` ='skip') where ".implode(' and ',$where)." limit 1");

	if (!empty($row)) {
		print "<h2>#".$row['systemtask_id'].". ".htmlentities($row['title'])."</h2>";
		print "<div class=interestBox>".htmlentities($row['comment'])."</div>";
		if (empty($row['spotcheck'])) {
			$row['spotcheck'] = 'never!';
		}
		print "<p>Last checked: {$row['spotcheck']}</p>";

		$disabled = "";
		if ($row['user_id'] != $USER->user_id) {
			$disabled = 'disabled';
		}
		?>
		<form method=post>
			<input type=hidden name="id" value="<? print $row['systemtask_id']; ?>" />
			<input type=submit name=spotcheck value="Spot Check Done" <? echo $disabled;?>/>
			<input type=submit name=thorough value="Thorough Check Done" <? echo $disabled;?>/>
			<input type=submit name=skip value="Skip This Task for now" />
			<script>
			function ticked(that) {
				if (that.checked) {
					for(q=0;q<that.form.elements.length;q++) {
						var ele = that.form.elements[q];
						if (ele.tagName.toLowerCase() == 'input' && (ele.type.toLowerCase() == 'submit'))
							ele.disabled = false;
					}
				}
			}
			</script>
		<?


		if ($row['user_id'] != $USER->user_id) {
			print "<p>This task is not assigned to you, tick this box: <input type=checkbox onclick='ticked(this)'> to confirm you truely understand this task.</p>";
			print "<p>Tick this box: <input type=checkbox name='assign'> to assign this task to you. (you intend to look at it in future)</p>";
		}
		print "</form>";
	} else {
		print "No tasks!";
	}


} elseif (!empty($_POST['id'])) {
	if (empty($_POST['when']) || $_POST['when'] == 'now') {
		$when = 'NOW()';
	} else {
		$t=strtotime(trim($_POST['when']));
                $today=strtotime("today");

                if ($t>$today) {
	                $d=strftime("%a, %d-%b-%Y %H:%M", $t);
                        die("evaluated as a future day ($d)");
                } else {
                        $when=strftime("'%Y-%m-%d'", $t);
		}
	}
	foreach ($_POST['id'] as $id) {
		$id = intval($id);
		if (!empty($_POST['spotcheck'])) {
			$db->Execute("UPDATE systemtask SET `spotcheck` = $when WHERE status = 'active' AND systemtask_id = $id");
			$event = 'spotcheck';
		}
		if (!empty($_POST['thorough'])) {
			$db->Execute("UPDATE systemtask SET `thorough` = $when WHERE status = 'active' AND systemtask_id = $id");
			$event = 'thorough';
		}
		if (!empty($_POST['delete'])) {
			$db->Execute("UPDATE systemtask SET `status` = 'deleted' WHERE status = 'active' AND systemtask_id = $id");
			$event = 'delete';
		}
		if (!empty($_POST['assign'])) {
			$db->Execute("UPDATE systemtask SET user_id = ".intval($_POST['user_id'])." WHERE systemtask_id = $id");
			$event = 'assign';
		}
		if (!empty($_POST['comment'])) {
			$db->Execute("UPDATE systemtask SET comment = ".$db->Quote($_POST['comment'])." WHERE systemtask_id = $id");
			if ($db->Affected_Rows() > 0)
				$event = 'comment';
		}
		if (empty($_POST['skip']) && !empty($event)) {
			$db->Execute("INSERT INTO systemtask_log SET systemtask_id = $id, `when` = $when ".
        		        ", user_id = {$USER->user_id}, created = NOW(), `event` = ".$db->Quote($event));
		}
	}
	if (!empty($_POST['api'])) {
		print "ok";
	} elseif (count($_POST['id']) == 1) {
		header("Location: ?id=".intval($id));
	} else
		header("Location: ?");
	exit;

} elseif (!empty($_GET['id']) && $_GET['id'] == '*') {

        print "<h3><a href=?>Tasks</a> :: last 100 Log entries</h3>";
        $rows = $db->getAll("SELECT l.*,t.title,(l.user_id=t.user_id OR l.user_id = 3) as valid FROM systemtask_log l INNER JOIN systemtask t USING (systemtask_id) WHERE l.user_id > 0 AND event != 'skip' ORDER BY systemtask_log_id DESC LIMIT 100");
        if (!empty($rows)) {

        print "<table border=1 cellspacing=0 cellpadding=4>";
        print "<tr><th>Task</th>";
        print "<th>Who</th>";
        print "<th>When</th>";
        print "<th>Event</th>";
        print "<th>Date</th></tr>";
        foreach ($rows as $row) {
		if (!$row['valid']) {
			print "<tr style=background-color:silver>";
		} else
	                print "<tr>";
		print "<td><a href=\"?id={$row['systemtask_id']}\">".htmlentities($row['title'])."</a></td>";
                print "<td>".htmlentities($row['user_id'])."</td>";
                print "<td>".htmlentities($row['when'])."</td>";
                print "<td>".htmlentities($row['event'])."</td>";
                print "<td>".substr($row['created'],0,10)."</td>";
                print "</tr>";
        }
        print "</table>";
	}

} elseif (!empty($_GET['id'])) {
	$row = $db->getRow("SELECT * FROM systemtask WHERE status = 'active' AND systemtask_id = ".intval($_GET['id']));
	if (!empty($row)) {
		print "<h3><a href=?>Tasks</a> :: ".htmlentities($row['title'])."</h3>";

		?>
		<form method=post>
			<textarea name="comment" rows=4 cols=80 wrap=soft><? echo htmlentities($row['comment']); ?></textarea><input type=submit name=update value="Update Description"><br>
			<input type=hidden name="id[]" value="<? print $row['systemtask_id']; ?>" />
			<input type=submit name=spotcheck value="Spot Check Done" />
			<input type=submit name=thorough value="Thorough Check Done " />
			<input type=text name=when value=now size=10> or
			<input type=submit name=delete value="Delete" onclick="return confirm('are you sure?');"/> or
			<input type=text name=user_id size=5><input type=submit name=assign value="Assign" />
		</form>
		<?

		print "<h3>Task Log</h3>";
		$rows = $db->getAll("SELECT * FROM systemtask_log WHERE systemtask_id = ".intval($_GET['id'])." AND event != 'skip' ORDER BY systemtask_log_id DESC");
		if (!empty($rows)) {

	print "<table border=1 cellspacing=0 cellpadding=4>";
	print "<tr><th>Who</th>";
	print "<th>When</th>";
	print "<th>Event</th>";
	print "<th>Date</th></tr>";
	foreach ($rows as $row) {
		print "<tr>";
		print "<td>".htmlentities($row['user_id'])."</td>";
		print "<td>".htmlentities($row['when'])."</td>";
		print "<td>".htmlentities($row['event'])."</td>";
		print "<td>".substr($row['created'],0,10)."</td>";
		print "</tr>";
	}
	print "</table>";

		}

	} else {
		print "No task found";
	}

} elseif (isset($_GET['overdue'])) {

	$all = $db->getCol("select expected FROM systemtask where expected != '' AND status = 'active'group by expected");

	foreach ($all as $interval) {
		print "<h3>$interval</h3>";
		$rows = $db->getAll("SELECT systemtask_id,title,spotcheck,notified,(spotcheck > date_sub(NOW(), INTERVAL $interval)) as done FROM systemtask WHERE expected = ".$db->quote($interval)." AND status = 'active'");
		if (!empty($rows)) {

	print "<table border=1 cellspacing=0 cellpadding=4>";
	print "<tr><th>Task</th>";
	print "<th>Spotcheck</th>";
	print "<th>Done?</th>";
	print "<th>Notifed?</th>";
	print "</tr>";
	foreach ($rows as $row) {
		print "<tr>";
                print "<td><a href=\"?id={$row['systemtask_id']}\">".htmlentities($row['title'])."</a></td>";
		print "<td>".htmlentities($row['spotcheck'])."</td>";
		print "<td>".htmlentities($row['done'])."</td>";
		print "<td>".htmlentities($row['notified'])."</td>";
		print "</tr>";
	}
	print "</table>";
		}
	}


} elseif (isset($_GET['spark'])) {

?>
        <h3><a href=?>Tasks</a> :: Time between checks</h3>
	<p>The sparkline shows the time gap between successive spotchecks, the last point on each graph is the time between the last check and NOW. A flat line shows a check is happening regually.</p>
	<p>A upturn in the line suggests, that a test is overdue or has recently began failing. (note that lines taking a downturn on the last point is normal, because a test isnt due yet).</p>
	<p>No vertical scale is shown because some checkes happen hourly, or daily, whereas others are weekly</p>
    	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
   	<script type="text/javascript" src="/js/jquery.sparkline.min.js"></script>
	<table>
<?
	$where = '';
	if (isset($_GET['u'])) {
		$u = intval($_GET['u']);
		$where .= " AND t.user_id = $u";
		$realname = $db->getOne("SELECT realname FROM user WHERE user_id=$u AND rights LIKE '%basic%'");
		print "<br>Only showing tasks assigned to <a href=\"/profile/$u\">".htmlentities($realname)."</a> (click to view Geograph Profile) - <a href=\"?spark\">show all</a>";
	}

	$rows = $db->getAll("select systemtask_id,title,count(*) as logs, group_concat(`when` order by `when` desc) as whens from systemtask t inner join systemtask_log l using (systemtask_id) where t.status = 'active' and event in ('spotcheck','thorough') $where group by systemtask_id having logs > 2");
	foreach ($rows as $row) {
		$values = array();
		$time = time();
		$bits = explode(',',$row['whens']);
		if (strlen($row['whens']) == 1024) array_pop($bits); //the last one is likly to be truncated;
		foreach ($bits as $bit) {
			$t = strtotime($bit);
			$d = $time - $t;
			array_unshift($values,$d);
			$time = $t;
		}
		$average = array_sum($values) / count($values);
		$last = end($values);
		$values = implode(',',$values);
		if ($last > $average*1.4) {
			print "<tr style=background-color:pink>";
		} else {
			print "<tr>";
		}
		print "<td align=right><span class=sparkline>$values</span></td>";
		print "<td>".substr($bits[0],0,10)."</td>";
		print "<td><a href=\"?id={$row['systemtask_id']}\">".htmlentities($row['title'])."</a></td>";
		print "<td>x {$row['logs']}</td>";
		print "</tr>";
	}
?>
	</table>
<script>
$(function(){
	$('.sparkline').sparkline();
});
</script>
<?

} else {
	$where = '';
	print "<h2>Ongoing Tasks</h2>";
	print "<p>These are regular system administration tasks performed by a team of volenteers, to keep the Geograph Website online and functional. If you would like to partipate, see <a href=\"/discuss/index.php?&action=vthread&forum=1&topic=25178\">Thread</a>. Also <a href=\"?id=*\">View all recent log entries</a></p>";
	print "Theme (filter by): ";
	foreach(explode(',','Disk Space, RAID, SMART,Backup,Offsite,Database,FileSystem,Public DB,Sphinx') as $key) {
		if (isset($_GET['q']) && $key == $_GET['q']) {
			print "<b>".htmlentities($key)."</b> &middot; ";
		} else {
			print "<a href=\"?q=".urlencode($key)."\">".htmlentities($key)."</a> &middot; ";
		}
	}
	if (!empty($_GET['q'])) {
		$where = " AND title LIKE ".$db->Quote('%'.$_GET['q'].'%');
		print "<a href=?>all</a>";
	}

	if (isset($_GET['u'])) {
		$u = intval($_GET['u']);
		$where .= " AND s.user_id = $u";
		$realname = $db->getOne("SELECT realname FROM user WHERE user_id=$u AND rights LIKE '%basic%'");
		print "<br>Only showing tasks assigned to <a href=\"/profile/$u\">".htmlentities($realname)."</a> (click to view Geograph Profile) - <a href=?>show all</a>";
	}
	if (isset($_GET['days'])) {
		$days = intval($_GET['days']);
		$where .= " AND  greatest(ifnull(spotcheck,0),ifnull(thorough,0)) < date_sub(now(),interval $days day)";
		print "<br>Only showing task not checked in last $days days  - <a href=?>show all</a>";
	}

	$rows = $db->getAll("SELECT s.*,realname FROM systemtask s LEFT JOIN user USING (user_id) WHERE status = 'active' $where");
	print "<form method=post><table class=\"report sortable\" id=\"photolist\" border=1 cellspacing=0 cellpadding=4>";


	print "<thead><tr><td>S</td>";
	print "<td>Task</td>";
	print "<td>Last Spot Check</td>";
	print "<td>Last Thorough Check</td>";
	print "<td>Volunteer</th></tr></thead><tbody>";
	$i = 0;
	foreach ($rows as $row) {
		$span = (!empty($_GET['desc']) && !empty($row['comment']))?' rowspan=2':'';
		$bgcolor = ' style="background-color:'.($i%2?'#ddd':'#fff').'"';
		print "<tr $bgcolor><td $span><input type=checkbox name=\"id[]\" value=\"{$row['systemtask_id']}\"/></td>";
		print "<td><a href=\"?id={$row['systemtask_id']}\">".htmlentities($row['title'])."</a></td>";
		if (!empty($row['thorough']) && $row['thorough'] > $row['spotcheck']) {
			print "<td style=color:gray>".substr($row['thorough'],0,10)."</td>";
		} elseif (empty($row['spotcheck'])) {
			print "<td style=color:gray></td>";
		} else {
			print "<td>".substr($row['spotcheck'],0,10)."</td>";
		}
		if (empty($row['thorough'])) {
			print "<td style=color:gray></td>";
		} else {
			print "<td>".substr($row['thorough'],0,10)."</td>";
		}
		print "<td><a href=\"?u={$row['user_id']}\">".htmlentities($row['realname'])."</a></td>";
		print "</tr>";
		if (!empty($_GET['desc']) && !empty($row['comment'])) {
			print "<tr $bgcolor><td colspan=4 style=font-size:small>".htmlentities($row['comment'])."</td></tr>";
		}
		$i++;
	}
	print "</tbody></table>";

?>
	Apply for ticked items above:
        <input type=submit name=spotcheck value="Spot Check Done" />
        <input type=submit name=thorough value="Thorough Check Done " />
        <input type=text name=when value=now size=10> or
        <input type=submit name=delete value="Delete" onclick="return confirm('are you sure?');"/> or
	<input type=text name=user_id size=5><input type=submit name=assign value="Assign" />
</form>

<form method=post>
	<h3>Create new task</h3>
	<input type=text name=title size=60 maxlength=100 /> <input type=submit name=create value="Create" />
</form>
<?

}



$smarty->display('_std_end.tpl');

