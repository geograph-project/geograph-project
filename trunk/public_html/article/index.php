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

$template = 'article.tpl';
$smarty->caching = 0; //dont cache!

$cacheid = $USER->hasPerm('basic')?$USER->user_id:0;

$isadmin=$USER->hasPerm('moderator')?1:0;
$smarty->assign_by_ref('isadmin', $isadmin);

if ($isadmin) {
	if (!empty($_GET['page']) && !preg_match('/[^\w-\.]/',$_GET['page'])) {
		$db=NewADOConnection($GLOBALS['DSN']);
		
		$a = intval($_GET['approve']);	
		
		$sql = "UPDATE article SET approved = $a WHERE url = ".$db->Quote($_GET['page']);
		$db->Execute($sql);

		//and back it up
		$sql = "INSERT INTO article_revisions SELECT *,NULL,{$USER->user_id} FROM article WHERE article_id = ".$db->Quote($_REQUEST['article_id']);
		$db->Execute($sql);
		
		$smarty->clear_cache($template, $cacheid);
	}
}
if (!$smarty->is_cached($template, $cacheid))
{
	
	$db=NewADOConnection($GLOBALS['DSN']);
	
	$list = $db->getAll("
	select article.*,realname
	from article 
		inner join user using (user_id)
	where (licence != 'none' and approved = 1) 
		or user.user_id = {$USER->user_id}
		or $isadmin
	order by create_time desc");
	
	$urls = array();
	foreach ($list as $i => $row) {
		$urls[] = $row['url'];
	}
	$_SESSION['article_urls'] = $urls;
	
	$smarty->assign_by_ref('list', $list);

}

$smarty->display($template, $cacheid);

	
?>
