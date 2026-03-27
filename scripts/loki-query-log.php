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

$param = array('debug'=>0, 'stream' => '', 'limit' => 5000, 'date' => '', 'extra'=>'', 'extra2'=> '+1 day', 'hours'=>0, 'minutes'=>0, 'bot'=>1, //these are handled by wrapper
'second' => '', 'third' => '', 'not' => '', 'not2' => '', //these are handled by wrapper
'string' => ' /search.php?form=simple&q=', 'status' => 307, //suitable query defaults
'inject' => false, 'count'=>100, 'classify' => false); //custom params for this script

//we look for status=307 specifically, to try to only get valid user queries, and 'execute' bots that got blocked!

chdir(__DIR__);
require "./_loki-wrapper.inc.php";

############################################

//sets up common filters, from $param (including 'string')
$query = get_base_query($param, $add_pattern = true);

//$param['status'] is handled automatically!

############################################

// Convert errors/notices to Exceptions
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

if (!empty($param['inject']))
	$db = GeographDatabaseConnection(false);



$generator = getlogs($query, $fp = null, $param['limit'], $start, $end);

$data = array();
foreach ($generator as $line) {

	if (preg_match('/^([\d.,: ]+) - (\w+|-) \[(\d+)\/(\w+)\/(\d+):(\d+):(\d+):(\d+) \+0\d00\] "(\w+) (.*?) HTTP\/\d.\d"( "[\w.]+")? (\d+) (\d+) "(.*?)" "(.*?)"\s?([\d.]*)\s?([\w]*)\s?([\w]*)\s(.*)/',$line,$m)) {
		list($all,$ip,$uid,$year,$month,$day,$hour,$min,$sec, $method,$url,$host,$status,$bytes,$referer,$agent,$time,$proto,$sessid,$purpose) = $m;

		//extrct parts from main url
		$r = parse_query($url, $referer);

		//need to convert month name!
		try {
                        $timestamp = DateTime::createFromFormat('d/M/Y H:i:s', "$year/$month/$day $hour:$min:$sec"); //in particular will NOT cope with : between date/time!
			$r['ts'] = $timestamp->format('Y-m-d H:i:s');
                } catch (ErrorException $e) {
                        echo "Error: " . $e->getMessage() . PHP_EOL;
                        print($m); // Uncomment to see the bad data
                        exit;
                }

		if (!empty($uid))
			$r['reg'] = 1;

		if (!empty($sessid))
			$r['sess'] = $sessid;
		else
			$r['sess'] = md5($ip."'".$uid);

		//some quick stats!
		if (!empty($r['query']))
			$data[$r['query']] = 1 + ($data[$r['query']] ?? 0);
//		else
//			print "fail: $all\n";

		if (!empty($param['inject']))
			$db->Execute('INSERT INTO query_log SET `'.implode('` = ?,`',array_keys($r)).'` = ?',array_values($r));

	}
}

print "ids = ".count($data)."\n";

	arsort($data);
	if (count($data) > $param['count'])
		$data = array_slice($data,0,$param['count'],true);

	foreach ($data as $key => $value)
		printf("%4d. %s   %s\n",$value,$key, $example[$key] ?? '');

	print "\n";

############################################

function parse_query($url, $referer = null) {
	$r = array();

		//extract a referer
		if (!empty($referer) && strlen($referer) > 1) { //skip "-"
			//a recursive call, to decide the referer!
			$rr = parse_query($referer);
			if (!empty($rr['query'])) {
				$r['ref'] = $rr['query'];
			}
		}

	$bits = parse_url($url);

	//of/ URLs!
	if (preg_match('/^\/of\/([^?]+)/',$bits['path'],$m)) {
		$r['form'] = 'of.php';
		$r['query'] = urldecode($m[1]);

	//search.php
	} elseif ($bits['path'] == '/search.php') {
		//note, that technically form=simple might redirect to /of/ need to figure that out!
		$r['form'] = 'search.php';

	//finder -- NOTE: does use push-state, so actully data for this search might have be recovered from logs for ajax requests!!
	} elseif ($bits['path'] == '/finder/finder.php') {
		$r['form'] = 'finder.php';

	//finder -- from the API request
	} elseif (strpos($url,'/api-facetql') === 0 && strpos($referer, "finder.php") !== FALSE) {
		//do check ia api-facet request, dont change form when moving from finder to elsewhere - only want request IN finder
		$r['form'] = 'finder.php';
		//query below will change to 'similarity' if a label query!


		//note, ref still works, as the API quest to facetql is made BEFORE pushstate, so ref is still the OLD query!

	//near/
	} elseif (preg_match('/^\/near\/([^?]+)/',$bits['path'],$m)) {
		$r['form'] = 'near.php';
		$r['location'] = urldecode($m[1]);

		//filter is recorded from query below
		//todo, record dist??

	//place/
	} elseif (preg_match('/^\/place\/([^?]+)/',$bits['path'],$m)) {
		//not sure HOW these should be recorded, but good enough for now??
		$r['form'] = 'nearest';
		$r['location'] = urldecode($m[1]);
	} else {
		$r['form'] = 'unknown'; //mainly to avoid a notice (which becomes fatal) below
	}

	//search.php?form=simple&q=fife&go=Find&type=on
	//of/ URL, with form=simple is specifically when selectd the 'Photos' radio in the search box at top!
		//of/?form=simple&q=fife&go=Find&type=on
	//but also, its the q param that want, even if query forst
		//of/one?q=two
	if (!empty($bits['query'])) {
		$q = array();
		parse_str($bits['query'], $q);

		//I think a q param will always take precedence!
		if (!empty($q['q'])) {
			if($r['form'] == 'of.php' && !empty($r['query'])) //  /of/original?q=new   ref=original, query = new !!
				$r['ref'] = $r['query']; //if was a query from 'of' then actully a referer if 'q' param!
			$r['query'] = $q['q'];
		}
		//else searchtext?
		//search.php, has split search boxes
		if (!empty($q['location'])) {
			$r['location'] = $q['location'];
		}

		if (!empty($q['filter']) && $r['form'] == 'near.php') {
			$r['query'] = $q['filter'];
		}

		if (!empty($q['loc']) && $r['form'] == 'finder.php') {
			$r['location'] = $q['loc'];
		}
		if (!empty($q['type']) && $r['form'] == 'finder.php' && $q['type'] == 'similarity') {
			$r['form'] = 'similarity';
		}

		if (!empty($q['match']) && $r['form'] == 'finder.php') {
			//this is from the API request!
			$r['query'] = $q['match'];
			if (!empty($q['geo']))
				$r['location'] = $q['geo'];
		}
		if (!empty($q['label']) && $r['form'] == 'finder.php') {
			$r['query'] = $q['label'];
			$r['form'] = 'similarity';
			if (!empty($q['geo']))
				$r['location'] = $q['geo'];
		}

	}
	/// unclear if should 'autosplit' {something} near {somwhere} queruies (in query) 

	return $r;
}


