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
	'dir'=>'/var/www',		//base installation dir

	'config'=>'www.geograph.virtual', //effective config

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


$db = NewADOConnection($GLOBALS['DSN']);


$a = array();




$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


	$sql = "select user_id,grid_reference,title,count(*) as images,group_concat(gridimage_id) as ids from gridimage_search where  title != '' group by user_id,grid_reference,title having images > 1 order by null";
	print "$sql\n";
	
	$recordSet = &$db->Execute($sql);
		
	while (!$recordSet->EOF) 
	{
		$updates = array();
		$updates['user_id'] = $recordSet->fields['user_id'];
		$updates['grid_reference'] = $recordSet->fields['grid_reference'];
		$updates['title'] = $recordSet->fields['title'];
		$updates['images'] = $recordSet->fields['images'];
		$updates['type'] = $recordSet->fields['T'];
		
		$db->Execute('INSERT IGNORE INTO swarm SET created = now(),`'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
		$swarm_id = $db->Insert_ID();	


		foreach (explode(',',$recordSet->fields['ids']) as $id) {
			$updates = array();
			$updates['gridimage_id'] = $id;
			$updates['swarm_id'] = $swarm_id;
		
			$db->Execute('INSERT IGNORE INTO gridimage_swarm SET created = now(),`'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
		}
		print ".";
	
		$recordSet->MoveNext();
	}
				
	$recordSet->Close();
	print "l\n";
	exit;#!




?>
