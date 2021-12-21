<?php

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

if (empty($CONF['loki_address']))
	die("Loki Not Configured\n");

############################################
// setup some default values, but the parent could do this itself

$start = null;
$end = null;

//number of hours
if (!empty($param['hours'])) {
        $start = strtotime("-{$param['hours']} hour");

        $start = $start.'000000000';  //as a nanosecond Unix epoch.
        $end = null; //now

//minutes
} elseif (!empty($param['minutes'])) {
        $start = strtotime("-{$param['minutes']} minute");

        $start = $start.'000000000';  //as a nanosecond Unix epoch.
        $end = null; //now

//a single day
} elseif (!empty($param['date'])) {
        $start = strtotime($param['date']);
        $end = strtotime($param['date']."+1 ".$param['diff']);

        $start = $start.'000000000';  //as a nanosecond Unix epoch.
        $end = $end.'000000000';
}

//after already set date (so end still set!)
if (!empty($param['start'])) {
        $start = $param['start'];
}

############################################

//main loop, WITHOUT $fp, its a generator, with yeald
// $generator = getlogs($query, $fp = null, $param['limit'], $start, $end);
// foreach ($generator as $str) {
//        if (preg_match('/"GET \/(\w+[\w\.-]+)?.*" 200 .*" (\d[\.\d]*) /',$str,$m)) {

// or can pass a filepointer to output the lines, can pass STDOUT, to directly print!

function getlogs($query, $fp = null, $limit = 5000, $start = null, $end = null) {
	global $server, $param, $CONF;

	if (empty($param['bot'])) {
		$query .= ' != "Googlebot"';
		$query .= ' != "bingbot/2.0"';
		$query .= ' != "archive.org_bot"';
		$query .= ' != "size=largest"'; //this is a special param that only wikimedia know about
	}

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

	if (!empty($json['data']) && !empty($json['data']['result'])) {
		//split into multiple streams
		foreach ($json['data']['result'] as $idx => $result) {
                        foreach ($result['values'] as $line) {
                                $str = $line[1];
                                if ($str[0] == '{') { //for some reasons our logs have become json encoded
                                        $d = json_decode($str,true);
                                        $str = $d['log'];
                                }
				if (!empty($fp)) {
					fwrite($fp,$str);
				} else {
					yield $str;
				}
                        }
		}
	}
}
