<?php
/**
 * $Project: GeoGraph $
 * $Id: faq.php 15 2005-02-16 12:23:35Z lordelph $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2006 Barry Hunter (geo@barryhunter.co.uk)
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

$USER->mustHavePerm('basic');
$isadmin=$USER->hasPerm('moderator')?1:0;

if (empty($_REQUEST['id'])) {
	$smarty->display('static_404.tpl');
	exit;
}


$template = 'project_idea_item.tpl';
$cacheid = '';

	$db=GeographDatabaseConnection(false);
	if ($_REQUEST['id'] == 'new') {

	} else {
		$sql_where = " project_idea_id = ".$db->Quote($_REQUEST['id']);

		$prev_fetch_mode = $ADODB_FETCH_MODE;
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
		$page = $db->getRow($sql = "
		select project_idea.*,realname
		from project_idea
			left join user using (user_id)
		where $sql_where
		limit 1");
		$ADODB_FETCH_MODE = $prev_fetch_mode;

		if (count($page)) {
			$smarty->assign('idea', $page);
			$smarty->assign('id', $page['project_idea_id']);
		} else {
			header("HTTP/1.0 404 Not Found");
			header("Status: 404 Not Found");
			$template = 'static_404.tpl';
		}
	}


if ($template != 'static_404.tpl' && isset($_POST) && isset($_POST['submit'])) {
	$updates = array();

	foreach (array('pledge','reason') as $key) {
		if (!empty($_POST[$key])) {
			$updates = array();
			$updates[] = 'content = '.$db->Quote($_POST[$key]);
			$updates[] = 'anon = '.(empty($_POST[$key.'_anon'])?0:1);
			if (!empty($_POST[$key.'_delete']))
				$updates[] = 'approved = -1';

		        if ($_POST[$key.'_id'] == 'new') {
				$updates[] = "`item_type` = '$key'";
				$updates[] = "`project_idea_id` = ".intval($_REQUEST['id']);
		                $updates[] = "`user_id` = {$USER->user_id}";
		                $updates[] = "`created` = NOW()";
		                $sql = "INSERT INTO project_idea_item SET ".implode(',',$updates);
		        } else {
		                $sql = "UPDATE project_idea_item SET ".implode(',',$updates)." WHERE project_idea_item_id = ".$db->Quote($_POST[$key.'_id'])." AND user_id = {$USER->user_id}";
		        }
		        $db->Execute($sql);
		}
	}

        $smarty->clear_cache('project_ideas.tpl');
        $smarty->clear_cache('project_ideas.tpl',$USER->user_id);
        $smarty->clear_cache('project_idea.tpl',$_REQUEST['id']);
        $smarty->clear_cache('project_idea.tpl',$_REQUEST['id']."|".$USER->user_id);

        header("Location: /project/idea.php?id=".intval($_REQUEST['id']));
        exit;
}

if (!empty($_GET['id2'])) {
	$id = intval($_GET['id2']);
	$data = $db->getAll("SELECT * FROM project_idea_item WHERE project_idea_id = ".$db->Quote($_GET['id'])." AND user_id = {$USER->user_id} AND approved = 1 ORDER BY (project_idea_item_id = $id) DESC");
	$done = array();
	foreach ($data as $row) {
		if (empty($done[$row['item_type']])) {
			$smarty->assign($row['item_type'],$row['content']);
			$smarty->assign($row['item_type']."_id",$row['project_idea_item_id']);
			$done[$row['item_type']] = 1;
		}
	}
}


$smarty->display($template, $cacheid);


