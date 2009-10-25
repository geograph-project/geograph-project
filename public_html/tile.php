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

/**************************
* Coverage Maps
*/
if (isset($_GET['map']))
{
	require_once('geograph/map.class.php');
	require_once('geograph/mapmosaic.class.php');
	require_once('geograph/gridimage.class.php');
					
	//render and return a map with token $_GET['map'];
	$map=new GeographMap;
	if (isset($_GET['refresh']) && $_GET['refresh'] == 2 && (init_session() || true) && $USER->hasPerm('admin'))
		$map->caching=false;
	if($map->setToken($_GET['map']))
		$map->returnImage();
	exit;

/**************************
* Raster Maps
*/	
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

/**************************
* Mapper Tiles
*/
} elseif (isset($_GET['e']) && isset($_GET['n'])) {

	require_once('geograph/conversions.class.php');
	$conv = new Conversions();
	
	/**************************
	* Debug Tiles
	*/
	if (false) {
		###############

		$w = 250 ; 
		$img=imagecreate($w,$w);
		imagecolorallocate($img, 0,0,0);
		$colMarker=imagecolorallocate($img, 255,255,255);

		imagerectangle($img, 1, 1, $w, $w, $colMarker);

		imagestring($img, 1, 2, 2, $_GET['b'], $colMarker);

		$b = explode(',',$_GET['b']);

		$s = intval( ($b[2]-$b[0])/1000 );

		imagestring($img, 1, 20, 20, "$s km", $colMarker);
		
		imagestring($img, 1, 50, 50, "z=".$_GET['z'], $colMarker);

		header("Content-Type: image/png");
		imagepng($img); 
		exit;

		###############
	}
	
	list($e,$n,$reference_index) = array(intval($_GET['e'])*1000,intval($_GET['n'])*1000,1);
	
	/**************************
	* Great Britain Only
	*/
	if ($reference_index == 1) {
		require_once('geograph/rastermap.class.php');
		$square = false;
		$rastermap = new RasterMap($square);
		if (isset($_GET['debug']))
			init_session();
		
		if (isset($_GET['z'])) {
			switch($_GET['z']) {
				case 0: $rastermap->service = 'OS250k-m40k'; break;
				case 1: $rastermap->service = 'OS250k-m10k'; break;
				case 2: $rastermap->service = 'OS50k-mapper2'; break;#4k
				case 3: $rastermap->service = 'OS50k-mapper3'; break;#2k
				default: die("invalid zoom");
			} 
		} else {
			//legacy support for no zoom specified
			$rastermap->service = 'OS50k-mapper2';
		} 

		$rastermap->nateastings = $e;
		$rastermap->natnorthings = $n;
		$rastermap->width = $rastermap->tilewidth[$rastermap->service];

		/**************************
		* OS Map Tiles - handled by RasterMap class
		*/
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
					header("Location: http://{$CONF['CONTENT_HOST']}/maps/validate.png");
				} else {
					header("Location: http://{$CONF['CONTENT_HOST']}/maps/login.png");
				}
				exit;
			}

			if (!($tt->useCredit())) {
				//run out of credit!

				customNoCacheHeader(); 
				header("HTTP/1.0 307 Temporary Redirect");
				header("Status: 307 Temporary Redirect");

				if ($USER->registered) {
					header("Location: http://{$CONF['CONTENT_HOST']}/maps/validate.png");
				} else {
					header("Location: http://{$CONF['CONTENT_HOST']}/maps/login.png");
				}
 				exit;
			}
		
			if (isset($_GET['refresh']) && $_GET['refresh'] == 2 && $USER->hasPerm('admin'))
				$rastermap->caching=false;
	
			$rastermap->returnImage();
		
		/**************************
		* Coverage Tiles
		*/
		} else {
			preg_match('/-(\d+)k-/',$rastermap->folders[$rastermap->service],$m);
			$stepdist = ($m[1]-1);
			$widthdist = ($m[1]);

			if ($_GET['l'] == 'p' && $widthdist > 4) {
				//centisquare map not supported at this zoom level
				customNoCacheHeader();
				header("HTTP/1.0 302 Found");
				header("Status: 302 Found");
				header("Location: http://{$CONF['STATIC_HOST']}/img/blank.gif");
				exit;
			}

			/////////////////////////
			//check if we have a cached tile
			$mustgenerate = false;
			
			if ($memcache->valid && !isset($_GET['refresh'])) {
				$mkey = "{$_GET['l']}:$e,$n,$reference_index,$widthdist";
				$lastmod =& $memcache->name_get('tl',$mkey);
				if (!$lastmod) {
					$lastmod = time();
					$mustgenerate = true;
				}
			} else {
				$lastmod = time();
			}
		
			customCacheControl($lastmod,"$e,$n,$reference_index");
			customExpiresHeader(86400,true);
			
			if ($memcache->valid && $mkey && !$mustgenerate) {
				$data =& $memcache->name_get('td',$mkey);
				if ($data) {
					if ($data == 'blank') {
						header("HTTP/1.0 302 Found");
						header("Status: 302 Found");
						header("Location: http://{$CONF['CONTENT_HOST']}/maps/blank.png");
					} else {
						header("Content-Type: image/png");
						print $data;
					}
					exit;
				}
			} 
			
			/////////////////////////
			// no hit from the cache....
			
			$lastmod = time();
			
			
			$w = $rastermap->tilewidth[$rastermap->service];
			
			list($x,$y) = $conv->national_to_internal($e,$n,$reference_index);
			
			$db=NewADOConnection($GLOBALS['DSN']);
			
			$scanleft=$x;
			$scanright=$x+$stepdist;
			$scanbottom=$y;
			$scantop=$y+$stepdist;

			$rectangle = "'POLYGON(($scanleft $scanbottom,$scanright $scanbottom,$scanright $scantop,$scanleft $scantop,$scanleft $scanbottom))'";
		
			if ($_GET['l'] == 'p') {
				$sql="select (nateastings DIV 100 * 100) AS nateastings,
					(natnorthings DIV 100 * 100) AS natnorthings,
					count(*) as imagecount 
					from gridimage inner join gridsquare using (gridsquare_id) where 
					CONTAINS( GeomFromText($rectangle),	point_xy)
					and moderation_status = 'geograph' and natgrlen <= 3
					group by nateastings DIV 100, natnorthings DIV 100";
			} else {
				$sql="select x,y,imagecount,percent_land,has_geographs from gridsquare where 
					CONTAINS( GeomFromText($rectangle),	point_xy)";
			}
			
			/////////////////////////
			// fetch from database
			$arr = $db->getAll($sql);
			
			
			if (count($arr)) {
				/**************************
				* Centi Square Depth Tiles
				*/
				if ($_GET['l'] == 'p') {
					$pixels_per_centi = ($w / ($widthdist * 10) ); //10 as ten centis per km
					$half = ($pixels_per_centi/2);
					
					$img=imagecreate($w,$w);
					$colMarker=imagecolorallocate($img, 255,255,255);
					imagecolortransparent($img,$colMarker);
					$colSea=imagecolorallocate($img, 0,0,0);
					
					$colour = getColorKey($img);
					$k = array_keys($colour);
					$lastcolour = $colour[$k[count($k)-1]];
					$k = null;
					
					foreach ($arr as $i => $row) {
						$x1 = (($row['nateastings'] - $e) / 100);
						$y1 = (($row['natnorthings'] - $n) / 100);

						//+$half as in coords needed are the center
						$x2 = intval(($x1 * $pixels_per_centi)+$half);
						$y2 = $w - intval(($y1 * $pixels_per_centi)+$half);

						$color = $colour[$row['imagecount']];
						imagefilledellipse($img,$x2,$y2,$pixels_per_centi,$pixels_per_centi,$color);
						
						imageellipse($img,$x2,$y2,$pixels_per_centi,$pixels_per_centi,$lastcolour);
					}
					imagesavealpha($img, true);

				/**************************
				* GridSquare Depth Tiles
				*/
				} else {
					$part = $w /$widthdist /2;
					$part2 = $w /$widthdist;

					$img=imagecreate($w,$w);
					$colMarker=imagecolorallocate($img, 255,255,255);
					imagecolortransparent($img,$colMarker);
					
					$ff = array(
						1 => array('f'=> 5,	's' => imagefontwidth(5)*2.1,	'xd' => imagefontwidth(5)/2,	'yd' => imagefontheight(5)/2),
						2 => array('f'=> 4,	's' => imagefontwidth(4)*3.1,	'xd' => imagefontwidth(4),	'yd' => 3+imagefontheight(4)/4),
						3 => array('f'=> 3,	's' => imagefontwidth(3)*4.1,	'xd' => imagefontwidth(3)*1.5,	'yd' => imagefontheight(3)/2),
						4 => array('f'=> 1,	's' => imagefontwidth(1)*5.1,	'xd' => imagefontwidth(1)*2,	'yd' => imagefontheight(1)/2),
					);
					$h = imagefontheight(5);
					
					$colSea=imagecolorallocate($img, 0,0,0);
					$colGreen=imagecolorallocate($img, 117,255,101);
					$colBack=imagecolorallocate($img, 0,0,240);
					$colSuppBack=imagecolorallocate($img, 192,158,0);
					
					if ($widthdist > 4) {
						$colour = getColorKey($img);
					}
					foreach ($arr as $i => $row) {

						$x1 = $row['x'] - $x;
						$y1 = $stepdist - ($row['y'] - $y);

						$x2 = $part + ($x1 * $part2);
						$y2 = $part + ($y1 * $part2);
						
						if ($row['imagecount']) {
							$l = strlen($row['imagecount']);
							if ($widthdist > 4) {
								$color = $colour[$row['imagecount']];
							} else {
								$color = ($row['has_geographs'])?$colBack:$colSuppBack;	
							}
							if ($widthdist > 10) {
								imagefilledellipse ($img,$x2,$y2,5,5,$color);
							} else {
								imagefilledellipse ($img,$x2,$y2,$ff[$l]['s'],$h,$color);
				
								imagestring($img, $ff[$l]['f'], $x2-$ff[$l]['xd'], $y2-$ff[$l]['yd'], $row['imagecount'], $colMarker);	
							}
						} elseif ($row['percent_land']) {
							if ($widthdist > 10) {
								imagefilledellipse($img,$x2,$y2,5,5,$colGreen);
							} else {
								#imagestring($img, 5, $x2-2-$xd/2, $y2-$yd, 'O', $colGreen);
								imagefilledellipse($img,$x2,$y2,10,10,$colGreen);
							}
						}
						if (!$row['percent_land'] && !($widthdist > 10)) {
							$l = 1;
							imagestring($img, $ff[$l]['f'], $x2-$ff[$l]['xd'], $y2-$ff[$l]['yd'], 'X', $colSea);
						}
					}
				}
				header("Content-Type: image/png");
				if ($memcache->valid) {
					ob_start();
					imagepng($img);
					$memcache->name_set('td',$mkey,ob_get_flush(),$memcache->compress,$memcache->period_med*2);
					$memcache->name_set('tl',$mkey,$lastmod,$memcache->compress,$memcache->period_med*2);
				} else {
					imagepng($img);
				}

			} else {
				$blank = 'blank';
				$memcache->name_set('td',$mkey,$blank,false,$memcache->period_med*2);
				$memcache->name_set('tl',$mkey,$lastmod,$memcache->compress,$memcache->period_med*2);
				header("HTTP/1.0 302 Found");
				header("Status: 302 Found");
				header("Location: http://{$CONF['CONTENT_HOST']}/maps/blank.png");
			}

			exit;
		} 

	} 
} else {
	die("no action specified");
}


