<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Paul Dixon (paul@elphin.com)
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

$ri = (isset($_GET['ri']) && is_numeric($_GET['ri']))?intval($_GET['ri']):0;

$template='statistics_graph.tpl';
$cacheid='statistics|hectads'.$ri;

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*24; //24hour cache

if (!$smarty->is_cached($template, $cacheid))
{
	$db = GeographDatabaseConnection(true);
	
	$title = "Hectad Coverages";
	
	if ($ri) {
		$where[] = "reference_index = $ri";
		$smarty->assign('ri',$ri);

		$title .= " in ".$CONF['references_all'][$ri];
	} 

	if (count($where))
		$where_sql = " WHERE ".join(' AND ',$where);

	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$hectads = $db->CacheGetAll(3600,"select 
	concat(substring(grid_reference,1,length(grid_reference)-3),substring(grid_reference,length(grid_reference)-1,1)) as tenk_square,
	sum(has_geographs) as geograph_count,
	(sum(has_geographs) * 100 / sum(percent_land >0)) as percentage,
	sum(percent_land >0) as land_count
	from gridsquare 
	$where_sql
	group by tenk_square 
	having land_count > 0
	order by percentage desc,tenk_square");
	
	$count = array();
	foreach ($hectads as $i => $row) {
		if ($row['percentage'] == 0) {
			$count[0]++;
		} elseif ($row['percentage'] == 100) {
			$count[101]++;
		} else {
			$count[min(100,intval($row['percentage']/5)*5+5)]++;
		}
	}
	
	$percents = array_keys($count);
	natsort($percents);
	
	$table = array();
	$max = 0;
	foreach (array_reverse($percents) as $p) {
		$line = array();
		$line['title'] = ($p > 0 && $p < 101?"&lt; ":'').($p > 100?($p - 1):$p);
		$line['value'] = $count[$p];
		$table[] = $line;
		$max = max($max,$count[$p]);
	}
	
	$graphs = array();
	$graph = array();
	
	$graph['table'] = &$table;
	$graph['title'] = 'Percentage Coverage by Number of Hectads';
	$graph['max'] = $max;
	
	$graphs[] = &$graph;
	
	$smarty->assign_by_ref('graphs',$graphs);
	
	$smarty->assign("h2title",$title);
	$smarty->assign("total",count($table));
	$smarty->assign_by_ref('references',$CONF['references_all']);

} 

$smarty->assign("filter",1);
$smarty->display($template, $cacheid);

	
?>
