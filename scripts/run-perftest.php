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
    'host'=>false, //override mysql host
    'sph'=>false, //override sph host
    'verbose'=>false,
);

$ABORT_GLOBAL_EARLY=1; //avoids global.inc.php auto connecteding to redis to with "$memcache" variable

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$host = empty($CONF['db_read_connect'])?$CONF['db_connect']:$CONF['db_read_connect'];
if ($param['host']) {
    $host = $param['host'];
}
print(date('H:i:s')."\tUsing db server: $host\n");
$DSN_READ = str_replace($CONF['db_connect'],$host,$DSN);

//we've setup $DSN_READ, using $param[host] even if isn't a db_read_connect
$db = GeographDatabaseConnection(true);

############################################

if (!empty($param['sph']))
	$CONF['sphinx_host'] = $param['sph'];
print(date('H:i:s')."\tUsing sph server: {$CONF['sphinx_host']}\n");

//uses $CONF['sphinx_host']
$sph = GeographSphinxConnection('sphinxql',true);

############################################

if (!empty($param['rt']))
        $CONF['manticorert_host'] = $param['rt'];
print(date('H:i:s')."\tUsing rt server: {$CONF['manticorert_host']}\n");

$rt = GeographSphinxConnection('manticorert',true);

############################################

$h = fopen("../perftest/results.csv", 'a');

$times = array();
foreach (glob("../perftest/*.*") as $filename) {
	if(preg_match('/\.csv$/',$filename)) //wont be processed, but might as well skip even loading it!
		continue;

	$basename = basename($filename);
	$bits = explode(".",$basename);

if ($bits[0] == 'spatial2' && strpos($host,'test') == FALSE)
	continue;

	$ext = array_pop($bits);
	$query = file_get_contents($filename);

	print $basename.':'.substr(preg_replace('/\s+/',' ',$query),0,80)."....\r";

	$starttime = $endtime = 0;
	if ($ext == 'mysql') {
		if (!empty($param['sph']))
			continue;

		if ($host == $CONF['db_connect'] && $bits[0] == 'spatial2') //these queries are really slow, so lets not run them on the primary
			continue;

		if (strpos($query,'SQL_NO_CACHE') === FALSE)
			$query = preg_replace('/^\s*select /i','SELECT SQL_NO_CACHE ',$query);


	         $starttime = microtime(true);
	         $recordSet = $db->Execute($query);
	         $endtime = microtime(true);

	         $times[] = $endtime - $starttime;

		fputcsv($h, array(date('r'), $host, $param['config'], $basename, $recordSet->RecordCount(), $endtime - $starttime));

	} elseif ($ext == 'sphinxql') {

	         $starttime = microtime(true);
	         $recordSet = $sph->Execute($query);
	         $endtime = microtime(true);

	         $times[] = $endtime - $starttime;

		fputcsv($h, array(date('r'), $CONF['sphinx_host'], $param['config'], $basename, $recordSet->RecordCount(), $endtime - $starttime));

	} elseif ($ext == 'sprt') {

	         $starttime = microtime(true);
	         $recordSet = $rt->Execute($query);
	         $endtime = microtime(true);

	         $times[] = $endtime - $starttime;

		fputcsv($h, array(date('r'), $CONF['manticorert_host'], $param['config'], $basename, $recordSet->RecordCount(), $endtime - $starttime));

	}

	if (!empty($param['verbose'])) {
		printf("%50s  %6d   %5.3f  \n", $basename, $recordSet->RecordCount(), $endtime - $starttime);
	}

}

print "\n";

############################################


print "count=".count($times)."\n";
print "total=".array_sum($times)."\n";
print "avg=".(array_sum($times)/count($times))."\n";


