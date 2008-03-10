<?php
/**
 * $Project: GeoGraph $
 * $Id: totals.php 4220 2008-03-09 11:58:12Z barry $
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

$g=inSetRequestInt('g',1);

$template='games_statistics.tpl';
$cacheid=$g;

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*6; //6hour cache

if (!$smarty->is_cached($template, $cacheid))
{
	$smarty->assign('gamelist',array('0'=>'-all games-','1'=>'Mark It','2'=>'Place Memory'));
	
	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');  
	#$db->debug = true;

	if ($g > 0) {
		$wherewhere = " where game_id = $g";
		$andwhere = " and game_id = $g";
	} else {
		$wherewhere = $andwhere;
	}

	$stats= $db->GetRow("select 
			count(*) as images 
		from game_image_rate $wherewhere");
	
	$stats += $db->GetRow("select 
			count(distinct gridimage_id) as gone
		from game_rate where rating < 0 $andwhere");
	
	$stats += $db->GetRow("select 
			count(distinct user_id) as raters,
			count(*) as rates
		from game_rate $wherewhere");
	
	$stats += $db->GetRow("select 
			count(*) as rounds, 
			count(distinct if(user_id>0,user_id,concat(username,session))) as users_all,
			count(distinct user_id)-1 as users_users,
			sum(score) as tokens,
			sum(games) as plays_rounds
		from game_score $wherewhere");
	
	$stats += $db->GetRow("select 
			count(*) as wrounds, 
			count(distinct if(user_id>0,user_id,concat(username,session))) as wusers_all,
			count(distinct user_id)-1 as wusers_users,
			sum(score) as wtokens,
			sum(games) as wplays_rounds
		from game_score where created > date_sub(now(),interval 7 day) $andwhere");
	
	$stats += $db->GetRow("select 
			count(*) as plays_all
		from game_image_score $wherewhere");

	
	$smarty->assign_by_ref('stats', $stats);
	$smarty->assign('g', $g);	
}



$smarty->display($template, $cacheid);

	
?>