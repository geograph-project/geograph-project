<?php
/**
 * $Project: GeoGraph $
 * $Id: recreate_maps.php 2996 2007-01-20 21:39:07Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2011 Barry Hunter (geo@barryhunter.co.uk)
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


$db_write = GeographDatabaseConnection(false);
$db_read = GeographDatabaseConnection(true);


$a = array();




$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


	$sql = "SELECT gridimage_id,grid_reference,TO_DAYS(REPLACE(imagetaken,'-00','-01')) AS days 
		FROM gridimage_search WHERE imagetaken NOT LIKE '0000%' AND moderation_status = 'geograph' ORDER BY grid_reference,seq_no";
	print "$sql\n";
	
	$buckets = array();
	$count = 0;
	$last = '';
	
	$five_years_in_days = 365*5; 
	
	$recordSet = &$db_read->Execute($sql);
		
	while (!$recordSet->EOF) 
	{
		$days =  $recordSet->fields['days'];
		$square =  $recordSet->fields['grid_reference'];
		
		if ($square != $last) {
			//start fresh for a new square
			$buckets = array();
			
			//store it anyway
			$last = $square;
		}
		
		$point = 1;
		if (count($buckets)) {
			foreach ($buckets as $test) {
				if (abs($test-$days) < $five_years_in_days) {
					$point = 0;
					break; //no point still checking...
				}
			}
		} else {
			$point = 1; //the first submitted image for the square (NOT ftf which MIGHT be different due to shuffleing) 
		}
		$buckets[] = $days;
	
		if ($point) {
			$db_write->Execute("UPDATE gridimage SET points = 'tpoint',upd_timestamp=upd_timestamp WHERE gridimage_id = ".$recordSet->fields['gridimage_id']);
			$db_write->Execute("UPDATE gridimage_search SET points = 'tpoint',upd_timestamp=upd_timestamp WHERE gridimage_id = ".$recordSet->fields['gridimage_id']);
			print ". ";
			$count++;
		}
	
		$recordSet->MoveNext();
	}
				
	$recordSet->Close();
	
	$db_write->Execute("alter table user_stat comment='rebuild'"); //mark the table for complete rebuild!
	
	print "done [$count]\n";
	exit;#!




?>