////////////////////////////////////

function getColorKey(&$img) {
	global $db;
	
	$sql="select imagecount from gridsquare group by imagecount";
	$counts = $db->cacheGetCol(3600,$sql);

	$colour=array();
	$last=$lastcolour=null;
	for ($p=1; $p<count($counts); $p++) {
		$o = $counts[$p];
		//standard green, yellow => red
		switch (true) {
			//case $o == 1: $r=255; $g=255; $b=0; break; 
			//case $o == 2: $r=255; $g=196; $b=0; break; 
			//case $o == 3: $r=255; $g=132; $b=0; break; 
			case $o == 1: $r=255; $g=196; $b=0; break; 
			case $o == 2: $r=255; $g=154; $b=0; break; 
			case $o == 3: $r=255; $g=110; $b=0; break; 
			case $o == 4: $r=255; $g=64; $b=0; break; 
			case $o <  7: $r=225; $g=0; $b=0; break; #5-6
			case $o < 10: $r=200; $g=0; $b=0; break; #7-9
			case $o < 20: $r=168; $g=0; $b=0; break; #10-19
			case $o < 40: $r=136; $g=0; $b=0; break; #20-39
			case $o < 80: $r=112; $g=0; $b=0; break; #40-79
			default: $r=80; $g=0; $b=0; break;
		}
		$key = "$r,$g,$b";
		if ($key == $last) {
			$colour[$o] = $lastcolour;
		} else {
			$lastcolour = $colour[$o]=imagecolorallocate($img, $r,$g,$b);
		}
		$last = $key;
	}
	return $colour;
}

?>
