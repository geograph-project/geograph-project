<?php
/**
 * $Project: GeoGraph $
 * $Id: mapbrowse.php 2630 2006-10-18 21:12:28Z barry $
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
require_once('geograph/gridsquare.class.php');

if (isset($_GET['map']))
{
	require_once('geograph/map.class.php');
	require_once('geograph/mapmosaic.class.php');
	require_once('geograph/gridimage.class.php');

	require_once('geograph/image.inc.php');
					
	//render and return a map with token $_GET['map'];
	$map=new GeographMap;
	if (isset($_GET['refresh']) && $_GET['refresh'] == 2 && (init_session() || true) && $USER->hasPerm('admin'))
		$map->caching=false;
	if($map->setToken($_GET['map']))
		$map->returnImage();
	exit;
	
} elseif (isset($_GET['r'])) {
	require_once('geograph/rastermap.class.php');
	$square = false;				
	$rastermap = new RasterMap($square);
	if (isset($_GET['debug']) || isset($_GET['refresh']))
		init_session();
	if($rastermap->setToken($_GET['r'])) {
		if (isset($_GET['debug']))
			print $rastermap->getOSGBStorePath($rastermap->service,0,0,false);
		if (isset($_GET['refresh']) && $_GET['refresh'] == 2 && $USER->hasPerm('admin'))
			$rastermap->caching=false;
	
		$rastermap->returnImage();
	}
	exit;	
} elseif (isset($_GET['e']) && isset($_GET['n'])) {



	require_once('geograph/conversions.class.php');
	$conv = new Conversions();
	
	list($e,$n,$reference_index) = array(intval($_GET['e'])*1000,intval($_GET['n'])*1000,1);
	
	if ($reference_index == 1) {
		require_once('geograph/rastermap.class.php');
		$square = false;				
		$rastermap = new RasterMap($square);
		if (isset($_GET['debug']))
			init_session();
		
		$rastermap->service = 'OS50k-mapper2';
		$rastermap->nateastings = $e;
		$rastermap->natnorthings = $n;
		$rastermap->width = $rastermap->tilewidth[$rastermap->service];

		if ($_GET['l'] == 'o') {
		
			//we need to silently load the session
			customNoCacheHeader('',true);

			init_session();

			if (isset($_SESSION['maptt'])) {
				$tt = $_SESSION['maptt'];
			} elseif (!empty($_GET['tt'])) {
				$tt = new ThrottleToken($_GET['tt']);
			} else {
				customNoCacheHeader();       
				header("HTTP/1.0 307 Temporary Redirect");
				header("Status: 307 Temporary Redirect");
				if ($USER->registered) {
 	                               header("Location: /maps/validate.png");
 				} else {
					header("Location: /maps/login.png");
				}
				exit;
			}

			if (!($tt->useCredit())) {
				//run out of credit!

				customNoCacheHeader(); 
				header("HTTP/1.0 307 Temporary Redirect");
				header("Status: 307 Temporary Redirect");

                                if ($USER->registered) {
                                       header("Location: /maps/validate.png");
                                } else {
                                        header("Location: /maps/login.png");
                                }
 				exit;
			}
		
			if (isset($_GET['refresh']) && $_GET['refresh'] == 2 && $USER->hasPerm('admin'))
				$rastermap->caching=false;
	
			$rastermap->returnImage();
		} else {
			$mustgenerate = false;
			
			if ($valid->memcache) {
				$mkey = "{$_GET['l']},$e,$n,$reference_index";
				$lastmod =& $memcache->name_get('tl',$mkey);
				if (!$lastmod) {
					$lastmod = time();
					$mustgenerate = true;
				}
			} else {
				$lastmod = time();
				$mustgenerate = true;
			}
		
			customCacheControl($lastmod,"$e,$n,$reference_index");
			customExpiresHeader(86400,true);
				
			if ($valid->memcache && !$mustgenerate) {
				$data =& $memcache->name_get('td',$mkey);
				if ($data) {
					if ($data == 'blank') {
						header("HTTP/1.0 302 Found");
						header("Status: 302 Found");
						header("Location: /maps/blank.png");
					} else {
						header("Content-Type: image/png");
						print $data;
					}
					exit;
				}
			} 
			
			preg_match('/-(\d)k-/',$rastermap->folders[$rastermap->service],$m);
			$stepdist = ($m[1]-1);
		
			list($x,$y) = $conv->national_to_internal($e,$n,$reference_index);	
			
			$db=NewADOConnection($GLOBALS['DSN']);
			
			$scanleft=$x;
			$scanright=$x+$stepdist;
			$scanbottom=$y;
			$scantop=$y+$stepdist;

			$rectangle = "'POLYGON(($scanleft $scanbottom,$scanright $scanbottom,$scanright $scantop,$scanleft $scantop,$scanleft $scanbottom))'";
		
			
			$sql="select x,y,grid_reference,imagecount,percent_land,has_geographs from gridsquare where 
				CONTAINS( GeomFromText($rectangle),	point_xy)
				having imagecount>0 or percent_land = 0";
			
			$arr = $db->getAll($sql);
			
			
			if (count($arr)) {
				$w = $rastermap->tilewidth[$rastermap->service];
				$part = $w /8;
				$part2 = $w /4;
				$xd = imagefontwidth(5)/2;
				$yd = imagefontheight(5)/2;
				$s = imagefontwidth(5)*2.1;
				
				$img=imagecreate($w,$w);
				$colMarker=imagecolorallocate($img, 255,255,255);
				imagecolortransparent($img,$colMarker);
				
				$colSea=imagecolorallocate($img, 0,0,0);
				$colBack=imagecolorallocate($img, 0,0,240);
				$colSuppBack=imagecolorallocate($img, 192,158,0);
				
				foreach ($arr as $i => $row) {
					
					$x1 = $row['x'] - $x;
					$y1 = $stepdist - ($row['y'] - $y);
					
					$x2 = $part + ($x1 * $part2);
					$y2 = $part + ($y1 * $part2);
					
					if ($row['imagecount']) {
						$color = ($row['has_geographs'])?$colBack:$colSuppBack;	
						imagefilledellipse ($img,$x2,$y2,$s*strlen($row['imagecount']),$s,$color);

						imagestring($img, 5, $x2-2-$xd*strlen($row['imagecount'])/2, $y2-$yd, $row['imagecount'], $colMarker);	
					} 
					if (!$row['percent_land']) {
						imagestring($img, 5, $x2-2-$xd/2, $y2-$yd, 'X', $colSea);
					}
				}
				header("Content-Type: image/png");
				if ($memcache->valid) {
					ob_start();
					imagepng($img);
					$memcache->name_set('td',$mkey,ob_get_flush(),$memcache->compress,$memcache->period_long*4);
		;
				} else {
					imagepng($img);
				}

			} else {
				$blank = 'blank';
				$memcache->name_set('td',$mkey,$blank,false,$memcache->period_long*4);
				header("HTTP/1.0 302 Found");
				header("Status: 302 Found");
				header("Location: /maps/blank.png");
			}

			exit;
		} 

	} 
}

?>
