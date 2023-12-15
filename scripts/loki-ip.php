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

$param = array('debug'=>0, 'date' => '', 'extra'=>'', 'extra2'=> '+1 day', 'hours'=>0, 'minutes'=>0, 'bot'=>1, //these are handled by wrapper
 'string'=>'', 'not'=>'', 'second'=>'', 'status'=>false, 'ua'=>false); //custom params for this script

chdir(__DIR__);
require "./_loki-wrapper.inc.php";

############################################

//https://grafana.cloud.geograph.org.uk/explore?orgId=1&left=%5B%22now-12h%22,%22now%22,%22Loki%22,%7B%22expr%22:%22%7Bjob%3D%5C%22production%2Fgeograph%5C%22,%20container%3D%5C%22nginx%5C%22%7D%20%7C%20json%20%7C%20stream%3D%5C%22stdout%5C%22%20%7C%20pattern%20%60%3C_%3E%20-%20%3C_%3E%20%3C_%3E%20%5C%22%3Cmethod%3E%20%3Cpath%3E%20%3C_%3E%5C%22%20%5C%22%3C_%3E%5C%22%20%3Cstatus%3E%20%3C_%3E%20%5C%22%3C_%3E%5C%22%20%5C%22%3C_%3E%5C%22%60%20%7C%20status!%3D%5C%22200%5C%22%20%7C%20status!%3D%5C%22301%5C%22%20%7C%20status!%3D%5C%22302%5C%22%20%7C%20status!%3D%5C%22304%5C%22%20%7C%20status!%3D%5C%22204%5C%22%22,%22refId%22:%22A%22,%22range%22:true,%22maxLines%22:10%7D%5D 
//$query = '{job="production/geograph", container="nginx"} | json | stream="stdout" | pattern `<_> - <_> <_> "<method> <path> <_>" "<_>" <status> <_> "<_>" "<_>"` | status!="200" | status!="301" | 
//status!="302" | status!="304" | status!="307" | status!="204"'; todo, for some reason | status>=400 doesnt work (as status seems to be string, not a number, cant do range filters)

$grouper = 'ip'; //from the pattern above!

// json  doesn work with with pattern! so put stream into the 'base' query.
$param['stream'] = '';

############################################

//sets up common filters, from $param (including 'string')
$query = get_base_query($param, $add_pattern = true);

############################################

print "q=$query\n";

$stat = array();
/* https://grafana.com/blog/2021/01/11/how-to-use-logql-range-aggregations-in-loki/
rate(log-range): calculates the number of entries per second
count_over_time(log-range): counts the entries for each log stream within the given range
bytes_rate(log-range): calculates the number of bytes per second for each stream
bytes_over_time(log-range): counts the amount of bytes used by each log stream for a given range
*/
$generator = getgroups($query, $grouper, 'count_over_time', $period = '10m',
	     $fp = null, $start, $end);

foreach ($generator as $line) {
	//the function is only executed once loop once!

	list($ip,$time,$value) = $line;

	@$stat[$ip]+=$value;
}
//print_r($stat);

ksort($stat);

foreach($stat as $ip => $count) {
        printf("%6d. %s\n",  $count, $ip);
}
printf("%6d. %s (%d ips)\n", array_sum($stat), 'TOTAL', count($stat));
//print ".\n";



