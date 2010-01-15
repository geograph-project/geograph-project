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

if (isset($CONF['curtail_level']) && $CONF['curtail_level'] > 5 ) {
	header("HTTP/1.1 503 Service Unavailable");
	die("server busy, please try later");
}

$USER->hasPerm("admin") || $USER->hasPerm("ticketmod") || $USER->mustHavePerm("moderator");

if (isset($_SESSION['editpage_options']))
	unset($_SESSION['editpage_options']);
$_SESSION['thumb'] = true;

$smarty = new GeographPage;

$template='admin_index.tpl';
$cacheid=$USER->user_id;
$smarty->caching=0;


	$db = GeographDatabaseConnection(true);

if ($USER->hasPerm("ticketmod")) {
		
	$smarty->assign('tickets_new', $db->GetOne("select count(*) from gridimage_ticket where moderator_id=0 and status<>'closed' and deferred < date_sub(NOW(),INTERVAL 24 HOUR)"));
	$smarty->assign('tickets_yours', $db->GetOne("select count(*) from gridimage_ticket where moderator_id={$USER->user_id} and status<>'closed'"));
	
	$smarty->assign('contacts_open', $db->GetOne("select count(*) from contactform where status = 'open' and moderator_id in (0,{$USER->user_id})"));
}

if ($USER->hasPerm("moderator")) {
	$smarty->assign('images_pending', $db->GetRow("select count(*) as `count`,(unix_timestamp(now()) - unix_timestamp(min(submitted))) as age from gridimage where moderation_status='pending'"));
	
	$smarty->assign('gridsquares_sea', $db->GetAssoc("select reference_index,count(*) from gridsquare where percent_land=-1 group by reference_index"));


	$smarty->assign('images_pending_available', $db->GetOne("select count(distinct gridimage_id) from gridimage as gi left join gridsquare_moderation_lock as l on(gi.gridsquare_id=l.gridsquare_id and lock_obtained > date_sub(NOW(),INTERVAL 1 HOUR) ) where submitted > date_sub(now(), interval 7 day) and submitted < date_sub(now(),interval 30 minute) and (moderation_status = 2) and (l.gridsquare_id is null OR (l.user_id = {$USER->user_id} AND lock_type = 'modding') OR (l.user_id != {$USER->user_id} AND lock_type = 'cantmod') )"));

	$smarty->assign('gridsquares_sea_test', $db->GetOne("select count(*) from mapfix_log where old_percent_land=-1 and created > date_sub(now(),interval 30 minute) and user_id != {$USER->user_id}"));


	$smarty->assign('articles_ready', $db->getOne("select count(*) from article where licence != 'none' and approved = 0"));

	$smarty->assign('originals_new', $db->getOne("select count(*) from gridimage_pending where status = 'new' and type = 'original'")+1);
}

$smarty->assign('names_pending', $db->GetOne("select count(*) from game_score where approved=0"));

$smarty->assign('pics_pending', $db->GetOne("select count(*) from gridimage_daily where showday is null and (vote_baysian > 3.5)"));
$smarty->assign('pics_no_vote', $db->GetOne("select count(*) from gridimage_daily where showday is null and (vote_baysian = 0)"));

$smarty->display($template,$cacheid);

	
?>