############################################

if ($param['classify']) {
	//use nowdoc to avoid needing to add more slashes!
	$queries = <<<'END'

update query_log set type = 'simplegr' where type is null and query regexp '^\\s*[A-Z]{1,2}\\s*\\d{2,5}\\s*\\d{2,5}\\s*$';

update query_log set type = 'decimal' where type is null and query regexp '^[0-9]+.[0-9]+,? +-?[0-9]+.[0-9]+$';

update query_log set type = 'centi' where type is null and query like 'centi(%';

update query_log set type = 'linked' where type is null and binary query LIKE 'windmill OR mill %';

update query_log set type = 'linked' where type is null and binary query regexp '\\w+ \\w+ [A-Z]+\\d+[A-Z]?$';

update query_log set type = 'junk' where type is null and junk=1;

update query_log
inner join sphinx_placenames on (query = Place)
set query_log.type = 'place'
where query_log.type is null;

UPDATE query_log
INNER JOIN os_gaz ON query_log.query = os_gaz.def_nam
SET query_log.type = 'place'
WHERE query_log.type IS NULL;

UPDATE query_log
INNER JOIN ie_open_places ON query_log.query = ie_open_places.name
SET query_log.type = 'place'
WHERE query_log.type IS NULL;

END;

	foreach(explode(";\n",$queries) as $query) {
		$query = trim($query);
		if (empty($query)) continue;
		print "-- $query ;\n";
		$db->Execute($query);
		print "=> ".$db->Affected_Rows()."\n";
	}
}



/*

update query_log set type = 'simplegr' where type is null and query regexp '^\\s*[A-Z]{1,2}\\s*\\d{2,5}\\s*\\d{2,5}\\s*$';


So run the 'simple' one first 
update query_log 
inner join sphinx_placenames on (query = Place)
set query_log.type = 'place'
where query_log.type is null and has_dup=0;

then run a more advanced one... 

update query_log 
inner join sphinx_placenames on (sphinx_placenames.Place LIKE CONCAT(trim(query_log.query), '/%'))
set query_log.type = 'place'
where query_log.type is null and has_dup=1;


Note might still want to do with has_dup=1, as some queries are of the form "Altry Burn/NX6799" (likely via 'place') 


... actully running seperate queris is better than using sphinx_placenames

-- 1. Update from GB source
UPDATE query_log 
INNER JOIN os_gaz ON query_log.query = os_gaz.def_nam
SET query_log.type = 'place'
WHERE query_log.type IS NULL;

-- 2. Update from Ireland source
UPDATE query_log 
INNER JOIN ie_open_places ON query_log.query = ie_open_places.name
SET query_log.type = 'place'
WHERE query_log.type IS NULL;


   and q.query NOT regexp '[@:|\\\\[]' AND q.query NOT like 'centi(%' AND q.query NOT like 'windmill OR mill %'
 42     and binary q.query NOT regexp '\\\\w+ \\\\w+ [A-Z]+\\\\d+[A-Z]?$'
 43     and binary q.query NOT regexp '^[A-Z]{1,2}\\\\d{4,10}$'
 44     and binary q.query NOT regexp '^[A-Z]{1,2} \\\\d{2,5} \\\\d{2,5}$'
 45     and binary q.query NOT regexp '^[0-9]+.[0-9]+, +-?[0-9]+.[0-9]+$'
 46     and junk = 0 and type IS NULL


update query_log set type = 'decimal' where type is null and query regexp '^[0-9]+.[0-9]+, +-?[0-9]+.[0-9]+$';
update query_log set type = 'centi' where type is null and query like 'centi(%';

update query_log set type = 'linked' where type is null and binary query LIKE 'windmill OR mill %';
update query_log set type = 'linked' where type is null and binary query regexp '\\w+ \\w+ [A-Z]+\\d+[A-Z]?$';

update query_log set type = 'junk' where type is null and junk=1;


update query_log set type = '' where type is null and query regexp '';
update query_log set type = '' where type is null and query regexp '';
update query_log set type = '' where type is null and query regexp '';
update query_log set type = '' where type is null and query regexp '';
update query_log set type = '' where type is null and query regexp '';






*/
