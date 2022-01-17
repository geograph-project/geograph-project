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
    'reconnect'=>false,
    'sleep'=>false,
);

$ABORT_GLOBAL_EARLY=1; //avoids global.inc.php auto connecteding to redis to with "$memcache" variable

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

if ($param['host']) {
    $CONF['redis_host'] = $param['host'];
}
print(date('H:i:s')."\tUsing server: $CONF[redis_host]\n");

############################################

//we actully testing the 'memcache' inteface!
$namespace = 'test';
$mkey = $_SERVER['PHP_SELF'];
$payload = 'test';

$memcache = new MultiServerMemcache($CONF['memcache']['app']);

$period = $memcache->period_long;

############################################

//keep looping foever, 
while (1) {
	$sleep = rand(180,3600*6);

	print date('r')." Writing Key (should last $period seconds, testing every $sleep seconds)...\n";
	$start = time();
	$memcache->name_set($namespace,$mkey,$payload,false,$period);
	
	while (1) {
		print date('r')." Testing Key...\r";
		$end = time();
		$str = $memcache->name_get($namespace,$mkey);
		if ($str == $payload) {
			sleep($sleep);
			//create a new object, beause we been asleep!
			$memcache = new MultiServerMemcache($CONF['memcache']['app']);
		} else {
			$diff = $end-$start;
			print date('r')." Key Gone at $diff seconds!\n";
			break; //the inner loop only
		}
	}

}

############################################

