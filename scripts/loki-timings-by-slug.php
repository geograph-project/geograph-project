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

$param = array('debug'=>0, 'stream' => 'stdout', 'limit' => 5000, 'date' => '', 'extra'=>'', 'extra2'=> '+1 day', 'hours'=>0, 'bot'=>1, 'all'=>0, 'save'=>0, 'auto'=>false);

chdir(__DIR__);
require "./_loki-wrapper.inc.php";

if (!empty($param['save']))
	$db = GeographDatabaseConnection(false);


############################################

//TODO!
//should use get_base_query, and we now know how to process the duration inside loki, eg with a 'sum() by' query!

$query = '{job="production/geograph", container="nginx"} |= "Googlebot" |= " 200 " != " /sitemap"';
$agent = "Googlebot"; //for the log table!


if (!empty($param['auto'])) {

	$self = basename($argv[0]);
        foreach (range(-14,-1) as $offset) { //loki only keeps 16 days, but cant use 16, and day 16 will be partial! (and loki hard errors, if outside its time!)
               $d = date('Y-m-d',strtotime($offset.' day'));

		$c = $db->getOne("SELECT id FROM timings_by_day WHERE day = '$d' AND agent='$agent'");
		if ($c) continue;

		$cmd = "php $self --date=$d --limit={$param['limit']} --all=1 --save --config={$param['config']}";

		print "$cmd\n";
		if ($param['auto'] > 1)
			passthru($cmd);
	}
	exit;
}

############################################
$stat = array();

if (!empty($param['date'])) {

        //a single day
        $start = strtotime($param['date'].$param['extra']);
        $end = strtotime($param['date'].$param['extra2']);

        $start = $start.'000000000';  //as a nanosecond Unix epoch.
        $end = $end.'000000000';

	$fp = null;
                $c =1;
                $sleep=0;
                while (1) {
                        $generator = getlogs($query, $fp = null, $param['limit'], $start, $end);
			foreach ($generator as $str) {
				if (preg_match('/"GET \/(\w+[\w\.-]+)?.*" 200 .*" (\d[\.\d]*) /',$str,$m)) {
					if (preg_match('/sitemap.*\.gz/',$m[1]))
						$m[1] = "sitemap...gz";
					@$stat[$m[1]][] = $m[2];
				}
			}

			//note, should iterate the generator FIRST!
			$r = $GLOBALS['loki_status']; //getlogs can't return stats, as it a generator
                        if (posix_isatty(STDOUT) || !empty($param['stats']))
                                printf("%d, count:%d, max:%s, last:%s\n", $c, $r['count'], $r['max'], $r['max']?date('r',$r['max']/1000000000):'');
                        //todo if ($r['status'] != 'success') continue; //to retry the last, possibly after a long sleep!
                        if (@$r['status'] != 'success') {
                                if ($sleep > 16) {
                                        debug_message('[Geograph] Loki Fetch failure '.$start, print_r($param,true).print_r($r,true) );
                                        die("Aborting\n");
                                }
                                $sleep++;
                                $sleep*=2;
                                print "Failed! Needs to retry $start. Now sleeping for $sleep seconds.... ";
                                sleep($sleep);
                                print " and Trying again...\n";
                                continue;
                        }

                        if ($r['count'] < $param['limit']) //got all!
                                break;
                        if (empty($param['all']))
                                break;

                        $start = $r['max']+1;
                        $c++;
                        $sleep=0;
                }

	if (posix_isatty(STDOUT) || $param['stats'])
		print "\n\n";

############################################

} else {
	$generator = getlogs($query, $fp = null, $param['limit'], $start, $end);

	foreach ($generator as $str) {
		if (preg_match('/"GET \/(\w+[\w\.-]+)?.*" 200 .*" (\d[\.\d]*) /',$str,$m)) {
			if (preg_match('/sitemap.*\.gz/',$m[1]))
				$m[1] = "sitemap...gz";
			@$stat[$m[1]][] = $m[2];
		}
	}
}

############################################
$rows = array();

print "                                   Slug  Min  Avg  Max  Count  p90\n";
foreach ($stat as $slug => $data) {
	$sum = array_sum($data);
	$min = min($data);
	$max = max($data);
	$cnt = count($data);

        // Calculate 90th percentile
        $p90 = percentile($data, 90);

	printf("%40s  %6.3f  %6.3f  %6.3f  (%5d)  %6.3f\n", $slug, $min, $sum/$cnt, $max, $cnt, $p90);

	if (!empty($param['save'])) {
		$updates = array();
                $updates['agent'] = $agent;
		$updates['day'] = $param['date'];
		$updates['slug'] = $slug;
                $updates['tmin'] = $min;
                $updates['tavg'] = $sum/$cnt;
                $updates['tmax'] = $max;
                $updates['cnt'] = $cnt;
                $rows[] = $updates;
	}
}
print "\n";

if (!empty($rows)) {
        $str = "INSERT INTO timings_by_day (`".implode("`,`",array_keys($updates))."`) VALUES "; $sep = "\n";
        foreach ($rows as $row) {
                $str .= $sep.'('.implode(',',array_map('myquote',$row)).')';
                $sep = ",\n";
	}
	$db->Execute($str);
	$affected = $db->Affected_Rows();
	print "$affected saved\n";
}


#######################

function myquote($in) {
        if (is_numeric($in))
                return $in;
        global $db;
        return $db->Quote($in);
}


/**
 * Calculates the Nth percentile of an array of numbers using linear interpolation.
 */
function percentile(array $data, float $percentile): float {
    if (empty($data)) {
        return 0.0;
    }

    sort($data);
    $count = count($data);

    if ($count === 1) {
        return (float)$data[0];
    }

    // Index position corresponding to the percentile
    $index = ($percentile / 100) * ($count - 1);
    $floor = floor($index);
    $fraction = $index - $floor;

    if (isset($data[$floor + 1])) {
        return $data[$floor] + $fraction * ($data[$floor + 1] - $data[$floor]);
    }

    return (float)$data[$floor];
}

/**
 * Calculates the mean after dropping the top X percentage of outliers.
 */
function trimmed_mean(array $data, float $trimPercent = 10.0): float {
    if (empty($data)) {
        return 0.0;
    }

    sort($data);
    $count = count($data);

    // Determine how many high values to drop
    $dropCount = (int) floor($count * ($trimPercent / 100));
    $trimmed = array_slice($data, 0, $count - $dropCount);

    return array_sum($trimmed) / count($trimmed);
}
