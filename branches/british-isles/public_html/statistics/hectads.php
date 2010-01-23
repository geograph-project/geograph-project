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
$smarty->cache_lifetime = 3600*6; //6hour cache

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
	$percents = $db->GetAll("SELECT 
	(floor(geosquares/landsquares*100) div 5)*5 as percentage,count(*) as c
	FROM hectad_stat 
	$where_sql
	GROUP BY FLOOR(geosquares/landsquares*100) div 5 DESC");
	
	$table = array();
	$max = 0; $sum=0;
	foreach ($percents as $row) {
		$line = array();
		$line['title'] = (($row['percentage']<100)?"{$row['percentage']}+":$row['percentage']).'%';
		$line['value'] = $row['c'];
		$table[] = $line;
		$max = max($max,$row['c']);
		$sum += $row['c'];
	}
	
	$graphs = array();
	$graph = array();
	
	$graph['table'] = &$table;
	$graph['title'] = 'Percentage Coverage by Number of Hectads';
	$graph['max'] = $max;
	$graph['total'] = array('title'=>'Total','value'=>$sum);
	
	$graphs[] = &$graph;
	
	$smarty->assign_by_ref('graphs',$graphs);
	
	$smarty->assign("h2title",$title);
	$smarty->assign("total",count($table));
	$smarty->assign_by_ref('references',$CONF['references_all']);

} 

$smarty->assign("filter",1);
$smarty->display($template, $cacheid);

	
?>
