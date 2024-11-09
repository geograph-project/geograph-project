<?php
/**
 * $Project: GeoGraph $
 *
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2021 Barry Hunter (geo@barryhunter.co.uk)
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

$param = array('debug'=>0, 'test'=>'', 'days' => 7);

//NOTE! $param['hours'] - only works for the hostname lookup. All other queries have custom time intervals
// defaults to use 48 hours, but sometimes there are bots with LOTS of IPs, and 48 hours is too much.

chdir(__DIR__);
require "./_loki-wrapper.inc.php";

$debug = (posix_isatty(STDOUT) || $param['debug']);

############################################

$db = GeographDatabaseConnection(true);

function myquote($in) {
        if (is_numeric($in))
                return $in;
        global $db;
        return $db->Quote($in);
}

########################################
// set slope

$days = $param['days'];
$key = "{$days}day";
$interval = "INTERVAL ".($days+1)." DAY"; //add one, because wont be data for today!

$step = 8; //hour many hours per block in spark. Goal is to keep it under 9 chars!
$step = 24; //ideally, want to factor of 24*7, so get whole hours!

//we have to first get list of all hours
$keys = array();
foreach (range(-1 * ($days+1),-1) as $offset) {
        $d = date('Y-m-d',strtotime($offset.' day'));

        foreach (range(0,23) as $hour) {
		$keys[] = sprintf('%s %02d:00:00', $d, $hour);
	}
}

########################################

if (!empty($param['test'])) {
	if (strlen($param['test']) < 10) {
		$sparkkey = $param['test'];
	} else
		$sparkkey = $db->getOne("SELECT spark$key FROM agents_all_time WHERE useragent = ".$db->Quote($param['test']));

	if (empty($sparkkey))
		die("unknown spakr\n");

	$find = '';
	$repl = '';
	foreach(str_split($sparkkey) as $i => $d) {
		$find .= "(?:($d)|.)"; //if digit matches, captures, otherwise empty
		$repl .= "\\".($i+1);
	}

	$sorter = "regexp_replace(spark$key, ".$db->Quote($find).", ".$db->Quote($repl).")";
	$sql = "SELECT spark$key,useragent FROM agents_all_time WHERE spark$key IS NOT NULL ORDER BY LENGTH($sorter) DESC";
	$sql = "SELECT $sorter AS sorter, spark$key,useragent FROM agents_all_time WHERE spark$key IS NOT NULL ORDER BY LENGTH(sorter) DESC";

	foreach($db->getAll($sql) as $row)
		print implode("\t",$row)."\n";
	print "\n";
	exit;
}

########################################

$slopekey = "slope$key";
$data = $db->getAssoc("select useragent,hits,hours,hits/hours from agents_all_time where hits/hours>1000 and hours > 3 and last_hour > DATE_SUB(DATE(NOW()), $interval) and $slopekey IS NULL");

$c=0;
if (!empty($data))
        foreach ($data as $agent => $row) {
		$hours = $db->getAssoc("SELECT hour,hits FROM agents_by_hour WHERE useragent = ".$db->Quote($agent)." AND hour > DATE_SUB(DATE(NOW()), $interval) ORDER BY hour");

		$values = array();
                //loop though ALL hours, not the ones in $points - as still want zeros!
                foreach($keys as $hour)
                        $values[] = 0+@$hours[$hour];
		$reg = linear_regression(array_keys($values), array_values($values));
		$updates = array();
		$updates[] = "$slopekey = {$reg['slope']}";
//print_r($values);

		$top = max($values);
		$spark = array();
		foreach(range(0,count($values)-1,$step) as $i) { //step must be a factor of 24!
			$total = 0;
			foreach(range($i,$i+$step-1) as $i2)
				$total += $values[$i2];
			$total /= $step; //gets an average for the 8 hour period!

			$total = $total / $top * 100; //normalize to percentage of top/peak!
			$spark[] = floor(log($total+1)); //avoid 0!
		}
		$updates[] = "spark$key = ".$db->Quote(implode('',$spark));
//print_r($spark);


                $sql = "UPDATE agents_all_time SET ".implode(', ',$updates)." WHERE useragent = ".$db->Quote($agent);
print "$sql;\n";
//exit;
                $db->Execute($sql);
                $c+=$db->Affected_Rows();
        }
print "Updated Slope = $c\n";

########################################

###########

// https://halfelf.org/2017/linear-regressions-php/

function linear_regression( $x, $y ) {

    $n     = count($x);     // number of items in the array
    $x_sum = array_sum($x); // sum of all X values
    $y_sum = array_sum($y); // sum of all Y values

    $xx_sum = 0;
    $xy_sum = 0;

    for($i = 0; $i < $n; $i++) {
        $xy_sum += ( $x[$i]*$y[$i] );
        $xx_sum += ( $x[$i]*$x[$i] );
    }

    // Slope
    $slope = ( ( $n * $xy_sum ) - ( $x_sum * $y_sum ) ) / ( ( $n * $xx_sum ) - ( $x_sum * $x_sum ) );

    // calculate intercept
    $intercept = ( $y_sum - ( $slope * $x_sum ) ) / $n;

    return array(
        'slope'     => $slope,
        'intercept' => $intercept,
    );
}


