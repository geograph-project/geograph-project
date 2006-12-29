<?php
/**
 * $Project: GeoGraph $
 * $Id: apikeys.php 939 2005-06-29 22:22:57Z barryhunter $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Barry Hunter (geo@barryhunter.co.uk)
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

$USER->mustHavePerm("admin");

$smarty = new GeographPage;

$db = NewADOConnection($GLOBALS['DSN']);
if (!$db) die('Database connection failed');  

if (isset($_GET['revoke'])) {
	$u = new GeographUser(intval($_GET['revoke']));
	if ($u->registered) {
		$right = !empty($_GET['right'])?$_GET['right']:'moderator';
		if ($db->Execute("UPDATE user SET rights = REPLACE(rights,'$right','') WHERE user_id = {$u->user_id}")) {
			$smarty->assign('message', "$right rights removed from ".$u->realname);
		}
	}
} elseif (isset($_GET['grant'])) {
	$u = new GeographUser(intval($_GET['grant']));
	if ($u->registered) {
		$right = !empty($_GET['right'])?$_GET['right']:'moderator';
		if ($db->Execute("UPDATE user SET rights = CONCAT(rights,',$right') WHERE user_id = {$u->user_id}")) {
			$smarty->assign('message', "$right rights added for ".$u->realname);
		}
	}
}

if (!empty($_GET['q']) && trim($_GET['q'])) {
	$q=$db->Quote('%'.$_GET['q'].'%');
	$sql_where = " or (user_id LIKE $q) or (realname LIKE $q) or (nickname LIKE $q)";
	$smarty->assign('q', $_GET['q']);
} else {
	$sql_where = '';
}

$moderators = $db->GetAssoc("
select user.user_id,user.realname,user.nickname,user.rights
from user 
where (rights LIKE '%moderator%' OR rights LIKE '%ticketmod%' OR rights LIKE '%admin%') $sql_where
group by user.user_id");


if (isset($_GET['stats'])) { 
	$user = $db->Quote($_GET['stats']);
	$moderatorstats = $db->GetRow("
	select user.user_id,user.realname,user.nickname,user.rights,
	(select count(*) from gridimage gi2 where gi2.user_id = user.user_id) as photo_count,
	(select count(*) from geobb_posts p where p.poster_id = user.user_id) as post_count,
	(select count(*) from gridimage gi where gi.moderator_id = user.user_id) as count,
	(select count(*) from moderation_log ml where ml.user_id = user.user_id) as log_count,
	(select count(*) from gridimage_ticket t where t.moderator_id = user.user_id) as ticket_count
	from user 
	where user.user_id = $user");
	$found = 0;
	foreach ($moderators as $i => $row) {
		if ($row['user_id'] == $moderatorstats['user_id']) {
			$moderators[$i] = array_merge($row,$moderatorstats);
			$found = 1;
			break;
		}
	}
	if (!$found)
		$moderators[] = $moderatorstats;
	$smarty->assign('stats', $_GET['stats']);
} 

$smarty->assign_by_ref('moderators', $moderators);


$smarty->display('admin_moderator_admin.tpl');


	
?>
