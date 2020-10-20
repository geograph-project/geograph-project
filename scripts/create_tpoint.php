<?php
/**
 * $Project: GeoGraph $
 * $Id: create_tpoint.php 8956 2019-05-27 09:58:47Z barry $
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

$param = array(
	'limit'=>4,
	'clear'=>0,
	'verbose'=>0,
	'source'=>'hectads',
);

chdir(__DIR__);
require "./_scripts.inc.php";

#######################################################################

$db_write = GeographDatabaseConnection(false);
$db_read = GeographDatabaseConnection(2); //to ensure not lagging too much
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

function dbExecute($sql) {
	global $db_write,$a;
	if (true) {
		$db_write->Execute($sql) or die($sql."\n".$db->ErrorMsg()."\n\n");
		$a += $db->Affected_Rows();
	} else {
		print "$sql;\n";
	}
}

#######################################################################

	if ($param['clear'] == 2) {
		//not recommended to do this! use the nibble version instead!
		dbExecute("UPDATE gridimage SET points = '',upd_timestamp=upd_timestamp");
		dbExecute("UPDATE gridimage_search SET points = '',upd_timestamp=upd_timestamp");
	}

$count = $a = 0;

#######################################################################


if ($param['source'] == 'myriads') {
	//even using gridprefix, results in myriads with 200k photos, so lets use hectads,
	//  x/y are set with MIN(), so might not be the technical orgin, but is gareneteed to contain the images, just might do some squares multiple times
	$prefixes = $db_read->GetAll('SELECT hectad AS prefix, x as origin_x, y as origin_y, 10 as width, 10 as height FROM hectad_stat WHERE images>0 LIMIT '.$param['limit']);
} else {
	$prefixes = $db_read->GetAll('select * from gridprefix where landcount >0 order by landcount desc limit '.$param['limit']);
}

$outer_total = count($prefixes);
$outer_count = 0;

print "$htotal Prefixes...\n";
foreach ($prefixes as $prefix) {

        $left=$prefix['origin_x'];
        $right=$prefix['origin_x']+$prefix['width']-1;
        $top=$prefix['origin_y']+$prefix['height']-1;
        $bottom=$prefix['origin_y'];

        $rectangle = "'POLYGON(($left $bottom,$right $bottom,$right $top,$left $top,$left $bottom))'";

        $sql_where = "CONTAINS(GeomFromText($rectangle),point_xy)";

	#######################################################################

	$sql = "SELECT gridimage_id,grid_reference,TO_DAYS(REPLACE(imagetaken,'-00','-01')) AS days
		FROM gridimage_search
		WHERE imagetaken NOT LIKE '0000%' AND moderation_status = 'geograph'
		AND $sql_where
		ORDER BY grid_reference,seq_no";
	$recordSet = $db_read->Execute($sql);

	if ($param['verbose'])
		print "=> $sql\n";

	$inner_total = $recordSet->RecordCount();
	$outer_count++;
	print "{$prefix['prefix']}/$inner_total ($outer_count/$outer_total)\n";

	#######################################################################

	$buckets = array();
	$last = '';

	$five_years_in_days = 365*5;
	$break = ($param['verbose'])?10:1000;

	while (!$recordSet->EOF)
	{
		$days = $recordSet->fields['days'];
		$square = $recordSet->fields['grid_reference'];

		#####################

		if ($square != $last) {
			if ($param['verbose'])
				print " $square. ";
			if ($param['clear'] == 1) {
				$gridsquare_id = $db_read->getOne("SELECT gridsquare_id FROM gridsquare WHERE grid_reference = '$square'");
				dbExecute("UPDATE gridimage SET points = '',upd_timestamp=upd_timestamp WHERE gridsquare_id = $gridsquare_id AND points = 'tpoint'");
				dbExecute("UPDATE gridimage_search SET points = '',upd_timestamp=upd_timestamp WHERE grid_reference = '$square' AND points = 'tpoint'");
			}

			//start fresh for a new square
			$buckets = array();
			$last = $square;
		}

		#####################

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
			dbExecute("UPDATE gridimage SET points = 'tpoint',upd_timestamp=upd_timestamp WHERE gridimage_id = ".$recordSet->fields['gridimage_id']);
			dbExecute("UPDATE gridimage_search SET points = 'tpoint',upd_timestamp=upd_timestamp WHERE gridimage_id = ".$recordSet->fields['gridimage_id']);
			$count++;
			if (!($count%$break)) {
				if (!$param['verbose'])
					print "$square ";
				print "($count,$a). ";
			}
		}

		#####################

		$recordSet->MoveNext();
	}

	$recordSet->Close();

	print " /($count,$a).\n\n";
}


dbExecute("alter table user_stat comment='rebuild'"); //mark the table for complete rebuild!

print " done [$count]\n";




