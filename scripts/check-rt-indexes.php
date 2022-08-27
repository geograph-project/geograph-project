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
	'disk'=>true,
);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);

############################################

print(date('H:i:s')."\tUsing rt server: {$CONF['manticorert_host']}\n");

$rt = GeographSphinxConnection('manticorert',true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

############################################

if (!empty($param['disk'])) {
	$indexes = $rt->getAssoc("SHOW TABLES");
	$cluster = $rt->getRow("SHOW STATUS LIKE 'cluster%indexes'");
	$list = @explode(',',$cluster['Value']);

	$usage = array();
	$largest = 0;
	foreach ($indexes as $index => $type) {
		printf('%-25s %-10s %7s  ', $index, $type, in_array($index,$list)?'cluster':'');
		if ($type == 'local' || $type == 'rt') {
			$data = $rt->getAssoc("SHOW TABLE $index STATUS");
			$data['count'] = 1;
			foreach ($data as $key => $value)
				if (is_numeric($value) && strpos($key,'field_tokens') === FALSE)
					@$usage[$key] += $value;
			if ($data['disk_bytes'] > $largest)
				$largest = $data['disk_bytes'];

			printf('%16s %16s %16s', number_format($data['disk_bytes'],0), number_format($data['ram_bytes'],0), '/'.number_format($data['mem_limit'],0));
		}
		print "\n";
	}

	$usage['largest'] = $largest;

	printf('%25s %-10s %7s  ', 'Total', $usage['count'], count($list));
	printf('%16s %16s %16s', number_format($usage['disk_bytes'],0), number_format($usage['ram_bytes'],0), '/'.number_format($usage['mem_limit'],0));
	print "\n\n";

//todo, shouldnt be hardcoded
if ($param['config'] == 'www.geograph.org.uk') {
	$disk_limit = 10; //Gi
	$memory_limit = 1; //Gi
} else {
	//currently 1Gb is the volume size, and 0.25 is the memory limit set!
	$disk_limit = 1; //Gi
	$memory_limit = 0.25; //Gi
}


	$total = $disk_limit * 1024 * 1024 * 1024;
	$usage['available_disk'] = $total - $usage['disk_bytes'];
	if ($usage['available_disk'] < $largest* 3) {
		print "WARNING: only {$usage['available_disk']} disk free, but the latest index is $largest, which may cause disk issues (should be 3x available for safety)\n";
	}

	$total = $memory_limit * 1024 * 1024 * 1024;
	$usage['available_memory'] = $total - $usage['ram_bytes'] - @$usage['disk_mapped'];
	if ($usage['available_memory'] < 1024 * 1024 * 1024 * ($memory_limit*0.2)) {
		print "WARNING: only {$usage['available_memory']} ram free, but usage is {$usage['ram_bytes']}, which is getting close to $total\n";
	}

	if ($usage['mem_limit']+$usage['disk_mapped'] > $total) { //include disk_mapped as that is already used memory (ram from disk chunks)
		print "WARNING: total of mem_limit is larger than physical memory\n";
	}

	foreach ($usage as $key => $value)
		printf("%40s %s\n", $key, number_format($value,0));
	exit;
}

############################################


function ignoreErrorHandler()
{
	return true;
}




