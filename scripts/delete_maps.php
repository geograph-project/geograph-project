<?php
/**
 * $Project: GeoGraph $
 * $Id: delete_maps.php 6103 2009-11-21 20:51:33Z barry $
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

//these are the arguments we expect
$param=array(
	'action'=>'nonalign',

	'timeout'=>14, //timeout in minutes
	'sleep'=>10,	//sleep time in seconds
	'load'=>100,	//maximum load average
	'base'=>1,	//delete the basemaps?
	'dryrun'=>0,	//test only?
);

$HELP = <<<ENDHELP
    --action=<action>   : one of 'nonalign' (default) or 'recent'
    --timeout=<minutes> : maximum runtime of script (14)
    --sleep=<seconds>   : seconds to sleep if load average exceeded (10)
    --load=<loadavg>    : maximum load average (100)
    --base=1/0          : delete the basemap (1)
    --dryrun=1/0        : dont actully delete (0)
ENDHELP;


############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


require_once('geograph/gridimage.class.php');
require_once('geograph/gridsquare.class.php');
require_once('geograph/map.class.php');
require_once('geograph/image.inc.php');
require_once('geograph/mapmosaic.class.php');

$start_time = time();

$end_time = $start_time + (60*$param['timeout']);

$total = 0;

if ($param['action'] == 'nonalign') { 
	$prefixes = $db->GetAll("select * from gridprefix order by rand();");


	foreach($prefixes as $idx=>$prefix) {

		//sleep until calm if we've specified a load average
		if ($param['load']<100)
		{
			while (get_loadavg() > $param['load'])
			{
				sleep($param['sleep']);
				if (time()>$end_time) 
					exit;	
			}
		}

		//mysql might of closed the connection in the meantime if we reuse the same object
		$mosaic = new GeographMapMosaic;

		print "Starting {$prefix['prefix']}...\n";flush();

		$minx=$prefix['origin_x'];
		$maxx=$prefix['origin_x']+$prefix['width']-1;
		$miny=$prefix['origin_y'];
		$maxy=$prefix['origin_y']+$prefix['height']-1;

		$crit = "map_x between $minx and $maxx and ".
			"map_y between $miny and $maxy and ".
			"pixels_per_km >= 40 and ".
			"((map_x-{$prefix['origin_x']}) mod 5) != 0 and ".
			"((map_y-{$prefix['origin_y']}) mod 5) != 0";

		$count = $mosaic->deleteBySql($crit,$param['dryrun'],$param['base']);
		print "Deleted $count\n";

		$total += $count;

		if (time()>$end_time) {
			//well come to the end of the scripts useful life
			exit;	
		}
	}
} elseif ($param['action'] == 'recent') { 
	$crit = "type_or_user = -6 limit 500";
	
	while (1) {
		//sleep until calm if we've specified a load average
		if ($param['load']<100)
		{
			while (get_loadavg() > $param['load'])
			{
				sleep($param['sleep']);
				if (time()>$end_time) 
					exit;	
			}
		}
		//mysql might of closed the connection in the meantime if we reuse the same object
		$mosaic = new GeographMapMosaic;

		print "Starting...\n";flush();

		$count = $mosaic->deleteBySql($crit,$param['dryrun'],$param['base']);
		print "... Deleted $count\n";

		$total += $count;

	
		if (!$count) {
			break;
		} elseif (time()>$end_time) {
			//well come to the end of the scripts useful life
			exit;	
		}
		
	}
} else {
	die("unknown action\n");
}
 
print "Total: $total\n";

