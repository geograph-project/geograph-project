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


$len = (isset($_GET['len']) && is_numeric($_GET['len']))?max(0,min(3,intval($_GET['len']))):2;

$words = (isset($_GET['words']) && preg_match('/^[\w ]+$/',$_GET['words']))?$_GET['words']:'';

$u = (isset($_GET['u']) && is_numeric($_GET['u']))?intval($_GET['u']):0;

//bare minimum for the dynamic section
if ($u) {
	if ($u == -1) {
		if ($USER->registered)
			$u = $USER->user_id;
		else 
			$u = 0;
	}
	if ($u) {
		$profile=new GeographUser($u);
		$smarty->assign_by_ref('profile', $profile);
		$smarty->assign_by_ref('u', $u);
	}
}	
	
if (!empty($_GET['t'])) {
	$template='explore_wordnet_simple.tpl';
	$cacheid="explore|wordnet_simple$u.".$len.".".str_replace(' ','.',$words);
} else {	
	$template='explore_wordnet.tpl';
	$cacheid="explore|wordnet$u.".$len.".".str_replace(' ','.',$words);
}

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*24; //24hr cache

if (!$smarty->is_cached($template, $cacheid))
{
	//lets hobble this!
	header("HTTP/1.1 503 Service Unavailable");
	$smarty->assign('searchq',stripslashes($_GET['q']));
	$smarty->display('function_disabled.tpl');
	exit;
	
	
	$db=NewADOConnection($GLOBALS['DSN']);
	if (empty($db)) die('Database connection failed');  
	
	$sql_crit = '';
	$extra_link = '&amp;len='.$len;
	if (!empty($words)) {
		$ids = $db->cachegetAssoc(3600,"SELECT gid,title FROM `wordnet$len` WHERE title > 0 AND words = ".$db->Quote(trim($words)) );
		if (count($ids)) {
			$sql_crit = " AND gid IN(".implode(',',array_keys($ids)).")";
			$smarty->assign('words', trim($_GET['words']));
		}
	}
	$smarty->assign_by_ref('len', $len);
	
	if ($u) {
		$sql_crit .= " AND user_id = $u";
		$extra_link .= '&amp;u='.$u;
	}
	
	$common = array();
	$handle = fopen("common-words.dat", "r");
	while ($handle && !feof($handle)) {
		$buffer = strtolower(rtrim(fgets($handle, 4096)));
		$common[$buffer]=1;
	}
	$smarty->assign_by_ref('extra_link', $extra_link);

	
	if ($len == 1) { 
		$size = $startsize = 40;
		$sizedelta = 0.6;
	} else if ($len == 2) { 
		$size = $startsize = 30;
		$sizedelta = 0.3;
	} else {
		$size = $startsize = 28;
		$sizedelta = 0.3;
	}
	
	$having_crit = ($words)?'':'HAVING sum_title > 1';
	 	
	$wordlist = $db->cachegetAssoc(3600,"SELECT REPLACE(words,' ','&nbsp;'),COUNT(*) as sum_title,'size' FROM `wordnet$len` as wordnet INNER JOIN `gridimage` ON(gid = gridimage_id) WHERE submitted > date_sub(now(), interval 7 day) $sql_crit GROUP BY words $having_crit ORDER BY sum_title desc LIMIT 50");
	foreach($wordlist as $words=>$obj) {
		$count=0;
		foreach (explode('&nbsp;',$words) as $word) {
			if ($common[$word])
				$count++;
		}	
		
		$wordlist[$words]['size'] = round($size); $size -= $sizedelta; 
		//log ( ( ($obj['sum_title'] * ( 20/$avg ) / (4-($len-$count) ) - 4) )+5 ) * 5;
		
		$hex = dechex($count*100);
		$wordlist[$words]['color'] = $hex.$hex.$hex;
	}
	
	if (empty($_GET['t']))
		ksort($wordlist);
	$smarty->assign_by_ref('wordlist', $wordlist);
	
	$size = $startsize;
	$sizedelta /= 2;
	
	if ($u) {
		$sql_from = "INNER JOIN `gridimage` ON(gid = gridimage_id) ";
	}
	
	$toplist = $db->cachegetAssoc(3600,"SELECT REPLACE(words,' ','&nbsp;'),COUNT(*) as sum_title,'size' FROM `wordnet$len` as wordnet $sql_from WHERE 1 $sql_crit GROUP BY words $having_crit ORDER BY sum_title desc LIMIT 100");
	
	foreach($toplist as $words=>$obj) {
		$count=0;
		foreach (explode('&nbsp;',$words) as $word) {
			if (isset($common[$word]))
				$count++;
		}	

		$toplist[$words]['size'] = round($size); $size -= $sizedelta; 
		$hex = dechex($count*100);
		$toplist[$words]['color'] = $hex.$hex.$hex;
	}
	
	if (empty($_GET['t']))
		ksort($toplist);	
	$smarty->assign_by_ref('toplist', $toplist);
}


$smarty->display($template, $cacheid);

	
?>
