<?php
/**
 * $Project: GeoGraph $
 * $Id$
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
init_session();



	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');  
	#$db->debug = true;
	
	$all = $db->cachegetAssoc(3600,"select right(grid_reference,4) as gr,count(*) as c from gridimage_search group by right(grid_reference,4)");

	if ($_GET['u']) {
		foreach (range(0,99) as $x) {
			foreach (range(0,99) as $y) {
				$gr = sprintf("%02d%02d",$x,$y);
				if (empty($all[$gr]))
					print "$gr<BR>";
			}		
		}
		exit;
	}

	foreach ($all as $gr => $c) {
		$m = max($m,$c);
	}
$imgw = 400; $imgh=400; $pixels_per_km = 4;
	$img=imagecreatetruecolor($imgw,$imgh);

	//fill in with sea
	$blue=imagecolorallocate ($img, 101,117,255);
	imagefill($img,0,0,$blue);
	$o = 255;
	$tune = ($_GET['t'])?$_GET['t']:0.75;
	$ratio = $tune * $m;
	for($i = 0;$i < $m; $i++) {
		$o = ( log($i) * $ratio);
		if ($_GET['i']) $o = 255 - $o;
		$o = max($o,0);
		$col[$i]=imagecolorallocate ($img, $o,$o,$o);
	#	$o-=(255/$m);
	if ($_GET['debug']) print "$i $o<br>";
	}
	if ($_GET['debug']) exit;
	foreach ($all as $gr => $c) {
		preg_match("/(\d{2})(\d{2})/",$gr,$m);
		$imgx1 = $m[1] * $pixels_per_km;
		$imgy1 = $imgh - ($m[2] * $pixels_per_km) - $pixels_per_km;
		
	
		$imgx2=$imgx1 + $pixels_per_km;
		$imgy2=$imgy1 + $pixels_per_km;
		imagefilledrectangle ($img, $imgx1, $imgy1, $imgx2, $imgy2, $col[$c]);
		

	}		
	header("Content-Type: image/png");
	imagepng($img);
?>
