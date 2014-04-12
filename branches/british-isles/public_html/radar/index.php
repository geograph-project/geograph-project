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

	if (!empty($_GET['t']) && preg_match('/^\w+$/',$_GET['t'])) {
		$url = "http://ww3.scenic-tours.co.uk/serve.php?t=".$_GET['t'];
	} else {
		$url = "http://ww3.scenic-tours.co.uk/serve.php?t=WoNObJvoOljhJL5405oOaO4juNMb4XhujtZ"; //this is a specific version, update it to the latest stable
                $url = "http://ww3.scenic-tours.co.uk/serve.php?t=WoNObJvoOhubJL5405oV8l8wabwwuhj8NVV";
	}
	preg_match('/=(\w+)$/',$url,$m);
	$token = $m[1];



if (!empty($_GET['manifest'])) {

	header("Content-Type: text/cache-manifest");
	print "CACHE MANIFEST\n";
	print "# ".date('r')."\n\n";
	print "CACHE:\n";
	print "http://geograph.org.uk/browser\n";
	print "http://www.geograph.org.uk/browser\n";
	print "http://www.geograph.org.uk/browser/\n";
	print "http://www.geograph.org.uk/browser/?t=$token\n";
	print "http://wac.3c13.edgecastcdn.net/803C13/nearby/geograph/playground/serve.php?t=$token&output=css\n";
	print "http://s1.geograph.org.uk/js/gears_init.v1.js\n";
	print "http://s1.geograph.org.uk/mapper/geotools2.v7300.js\n";
	print "http://wac.3c13.edgecastcdn.net/803C13/nearby/geograph/playground/serve.php?t=$token&output=js\n";
	print "http://wac.3c13.edgecastcdn.net/803C13/nearby/geograph/playground/serve.v1357848543.js\n";
	print "https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js\n";

	exit;
}


	$ctx = stream_context_create(array(
	    'http' => array(
	        'timeout' => 1
	        )
	    )
	);
        $mkey = $_SERVER['HTTP_HOST'];
	$mkey .= $url;
        if (rand(1,10) > 5 && $memcache->valid) {
                $remote =& $memcache->name_get('radar',$mkey);
        }

	if (empty($remote) || strlen($remote) < 512)
		$remote = file_get_contents($url, 0, $ctc);

	if (empty($remote) || strlen($remote) < 512) {
		if ($memcache->valid) {
			$remote =& $memcache->name_get('radar',$mkey);
		} else {
			die("Sorry, unable to load page. Please try later. <a href=\"/\">back to homepage</a>");
		}
	}

	if ($memcache->valid) {
		$memcache->name_set('radar',$mkey,$remote,$memcache->compress,$memcache->period_long*2);
	}

	$remote = str_replace('<head>','<head><base href="http://ww2.scenic-tours.co.uk/"/>',$remote);

	$remote = str_replace('"serve.','"http://wac.3c13.edgecastcdn.net/803C13/nearby/geograph/playground/serve.',$remote);


	$remote = str_replace('<html','<html manifest="/radar/cached.php?manifest=1&t='.$token.'"',$remote);

if (strpos($_SERVER['HTTP_USER_AGENT'],'MSIE ') !== FALSE) {
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<?
}

print $remote;



