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
 'string'=>'', 'not'=>'', 'second'=>'', 'status'=>false, 'common'=>1, 'duration'=>false,
 'min' => 100); //custom params for this script

//NOTE! $param['hours'] - only works for the hostname lookup. All other queries have custom time intervals
// defaults to use 48 hours, but sometimes there are bots with LOTS of IPs, and 48 hours is too much.

chdir(__DIR__);
require "./_loki-wrapper.inc.php";

$debug = (posix_isatty(STDOUT) || $param['debug']);

############################################

//pattern is setup inside get_base_query()
$grouper = 'agent'; //from the pattern

// json  doesn work with with pattern! stream is now in the 'base' query, so dont use this anyway
$param['stream'] = '';

############################################

//sets up common filters, from $param (including 'string')
$query = get_base_query($param, $add_pattern = true);

############################################

$db = GeographDatabaseConnection(true);

function myquote($in) {
        if (is_numeric($in))
                return $in;
        global $db;
        return $db->Quote($in);
}

if ($debug)
	print "q=$query\n";

$c = 0; $d = 0;
foreach (range(-14,-1) as $offset) {
	$d = date('Y-m-d',strtotime($offset.' day'));

	foreach (range(0,23) as $hour) {
		$key = sprintf('%s %02d:00:00', $d, $hour);

		//if getcount(key) continue;
		if ($db->getOne("SELECT `hour` FROM agents_by_hour WHERE `hour` = ".$db->Quote($key)) ) {
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

		$stat = array();
		$generator = getgroups($query, $grouper, 'count_over_time', $period = '10m',
		     $fp = null, $start, $end);

		foreach ($generator as $line) {
			list($agent,$time,$value) = $line;
			@$stat[$agent]+=$value;
		}

		if (empty($stat)) {
			print("$key no results!\n");
			continue;
		}

		$c++;
		$rows = array();
		foreach($stat as $ua => $count) {
		        if ($param['min'] && $count < $param['min'])
                		continue;

			$updates = array();
			$updates['hour'] = $key;
			$updates['useragent'] = $ua;
			$updates['hits'] = $count;
			$rows[] = $updates;
		}

		$str = "INSERT INTO agents_by_hour (`".implode("`,`",array_keys($updates))."`) VALUES "; $sep = "\n";
                foreach ($rows as $row) {
                        $str .= $sep.'('.implode(',',array_map('myquote',$row)).')';
                        $sep = ",\n";
                }
		$db->Execute($str);
		$affected = $db->Affected_Rows();
		$d =+ $affected;
		print "$key = $affected\n";
	}
}

print "Saved $c hours with $d row\n";

########################################
// this creates NEW useragents.
$sql = "
INSERT INTO agents_all_time (useragent,first_hour,last_hour,hours,hits,created)
SELECT useragent, min(hour) as first_hour,max(hour) as last_hour,count(*) hours, sum(hits) hits, NOW() AS created
FROM agents_by_hour GROUP BY useragent
";

//... also update last_hour,hits/count
$sql .= " on duplicate key update last_hour = VALUES(last_hour), hours=VALUES(hours), hits = VALUES(hits)";

if ($debug)
	print "$sql;\n";

 $db->Execute($sql);

print "new/updated agents = ".$db->Affected_Rows()."\n";

########################################
// set bots

$sql = "SELECT useragent FROM agents_all_time WHERE bot IS NULL LIMIT 1000";
$rows = $db->getAll($sql);
$c = 0;
if (!empty($rows))
	foreach ($rows as $row) {
		$bot = 1-appearsToBePerson2($row['useragent']);
		$sql = "UPDATE agents_all_time SET `bot` = $bot WHERE useragent = ".$db->Quote($row['useragent']);
		$db->Execute($sql);
		$c+=$db->Affected_Rows();
	}
print "Updated Bots = $c\n";

########################################
// set rotbots

$robots = array();
        //todo perhaps look over last 48 hours?? (not use start/end)
        $start2 = strtotime("-48 hour");
        $start2 = $start2.'000000000';  //as a nanosecond Unix epoch.
        $end2 = null; //now

                //intentionally NOT using $query, as dont want all the magic filters
        $generator = getgroups($CONF['loki_query']." |= \" /robots.txt \" | $pattern", $grouper, 'count_over_time', $period = '1h',
             $fp = null, $start2, $end2);
        foreach ($generator as $line) {
                list($agent,$time,$value) = $line;
                @$robots[$agent]+=$value;
        }

$c=0;
if (!empty($robots))
        foreach ($robots as $agent => $count) {
                $sql = "UPDATE agents_all_time SET `robots` = 1 WHERE useragent = ".$db->Quote($agent);
                $db->Execute($sql);
                $c+=$db->Affected_Rows();
        }
print "Updated RoBots = $c\n";

########################################
// set potmel

$robots = array();
        $generator = getgroups($CONF['loki_query']." |= \" /potmel.php \" | $pattern", $grouper, 'count_over_time', $period = '1h',
             $fp = null, $start2, $end2);
        foreach ($generator as $line) {
                list($agent,$time,$value) = $line;
                @$robots[$agent]+=$value;
        }

$c=0;
if (!empty($robots))
        foreach ($robots as $agent => $count) {
                $sql = "UPDATE agents_all_time SET `potmel` = 1 WHERE useragent = ".$db->Quote($agent);
                $db->Execute($sql);
                $c+=$db->Affected_Rows();
        }
print "Updated PotMel = $c\n";

########################################
// set potmelb

$robots = array();
        $generator = getgroups($CONF['loki_query']." |= \" /export.potmel.php \" | $pattern", $grouper, 'count_over_time', $period = '1h',
             $fp = null, $start2, $end2);
        foreach ($generator as $line) {
                list($agent,$time,$value) = $line;
                @$robots[$agent]+=$value;
        }

$c=0;
if (!empty($robots))
        foreach ($robots as $agent => $count) {
                $sql = "UPDATE agents_all_time SET `potmelb` = 1 WHERE useragent = ".$db->Quote($agent);
                $db->Execute($sql);
                $c+=$db->Affected_Rows();
        }
print "Updated PotMelB = $c\n";

########################################
// lookup if used by users

$sql = "SELECT last_hour,useragent FROM agents_all_time WHERE users IS NULL AND bot=0 AND useragent != '-' LIMIT 250";
$rows = $db->getAll($sql);
$c = 0;

		$grouper2 = 'uid';
		$pattern = 'pattern `<ip> - <uid> [<_>] "<_>`';

	//only gets users!
		$pattern .= ' | uid!=0| uid!="-"';

if (!empty($rows))
        foreach ($rows as $row) {
		$agent = addslashes($row['useragent']);

		if ($debug)
			print "-- \"$agent\" \n";
		//exit;

			//probablty should be 'is_ascii'?
			// its mainly to catch Skip 1\'\\x222000
		if (!preg_match('/^[\w :;()!\/\.,=\[\]@+-]+$/',$row['useragent'])) {
			if ($debug)
				print "Skip $agent\n";
			continue;
		}
		//we dont detect these as bots, as such, but actully we know probably not!
		if (preg_match('/SearchHelper|ddg_win|GoogleEarth|ruby-oembed/',$row['useragent'])) {
			if ($debug)
	        		print "Skip $agent\n";
		        continue;
		}

		//todo, perhaps could set start/end from `hour` for now just reuse start2/end2!
	        $end2 = strtotime($row['last_hour'])+(60*60);
		$start2 = $end2 - (48*60*60); //ie 48 hour window

		$start2 = $start2.'000000000';  //as a nanosecond Unix epoch.
                $end2 = $end2.'000000000';

		$generator = getgroups($CONF['loki_query']." |= \"$agent\" | $pattern", $grouper2, 'count_over_time', $period = '1h',
	             $fp = null, $start2, $end2);

		$stat = array();
	        foreach ($generator as $line) {
        	        list($uid,$time,$value) = $line;
	                @$stat[$uid]+=$value;
        	}

		$count = count($stat);
                $sql = "UPDATE agents_all_time SET `users`=$count WHERE useragent = ".$db->Quote($row['useragent']);

		if ($debug)
			print "$sql;\n";
                $db->Execute($sql);
                $c+=$db->Affected_Rows();
        }
print "Updated Users = $c\n";

########################################
//lookup the hostname for each agent - needs grouping by IP (so also counts ips!) 

//use LAST hour, the first hour, might still been ramped up! ??
$sql = "SELECT last_hour,useragent FROM agents_all_time WHERE hostname IS NULL AND useragent != '-' LIMIT 250";
$rows = $db->getAll($sql);
$c = 0;

		$grouper2 = 'ip';
		$pattern = "pattern `<ip> - <_> <_> \"<_> <_> <_>\" <_>`";

if (!empty($rows))
        foreach ($rows as $row) {
		$agent = addslashes($row['useragent']);

		if ($debug)
			print "-- \"$agent\" \n";
		//exit;

			//probablty should be 'is_ascii'?
			// its mainly to catch Skip 1\'\\x222000
		if (!preg_match('/^[\w :;()!\/\.,=\[\]@+-]+$/',$row['useragent'])) {
			if ($debug)
				print "Skip $agent\n";
			continue;
		}

		//todo, perhaps could set start/end from `hour` for now just reuse start2/end2!
	        $end2 = strtotime($row['last_hour'])+(60*60); //end of the hour!
		$start2 = $end2 - (48*60*60);

		//to allow moping up ones with too many rows at 48!
		if (!empty($param['hours']))
			 $start2 = $end2 - ($param['hours']*60*60);

		$start2 = $start2.'000000000';  //as a nanosecond Unix epoch.
                $end2 = $end2.'000000000';

		$generator = getgroups($CONF['loki_query']." |= \"$agent\" | $pattern", $grouper2, 'count_over_time', $period = '1h',
	             $fp = null, $start2, $end2);

		$stat = array();
	        foreach ($generator as $line) {
        	        list($ip,$time,$value) = $line;
	                @$stat[$ip]+=$value;
        	}

		if (empty($stat))
			continue;

		// this just continues with the last $ip set!
		if (strpos($ip,',') !== FALSE) {
                        $bits = explode(",",$ip);
                        $ip = trim($bits[0]);
                }

		$host = gethostbyaddr($ip);
		//if ($host == $ip) $host = '';
		$ip = $db->Quote($ip);
		$host = $db->Quote($host);
		$count = count($stat);
                $sql = "UPDATE agents_all_time SET `ips`=$count, hostname=$host, ip=INET6_ATON($ip) WHERE useragent = ".$db->Quote($row['useragent']);

		if ($debug)
			print "$sql;\n";
                $db->Execute($sql);
                $c+=$db->Affected_Rows();
        }
print "Updated Hosts = $c\n";

########################################
########################################
########################################
// the built in version works on SERVER not a passwed in UA

//NOTE: if update this function, then run
# UPDATE agents_all_time SET bot = NULL
//... so this function gets rerun, but could do ... WHERE useragent LIKE '%HTTrack%' to do it directly!

function appearsToBePerson2($user_agent) {
        if (empty($user_agent))
                return false;
        if ( (stripos($user_agent, 'http')===FALSE) &&
            (stripos($user_agent, 'bot')===FALSE) &&
            (strpos($user_agent, 'Preview')===FALSE) &&
            (stripos($user_agent, 'Magnus')===FALSE) &&
            (strpos($user_agent, 'curl')===FALSE) &&
            (strpos($user_agent, 'python-requests')===FALSE) &&
            (strpos($user_agent, 'LWP::Simple')===FALSE) &&
            (strpos($user_agent, 'Siege')===FALSE) &&
            (strpos($user_agent, 'HTTrack')===FALSE) &&
            (strpos($user_agent, 'HeadlessChrome')===FALSE) &&
            (strpos($user_agent, 'InspectionTool')===FALSE) &&
            (strpos($user_agent, 'The Knowledge AI')===FALSE) &&
            (strpos($user_agent, 'GoogleOther')===FALSE)
                )
                return true;

        return false;
}


