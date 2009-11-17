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

if (!empty($_GET['r'])) {
	$token=new Token;
	$token->setValueBinary("r", $_GET['r']);


	print "<p>Copy/Paste all this into a forum thread..</p>";
	print "<p><input size=110 value=\"[img]http://www.geograph.org.uk/stuff/tile-mangle.php?t=".$token->getToken()."&amp;/.jpg[/img]\"></p>";
	exit;
	
} elseif (!empty($_GET['t'])) {
	$token=new Token;

	if ($token->parse($_GET['t']) && $token->hasValue("r")) {
		$r = $token->getValueBinary("r");
	} else {
		die("nope!");
	}

} else {
	die("ha!");
}


$mapurl = 'http://t0.geograph.org.uk/tile.php?r='.$r;



customCacheControl(filemtime(__FILE__),$_SERVER['QUERY_STRING']);
customExpiresHeader(86400*3,true);

header("Content-Type: image/png");
	

$str =& $memcache->name_get('tile',$mapurl);

if (empty($str)) {
	$str =  file_get_contents($mapurl);


	if (!$str || strlen($str) < 600) { //something so small is likly to be an error message :(
		header("HTTP/1.0 503 Service Unavailable");
		$str = file_get_contents("../maps/sorry.png");
	} else {
		$im1 = imagecreatefromstring($str);

		$s = 80;

		$im2 = imagecreate($s,$s);
		imagecopyresized($im2,$im1,0,0,0,0,$s,$s,250,250);

		$im3 = imagecreate(250,250);
		imagecopyresampled($im3,$im2,0,0,0,0,250,250,$s,$s);

		ob_start();
		imagepng($im3);
		$str = ob_get_clean();
		
		$memcache->name_set('tile',$mapurl,$str,$memcache->compress,$memcache->period_long);


	}
}

print $str;





