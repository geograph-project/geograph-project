<?php
/**
 * $Project: GeoGraph $
 * $Id: staticpage.php 7628 2012-06-27 15:22:45Z geograph $
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

$seconds = 3600;

//init_session();
init_session_or_cache($seconds, 900); //cache publically, and privately

customGZipHandlerStart();

############

	$ctx = stream_context_create(array(
	    'http' => array(
	        'timeout' => 1
	        )
	    )
	);
        $mkey = $_SERVER['HTTP_HOST'].$_GET['output'];

	if (empty($_GET['output'])) {
                header("Content-Type: text/javascript");
		$url = "http://ww2.scenic-tours.co.uk/serve.js";
		$mkey .= ".js";
	} elseif (!empty($_GET['t'])) {
		$url = "http://ww2.scenic-tours.co.uk/serve.php?t=".$_GET['t']."&output=".$_GET['output'];
		$mkey .= $url;
	} else {
		$url = "http://ww4.scenic-tours.co.uk/serve.php?t=WolhXJL5405oNulVhXhhbluwN44X&output=".$_GET['output'];
		 //ww4 runs around edgecast! we use memcache anyway, so dont need the caching
	}
        if (rand(1,10) > 5 && $memcache->valid) {
                $remote =& $memcache->name_get('browser',$mkey);
        }

	if (empty($remote) || strlen($remote) < 512)
		$remote = file_get_contents($url, 0, $ctc);

	if (empty($remote) || strlen($remote) < 512) {
		if ($memcache->valid) {
			$remote =& $memcache->name_get('browser',$mkey);
		} else {
			die("Sorry, unable to load page. Please try later. <a href=\"/\">back to homepage</a>");
		}
	}

	if ($memcache->valid) {
		$memcache->name_set('browser',$mkey,$remote,$memcache->compress,$memcache->period_long*2);
	}


        if ($_GET['output'] == 'js' || $_GET['output'] == 'js2') {
                header("Content-Type: text/javascript");
        }
        if ($_GET['output'] == 'css') {
                header("Content-Type: text/css");
	}

print $remote;

