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
init_session();


$smarty = new GeographPage;

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*24; //24hr cache

if (!empty($_GET['ri'])) {
	if (!empty($_GET['adm1'])) {
		if (!empty($_GET['pid'])) {
			$db=NewADOConnection($GLOBALS['DSN']);
	
			require_once('geograph/searchcriteria.class.php');
			require_once('geograph/searchengine.class.php');
			require_once('geograph/searchenginebuilder.class.php');
	
			if ($_GET['pid'] > 1000000) {
				$sql = "SELECT def_nam as full_name,east as e,north as n,full_county as adm1_name FROM os_gaz WHERE seq = ".($_GET['pid']-1000000);
				$placename = $db->GetRow($sql);
				$adm1_name = ", ".$placename['adm1_name'];
			} else {
				$sql = "SELECT full_name,e,n FROM loc_placenames WHERE id = {$_GET['pid']}";
				$placename = $db->GetRow($sql);

				if ($_GET['ri'] == 2) {
					$sql = "SELECT name FROM loc_adm1 WHERE reference_index = {$_GET['ri']} AND adm1 = {$_GET['adm1']}";
					$adm1_name = ", ".$db->GetOne($sql);
				}
			}
			$dataarray['description'] = "around {$placename['full_name']}$adm1_name";
			$dataarray['searchq'] = " gi.placename_id = {$_GET['pid']} ";
			
			
			$dataarray['x'] = intval($placename['e']/1000) + $CONF['origins'][$_GET['ri']][0];
			$dataarray['y'] = intval($placename['n']/1000) + $CONF['origins'][$_GET['ri']][1];
			#$this->placename = $places[0]['full_name'];
			$dataarray['reference_index'] = $_GET['ri'];
			
			$engine = new SearchEngineBuilder('#'); 
			$engine->buildAdvancedQuery($dataarray);
			
			//should never return!
		}
		$template='explore_places_adm1.tpl';
		$cacheid='places|'.$_GET['ri'].'.'.$_GET['adm1'];
	} else {
		$template='explore_places_ri.tpl';
		$cacheid='places|'.$_GET['ri'];
	}
} else {
	$template='explore_places.tpl';
	$cacheid='places';
}
if (isset($_GET['refresh']) && $USER->hasPerm('admin'))
	$smarty->clear_cache($template, 'places');

//regenerate?
if (!$smarty->is_cached($template, $cacheid))
{
	require_once('geograph/gridimage.class.php');
	require_once('geograph/gridsquare.class.php');
	require_once('geograph/imagelist.class.php');

	$db=NewADOConnection($GLOBALS['DSN']);
	
	
	if (!empty($_GET['ri'])) {
		$smarty->assign('ri', $_GET['ri']);
		if (!empty($_GET['adm1'])) {
			$smarty->assign('adm1', $_GET['adm1']);
			if (!empty($_GET['pid'])) {
				//todo: error message?
			} 
			if ($_GET['ri'] == 2) {
			
				$sql = "SELECT name FROM loc_adm1 WHERE reference_index = {$_GET['ri']} AND adm1 = {$_GET['adm1']}";
				$smarty->assign_by_ref('adm1_name', $db->GetOne($sql));
				$smarty->assign('parttitle', "in County");
				$sql_where = "AND loc_placenames.adm1 = {$_GET['adm1']}";
				$sql = "SELECT placename_id,full_name,count(*) as c,gridimage_id 
				FROM gridimage INNER JOIN loc_placenames ON(placename_id = loc_placenames.id)
				WHERE moderation_status <> 'rejected' 
					AND loc_placenames.reference_index = {$_GET['ri']}
					$sql_where
				GROUP BY placename_id";
			} else {
				//adm1 is actullu just an example placename!
				$sql = "SELECT co_code,full_county as adm1_name FROM os_gaz WHERE seq = ".$_GET['adm1'];
				$placename = $db->GetRow($sql);
				
				$smarty->assign_by_ref('adm1_name', $placename['adm1_name']);
				$smarty->assign('parttitle', "in County");
				$sql_where = "AND co_code = '{$placename['co_code']}'";
				
				$sql = "SELECT placename_id,def_nam as full_name,count(*) as c,gridimage_id 
				FROM gridimage INNER JOIN os_gaz ON(placename_id-1000000 = os_gaz.seq)
				WHERE moderation_status <> 'rejected' AND placename_id > 1000000
					$sql_where
				GROUP BY placename_id";
			}


			$counts = $db->GetAssoc($sql);
			$smarty->assign_by_ref('counts', $counts);
			
		} elseif ($_GET['ri'] == 1) {
			$sql = "SELECT seq as adm1,
			full_county as name,
			count(*) as images,count(distinct (seq)) as places 
			FROM gridimage INNER JOIN os_gaz ON(placename_id-1000000 = os_gaz.seq)
			WHERE moderation_status <> 'rejected' AND placename_id > 1000000
			GROUP BY full_county";
			$counts = $db->GetAssoc($sql);
			$smarty->assign_by_ref('counts', $counts);
		} else {
			$sql = "SELECT loc_placenames.adm1,loc_adm1.name,
			count(*) as images,count(distinct (placename_id)) as places 
			FROM gridimage INNER JOIN loc_placenames ON(placename_id = loc_placenames.id)
			INNER JOIN loc_adm1 ON(loc_adm1.adm1 = loc_placenames.adm1 AND loc_adm1.reference_index = {$_GET['ri']})
			WHERE moderation_status <> 'rejected' AND loc_placenames.reference_index = {$_GET['ri']}
			GROUP BY loc_placenames.adm1";
			$counts = $db->GetAssoc($sql);
			unset($counts['0']); //adm1 0 is bogus!
			$smarty->assign_by_ref('counts', $counts);
		}
	} else {
		$sql = "SELECT reference_index,count(*) as c 
		FROM gridimage_search
		GROUP BY reference_index";
		$counts = $db->GetAssoc($sql);
		$smarty->assign_by_ref('counts', $counts);
	}
	

	$smarty->assign_by_ref('references',$CONF['references']);
}

$smarty->display($template, $cacheid);

	
?>
