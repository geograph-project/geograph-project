<?php
/**
 * $Project: GeoGraph $
 * $Id: conversion.php 2960 2007-01-15 14:33:27Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2008 Barry Hunter (geo@barryhunter.co.uk)
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

//these are the arguments we expect
$param=array(
);

$HELP = <<<ENDHELP
    --mode=exteral|geograph
    --sleep=<seconds>   : seconds to sleep between calls (0)
    --number=<number>   : number of items to process in each batch (10)
ENDHELP;


chdir(__DIR__);
require "./_scripts.inc.php";

###############################################

if (!file_exists('/var/www/geograph/count-top-pairs.txt')) {
	$db = GeographDatabaseConnection(false);
	$sph = GeographSphinxConnection('sphinxql',true);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	$tags = $db->getAssoc("SELECT tag_id,tag FROM tag where prefix = 'top' and status = 1 and canonical = 0");

	foreach ($tags as $id1 => $tag1)
		foreach ($tags as $id2 => $tag2)
			if ($id1 > $id2) { //ensures dont check self, but also means only check each pair once (in assending order) 
				$sql = "select count(*) from sample8 where context_ids in ($id1) and context_ids in ($id2)";
				$count = $sph->getOne($sql);
				print "$count | $tag1 | $tag2 \n";
			}

}

###############################################

$pairs = array();
$h = fopen('/var/www/geograph/count-top-pairs.txt','r');
while ($h && !feof($h)) {
        $line = trim(fgets($h));
        $bits = explode(' | ',$line);
        if (count($bits) == 3) {
		@$pairs[$bits[1]][$bits[2]] = intval($bits[0]);
		@$pairs[$bits[2]][$bits[1]] = intval($bits[0]);
        }
}
fclose($h);

$tops = array_keys($pairs);

###############################################

shuffle($tops);

$dists = $clusters = array();
foreach ($tops as $c => $top) {
	if (empty($clusters)) {
		//prime the first cluster, with the first top
		$clusters[] = array($top);
		continue;
	}
	$found = null;
	$counter = array();
	foreach ($clusters as $idx => $rows) {
		foreach ($rows as $other) {
			//overlaps if one pair is above a value
			//if (@$pairs[$top][$other] > 50000)
			//	$found = $idx;
			@$counter[$idx][] = $pairs[$top][$other];
		}
	}


	//look for a existing cluster with little overlap
	foreach ($counter as $idx => $row) {
                $avg = array_sum($row) / count($row);
print "$top = $idx = $avg\n";
		if ($avg < 10000)
			$found = $idx;
		$dists[] = $avg;
	}

	if (is_null($found)) {
		$clusters[] = array($top);
	} else {
		$clusters[$found][] = $top;
	}

/*
print_clusters($clusters);
//if ($c == 4)
//exit;
readline("continue?"); //just to pause the display
print "\n\n";
*/
continue;

	////////////////////////////////
	//if overlapp, start a new cluster
	if (!is_null($found)) {
		$clusters[] = array($top);

	//otherwise add to one with least overlap
	} else {
		$best = 0;
		$value = 9999999;
		foreach ($counter as $idx => $row) {
			$avg = array_sum($row) / count($row);
			if ($avg < $value) {
				$value = $avg;
				$best = $idx;
			}
		}
		$clusters[$best][] = $top;
	}



}


print_clusters($clusters);

$stat = array();
$mult = intval(max($dists) /10);
foreach($dists as $avg) {
	//$k = intval($avg/$mult)*$mult;
	$k = exp(intval(log($avg)));
	@$stat[$k]++;
}
ksort($stat);
print_r($stat);


################

function print_clusters($clusters) {
	foreach ($clusters as $idx => $row) {
		$row = array_map('despace',$row);
		printf("%2d : %2d : %s\n", $idx, count($row), implode(' ',$row));
	}
}


function despace($in) {
	return preg_replace('/[^\w]+/','',$in);
}
