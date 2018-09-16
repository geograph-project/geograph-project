<?php
/**
 * $Project: GeoGraph $
 * $Id: imagemap.php 8688 2017-12-16 15:39:01Z hansjorg $
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
require_once('geograph/gridimage.class.php');
require_once('geograph/map.class.php');
require_once('geograph/mapmosaic.class.php');
require_once('geograph/image.inc.php');
init_session();



	$map=new GeographMap;
	
	if (isset($_GET['key'])) 
		$map->_outputDepthKey();
	
	if (isset($_GET['refresh']) && $_GET['refresh'] == 2 && $USER->hasPerm('admin'))
		$map->caching=false;
	

		$map->setOrigin(0,-10);
		$map->setImageSize(1200/2,1700/2);
		$map->setScale(1.3/2);

		$year = !empty($_GET['year'])?intval($_GET['year']):date('Y');

		if ((!isset($_GET['year']) || !empty($_GET['year'])) && $year >= 2004 && $year <= date('Y')) {
			$map->type_or_user = -1 * $year;
		} elseif (isset($_GET['depth'])) {
			$map->setOrigin(0,-10);
			$map->setImageSize(900,1300);
			$map->setScale(1);
			
			unset($CONF['enable_newmap']);
			
			$map->type_or_user = -1;
		} elseif (isset($_GET['number'])) {
			$map->setOrigin(0,-10);
			$map->setImageSize(900,1300);
			$map->setScale(1);
			
			$map->minimum = intval($_GET['number']);
			
			unset($CONF['enable_newmap']);
		} elseif (isset($_GET['plain'])) {
			$map->setOrigin(0,-10);
			$map->setImageSize(900,1300);
			$map->setScale(1);
			
		} elseif (isset($_GET['big'])) {
			$map->setOrigin(0,-10);
			$map->setImageSize(1200,1700);
			$map->setScale(1.3);
			
			$map->type_or_user = -10;
		} elseif (isset($_GET['date'])) {
			$map->setOrigin(0,-10);
			$map->setImageSize(900,1300);
			$map->setScale(1);
			
			$map->mapDateStart = "2005-06-07";
			$map->mapDateCrit = "2005-06-01";
			
			$map->type_or_user = -1;
		} elseif (isset($_GET['years']) && $USER->hasPerm("admin")) {
			$map->setOrigin(0,-10);
			$map->setImageSize(900,1300);
			$map->setScale(1);
			$map->type_or_user = -2;
			
			set_time_limit(3600*3);
			
			$root=&$_SERVER['DOCUMENT_ROOT'];
			$n = time()-(60*60*24*7);
			for($t=2000; $t<2009; $t++) {
				foreach (range(1,12) as $m) {
					$map->displayYear = sprintf("%04d-%02d",$t,$m);

					$target=$map->getImageFilename();

					if (!file_exists($root.$target)) {
						$map->_renderMap();	
					}
					print "{$map->displayYear} DONE<BR>";flush();
				}
			}
			exit;
		} elseif (isset($_GET['dates']) && $USER->hasPerm("admin")) {
			$map->setOrigin(0,-10);
			$map->setImageSize(900,1300);
			$map->setScale(1);
			$map->type_or_user = ($_GET['dates'] == -2)?-2:-1;
			
			set_time_limit(3600*3);
			
			$root=&$_SERVER['DOCUMENT_ROOT'];
			$n = time()-(60*60*24*7);
			for($t=strtotime("10 March 2005"); $t<$n; $t+=(60*60*24*7) ) {
				$map->mapDateStart = date('Y-m-d',$t);
				$map->mapDateCrit = date('Y-m-d',$t-(60*60*24*7));
			
				$target=$map->getImageFilename();
				
				if (!file_exists($root.$target)) {
					$map->_renderMap();	
				}
				print "{$map->mapDateStart} DONE<BR>";flush();
			}
			exit;
		} elseif (isset($_GET['dedates']) && $USER->hasPerm("admin")) {
			# http://geo.hlipp.de/imagemap.php?dedates=1&refresh=2&year=0
			# http://geo.hlipp.de/imagemap.php?dedates=1&refresh=2&year=0&Z=7
			# http://geo.hlipp.de/imagemap.php?dedates=1&refresh=2&year=0&step=week
			# http://geo.hlipp.de/imagemap.php?dedates=1&refresh=2&year=0&t=-1
			# test:             ffmpeg -i map_ddate_z7_l7_o0_t0.gif -movflags faststart -pix_fmt yuv420p  ~/geo/map_ddate_z7_l7_o0_t0.mp4
			# better but large: ffmpeg -i map_ddate_z7_l7_o0_t0_w.gif -c:v libx264 -preset veryslow -qp 0 map_ddate_z7_l7_o0_t0_w.mp4
			# seems okay:       ffmpeg -i map_ddate_z7_l7_o0_t0_w.gif -c:v libx264 -preset veryslow -qp 0 -pix_fmt yuv420p -movflags faststart map_ddate_z7_l7_o0_t0_w.yuv420p.mp4
			$zoom = intval($_GET['Z']);
			if ($zoom == 7) {
				$xmin = 66;
				$xmax = 69;
				$ymin = 40;
				$ymax = 44;
				$marginL = 0;
				$marginR = 144;
				$marginT = 80;
				$marginB = 0;
			} else { /* default: zoom level 6 */
				$zoom = 6;
				$xmin = 33;
				$xmax = 34;
				$ymin = 20;
				$ymax = 22;
				$marginL = 0;
				$marginR = 72;
				$marginT = 48;
				$marginB = 130;
			}
			$layers = intval($_GET['l']);
			if ($layers < 1 || $layers > 31) { /* default: base + data layer + regions */
				$layers = 7;
			}
			$layers |= 2;
			$overlay = intval($_GET['o']);
			if ($overlay < 0 || $overlay > 2) {
				$overlay = 0;
			}
			$typeuser = intval($_GET['t']);
			if ($typeuser < -1 || $typeuser > 0) { /* default: coverage */
				$typeuser = 0;
				/* could also do per user, would need to adjust _createSquaremapM in map.class.php */
			}
			$gifsuffix = '';
			# image 1: 2008-07-09 (Wed)
			if ($_GET['step'] == 'week') {
				$weekly = true;
				#$t = strtotime('2008-07-07'); // monday before image 1
				$t = mktime(12, 0, 0, 7, 7, 2008); # try noon, reducing possible error sources (time jumps from monday to sunday for some reason...)
				                                   #                             ^^^^^^^^^^^^^ (such as time zones, dst, leap seconds, ...)
				                                   # this is only for the loop, we actually only use the date part for queries and file names.
				$defdelay = 20;
				$gifsuffix .= '_w';
			} else { /* default: month */
				$weekly = false;
				$year = 2008;
				$month = 7;
				#$t = strtotime('2008-07-01');
				$t = mktime(12, 0, 0, $month, 1, $year);
				$defdelay = 40;
			}
			$delay = intval($_GET['d']);
			if ($delay <= 0 || $delay >= 1000) {
				$delay = $defdelay;
			} else {
				$gifsuffix .= "_d$delay";
			}

			// have to add 12 hours as we also add 12 hours to the actual time to avoid DST issues
			//$n = time()-(60*60*24*3) + (60*60*12); /* three days for the moderators;-) */
			$n = time()- (60*60*16) + (60*60*12); /* 16 hours for the admin to moderate;-) */

			$w = 256;
			$h = 256;

			$map->enableMercator(true);
			$map->setOrigin($xmin, $ymin);
			$map->setImageSize($w,$h);
			$map->setScale($zoom);
			$map->type_or_user = $typeuser;
			$map->overlay = $overlay;
			$map->layers = $layers;
			$map->caching_squaremap = true;
			$map->caching = true;

			$w_comb = $w * ($xmax - $xmin + 1);
			$h_comb = $h * ($ymax - $ymin + 1);
			$w_dest = $w_comb - $marginL - $marginR;
			$h_dest = $h_comb - $marginT - $marginB;

			set_time_limit(3600*3);

			$frames = array();
			$root=&$_SERVER['DOCUMENT_ROOT'];
			@mkdir($root."/maps/special");
			while ($t < $n) {
				/* render tiles */
				#$map->displayYear = sprintf("%04d-%02d",$t,$m); # FIXME
				$map->mapDateStart = date('Y-m-d',$t); #FIXME
				$map->mapDateCrit = date('Y-m-d',$t); #FIXME
				$destname = $root."/maps/special/map_ddate_z{$zoom}_l{$layers}_o{$overlay}_t{$typeuser}_d{$map->mapDateStart}.png";
				if (!file_exists($destname)) { /* skip if combined map already exists */
					$tiles = array();
					for ($y = $ymin; $y <= $ymax; ++$y) {
						for ($x = $xmin; $x <= $xmax; ++$x) {
							$map->setOrigin($x, $y);
							$target=$map->getImageFilename();
							$tiles[] = array($x - $xmin, $y - $ymin, $root.$target);
							if (!file_exists($root.$target)) {
								$map->_renderMap();	
							}
						}
					}
					/* combine tiles */
					$imgcomb = imagecreatetruecolor($w_comb, $h_comb);
					foreach ($tiles as $tile) {
						$tmpimg = imagecreatefrompng($tile[2]);
						imagecopy($imgcomb, $tmpimg, $tile[0]*$w, $tile[1]*$h, 0, 0, $w, $h);
						imagedestroy($tmpimg);
					}
					/* remove margin */
					$img = imagecreatetruecolor($w_dest, $h_dest);
					imagecopy($img, $imgcomb, 0, 0, $marginL, $marginT, $w_dest, $h_dest);
					imagedestroy($imgcomb);
					$black = imagecolorallocate ($img, 70, 70, 0);
					imagestring($img, 5, 3, $h_dest-15, $map->mapDateStart, $black);
					imagepng($img, $destname);
					imagedestroy($img);
				}
				$frames[] = $destname;
				print "{$map->mapDateStart} DONE<BR>";flush();
				if ($weekly) {
					$t += (60*60*24*7);
					# for whatever reason we get
					# ... many mondays ...
					# 2008-10-20 DONE
					# 2008-10-26 DONE
					# ... many sundays ...
				} else {
					if ($month == 12) {
						$month = 1;
						$year++;
					} else {
						$month++;
					}
					$t = mktime(12, 0, 0, $month, 1, $year);
				}
			}
			/* create animated gif */
			if ($CONF['imagemagick_path'] !== '' && count($frames)) {
				$gifname = $root."/maps/special/map_ddate_z{$zoom}_l{$layers}_o{$overlay}_t{$typeuser}{$gifsuffix}.gif";
				if (isset($CONF['imagemagick_tmpdir']) && $CONF['imagemagick_tmpdir'] !== '') {
					$imenv = "MAGICK_TEMPORARY_PATH=\"{$CONF['imagemagick_tmpdir']}\" ";
				} else {
					$imenv = '';
				}
				$cmd = "$imenv\"{$CONF['imagemagick_path']}\"convert -delay $delay ".implode(' ', $frames)." -loop 0 {$gifname}";
				passthru($cmd);
			}
			exit;
		}
	
			//force render of this map 
			//$map->_renderRandomGeographMap();
				//now done with type_or_user = -1
	
	if (count($_GET))
		$map->returnImage();
	exit;


	
?>
