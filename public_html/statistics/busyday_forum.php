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

$cacheid='statistics|busyday_forum'.isset($_GET['users']).$u.'.'.isset($_GET['threads']);

if (!$smarty->is_cached($template, $cacheid))
{
	require_once('geograph/gridimage.class.php');
	require_once('geograph/gridsquare.class.php');
	require_once('geograph/imagelist.class.php');

	$db = GeographDatabaseConnection(true);  

	$column = 'post_time';  
	
	$title = "Busiest Day for Forum Posts";

	$where = array();
	
	if (!empty($u)) {
		$where[] = "poster_id=".$u;
		$smarty->assign('u', $u);

		$profile=new GeographUser($u);
		$smarty->assign_by_ref('profile', $profile);
		$title .= " for ".($profile->realname);
	} elseif (isset($_GET['users'])) {
		$group_sql = 'poster_id,';
		$column_sql = "CONCAT('<a href=\"/profile/',poster_id,'\">',poster_name,'</a>') as User,";
		$title .= " by user";
	}
	if (isset($_GET['threads'])) {
		$join_sql .= ' inner join geobb_topics using(topic_id)';
		$group_sql .= 'geobb_posts.topic_id,';
		$column_sql .= "CONCAT('<a href=\"/discuss/?action=vthread&amp;topic=',geobb_posts.topic_id,'\">',topic_title,'</a>') as Topic,";
		$title .= " by topic";
	}
	
	if (count($where))
		$where_sql = " AND ".join(' AND ',$where);
		
		
	 $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$table=$db->GetAll("
	select 
	$column_sql
	DATE_FORMAT($column,'%d/%m/%Y') as Date,
	count(*) as Posts,
	count(distinct poster_id) as Users
	from geobb_posts $join_sql
	where 1 $where_sql
	group by $group_sql date($column) 
	order by Posts desc limit 50;" );
	
	$title = count($table)." $title";
	
	$smarty->assign_by_ref('table', $table);
	
	$smarty->assign("h2title",$title);
	$smarty->assign("total",count($table));
	
	$extra = array();

	foreach (array('users','threads') as $key) {
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
$smarty->assign("nosort",1);
$smarty->display($template, $cacheid);

	
?>
