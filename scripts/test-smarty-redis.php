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
