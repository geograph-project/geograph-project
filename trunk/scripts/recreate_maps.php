<?php
/**
 * $Project: GeoGraph $
 * $Id$
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
recreate_maps.php 
---------------------------------------------------------------------
php recreate_maps.php 
    --dir=<dir>         : base directory (/home/geograph)
    --config=<domain>   : effective domain config (www.geograph.org.uk)
    --timeout=<minutes> : maximum runtime of script (14)
    --sleep=<seconds>   : seconds to sleep if load average exceeded (10)
    --load=<loadavg>    : maximum load average (100)
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

$db = NewADOConnection($GLOBALS['DSN']);

$start_time = time();

$end_time = $start_time + (60*$param['timeout']);

$map=new GeographMap;
			
while (1) {

	$invalid_maps = $db->GetOne("select count(*) from mapcache where age > 0 and type_or_user >= 0");

	if ($invalid_maps) {
		//done as many small select statements to allow new maps to be processed 
		$recordSet = &$db->Execute("select * from mapcache where age > 0 and type_or_user >= 0
			order by pixels_per_km desc, age desc limit 50");
		while (!$recordSet->EOF) 
		{
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
			
			foreach($recordSet->fields as $name=>$value)
			{
				if (!is_numeric($name))
					$map->$name=$value;
			}

			$ok = $map->_renderMap();
				
			echo (($ok?'re-rendered ':'FAILED: ').$map->getImageFilename()."\n");
			flush();
			
			
			if (time()>$end_time) {
				$recordSet->Close(); 
				//well come to the end of the scripts useful life
				exit;	
			}

			$recordSet->MoveNext();

		}
		
	} else {
		//nothing more to do here

		exit;
	}
	
	//sleep anyway for a bit
	sleep($param['sleep']);
					
	if (time()>$end_time) {
		//retreat and let the next recruit take the strain
		exit;	
	}
}	

 

	
?>
