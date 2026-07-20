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
 'second'=>'', 'status'=>200, 'save'=>false); //custom params for this script

chdir(__DIR__);
require "./_loki-wrapper.inc.php";

$db = GeographDatabaseConnection(true);

############################################
//this is is just testing, it provides queries to manually execute, so need to manaually copy paste (AFTER checking hte query is good!) 
// .. .will work towards automating, but need to figure out first!

if (!empty($param['save']) && $param['save']>10) {
	$data = $db->getAll("select substring_index(ident,' ',1) as fragment2, count(*),ident from api_all_time
	 where ident is not null and fragment IS null group by fragment2 having count(*) > 1 and fragment2 NOT in('-,','API','Ruby,')
	 and fragment2 NOT like 'Mozilla/%' and fragment2 NOT like 'curl/%' and fragment2 not like 'okhttp/%' and  fragment2 not like 'python-requests/%'");
	foreach ($data as $row) {
		$sql = "UPDATE api_all_time SET fragment = '{$row['fragment2']}' WHERE fragment IS NULL AND ident LIKE '{$row['fragment2']}%'";
		print "$sql;\n";
	}
	print "\n";
	$data = $db->getAll("select regexp_substr(ident,'compatible; (\\\\w+[\\\\w-]*\/\\\\d.\\\\d+)') as fragment2, count(*),ident from api_all_time
         where ident is not null and fragment IS null and ident like '%compatible;%+http%' group by fragment2");
	foreach ($data as $row) {
		$sql = "UPDATE api_all_time SET fragment = '{$row['fragment2']}' WHERE fragment IS NULL AND ident LIKE '%{$row['fragment2']}%'";
		print "$sql;\n";
	}
	print "\n";

	$data = $db->getAll("select regexp_substr(ident,'\\\\+https?://[^)]+') as fragment2, count(*),ident from api_all_time
	 where ident is not null and fragment IS null and ident like '%+http%' group by fragment2");
	foreach ($data as $row) {
		$sql = "UPDATE api_all_time SET fragment = '{$row['fragment2']}' WHERE fragment IS NULL AND ident LIKE '%{$row['fragment2']}%'";
		print "$sql;\n";
	}
	print "\n";

	$data = $db->getAll("select substring_index(ident,', ',-1) as fragment2,sum(hits),count(*),ident from api_all_time where ident is not null and fragment is null
	 group by substring_index(ident,', ',-1) having fragment2 NOT like '%Gecko%' order by ident");
	foreach ($data as $row) {
		$sql = "UPDATE api_all_time SET fragment = '{$row['fragment2']}' WHERE fragment IS NULL AND ident LIKE '%{$row['fragment2']}%'";
		print "$sql;\n";
	}
	print "\n";

	$data = $db->getAll("select one.fragment,two.ident from api_all_time one
	 inner join api_all_time two on (two.ident like CONCAT('%',one.fragment,'%'))
	 where length(one.fragment)>1 and two.fragment is null group by one.fragment");
	foreach ($data as $row) {
		$sql = "UPDATE api_all_time SET fragment = '{$row['fragment']}' WHERE fragment IS NULL AND ident LIKE '%{$row['fragment']}%'";
		print "$sql;\n";
	}
	print "\n";

	exit;
}

############################################

$skip = array(301,302,304,307,204,403,405);

$stat = array();
############################################

$endpoints = [
    'syndicator' => [
        'string' => 'GET /syndicator.php',
        'not' => 'key=', // Skip if they actually passed a key (that logged in sperate script!)
    ],
    'facet' => [
        'string'   => 'GET /api-facet',  //includes old, and new ql, and even vector
        'not' => 'key=', //have some extra rules below, to try to exclude 'internal' use!
    ],
    'api' => [
        'string'   => 'GET /api/',
        'not' => 'key=', //keys in the path, are excluded with regex below!
    ],

    'empty1' => [
        'string' => 'key=&', //specifically an empty key!
    ],
    'empty2' => [
        'string' => 'key= ',
    ],

//    'feeds' => [
//        'string'   => 'GET /feed/',
//        // Feeds rarely use key= query params, so no exclude needed
//    ],
    'description' => [
        'string'   => 'GET /stuff/description.json.php',
        'not' => 'key=',
    ],

/* .. think going to have to look at use reuse seperately (its also fetching images NOT, 
    'reuse' => [
        'string' => 'GET /reuse.php',
	'second' => 'download=',
        'not' => 'key=', //keys in the path, are excluded with regex below!
    ], */
    'rdf' => [
	'string' => 'GET /photo/',
	'second' => '.rdf '
    ],
/* .. too much noise, is linked on a rel=alternate!
    'kml' => [
	'string' => 'GET /photo/',
	'second' => '.kml '
    ], */
];

//this global is used by get_base_query!
$pattern = 'pattern `<ip> - <_> <_> "<_> <path> <_>" "<_>" <status> <_> "<referer>" "<agent>"`';
$grouper = "ip,referer,agent";

$param['common'] = true; //excludes common Internal requests

function getallrows($start, $end) {
	global $endpoints,$param,$skip, $pattern,$grouper, $stat;

	foreach ($endpoints as $name => $endpoint) {
		$param['string'] = $endpoint['string'];
		$param['second'] = $endpoint['second'] ?? false;
		$param['not'] = $endpoint['not'] ?? false; //always need to set something to overwrite previous!

		//sets up common filters, from $param (including 'string')
		$query = get_base_query($param, $add_pattern = true);

		if (empty($param['status'])) { //get_base_query will add a single status based on $param!
			foreach ($skip as $id)
				$query .= " | status!=\"$id\"";
		}

	if ($name == 'facet' || $name == 'description') {
		$query .= ' | referer!="https://www.geograph.org/"';

		//our own use of facet api!
	        $query .= ' | referer!~"https://(www|m)\\\\.geograph\\\\.(org\\\\.uk|ie)/.*"';
	}

	if ($name == 'api') {
		$query .= ' | referer!~".*submissions\\\\.php.*"'; //uses /api/Snippet without a key!
		$query .= ' != "/api/oembed"'; // as noted in <head>, it gets lots of requests from scrapers, probably too noisy to consider as someone using API. Real use with a key will be cought seperately.  
		$query .= " | regexp `(?i)/api/(?:photo/(?:[^?\\s/]+/){1}|Gridref/(?:[^?\\s/]+/){1}|latlong/(?:[^?\\s/]+/){2})(?P<key>[^?\s/]+)(?:\\s|\\?|$)`";
		$query .= ' | key=""';
	}

		print "q=$query\n";
	//continue;

		$generator = getgroups($query, $grouper, 'count_over_time', $period = '1h', $fp = null, $start, $end, $as_array=true); //treat key as arrays, os can be mutliple
		foreach ($generator as $line) {
	        	list($key,$time,$value) = $line;

			/* if ($key['agent'] == 'Glade/1.0 (getglade.co.uk)')
				$key = $key['agent'];
			elseif (strpos($key['agent'],'SemanticVisionsBot')
			||      strpos($key['agent'],'//www.FeedBurner.com')
			||      strpos($key['agent'],'ChatGPT'))
				$key = $key['agent'];
			else -- actully going to do this 'grouping' in post processing - using the 'fragment' idea!*/
				$key = implode(', ',$key);

		        @$stat[$key]+=$value;
		}
	}
}

############################################

if (empty($param['save'])) {
	$stat = array();
	getallrows($start, $end); //just pass in the automatic ones!

	ksort($stat);

	foreach($stat as $path => $count) {
        	printf("%6d. %s\n",  $count, $path);
	}
	printf("%6d. %s (%d path)\n", array_sum($stat), 'TOTAL', count($stat));
	exit;
}

$debug = false;

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
                if ($db->getOne("SELECT `hour` FROM api_by_hour WHERE ident IS NOT NULL AND `hour` = ".$db->Quote($key)) ) {
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
                        $updates['ident'] = $ua;
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
//break 2;

	}
//break;
}

print "Saved $hours_total hours with $affected_total row\n";

$db->Execute("INSERT INTO api_all_time (apikey,ident,first_hour,last_hour,hours,hits,created)
SELECT apikey,ident, min(hour) as first_hour,max(hour) as last_hour,count(*) hours, sum(hits) hits, NOW() AS created
FROM api_by_hour WHERE ident IS NOT NULL GROUP BY ident
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



