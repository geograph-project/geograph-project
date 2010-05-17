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
require_once('geograph/mapmosaic.class.php');
init_session();

$smarty = new GeographPage;



$template='statistics_not_geographed.tpl';
$cacheid='statistics|not_geographed';

if ($smarty->caching) {
	$smarty->caching = 2; // lifetime is per cache
	$smarty->cache_lifetime = 3600*24; //24hr cache
}

if (isset($_GET['refresh']) && $USER->hasPerm('admin'))
	$smarty->clear_cache($template, $cacheid);

$smarty->assign_by_ref('references_real',$CONF['references']);

if (!$smarty->is_cached($template, $cacheid))
{
	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');  
	#$db->debug = true;

	$mosaic=new GeographMapMosaic;
	$mosaic->setScale(40);
	$mosaic->setMosaicFactor(2);

	$mostarray = array();
	
	foreach ($CONF['references'] as $ri => $rname) {
		$letterlength = $CONF['gridpreflen'][$ri];
		
		$most = $db->GetAll("select 
		grid_reference,x,y,
		concat(substring(grid_reference,1,".($letterlength+1)."),substring(grid_reference,".($letterlength+3).",1)) as tenk_square,
		sum(has_geographs) as geograph_count,
		sum(percent_land >0) as land_count
		from gridsquare 
		where reference_index = $ri 
		group by tenk_square 
		having geograph_count = 0 and land_count > 0
		order by tenk_square");
		
		$i = 1;
		$lastgeographs = -1;
		foreach($most as $id=>$entry) 
		{
			$most[$id]['x'] = ( intval(($most[$id]['x'] - $CONF['origins'][$ri][0])/10)*10 ) +  $CONF['origins'][$ri][0];
			$most[$id]['y'] = ( intval(($most[$id]['y'] - $CONF['origins'][$ri][1])/10)*10 ) +  $CONF['origins'][$ri][1];

			//get a token to show a suroudding geograph map
			$mosaic->setOrigin($most[$id]['x'],$most[$id]['y']);

			$most[$id]['map_token'] = $mosaic->getToken();
		}	
		$mostarray += array($ri => $most);
	}
	$smarty->assign_by_ref("most", $mostarray);
}


$smarty->display($template, $cacheid);

	
?>
