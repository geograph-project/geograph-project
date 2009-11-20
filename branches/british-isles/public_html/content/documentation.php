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




$template = 'content_docs.tpl';

$db = GeographDatabaseConnection(true);

$data = $db->getRow("show table status like 'article'");

//when this table was modified
$mtime = strtotime($data['Update_time']);
	
//can't use IF_MODIFIED_SINCE for logged in users as has no concept as uniqueness
customCacheControl($mtime,$cacheid,($USER->user_id == 0));

if (!$smarty->is_cached($template, $cacheid))
{
	$prev_fetch_mode = $ADODB_FETCH_MODE;
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$list = $db->getAll("
	select content.url,content.title,content.user_id,coalesce(category_name,'Help Document') as category_name,realname
	from content 
		inner join user using (user_id)
		left join article on (foreign_id = article_id)
		left join article_cat on (article.article_cat_id = article_cat.article_cat_id)

	where category_name like '%Geograph %' or source = 'help'

	order by sort_order,article.article_cat_id,article_sort_order desc,create_time desc");
	
	$ADODB_FETCH_MODE = $prev_fetch_mode;

	
	$smarty->assign_by_ref('list', $list);

}

$smarty->display($template, $cacheid);

	
?>
