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

$param=array(
	'direction'=>'forward', 'restart'=>false, //restart from beginning again
	'redo'=>false, //redo even if have an id (should be safe with cursor looping too!)
	'radius'=>75000,
	'limit'=>1000, //per loop, not number of loops!
	'total'=>1000000, 
	'table'=>'gridsquare',  //gridsquare/gridimage for now
	'ri'=>0,
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
$table_id = $param['table']."_id";
//Needs {$table}_id -column as primary key
//Needs gridsquare_id column - it can be the key!
//also needs the reference_index AND grid_reference column so can get easting/northigns from it.
//and if has nateastings/natnorthings, they will be used!
// and if has upd_timestamp will use beed to kinda on theory only update specific row.


$prompt_name = "pid.$table"; //not a real prompt, but used for labeller_progress
$last_id = null;
$block = intval($param['limit']/10);
$cache = array(); $hit=0;

if (empty($param['restart'])) //set to true to ignore
        $last_id = $db->getOne("SELECT last_id FROM labeller_progress WHERE model = '$prompt_name' AND direction = '{$param['direction']}'");

while(true) {
        $where = array();
        if ($param['direction'] == 'forward') {
            if (!empty($last_id)) $where['last'] = "$table_id > $last_id";
            $order = "$table_id ASC";
        } else {
            if (!empty($last_id)) $where['last'] = "$table_id < $last_id";
            else $last_id = 99999999;
            $order = "$table_id DESC";
        }

############################################
//rebuild the query inside the loop, as last_id changes!

	if (empty($param['redo']))
		$where[] = "$table.placename_id = 0";
	if (!empty($param['ri']))
		$where[] = "reference_index = ".intval($param['ri']);

	if ($table == 'gridimage') {
		$crit = $where?implode(" AND ",$where):1;

		//we get the gridsquare placename_id, a can use that as a fallback if no nateastings!
		//we had ,gridsquare.placename_id -- actully NO, we DONT want this now, gridimage and gridsquaire
		$sql = "SELECT gridimage_id,nateastings,natnorthings,upd_timestamp,
		gridsquare_id,x,y,reference_index,grid_reference
		FROM $table STRAIGHT_JOIN gridsquare USING (gridsquare_id) WHERE {$crit} ORDER BY {$order} LIMIT {$param['limit']}";
		//not sure why, but STRAIGHT_JOIN, so can use the key on gridimage_id!

		// -- we now want gridimage.placename_id to specifically match the 'near' line!!
		$default_gazetter = ''; //uses $CONF['use_gazetteer'] !

	} elseif ($table == 'gridsquare') {
		$where[] = "(imagecount > 0 or percent_land>0)";

		$crit = $where?implode(" AND ",$where):1;
		$sql = "SELECT * FROM $table WHERE {$crit} ORDER BY {$order} LIMIT {$param['limit']}";

		$default_gazetter = 'OS'; //we need explicitly OS gaz, gridsquare.placename_id should match sphinx_placenames, which still uses os_gaz! (not os_gaz_250)
	}

	if (empty($gid)) //first time only!
		print "$sql;\n";

	$recordSet = $db->Execute($sql);
        if ($recordSet->EOF) {
            break;
        }

############################################

	//tells gazetter class to skip saving result to memcache.
	// as we are bulk process, dont want to 'glober' allover the cache!
	$GLOBALS['DISABLE_MEMCACHE_SAVING'] = true;

	while (!$recordSet->EOF) {
		$pid = null;

		$gid = $recordSet->fields["{$table}_id"];

		$square=new GridSquare;
		#$square->_initFromArray($recordSet->fields);
		//store cols as members
		foreach($recordSet->fields as $name=>$value) {
			if (!is_numeric($name) && !empty($value)) //getNatEastings checks isset, not !empty.
				$square->$name=$value;
		}
		$square->_storeGridRef($square->grid_reference);


		$key = empty($square->nateastings)?$square->grid_reference:sprintf("%d,%d,%d",$square->reference_index,$square->nateastings/10,$square->natnorthings/10);

		if (!empty($cache[$key])) {
			$pid = $cache[$key];
			$hit++;

		} elseif (empty($square->nateastings)) {
			if (!empty($square->placename_id) && empty($param['redo'])) {
				$pid = $square->placename_id; //if have one from square, can use it
			} else {
				//figure out one from for the center of the square
				$square->getNatEastings();
			}
		}

		if (empty($pid)) { //we may of set one from the source squery. (eg on photos, that DONT have a nateastings, can fallback and use one from gridsquare
			$place = $square->findNearestPlace($param['radius'], $default_gazetter);
			if (!empty($place['pid']))
				$pid = $place['pid'];
		}

		if (!empty($pid)) {
			$cache[$key] = $pid;

			$extra = "";

			//need to prevent ON UPDATE current_timestamp() updates!
			if (!empty($recordSet->fields['upd_timestamp']))
				$extra = ",upd_timestamp = upd_timestamp";
			if (!empty($recordSet->fields['last_timestamp']))
				$extra = ",last_timestamp = last_timestamp";

			$db->Execute("update LOW_PRIORITY $table set placename_id = $pid$extra where {$table}_id = $gid");
		}

		if (++$count%$block==0) {
			printf("done %d at %d seconds\n",$count,time()-$tim);
			flush();
		}

                if ($param['direction'] == 'forward') {
                        $last_id = max($last_id, $recordSet->fields[$table_id]);
                } else {
                        $last_id = min($last_id, $recordSet->fields[$table_id]);
                }

		$recordSet->MoveNext();
	}
	printf("done %d at %d seconds, last = $last_id   ($hit hits)\n",$count,time()-$tim);
	$recordSet->Close();

############################################

	//might as well do this as go along
	if (!empty($last_id)) {
        	$db->Execute("INSERT INTO labeller_progress (model, last_id, direction) VALUES ('$prompt_name', $last_id, '{$param['direction']}')
                	ON DUPLICATE KEY UPDATE last_id = VALUES(last_id)");
	}

	if ($count >= $param['total'])
		break;

	if (count($cache) > 100000) {
	    // Keep only the last 99,000 elements, dropping the oldest 1,000
	    $cache = array_slice($cache, 1000, null, true);
	}
}

print "Done.\n";

