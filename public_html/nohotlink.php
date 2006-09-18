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

	
if ($_SERVER["PATH_INFO"]) {
	$filename = $_SERVER["PATH_INFO"];
} else {
	$filename = $_SERVER["SCRIPT_NAME"];
}

$path = "/{$_SERVER['HTTP_HOST']}/";
if (preg_match('/\/(\d{6})/',$filename,$m))
	$path .= "photo/".$m[1];

$img=imagecreatetruecolor(250,100);

$blue=imagecolorallocate ($img, 101,117,255);
imagefill($img,0,0,$blue);
$black=imagecolorallocate($img, 255,255,255);

imagestring($img, 2, 5, 5, "Image from", $black);	
imagestring($img, 3, 72, 5, "{$_SERVER['HTTP_HOST']}", $black);

imagestring($img, 5, 5, 30, "View image at:", $black);

imagestring($img, 2, 10, 45, "http:/", $black);
imagestring($img, 2, 44, 45, $path, $black);
imageline($img, 10, 58, 44+strlen($path)*imagefontwidth(2), 58, $black);

imagestring($img, 2, 15, 70, "To prevent this message take a copy.", $black);
imagestring($img, 2, 10, 84, "All images a Creative Commons Licenced", $black);

header("Content-Type: image/png");
imagepng($img,NULL,50);

?>
