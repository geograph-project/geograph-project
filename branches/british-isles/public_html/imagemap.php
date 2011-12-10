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
	
		//standard 1px national map
		$map->setOrigin(0,-10);
		$map->setImageSize(900,1300);
		$map->setScale(1);

		if (isset($_GET['big'])) {
			$map->setOrigin(0,-10);
			$map->setImageSize(1200,1700);
			$map->setScale(1.3);
		}

		$year = !empty($_GET['year'])?intval($_GET['year']):date('Y');

	#XMAS map for a year
		if ((!isset($_GET['year']) || !empty($_GET['year'])) && $year >= 2004 && $year <= date('Y')) {
			$map->setOrigin(0,-10);
			$map->setImageSize(1200/2,1700/2);
			$map->setScale(1.3/2);
		
			$map->type_or_user = -1 * $year;

	#DEPTH MAP			
		} elseif (isset($_GET['depth'])) {
			
			unset($CONF['enable_newmap']); //hide placenames
			
			$map->type_or_user = -1;

	#CENTISQUARE DEPTH  MAP
		} elseif (isset($_GET['centi'])) {
			
			$map->type_or_user = -8;
			
			unset($CONF['enable_newmap']); //hide placenames

        #PHOTO AGE MAP
                } elseif (isset($_GET['age'])) {

                        $map->type_or_user = -7;

                        unset($CONF['enable_newmap']); //hide placenames

	#CENTISQUARE DEPTH  MAP
		} elseif (isset($_GET['userdepth'])) {
			
			$map->type_or_user = -13;
			
			unset($CONF['enable_newmap']); //hide placenames
	
	#NUMOBER OF GROUPINGS
		} elseif (isset($_GET['groups'])) {
			
			unset($CONF['enable_newmap']); //hide placenames
			
			$map->type_or_user = -3;
	
	#NUMBER OF LAND MAP FIXES
		} elseif (isset($_GET['fixes'])) {
			
			unset($CONF['enable_newmap']); //hide placenames
			
			$map->type_or_user = -4;
	
	#NEEDS AT LEAST X IMAGES TO MARK READ
		} elseif (isset($_GET['number'])) {
			
			$map->minimum = intval($_GET['number']);
			
			unset($CONF['enable_newmap']); //hide placenames
	
	#COLOURED BY VISITS
		} elseif (isset($_GET['hits'])) {
			
			$map->type_or_user = -5;

			print $map->getToken();
			exit;			
	
	#BOG STANDARD RED/GREEN MAP
		} elseif (isset($_GET['plain'])) {
	
	#ONLY MARKS SQUARES WITH PHOTOS IN LAST X DAYS
		} elseif (isset($_GET['since'])) {
			
			$map->numberOfDays = $_GET['since'];
			$map->type_or_user = -2;
			
	#RECENT ONLY MAP
		} elseif (isset($_GET['recent'])) {
			
			$map->type_or_user = -6;
			
	#EXTEA BIG MAP
		} elseif (isset($_GET['big'])) {
			$map->setOrigin(0,-10);
			$map->setImageSize(1200,1700);
			$map->setScale(1.3);
			
			$map->type_or_user = -10;
			
	#EXAMPLE DATE MAP
		} elseif (isset($_GET['date'])) {
			
			$map->mapDateStart = "2005-06-07";
			$map->mapDateCrit = "2005-06-01";
			
			$map->type_or_user = -1;
		
	#COVERAGE OVERLAYS BY MONTH
		} elseif (isset($_GET['months']) && $USER->hasPerm("admin")) {

			$map->type_or_user = -2;
			
			set_time_limit(3600*3);
			
			$root=&$_SERVER['DOCUMENT_ROOT'];
			$n = time()-(60*60*24*7);
			for($t=date('Y'); $t>=2000; $t--) {
				foreach (range(1,12) as $m) {
					$map->displayYear = sprintf("%04d-%02d",$t,$m);

					$target=$map->getImageFilename();

					if (!file_exists($root.$target)) {
						$map->_renderMap();	
					}
					print "{$map->displayYear} DONE<BR>";flush();
				}
			}
			print "All DONE";
			exit;
			
	#COVERAGE OVERLAYS BY YEAR
		} elseif (isset($_GET['years']) && $USER->hasPerm("admin")) {

			$map->type_or_user = -2;
			
			set_time_limit(3600*3);
			
			$root=&$_SERVER['DOCUMENT_ROOT'];
			$n = time()-(60*60*24*7);
			for($t=date('Y'); $t>=1900; $t--) {
				$map->displayYear = sprintf("%04d",$t);

				$target=$map->getImageFilename();

				if (!file_exists($root.$target)) {
					$map->_renderMap();	
				}
				print "{$map->displayYear} DONE<BR>";flush();
			}
			print "All DONE";
			exit;
			
	#MULITPLE "ONLY MARKS SQUARES WITH PHOTOS IN LAST X DAYS" MAPS
		} elseif (isset($_GET['days']) && $USER->hasPerm("admin")) {

			$map->type_or_user = -2;
			
			set_time_limit(3600*3);
			
			$root=&$_SERVER['DOCUMENT_ROOT'];
			$n = time()-(60*60*24*7);
			for($t=0; $t<=2400; $t+=30) {
				$map->numberOfDays = $t;

				$target=$map->getImageFilename();

				if (!file_exists($root.$target)) {
					$map->_renderMap();	
					print "{$map->numberOfDays} DONE<BR>";flush();
				}
			}
			print "All DONE";
			exit;
			
	#COVERAGE ANIMATION BUILDUP TILES
		} elseif (isset($_GET['dates']) && $USER->hasPerm("admin")) {

			$map->type_or_user = ($_GET['dates'] == -2)?-2:-1;
			
			set_time_limit(3600*3);
			
			$root=&$_SERVER['DOCUMENT_ROOT'];
			$n = time()-(60*60*24*7);
			for($t=strtotime("2008-12-11"); $t<$n; $t+=(60*60*24*7) ) {
				$map->mapDateStart = date('Y-m-d',$t);
				$map->mapDateCrit = date('Y-m-d',$t-(60*60*24*7));
			
				$target=$map->getImageFilename();
				
				if (!file_exists($root.$target)) {
					$map->_renderMap();	
					print "{$map->mapDateStart} DONE<BR>";flush();
				}
			}
			print "All DONE";
			exit;
		}
	
	
		if (!empty($_GET['token'])) {
			print_r($map);
			print "filename: ";
			print $map->getBaseMapFilename();
			print "URL: ";
			print $map->getImageUrl();exit;
		}
		if (count($_GET))
			$map->returnImage();
		exit;


