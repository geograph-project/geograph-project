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

$smarty = new GeographPage;

$template = 'team.tpl';
$cacheid = '';

if (!$smarty->is_cached($template, $cacheid))
{
	$db = GeographDatabaseConnection(true);

	$team = $db->GetAssoc("
	select 
		user.user_id,user.realname,user.nickname,user.rights,role
	from user
		left join user_moderation using (user_id)
	where length(rights) > 0 AND (rights LIKE '%admin%' OR rights LIKE '%moderator%' OR role != '')
	group by user.user_id
	order by user_moderation.user_id IS NOT NULL DESC,user_moderation.first,user_id");

	$smarty->assign_by_ref('team', $team);
}

$smarty->display($template, $cacheid);
	
?>
