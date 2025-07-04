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
	'rt'=>false, //override rt host
	'disk'=>true,
);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);

############################################

//if setup to use the balancer, we actully need a worker to get actual status info
$CONF['manticorert_host'] = str_replace('balancer','worker', $CONF['manticorert_host']);

if (!empty($param['rt']))
      $CONF['manticorert_host'] = $param['rt'];
print(date('H:i:s')."\tUsing rt server: {$CONF['manticorert_host']}\n");

$rt = GeographSphinxConnection('manticorert',true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

############################################

if (!empty($param['disk'])) {
	$indexes = $rt->getAssoc("SHOW TABLES");
	$cluster = $rt->getRow("SHOW STATUS LIKE 'cluster%indexes'");
	$list = @explode(',',$cluster['Value']);

	print "Index\t\t\tType\t\tCluster\t\tDisk Bytes\tRam Bytes\tMem Limit\n";

	$usage = array();
	$largest = 0;
	$count = 0;
	foreach ($indexes as $index => $type) {
		printf('%-25s %-10s %7s  ', $index, $type, in_array($index,$list)?'cluster':'');
		if ($type == 'local' || $type == 'rt') {
			$data = $rt->getAssoc("SHOW TABLE $index STATUS");
			$count++;
			foreach ($data as $key => $value)
				if (is_numeric($value) && strpos($key,'field_tokens') === FALSE && strpos($key,'tid') !== 0)
					@$usage[$key] += $value;
			if ($data['disk_bytes'] > $largest)
				$largest = $data['disk_bytes'];

			printf('%16s %16s %16s', number_format($data['disk_bytes'],0), number_format($data['ram_bytes'],0), '/'.number_format(($data['mem_limit']??0) + ($data['disk_mapped']??0), 0));
		}
		print "\n";
	}

	$usage['largest'] = $largest;

	printf('%25s %-10s %7s  ', 'Total', $count, count($list));
	printf('%16s %16s %16s', number_format($usage['disk_bytes'],0), number_format($usage['ram_bytes'],0), '/'.number_format(($usage['mem_limit']??0) + ($usage['disk_mapped']??0), 0));
	print "\n\n";

//todo, shouldnt be hardcoded
if (strpos($CONF['manticorert_host'], '.production.')) {
	$disk_limit = 10; //Gi
	$memory_limit = 1; //Gi

} elseif (strpos($CONF['manticorert_host'], '.dev.')) {
	$disk_limit = 1; //Gi
	$memory_limit = 0.5; //Gi

} else { //staging
	//currently 1Gb is the volume size, and 0.25 is the memory limit set!
	$disk_limit = 1; //Gi
	$memory_limit = 0.25; //Gi
}

	/////////////////////////////////
	//DISK usage

	$usage['total_disk'] = $total = $disk_limit * 1024 * 1024 * 1024;
	$usage['available_disk'] = $total - $usage['disk_bytes'];
	if ($usage['available_disk'] < $largest* 3) {
		print "WARNING: only {$usage['available_disk']} disk free, but the latest index is $largest, which may cause disk issues (should be 3x available for safety)\n";
	}
	$usage['available_disk_percent'] = ($usage['available_disk'] / $total ) * 100;

	$perdoc = $usage['disk_bytes'] / $usage['indexed_documents'];
	$usage['estimated_documents_disk'] = $total / $perdoc;

	/////////////////////////////////
	//CURRENT MEMORY usage

	$usage['total_memory'] = $total = $memory_limit * 1024 * 1024 * 1024;
	$usage['available_memory'] = $total - $usage['ram_bytes'];
	$usage['available_memory_percent'] = ($usage['available_memory'] / $total ) * 100;
	if ($usage['available_memory'] < 1024 * 1024 * 1024 * ($memory_limit*0.2)) {
		print "WARNING: only {$usage['available_memory']} ram free, but usage is {$usage['ram_bytes']}, which is getting close to $total\n";
	}

	/////////////////////////////////
	//WORST MEMORY usage

	//So really ram_bytes = disk_mapped_cached + ram_chunk + (bit extra for headers etc)
	//âpeakâ(worst case) memory usage woudl be basically = disk_mapped + mem_limit + (bit extra for headers etc)
	$usage['headers_memory'] = $usage['ram_bytes'] - $usage['disk_mapped_cached'] - $usage['ram_chunk'];  //deducts current uage to get remained
	$usage['worse_memory'] = $usage['mem_limit']+$usage['disk_mapped']+$usage['headers_memory']; //add that raminer to wrost, to get total

	if ($usage['worse_memory'] > $total) { //includes disk_mapped as that POTENTIAL memory usage (ram from loading disk chunks)
		print "WARNING: total of mem_limit+disk_mapped+overhead is larger than physical memory\n";
	}

	//this is not accurate - parciullg if multiple indexes
	//while dos have some overhead built in as ignores currents size of ram_chunk, it doesnt allow general ooverhead (eg dont go over 80% usage, also ignores allowance for replication etc) 
	// so in practice, should bea  LOT lower!
	$perdoc = $usage['worse_memory'] / $usage['indexed_documents'];
	$total = $memory_limit * 1024 * 1024 * 1024;
	$usage['estimated_documents_memory'] = $total / $perdoc;

	/////////////////////////////////

	foreach ($usage as $key => $value)
		if ($value > 0)
			printf("%40s %s\n", $key, number_format($value,0));
	exit;
}

############################################


function ignoreErrorHandler()
{
	return true;
}




