<?php
/**
 * $Project: GeoGraph $
 * $Id: buildplacename_id.php 8713 2018-02-09 19:33:31Z barry $
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
	'radius'=>100000,
	'limit'=>1000,
	'table'=>'gridsquare',  # gridsquare/gridimage for now
);


chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

//insert a FAKE log (just so we can plot on a graph ;)
$db->Execute("INSERT INTO event_log SET
        event_id = 0,
        logtime = NOW(),
        verbosity = 'trace',
        log = 'running event_handlers/every_day/".basename($argv[0])."',
        pid = 33");

############################################

	set_time_limit(3600*24);

	$tim = time();
	$count=0;

	$table = $param['table'];
	//Needs {$table}_id -column as primary key
	//Needs gridsquare_id column - it can be the key!
	//also needs the reference_index AND grid_reference column so can get easting/northigns from it.
	//and if has nateastings/natnorthings, they will be used!
	// and if has upd_timestamp will use beed to kinda on theory only update specific row.

	if ($table == 'gridimage') {
		$crit = "$table.placename_id = 0";

		//we get the gridsquare placename_id, a can use that as a fallback if no nateastings!
		$sql = "SELECT gridimage_id,nateastings,natnorthings,upd_timestamp,
		gridsquare_id,x,y,reference_index,grid_reference,gridsquare.placename_id
		FROM $table INNER JOIN gridsquare USING (gridsquare_id) WHERE {$crit} LIMIT {$param['limit']}";

	} else {
		$crit = "placename_id = 0 AND imagecount > 0"; //todo maybe landcount>0 instead

		$sql = "SELECT * FROM $table WHERE {$crit} LIMIT {$param['limit']}";
	}

	print "$sql;\n";

	$recordSet = $db->Execute($sql);
	while (!$recordSet->EOF)
	{
		$pid = null;

			$gid = $recordSet->fields["{$table}_id"];

				$square=new GridSquare;
				#$square->_initFromArray($recordSet->fields);
				//store cols as members
				foreach($recordSet->fields as $name=>$value) {
					if (!is_numeric($name))
						$square->$name=$value;
				}
				$square->_storeGridRef($square->grid_reference);


				if (empty($square->nateastings)) {
					if (!empty($square->placename_id)) {
						$pid = $square->placename_id; //if have one from square, can use it
					} else {
						//figure out one from for the center of the square
						$square->getNatEastings();
					}
				}

			if (empty($pid)) { //we may of set one from the source squery. (eg on photos, that DONT have a nateastings, can fallback and use one from gridsquare

				$places = $square->findNearestPlace($param['radius'],'OS'); //we need explicitly OS gaz, as that what placename_id is based on!
				if (!empty($places['pid']))
					$pid = $places['pid'];
			}

		if (!empty($pid)) {

			$extra = "";
			if (!empty($recordSet->fields['upd_timestamp']))
				$extra = ",upd_timestamp = upd_timestamp";
			if (!empty($recordSet->fields['last_timestamp']))
				$extra = ",last_timestamp = last_timestamp";

			$db->Execute("update LOW_PRIORITY $table set placename_id = $pid$extra where {$table}_id = $gid");
		}

		if (++$count%50==0) {
			printf("done %d at <b>%d</b> seconds\n",$count,time()-$tim);
			flush();
		}

		$recordSet->MoveNext();
	}
	printf("done %d at <b>%d</b> seconds\n",$count,time()-$tim);
	$recordSet->Close();


