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

$cacheid = $user->registered.'.'.$CONF['forums'];

if (!empty($_GET)) {
	ksort($_GET);
	$cacheid .= ".".md5(serialize($_GET));
}

if (isset($_REQUEST['inner'])) {
	$template = 'content_iframe.tpl';
} else {
	$template = 'content.tpl';
}


if ($template == 'content_iframe.tpl' && !$smarty->is_cached($template, $cacheid))
{

	$db=NewADOConnection($GLOBALS['DSN']);
	
	$limit = 25;
	
	if (!empty($_GET['user_id']) && preg_match('/^\d+$/',$_GET['user_id'])) {
		$where = "AND content.user_id = {$_GET['user_id']}";
		$smarty->assign('extra', "&amp;user_id={$_GET['user_id']}");
	
	} elseif (!empty($_GET['q']) && preg_match('/^[\w ]+$/',$_GET['q'])) {
		$where = "AND title LIKE '%{$_GET['q']}%'";
		$smarty->assign('extra', "&amp;q={$_GET['q']}");
	} elseif (isset($_GET['docs'])) {
		$where = "`use` = 'document'";
		$limit = 1000;
	} else {
		$where = "`use` = 'info'";
	}
	
	#$pg = empty($_GET['page'])?1:intval($_GET['page']);
	
	$order = (isset($_GET['order']) && ctype_lower($_GET['order']))?$_GET['order']:'updated';

	
	switch ($order) {
		case 'created': $sql_order = "created desc";
			$title = "Recently Created"; break;
		case 'title': $sql_order = "title";
			$title = "By Content Title";break;
		case 'updated':
		default: $sql_order = "updated desc";
			$title = "Recently Updated";
	}
	
	
	
	$prev_fetch_mode = $ADODB_FETCH_MODE;
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$list = $db->getAll("
	select content.content_id,content.user_id,url,title,extract,updated,created,realname,type,gridimage_id
	from content 
		left join user using (user_id)
	where $where
	order by  $sql_order limit $limit");
	
	foreach ($list as $i => $row) {
		if ($row['gridimage_id']) {
			$list[$i]['image'] = new GridImage;
			$g_ok = $list[$i]['image']->loadFromId($row['gridimage_id'],true);
			if ($g_ok && $list[$i]['image']->moderation_status == 'rejected')
				$g_ok = false;
			if (!$g_ok) {
				unset($list[$i]['image']);
			}
			print "$i::$g_ok :: {$row['gridimage_id']}<BR>";
		}
	}
	
	$ADODB_FETCH_MODE = $prev_fetch_mode;
	
	$smarty->assign_by_ref('list', $list);
	$smarty->assign_by_ref('title', $title);
}

$smarty->display($template, $cacheid);

	
?>
