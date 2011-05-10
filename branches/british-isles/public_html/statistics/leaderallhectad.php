<?php
/**
 * $Project: GeoGraph $
 * $Id: leaderhectad.php 2962 2007-01-15 15:20:01Z barry $
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


$type = (isset($_GET['type']) && preg_match('/^\w+$/' , $_GET['type']))?$_GET['type']:'every';


$smarty = new GeographPage;

$template='statistics_leaderallhectad.tpl';
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
	$hectads = $db->getAll("select 
	hectad,
	geosquares,
	landsquares,
	(geosquares * 100 / landsquares) as percentage
	from hectad_stat 
	where geosquares > 0 and geosquares >= landsquares
	order by percentage desc,landsquares desc,hectad");
	
	
	foreach ($hectads as $i => $hectad) {
		
		$users = $db->getAll("SELECT user_id,u.realname,
			images,
			geosquares,
			first_submitted,
			last_submitted
			FROM 
				hectad_user_stat
				INNER JOIN user u USING (user_id)
			WHERE 
				hectad LIKE '{$hectad['hectad']}'
			ORDER BY last_submitted DESC");
		
		$best_user = array();
		if ($type == 'every') {
			foreach ($users as $i => $user) {
				if ($user['geosquares'] == $hectad['landsquares']) {
					if (isset($topusers[$user['user_id']])) {
						$topusers[$user['user_id']]['imgcount']++;
						$topusers[$user['user_id']]['bigesthectad'] = max($topusers[$user['user_id']]['bigesthectad'],$hectad['landsquares']);
						array_push($topusers[$user['user_id']]['squares'],$hectad['hectad']."[".$hectad['landsquares']."]");
					} else {
						$topusers[$user['user_id']] = $user;
						$topusers[$user['user_id']]['imgcount'] = 1;
						$topusers[$user['user_id']]['bigesthectad'] = $hectad['landsquares'];
						$topusers[$user['user_id']]['squares'] = array($hectad['hectad']."[".$hectad['landsquares']."]");
					}
				}
			}
		} elseif ($type == 'most') {
			$max = 0;
			foreach ($users as $i => $user) {
				if ($user['geosquares'] > $max) {
					$max = $user['geosquares'];
					$best_user = $user;
				}
			}
		} 
		
		if (count($best_user)) {
			if ($best_user['geosquares'] == $hectad['landsquares']) {
				$landsquares = $hectad['landsquares'];
			} else {
				$landsquares = $best_user['geosquares'].'/'.$hectad['landsquares'];
			}
			if (isset($topusers[$best_user['user_id']])) {
				$topusers[$best_user['user_id']]['imgcount']++;
				$topusers[$best_user['user_id']]['bigesthectad'] = max($topusers[$best_user['user_id']]['bigesthectad'],$hectad['landsquares']);
				array_push($topusers[$best_user['user_id']]['squares'],$hectad['hectad']."[".$landsquares."]");
			} else {
				$topusers[$best_user['user_id']] = $best_user;
				$topusers[$best_user['user_id']]['imgcount'] = 1;
				$topusers[$best_user['user_id']]['bigesthectad'] = $hectad['landsquares'];
				$topusers[$best_user['user_id']]['squares'] = array($hectad['hectad']."[".$landsquares."]");
			}
		}
		
	
	}
	
	function cmp(&$a, &$b) 
	{
		global $topusers;
		if ($a['imgcount'] == $b['imgcount']) {
			if ($a['bigesthectad'] == $b['bigesthectad']) {
				return 0;
			}
			return ($a['bigesthectad'] > $b['bigesthectad']) ? -1 : 1;
		}
		return ($a['imgcount'] > $b['imgcount']) ? -1 : 1;
	}

	uasort($topusers, "cmp");
	
	$heading = "Hectads";
	if ($type == 'every') {
		$desc = "hectads contributed to every square";
	} elseif ($type == 'most') {
		$desc = "hectads where they are the contributor to the most squares";
	} 
	
	
	$lastimgcount = 0;
	$i = 1;
	foreach($topusers as $idx=>$entry)
	{
		if ($lastimgcount == $entry['imgcount']) {
			if ($i > 200) { //|| ($type != 'only' && $entry['imgcount'] == 1)
				unset($topusers[$idx]);
			} else {
				$topusers[$idx]['ordinal'] = '&nbsp;&nbsp;&nbsp;&quot;';
				$topusers[$idx]['hectads'] = implode(',', $entry['squares']);
			}
		} else {
			if ($i > 200) {
				unset($topusers[$idx]);
			} else {
				$topusers[$idx]['ordinal'] = smarty_function_ordinal($i);
				$topusers[$idx]['hectads'] = implode(',', $entry['squares']);
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

	$smarty->assign('types', array('every','most'));
	
	//lets find some recent photos
	new RecentImageList($smarty);
}

$smarty->display($template, $cacheid);

