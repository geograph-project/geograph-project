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
    'rt'=>false, //override rt host
	'loops' => 2,
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

$h = fopen("../perftest/results-seige.csv", 'a');

$max = $db->getOne("SELECT MAX(gridimage_id) FROM gridimage_search");

$filenames = array();
$i = popen("grep -P '(7251001|394410|217210|ST9429)' ../perftest/photopage*",'r');
while ($i && !feof($i)) {
	$line = trim(fgets($i));
	if (empty($line))
		continue;
	list($filename,$line) = explode(':',$line);
	$filenames[] = $filename;
}

print_r($filenames);

$times = array();
$search = $replace = array();
foreach(range(1,$param['loops']) as $loop) {

	##################

	$gridimage_id = rand(4,$max);
	$search[0] = 7251001; $replace[0] = $gridimage_id;

	$row = $db->getRow("select gridimage_id,nateastings,natnorthings, reference_index, gridsquare_id, grid_reference from gridimage inner join gridsquare using (gridsquare_id) where gridimage_id = $gridimage_id");
	if (!empty($row) && $row['reference_index'] == 1) { //not ideal, but if no image, or ireland, then just leave the replace array untouched! (uses last location again!)
		$radius = 75000; //what created the files orginally!
		$e = 394410; $n = 129110;

                $left=$e-$radius;
                $right=$e+$radius;
                $top=$n-$radius;
                $bottom=$n+$radius;

		$search[1] = $e;   $replace[1] = $row['nateastings'];
		$search[2] = $n;   $replace[2] = $row['natnorthings'];
		$search[3] = $e-$radius; $replace[3] = $row['nateastings']-$radius;
		$search[4] = $e+$radius; $replace[4] = $row['nateastings']+$radius;
		$search[5] = $n-$radius; $replace[5] = $row['natnorthings']-$radius;
		$search[6] = $n+$radius; $replace[6] = $row['natnorthings']+$radius;

		$search[10] = 'ST9429'; $replace[10] = $row['grid_reference'];
		$search[11] = 217210;   $replace[11] = $row['gridsquare_id'];
	}

	##################

//$filenames = array("../perftest/photopage2.mysql");

	foreach($filenames as $filename) {
		$basename = basename($filename);
		$bits = explode(".",$basename);
		$ext = array_pop($bits);

		$query = file_get_contents($filename);

		$query = str_replace($search, $replace, $query);

		print $basename.':'.substr(preg_replace('/\s+/',' ',$query),0,80)."...\r";

		if ($ext == 'mysql') {
			if (!empty($param['sph']))
				continue;

			if ($host == $CONF['db_connect'] && $bits[0] == 'spatial2') //these queries are really slow, so lets not run them on the primary
				continue;

			$query = preg_replace('/^\s*select /i','SELECT SQL_NO_CACHE ',$query);


		         $starttime = microtime(true);
		         $recordSet = $db->Execute($query);
	        	 $endtime = microtime(true);

		         $times[] = $endtime - $starttime;

			fputcsv($h, array(date('r'), $host, $param['config'], $basename, $recordSet->RecordCount(), $endtime - $starttime, $gridimage_id));

		} elseif ($ext == 'sphinxql') {

		         $starttime = microtime(true);
		         $recordSet = $sph->Execute($query);
	        	 $endtime = microtime(true);

		         $times[] = $endtime - $starttime;

			fputcsv($h, array(date('r'), $CONF['sphinx_host'], $param['config'], $basename, $recordSet->RecordCount(), $endtime - $starttime, $gridimage_id));

		} elseif ($ext == 'sprt') {

		         $starttime = microtime(true);
	        	 $recordSet = $rt->Execute($query);
		         $endtime = microtime(true);

		         $times[] = $endtime - $starttime;

			fputcsv($h, array(date('r'), $CONF['manticorert_host'], $param['config'], $basename, $recordSet->RecordCount(), $endtime - $starttime, $gridimage_id));

		}
	}
}

print "\n";

############################################


print "count=".count($times)."\n";
print "total=".array_sum($times)."\n";
print "avg=".(array_sum($times)/count($times))."\n";


