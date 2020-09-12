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
	'timeout'=>14, //timeout in minutes
	'number'=>10,	//number to do each time
	'offset'=>0,	//so can also process the middle
	'sleep'=>10,	//sleep time in seconds
	'load'=>4,	//maximum load average
);

$HELP = <<<ENDHELP
    --timeout=<minutes> : maximum runtime of script (14)
    --sleep=<seconds>   : seconds to sleep if load average exceeded (10)
    --number=<number>   : number of items to process in each batch (10)
    --offset=<number>   : non-zero to process part of the dataset (0)
    --load=<loadavg>    : maximum load average (4)
ENDHELP;

chdir(__DIR__);
require "./_scripts.inc.php";

#######################

require_once('geograph/gridimage.class.php');
require_once('geograph/gridsquare.class.php');
require_once('geograph/map.class.php');
require_once('geograph/image.inc.php');

$db = NewADOConnection($GLOBALS['DSN']);

$start_time = time();

$end_time = $start_time + (60*$param['timeout']);

$map=new GeographMap;
$debug = false;

while (1) {
	#$db = NewADOConnection($GLOBALS['DSN']);
	$invalid_maps = $db->GetOne("select SQL_NO_CACHE count(*) from kmlcache where rendered != 1");

	if ($invalid_maps) {
		//done as many small select statements to allow new maps to be processed
		$recordSet = &$db->Execute("select url,filename,rendered from kmlcache where rendered != 1 order by level limit {$param['offset']},{$param['number']}");
		while (!$recordSet->EOF) 
		{
			//sleep until calm if we've specified a load average
			if ($param['load']<100)
			{
				while (get_loadavg() > $param['load'])
				{
					sleep($param['sleep']);
					if (time()>$end_time)
						die($debug?'C':'');

				}
			}

			if ($debug)
				print "Starting: {$recordSet->fields['filename']}\n";

			$postfix = ($recordSet->fields['rendered'] == 2)?'&newonly=1':'';

			if ($debug)
				print "...Fetching: http://{$_SERVER['HTTP_HOST']}/_scripts/kml-{$recordSet->fields['url']}$postfix\n";

			file_get_contents("http://{$_SERVER['HTTP_HOST']}/_scripts/kml-{$recordSet->fields['url']}$postfix");

			if ($debug)
				print "...Done\n";

			if (time()>$end_time) {
				$recordSet->Close();
				//well come to the end of the scripts useful life
				die($debug?'D':'');
			}

			$recordSet->MoveNext();

		}
	} else {
		//nothing more to do here

		die($debug?'E':'');
	}

	//sleep anyway for a bit
	sleep($param['sleep']);

	if (time()>$end_time) {
		//retreat and let the next recruit take the strain
		die($debug?'F':'');
	}
}

