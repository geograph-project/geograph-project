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
	if (isset($_GET['debug']))
		init_session();
	if($rastermap->setToken($_GET['r'])) {
		if (isset($_GET['debug']))
			print $rastermap->getOSGBStorePath($rastermap->service,0,0,false);
	
		$rastermap->returnImage();
	}
	exit;	
}
