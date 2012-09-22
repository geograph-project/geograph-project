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

if (isset($_GET['output']) && $_GET['output'] == 'csv') {
	$template='statistics_table_csv.tpl';
	# let the browser know what's coming
	header("Content-type: application/octet-stream");
	header("Content-Disposition: attachment; filename=\"".basename($_SERVER['SCRIPT_NAME'],'.php').".csv\"");
} else {
	$template='statistics_table.tpl';
}

$u = (isset($_GET['u']) && is_numeric($_GET['u']))?intval($_GET['u']):0;

$cacheid='statistics|years_forum'.$u;

if (!$smarty->is_cached($template, $cacheid))
{
	require_once('geograph/gridimage.class.php');
	require_once('geograph/gridsquare.class.php');
	require_once('geograph/imagelist.class.php');

	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');  

	$column = 'post_time';  

	$title = "Number of Days Forum Posts Made per Year";

	$where = array();
	
	if (!empty($u)) {
		$where[] = "poster_id=".$u;
		$smarty->assign('u', $u);

		$profile=new GeographUser($u);
		$smarty->assign_by_ref('profile', $profile);
		$title .= " for ".($profile->realname);
	} 

	
	if (count($where))
		$where_sql = " AND ".join(' AND ',$where);
		
		
	 $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$table=$db->GetAll("
	select 
	SUBSTRING($column,1,4) as Year,
	count(*)/count(DISTINCT substring( $column, 1, 10 )) as `Average Posts Per Day`,
	count(DISTINCT substring( $column, 1, 10 )) as Days 
	from geobb_posts 
	where 1 $where_sql 
	group by SUBSTRING($column,1,4)
	order by Year desc;" );
	
	$thisyear = date('Y');
	foreach ($table as $id => $row) {
		if ($row['Year'] == 2005) {
			$days = 284; //as the forum launced on Mar 21st
		} elseif ($row['Year'] == $thisyear) {
			$days = date('z')+1;
		} else {
			$days = 365 + (!($row['Year'] % 4) && (($row['Year'] % 100) || !($row['Year'] % 400)));
		}
		$table[$id]['Days in Year'] = $days;
		$table[$id]['Percentage'] = sprintf('%.1f',$row['Days']/$days*100)."%";
	}
	
	$smarty->assign_by_ref('table', $table);
	
	$smarty->assign("footnote","<br/>The Percentage column is the number of days in that year that we do have photos for (taking into account leap years ;)");
	$smarty->assign("h2title",$title);
	$smarty->assign("total",count($table));
	
} else {
	if ($u) {
		$profile=new GeographUser($u);
		$smarty->assign_by_ref('profile', $profile);
		$smarty->assign_by_ref('u', $u);
	}
}
$smarty->assign("filter",2);
$smarty->assign("nosort",1);
$smarty->display($template, $cacheid);

	
?>
