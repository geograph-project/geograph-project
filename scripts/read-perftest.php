<?php
/**
 * $Project: GeoGraph $
 * $Id: recreate_maps.php 2996 2007-01-20 21:39:07Z barry $
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

############################################

//these are the arguments we expect
$param=array(
	'host' => false,
	'time' => false,
	'ext' => false,
	'matrix' => false,
	'v' => false,
	'filter' => false,
	'csv' => 'results.csv',
);

$ABORT_GLOBAL_EARLY=1; //avoids global.inc.php auto connecteding to redis to with "$memcache" variable

chdir(__DIR__);
require "./_scripts.inc.php";

############################################
$count = array();
$total = array();
$values = array();

$h = fopen("../perftest/".$param['csv'], 'r');

	//	fputcsv($h, array(date('r'), $host, $param['config'], $basename, $recordSet->RecordCount(), $endtime - $starttime));
	//                         0            1          2             3              4                         5

	function dim($dim) {
		global $bits; //save passing as variable!
		switch($dim) {
			case 'hour':
	                        $dates = explode(':',$bits[0]);
        	                return $dates[0];
			case 'minute':
	                        $dates = explode(':',$bits[0]);
        	                return $dates[0].':'.$dates[1]; //omiit seconds!
			case 'host':
				$parts = explode('.',$bits[1]);
        	                return $parts[0];
			case 'config':
				return $bits[2];
			case 'basename':
				return $bits[3];
			case 'base':
				$parts = explode('.',$bits[3]);
        	                return $parts[0];
		}
		return 'unknown';
	}


############################################
//over time for single host

if (!empty($param['host'])) {

        while ($h && !feof($h)) {
                $bits = fgetcsv($h);
                if (!empty($bits[5]) && $bits[2] == $param['config'] && $bits[1] == $param['host']) {
			if (!empty($param['filter']) && strpos($bits[3],$param['filter']) ===FALSE)
				continue;

			$dates = explode(':',$bits[0]);
			$minute = $dates[0].':'.$dates[1]; //omiit seconds!

                        @$count[$minute]++;
                        @$total[$minute]+=$bits[5];
                }
        }

	foreach ($count as $minute => $c) {
		$t = $total[$minute];
		printf("%55s %05d  %5.3f\n", $minute, $c, $t/$c);
	}

############################################
//over time for multi host

} elseif (!empty($param['time'])) {

        while ($h && !feof($h)) {
                $bits = fgetcsv($h);
                if (!empty($bits[5]) && $bits[2] == $param['config']) {
			if (!empty($param['filter']) && strpos($bits[3],$param['filter']) ===FALSE)
				continue;

			$dates = explode(':',$bits[0]);
			$hour = $dates[0];

                        @$count[$hour][$bits[1]]++;
                        @$total[$hour][$bits[1]]+=$bits[5];
                }
        }

	foreach ($count as $hour => $hosts) {
		foreach ($hosts as $host => $c) {
			$t = $total[$hour][$host];
			printf("%s\t%s\t%d\t%7.5f\n", $hour.':00:00', $host, $c, $t/$c);
		}
	}


############################################
//generic two dimensional matrix

} elseif (!empty($param['v'])) {

	list($one,$two) = explode(',',$param['v']);

	$bases = array();
	$counter = 0; $last = 0;
	while ($h && !feof($h)) {
		$bits = fgetcsv($h);

		if (!empty($bits[5]) && $bits[2] == $param['config']) {
			if (!empty($param['ext']) && strpos($bits[3], $param['ext']) ===FALSE)
				continue;
			if (!empty($param['filter']) && strpos($bits[3],$param['filter']) ===FALSE)
				continue;

			$o = dim($one); $t = dim($two);
			@$count[$o][$t]++;
			@$total[$o][$t]+=$bits[5];
			@$bases[$t]++;
		}
	}

	print "$two\\$one\t";

	foreach ($count as $o => $rows) {
		print "C\t{$o}\t";
	}
	print "\n";

	foreach ($bases as $t => $dummy) {
		print str_replace(' ','_',$t)."\t";
		foreach ($count as $o => $rows) {
			if (!empty($rows[$t])) {
				$c = $rows[$t];
				$tt = $total[$o][$t];
				printf('(%d) %5.3f', $c, $tt/$c);
			} else {
				print ". .";
			}
			print "\t";
		}
		print "\n";
	}

############################################
//matrix of hosts+queries

} elseif (!empty($param['matrix']) || !empty($param['ext'])) {

	$bases = array();
	$counter = 0; $last = 0;
	while ($h && !feof($h)) {
		$bits = fgetcsv($h);

		if (!empty($bits[5]) && $bits[2] == $param['config']) {
			if (!empty($param['ext']) && strpos($bits[3], $param['ext']) ===FALSE)
				continue;
			if (!empty($param['filter']) && strpos($bits[3],$param['filter']) ===FALSE)
				continue;

			if (!empty($param['matrix'])) {
				$bits[3] = preg_replace('/\.\w+$/','',$bits[3]);
			}

			if (strpos($bits[1],'production-replica5') ===0) {
				$time = strtotime($bits[0]);
				if (($time - $last) > 1000)
					$counter++;
				$last = $time;
				$bits[1] = "test$counter";
			}

			@$count[$bits[1]][$bits[3]]++;
			@$total[$bits[1]][$bits[3]]+=$bits[5];
			@$bases[$bits[3]]++;
		}
	}

	print "base\t";

	foreach ($count as $host => $rows) {
		$bits = explode('.',$host);
		print "C\t{$bits[0]}\t";
	}
	print "\n";

	foreach ($bases as $base => $dummy) {
		print "$base\t";
		foreach ($count as $host => $rows) {
			if (!empty($rows[$base])) {
				$c = $rows[$base];
				$t = $total[$host][$base];
				printf('(%d) %5.3f', $c, $t/$c);
			} else {
				print ". .";
			}
			print "\t";
		}
		print "\n";
	}


############################################
//general summary

} else {

	while ($h && !feof($h)) {
		$bits = fgetcsv($h);
		if (!empty($bits[5]) && $bits[2] == $param['config']) {

			if (!empty($param['filter']) && strpos($bits[3],$param['filter']) ===FALSE)
				continue;

			@$count[$bits[1]][$bits[2]]++;
			@$total[$bits[1]][$bits[2]]+=$bits[5]; // keep a running total, so can calculate mean easily.
			@$values[$bits[1]][$bits[2]][] = $bits[5]; // collate all the values, to be able to work out the stdev
		}
	}

	ksort($count);

	foreach ($count as $host => $configs) {
		foreach ($configs as $config => $c) {
			$t = $total[$host][$config];
			printf("%65s %40s %05d  %5.3f +%.3f\n", $host, $config, $c, $mean = $t/$c,
				standard_deviation($values[$host][$config], $mean)  ); //can send the mean as already have it!
		}
	}

}

############################################

//print "count=".count($times)." <br>\n";
//print "total=".array_sum($times)." <br>\n";
//print "avg=".(array_sum($times)/count($times))." <br>\n";


//https://www.php.net/manual/en/function.stats-standard-deviation.php

function standard_deviation($aValues, $fMean=false, $bSample = false) {
	if (empty($fMean))
	    $fMean = array_sum($aValues) / count($aValues);

    $fVariance = 0.0;
    foreach ($aValues as $i)
    {
        $fVariance += pow($i - $fMean, 2);
    }
    $fVariance /= ( $bSample ? count($aValues) - 1 : count($aValues) );
    return (float) sqrt($fVariance);
}
