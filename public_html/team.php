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

$where = '';
if (!empty($_GET['role']) && preg_match('/^\w+$/',$_GET['role'])) {
	$where = " and rights like '%{$_GET['role']}%'";
	$cacheid = $_GET['role'];
}

if (!$smarty->is_cached($template, $cacheid))
{
	$db = GeographDatabaseConnection(true);

	$team = $db->GetAssoc("
	select 
		user.user_id,user.realname,user.nickname,user.rights,role,email
	from user
		left join user_moderation using (user_id)
	where length(rights) > 0 AND (rights != 'basic' OR role != '') AND rights NOT LIKE '%dormant%' AND rights NOT LIKE '%suspicious%' AND rights != 'basic,traineemod'
		and user.user_id != 23277 $where
	group by user.user_id
	order by rand()");

	foreach ($team as $key => $row) {
		$rights = array();
		foreach (explode(',',$row['rights']) as $right) {
			if (isset($positions[$right])) {
				$rights[$right] = $positions[$right];
			}
		}
		if (isset($rights['moderator']) && isset($rights['ticketmod'])) {
			unset($rights['ticketmod']);
		}
		if (empty($rights) && empty($row['role'])) {
			unset($team[$key]);
		} else {
			$row['role'] = str_replace('Ticket ','',$row['role']);
			if (!empty($row['role']) && !in_array($row['role'],$rights) && $row['role'] != 'Member')
				array_unshift($rights,$row['role']);

			$team[$key]['md5_email'] = md5(strtolower($row['email']));
			if ($_GET['preview'] == 2) {
				$team[$key]['rights'] = implode(', ',$rights);
			} else {
				$team[$key]['rights'] = implode(', ',array_keys($rights));
			}
		}
	}

	$positions = array(
	'founder' => 'Founder',
	'developer' => 'Developer',
	'director' => 'Company Director',
	'moderator' => 'Moderator',
	'ticketmod' => 'Moderator',
	'poty' => 'PoTY Coordinator',
	'forum' => 'Forum Moderator',
	'complaints' => 'Complaints Resolution',
	'docs' => 'Documentation Writer',
	'coordinator' => 'Moderator Coordinator',
	'support' => 'Support Representative');

	##'member' => 'Company Member',
	##'basic' => 'Member',
	##'traineemod' => 'Trainee Moderator',
	##'suspicious' => '',
	##'dormant' => '',

        $smarty->assign_by_ref('positions', $positions);

	$smarty->assign_by_ref('team', $team);
}

$smarty->display($template, $cacheid);


