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


$db=NewADOConnection($GLOBALS['DSN']);
if (!$db) die('Database connection failed');


$ins = "INSERT INTO usage_log SET
	action = ".$db->Quote(@$_GET['action']).",
	param = ".$db->Quote(@$_GET['param']).",
	value = ".$db->Quote(@$_GET['value']).",
	ipaddr = INET6_ATON('".getRemoteIP()."'),
	user_id = ".intval($USER->user_id).",
	useragent = ".$db->Quote($_SERVER['HTTP_USER_AGENT']).",
	session = ".$db->Quote(session_id());

$db->Execute($ins);


//not perfect, but lets count viewing a page of thumbs or the large image as a 'pageview'.
if (($_GET['action'] == 'view' && $_GET['param'] == 'page')
   ||($_GET['action'] == 'set' && $_GET['param'] == 'display' && in_array($_GET['value'], array('date_slider','grouped','map','map_dots')) )
   ||($_GET['action'] == 'show' && $_GET['param'] == 'main')) {
   // we only bother with update, as it should NEVER be the 'first' view.
   //$upd = "UPDATE visitor_log SET pages=pages+1 WHERE `session` = ".$db->Quote(session_id());
   //$db->query($upd);

	//actully lets just use the full function. we may need to update session1 anyway. (turns out can be first view, if session expired as was auto-renewed with remember_me!)
	recordVisitor();
}
