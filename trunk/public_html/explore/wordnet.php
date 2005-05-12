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

$len = intval($_GET['len']);
if (!$len)
	$len = 2;

if (preg_match('/^[\w ]+$/',$_GET['words']))
	$words = $_GET['words'];

$template='statistics_wordnet.tpl';
$cacheid='statistics|wordnet.'.$len.".".str_replace(' ','.',$words);

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*24; //24hr cache

if (!$smarty->is_cached($template, $cacheid))
{
	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');  
	#$db->debug = true;
	
	if ($_GET['words']) {
		$ids = $db->GetAssoc("SELECT DISTINCT gid,title FROM `wordnet` WHERE title > 0 AND words = ".$db->Quote(trim($_GET['words'])) );
		if (count($ids)) {
			$sql_crit = " AND gid IN(".implode(',',array_keys($ids)).")";
			$smarty->assign('words', trim($_GET['words']));
		}
	}
	$smarty->assign('len', $len);
	
	
	$handle = fopen("common-words.dat", "r");
	while ($handle && !feof($handle)) {
		$buffer = strtolower(rtrim(fgets($handle, 4096)));
		$common[$buffer]++;
	}
	

	
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
	
	$wordlist = $db->GetAssoc("SELECT REPLACE(words,' ','&nbsp;'),len,SUM(wordnet.title) as sum_title FROM `wordnet` INNER JOIN `gridimage` ON(gid = gridimage_id) WHERE wordnet.title > 0 AND len = $len AND submitted > date_sub(now(), interval 7 day) $sql_crit GROUP BY len,words ORDER BY sum_title desc LIMIT 50");
	#foreach($wordlist as $words=>$obj) {
	#	$total += $obj['sum_title'];
	#}
	#$avg = $total/count($wordlist);
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
	ksort($wordlist);	
	$smarty->assign_by_ref('wordlist', $wordlist);
	
	$size = $startsize;
	$sizedelta /= 2;
	
	$toplist = $db->GetAssoc("SELECT REPLACE(words,' ','&nbsp;'),len,SUM(title) as sum_title FROM `wordnet` WHERE title > 0 AND len = $len $sql_crit GROUP BY len,words ORDER BY sum_title desc LIMIT 100");
	foreach($toplist as $words=>$obj) {
		$count=0;
		foreach (explode('&nbsp;',$words) as $word) {
			if ($common[$word])
				$count++;
		}	

		$toplist[$words]['size'] = round($size); $size -= $sizedelta; 
		$hex = dechex($count*100);
		$toplist[$words]['color'] = $hex.$hex.$hex;
	}
	ksort($toplist);	
	$smarty->assign_by_ref('toplist', $toplist);
	
	
	$smarty->assign('generation_time', time());
	
}


$smarty->display($template, $cacheid);

	
?>
