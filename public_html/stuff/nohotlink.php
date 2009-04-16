<?php
/**
 * $Project: GeoGraph $
 * $Id: imagemap.php 1690 2005-12-22 15:05:42Z barryhunter $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Barry Hunter (geo@barryhunter.co.uk)
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

if (!empty($_SERVER["PATH_INFO"])) {
	$filename = $_SERVER["PATH_INFO"];
} else {
	$filename = $_SERVER["SCRIPT_NAME"];
}

$path = "/{$_SERVER['HTTP_HOST']}/";
if (preg_match('/\/(\d{6,})_/',$filename,$m)) {#
	$id = intval($m[1]);
	$path .= "photo/".$id;
}


$t=time()+(3600*24*7);
$expires=strftime("%a, %d %b %Y %H:%M:%S GMT", $t);
header("Expires: $expires");
customCacheControl(filemtime(__FILE__),$m[1]);



$img=imagecreate(250,112);

$blue=imagecolorallocate ($img, 101,117,255);
imagefill($img,0,0,$blue);
$black=imagecolorallocate($img, 255,255,255);


if ($id) {
	//die as quickly as possible with the minimum 
	$db = NewADOConnection($GLOBALS['DSN']);

	$realname =& $db->getOne($sql = "select realname from gridimage_search where gridimage_id=".intval($id) );
}
	
if (!empty($realname)) {
	imagestring($img, 2, 8, 5, "c Copyright", $black);	
	imageellipse($img, 10, 12, 12, 12, $black);	

	imagestring($img, 3, 78, 5, $realname, $black);
} else {
	imagestring($img, 2, 5, 5, "Image from", $black);	

	imagestring($img, 3, 72, 5, "{$_SERVER['HTTP_HOST']}", $black);
}


imagestring($img, 5, 5, 30, "View image at:", $black);

imagestring($img, 2, 10, 45, "http:/", $black);
imagestring($img, 2, 44, 45, $path, $black);
imageline($img, 10, 58, 44+strlen($path)*imagefontwidth(2), 58, $black);

imagestring($img, 2, 5, 70, "All images are Creative Commons Licensed", $black);
imagestring($img, 2, 20, 84, "To prevent this message take a copy", $black);
imagestring($img, 2, 36, 98, "Image creator must be credited", $black);

if (empty($_SERVER["PATH_INFO"])) {
	header("HTTP/1.1 403 Forbidden");
	header("Status: 403 Forbidden");
}

if ( (preg_match('/thread|topic|forum|28dayslater|secretscotland|geograph\.org\.uk|hbwalkersaction/',$_SERVER['HTTP_REFERER']) || !empty($_SERVER["PATH_INFO"]))
	&& preg_match('/^\/(geo|)photos\/(\d+\/|)\d+\/\d+\/\d{6,}_(\w+)\.jpg$/',$filename,$m) 
	&& strpos($m[3],'_') === FALSE && file_exists($_SERVER['DOCUMENT_ROOT'].$filename)) {
	$fullimg = imagecreatefromjpeg($_SERVER['DOCUMENT_ROOT'].$filename); 
	$fullw=imagesx($fullimg);
	$fullh=imagesy($fullimg);

	$iw=imagesx($img);
	$ih=imagesy($img);

	imagecopy($fullimg,$img,$fullw-$iw,$fullh-$ih,0,0,$iw,$ih);

	header("Content-Type: image/jpeg");
	imagejpeg($fullimg,null,75);
} else {
	header("Content-Type: image/png");
	imagepng($img);
}
?>
