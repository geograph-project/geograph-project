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

if (empty($_REQUEST['article_id']) && (empty($_REQUEST['page']) || preg_match('/[^\w-\.]/',$_REQUEST['page']))) {
	$smarty->display('static_404.tpl');
	exit;
}


$template = 'article_edit.tpl';




	$db=NewADOConnection($GLOBALS['DSN']);
	if ($_REQUEST['page'] == 'new' || $_REQUEST['article_id'] == 'new') {
		$smarty->assign('article_id', "new");
		$smarty->assign('title', "New Article");
		$smarty->assign('realname', $USER->realname);
		$smarty->assign('user_id', $USER->user_id);
	} else {
		if (!empty($_REQUEST['article_id'])) {
			$sql_where = " article_id = ".$db->Quote($_REQUEST['article_id']);
		} else {
			$sql_where = " url = ".$db->Quote($_REQUEST['page']);
		}
		$prev_fetch_mode = $ADODB_FETCH_MODE;
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;	
		$page = $db->getRow("
		select article.*,realname,gs.grid_reference
		from article 
			left join user using (user_id)
			left join gridsquare gs on (article.gridsquare_id = gs.gridsquare_id)
		where $sql_where
		limit 1");
		$ADODB_FETCH_MODE = $prev_fetch_mode;
		
		if (count($page) && ($page['user_id'] == $USER->user_id || $USER->hasPerm('moderator'))) {
			foreach ($page as $key => $value) {
				$smarty->assign($key, $value);
			}
		} else {
			$template = 'static_404.tpl';
		}
	}


if ($template != 'static_404.tpl' && isset($_POST) && isset($_POST['submit'])) {
	$errors = array();
	

	$_POST['publish_date']=sprintf("%04d-%02d-%02d",$_POST['publish_dateYear'],$_POST['publish_dateMonth'],$_POST['publish_dateDay']);
	$_POST['title'] = preg_replace('/[^\w-\., ]+/','',trim($_POST['title']));
	if (empty($_POST['url']) && !empty($_POST['title'])) {
		$_POST['url'] = $_POST['title'];
	}
	$_POST['url'] = preg_replace('/ /','-',trim($_POST['url']));
	$_POST['url'] = preg_replace('/[^\w-\.,]+/','',$_POST['url']);
	
	if ($_POST['title'] == "New Article")
		$errors['title'] = "Please give a meaningful title";
	
	$gs=new GridSquare();
	if (!empty($_POST['grid_reference'])) {
		if ($gs->setByFullGridRef($_POST['grid_reference'])) {
			$_POST['gridsquare_id'] = $gs->gridsquare_id;
		} else 
			$errors['grid_reference'] = $gs->errormsg;
	}
	
	//the most basic protection
	$_POST['content'] = strip_tags($_POST['content']);
	$_POST['content'] = preg_replace('/[“”]/','',$_POST['content']);

	$_POST['extract'] = strip_tags($_POST['extract']);
	$_POST['extract'] = preg_replace('/[“”]/','',$_POST['extract']);

	
	$updates = array();
	foreach (array('url','title','licence','content','publish_date','gridsquare_id','extract') as $key) {
		if ($page[$key] != $_POST[$key]) {
			$updates[] = "`$key` = ".$db->Quote($_POST[$key]); 
			$smarty->assign($key, $_POST[$key]);
			if ($key == 'url' || $key = 'title') {
				$sql = "select count(*) from article where `$key` = ".$db->Quote($_POST[$key]);
				if (!empty($_REQUEST['article_id'])) {
					$sql .=  " and article_id != ".$db->Quote($_REQUEST['article_id']);
				}
				if ($db->getOne($sql)) 
					$errors[$key] = "(".$db->Quote($_POST[$key]).') is already in use';				
			}
		} elseif (empty($_POST[$key]) && $key != 'gridsquare_id') 
			$errors[$key] = "missing required info";		
	}
	if (!count($updates)) {
		$smarty->assign('error', "No Changes to Save");
		$errors[1] =1;
	}
	if ($_REQUEST['page'] == 'new' || $_REQUEST['article_id'] == 'new') {
	
		//todo check has title/url and that its unique!
		
		$updates[] = "`user_id` = {$USER->user_id}";
		$updates[] = "`create_time` = NOW()";
		$sql = "INSERT INTO article SET ".implode(',',$updates);
	} else {
		
		$sql = "UPDATE article SET ".implode(',',$updates)." WHERE article_id = ".$db->Quote($_REQUEST['article_id']);
	}
	if (!count($errors) && count($updates)) {
		
		$db->Execute($sql);
		if ($_REQUEST['page'] == 'new' || $_REQUEST['article_id'] == 'new') {
			$_REQUEST['article_id'] = $db->Insert_ID();
		}
		//and back it up
		$sql = "INSERT INTO article_revisions SELECT *,NULL,{$USER->user_id} FROM article WHERE article_id = ".$db->Quote($_REQUEST['article_id']);
		$db->Execute($sql);

		$smarty->clear_cache('article_article.tpl', $_POST['url']);
		$smarty->clear_cache('article.tpl');
		
		$_SESSION[$_POST['url']] = $db->Insert_ID();

		header("Location: /article/");
		exit;
	} else {
		if ($errors[1] != 1)
			$smarty->assign('error', "Please see messages below...");
		$smarty->assign_by_ref('errors',$errors);
	}
} 

	$smarty->assign('licences', array('none' => '(Temporarily) Not Published','pd' => 'Public Domain','cc-by-sa/2.0' => 'Creative Commons BY-SA/2.0' ,'copyright' => 'Copyright'));



$smarty->display($template, $cacheid);

	
?>
