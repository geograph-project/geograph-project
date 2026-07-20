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

$param = array('debug'=>0, 'stream' => 'stdout', 'limit' => 5000, 'date' => '', 'extra'=>'', 'extra2'=> '+1 day', 'hours'=>0, 'minutes'=>0, 'bot'=>1, 'not'=>'', //these are handled by wrapper
 'second'=>'', 'status'=>false, 'count'=>100, 'save'=>false); //custom params for this script

chdir(__DIR__);
require "./_loki-wrapper.inc.php";

$db = GeographDatabaseConnection(true);

$skip = array(301,302,304,307,204,403,405);

$stat = array();

########################################################################################

function getallrows($start, $end) {
        global $param,$skip, $pattern,$grouper, $stat;

	############################################
	// standard key= URLs

	$param['string'] = "key=";
	$param['not'] = false;
	$param['common'] = false;

	//sets up common filters, from $param (including 'string')
	$query = get_base_query($param, $add_pattern = true);

	//use regex to get tye key, better tnan pattern
	$grouper = 'key';
	$query .= " | regexp `[?&]key=(?P<key>[a-zA-Z0-9_%@\\.-]+)`";

	if (empty($param['status'])) { //get_base_query will add a single status based on $param!
		foreach ($skip as $id)
			$query .= " | status!=\"$id\"";
	}

	print "q=$query\n";

	$generator = getgroups($query, $grouper, 'count_over_time', $period = '1h', $fp = null, $start, $end);
	foreach ($generator as $line) {
	        list($key,$time,$value) = $line;
		if ($key)  //in fact if they key is empty, lets NOT save that (let the keyless script catch them!)
		        @$stat[$key]+=$value;
	}

	############################################
	//restAPI has a more complex pattern!

	$param['string'] = "GET /api/";
	$param['not'] = "key="; //URLs with key= covered above!
	$param['common'] = true; //this excludes 'internal' requests, which are often keyless!

	$query = get_base_query($param, $add_pattern = true);
	$query .= " | regexp `(?i)/api/(?:photo/(?:[^?\\s/]+/){1}|Gridref/(?:[^?\\s/]+/){1}|latlong/(?:[^?\\s/]+/){2})(?P<key>[^?\s/]+)(?:\\s|\\?|$)`";
	//note, this will see all other APIs routes as keyless, but in practice they dont tend to be used by API users anyway.

	if (empty($param['status'])) {
		foreach ($skip as $id)
			$query .= " | status!=\"$id\"";
	}

	print "q=$query\n";

	$generator = getgroups($query, $grouper, 'count_over_time', $period = '1h', $fp = null, $start, $end);
	foreach ($generator as $line) {
	        list($key,$time,$value) = $line;
		if ($key)  //the first part is only API requests that declare a key, it naurally excludes calls wiuthout a API key, to be consistent, should exclude keyless /API/ requests too.
		        @$stat[$key]+=$value;
	}

	//... to include include keyless requests in the stats, either need to be consistent (and look for all keyless requests, eg on syndicator), and CAN then include them in /api/ URLs,
	//... but really looking at keyless requests should seperate process anyway, because in taht case need to look at useragent and/or IP etc to try to seperate out differetn 'actors'.

	############################################
}

########################################################################################

if (empty($param['save'])) {
        $stat = array();
        getallrows($start, $end); //calling with already setup defaults

	ksort($stat);

	foreach($stat as $path => $count) {
        	printf("%6d. %s\n",  $count, $path);
	}
	printf("%6d. %s (%d path)\n", array_sum($stat), 'TOTAL', count($stat));
	exit;
}

########################################################################################

$debug = true;

$hours_total = 0; $affected_total = 0;
foreach (range(-14,0) as $offset) {
        $d = date('Y-m-d',strtotime($offset.' day'));

        $last = 23;
        if ($offset == 0) { //today!
                $current = date('G');
                if ($current < 3)
                        break;
                $last = max(0, $current-3); //to allow for timezone?
        }

        foreach (range(0,$last) as $hour) {
                $key = sprintf('%s %02d:00:00', $d, $hour);

                //if getcount(key) continue;
                if ($db->getOne("SELECT `hour` FROM api_by_hour WHERE apikey IS NOT NULL AND `hour` = ".$db->Quote($key)) ) {
                        if ($debug)
                                print "$key - skipping\n";
                        continue;
                }

                $start = strtotime($key);
                $end = $start + 60*60;

                $start = $start.'000000000';  //as a nanosecond Unix epoch.
                $end = $end.'000000000';

                $end -= 1;

                if ($debug)
                        print "$key = s=$start, e=$end ... ";

                $stat = array(); //start again each hour!
                getallrows($start, $end); //now passing in specific timespan!

                if (empty($stat)) {
                        print("$key no results!\n");
                        continue;
                }

                $hours_total++;
                $rows = array();
                foreach($stat as $ua => $count) {
                        if (!empty($param['min']) && $count < $param['min'])
                                continue;

                        $updates = array();
                        $updates['hour'] = $key;
                        $updates['apikey'] = $ua;
                        $updates['hits'] = $count;
                        $rows[] = $updates;
                }


                $str = "INSERT INTO api_by_hour (`".implode("`,`",array_keys($updates))."`) VALUES "; $sep = "\n";
                foreach ($rows as $row) {
                        $str .= $sep.'('.implode(',',array_map('myquote',$row)).')';
                        $sep = ",\n";
                }
                $db->Execute($str);
                $affected = $db->Affected_Rows();
                $affected_total =+ $affected;
                print "$key = $affected\n";
        }
}

print "Saved $hours_total hours with $affected_total row\n";

$db->Execute("INSERT INTO api_all_time (apikey,ident,first_hour,last_hour,hours,hits,created)
SELECT apikey,ident, min(hour) as first_hour,max(hour) as last_hour,count(*) hours, sum(hits) hits, NOW() AS created
FROM api_by_hour WHERE apikey IS NOT NULL GROUP BY apikey
on duplicate key update last_hour = VALUES(last_hour), hours=VALUES(hours), hits = VALUES(hits)");

$affected = $db->Affected_Rows();
print "$affected Affected updating api_all_time\n";

#######################

function myquote($in) {
        if (is_numeric($in))
                return $in;
        global $db;
        return $db->Quote($in);
}


