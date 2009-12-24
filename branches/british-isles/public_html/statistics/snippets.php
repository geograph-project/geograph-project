<?php
/**
 * $Project: GeoGraph $
 * $Id: statistics.php 5782 2009-09-12 09:47:01Z barry $
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

$template='statistics_snippets.tpl';
$cacheid='statistics|main';

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*3; //6hr cache


if (!$smarty->is_cached($template, $cacheid))
{
	$db = GeographDatabaseConnection(true);
	
	$smarty->assign("snippets",  $db->GetRow("
		select 
			count(*) as snippets,
			count(distinct user_id) as users, 
			count(distinct substring(created,1,10)) as days,
			count(distinct grid_reference) as squares
		from snippet 
		where enabled = 1"));

	$smarty->assign("gridimage_snippets",  $db->GetRow("
		select 
			count(distinct gridimage_id) as images,
			count(distinct snippet_id) as snippets,
			count(distinct user_id) as users, 
			count(distinct substring(created,1,10)) as days
		from gridimage_snippet 
		where gridimage_id < 4294967296"));
	
	
	$fields = "s.*,realname,COUNT(gs.snippet_id) AS images";
	$tables = "snippet s INNER JOIN user u USING (user_id) LEFT JOIN gridimage_snippet gs ON (s.snippet_id = gs.snippet_id AND gridimage_id < 4294967296)";
	
	#A sample of recent shared descriptions
	$where = array();
	$where[] = "enabled = 1"; 
	$where[] = "length(comment) > FLOOR(40 + (RAND() * 60))"; 
	$where= implode(' AND ',$where);
	
	$results = $db->getAll($sql="SELECT $fields FROM  WHERE $where GROUP BY s.snippet_id HAVING images > 1 ORDER BY s.created DESC LIMIT 10"); 
	
	$smarty->assign_by_ref('results',$results);


	#Some active shared descriptions
	$where = array();
	$where[] = "enabled = 1"; 
	$where= implode(' AND ',$where);
	
	$results2 = $db->getAll($sql="SELECT $fields FROM $tables WHERE $where GROUP BY s.snippet_id HAVING images > 2 ORDER BY gs.created DESC LIMIT 15"); 
	
	$smarty->assign_by_ref('results2',$results2);

}


$smarty->display($template, $cacheid);

