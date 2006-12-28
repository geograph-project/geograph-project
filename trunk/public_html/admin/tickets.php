<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Paul Dixon (paul@elphin.com)
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

$USER->mustHavePerm("moderator");

$smarty = new GeographPage;

$db = NewADOConnection($GLOBALS['DSN']);

#############################

$db->Execute("LOCK TABLES 
gridimage_moderation_lock WRITE, 
gridimage_moderation_lock l WRITE, 
gridimage_ticket t READ, 
user suggester READ,
gridimage i  READ,
user as submitter READ");

#############################

$limit = (isset($_GET['limit']) && is_numeric($_GET['limit']))?min(100,intval($_GET['limit'])):50;


$newtickets=$db->GetAll(
	"select t.*,suggester.realname as suggester,submitter.realname as submitter, i.title
	from gridimage_ticket as t
	inner join user as suggester on (suggester.user_id=t.user_id)
	inner join gridimage as i on (t.gridimage_id=i.gridimage_id)
	inner join user as submitter on (submitter.user_id=i.user_id)
	left join gridimage_moderation_lock as l
		on(i.gridimage_id=l.gridimage_id and lock_obtained > date_sub(NOW(),INTERVAL 1 HOUR) )
	where t.moderator_id=0 and t.status<>'closed'
		and (l.gridimage_id is null OR 
				(l.user_id = {$USER->user_id} AND lock_type = 'modding') OR
				(l.user_id != {$USER->user_id} AND lock_type = 'cantmod')
		)
	order by t.suggested
	limit $limit");
$smarty->assign_by_ref('newtickets', $newtickets);

foreach ($newtickets as $i => $row) {
	$db->Execute("REPLACE INTO gridimage_moderation_lock SET user_id = {$USER->user_id}, gridimage_id = {$row['gridimage_id']}");
}

#############################

$db->Execute("UNLOCK TABLES");

#############################

if (empty($_GET['sidebar'])) {
	$opentickets=$db->GetAll(
		"select t.*,suggester.realname as suggester,submitter.realname as submitter, i.title,
		moderator.realname as moderator
		from gridimage_ticket as t
		inner join user as suggester on (suggester.user_id=t.user_id)
		inner join gridimage as i on (t.gridimage_id=i.gridimage_id)
		inner join user as submitter on (submitter.user_id=i.user_id)
		inner join user as moderator on (moderator.user_id=t.moderator_id)
		where t.moderator_id>0 and t.status<>'closed'
		order by t.updated");
	$smarty->assign_by_ref('opentickets', $opentickets);
}

#############################

$template = (!empty($_GET['sidebar']))?'admin_tickets_sidebar.tpl':'admin_tickets.tpl';
$smarty->display($template);

	
?>
