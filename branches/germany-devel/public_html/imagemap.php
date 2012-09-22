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
		}
	
			//force render of this map 
			//$map->_renderRandomGeographMap();
				//now done with type_or_user = -1
	
	if (count($_GET))
		$map->returnImage();
	exit;


	
?>
