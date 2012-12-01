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

error_reporting(E_ALL);
ini_set('display_errors',1);


require_once('geograph/global.inc.php');
init_session();


$smarty = new GeographPage;

$USER->mustHavePerm('basic');
$isadmin=$USER->hasPerm('moderator')?1:0;

if (empty($_REQUEST['id'])) {
	$smarty->display('static_404.tpl');
	exit;
}


$template = 'project_links.tpl';
$cacheid = '';

	$db=NewADOConnection($GLOBALS['DSN']);
	
		$sql_where = " project_id = ".$db->Quote($_REQUEST['id']);
		 
		$prev_fetch_mode = $ADODB_FETCH_MODE;
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;	
		$page = $db->getRow($sql = "
		select project.*,realname
		from project 
			left join user using (user_id)
		where $sql_where
		limit 1");
		$ADODB_FETCH_MODE = $prev_fetch_mode;
		
		if (count($page) && ($page['user_id'] == $USER->user_id || $USER->hasPerm('moderator'))) {
			$smarty->assign($page);
			$smarty->assign('id', $page['project_id']);
			
			$links = $db->getAll("SELECT * FROM project_link WHERE project_id = {$page['project_id']} ORDER BY project_link_id");
			$smarty->assign_by_ref('links', $links);
			
		} else {
			header("HTTP/1.0 404 Not Found");
			header("Status: 404 Not Found");
			$template = 'static_404.tpl';
		}
	


if ($template != 'static_404.tpl' && isset($_POST) && isset($_POST['submit'])) {

	$sqls = array();
	foreach ($_POST['link'] as $key => $link) {
		if (!empty($link)) {
			if ($link == '-deleted-' && $key >= 100) {
				$sqls[] = "DELETE FROM project_link WHERE project_id = {$page['project_id']} AND project_link_id = ".$db->Quote($key);
			} else {
				$updates= array();
				if (empty($_POST['title'][$key]) && preg_match('/\/discuss\/.*topic=(\d+)/',$link,$m)) {
					$updates[] = "`title` = ".$db->Quote("Thread: ".$db->getOne("SELECT topic_title FROM geobb_topics WHERE topic_id = {$m[1]}")); 
				} else {
					$updates[] = "`title` = ".$db->Quote(trim($_POST['title'][$key])); 
				}
				$updates[] = "`link` = ".$db->Quote(trim($link)); 
				if ($key < 100) {
					$updates[] = "`project_id` = {$page['project_id']}";
					$updates[] = "`user_id` = {$USER->user_id}";
					$updates[] = "`created` = NOW()";
					$sqls[] = "INSERT INTO project_link SET ".implode(',',$updates);
				} else {
					$sqls[] = "UPDATE project_link SET ".implode(',',$updates)." WHERE project_id = {$page['project_id']} AND project_link_id = ".$db->Quote($key);
				}
			}
		}
	}

	if (count($sqls)) {
		foreach ($sqls as $sql)
			$db->Execute($sql);
	
		$smarty->clear_cache('projects.tpl');
		$smarty->clear_cache('projects.tpl',$USER->user_id);
		$smarty->clear_cache('project_entry.tpl',$_REQUEST['id']);
		$smarty->clear_cache('project_entry.tpl',$_REQUEST['id']."|".$USER->user_id);
	}

	header("Location: /project/entry.php?id=".intval($_REQUEST['id']));
	exit;
} 

$smarty->display($template, $cacheid);


