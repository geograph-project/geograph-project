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

$cacheid = $USER->hasPerm('basic')?$USER->user_id:0;

$isadmin=$USER->hasPerm('moderator')?1:0;

foreach($_POST as $key => $value) {
	if (preg_match('/status_(\d+)/',$key,$m))
		$_POST['status'][$m[1]] = $value;
}

if ($isadmin && !empty($_POST['status'])) {
	$db = GeographDatabaseConnection(false);
	foreach($_POST['status'] as $id => $value) {
		if (!empty($id) && preg_match('/^\d+$/',$id)) {

			$id = intval($id);
			$value = $db->Quote($value);

			$sql = "UPDATE project_idea SET status = $value WHERE project_idea_id = $id";
			$db->Execute($sql);

			$smarty->clear_cache('project_ideas.tpl');
			$smarty->clear_cache('project_ideas.tpl',$cacheid);
			$smarty->clear_cache('project_idea.tpl',$id);
			$smarty->clear_cache('project_idea.tpl',$id."|".$USER->user_id);
		}
	}
}


$smarty->display('_std_begin.tpl');


	$db=GeographDatabaseConnection(false);
	$prev_fetch_mode = $ADODB_FETCH_MODE;
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	$order = "created";
	if (!empty($_GET['order']) && preg_match('/^\w+/',$_GET['order'])) {
		$order = $_GET['order'];
	}
	$smarty->assign('order',$order);

	if ($order == "created" || $order == "updated")
		$order = "i.$order";

	$filter = "status NOT IN('invalid','complete')";
	if (!empty($_GET['status']) && preg_match('/^\w+/',$_GET['status']))
		$filter = "status = ".$db->Quote($_GET['status']);

	$list = $db->getAll($sql = "
	select i.*,realname,
		count(distinct IF(item_type='pledge',t.project_idea_item_id,NULL)) as pledges,
		count(distinct IF(item_type='reason',t.project_idea_item_id,NULL)) as reasons,
		greatest(max(t.created),max(v.created)) as activity,
		sum(10/POW(vote,1.1))*count(distinct v.project_idea_vote_id)/count(*) as score
	from project_idea i
		left join user using (user_id)
		left join project_idea_item t on (t.project_idea_id = i.project_idea_id and t.approved=1)
		left join project_idea_vote v on (v.project_idea_id = i.project_idea_id and v.approved=1 and v.vote > 0)
	where ((i.approved = 1 and i.published < now())
		or i.user_id = {$USER->user_id}
		or ($isadmin and i.approved != -1)
		)
		and $filter
	group by i.project_idea_id
	order by $order desc");

	print "<form method=post>";
	print "<table cellspacing=0 cellpadding=2 border=1 bordercolor=#eee>";
	foreach($list as $row) {
		print "<tr>";
		print "<td>".htmlentities($row['status']);
		print "<td><b>".htmlentities($row['title'])."</b>";
		print "<td rowspan=2 style=font-size:0.6em;max-width:90ch>".htmlentities($row['content']);

		print "<tr>";
		print "<td colspan=2>";
		if ($isadmin) {
			if ($row['status'] == 'new') {
				print "<label><input type=checkbox name=\"status[{$row['project_idea_id']}]\" value=invalid>Invalid</label> ";
				print "<label><input type=checkbox name=\"status[{$row['project_idea_id']}]\" value=inprogress>in Progress</label> ";
			}
			print "<label><input type=checkbox name=\"status[{$row['project_idea_id']}\" value=complete>Complete</label> ";
		}
		print "<tr>";
		print "<td colspan=3>&nbsp;";
	}
	print "</table>";
	if ($isadmin) {
		print "<input type=submit>";
	}
	print "</form>";


$smarty->display('_std_end.tpl');


