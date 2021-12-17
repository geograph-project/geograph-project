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

$param = array('debug'=>0, 'stream' => 'stdout', 'limit' => 5000, 'date' => '', 'diff'=>'hour', 'hours'=>0, //these are handled by wrapper
 'string'=>''); //custom params for this script

chdir(__DIR__);
require "./_loki-wrapper.inc.php";

############################################

//https://grafana.cloud.geograph.org.uk/explore?orgId=1&left=%5B%22now-12h%22,%22now%22,%22Loki%22,%7B%22expr%22:%22%7Bjob%3D%5C%22production%2Fgeograph%5C%22,%20container%3D%5C%22nginx%5C%22%7D%20%7C%20json%20%7C%20stream%3D%5C%22stdout%5C%22%20%7C%20pattern%20%60%3C_%3E%20-%20%3C_%3E%20%3C_%3E%20%5C%22%3Cmethod%3E%20%3Cpath%3E%20%3C_%3E%5C%22%20%5C%22%3C_%3E%5C%22%20%3Cstatus%3E%20%3C_%3E%20%5C%22%3C_%3E%5C%22%20%5C%22%3C_%3E%5C%22%60%20%7C%20status!%3D%5C%22200%5C%22%20%7C%20status!%3D%5C%22301%5C%22%20%7C%20status!%3D%5C%22302%5C%22%20%7C%20status!%3D%5C%22304%5C%22%20%7C%20status!%3D%5C%22204%5C%22%22,%22refId%22:%22A%22,%22range%22:true,%22maxLines%22:10%7D%5D 
//$query = '{job="production/geograph", container="nginx"} | json | stream="stdout" | pattern `<_> - <_> <_> "<method> <path> <_>" "<_>" <status> <_> "<_>" "<_>"` | status!="200" | status!="301" | 
//status!="302" | status!="304" | status!="307" | status!="204"'; todo, for some reason | status>=400 doesnt work (as status seems to be string, not a number, cant do range filters)

$skip = array(200,301,302,304,307,204,405);
$pattern = 'pattern `<_> - <_> <_> "<_> <path> <_>" "<_>" <status> <_> "<_>" "<_>"`';

$query = $CONF['loki_query']." | $pattern";
foreach ($skip as $id)
	$query .= " | status!=\"$id\"";

if (!empty($param['string'])) {
	$query .= " |= \"".addslashes($param['string'])."\"";
}

############################################

$generator = getlogs($query, $fp = null, $param['limit'], $start, $end);

$stat = array();
foreach ($generator as $line) {
	if (preg_match('/^([^ ]+) .* "([A-Z]+) (\/.*?) HTTP\/(\d\.\d)" "(\w+\.[\w\.]+)" (\d+) (\d+) "(.*?)" "(.*?)"/',$line,$m)) {
	//		   1            2         3           4            5            6     7     8       9

		if (!empty($param['string'])) {
			print "{$m[1]} {$m[3]} {$m[6]} \"{$m[8]}\" \"{$m[9]}\"\n";
			continue;
		}

		@$stat[$m[6]]['ip:'.$m[1]]++;
		@$stat[$m[6]]['url:'.$m[3]]++;
		@$stat[$m[6]]['ref:'.$m[8]]++;
		@$stat[$m[6]]['ua:'.$m[9]]++;
	}

 //   echo "$line\n";exit;
}

foreach ($stat as $status => $data) {
	arsort($data);
	if (count($data) > 10)
		$data = array_slice($data,0,10,true);
	print str_repeat("$status ",10)."\n";
	foreach ($data as $key => $value)
		printf("%4d. %s\n",$value,$key);

	print "\n\n";
}

