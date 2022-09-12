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
    'host'=>false,
    'verbose'=>false,
);

$ABORT_GLOBAL_EARLY=1; //avoids global.inc.php auto connecteding to redis to with "$memcache" variable

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(true);
if (!$db->readonly) {
        die("No database replica"); //at the moment only run against the replica (its a lofi way to check they dont modify data!)
}

############################################

$rows = $db->getAll("SELECT * FROM alarm WHERE active = 1 ORDER BY alarm_id DESC");

print "Found ".count($rows)." check(s)\n";
$good = $bad = 0;

foreach ($rows as $row) {
	if (!empty($row['url_head'])) {
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $row['url_head']);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_setopt($ch, CURLOPT_HEADER, true);

		$response = curl_exec($ch);
		if (curl_errno($ch)) {
		    echo 'Error:' . curl_error($ch);
		    exit();
		}
		curl_close($ch);
		$results = array(array());
		foreach (explode("\n",str_replace("\r",'',$response)) as $line)
			if (preg_match('/^(\w+[\w-]*\w): (.*)/',$line,$m))
				$results[0][$m[1]] = $m[2];

	} elseif (!empty($row['sql_query'])) {
		if (strpos($row['sql_query'],'$recent_ticket_item_id'))
			$row['sql_query'] = str_replace('$recent_ticket_item_id', $db->getOne("SELECT  max(gridimage_ticket_item_id)-1000 from gridimage_ticket_item"), $row['sql_query']);

		$results = $db->getAll($row['sql_query']);
	} else { //maybe some alarms, we can't test here!
		continue;
	}

	if (is_numeric($row['min_rows'])) //could be zero!
		result(count($results), count($results) >= $row['min_rows'], $row,'min_rows');
	if (is_numeric($row['max_rows']))
		result(count($results), count($results) <= $row['max_rows'], $row,'max_rows');

	foreach ($results as $result) {

		//checks "at least" this number
		if (!is_null($row['min_value'])) { //could be number or string!
			if (strpos($row['min_value'],'now') === 0) { //pretty fragile test
				$row['min_value'] = strtotime($row['min_value']);
				$result[$row['metric']] = strtotime($result[$row['metric']]);
			}

			$max_value = is_numeric($row['min_value'])?$row['min_value']:$result[$row['min_value']]; //find the max for this one line!

			result($result[$row['metric']], $result[$row['metric']] >= $max_value, $row, $result[$row['label']]);
		}

		//checks "at most"
		if (!is_null($row['max_value'])) { //could be number or string!
			$max_value = is_numeric($row['max_value'])?$row['max_value']:$result[$row['max_value']]; //find the max for this one line!

			result($result[$row['metric']], $result[$row['metric']] <= $max_value, $row, $result[$row['label']]);
		}
	}
}
print "$good test".(($good==1)?'':'s')." ok\n";

############################################

function result($value,$result,$row,$label) {
        global $good,$bad;
        $result?$good++:$bad++;

	($result)?is_ok($value,$row,$label):is_alarm($value,$row,$label);
}

function is_ok($value,$row,$label) {
	global $param;
	//todo, could still log the metric, even if not printing (for long term graphing)
	if (empty($param['verbose']))
		return;
	if (is_numeric($value))
		$value = number_format($value,preg_match('/\.\d{4,}/',$value)?3:0); //dont want to round ints!

	printf("%30s %-40s %s\t%s\n", $row['alarm_name'], $label, $value, 'ok');
}

function is_alarm($value,$row,$label) {
	if (is_numeric($value))
		$value = number_format($value,preg_match('/\.\d{4,}/',$value)?3:0); //dont want to round ints!

	printf("%30s %-40s %s\t%s\n", $row['alarm_name'], $label, $value, 'ALARM');
}



