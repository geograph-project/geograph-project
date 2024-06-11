<?php
/**
 * $Project: GeoGraph $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2022 Barry Hunter (geo@barryhunter.co.uk)
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

//these are the arguments we expect
$param=array('s'=>false,
'print'=>true,'debug'=>false,'execute'=>false,'stats'=>1000,
'limit'=>10);


chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

##################################



        if (empty($redis)) {
                $redis = new Redis();
                $redis->connect($CONF['redis_host'], $CONF['redis_port']);
        }
        if (!empty($CONF['redis_api_db']))
                $redis->select($CONF['redis_api_db']);

        $mainkey = 'restapi';
        $prefix = 'r';

        if (!empty($param['s'])) {
                $mainkey = 'syndicator';
                $prefix = 's';
        }

##################################

        $data = $redis->hGetAll($mainkey);

	$updates = array();
	$updates['base'] = $mainkey;

##################################

	if ($param['print'] || $param['stats'])
		print "Found ".count($data)." top level Keys\n";

	$c= 0;
        foreach ($data as $key => $value) {
                $bits = explode('|',$key);

		$updates['apikey'] = $bits[0];
		$updates['ipaddr'] = $bits[1]; //toconver!
		$updates['refhost'] = $bits[2];
		$updates['useragent'] = @$bits[3];
		$updates['allkey'] = md5($key);

//2a00:23c8:b90d:1601:dceb:4094:4279:654d
//2a00:23c6:af1c:d701:1068:860:4208:a79e
//185.220.101.67
/// this is mainly because we have some records that are ipv4+port, which breaks inet6_aton()
if (!preg_match('/^\d+\.\d+\.\d+\.\d+$/',$updates['ipaddr']) && !preg_match('/^[a-f0-9]{4}(:[a-f0-9]{0,4}){4,7}$/',$updates['ipaddr'])) {
	print "Skipping $key\n";
	continue;
}

		if ($param['debug'])
			print_r($updates);
                $data2 = $redis->hGetAll($prefix.'|'.$key);

		if ($param['print'])
			print "Found ".count($data2)." keys for $key\n";

if (count($data2) > 10000) {
	print "Large keyspace [".count($data2)."] / $key\n";
//	print "skipping $key\n";
//	continue;
}
                foreach ($data2 as $date => $count) {
                         //@$stat[$date]+=$count;
			$bits = explode(' ',$date);
			$updates['date'] = $bits[0];
			$updates['hour'] = $bits[1];

			$updates['count'] = $count;
			if ($param['debug'])
				print_r($updates);

			$sql = "INSERT INTO api_usage_counter SET "; $sep = '';
			foreach ($updates as $k => $v) {
				if (is_numeric($v)) {
					$sql .= "$sep`$k` = $v";
				} elseif ($k == 'ipaddr') {
					$sql .= "$sep`$k` = inet6_aton(".$db->Quote($v).")";
				} else {
					$sql .= "$sep`$k` = ".$db->Quote($v);
				}
				$sep = ',';
			}
			if ($param['print'])
				print "$sql;\n";
			if ($param['execute'])
				$db->Execute($sql);
if ($param['debug'])
	break;
                }
		//
		if ($param['print']) {
			print "delete $mainkey :: $key\n";
			print "delete $prefix|$key\n";
		}
		if ($param['execute']) {
			$redis->hDel($mainkey,$key);
			$redis->del($prefix.'|'.$key);
		}

if ($param['debug'])
	break;

		$c++;
		if ($c > $param['limit'])
			exit;

		if ($param['stats'] && !($c%$param['stats']))
			print "Deleted $c Keys\n";
	}


