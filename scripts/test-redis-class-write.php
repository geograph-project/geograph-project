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
	'start'=>1,
        'loop'=>1,
	'life'=>3600, //seconds
	'bytes'=>265,
	'sleep'=>0,

    'host'=>false,
);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

if (empty($param['life']) || $param['life'] < 2 || $param['life'] > 10000) {
	die("life out of range");
}

############################################

if ($param['host']) {
    $CONF['redis_host'] = $param['host'];
}
print("Using server: $CONF[redis_host]\n");

############################################

//$redis_handler = new RedisServer($CONF['redis_host'], $CONF['redis_port']);

$redis = new Redis();
$redis->connect($CONF['redis_host'], $CONF['redis_port']);


$redis->select(13); //use a db not use for anything else

############################################

$c = max(1,intval($param['loop']/10));

$total = 0;
foreach (range($param['start'],$param['loop']) as $r) {

	$total += $param['bytes'];
	$str = str_repeat('#',$param['bytes']);

	$rediskey = "test$r-{$param['bytes']}";

		$before = microtime(true);

		$redis->setEx($rediskey,intval($param['life']),$str);

		$after = microtime(true);


		printf("\t %.3f seconds, %d bytes. Total: %d\n", $after-$before, strlen($str), $total);


	if (!($r%$c)) {
		print "checking...\n";
		$check = $redis->get($rediskey);
		if (strlen($check) != $param['bytes']) {
			die("Got ".strlen($check)." bytes back, for $rediskey\n");
		}
	}
	if ($param['sleep'])
		usleep($param['sleep']);
}

############################################

