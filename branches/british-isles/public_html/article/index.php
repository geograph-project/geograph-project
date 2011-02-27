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

$smarty->caching = 0; //dont cache!

$cacheid = $USER->hasPerm('basic')?$USER->user_id:0;

if (!empty($_GET)) {
	ksort($_GET);
	$cacheid .= ".".md5(serialize($_GET));
}

$isadmin=$USER->hasPerm('moderator')?1:0;
$smarty->assign_by_ref('isadmin', $isadmin);

$template = ($isadmin)?'article_admin.tpl':'article.tpl';
if (isset($_GET['table'])) {
	$template = 'article_table.tpl';
}

if ($isadmin) {
	if (!empty($_GET['page']) && preg_match('/^[\w-]+$/',$_GET['page'])) {
		$db = GeographDatabaseConnection(false);
		
		$a = intval($_GET['approve']);	
		
		$sql = "UPDATE article SET approved = $a WHERE url = ".$db->Quote($_GET['page']);
		$db->Execute($sql);

		//and back it up
		$sql = "INSERT INTO article_revisions SELECT *,NULL,{$USER->user_id} FROM article WHERE url = ".$db->Quote($_GET['page']);
		$db->Execute($sql);
		
		$article_id = $db->getOne("SELECT article_id FROM article WHERE url = ".$db->Quote($_GET['page']));
		if ($a > 0) {
			require_once('geograph/event.class.php');
			new Event("article_updated", $article_id);
		
		} else {
			$db->Execute("delete from content where foreign_id = $article_id and type = 'article'");
			//todo maybe make it an event?
		}
		
		$smarty->clear_cache($template, $cacheid);
	}
}

$db = GeographDatabaseConnection(true);

$data = $db->getRow("show table status like 'article'");

//when this table was modified
$mtime = strtotime($data['Update_time']);
	
//can't use IF_MODIFIED_SINCE for logged in users as has no concept as uniqueness
customCacheControl($mtime,$cacheid,($USER->user_id == 0));

