<?php
/**
 * $Project: GeoGraph $
 * $Id: hectads.php 2187 2006-04-28 12:03:21Z barryhunter $
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
$cacheid='statistics|photos'.$ri;

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*24; //24hour cache

if (!$smarty->is_cached($template, $cacheid))
{
	$db = GeographDatabaseConnection(true);
	
	$title = "Photos Per Square";
	
	if ($ri) {
		$where[] = "reference_index = $ri";
		$smarty->assign('ri',$ri);

		$title .= " in ".$CONF['references_all'][$ri];
	} 

	if (count($where))
		$where_sql = " AND ".join(' AND ',$where);

	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$images = $db->CacheGetAll(3600,"select 
	imagecount,count(*) as count
	from gridsquare 
	where percent_land >0
		$where_sql
	group by imagecount 
	order by null");
	
	$count = array();
	foreach ($images as $i => $row) {
		if ($row['imagecount'] == 0) {
			$count[0]+=$row['count'];
		} elseif ($row['imagecount'] <= 10) {
			$count[$row['imagecount']]+=$row['count'];
		} elseif ($row['imagecount'] < 100) {
			$count[intval($row['imagecount']/10)*10+10]+=$row['count'];
		} else {
			$count[intval($row['imagecount']/100)*100+100]+=$row['count'];
		}
	}
	
	$percents = array_keys($count);
	natsort($percents);
	
	$table = array();
	$max = 0;
	foreach ($percents as $p) {
		$line = array();
		$line['title'] = ($p > 10?"&lt; ":'').$p;
		$line['value'] = $count[$p];
		$table[] = $line;
		$max = max($max,$count[$p]);
	}
	
	$graphs = array();
	$graph = array();
	
	$graph['table'] = &$table;
	$graph['title'] = 'Number of photos by number of land squares';
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
