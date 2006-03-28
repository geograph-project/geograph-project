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
$smarty->cache_lifetime = 3600*3; //3hr cache

$template='statistics_table.tpl';

$ri = (isset($_GET['ri']) && is_numeric($_GET['ri']))?intval($_GET['ri']):0;

$u = (isset($_GET['u']) && is_numeric($_GET['u']))?intval($_GET['u']):0;


$cacheid='forum_image_breakdown'.$ri.'.'.$u;

if (!$smarty->is_cached($template, $cacheid))
{
	require_once('geograph/gridimage.class.php');
	require_once('geograph/gridsquare.class.php');
	require_once('geograph/imagelist.class.php');

	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');  

	$title = "Breakdown of Images";
	
	$where = array();

	if (!empty($u)) {
		$where[] = "user_id=".$u;
		$smarty->assign('u', $u);

		$profile=new GeographUser($u);
		$smarty->assign_by_ref('profile', $profile);
		$title .= " by ".($profile->realname);
		$having_sql = '';
	} else {
		$having_sql = "HAVING `Images` > 4";
	}
	
	if ($ri) {
		$where[] = "reference_index = $ri";
		$smarty->assign('ri',$ri);

		$title .= " in ".$CONF['references_all'][$ri];
	}
	
	$where_sql = '';
	if (count($where))
		$where_sql = " WHERE ".join(' AND ',$where);
	
	$title .= " used in Forum Topics";	
		
	 $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$table=$db->GetAll("SELECT 
	topic_title as `Topic`,
	count( DISTINCT gp.gridimage_id ) AS `Images`, 
	count( DISTINCT post_id ) AS `Posts`, 
	count( DISTINCT user_id ) AS `Photographers`
	FROM gridimage_post gp
	INNER JOIN `geobb_topics` gt ON (gp.topic_id = gt.topic_id)
	INNER JOIN gridimage_search gi ON (gp.gridimage_id = gi.gridimage_id)
	$where_sql 
	GROUP BY gp.topic_id 
	$having_sql
	ORDER BY `Images` DESC" );
	
	$smarty->assign_by_ref('table', $table);
	
	$smarty->assign("h2title",$title);
	$smarty->assign("total",count($table));
	$smarty->assign_by_ref('references',$CONF['references_all']);	
	
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
