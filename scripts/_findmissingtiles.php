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
	'dir'=>'/var/www/geograph_live',		//base installation dir

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

	echo "Checking Folders...\n\n";
	flush();

	# /maps/detail/741/120/detail_741_120_200_200_40_0.png
	# or /maps/detail/741/120/detail_741_120_200_200_40_0_1.png
	
function recurse_maps($folder) {	
	global $db;
	$root=&$_SERVER['DOCUMENT_ROOT'];
	$dh = opendir($root.$folder);
	
	while (($file = readdir($dh)) !== false) {
		if (is_dir($root.$folder.$file) && strpos($file,'.') !== 0) {
			recurse_maps($folder.$file.'/');
			print "done $folder $file\n";
		} elseif (preg_match("/detail_(\d+)_(\d+)_(\d+)_(\d+)_(\d+)_(\d+)_?(\d*)\./",$file,$m)) {
			array_shift($m);//remove $0
			
			$pallete = array_pop($m); //remove optional pallete - because it will always set even if empty. 
			
			$pallete += 0; //turn into a number 
			
			$sql = "INSERT DELAYED IGNORE INTO mapcache2 VALUES(".join(',',$m).",0,$pallete)";
			$db->Execute($sql);
		}		
	}
	closedir($dh);
}
	
	recurse_maps("/maps/detail/");
	
	print "-----------\nDone\n\n";

 

	
?>
