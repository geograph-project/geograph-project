<?php
/**
 * $Project: GeoGraph $
 * $Id: faq.php 15 2005-02-16 12:23:35Z lordelph $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2007 Barry Hunter (geo@barryhunter.co.uk)
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

$cacheid = $USER->hasPerm('basic')?$USER->user_id:0;

$isadmin=$USER->hasPerm('moderator')?1:0;
$smarty->assign_by_ref('isadmin', $isadmin);

$template = 'events.tpl';

if ($isadmin) {
	if (!empty($_GET['id']) && preg_match('/^\d+$/',$_GET['id'])) {
		$db=NewADOConnection($GLOBALS['DSN']);
		
		$a = intval($_GET['approve']);	
		
		$sql = "UPDATE geoevent SET approved = $a WHERE id = ".$db->Quote($_GET['id']);
		$db->Execute($sql);

		
		
		$smarty->clear_cache($template, $cacheid);
	}
}
if (!$smarty->is_cached($template, $cacheid))
{
	$smarty->assign('google_maps_api_key',$CONF['google_maps_api_key']);

	$db=NewADOConnection($GLOBALS['DSN']);
	
	$prev_fetch_mode = $ADODB_FETCH_MODE;
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$list = $db->getAll("
	select geoevent.*,
		realname,
		(event_time > now()) as future,
		grid_reference,x,y,
		sum(type='attend') as attendees
	from geoevent 
		inner join user using (user_id)
		inner join gridsquare using (gridsquare_id)
		left join geoevent_attendee using (geoevent_id)
	where ((approved = 1) 
		or user.user_id = {$USER->user_id}
		or ($isadmin and approved != -1)
		) and (event_time > date_sub(now(),interval 1 month) )
	group by geoevent_id
	order by future desc,event_time");
	
	
	$conv = new Conversions;
	$f = 0;
	foreach ($list as $i => $row) {
		if ($row['future']) {
			$f++;
		}
		list($list[$i]['wgs84_lat'],$list[$i]['wgs84_long']) = $conv->internal_to_wgs84($row['x'],$row['y']);
	}
	$smarty->assign('future', $f);
	$smarty->assign_by_ref('list', $list);

}

$smarty->display($template, $cacheid);

	
?>
