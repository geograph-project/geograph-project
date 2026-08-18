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
        $start = strtotime($param['date'].$param['extra']);
        $end = strtotime($param['date'].$param['extra2']);

        $start = $start.'000000000';  //as a nanosecond Unix epoch.
        $end = $end.'000000000';
}

//after already set date (so end still set!)
if (!empty($param['start'])) {
        $start = $param['start'];
}

############################################

$pattern = 'pattern `<ip> - <_> <_> "<_> <path> <_>" "<_>" <status> <_> "<_>" "<agent>"`';

function get_base_query($param, $add_pattern = false) {
	global $CONF, $pattern;

	$query = $CONF['loki_query'];

	if (!empty($param['string']))
		$query .= " |= \"".addslashes($param['string'])."\"";

	if (!empty($param['second']))
	        $query .= ' |= "'.str_replace('"','\"',$param['second']).'"';

	if (!empty($param['third']))
	        $query .= ' |= "'.str_replace('"','\"',$param['third']).'"';

        if (!empty($param['common'])) {
		//some internal requests
		$query .= ' != "Geograph Mobile Site"';
		$query .= ' != "Internal Request"';
		$query .= ' != "/stuff/related.json.php"'; //doesnt have a UA!
		//TODO, also have internal requests on places.json.php - can't exclude all of them thou, as it can be used by others too

		$query .= ' != "Amazon CloudFront"'; //these ARE real request, but as CloudFront hides the UA, no point counting here?
	}

	if (!empty($param['not']))
	        $query .= ' != "'.str_replace('"','\"',$param['not']).'"';

	if (!empty($param['not2']))
	        $query .= ' != "'.str_replace('"','\"',$param['not2']).'"';

	if (!empty($param['duration'])) // && strpos($param['base'],'nginx"'))
	        $query .= ' | regexp `" (?P<duration>\\d+\.\\d+) http` | duration > '.$param['duration'];

	if ($add_pattern || !empty($param['status']))
		$query .= " | $pattern";

	if (!empty($param['status']) && is_numeric($param['status']))
	        $query .= " | status={$param['status']}";

	if (!empty($param['ua']) && is_string($param['ua']))
		$query .= " | agent=~\"{$param['ua']}\"";

	if (!empty($param['ip']) && is_string($param['ip']))
		$query .= " | ip=~\"{$param['ip']}\"";

	return $query;
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

        $r = array();
        $r['count']=0;
        $r['max']=0;
        $r['status'] = @$json['status'];

	if (!empty($json['data']) && !empty($json['data']['result'])) {
		//split into multiple streams
		foreach ($json['data']['result'] as $idx => $result) {
			$r['count']+=count($result['values']);
                        foreach ($result['values'] as $line) {
				$r['max'] = max($r['max'],$line[0]);
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
	//we can't 'return' this, as we can work as a generator!
	$GLOBALS['loki_status'] = $r;
}

############################################

function getgroups($query, $grouper, $funct = 'rate', $period = '10m',  $fp = null, $start = null, $end = null, $as_array = false) {
	global $server, $param, $CONF;

	//convenience function to hide some common bots
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

	$query = "sum by ($grouper) ($funct($query [$period]))";

	if ($param['debug'])
		print "$query\n";

	$data = array(
		'query' => $query,
		'direction' => 'forward',
		'step' => $period, //this ensures only get one point per period. Otherwise get multiple points per period (I think so could plot a graph and it bar for the period)
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
			if ($as_array) {
				//dont 'flatten' and just return the whole array (may be a multi-group?)
				$group = $result['metric'];
			} else {
				if (!isset( $result['metric'][$grouper])) {
					//print_r($result);
					$group = '';
				} else
				$group = $result['metric'][$grouper];
			}
                        foreach ($result['values'] as $line) {
				//todo, this it outputing one line per value, maybe should be one line per group?
                                //$str = $group.','.implode(",",$line);
				$str = array($group, $line[0], $line[1]);
				if (!empty($fp)) {
					fwrite($fp,$str);
				} else {
					yield $str;
				}
                        }
		}
	}
}

function get_timing_groups($query, $grouper = '', $unwrap_field = 'timing', $period = '1h', $fp = null, $start = null, $end = null, $as_array = false) {
    global $param, $CONF;

    // Convenience function to hide common noise/bots
    if (empty($param['bot'])) {
        $query .= ' != "Googlebot"';
        $query .= ' != "bingbot/2.0"';
        $query .= ' != "archive.org_bot"';
        $query .= ' != "size=largest"';
    }

    // Stream selector handling
    if (!empty($param['stream'])) {
        $count = 0;
        $query = preg_replace('/stream="\w+"/', 'stream="'.$param['stream'].'"', $query, -1, $count);
        if ($count == 0) {
            $query .= ' | json | stream="'.$param['stream'].'"';
        }
    }

    // Pattern parser to extract the target field (e.g. timing)
    $pattern = '| pattern `<ip> - <_> <_> "<_> <path> <_>" "<_>" <status> <_> "<_>" "<agent>" <'.$unwrap_field.'> http`';

    // Build the query structure depending on whether grouping by a label or aggregating globally
    if (!empty($grouper)) {
        $query = "avg by ($grouper) (avg_over_time($query $pattern | unwrap $unwrap_field [$period]))";
    } else {
        $query = "avg(avg_over_time($query $pattern | unwrap $unwrap_field [$period]))";
    }

    if (!empty($param['debug'])) {
        print "$query\n";
    }

    $data = array(
        'query'     => $query,
        'direction' => 'forward',
        'step'      => $period, // Ensures 1 data point evaluated per period window
    );
    if (!empty($start)) $data['start'] = $start;
    if (!empty($end))   $data['end']   = $end;

    $url = "{$CONF['loki_address']}loki/api/v1/query_range?".http_build_query($data);
    if (!empty($param['debug'])) {
        print "$url\n";
    }

    if (isset($param['debug']) && $param['debug'] == '2') {
        exit;
    }

    $data = file_get_contents($url);
    $json = json_decode($data, true);

    if (!empty($param['debug'])) {
        print_r($json);
    }

    if (!empty($json['data']) && !empty($json['data']['result'])) {
        foreach ($json['data']['result'] as $result) {
            if ($as_array) {
                $group = $result['metric'];
            } else {
                if (!empty($grouper) && isset($result['metric'][$grouper])) {
                    $group = $result['metric'][$grouper];
                } else {
                    $group = 'all';
                }
            }

            foreach ($result['values'] as $line) {
                // $line[0] = Unix timestamp in nanoseconds, $line[1] = Average timing value
                $str = array($group, $line[0], $line[1]);

                if (!empty($fp)) {
                    fputcsv($fp, $str);
                } else {
                    yield $str;
                }
            }
        }
    }
}

