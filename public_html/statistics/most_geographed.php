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

$myriad = (isset($_GET['myriad']) && preg_match('/^[\w]{1,3}$/' , $_GET['myriad']))?$_GET['myriad']:'';

$ri = (isset($_GET['ri']) && is_numeric($_GET['ri']))?intval($_GET['ri']):0;

$template='statistics_most_geographed.tpl';
$cacheid='statistics|most_geographed'.$ri.$myriad;

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*6; //24hr cache

if (!$smarty->is_cached($template, $cacheid))
{
	$db = GeographDatabaseConnection(true);

	$mosaic=new GeographMapMosaic;
	$mosaic->setScale(40);
	$mosaic->setMosaicFactor(2);

	$sql_where = '';
	if ($myriad) {
		$sql_where = " and hectad like '$myriad%'";
		if (strlen($myriad) == 2) {
			$ri = 1;
		} elseif (strlen($myriad) == 1) {
			$ri = 2;
		} 
		$smarty->assign('myriad',$myriad);
	} 
	if ($ri) {
		$sql_where .= " and reference_index = $ri";
		$smarty->assign('ri',$ri);
	} 


	$prev_fetch_mode = $ADODB_FETCH_MODE;
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;	
	$most = $db->GetAll("SELECT SQL_CALC_FOUND_ROWS
	reference_index,x,y,hectad,geosquares,landsquares,last_submitted,ftfusers,map_token,largemap_token,
	(geosquares/landsquares*100) as percentage
	FROM hectad_stat 
	WHERE geosquares > 0 and (geosquares/landsquares) <1 $sql_where
	ORDER BY percentage DESC,hectad LIMIT 150");
	$ADODB_FETCH_MODE = $prev_fetch_mode;

	$smarty->assign("total_rows",$db->getOne("SELECT FOUND_ROWS()"));
	$smarty->assign("shown_rows",count($most));

	$i = 1;
	$lastgeographs = -1;
	foreach($most as $id=>$entry) 
	{
		if ($lastgeographs == $most[$id]['percentage'])
			$most[$id]['ordinal'] = '&quot;&nbsp;&nbsp;&nbsp;';
		else {
			$most[$id]['ordinal'] = smarty_function_ordinal($i);
			$lastgeographs = $most[$id]['percentage'];
		}
		$i++;

		if (empty($entry['map_token'])) {
			if ($db->readonly) {
				$db = GeographDatabaseConnection(false);
			}
			$rii = $entry['reference_index'];
			$x = ( intval(($entry['x'] - $CONF['origins'][$rii][0])/10)*10 ) +  $CONF['origins'][$rii][0];
			$y = ( intval(($entry['y'] - $CONF['origins'][$rii][1])/10)*10 ) +  $CONF['origins'][$rii][1];

			//get a token to show a suroudding geograph map
			$mosaic->setOrigin($x,$y);

			$most[$id]['map_token'] = $mosaic->getToken();

			$db->Execute(sprintf("UPDATE hectad_stat SET
				map_token = %s
				WHERE hectad = %s",
				$db->Quote($most[$id]['map_token']),
				$db->Quote($entry['hectad']) ));
		}

	}

	$smarty->assign_by_ref("most", $most);

	$smarty->assign_by_ref('references',$CONF['references_all']);
}


$smarty->display($template, $cacheid);

