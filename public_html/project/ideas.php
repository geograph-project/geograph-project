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
$cacheid .= '|'.md5(implode(',',$_GET));

$isadmin=$USER->hasPerm('moderator')?1:0;
$smarty->assign_by_ref('isadmin', $isadmin);

$template = 'project_ideas.tpl';
if (!empty($_GET['vote'])) {
        $template = 'project_ideas_vote.tpl';
}

if ($isadmin) {
	if (!empty($_GET['id']) && preg_match('/^\d+$/',$_GET['id'])) {
		$db = GeographDatabaseConnection(false);

		$a = intval($_GET['approve']);

		$sql = "UPDATE project_idea SET approved = $a WHERE project_id = ".$db->Quote($_GET['id']);
		$db->Execute($sql);

		$smarty->clear_cache('project_ideas.tpl');
		$smarty->clear_cache('project_ideas.tpl',$cacheid);
		$smarty->clear_cache('project_idea.tpl',$_REQUEST['id']);
		$smarty->clear_cache('project_idea.tpl',$_REQUEST['id']."|".$USER->user_id);
	}
}


if (!$smarty->is_cached($template, $cacheid))
{
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

	//for the score calculation see http://stackoverflow.com/questions/2436284/mysql-sum-for-distinct-rows
	// because there can be multiple 'items' per idea, the vote rows can be counted many times, throwing off the SUM()


	$smarty->assign_by_ref('ideas', $list);
}

$smarty->display($template, $cacheid);

