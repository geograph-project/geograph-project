<?php
/**
 * $Project: GeoGraph $
 * $Id: leadermyriad.php 2962 2007-01-15 15:20:01Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Paul Dixon (paul@elphin.com)
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


$type = (isset($_GET['type']) && preg_match('/^\w+$/' , $_GET['type']))?$_GET['type']:'last';


$smarty = new GeographPage;

$template='statistics_leadermyriad.tpl';
$cacheid=$type;


$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*24; //24hour cache

if (!$smarty->is_cached($template, $cacheid))
{
	require_once('geograph/gridimage.class.php');
	require_once('geograph/gridsquare.class.php');
	require_once('geograph/imagelist.class.php');

        $db = GeographDatabaseConnection(true);
	
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$myriads = $db->CacheGetAll(3600,"select 
	substring(grid_reference,1,length(grid_reference)-4) as tenk_square,
	sum(has_geographs) as geograph_count,
	sum(percent_land >0) as land_count,
	(sum(has_geographs) * 100 / sum(percent_land >0)) as percentage
	from gridsquare 
	group by tenk_square 
	having geograph_count > 0 and percentage >=100
	order by percentage desc,land_count desc,tenk_square");
	
	
	foreach ($myriads as $i => $myriad) {
		$str = $myriad['tenk_square'].'%';
		
		$users = $db->CacheGetAll(3600,"SELECT gi.user_id,u.realname,
		COUNT(*) AS count,
		UNIX_TIMESTAMP(MIN(submitted)) as first_date,
		UNIX_TIMESTAMP(MAX(submitted)) as last_date
		FROM 
			gridimage_search gi
			INNER JOIN user u USING (user_id)
		WHERE 
			grid_reference LIKE '$str' AND
			moderation_status = 'geograph' AND
			ftf = 1
		GROUP BY user_id 
		ORDER BY last_date DESC");
		
		$best_user = array();
		if ($type == 'first') {
			$min = 999999999999999999999;
			foreach ($users as $i => $user) {
				if ($user['first_date'] < $min) {
					$min = $user['first_date'];
					$best_user = $user;
				}
			}
		} elseif ($type == 'most') {
			$max = 0;
			foreach ($users as $i => $user) {
				if ($user['count'] > $max) {
					$max = $user['count'];
					$best_user = $user;
				}
			}
		} elseif ($type == 'last') {
			$max = 0;
			foreach ($users as $i => $user) {
				if ($user['last_date'] > $max) {
					$max = $user['last_date'];
					$best_user = $user;
				}
			}
		} elseif ($type == 'only') {
			if (count($users) == 1) {
				$best_user = $users[0];
			}
		} elseif ($type == 'all') {
			foreach ($users as $i => $user) {
				if (isset($topusers[$user['user_id']])) {
					$topusers[$user['user_id']]['imgcount']++;
					$topusers[$best['user_id']]['bigestmyriad'] = max($topusers[$user['user_id']]['bigestmyriad'],$myriad['land_count']);
					array_push($topusers[$user['user_id']]['squares'],$myriad['tenk_square']."[".$myriad['land_count']."]");
				} else {
					$topusers[$user['user_id']] = $user;
					$topusers[$user['user_id']]['imgcount'] = 1;
					$topusers[$best['user_id']]['bigestmyriad'] = $myriad['land_count'];
					$topusers[$user['user_id']]['squares'] = array($myriad['tenk_square']."[".$myriad['land_count']."]");
				}
			}
		}
		
		if (count($best_user)) {
			if (isset($topusers[$best_user['user_id']])) {
				$topusers[$best_user['user_id']]['imgcount']++;
				$topusers[$best_user['user_id']]['bigestmyriad'] = max($topusers[$best_user['user_id']]['bigestmyriad'],$myriad['land_count']);
				array_push($topusers[$best_user['user_id']]['squares'],$myriad['tenk_square']."[".$myriad['land_count']."]");
			} else {
				$topusers[$best_user['user_id']] = $best_user;
				$topusers[$best_user['user_id']]['imgcount'] = 1;
				$topusers[$best_user['user_id']]['bigestmyriad'] = $myriad['land_count'];
				$topusers[$best_user['user_id']]['squares'] = array($myriad['tenk_square']."[".$myriad['land_count']."]");
			}
		}
	}
	
	function cmp(&$a, &$b) 
	{
		if ($a['imgcount'] == $b['imgcount']) {
			if ($a['bigestmyriad'] == $b['bigestmyriad']) {
				return 0;
			}
			return ($a['bigestmyriad'] > $b['bigestmyriad']) ? -1 : 1;
		}
		return ($a['imgcount'] > $b['imgcount']) ? -1 : 1;
	}

	uasort($topusers, "cmp");
	
	$heading = "Myriads";
	if ($type == 'first') {
		$desc = "completed myriads geographed first";
	} elseif ($type == 'most') {
		$desc = "completed myriads where they are the largest contributor";
	} elseif ($type == 'last') {
		$desc = "myriads finished";
	} elseif ($type == 'only') {
		$desc = "completed myriads where they are the ONLY contributor";
	} elseif ($type == 'all') {
		$desc = "completed myriads contributed to";
	} 
	
	$lastimgcount = 0;
	$i = 1;
	foreach($topusers as $idx=>$entry)
	{
		if ($lastimgcount == $entry['imgcount']) {
			if ($i > 200 || ($type == 'all' && $entry['imgcount'] == 1)) {
				unset($topusers[$idx]);
			} else {
				$topusers[$idx]['ordinal'] = '&nbsp;&nbsp;&nbsp;&quot;';
				$topusers[$idx]['myriads'] = implode(',', $entry['squares']);
			}
		} else {
			if ($i > 200 || ($type == 'all' && $entry['imgcount'] == 1)) {
				unset($topusers[$idx]);
			} else {
				$topusers[$idx]['ordinal'] = smarty_function_ordinal($i);
				$topusers[$idx]['myriads'] = implode(',', $entry['squares']);
			}
			$lastimgcount = $entry['imgcount'];
			$lastrank = $i;
		}
		$i++;
	}
	
	$smarty->assign_by_ref('topusers', $topusers);
	
	$smarty->assign('heading', $heading);
	$smarty->assign('desc', $desc);
	$smarty->assign('type', $type);

	$smarty->assign('types', array('first','most','last','only','all'));
	
	//lets find some recent photos
	new RecentImageList($smarty);
}

$smarty->display($template, $cacheid);

	
?>
