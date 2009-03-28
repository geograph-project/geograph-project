<?php
/**
 * $Project: GeoGraph $
 * $Id: recreate_maps.php 3865 2007-10-23 20:20:52Z geograph $
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

/**
* get 1 minute load average
*/
function get_loadavg() 
{
	$uname = posix_uname();
	switch ($uname['sysname']) {
		case 'Linux':
			return linux_loadavg();
			break;
		case 'FreeBSD':
			return freebsd_loadavg();
			break;
		default:
			return -1;
	}
}

/*
 * linux_loadavg() - Gets the 1 min load average from /proc/loadavg
 */
function linux_loadavg() {
	$buffer = "0 0 0";
	$f = fopen("/proc/loadavg","r");
	if (!feof($f)) {
		$buffer = fgets($f, 1024);
	}
	fclose($f);
	$load = explode(" ",$buffer);
	return (float)$load[0];
}

/*
 * freebsd_loadavg() - Gets the 1 min  load average from uptime
 */
function freebsd_loadavg() {
	$buffer= `uptime`;
	ereg("averag(es|e): ([0-9][.][0-9][0-9]), ([0-9][.][0-9][0-9]), ([0-9][.][0-9][0-9]*)", $buffer, $load);
	return (float)$load[2];
}
    
    

//these are the arguments we expect
$param=array(
	'dir'=>'/home/geograph',		//base installation dir

	'config'=>'www.geograph.org.uk', //effective config

	'timeout'=>14, //timeout in minutes
	'sleep'=>10,	//sleep time in seconds
	'load'=>100,	//maximum load average
	'load'=>100,	//maximum load average
	'base'=>1,	//maximum load average
	'dryrun'=>0,	//maximum load average
	'help'=>0,		//show script help?
);

//very simple argument parser
for($i=1; $i<count($_SERVER['argv']); $i++)
{
	$arg=$_SERVER['argv'][$i];

	if (substr($arg,0,2)=='--')

	{
		$arg=substr($arg,2);
		$bits=explode('=', $arg,2);
		if (isset($param[$bits[0]]))
		{
			//if we have a value, use it, else just flag as true
			$param[$bits[0]]=isset($bits[1])?$bits[1]:true;
		}
		else die("unknown argument --$arg\nTry --help\n");
	}
	else die("unexpected argument $arg - try --help\n");
	
}


if ($param['help'])
{
	echo <<<ENDHELP
---------------------------------------------------------------------
delete_maps.php 
---------------------------------------------------------------------
php delete_maps.php 
    --dir=<dir>         : base directory (/home/geograph)
    --config=<domain>   : effective domain config (www.geograph.org.uk)
    --timeout=<minutes> : maximum runtime of script (14)
    --sleep=<seconds>   : seconds to sleep if load average exceeded (10)
    --load=<loadavg>    : maximum load average (100)
    --base=1/0          : delete the basemap (1)
    --dryrun=1/0        : dont actully delete (0)
    --help              : show this message	
---------------------------------------------------------------------
	
ENDHELP;
exit;
}
	
//set up  suitable environment
ini_set('include_path', $param['dir'].'/libs/');
$_SERVER['DOCUMENT_ROOT'] = $param['dir'].'/public_html/'; 
$_SERVER['HTTP_HOST'] = $param['config'];


//--------------------------------------------
// nothing below here should need changing

require_once('geograph/global.inc.php');
require_once('geograph/gridimage.class.php');
require_once('geograph/gridsquare.class.php');
require_once('geograph/map.class.php');
require_once('geograph/image.inc.php');
require_once('geograph/mapmosaic.class.php');
	
$db = NewADOConnection($GLOBALS['DSN']);

$start_time = time();

$end_time = $start_time + (60*$param['timeout']);


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
print "Total: $total\n";
exit;

?>
