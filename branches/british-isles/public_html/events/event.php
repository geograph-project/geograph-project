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

if (empty($_GET['id']) || preg_match('/[^\d]/',$_GET['id'])) {
	$smarty->display('static_404.tpl');
	exit;
}

$event_id = intval($_GET['id']);

$db=NewADOConnection($GLOBALS['DSN']);

$isadmin=$USER->hasPerm('moderator')?1:0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

	$updates = array();
	if (empty($_POST['attendee'])) {
		$page = array();
		$updates[] = "`user_id` = {$USER->user_id}";
	} else {
		$page = $db->getRow("
		select *
		from geoevent_attendee 
		where geoevent_attendee_id = ".$db->Quote($_POST['attendee']));
		if ($page['user_id'] != $USER->user_id && !$isadmin) {
			die("fatal error");
		}
	}

	foreach (array('type','message') as $key) {
		if ($page[$key] != $_POST[$key]) {
			$updates[] = "`$key` = ".$db->Quote($_POST[$key]); 
		}		
	}
	if (!count($updates)) {
		$smarty->assign('error', "No Changes to Save");
	} else {
		if (empty($_POST['attendee'])) {
			$updates[] = "`created` = NOW()";
			$updates[] = "`geoevent_id` = $event_id";
			$sql = "INSERT INTO geoevent_attendee SET ".implode(',',$updates);
		} else {

			$sql = "UPDATE geoevent_attendee SET ".implode(',',$updates)." WHERE geoevent_attendee_id = ".$db->Quote($_REQUEST['attendee']);
		}
				
		$db->Execute($sql);
		
		$memcache->name_increment('ep',$event_id,1,true);

		$smarty->clear_cache('events.tpl');
	}
}


$template = 'events_event.tpl';
$cacheid = 'event|'.$event_id;
if ($serial = & $memcache->name_get('ep',intval($event_id)) ) {
	$cacheid .= '|'.$serial;
}
$cacheid .= '|'.$USER->hasPerm('moderator')?1:0;




$sql_where = " geoevent_id = ".$db->Quote($event_id);

$page = $db->getRow("
select geoevent.*,DATEDIFF(event_time,NOW()) as days,
realname,gs.gridsquare_id,gs.grid_reference
from geoevent 
	left join user using (user_id)
	left join gridsquare gs on (geoevent.gridsquare_id = gs.gridsquare_id)
where $sql_where
limit 1");

if (count($page)) {
	
	if ($page['approved'] == -1 && !$USER->hasPerm('moderator')) {
		header("HTTP/1.0 403 Forbidden");
		header("Status: 403 Forbidden");
		$template = "static_404.tpl";
	}

	if ($page['user_id'] == $USER->user_id) {
		$cacheid .= '|'.$USER->user_id;
	}
	
	//when this page was modified
	$mtime = strtotime($page['updated']);
		
	//can't use IF_MODIFIED_SINCE for logged in users as has no concept as uniqueness
	customCacheControl($mtime,$cacheid,($USER->user_id == 0));

}

if (!$smarty->is_cached($template, $cacheid))
{
	if (count($page)) {
		$smarty->assign('google_maps_api_key',$CONF['google_maps_api_key']);
		
		$smarty->assign($page);
		if (!empty($page['extract'])) {
			$smarty->assign('meta_description', $page['description']);
		}
		
		if (!empty($page['gridsquare_id'])) {
			$square=new GridSquare;
			$square->loadFromId($page['gridsquare_id']);
			$smarty->assign('grid_reference', $square->grid_reference);
			
			require_once('geograph/conversions.class.php');
			$conv = new Conversions;
		
			list($lat,$long) = $conv->gridsquare_to_wgs84($square);
			$smarty->assign('lat', $lat);
			$smarty->assign('long', $long);
		}
		if (!empty($page['gridimage_id'])) {
			
			$image=new GridImage();
			$image->loadFromId($page['gridimage_id']);

			if ($image->moderation_status=='rejected' || $image->moderation_status=='pending') {
				//clear the image
				$image= false;
			} 
			$smarty->assign_by_ref('image', $image);
		}
	} else {
		$template = 'static_404.tpl';
		header("HTTP/1.0 404 Not Found");
		header("Status: 404 Not Found");
	}
} else {
	$smarty->assign('user_id', $page['user_id']);
	$smarty->assign('url', $page['url']);
}

$types = array('attend'=>'will attend','maybe'=>'probably attend','not'=>'unable to attend');

$prev_fetch_mode = $ADODB_FETCH_MODE;
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
$list = $db->getAll($sql = "
select geoevent_attendee.*,realname
from geoevent_attendee 
	left join user using (user_id)
where $sql_where
order by `type`+0,updated desc");
$stats = array();
$a = false;
foreach ($list as $i => $row) {
	if (!empty($USER->user_id) && $USER->user_id == $row['user_id']) {
		$smarty->assign('attendee', $list[$i]);
		$a = true;
	}
	$type = $list[$i]['type'] = $types[$row['type']];
	$stats[$type]=isset($stats[$type])?($stats[$type]+1):1;
}

$smarty->assign_by_ref('stats', $stats);
$smarty->assign_by_ref('list', $list);
$smarty->assign('types', $types);
$smarty->assign('geoevent_id', $page['geoevent_id']);

$smarty->display($template, $cacheid);

	
?>
