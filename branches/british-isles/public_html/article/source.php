<?php
/**
 * $Project: GeoGraph $
 * $Id: article.php 6904 2010-11-13 18:44:10Z barry $
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

if (empty($_GET['url']) || preg_match('/[^\w\.\,-]/',$_GET['url'])) {
	header("HTTP/1.0 404 Not Found");
	header("Status: 404 Not Found");
	print "404";
	exit;
}

$db = GeographDatabaseConnection(true);

$page = $db->getRow("
select content,update_time
from article 
where ( (licence != 'none' and approved > 0) 
	or user_id = {$USER->user_id} )
	and url = ".$db->Quote($_GET['url']).'
limit 1');
if (count($page)) {
	
	//when this page was modified
	$mtime = strtotime($page['update_time']);
	
	//can't use IF_MODIFIED_SINCE for logged in users as has no concept as uniqueness
	customCacheControl($mtime,$cacheid,($USER->user_id == 0));

	header("Content-Type: text/plain");
	print $page['content'];

} else {
	header("HTTP/1.0 404 Not Found");
	header("Status: 404 Not Found");
	print "404";
}
