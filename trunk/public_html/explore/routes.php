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

$ri = (isset($_GET['ri']) && is_numeric($_GET['ri']))?intval($_GET['ri']):0;

$template='explore_routes.tpl';
$cacheid='routes'.$ri;

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*24; //24hr cache

if (!$smarty->is_cached($template, $cacheid))
{
	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');  
	
	$title = "Interesting Routes";
	
	$where = array('enabled = 1');
	if ($ri) {
		$where[] = "reference_index = $ri";
		$smarty->assign('ri',$ri);

		$title .= " in ".$CONF['references_all'][$ri];
	} 

	if (count($where))
		$where_sql = " where ".join(' AND ',$where);

	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$routes =& $db->getAll("
	select
		route.route_id,
		name,
		count(*) as `count`,
		orderby,
		route_group
	from
		route
		inner join route_item using (route_id)
	$where_sql
	group by
		route.route_id
	order by 
		route_group,name");
	
	$table = array();
	foreach ($routes as $i => $row) {
		if ($last != $row['route_group']) {
			$table[] = array('Route Name'=>"<b>{$row['route_group']}</b>",'Number of<br/> Gridsquares'=>'<hr/>'); 
		}
		$line = array();
		$extra = ($row['orderby'])?"&amp;orderby={$row['orderby']}":'';
		$line['Route Name'] = "<a href=\"/search.php?route_id={$row['route_id']}$extra&amp;do=1\">{$row['name']}</a>";
		$line['Number of Gridsquares'] = number_format($row['count']);
		$table[] = $line; 
		$last = $row['route_group'];
	}
	
	$smarty->assign_by_ref('table',$table);
	
	$smarty->assign("h2title",$title);
	$smarty->assign("total",count($table));
	$smarty->assign_by_ref('references',$CONF['references_all']);

} 

$smarty->assign("filter",1);
$smarty->display($template, $cacheid);

	
?>
