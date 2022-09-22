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

$param = array('limit' => 100000,'update'=>true);

chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

############################################

//insert a FAKE log (just so we can plot on a graph ;)
$db->Execute("INSERT INTO event_log SET
        event_id = 0,
        logtime = NOW(),
        verbosity = 'trace',
        log = 'running event_handlers/every_day/".basename($argv[0])."',
        pid = 33");

$sqls = array();
##################################################
//generate some fake seqeuences for the few new images!
// (do this first, before updating sequence, so that new rows are created in gridimage_sequence, so UPDATE works)

//old: 1000000+(ABS(CRC32(gridimage_id)) MOD 11000000) as sequence  (triangle sequence
//new: 3000000+(ABS(CRC32(gridimage_id)) MOD 1000000) as sequence2  (square sequence

//... gridimage.class now creates such fake sequence directly.


##################################################
//copy in new computed values (if available!)

if ($db->getOne("SHOW TABLES LIKE 'gridimage_square2'")) {

	//create a copy table, (so replace into works in gridimage.class
	$build = "REPLACE INTO gridimage_sequence
		SELECT gridimage_id,sequence2 AS sequence FROM gridimage_square2 t
		WHERE \$where";

	//and update gridimage_search
	$build2 = "update gridimage_search gs inner join gridimage_square2 t using (gridimage_id)
		set gs.sequence = t.sequence2, upd_timestamp=upd_timestamp
		WHERE \$where";

	$min = $db->getOne("SELECT MIN(gridimage_id) FROM gridimage_square2");
	$max = $db->getOne("SELECT MAX(gridimage_id) FROM gridimage_square2");

	for($start = $min;$start<$max;$start+=$param['limit']) {
	        $end = $start+$param['limit']-1;
        	$sqls[] = str_replace("\$where","gridimage_id BETWEEN $start AND $end",$build);
        	$sqls[] = str_replace("\$where","gridimage_id BETWEEN $start AND $end",$build2);
	}

	$sqls[] = "DROP TABLE gridimage_square2"; //its used now, so drop it!
}

##################################################
//fill in/update the the score and baysian figures.

if ($param['update']) { //so can abort early (only import gridimage_square2) without doing a full update

	$build = "update gridimage_search gs inner join gridimage_log using (gridimage_id) left join gallery_ids i on (id = gridimage_id)
		set gs.score = (hits+hits_archive)*COALESCE(i.baysian,3.2),
			gs.baysian = i.baysian, upd_timestamp=upd_timestamp
		WHERE \$where";

	//$max = $db->getOne("SELECT MAX(gridimage_id) FROM gridimage_sequence"); #cant use this, new rows may be inserted via the first query above!
	$max = $db->getOne("SELECT MAX(gridimage_id) FROM gridimage_search");

	for($start = 1;$start<$max;$start+=$param['limit']) {
        	$end = $start+$param['limit']-1;
	        $sqls[] = str_replace("\$where","gridimage_id BETWEEN $start AND $end",$build);
	}
}

######################################################################################################################################################

foreach ($sqls as $sql) {
	print "---\n$sql\n---\n".date('r')." (started)\n";
	$db->Execute($sql);
	print date('r')." (done)\n";
	print "Rows Affected: ".$db->Affected_Rows()."\n";
}
print ".\n";
