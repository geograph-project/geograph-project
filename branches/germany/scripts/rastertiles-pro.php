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

/**
* get 1 minute load average
*/
function get_loadavg() 
{
	if (!function_exists('posix_uname')) {
		return -1;
	}
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
	if (is_readable("/proc/loadavg")) {
		$f = fopen("/proc/loadavg","r");
		if ($f) {
			if (!feof($f)) {
				$buffer = fgets($f, 1024);
			}
			fclose($f);
		}
	}
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
	'limit'=>5,	//number to do each time
	'skip'=>0,	//so can also process the middle
	'sleep'=>10,	//sleep time in seconds
	'load'=>4,	//maximum load average
	'help'=>0,	//show script help?
	
	'run'=>0,
	'force'=>0,
	'nice'=>0,
	
	'epoch'=>'latest',
	'processTile'=>false,
	'processSingleTile'=>false,
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


if (isset($param['processSingleTile']) && $param['processSingleTile'] === true) {
	$param['processSingleTile'] = 200;
}

if ($param['help'])
{
	echo <<<ENDHELP
---------------------------------------------------------------------
recreate_maps.php 
---------------------------------------------------------------------
php recreate_maps.php 
    --dir=<dir>         : base directory (/home/geograph)
    --config=<domain>   : effective domain config (www.geograph.org.uk)
    
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
require_once('geograph/rastermapOS.class.php');


$db = NewADOConnection($GLOBALS['DSN']);

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

?>
