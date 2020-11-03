<?php
/**
 * $Project: GeoGraph $
 * $Id: recreate_maps.php 2996 2007-01-20 21:39:07Z barry $
 *
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Barry Hunter (geo@barryhunter.co.uk)
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

//these are the arguments we expect
$param=array(
        'loop'=>1,
    'host'=>false,
    'info'=>false,
);

$ABORT_GLOBAL_EARLY=1; //avoids global.inc.php auto connecteding to redis to with "$memcache" variable

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

if ($param['host']) {
    $CONF['redis_host'] = $param['host'];
}
print("Using server: $CONF[redis_host]\n");

############################################

//$redis_handler = new RedisServer($CONF['redis_host'], $CONF['redis_port']);

$redis = new Redis();
$redis->connect($CONF['redis_host'], $CONF['redis_port']);

############################################

if ($param['info']) {
	$r = $redis->info();
	foreach ($r as $key => $value) {
		printf("%-40s : %s\n", $key, $value);
	}
	exit;
}


############################################

//we now configure each use of memcache to use seperated redis dbs!
if (!empty($CONF['memcache']['smarty']['redis']))
	$redis->select($CONF['memcache']['smarty']['redis']);

$key = "basicuser0^1^0_100^%%BA^BAA^BAA323DD%%profile.tpl";
// this is the key generated by accessing http://staging.geograph.org.uk/profile/1 (NOT logged in) 
/// .. so visit that URL first, to add the data to cache!

$rediskey = @$CONF['memcache']['smarty']['p'].$key; //memcache handler uses the prefix at front



print "key = $rediskey   (db: {$CONF['memcache']['smarty']['redis']})\n";

############################################


foreach (range(1,$param['loop']) as $r) {


		$before = microtime(true);
		// $str = $redis_handler->Get($rediskey);

		$str = $redis->get($rediskey);

		$after = microtime(true);


		printf("\t %.3f seconds, %d bytes\n", $after-$before, strlen($str));

}

############################################

