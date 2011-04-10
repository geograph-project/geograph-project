<?php
/**
 * $Project: GeoGraph $
 * $Id: hectads.php 6328 2010-01-23 18:01:00Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2011 Barry Hunter (geo@barryhunter.co.uk)
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

if (empty($_GET['q'])) {
	print "no keywords!";
	exit;
}


$template='statistics_graph.tpl';
$cacheid='statistics|keywords'.md5($_GET['q']);

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*6; //6hour cache

if (!$smarty->is_cached($template, $cacheid))
{
	
	$title = "Keyword Stats";
	
	$sphinx = new sphinxwrapper();
	
	$smarty->assign("prefix","
	<form method=\"get\">
		<div class=\"interestBox\">
			Keywords: <input type=\"text\" name=\"q\" value=\"".htmlentities($_GET['q'])."\" size=\"80\"/> <input type=submit value=\"Update\"/>
		</div>
	</form>");
	
	
	$cl = $sphinx->_getClient();
	
	$graphs = array();
	$indexes = explode(',',"{$CONF['sphinx_prefix']}gi_stemmed_delta,{$CONF['sphinx_prefix']}gi_stemmed");
	
	foreach ($indexes as $index) {
	
		$data = $cl->BuildKeywords($_GET['q'],$index, true);


		$table = array();
		$max = 0; $sum=0;
		foreach ($data as $row) {
			if (!$row['docs'])
				continue;
			$line = array();
			$line['title'] = $row['normalized'];
			$line['value'] = $row['docs'];
			$table[] = $line;
			$max = max($max,$row['docs']);
			$sum += $row['docs'];
		}

		$graph = array();

		$graph['table'] = $table;
		$graph['title'] = (preg_match('/_delta/',$index)?'Recent Images (last 48 hours)':'Everything Else');
		$graph['max'] = $max;
		#$graph['total'] = array('title'=>'Total','value'=>$sum);

		$graphs[] = $graph;
	}
	
	$smarty->assign_by_ref('graphs',$graphs);
	
	$smarty->assign("h2title",$title);
	$smarty->assign("total",count($table));


} 


$smarty->display($template, $cacheid);

