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

$days = (isset($_GET['days']) && is_numeric($_GET['days']))?intval($_GET['days']):7;



$template='statistics_estimate.tpl';
$cacheid='statistics|estimate'.$ri.'.'.$days;

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*24; //24hr cache

if (!$smarty->is_cached($template, $cacheid))
{
	$db = GeographDatabaseConnection(true);

	if ($ri) {
		$whereri = " where reference_index = $ri";
		$andri = " and reference_index = $ri";
		$smarty->assign('ri',$ri);
	} else {
		$whereri = "";
		$andri = ""; 
	}

	$table = array();

	$beginday = date("Y-m-d",mktime(0,0,0,date('m'),date('d')-$days,date('Y')));
	$today = date("Y-m-d");

	$sql = "select substring(submitted,1,10) as d ,count(*) as c from gridimage_search where submitted > '$beginday' AND submitted < '$today' $andri group by substring(submitted,1,10)";
	$sql2 = "select count(*) from gridimage_search $whereri ";
	$image = calc($sql,$sql2,10000,'Images');
	
	$smarty->assign("image",$image);
	
	
	$sql = "select substring(submitted,1,10) as d ,count(*) as c from gridimage_search where moderation_status = 'geograph' and submitted > '$beginday' AND submitted < '$today' $andri group by substring(submitted,1,10)";
	$sql2 = "select count(*) from gridimage_search where moderation_status = 'geograph' $andri";
		
	$geograph = calc($sql,$sql2,10000,'Geographs');
		
	$smarty->assign("geograph",$geograph);
	
	
	$sql = "select substring(suggested,1,10) as d ,count(*) as c from gridimage_ticket where suggested > '$beginday' AND suggested < '$today' group by substring(suggested,1,10)";
	$sql2 = "select count(*) from gridimage_ticket";
		
	$ticket = calc($sql,$sql2,10000,'Suggestions');
		
	$smarty->assign("ticket",$ticket);
	
	
	$sql = "select date(last_submitted) as d ,count(*) as c from hectad_stat where last_submitted > '$beginday' AND last_submitted < '$today' $andri group by date(last_submitted)";
	$sql2 = "select count(*) from hectad_complete $whereri";
		
	$hectad = calc($sql,$sql2,100,'Hectads');
		
	$smarty->assign("hectad",$hectad);
	
	
	$sql = "select substring(crt_timestamp,1,10) as d ,count(*) as c from queries where crt_timestamp > '$beginday' AND crt_timestamp < '$today' group by substring(crt_timestamp,1,10)";
	$sql2 = "SELECT (select count(*) from queries)+(select count(*) from queries_archive)";

	$searches = calc($sql,$sql2,10000,'Searches');
				
	$smarty->assign("searches",$searches);
	
	
	$sql = "select substring(post_time,1,10) as d ,count(*) as c from geobb_posts where post_time > '$beginday' AND post_time < '$today' group by substring(post_time,1,10)";
	$sql2 = "select count(*) from geobb_posts";

	$post = calc($sql,$sql2,1000,'Posts');
			
	$smarty->assign("post",$post);


	$sql = "select substring(signup_date,1,10) as d ,count(*) as c from user where rights <> '' and signup_date > '$beginday' AND signup_date < '$today' group by substring(signup_date,1,10)";
	$sql2 = "select count(*) from user where rights <> ''";

	$users = calc($sql,$sql2,500,'Users');
			
	$smarty->assign("users",$users);
	
	
	$sql = "select substring(signup_date,1,10) as d ,count(*) as c from user where rights <> '' and signup_date > '$beginday' AND signup_date < '$today' and (select gridimage_id from gridimage_search gi where gi.user_id = user.user_id $andri limit 1) is not NULL group by substring(signup_date,1,10)";
	
	$sql2 = "select count(distinct user_id) from gridimage_search $whereri";

	$cusers = calc($sql,$sql2,100,'Contributing Users');
				
	$smarty->assign("cusers",$cusers);
	

	$sql = "select substring(submitted,1,10) as d ,count(*) as c from gridimage_search where ftf = 1 and submitted > '$beginday' AND submitted < '$today' $andri group by substring(submitted,1,10)";
	$sql2 = "select count(*) from gridimage_search where ftf = 1 $andri";

	$point = calc($sql,$sql2,10000,'Points');
			
	$smarty->assign("point",$point);


	$total['average'] = $total['average_r'] = $point['total'] / ($days/7); 
	$total['next'] = $db->getOne("select count(*) from gridsquare where percent_land > 0 $andri");
		
	$total['dif'] = $total['next'] - $point['count'];
		
	$total['weeks'] = $total['dif']/$total['average'];
	$total['weeks_r'] = floor($total['weeks']);

	if ($total['weeks'] < 1040) { //20years
		$total['endtime'] = strtotime("+{$total['weeks_r']} weeks");
		$total['enddate'] = "about ".date("F Y",$total['endtime']);
	} else {
		$total['enddate'] = "sometime in ".(date("Y")+round($total['weeks']/52));
	}


	$smarty->assign("totall",$total);
	$smarty->assign_by_ref('references',$CONF['references_all']);
	
	//little bodge to rename the first item to have key '0'
	$keys = array_keys($table);
	$key1 = $keys[0];
	$first = $table[$key1];
	unset($table[$key1]);
	array_unshift($table,$first);

	$smarty->assign_by_ref('table', $table);
		
	$smarty->assign("total",count($table));
}

$smarty->display($template, $cacheid);

function calc($sql,$sql2,$mult,$title) {
	global $db,$table,$days;
	
	$array = $db->getAssoc($sql);
	
	$total = 0;
	if (count($table)) {
		foreach ($table as $key => $value) {
			$total += $array[$key];
			if ($title) {
				$table[$key]['Date'] = $key;
				$table[$key][$title] = $array[$key];
			}
		}
	} else {
	
		foreach ($array as $key => $value) {
			$total += $value;
			if ($title) {
				$table[$key]['Date'] = $key;
				$table[$key][$title] = $value;
			}
		}
	}
	$image['total'] = $total;
	
	$image['average'] = $total / $days;
	$image['average_r'] = floor($image['average']);
	
	if ($title) {
		$table['Average']['Date'] = 'Average';
		$table['Average'][$title] = $image['average_r'];
	}
	
	$image['count'] = $db->getOne($sql2);
	
	$image['next'] = ceil($image['count']/$mult) * $mult;
	
	$image['dif'] = $image['next'] - $image['count'];
	
	$image['days'] = $image['dif']/$image['average'];
	$image['days_r'] = ceil($image['days']);
	return $image;
}
	
?>