<?php
/**
 * $Project: GeoGraph $
 * $Id: staticpage.php 5779 2009-09-12 09:31:23Z barry $
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


customGZipHandlerStart();
customExpiresHeader(86400*3,true);

//no better way at the moment...
customCacheControl(filemtime(__FILE__),$_SERVER['QUERY_STRING']);


#if ($_GET['source'] == 'OSM-cycle')

$mapurl = "http://old-dev.openstreetmap.org/~ojw/StaticMap/?mode=Export&show=1&layer=cycle&";



if (preg_match('/^[\w&=\.-]+$/',$_SERVER['QUERY_STRING'])) {
	$mapurl .= $_SERVER['QUERY_STRING'];
}



//Pass these though - gives more transparency what we doing
$opts = array(
  'http'=>array(
    'user_agent'=>$_SERVER['HTTP_USER_AGENT'],
    'header'=>"Referer: {$_SERVER['HTTP_REFERER']}\r\n" 
  )
);

$context = stream_context_create($opts);

//todo - run it via memcache (at the moment its primarlly for access via webarchive.org.uk - which only crawls once

header("Content-Type: image/png");
$str =  file_get_contents($mapurl, false, $context);

if (!$str || strlen($str) < 600) { //something so small is likly to be an error message :(

	$str = file_get_contents("maps/sorry.png");
}


print $str;


