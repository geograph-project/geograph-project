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
require_once('3rdparty/geocubesapi.class.php');


$db = NewADOConnection($GLOBALS['DSN']);

//do this one first otherwise this query runs (unnesserically) on new photos too
print "LOCATING UPDATED PHOTOS...\n";
$sql = "UPDATE gridimage_search,geocubes SET outstanding = 1 WHERE gridimage_search.gridimage_id = geocubes.gridimage_id AND upd_timestamp > lastsent;";
$sql = "UPDATE gridimage_search INNER JOIN geocubes USING(gridimage_id) SET outstanding = 1 WHERE upd_timestamp > lastsent;";
	$db->Execute($sql);
	print "Affected Rows: ".$db->Affected_Rows()."\n\n";
	
print "LOCATING NEW PHOTOS...\n";
$sql = "INSERT INTO geocubes SELECT gi.gridimage_id,0 AS lastsent,1 AS outstanding FROM gridimage_search gi LEFT JOIN geocubes USING (gridimage_id) WHERE geocubes.gridimage_id IS NULL;";
	$db->Execute($sql);
	print "Affected Rows: ".$db->Affected_Rows()."\n\n";

print "LOCATED REMOVED PHOTOS...\n";
$sql = "UPDATE geocubes LEFT JOIN gridimage_search USING(gridimage_id) SET outstanding = 1 WHERE gridimage_search.gridimage_id IS NULL;";
	$db->Execute($sql);
	print "Affected Rows: ".$db->Affected_Rows()."\n\n";


$sql = "SELECT geocubes.gridimage_id, wgs84_lat, wgs84_long, title, user_id, crc32(imageclass) as crc_imageclass
	FROM geocubes LEFT JOIN gridimage_search USING(gridimage_id)
	WHERE outstanding = 1
	LIMIT 1000";
$recordSet = &$db->Execute($sql);

while (($rows = $recordSet->RecordCount()) > 0) {
	
	//recreate it each time to be sure. (descruture is called which disconnects previous connection
	$gc = new geocubes($CONF['GEOCUBES_API_KEY'], $CONF['GEOCUBES_API_TOKEN']);
	
	print "UPDATING $rows records\n\n";
	
	$uids = array();
	$dids = array();
	while (!$recordSet->EOF) {
		
		$gid = $recordSet->fields['gridimage_id'];
		
		//we need to remove it before adding back anyway. 
		$gc->removePoint($gid);
		
		if (strlen($recordSet->fields['title']) > 1) {
			//it hasnt been deleted from gridimage_search so can add it (|back) !
			
			$row = $recordSet->fields;
			
			if ($gc->addPoint($row['gridimage_id'], $row['wgs84_lat'], $row['wgs84_long'], $row['title'], $row['user_id'], $row['crc_imageclass']) == 0) {
				print "ERROR adding $gid\n\n";
			} else {
				$uids[] = $gid;
			}
		} else {
			$dids[] = $gid;
		}
		
		$recordSet->MoveNext();
	}
	$recordSet->Close();
	
	$uids = implode(',',$uids);
	$updatesql = "UPDATE geocubes SET outstanding = 0 WHERE gridimage_id IN ($uids)";
		$db->Execute($updatesql);
		print "Records Updated: ".$db->Affected_Rows()."/$rows\n\n";
	
	$dids = implode(',',$dids);
	$updatesql = "DELETE FROM geocubes WHERE gridimage_id IN ($dids)";
		$db->Execute($updatesql);
		print "Records Deleted: ".$db->Affected_Rows()."/$rows\n\n";
	
	
	$recordSet = &$db->Execute($sql);//try again (for the while loop)
}


print "ALL DONE\n\n";

?>
