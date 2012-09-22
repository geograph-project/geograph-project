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

if (empty($_GET['page']) || preg_match('/[^\w-\.]/',$_GET['page'])) {
	$smarty->display('static_404.tpl');
	exit;
}

$isadmin=$USER->hasPerm('moderator')?1:0;

$template = 'article_history.tpl';
$cacheid = 'articles|'.$_GET['page'];
$cacheid .= '|'.$isadmin;
$cacheid .= '-'.(isset($_SESSION['article_urls']) && in_array($_GET['page'],$_SESSION['article_urls'])?1:0);
$smarty->assign_by_ref('isadmin', $isadmin);


$db=NewADOConnection($GLOBALS['DSN']);

$page = $db->getRow("
select article.article_id,title,url,article.user_id,extract,licence,approved,realname,update_time
from article 
	left join user using (user_id)
where ( (licence != 'none' and approved > 0) 
	or user.user_id = {$USER->user_id}
	or $isadmin )
	and url = ".$db->Quote($_GET['page']).'
limit 1');

if (count($page)) {
	$cacheid .= '|'.$page['update_time'];
	
	if ($page['user_id'] == $USER->user_id) {
		$cacheid .= '|'.$USER->user_id;
	}
	//when this page was modified
	$mtime = strtotime($page['update_time']);
	
	//can't use IF_MODIFIED_SINCE for logged in users as has no concept as uniqueness
	customCacheControl($mtime,$cacheid,($USER->user_id == 0));

}

if (!$smarty->is_cached($template, $cacheid))
{
	if (count($page)) {
		$smarty->assign($page);
		if (!empty($page['extract'])) {
			$smarty->assign('meta_description', $page['extract']);
		}
		
		$prev_fetch_mode = $ADODB_FETCH_MODE;
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
		$list = $db->getAll("
		select article_revisions.article_revision_id,article_revisions.article_cat_id,category_name,article_revisions.user_id,url,title,extract,licence,publish_date,approved,update_time,create_time,modifier,user.realname as modifier_realname,length(content) as content_length
		from article_revisions 
			inner join user on (modifier = user.user_id)
			left join article_cat on (article_revisions.article_cat_id = article_cat.article_cat_id)
		where article_id = {$page['article_id']}
		order by article_revision_id desc");


		$ADODB_FETCH_MODE = $prev_fetch_mode;
		
		$smarty->assign_by_ref('list', $list);
	
	} else {
		$template = 'static_404.tpl';
	}
} else {
	$smarty->assign('user_id', $page['user_id']);
	$smarty->assign('url', $page['url']);
}




$smarty->display($template, $cacheid);

	
?>
