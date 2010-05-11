<?php
/**
 * $Project: GeoGraph $
 * $Id$
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

header("HTTP/1.0 204 No Content");
header("Status: 204 No Content");
header("Content-Length: 0");
flush();


$db=NewADOConnection($GLOBALS['DSN']);
if (!$db) die('Database connection failed');

$ins = "INSERT INTO vote_log SET
	type = ".$db->Quote(@$_GET['t']).",
	id = ".intval(@$_GET['id']).",
	vote = ".intval(@$_GET['v']).",
	ipaddr = INET_ATON('".getRemoteIP()."'),
	user_id = ".intval($USER->user_id).",
	ua = ".$db->Quote($_SERVER['HTTP_USER_AGENT']);
	
$db->Execute($ins);



