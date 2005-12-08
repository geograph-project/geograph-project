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


$template='statistics_estimate.tpl';
$cacheid='statistics|estimate'.$ri;

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*24; //24hr cache

if (!$smarty->is_cached($template, $cacheid))
{
	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');  
	#$db->debug = true;

	if ($ri) {
		$whereri = " where reference_index = $ri";
		$andri = " and reference_index = $ri"; 
	} else {
		$whereri = "";
		$andri = ""; 
	}

	$beginday = date("Y-m-d",mktime(0,0,0,date('m'),date('d')-7,date('Y')));
	$today = date("Y-m-d");

	$sql = "select substring(submitted,1,10) as d ,count(*) as c from gridimage_search where submitted > '$beginday' AND submitted < '$today' $andri group by substring(submitted,1,10)";
	$sql2 = "select count(*) from gridimage_search $whereri ";
	$image = calc($sql,$sql2,10000);
	
	$smarty->assign("image",$image);
	
	
	$sql = "select substring(submitted,1,10) as d ,count(*) as c from gridimage_search where moderation_status = 'geograph' and submitted > '$beginday' AND submitted < '$today' $andri group by substring(submitted,1,10)";
	$sql2 = "select count(*) from gridimage_search where moderation_status = 'geograph' $andri";
		
	$geograph = calc($sql,$sql2,10000);
		
	$smarty->assign("geograph",$geograph);
	
	
	
	$sql = "select substring(post_time,1,10) as d ,count(*) as c from geobb_posts where  post_time > '$beginday' AND post_time < '$today' group by substring(post_time,1,10)";
	$sql2 = "select count(*) from geobb_posts";

	$post = calc($sql,$sql2,1000);
			
	$smarty->assign("post",$post);


	$sql = "select substring(signup_date,1,10) as d ,count(*) as c from user where rights <> '' and signup_date > '$beginday' AND signup_date < '$today' group by substring(signup_date,1,10)";
	$sql2 = "select count(*) from user where rights <> ''";

	$users = calc($sql,$sql2,1000);
			
	$smarty->assign("users",$users);
	
	
	$sql = "select substring(submitted,1,10) as d ,count(distinct grid_reference) as c from gridimage_search where ftf = 1 and submitted > '$beginday' AND submitted < '$today' group by substring(submitted,1,10)";
	$sql2 = "select count(distinct grid_reference) from gridimage_search $whereri";
			
	$square = calc($sql,$sql2,10000);
			
	$smarty->assign("square",$square);


	$sql = "select substring(submitted,1,10) as d ,count(*) as c from gridimage_search where ftf = 1 and submitted > '$beginday' AND submitted < '$today' $andri group by substring(submitted,1,10)";
	$sql2 = "select count(*) from gridimage_search where ftf = 1 $andri";

	$point = calc($sql,$sql2,10000);
			
	$smarty->assign("point",$point);


	$total['average'] = $point['total']; 
	$total['average_r'] = $point['total']; 
	$total['next'] = $db->CacheGetOne(24*3600*7,"select count(*) from gridsquare where percent_land > 0 $andri");
		
	$total['dif'] = $total['next'] - $total['count'];
		
	$total['weeks'] = $total['dif']/$total['average'];
	$total['weeks_r'] = floor($total['weeks']);

	$total['endtime'] = time() + ($total['weeks'] * 3600 * 24 * 7);
	
	$total['enddate'] = date("F Y",$total['endtime']);


	$smarty->assign("totall",$total);
}

$smarty->display($template, $cacheid);

function calc($sql,$sql2,$mult) {
	global $db;
	
	$array = $db->cachegetAll(3600 * 24,$sql);
	
	$total = 0;
	foreach ($array as $i => $r) {
		$total += $r['c'];
	}
	
	$image['total'] = $total;
	
	$image['average'] = $total / count($array);
	$image['average_r'] = floor($image['average']);
	
	$image['count'] = $db->getOne($sql2);
	
	$image['next'] = ceil($image['count']/$mult) * $mult;
	
	$image['dif'] = $image['next'] - $image['count'];
	
	$image['days'] = $image['dif']/$image['average'];
	$image['days_r'] = floor($image['days']);
	return $image;
}
	
?>