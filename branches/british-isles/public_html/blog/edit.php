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


$template = 'blog_edit.tpl';
$cacheid = '';

	$db=NewADOConnection($GLOBALS['DSN']);
	if ($_REQUEST['id'] == 'new') {
		$smarty->assign('id', "new");
		$smarty->assign('title', "New Entry");
		$smarty->assign('realname', $USER->realname);
		$smarty->assign('user_id', $USER->user_id);
		$page = array();
	} else {
		$sql_where = " blog_id = ".$db->Quote($_REQUEST['id']);
		 
		$prev_fetch_mode = $ADODB_FETCH_MODE;
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;	
		$page = $db->getRow($sql = "
		select blog.*,realname,gs.grid_reference
		from blog 
			left join user using (user_id)
			left join gridsquare gs on (blog.gridsquare_id = gs.gridsquare_id)
		where $sql_where
		limit 1");
		$ADODB_FETCH_MODE = $prev_fetch_mode;
		
		if (count($page) && ($page['user_id'] == $USER->user_id || $USER->hasPerm('moderator'))) {
			$smarty->assign($page);
			$smarty->assign('id', $page['blog_id']);
		} else {
			header("HTTP/1.0 404 Not Found");
			header("Status: 404 Not Found");
			$template = 'static_404.tpl';
		}
	}


if ($template != 'static_404.tpl' && isset($_POST) && isset($_POST['submit'])) {
	$errors = array();
	

	$_POST['published']=sprintf("%04d-%02d-%02d %02d:%02d:%02d",$_POST['publishedYear'],$_POST['publishedMonth'],$_POST['publishedDay'],$_POST['publishedHour'],$_POST['publishedMinute'],$_POST['publishedSecond']);
	$_POST['title'] = preg_replace('/[^\w-\., ]+/','',trim($_POST['title']));
	
	if ($_POST['title'] == "New Entry")
		$errors['title'] = "Please give a meaningful title";

	$gs=new GridSquare();
	if (!empty($_POST['grid_reference'])) {
		if ($gs->setByFullGridRef($_POST['grid_reference'])) {
			$_POST['gridsquare_id'] = $gs->gridsquare_id;
		} else 
			$errors['grid_reference'] = $gs->errormsg;
	}
	
	$_POST['content'] = strip_tags($_POST['content']);
	
	$updates = array();
	foreach (array('title','content','tags','published','gridsquare_id','gridimage_id') as $key) {
		if ($page[$key] != $_POST[$key]) {
			$updates[] = "`$key` = ".$db->Quote($_POST[$key]); 
			$smarty->assign($key, $_POST[$key]);
		} elseif (empty($_POST[$key]) && $key != 'tags' && $key != 'gridsquare_id' && $key != 'gridimage_id' ) 
			$errors[$key] = "missing required info";		
	}

	if (!count($updates)) {
		$smarty->assign('error', "No Changes to Save");
		$errors[1] =1;
	}
	if ($_REQUEST['id'] == 'new') {
	
		$updates[] = "`user_id` = {$USER->user_id}";
		$updates[] = "`created` = NOW()";
		$sql = "INSERT INTO blog SET ".implode(',',$updates);
	} else {
		
		$sql = "UPDATE blog SET ".implode(',',$updates)." WHERE blog_id = ".$db->Quote($_REQUEST['id']);
	}
	if (!count($errors) && count($updates)) {
		
		$db->Execute($sql);
		if ($_REQUEST['id'] == 'new') {
			$_REQUEST['id'] = $db->Insert_ID();
		}
	;
		
		$smarty->clear_cache('blogs.tpl');
		$smarty->clear_cache('blogs.tpl',$USER->user_id);
		$smarty->clear_cache('blog_entry.tpl',$_REQUEST['id']);
		$smarty->clear_cache('blog_entry.tpl',$_REQUEST['id']."|".$USER->user_id);
		
		header("Location: /blog/entry.php?id=".intval($_REQUEST['id']));
		exit;
	} else {
		if ($errors[1] != 1)
			$smarty->assign('error', "Please see messages below...");
		$smarty->assign_by_ref('errors',$errors);
	}
} 



$smarty->display($template, $cacheid);


