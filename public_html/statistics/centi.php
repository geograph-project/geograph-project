<?php
/**
 * $Project: GeoGraph $
 * $Id: statistics.php 5607 2009-07-09 16:26:03Z hansjorg $
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

#if (isset($_GET['by']) && preg_match('/^\w+$/' , $_GET['by'])) {
#	header("Location:http://{$_SERVER['HTTP_HOST']}/statistics/breakdown.php?".$_SERVER['QUERY_STRING']);
#	exit;
#}

require_once('geograph/global.inc.php');
//require_once('geograph/mapmosaic.class.php');
init_session();

$smarty = new GeographPage;

$template='statistics_centi.tpl';

$mincenti = 60;

$cacheid="statistics|centi|$mincenti";

if ($smarty->caching) {
	$smarty->caching = 2; // lifetime is per cache
	$smarty->cache_lifetime = 3600/4; //15min cache
}

if (!$smarty->is_cached($template, $cacheid))
{
	$db=GeographDatabaseConnection();
	#$db->debug = true;
	

	require_once('geograph/gridsquare.class.php');

	$sql = "select gs.grid_reference as gridref,count(distinct((gi.natnorthings div 100)*10+gi.nateastings div 100)) csq from gridsquare gs inner join gridimage gi using (gridsquare_id)"
		." where gs.imagecount>=$mincenti and gi.natgrlen in ('6','8','10')  group by gs.gridsquare_id having csq>=$mincenti order by csq desc,gs.grid_reference asc";
	$allcenti = $db->GetAll($sql);
	if ($allcenti === false) {
		$allcenti = array();
	}
	if (count($allcenti)) {
		$sql = "select gs.grid_reference as gridref,count(distinct((gi.natnorthings div 100)*10+gi.nateastings div 100)) csq from gridsquare gs inner join gridimage gi using (gridsquare_id)"
			." where gs.imagecount>=$mincenti and gi.moderation_status = 'geograph' and gi.natgrlen in ('6','8','10')  group by gs.gridsquare_id order by csq desc,gs.grid_reference asc";
		$geocenti = $db->GetAssoc($sql);
		foreach ($allcenti as &$row) {
			$row['csqgeo'] = isset($geocenti[$row['gridref']]) ? $geocenti[$row['gridref']] : 0;
		}
		unset($row);
	}

	$smarty->assign("rows", $allcenti);
} 


$smarty->display($template, $cacheid);

?>
