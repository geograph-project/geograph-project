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

//these are the arguments we expect
$param=array(
	'timeout'=>14, //timeout in minutes
	'limit'=>5,	//number to do each time
	'skip'=>0,	//so can also process the middle
	'sleep'=>10,	//sleep time in seconds
	'load'=>4,	//maximum load average

	'run'=>0,
	'force'=>0,
	'nice'=>0,

	'epoch'=>'latest',
	'processTile'=>false,
	'processSingleTile'=>false,
);


$HELP = <<<ENDHELP

    --timeout=<minutes> : maximum runtime of script (14)
    --sleep=<seconds>   : seconds to sleep if load average exceeded (10)
    --limit=<number>    : number of items to process in each batch (5)
    --skip=<number>     : skip checking X records (0)
    --load=<loadavg>    : maximum load average (4)

    --run               : run for real (false)
    --force             : overright existing files (false)
    --nice              : run imagemagick commands via nice (false)

    --epoch=<rev>       : specific map epoch to work with (latest)

    --processTile       : take a 20k tile and create 4 x 81 2km tiles
    --processSingleTile=<number> : create 400 tiles of specified width from 20k tile (200)
ENDHELP;


chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


require_once('geograph/rastermapOS.class.php');


if (isset($param['processSingleTile']) && $param['processSingleTile'] === true) {
	$param['processSingleTile'] = 200;
}


$start_time = time();

$end_time = $start_time + (60*$param['timeout']);

$m = new RasterMapOS();

if (!empty($param['epoch']) && preg_match('/^[\w]+$/',$param['epoch'])) 
	$CONF['os50kepoch'] = $param['epoch']."/";

foreach ($param as $key => $value) {
	if ($value) {
		$_GET[$key] = $value;
	}
}


$root = $CONF['os50kimgpath'].$CONF['os50kepoch'].'tiffs/';
$lldh = opendir($root);
$c = 1;
$cs = 0;
while (($llfile = readdir($lldh)) !== false) {
	if (is_dir($root.$llfile) && strpos($llfile,'.') !== 0) {
		$folder = $llfile.'/';
		$tiledh = opendir($root.$folder);

		while (($tilefile = readdir($tiledh)) !== false) {
			if (is_file($root.$folder.$tilefile) && strpos($tilefile,'.TIF') !== FALSE) {
				$tile = str_replace(".TIF",'',$tilefile);
				if (($param['skip'] > 0) && ($cs < $param['skip'])) {
					$cs++;
					print "skip $tile\n";
					continue;
				}
				
				//sleep until calm if we've specified a load average
				if ($param['load']<100)
				{
					while (get_loadavg() > $param['load'])
					{
						sleep(10);
						if (time()>$end_time) 
							die('C');
					}
				}
				
				print "TILE=$tile\n";
				$r = true;
				
				if ($param['processTile']) {
					$m->processTile($tile,100,100);
					$m->processTile($tile,300,100);
					$m->processTile($tile,300,300);
					$r = $m->processTile($tile,100,300);
				}

				if ($param['processSingleTile'])
					$r = $m->processSingleTile($tile,$param['processSingleTile']);
					
				
				if ($r)
					$c++;
				if ($c > $param['limit']) {
					print "\n\nTerminated due to limit\n\n";
					exit;
				}
				
				if (time()>$end_time) {
					$recordSet->Close(); 
					//well come to the end of the scripts useful life
					print "\n\nTerminated due to timeout\n\n";
				}
				
				sleep($param['sleep']);
			}
		}
	}
}

