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
	
	$map->enableCaching($CONF['smarty_caching']);


		$map->setOrigin(0,-10);
		$map->setImageSize(1200/2,1700/2);
		$map->setScale(1.3/2);
		
		if ($_GET['year'] == '2005') {
			$map->type_or_user = -2005;
		} elseif ($_GET['year'] == '2004') {
			$map->type_or_user = -2004;
		} else {
			$map->type_or_user = -1;
		}
	
			//force render of this map 
			//$map->_renderRandomGeographMap();
				//now done with type_or_user = -1
	
	$map->returnImage();
	exit;


	
?>
