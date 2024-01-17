<?php
/**
 * $Project: GeoGraph $
 * $Id: record_vote.php 6944 2010-12-03 21:44:38Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2008 Barry Hunter (geo@barryhunter.co.uk)
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

if (isset($_GET['html'])) {
        print "thanks - you may close this window";
} else {
	header("HTTP/1.0 204 No Content");
	header("Status: 204 No Content");
	header("Content-Length: 0");
}

flush();


$db = GeographDatabaseConnection(false);

$decode = json_decode(file_get_contents("php://input"),true);


/*
   value: delta, // Use `delta` so the value can be summed.
    // Custom params:
    metric_id: id, // Needed to aggregate events.
    metric_value: value, // Optional.
    metric_delta: delta, // Optional.

    // OPTIONAL: any additional params or debug info here.
    // See: https://web.dev/debug-web-vitals-in-the-field/
    // metric_rating: 'good' | 'needs-improvement' | 'poor'. 'needs-improvement' was 'ni'
    metric_rating: rating,
    // debug_info
    debug_target: debugTarget,
    debug_event: attribution ? attribution.eventType||'' : '',
    debug_timing: attribution ? attribution.loadState||'' : '',
    event_time: attribution ? attribution.largestShiftTime
*/

$ins = "INSERT INTO cwv_log SET

	name = ".$db->Quote(@$decode['name']).",
	navigation = ".$db->Quote(@$decode['navigation']).",
	metric_id = ".$db->Quote(@$decode['metric_id']).",
	metric_value = ".floatval(@$decode['metric_value']).",
	metric_delta = ".floatval(@$decode['metric_delta']).",

	metric_rating = ".$db->Quote(@$decode['metric_rating']).",
	debug_target = ".$db->Quote(@$decode['debug_target']).",
	debug_event = ".$db->Quote(@$decode['debug_event']).",
	debug_timing = ".$db->Quote(@$decode['debug_timing']).",
	event_time = ".$db->Quote(@$decode['event_time']).",

	href = ".$db->Quote(@$_SERVER['HTTP_REFERER']).",
	ipaddr = INET6_ATON('".getRemoteIP()."'),
	user_id = ".intval($USER->user_id).",
	useragent = ".$db->Quote($_SERVER['HTTP_USER_AGENT']).",
	session = ".$db->Quote(session_id());

$db->Execute($ins);



