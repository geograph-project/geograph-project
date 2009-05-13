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

if (empty($_REQUEST['article_id']) && (empty($_REQUEST['page']) || preg_match('/[^\w\.\,-]/',$_REQUEST['page']))) {
	header("HTTP/1.0 404 Not Found");
	header("Status: 404 Not Found");
	$smarty->display('static_404.tpl');
	exit;
}


$template = 'article_edit.tpl';
$cacheid = '';



	$db=NewADOConnection($GLOBALS['DSN']);
	if ($_REQUEST['page'] == 'new' || $_REQUEST['article_id'] == 'new') {
		$smarty->assign('article_id', "new");
		$smarty->assign('title', "New Article");
		$smarty->assign('realname', $USER->realname);
		$smarty->assign('user_id', $USER->user_id);
		$page = array();
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
		
		if (count($page) && (
				$page['user_id'] == $USER->user_id || 
				$USER->hasPerm('moderator') ||
				$page['approved'] == 2
			) ) {
			
			if (isset($_GET['release'])) {
				$db->Execute("DELETE FROM article_lock WHERE user_id = {$USER->user_id} AND article_id = {$page['article_id']}");
				
				if (empty($_GET['release'])) {
					header("HTTP/1.0 204 No Content");
					header("Status: 204 No Content");
					header("Content-Length: 0");
					flush();
				} else {
					header("Location: /article/");
				}
				exit;
				
			}
			$lockedby = $db->getOne("
				select 
					m.realname
				from
					article_lock as l
					inner join user as m using (user_id)
				where
					article_id = {$page['article_id']}
					and m.user_id != {$USER->user_id}
				and lock_obtained > date_sub(NOW(),INTERVAL 1 HOUR)");
					
			if ($lockedby) {
				$smarty->assign('lockedby', $lockedby);
				$template = 'article_locked.tpl';
				$smarty->display($template, $cacheid);
				exit;
			}

			$smarty->assign($page);
			$db->Execute("REPLACE INTO article_lock SET user_id = {$USER->user_id}, article_id = {$page['article_id']}");
		} else {
			header("HTTP/1.0 404 Not Found");
			header("Status: 404 Not Found");
			$template = 'static_404.tpl';
		}
	}


if ($template != 'static_404.tpl' && isset($_POST) && isset($_POST['submit'])) {
	$errors = array();
	
	$smarty->reassignPostedDate('publish_date');
	$_POST['title'] = preg_replace('/[^\w-\., ]+/','',trim($_POST['title']));
	if (empty($_POST['url']) && !empty($_POST['title'])) {
		$_POST['url'] = $_POST['title'];
	}
	$_POST['url'] = preg_replace('/ /','-',trim($_POST['url']));
	$_POST['url'] = preg_replace('/[^\w-]+/','',$_POST['url']);
	
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
	
	if ($page['approved'] == 2) {
		$keys = array('content');
	} else {
		$keys = array('url','title','licence','content','publish_date','article_cat_id','gridsquare_id','extract');
	}
	
	foreach ($keys as $key) {
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

		require_once('geograph/event.class.php');
		new Event("article_updated", $_REQUEST['article_id']);

		//and back it up
		$sql = "INSERT INTO article_revisions SELECT *,NULL,{$USER->user_id} FROM article WHERE article_id = ".$db->Quote($_REQUEST['article_id']);
		$db->Execute($sql);

		$_SESSION[$_POST['url']] = $db->Insert_ID();

		$smarty->clear_cache('article_article.tpl', $_POST['url']);
		$smarty->clear_cache('article.tpl');

		$db->Execute("DELETE FROM article_lock WHERE user_id = {$USER->user_id} AND article_id = {$_REQUEST['article_id']}");

		header("Location: /article/".(empty($_POST['url'])?$page['url']:$_POST['url']));
		exit;
	} else {
		if ($errors[1] != 1)
			$smarty->assign('error', "Please see messages below...");
		$smarty->assign_by_ref('errors',$errors);
	}
} 

	$smarty->assign('licences', array('none' => '(Temporarily) Not Published','pd' => 'Public Domain','cc-by-sa/2.0' => 'Creative Commons BY-SA/2.0' ,'copyright' => 'Copyright'));
	$smarty->assign('article_cat', array(0=>'')+$db->getAssoc("select article_cat_id,category_name from article_cat order by sort_order"));



$smarty->display($template, $cacheid);

	
?>
