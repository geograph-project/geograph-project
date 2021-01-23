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
init_session_or_cache($seconds*12, 900*12); //cache publically, and privately

customGZipHandlerStart();

$url = "https://www.geograph.org/leaflet/new.php";
if (!empty($_GET['q']))
	$url .= "?q=".urlencode($_GET['q']);
elseif (!empty($_GET['c']))
	$url .= "?c=".urlencode($_GET['c']);


	$ctx = stream_context_create(array(
	    'http' => array(
	        'timeout' => 1
	        )
	    )
	);
        $mkey = $_SERVER['HTTP_HOST'];
	$mkey .= $url;
        if (rand(1,10) > 5 && $memcache->valid) {
                $remote =& $memcache->name_get('quick',$mkey);
        }

	if (empty($remote) || strlen($remote) < 512)
		$remote = file_get_contents($url, 0, $ctc);

	if (empty($remote) || strlen($remote) < 512) {
		if ($memcache->valid) {
			$remote =& $memcache->name_get('quick',$mkey);
		} else {
			die("Sorry, unable to load page. Please try later. <a href=\"/\">back to homepage</a>");
		}
	}

	if ($memcache->valid) {
		$memcache->name_set('quick',$mkey,$remote,$memcache->compress,$memcache->period_long*2);
	}

	$remote = str_replace('<head>','<head><base href="https://www.geograph.org/leaflet/"/>',$remote);



if (strpos($_SERVER['HTTP_USER_AGENT'],'MSIE ') !== FALSE) {
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<?
}

print $remote;



