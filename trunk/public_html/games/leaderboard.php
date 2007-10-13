<?php
/**
 * $Project: GeoGraph $
 * $Id: moversboard.php 3001 2007-01-22 19:30:41Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 BArry Hunter (geo@barryhunter.co.uk)
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

$template='games_leaderboard.tpl';

$l=inSetRequestInt('l',-1);

$limit = (isset($_GET['limit']) && is_numeric($_GET['limit']))?min(250,intval($_GET['limit'])):50;

$cacheid="$l.$limit";


if (isset($_GET['more'])) {
	$smarty->clear_cache($template, $cacheid);
} 

if (!$smarty->is_cached($template, $cacheid))
{
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed'); 
	
	/////////////
	if ($l > -1) {
		$where = "level = $l";
	} else {
		$where = 'level > 0';
	}
	$sql="select game_score_id,username,gs.user_id,realname,round(avg(level)) as level,sum(score) as score,sum(games) as games,sum(score)/sum(games) as average
	from game_score gs
		left join user using(user_id)
	where $where
	group by username,gs.user_id 
	order by average desc,score desc, games desc,username,realname ";
	if ($_GET['debug'])
		print $sql;
	$topusers=$db->GetAssoc($sql);
	
	//assign an ordinal

	$i=1;$lastscore = '?';
	$average = $games = $score = 0;
	foreach($topusers as $id=>$entry)
	{
		if ($lastscore == $entry['average'])
			if ($i > $limit) {
				unset($topusers[$idx]);
				continue;
			} else {
				$topusers[$id]['ordinal'] = '&quot;&nbsp;&nbsp;&nbsp;';
			}
		else {
			if ($i > $limit) {
				unset($topusers[$idx]);
				continue;
			} else {
				$topusers[$id]['ordinal'] = smarty_function_ordinal($i);
				$lastscore = $entry['average'];
			}
		}
		$i++;
		$average += $entry['average'];
		$score += $entry['score'];
		$games += $entry['games'];
	}	
	
	
	if ($i > 1) {
		$smarty->assign('average', sprintf("%.2f",$average/($i-1)));
	} else {
		$smarty->assign('average', 0);
	}
	$smarty->assign('score', $score);
	$smarty->assign('games', $games);
	$smarty->assign('l', $l);
	
	$smarty->assign_by_ref('topusers', $topusers);
	
	$smarty->assign('cutoff_time', time()-86400*7);
}

$smarty->display($template, $cacheid);

	
?>
