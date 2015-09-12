<?php
/**
 * $Project: GeoGraph $
 * $Id: index.php 7816 2013-03-31 00:17:09Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2011 Rudi Winter (http://www.geograph.org.uk/profile/2520)
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


/*

  geotrips TODO list

  * make proper use of smarty templates
  * put logic in some geotrips class; would also reduce some code duplication
  * move css and icons to the usual locations
  * make db schema more geograph like?
  * rewrite rules
  * include in "content" (probably port content changes from gbi)
  * robots?
  * sitemap?
  * reduce track size?
  * allow user to add user id(s) of other contributors taking photos
    during the same trip which would also be shown
  * more links:
    - show page: link to edit page
    - edit page: links to show pages
    - index:     links to edit pages if matching uid
  * don't use column 'user' (better remove it?) as it gets inconsistent as soon as the user changes the real name
  * guarantee linear link graph
  * clear cache of neighbouring trips on change (old+new neighbour!)
  * don't load track for index page etc.
  * allow images with identical positions/without view direction
  * allow disconnected tracks: push current track after </rte> or </trk> (or </trkseg>?)
  * kml-export: images + track
  * German translation: solve "from $start" problem...

*/

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;

$max = isset($_GET['max']) ? intval($_GET['max']) : 10;
$days = isset($_GET['days']) ? intval($_GET['days']) : 7;

if ($max < 0) {
	$max = -1;
	$days = 0;
	$cacheid = 'trip|overview_full';
} else {
	$cacheid = 'trip|overview|index_'.$max.'_'.$days;
}

$template = 'geotrips_index.tpl';

if ($smarty->caching) {
	$smarty->caching = 2; // lifetime is per cache
	if ($max < 0) {
		$smarty->cache_lifetime = 3600*24; //24 hour cache
	} else {
		$smarty->cache_lifetime = 3600*12; //12 hour cache
	}
}

if (!$smarty->is_cached($template, $cacheid)) {
	require_once('geograph/gridimage.class.php');
	include('./geotrip_func.php');

	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');

	// get trips from database
	$trks=$db->getAll("select * from geotrips where location!='debug' order by id desc");

	$alltrips_shown = true;
	foreach ($trks as $i=>&$trip) {
		$bbox = explode(' ',$trip['bbox']);
		$trip['latlon'] = array(0.5*($bbox[0]+$bbox[2]), 0.5*($bbox[1]+$bbox[3]));
		$trip['nicetype'] = whichtype($trip['type']);
		$trip['gridimage'] = new GridImage($trip['img']);
		if (!$trip['gridimage']->isValid()) {
			//FIXME error handling
		}
		if ($trip['contfrom']) {
			$prevbbox=$db->getRow("select bbox from geotrips where id={$trip['contfrom']}"); // FIXME use getAssoc to get trks und get bounding box from there?
			# FIXME error handling
			$prevbbox=explode(' ',$prevbbox['bbox']);
			$trip['prevlatlon'] = array(0.5*($prevbbox[0]+$prevbbox[2]), 0.5*($prevbbox[1]+$prevbbox[3]));
		}
		$trip['visible'] = $max < 0 || $i < $max || strtotime($trip['updated']) > date('U')-$days*86400; // show all uploaded in last $days*24 hours, but at least $max
		if ($trip['visible']){
			$trip['grid_reference'] = bbox2gr($trip['bbox']);
		} else {
			$alltrips_shown = false;
		}
	}
	unset($trip);

	$smarty->assign('max', $max);
	$smarty->assign('alltrips', $alltrips_shown);
	$smarty->assign('days', $days);
	$smarty->assign('lat0', $CONF['gmcentre'][0]);
	$smarty->assign('lon0', $CONF['gmcentre'][1]);
	$smarty->assign('lonmin', $CONF['gmlonrange'][0][0]);
	$smarty->assign('lonmax', $CONF['gmlonrange'][0][1]);
	$smarty->assign('latmin', $CONF['gmlatrange'][0][0]);
	$smarty->assign('latmax', $CONF['gmlatrange'][0][1]);
	$smarty->assign_by_ref('trips', $trks);
}

$smarty->display($template, $cacheid);

?>
