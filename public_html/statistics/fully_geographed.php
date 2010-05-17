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
require_once('geograph/gridsquare.class.php');
init_session();

$smarty = new GeographPage;

$template='statistics_fully_geographed.tpl';
$cacheid='statistics|fully_geographed';

if ($smarty->caching) {
	$smarty->caching = 2; // lifetime is per cache
	$smarty->cache_lifetime = 3600*6; //6hr cache
}

$smarty->assign_by_ref('references_real',$CONF['references']);

if (!$smarty->is_cached($template, $cacheid))
{
	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');  
	#$db->debug = true;

	$mosaic=new GeographMapMosaic;
	$mosaic->setScale(40);
	$mosaic->setMosaicFactor(2);

	$largemosaic=new GeographMapMosaic;
	$largemosaic->setScale(80);
	$largemosaic->setMosaicFactor(2);
	$largemosaic->setMosaicSize(800,800);
	
	$censquare = new GridSquare;
	
	function cmp($a,$b) {
		if ($a['sort'] == $b['dateraw'])
			return 0;
		return ($a['dateraw'] > $b['dateraw'])?-1:1;
	}

	$db->Execute("TRUNCATE hectad_complete");

	$mostarray = array();

	foreach ($CONF['references'] as $ri => $rname) {
		$letterlength = $CONF['gridpreflen'][$ri];
		
		$prev_fetch_mode = $ADODB_FETCH_MODE;
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;	
		$most = $db->GetAll("select 
		grid_reference,x,y,
		concat(substring(grid_reference,1,".($letterlength+1)."),substring(grid_reference,".($letterlength+3).",1)) as tenk_square,
		sum(has_geographs) as geograph_count,
		sum(percent_land >0) as land_count,
		(sum(has_geographs) * 100 / sum(percent_land >0)) as percentage
		from gridsquare 
		where reference_index = $ri 
		group by tenk_square 
		having geograph_count > 0 and percentage >=100
		order by percentage desc,tenk_square");
		$ADODB_FETCH_MODE = $prev_fetch_mode;
		
		$lastgeographs = -1;
		foreach($most as $id=>$entry) 
		{
			$most[$id]['x'] = ( intval(($most[$id]['x'] - $CONF['origins'][$ri][0])/10)*10 ) +  $CONF['origins'][$ri][0];
			$most[$id]['y'] = ( intval(($most[$id]['y'] - $CONF['origins'][$ri][1])/10)*10 ) +  $CONF['origins'][$ri][1];

			//get a token to show a suroudding geograph map
			$mosaic->setOrigin($most[$id]['x'],$most[$id]['y']);

			$most[$id]['map_token'] = $mosaic->getToken();
			
			//get a token to show a suroudding geograph map
			$largemosaic->setOrigin($most[$id]['x'],$most[$id]['y']);

			$most[$id]['largemap_token'] = $largemosaic->getToken();

			//actully we don't need the full loading of a square
			//$ok = $censquare->loadFromPosition($most[$id]['x'],$most[$id]['y']);
			$censquare->x = $most[$id]['x'];
			$censquare->y = $most[$id]['y'];

			$crit = substr($most[$id]['tenk_square'],0,$letterlength+1).'_'.substr($most[$id]['tenk_square'],$letterlength+1,1).'_';

			list($most[$id]['date'],$most[$id]['dateraw']) = $db->getRow(
			"SELECT DATE_FORMAT(MAX(submitted),'%D %b %Y'),MAX(submitted)
			FROM gridimage_search
			WHERE grid_reference LIKE '$crit' AND moderation_status = 'geograph' AND ftf = 1");
			
			$db->Execute(sprintf("INSERT IGNORE INTO hectad_complete SET
				hectad_ref = %s,
				completed = %s,
				landcount = %d,
				reference_index = %d,
				largemap_token = %s",
				$db->Quote($most[$id]['tenk_square']),
				$db->Quote($most[$id]['dateraw']),
				intval($most[$id]['land_count']),
				intval($ri),
				$db->Quote($most[$id]['largemap_token']) ) );
			
			
		}	
		
		uasort($most,"cmp");
		
		$mostarray += array($ri => $most);
	}

	$smarty->assign_by_ref("most", $mostarray);

	$db->Execute("ALTER TABLE `hectad_complete` ORDER BY `completed` DESC");

}


$smarty->display($template, $cacheid);

?>
