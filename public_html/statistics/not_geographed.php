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

$ri = (isset($_GET['ri']) && is_numeric($_GET['ri']))?intval($_GET['ri']):0;

$template='statistics_not_geographed.tpl';
$cacheid='statistics|not_geographed'.$ri;

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*24; //24hr cache

if (!$smarty->is_cached($template, $cacheid))
{
	$db=GeographDatabaseConnection(true);
	
	$mosaic=new GeographMapMosaic;
	$mosaic->setScale(40);
	$mosaic->setMosaicFactor(2);

	$sql_where = '';
	if ($ri) {
		$sql_where .= " and reference_index = $ri";
		$smarty->assign('ri',$ri);
	} 

	$most = $db->GetAll("SELECT SQL_CALC_FOUND_ROWS
	reference_index,x,y,hectad,landsquares,last_submitted,map_token
	FROM hectad_stat 
	WHERE geosquares =0 $sql_where
	ORDER BY landsquares DESC,hectad LIMIT 150");

	$smarty->assign("total_rows",$db->getOne("SELECT FOUND_ROWS()"));
	$smarty->assign("shown_rows",count($most));

	foreach($most as $id=>$entry) 
	{
		if (empty($entry['map_token']))
		{
			if ($db->readonly) {
				$db = GeographDatabaseConnection(false);
			}
			
			$rii = $entry['reference_index'];
			$x = ( intval(($entry['x'] - $CONF['origins'][$rii][0])/10)*10 ) +  $CONF['origins'][$rii][0];
			$y = ( intval(($entry['y'] - $CONF['origins'][$rii][1])/10)*10 ) +  $CONF['origins'][$rii][1];

			//get a token to show a suroudding geograph map
			$mosaic->setOrigin($x,$y);

			$most[$id]['map_token'] = $mosaic->getToken();
		}
	}
	$smarty->assign_by_ref("most", $most);
	
	$smarty->assign_by_ref('references',$CONF['references_all']);
}

$smarty->display($template, $cacheid);

