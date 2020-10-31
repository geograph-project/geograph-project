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

if (false) {
	header("HTTP/1.0 503 Unavailable");
	print "<h3>Sorry. The browser is currently offline due to server issues.</h3>";
	print "<p>We hope to restore service within 24 hours";
	print "<p>return to <a href=/>Geograph Homepage</a>";
	exit;
}

require_once('geograph/global.inc.php');

if (empty($CONF['browser_url'])) {
	header("HTTP/1.0 503 Unavailable");
	$smarty = new GeographPage;

	$smarty->display("sample8_unavailable.tpl");
	exit;
}


$seconds = 3600;

//init_session();
init_session_or_cache($seconds, 900); //cache publically, and privately

customGZipHandlerStart();

############

if (!empty($_GET['_escaped_fragment_'])) {
	include __DIR__."/_fake-browser.php";
	exit;
}

############

	$ctx = stream_context_create(array(
	    'http' => array(
	        'timeout' => 1
	        )
	    )
	);
        $mkey = $_SERVER['HTTP_HOST'];
	$url = $CONF['browser_url'];
	if (!empty($_GET['t'])) {
		$url .= "&t=".$_GET['t'];
		$mkey .= $url;
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

	//these are now local
	//$remote = str_replace('<head>','<head><base href="http://ww2.scenic-tours.co.uk/"/>',$remote);
	$remote = preg_replace('/"serve\.v(\d+)\.js"/','"serve.php?v=$1"',$remote);

if (isset($_GET['inner'])) {
	$str = "<script>
	function resizeContainer() {
		var FramePageHeight =  document.body.offsetHeight + 10;
		window.parent.document.getElementById('iframe').style.height=FramePageHeight+'px';
	}
	setInterval(resizeContainer,1000);
	</script>";
	##$remote = str_replace('</body>',$str.'</body>',$remote);
	$remote = str_replace('Back to Geograph','',$remote);
} else {
	$remote = str_replace('Back to Geograph','Geograph Homepage',$remote);
}


$remote = str_replace('>var user_id = 3;<','>var user_id = '.$USER->user_id.';<',$remote);
$remote = str_replace('<body>','<body class="theme_'.$USER->getStyle().'">',$remote);

if (strpos($_SERVER['HTTP_USER_AGENT'],'MSIE ') !== FALSE) {
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<?
}

print $remote;




//browser, is one of the few 'pages' that doesnt use smarty to render template
if (function_exists('recordVisitor'))
	recordVisitor();


