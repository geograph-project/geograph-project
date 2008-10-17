<?php
/**
 * $Project: GeoGraph $
 * $Id: staticpage.php 3256 2007-04-12 15:20:28Z barry $
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

ini_set('expose_php',0);

$filename = preg_replace('/\.v[0-9]+\./','.',$_SERVER["SCRIPT_NAME"]);

$cachename = "cache/".str_replace('/','-',$_SERVER["SCRIPT_NAME"]);

if (strpos($cachename,'..') !== FALSE || stripos($cachename,'%2E') !== FALSE) {
	header("HTTP1.0 404 Forbidden");
	die();
}

if (!empty($_SERVER['HTTP_ACCEPT_ENCODING'])) {
	$gzip = strstr($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip');
	$deflate = strstr($_SERVER['HTTP_ACCEPT_ENCODING'], 'deflate');

	$encoding = $gzip ? 'gzip' : ($deflate ? 'deflate' : '');

	if (!strstr($_SERVER['HTTP_USER_AGENT'], 'Opera') && 
			preg_match('/^Mozilla\/4\.0 \(compatible; MSIE ([0-9]\.[0-9])/i', $_SERVER['HTTP_USER_AGENT'], $matches)) {
		$version = floatval($matches[1]);

		if ($version < 6)
			$encoding = '';

		if ($version == 6 && !strstr($_SERVER['HTTP_USER_AGENT'], 'EV1')) 
			$encoding = '';
	}
	if ($encoding) {
		$cachename .= ".$encoding";
		header ('Content-Encoding: '.$encoding);
	}
} else {
	$encoding = '';
}

header ('Vary: Accept-Encoding');

//an important note here, the cache lives FOREVER!

if (($mtime = apc_fetch("d$cachename")) === FALSE) {
	$mtime = @filemtime(".$filename");
	apc_store("d$cachename",$mtime,360000);

	if (!file_exists($cachename)) {

		$contents = implode('',file(".$filename"));

		if (strpos($filename,'css') !== FALSE) {
			// Compress whitespace.
#			$contents = preg_replace('/\s+/', ' ', $contents);

			// Remove comments.
#			$contents = preg_replace('/\/\*.*?\*\//', '', $contents);

		} elseif (strpos($filename,'js') !== FALSE) {
#			require_once dirname(__FILE__).'/lib/jsmin.php';
#			$contents = JSMin::minify($contents);
		}

		if ($encoding) {
			$contents = gzencode($contents, 9,  ($encoding == 'gzip') ? FORCE_GZIP : FORCE_DEFLATE);
		}

		file_put_contents($cachename,$contents);
	} 
}

customExpiresHeader(3600*24*180,true);
customCacheControl($mtime,$cachename);

if (strpos($cachename,'css') !== FALSE) {
	header("Content-type: text/css");
} else {
	header("Content-type: application/x-javascript");
}

if (($fsize = apc_fetch("s$cachename")) === FALSE) {
        $fsize = filesize($cachename);
        apc_store("s$cachename",$fsize,360000);
}

header('Content-length: '.$fsize);

if (($contents = apc_fetch("c$cachename")) === FALSE) {
	apc_store("c$cachename",implode('',file($cachename)),360000);
	readfile($cachename);
} else {
	echo $contents;
}

exit;



#################


function customCacheControl($mtime,$uniqstr,$useifmod = true,$gmdate_mod = 0) {
	global $encoding;
	if (isset($encoding) && $encoding != 'none') {
		$uniqstr .= $encoding;
	}
	
	$hash = "\"".crc32($mtime.'-'.$uniqstr)."\"";

	
	if(isset($_SERVER['HTTP_IF_NONE_MATCH'])) { // check ETag
		if($_SERVER['HTTP_IF_NONE_MATCH'] == $hash ) {
			header("HTTP/1.0 304 Not Modified");
			header ("Etag: $hash"); 
			header('Content-Length: 0'); 
			exit;
		}
	}	

	header ("Etag: $hash"); 

	if (!$gmdate_mod)
		$gmdate_mod = gmdate('D, d M Y H:i:s', $mtime) . ' GMT';

	if ($useifmod && !empty($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
		$if_modified_since = preg_replace('/;.*$/', '', $_SERVER['HTTP_IF_MODIFIED_SINCE']);

		if ($if_modified_since == $gmdate_mod) {
			header("HTTP/1.0 304 Not Modified");
			header('Content-Length: 0'); 
			exit;
		}
	}

	header("Last-Modified: $gmdate_mod");
}


function customExpiresHeader($diff,$public = false) {
	if ($diff > 0) {
		$expires=gmstrftime("%a, %d %b %Y %H:%M:%S GMT", time()+$diff);
		header("Expires: $expires");
		header("Cache-Control: max-age=$diff");
	} else {
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past 
	}
	if ($public)
		header("Cache-Control: Public",true);
}

?>
