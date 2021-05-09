<?php
/**
 * $Project: GeoGraph $
 * $Id: create_tpoint-delta.php 8957 2019-05-27 10:00:23Z barry $
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
	'debug' => false,
	'interval' => "72 hour",
);

chdir(__DIR__);
require "./_scripts.inc.php";

//--------------------------------------------

$db_write = GeographDatabaseConnection(false);
$db_read = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

//insert a FAKE log (just so we can plot on a graph ;)
$db_write->Execute("INSERT INTO event_log SET
        event_id = 0,
        logtime = NOW(),
        verbosity = 'trace',
        log = 'running event_handlers/every_day/".basename($argv[0])."',
        pid = 33");

############################################

$a = array();


	//$grs = $db_read->getCol("select grid_reference from gridimage_search where upd_timestamp >
	//	date_sub(now(),interval {$param['interval']}) group by grid_reference order by null");

	// using gridsquare is even better, as it only updates when images in square change, rather than small changes to title etc
	$grs = $db_read->getCol("select grid_reference from gridsquare where last_timestamp >
		date_sub(now(),interval {$param['interval']})");

	$sql = "SELECT gridimage_id,grid_reference,TO_DAYS(REPLACE(imagetaken,'-00','-01')) AS days, points
		FROM gridimage_search WHERE imagetaken NOT LIKE '0000%'
			and grid_reference in ('".implode("','",$grs)."')
			AND moderation_status = 'geograph' ORDER BY grid_reference,seq_no";

	if ($param['debug'])
		print "$sql\n";

	$buckets = array();
	$count = 0;
	$affected = 0;
	$last = '';

	$five_years_in_days = 365*5;

	$recordSet = $db_read->Execute($sql);

	while (!$recordSet->EOF)
	{
		$days =  $recordSet->fields['days'];
		$square =  $recordSet->fields['grid_reference'];

		if ($square != $last) {
			if ($last && $affected) {
				//we actully need to update the timestamp, as we've updated the points in square. Ideally we could actully update a tpoint count here too!
					//we need to update gridsquare.last_timestamp so things that depend on it update (like user_gridsquare!)
				$db_write->Execute("UPDATE gridsquare SET last_timestamp = NOW() WHERE grid_reference = '$last'");
			}
			//start fresh for a new square
			$buckets = array();

			//the main query can clear any geographs, need to clear non geos.
			$db_write->Execute("UPDATE gridimage_search SET points = '',upd_timestamp=upd_timestamp WHERE grid_reference = '$square' and moderation_status !='geograph' AND points = 'tpoint'");
			$affected = $db_write->Affected_Rows();

			if ($affected) {
				$gridsquare_id = $db->getOne("SELECT gridsquare_id FROM gridsquare WHERE grid_reference = '$square'");
				$db_write->Execute("UPDATE gridimage SET points = '',upd_timestamp=upd_timestamp WHERE gridsquare_id=$gridsquare_id and moderation_status !='geograph' AND points = 'tpoint'");
			}

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

		if ($point && $recordSet->fields['points'] != 'tpoint') {
			if ($param['debug']) {
                                print "Setting TPoint on {$recordSet->fields['gridimage_id']}, was '{$recordSet->fields['points']}'\n";
                        } else {
				$db_write->Execute("UPDATE gridimage SET points = 'tpoint',upd_timestamp=upd_timestamp WHERE gridimage_id = ".$recordSet->fields['gridimage_id']);
				$db_write->Execute("UPDATE gridimage_search SET points = 'tpoint',upd_timestamp=upd_timestamp WHERE gridimage_id = ".$recordSet->fields['gridimage_id']);
				$affected += $db_write->Affected_Rows();
			}
			//print ". ";
			$count++;
		} elseif (!$point && $recordSet->fields['points'] == 'tpoint') {
			if ($param['debug']) {
                                print "CLEARING TPoint on {$recordSet->fields['gridimage_id']}, was '{$recordSet->fields['points']}'\n";
                        } else {
				$db_write->Execute("UPDATE gridimage SET points = '',upd_timestamp=upd_timestamp WHERE gridimage_id = ".$recordSet->fields['gridimage_id']);
				$db_write->Execute("UPDATE gridimage_search SET points = '',upd_timestamp=upd_timestamp WHERE gridimage_id = ".$recordSet->fields['gridimage_id']);
				$affected += $db_write->Affected_Rows();
			}
			//print ". ";
			$count++;
		}

		$recordSet->MoveNext();
	}

	$recordSet->Close();

	if ($last && $affected) {
		$db_write->Execute("UPDATE gridsquare SET last_timestamp = NOW() WHERE grid_reference = '$last'");
	}

	if ($param['debug'])
		print "done [$count]\n";





