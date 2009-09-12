<?php
/**
 * $Project: GeoGraph $
 * $Id: index.php 4866 2008-10-19 21:06:25Z barry $
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


$db = GeographDatabaseConnection(true);

$data = $db->getRow("show table status like 'content_group'");

//when this table was modified
$mtime = strtotime($data['Update_time']);
	
//can't use IF_MODIFIED_SINCE for logged in users as has no concept as uniqueness
customCacheControl($mtime,$cacheid,($USER->user_id == 0));

$template = 'content_themes.tpl';

if (!empty($_GET['v'])) {
	switch ($_GET['v']) {
		case '2': $source = 'sphinx'; break;
		case '3': $source = 'user%'; break;
		default:  $source = 'carrot2'; break;
	}
} else {
	$source = 'carrot2';
}
$cacheid = $source.'.'.$USER->registered.'.'.$CONF['forums'];


if (!$smarty->is_cached($template, $cacheid))
{
	$where  = '';
	if ((isset($CONF['forums']) && empty($CONF['forums'])) || $USER->user_id == 0 ) {
		$where .= " AND content.`source` != 'themed'";
	}
	
	$prev_fetch_mode = $ADODB_FETCH_MODE;
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$list = $db->getAll($sql = "
	select content.content_id,content.user_id,url,title,extract,content.updated,content.created,realname,label,score
	from content_group
		inner join content using (content_id)
		left join user using (user_id)
	where content_group.`source` like '$source' and `type` = 'info' $where
	group by content_id,label
	order by label = '(Other)',content_group.label,content_group.score desc,content_group.sort_order
	");
	
	#print "<pre>";
	#print_r($sql);
	#exit;
	
	$smarty->assign_by_ref('list', $list);
	if (!empty($_GET['v'])) {
		$smarty->assign('v', intval($_GET['v']));
	}
}

$smarty->display($template, $cacheid);

?>
