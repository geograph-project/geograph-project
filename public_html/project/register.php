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

if (empty($_REQUEST['id'])) {
	$smarty->display('static_404.tpl');
	exit;
}


$template = 'project_register.tpl';
$cacheid = '';

	$db=GeographDatabaseConnection(false);
	
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
		
		if (count($page) && !empty($page['approved']) && $page['approved'] > 0) {
			$smarty->assign($page);
			$smarty->assign('id', $page['project_id']);
			
			$register = $db->getRow("SELECT * FROM project_register WHERE project_id = {$page['project_id']} AND user_id = {$USER->user_id}");
			$smarty->assign_by_ref('register', $register);
			
		} else {
			header("HTTP/1.0 404 Not Found");
			header("Status: 404 Not Found");
			$template = 'static_404.tpl';
		}
	


if ($template != 'static_404.tpl' && isset($_POST) && isset($_POST['submit'])) {

	$sqls = array();
	
	
	$updates= array();
					
	$updates[] = "`project_id` = {$page['project_id']}";
	$updates[] = "`user_id` = {$USER->user_id}";
	
	foreach (array('supporter','helper','subscriber') as $checkbox) 
		$updates[] = "`$checkbox` = ".(isset($_POST[$checkbox])+0);
	
	$updates[] = "`role` = ".$db->Quote(trim(strip_tags($_POST['role']))); 
	
	if ($db->Execute('INSERT INTO project_register SET '.implode(',',$updates).',created=NOW() ON DUPLICATE KEY UPDATE '.implode(',',$updates))) {
	
		$smarty->clear_cache('projects.tpl');
		$smarty->clear_cache('projects.tpl',$USER->user_id);
		$smarty->clear_cache('project_entry.tpl',$_REQUEST['id']);
		$smarty->clear_cache('project_entry.tpl',$_REQUEST['id']."|".$USER->user_id);
	}

	header("Location: /project/entry.php?id=".intval($_REQUEST['id']));
	exit;
} 

$smarty->display($template, $cacheid);


