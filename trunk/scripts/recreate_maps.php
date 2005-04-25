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

//doesnt seem to work???
	//$timeout_value = $argv[1];
	//$sleep_time = $argv[2];  
	//$maxload_value = $argv[3];  

list ($timeout_value,$sleep_time,$maxload_value) = array_keys($_REQUEST);

//how many minutes to allow the program to run
	//can use 0 to process upto 10 images straight away but will exit as soon as load average is too high!
if (!$timeout_value)
	$timeout_value = 14; //because probably being run every 15 minutes via cron

//percentage load that we must be under to do any processing...
if (!$sleep_time)
	$sleep_time = 10; 
	
//time in seconds to sleep between checking the load avarage
if (!$maxload_value)
	$maxload_value = 100; //because if missing then means then probablt wont work anyway on this system

//needed to allow the config file to load - could be passed in as a argument??
$_SERVER['HTTP_HOST'] = "geograph.local";

//not sure how to autodetect this?
$_SERVER['DOCUMENT_ROOT'] = "/home/geograph/public_html/"; 

//the number of maps to process before checking for new maps
$group_size = 10;

//--------------------------------------------
// nothing below here should need changing

require_once('geograph/global.inc.php');
require_once('geograph/gridimage.class.php');
require_once('geograph/gridsquare.class.php');
require_once('geograph/map.class.php');

$db = NewADOConnection($GLOBALS['DSN']);

$start_time = time();

$end_time = $start_time + (60*$timeout_value);

while (1) {

	$invalid_maps = $db->GetOne("select count(*) from mapcache where age > 0");

	if ($invalid_maps) {
		$map=new GeographMap;
		//done many small select statements to allow new maps to be processed 
		$recordSet = &$db->Execute("select * from mapcache where age > 0 order by pixels_per_km desc, age desc limit $group_size");
		while (!$recordSet->EOF) 
		{
			while ($maxload_value < 100 && get_loadavg() > $maxload_value) {
				if (time()>$end_time) {
					//we've waited too long, let the next recruit have a go.
					exit;	
				}
				sleep($sleep_time);
			}
			
			foreach($recordSet->fields as $name=>$value)
			{
				if (!is_numeric($name))
					$map->$name=$value;
			}

			$map->_renderMap();

			echo "re-rendered ".$map->getImageFilename()."\n";
			flush();

			if (time()>$end_time) {
				$recordSet->Close(); 
				//well come to the end of the scripts useful life
				exit;	
			}

			$recordSet->MoveNext();

		}
		sleep($sleep_time);
	} else {
		//nothing more to do here

		exit;
	}

	if (time()>$end_time) {
		//retreat and let the next recruit take the strain
		exit;	
	}
}	

	//load average code from http://leknor.com/code/php/view/class.gzip_encode.php.txt
	 function get_loadavg() {
		$uname = posix_uname();
		switch ($uname['sysname']) {
			case 'Linux':
				return linux_loadavg();
				break;
			case 'FreeBSD':
				return freebsd_loadavg();
				break;
		}
   }

    /*
     * linux_loadavg() - Gets the max() system load average from /proc/loadavg
     *
     * The max() Load Average will be returned
     */
    function linux_loadavg() {
		$buffer = "0 0 0";
		$f = fopen("/proc/loadavg","r");
		if (!feof($f)) {
			$buffer = fgets($f, 1024);
		}
		fclose($f);
		$load = explode(" ",$buffer);
		return max((float)$load[0], (float)$load[1], (float)$load[2]);
    }

    /*
     * freebsd_loadavg() - Gets the max() system load average from uname(1)
     *
     * The max() Load Average will be returned
     *
     * I've been told the code below will work on solaris too, anyone wanna
     * test it?
     */
    function freebsd_loadavg() {
		$buffer= `uptime`;
		ereg("averag(es|e): ([0-9][.][0-9][0-9]), ([0-9][.][0-9][0-9]), ([0-9][.][0-9][0-9]*)", $buffer, $load);

		return max((float)$load[2], (float)$load[3], (float)$load[4]);
    } 

	
?>
