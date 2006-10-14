<?php
/**
 * $Project: GeoGraph $
 * $Id: tickets.php 1568 2005-11-15 14:36:34Z barryhunter $
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

$USER->mustHavePerm("basic");

$smarty = new GeographPage;

$db = NewADOConnection($GLOBALS['DSN']);


$newtickets=$db->GetAll(
	"select t.*, i.title ".
	"from gridimage_ticket as t ".
	"inner join gridimage as i on (t.gridimage_id=i.gridimage_id) ".
	"left join gridimage_ticket_comment as c on (t.gridimage_ticket_id=c.gridimage_ticket_id) ".
	"where i.user_id = {$USER->user_id} and t.moderator_id=0 ". 
	"and c.gridimage_ticket_id IS NULL and t.status<>'closed' ".
	"order by t.suggested");
$smarty->assign_by_ref('newtickets', $newtickets);


$opentickets=$db->GetAll(
	"select t.*, i.title, ".
	"moderator.realname as moderator ".
	"from gridimage_ticket as t ".
	"inner join gridimage as i on (t.gridimage_id=i.gridimage_id) ".
	"left join gridimage_ticket_comment as c on (t.gridimage_ticket_id=c.gridimage_ticket_id) ".
	"inner join user as moderator on (moderator.user_id=t.moderator_id) ".
	"where i.user_id = {$USER->user_id} and t.status<>'closed' ".
	"and (t.moderator_id>0 or c.gridimage_ticket_id IS NOT NULL) ".
	"order by t.updated");
$smarty->assign_by_ref('opentickets', $opentickets);

$template = ($_GET['sidebar'])?'tickets_sidebar.tpl':'tickets.tpl';
$smarty->display($template);

	
?>
