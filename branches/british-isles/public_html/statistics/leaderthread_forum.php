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

$type = (isset($_GET['type']) && preg_match('/^[\w ]+$/' , $_GET['type']))?$_GET['type']:'Posts';


$cacheid='statistics|leaderthread_forum'.$u.$type;

if (!$smarty->is_cached($template, $cacheid))
{
        $db = GeographDatabaseConnection(true);

	$title = "Top Forum Threads";

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
	CONCAT('<a href=\"/discuss/?action=vthread&amp;topic=',geobb_posts.topic_id,'\">',topic_title,'</a>') as `Topic Title`,
	count(*) as Posts,
	count(distinct poster_id) as Posters,
	topic_views as Views,
	datediff(max(post_time),min(post_time))+1 as `Days Active For`,
	count(distinct substring(post_time,1,10)) as `Different Days`,
	count(*)/count(distinct substring(post_time,1,10)) as `Average Posts per Day`,
	topic_views/count(distinct substring(post_time,1,10)) as `Average Views per Day`
	from geobb_posts inner join geobb_topics using(topic_id)
	where 1 $where_sql
	group by geobb_posts.topic_id 
	order by `$type` desc limit 50;" );
	
	$title = count($table)." $title";
	
	$smarty->assign_by_ref('table', $table);
	
	$smarty->assign("h2title",$title);
	$smarty->assign("total",count($table));
	
	$head = "<p>Sort By";
	$i = 0;
	foreach ($table[0] as $key => $value) {
		if ($i > 0) {
			if ($key == $type) {
				$head .= "[<b>{$key}</b>] ";
			} else {
				$head .= "[<a href=\"{$_SERVER['PHP_SELF']}?u=$u&amp;type=$key\">$key</a>] ";
			}
		}
		$i++;
	}
	$head .= "</p>";
	$smarty->assign("headnote",$head);
	
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
