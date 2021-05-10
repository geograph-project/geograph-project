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

############################################

$param=array(
	'auto' => false, //experimental mode, that aims to automate daily downloads

	//main mode, to get loop and download to a filename
	'filename'=>false, //the filename to save the logs to!
	'all'=>false, //set to true so loops!
	'date'=>false, //date to download (eg 2021-05-04) parsed by strtotime
	'start'=>false, //can supply the full nanosecond timestamp, so can resume a aborted download

        'limit'=>10, //small number for testing, but set to high number, 5000 seems recommended

	'base'=>'{job="production/geograph", container="nginx"}', //a default query
	'string'=>false, //extra filter to apply
	'hours'=>false, //specify a number of hours to use with 'string' query. Defaults to one hour!

	//which stream to get
	'stream'=>'', //get the access_log, nginx container at least, access on stdout, and error on stderr!

	'debug'=>false,
);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

if (empty($CONF['loki_address']))
	die("Loki Not Configured\n");

############################################

	if ($param['auto']) {
		if (empty($CONF['s3_loki_bucket_path']))
			die("unknown folder");

		if (empty($filesystem)) //eventually gloabl will do this!
		        $filesystem = new FileSystem(); //sets up configuation automagically
		$source = "/tmp/loki-logs/";
		$destination = "/mnt/s3/fake/";

		$filesystem->buckets[$destination] = $CONF['s3_loki_bucket_path'];

		foreach (range(-16,-1) as $offset) {
			$d = date('Y-m-d',strtotime($offset.' day'));
			$base = "nginxaccess.$d.log";

			if ($filesystem->file_exists($destination.$base.".gz"))
				continue;

			if (!is_dir($source))
				mkdir($source);

			$cmd = "php ".__DIR__."/loki.php --filename=$source$base --date=$d --limit=5000 --all=1";
			if ($param['debug']) {
				print "$cmd --config={$param['config']}\n";
			} else {
				passthru($cmd);
			}
		}

		if (!empty($cmd)) { //actully did something!
			$cmd = "gzip $source*.log";
			print "$cmd\n";
			if (!$param['debug'])
				passthru($cmd);

			$cmd = "php ".__DIR__."/send-to-s3.php --src=$source --include='*.gz' --dst={$CONF['s3_loki_bucket_path']} --move=1 --dry=0";
			if ($param['debug']) {
				print "$cmd --config={$param['config']}\n";
			} else {
				passthru($cmd);
			}
		}
		exit;
	}

