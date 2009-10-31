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
$cacheid='statistics|users'.$ri;

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*24; //24hour cache

if (!$smarty->is_cached($template, $cacheid))
{
	$db = GeographDatabaseConnection(true); 
	
	$title = "User Submissions";
	
	$column = "count(*)";

	
	if ($ri) {
		$where[] = "reference_index = $ri";
		$smarty->assign('ri',$ri);

		$title .= " in ".$CONF['references_all'][$ri];
	} 

	if (count($where))
		$where_sql = " WHERE ".join(' AND ',$where);

	
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$hectads = $db->CacheGetAll(3600,"select 
	$column as count
	from gridimage_search 
	$where_sql
	group by user_id
	order by null");
	
	$count = array();
	foreach ($hectads as $i => $row) {
		if ($row['count'] == 0) {
			$count[0]++;
		} elseif ($row['count'] < 50) {
			$count[intval($row['count']/5)*5+5]++;
		} elseif ($row['count'] < 200) {
			$count[intval($row['count']/10)*10+10]++;
		} elseif ($row['count'] < 1000) {
			$count[intval($row['count']/50)*50+50]++;
		} else {
			$count[intval($row['count']/500)*500+500]++;
		}
	}
	
	$percents = array_keys($count);
	natsort($percents);
	
	$table = array();
	$max = 0;
	foreach ($percents as $p) {
		#if ($p == 1000 || $p == 50) {
		#	$table[] = array();
		#}
		$line = array();
		$line['title'] = "&lt; $p";
		$line['value'] = $count[$p];
		$table[] = $line;
		$max = max($max,$count[$p]);
	}
	
	$graphs = array();
	$graph = array();
	
	$graph['table'] = &$table;
	$graph['title'] = 'Images Submitted by Number of Contributors';
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
