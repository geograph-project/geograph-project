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

$param = array();
$param['single'] = 0;

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

if (!empty($param['host'])) {
    $CONF['redis_host'] = $param['host'];
}
print(date('H:i:s')."\tUsing server: $CONF[redis_host]\n");

############################################

$smarty = new GeographPage;
$GLOBALS['memcached_res']->redis->debug=true;

############################################

if (!empty($param['single'])) {
	$m = $GLOBALS['memcached_res'];

		function test_keys($keys) {
			global $m,$CONF;
			foreach ($keys as $cache_file) {
				if (strpos($cache_file,'user0^3^') !==0)
					continue;
				$r = $m->redis->get($CONF['template'].$cache_file);
				print "$cache_file => ".strlen($r)." bytes\n";
			}
			print "\n";
		}

	$tpl_file = 'profile.tpl';
	$ab = floor($param['single']/10000);
	$cache_id = "user$ab|{$param['single']}";

                                        if (!preg_match('/\|$/',$cache_id))
                                                $cache_id .="|"; //our hashes always has | always on the end!

print "Test 1 scanning single template\n";


                                                $it = NULL;
                                                $keys = array();
                                                /* Don't ever return an empty array until we're done iterating */
						$start = microtime(true);
                                                $m->redis->setOption(Redis::OPT_SCAN, Redis::SCAN_RETRY);
                                                while($keys = $m->redis->hScan($CONF['template'].'tpl'.$tpl_file, $it, "$cache_id*")) {
							printf("hScan: %.3f (%d keys)\n",  microtime(true) - $start, @count($keys));
                                                        if (!empty($keys)) {
								test_keys($keys);
                                                        }
                                                }

print str_repeat('~',50)."\n\n";

print "Test 2 listing ALL teplate\n";

				$start = microtime(true);
                                $keys = $m->redis->hGetAll($CONF['template'].'tpl'.$tpl_file); //returns key/value array
				printf("hGetAll: %.3f (%d keys)\n",  microtime(true) - $start, @count($keys));
				test_keys($keys);

print str_repeat('~',50)."\n\n";

print "Test 3 listing all prefix {$CONF['template']}pr$cache_id\n";

				$start = microtime(true);
				$keys = array_keys($m->redis->hGetAll($CONF['template'].'pr'.$cache_id));
				printf("hGetAll: %.3f (%d keys)\n",  microtime(true) - $start, @count($keys));
				test_keys($keys);

print str_repeat('~',50)."\n\n";

	exit;
}

############################################

$template = "_mobile_end.tpl"; //just an arbitary and small file
$template = "_mobile_begin.tpl";
$cacheid = "img01|100|test";

foreach (range(1,3) as $r) {
	$cacheid = "img01|".rand(100,110)."|test";

	//sets the cache keys! (althgouh we dont need them to test scanning!)
	$test = $smarty->fetch($template,$cacheid);
}

$cacheid2 = "singualr";
$test = $smarty->fetch($template,$cacheid2);

print str_repeat('~',50)."\n\n";

//$smarty->clear_cache(null, "img$ab|{$gid}");
############################################

print "Test 1 clear_cache($template,$cacheid)\n";

print $smarty->clear_cache($template,$cacheid);

print str_repeat('~',50)."\n\n";

############################################

print "Test 2 clear_cache($template)\n";

print $smarty->clear_cache($template);

print str_repeat('~',50)."\n\n";

############################################
//$cacheid = "img01|100";
$cacheid = preg_replace('/\w+$/','',$cacheid);

print "Test 3 clear_cache(null, $cacheid)\n";

print $smarty->clear_cache(null, $cacheid);

print str_repeat('~',50)."\n\n";

############################################
$cacheid = "singualr";

print "Test 4 clear_cache($template,$cacheid)\n";

print $smarty->clear_cache($template,$cacheid);

print str_repeat('~',50)."\n\n";

############################################
$cacheid = "user0|3";

print "Test A clear_cache(null,$cacheid)\n";

print $smarty->clear_cache(null,$cacheid);

print str_repeat('~',50)."\n\n";

############################################
$cacheid = "img0|19";

print "Test B clear_cache(null,$cacheid)\n";

print $smarty->clear_cache(null,$cacheid);

print str_repeat('~',50)."\n\n";

############################################
