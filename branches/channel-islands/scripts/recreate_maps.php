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

//these are the arguments we expect
$param=array(
	'timeout'=>14, //timeout in minutes
	'sleep'=>10,	//sleep time in seconds
	'load'=>100,	//maximum load average
);

chdir(__DIR__);
require "./_scripts.inc.php";

//--------------------------------------------

require_once('geograph/gridimage.class.php');
require_once('geograph/gridsquare.class.php');
require_once('geograph/map.class.php');
require_once('geograph/image.inc.php');

$db = NewADOConnection($GLOBALS['DSN']);

$start_time = time();

$end_time = $start_time + (60*$param['timeout']);

#$map=new GeographMap;
			
while (1) {

	$invalid_maps = $db->GetOne("select count(*) from mapcache where age > 0 and type_or_user >= -1");

	if ($invalid_maps) {
		//done as many small select statements to allow new maps to be processed 
		$recordSet = &$db->Execute("select * from mapcache where age > 0 and type_or_user >= -1
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

			$map=new GeographMap;
			
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

 
