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

if (isset($_POST) && isset($_POST['submit'])) {
	$publish_date=sprintf("%04d-%02d-%02d",$_POST['publish_dateYear'],$_POST['publish_dateMonth'],$_POST['publish_dateDay']);
	
			

} elseif (empty($_GET['page']) || preg_match('/[^\w-\.]/',$_GET['page'])) {
	$smarty->display('static_404.tpl');
	exit;
}


$template = 'article_edit.tpl';


$isadmin=$USER->hasPerm('moderator')?1:0;


	$db=NewADOConnection($GLOBALS['DSN']);
	if ($_GET['page'] == 'new') {
		$smarty->assign('title', "New Article");
		$smarty->assign('realname', $USER->realname);
		$smarty->assign('user_id', $USER->user_id);
	} else {
		$page = $db->getRow("
		select article.*,realname
		from article 
			left join user using (user_id)
		where licence != 'none' 
			and url = ".$db->Quote($_GET['page']).'
		limit 1');
		if (count($page) && ($page['user_id'] == $USER->user_id || $USER->hasPerm('moderator'))) {
			foreach ($page as $key => $value) {
				$smarty->assign($key, $value);
			}
		} else {
			$template = 'static_404.tpl';
		}
	}
	
	$smarty->assign('licences', array('none' => 'Not Published','pd' => 'Public Domain','cc-by-sa/2.0' => 'Creative Commons BY-SA/2.0' ,'copyright' => 'Full Copyright'));



$smarty->display($template, $cacheid);

	
?>