############################################

	elseif ($param['filename']) {
		$query = $param['base'];

		if ($param['string'])
	                $query .= ' |= "'.str_replace('"','\"',$param['string']).'"';

		$fp = fopen($param['filename'],'a');
		if (!$fp)
			die("unable to open $filename\n");


		$start = strtotime($param['date']);
		$end = strtotime($param['date']."+1 day");

		$start = $start.'000000000';  //as a nanosecond Unix epoch.
		$end = $end.'000000000';

		if (!empty($param['start'])) {
			$start = $param['start'];
		}

		$c =1;
		$sleep=0;
		while (1) {
			// getlogs($query, $fp = null, $limit = 5000, $start = null, $end = null) {
			$r = getlogs($query, $fp, $param['limit'], $start, $end);
			if (posix_isatty(STDOUT))
				printf("%d, count:%d, max:%s, last:%s\n", $c, $r['count'], $r['max'], $r['max']?date('r',$r['max']/1000000000):'');

			//todo if ($r['status'] != 'success') continue; //to retry the last, possibly after a long sleep!
			if (@$r['status'] != 'success') {
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

		//always output the last one!
		print_r($r);
		print "Written to {$param['filename']}\n";
	}


############################################

	//basic none-looping test function!
	elseif ($param['string']) {
		$query = $param['base'];
		$query .= ' |= "'.str_replace('"','\"',$param['string']).'"';


		$start = null;
		if (!empty($param['hours'])) {
			$start = strtotime("-{$param['hours']} hour");

			$start = $start.'000000000';  //as a nanosecond Unix epoch.
		}

		$r = getlogs($query, STDOUT, $param['limit'],$start);
		if (posix_isatty(STDOUT))
			print_r($r);
	}

############################################

	//fallback really small tester!
	else {
		$query = $param['base'];
		$query .= ' |= "\" 404 " '; //note the query is specifically matching agasint the encoded json!

		if (empty($param['stream'])) $param['stream'] = 'stdout';

		$r = getlogs($query, STDOUT, $param['limit']); //defaults to last hour!
		print "$query\n";
		if (posix_isatty(STDOUT))
			print_r($r);
	}

############################################

/*
/loki/api/v1/query_range is used to do a query over a range of time and accepts the following query parameters in the URL:

query: The LogQL query to perform
limit: The max number of entries to return
start: The start time for the query as a nanosecond Unix epoch. Defaults to one hour ago.
end: The end time for the query as a nanosecond Unix epoch. Defaults to now.
step: Query resolution step width in duration format or float number of seconds. duration refers to Prometheus duration strings of the form [0-9]+[smhdwy]. For example, 5m refers to a duration of 5 minutes. Defaults to a dynamic value based on start and end. Only applies to query types which produce a matrix response.
interval: Experimental, See Below Only return entries at (or greater than) the specified interval, can be a duration format or float number of seconds. Only applies to queries which produce a stream response.
direction: Determines the sort order of logs. Supported values are forward or backward. Defaults to backward.
*/


function getlogs($query, $fp = null, $limit = 5000, $start = null, $end = null) {
	global $server, $param, $CONF;

	//get the access_log, nginx container at least, access on stdout, and error on stderr!
	//$query .= ' | json | stream="stdout"';
	if ($param['stream']) {
		$count = 0;
		$query = preg_replace('/stream="\w+"/','stream="'.$param['stream'].'"',$query, -1, $count);
		if ($count == 0)
			$query .= ' | json | stream="'.$param['stream'].'"';
	}

	if ($param['debug'])
		print "$query\n";

	$data = array(
		'query' => $query,
		'limit' => $limit,
		'direction' => 'forward',
	);
	if (!empty($start)) $data['start'] = $start;
	if (!empty($end)) $data['end'] = $end;

	$url = "{$CONF['loki_address']}loki/api/v1/query_range?".http_build_query($data);
	if ($param['debug'])
		print "$url\n";

	if ($param['debug'] == '2')
		exit;

	//todo, perhaps should use some sort of streaming reader, rather than reading all into memory!
	$data = file_get_contents($url);

	$json = json_decode($data, true);
	if ($param['debug'])
		print_r($json);

	$r = array();
	$r['count']=0;
	$r['max']=0;
	$r['status'] = @$json['status'];
	if (!empty($json['data']) && !empty($json['data']['result'])) {
		//split into multiple streams
		foreach ($json['data']['result'] as $idx => $result) {
			@$r['streams'][$result['stream']['namespace'].'/'.$result['stream']['pod'].'/'.$result['stream']['component'].'/'.$result['stream']['container']]++;
			$r['count']+=count($result['values']);
			if (!empty($fp)) {
				/*
                            [values] => Array
                                (
                                    [0] => Array
                                        (
                                            [0] => 1620488339761435873
                                            [1] => {"log":"40.77.167.29 - 0 [08/May/2021:16:38:59 +0100] \"GET /emma1wainwright/ HTTP/1.1\" \"www.geograph.ie\" 404 2954 \"-\" \"Mozilla/5.0 (compatible; bingb
                                        )

				*/
				foreach ($result['values'] as $line) {
					$r['max'] = max($r['max'],$line[0]);
					$str = $line[1];
					if ($str[0] == '{') { //for some reasons our logs have become json encoded
						$d = json_decode($str,true);
						$str = $d['log'];
					}
					fwrite($fp,$str);
				}
			}
		}
	}
	return $r;
}
