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
	'action'=>'unknown', //which

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



$db = GeographDatabaseConnection(false);

$perpage = 1000;

list($min,$max)=$db->GetRow("select min(gridimage_id),max(gridimage_id) from gridimage_search where gridimage_id > 0");
$start=floor($min / $perpage) * $perpage;
if (!$start) $start =1;

print "list($min,$max)=>$start)\n\n";

$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

for ($from=$start; $start<=$max; $start+=$perpage)
{
	$sql = "select gridimage_id,comment from gridimage_search where gridimage_id between $start and ".($start+$perpage-1)." and (comment like '%[[%' or comment like '%/photo/%')";
	

	print "$sql\n";
	
	$bits = array();
	
	
	$recordSet = &$db->Execute($sql);
		
	while (!$recordSet->EOF) 
	{
		$gridimage_id =  $recordSet->fields['gridimage_id'];

		
		if (preg_match_all('/\[\[(\d+)\]\]/',$recordSet->fields['comment'],$g_matches)) {
			foreach ($g_matches[1] as $g_i => $g_id) {
				$bits[] = "($gridimage_id,$g_id,NOW())";	
			}
		}
		if (preg_match_all('/geograph\.(org\.uk|ie)\/photo\/(\d+)\b/',$recordSet->fields['comment'],$g_matches)) {
			foreach ($g_matches[2] as $g_i => $g_id) {
				$bits[] = "($gridimage_id,$g_id,NOW())";	
			}
		}
		
		$recordSet->MoveNext();
	}
				
	$recordSet->Close();

	if (count($bits)) {
		$sql = "INSERT INTO gridimage_backlink VALUES ".implode(',',$bits);
		print "$sql\n";
		$db->Execute($sql);
	}

	exit;
}