if (!$smarty->is_cached($template, $cacheid))
{
	
	if (!empty($_GET['user_id']) && preg_match('/^\d+$/',$_GET['user_id'])) {
		$where = "AND article.user_id = {$_GET['user_id']}";
		$smarty->assign('extra', "&amp;user_id={$_GET['user_id']}");
		$smarty->assign('desc', ", by specific user");
	
	} elseif (!empty($_GET['q']) && preg_match('/^[\w ]+$/',$_GET['q'])) {
		$where = "AND title LIKE '%{$_GET['q']}%'";
		$smarty->assign('extra', "&amp;q={$_GET['q']}");
		$smarty->assign('desc', ", matching [ {$_GET['q']} ]");
	
	} elseif (!empty($_GET['cat_q']) && preg_match('/^\![\w ]+$/',$_GET['cat_q'])) {
		$where = "AND category_name NOT LIKE '%".str_replace('!','',$_GET['cat_q'])."%'";
		$smarty->assign('extra', "&amp;cat_q={$_GET['cat_q']}");
		$smarty->assign('desc', ", not matching [ {$_GET['cat_q']} ]");
	
	} elseif (!empty($_GET['cat_word']) && preg_match('/^\![\w ]+$/',$_GET['cat_word'])) {
		$where = 'AND category_name NOT REGEXP '.$db->Quote('[[:<:]]'.str_replace('!','',$_GET['cat_word']).'[[:>:]]');
		$smarty->assign('extra', "&amp;cat_word={$_GET['cat_word']}");
		$smarty->assign('desc', ", category not matching word [ {$_GET['cat_word']} ]");
	
	} elseif (!empty($_GET['cat_q']) && preg_match('/^[\w ]+$/',$_GET['cat_q'])) {
		$where = "AND category_name LIKE '%{$_GET['cat_q']}%'";
		$smarty->assign('extra', "&amp;cat_q={$_GET['cat_q']}");
		$smarty->assign('desc', ", category matching [ {$_GET['cat_q']} ]");
	
	} elseif (!empty($_GET['cat_word']) && preg_match('/^[\w ]+$/',$_GET['cat_word'])) {
		$where = 'AND category_name REGEXP '.$db->Quote('[[:<:]]'.$_GET['cat_word'].'[[:>:]]');
		$smarty->assign('extra', "&amp;cat_word={$_GET['cat_word']}");
		$smarty->assign('desc', ", matching word [ {$_GET['cat_word']} ]");
	
	} else {
		$where = '';
	}
	
	$prev_fetch_mode = $ADODB_FETCH_MODE;
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	if (empty($where) && !isset($_GET['full'])) {
		$bit = "select @counter:=@counter+1 as counter,article.article_id,'STRING' AS category_name,article.user_id,url,title,extract,licence,publish_date,approved,update_time,create_time,realname,l.user_id as locked_user
		from article 
			inner join user using (user_id)
			left join article_lock as l
				on(article.article_id=l.article_id and lock_obtained > date_sub(NOW(),INTERVAL 1 HOUR) and l.user_id != {$USER->user_id})
		where ((licence != 'none' and approved > 0) 
			or user.user_id = {$USER->user_id}
			or ($isadmin and approved != -1))
			$where";
			
		$sql = array();	
		$sql[] = str_replace('STRING','Recently Created',$bit)." ORDER BY article_id DESC LIMIT 5";
		$sql[] = str_replace('STRING','Recently Updated',$bit)." ORDER BY update_time DESC LIMIT 7";
		$sql[] = str_replace('STRING','Your other Articles',$bit)." AND article.user_id = {$USER->user_id} ORDER BY article_id DESC LIMIT 50"; //group by to remove duplicates
		$db->query("SET @counter:=0");
		$list = $db->getAll("SELECT * FROM ((".implode(') UNION (',$sql).")) t2 GROUP BY article_id ORDER BY counter"); 
		$smarty->assign("linktofull",1);
	} else {
	
		$list = $db->getAll("
		select article.article_id,article.article_cat_id,category_name,article.user_id,url,title,extract,licence,publish_date,approved,update_time,create_time,realname,l.user_id as locked_user
		from article 
			inner join user using (user_id)
			left join article_cat on (article.article_cat_id = article_cat.article_cat_id)
			left join article_lock as l
				on(article.article_id=l.article_id and lock_obtained > date_sub(NOW(),INTERVAL 1 HOUR) and l.user_id != {$USER->user_id})
		where ((licence != 'none' and approved > 0) 
			or user.user_id = {$USER->user_id}
			or ($isadmin and approved != -1))
			$where
		order by lft,sort_order,article.article_cat_id,article_sort_order desc,create_time desc");
	}
	
	$urls = array();
	foreach ($list as $i => $row) {
		$urls[] = $row['url'];
		if ($isadmin) {
			$list[$i]['version'] = $db->getOne("
				select count(*)
				from article_revisions
				where article_id = {$row['article_id']}
				group by article_id");
			$list[$i] += $db->getRow("
				select modifier as modifier_id,realname as modifier_realname
				from article_revisions
					left join user on (article_revisions.modifier = user.user_id)
				where article_id = {$row['article_id']} and update_time = '{$row['update_time']}'");
		}
	}
	$ADODB_FETCH_MODE = $prev_fetch_mode;
	$_SESSION['article_urls'] = $urls;
	
	$smarty->assign_by_ref('list', $list);

}

if ($USER->registered && isset($_GET['full']) && (empty($_GET['user_id']) || $_GET['user_id'] != $USER->user_id)) {
	$smarty->assign('article_count', $db->CacheGetOne(3600,"SELECT count(*) FROM article WHERE user_id = ".$USER->user_id));
}

$smarty->display($template, $cacheid);


