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


$cacheid='statistics|overtimeforum'.isset($_GET['month']).'.'.$u;

if (!$smarty->is_cached($template, $cacheid))
{
	require_once('geograph/gridimage.class.php');
	require_once('geograph/gridsquare.class.php');
	require_once('geograph/imagelist.class.php');

        $db = GeographDatabaseConnection(true);

	$length = isset($_GET['month'])?10:7;
	
	$title = "Breakdown of Forum Posts over Time";
	
	$where = array();
	if (!empty($u)) {
		$where[] = "poster_id=".$u;
		$smarty->assign('u', $u);

		$profile=new GeographUser($u);
		$smarty->assign_by_ref('profile', $profile);
		$title .= " for ".($profile->realname);
	} else {
		$columns_sql = ", count( DISTINCT poster_id ) AS `Different Posters`";
	}
	if (count($where))
		$where_sql = " WHERE ".join(' AND ',$where);
		
	 $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$table=$db->GetAll("SELECT 
	substring( post_time, 1, $length ) AS `Date`, 
	count( * ) AS `Posts`, 
	count( DISTINCT forum_id ) AS `Different Forums`, 
	count( DISTINCT topic_id ) AS `Different Topics`
	$columns_sql
FROM `geobb_posts` $where_sql
GROUP BY substring( post_time, 1, $length )" );

	if (!isset($_GET['output']) || $_GET['output'] != 'csv') 
	{
		foreach($table as $idx=>$entry)
		{
			$table[$idx]['Date'] = getFormattedDate($table[$idx]['Date']);
		}
	}
	
	$smarty->assign_by_ref('table', $table);
	
	$smarty->assign("h2title",$title);
	$smarty->assign("total",count($table));
	
	
	$extra = array();
	foreach (array('month') as $key) {
		if (isset($_GET[$key])) {
			$extra[$key] = $_GET[$key];
		}
	}
	$smarty->assign_by_ref('extra',$extra);	
} else {
	if ($u) {
		$profile=new GeographUser($u);
		$smarty->assign_by_ref('profile', $profile);
		$smarty->assign_by_ref('u', $u);
	}
}
$smarty->assign("filter",2);

$smarty->display($template, $cacheid);

	
?>
