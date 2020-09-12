<?php
/**
 * $Project: GeoGraph $
 * $Id: conversion.php 2960 2007-01-15 14:33:27Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2008 Barry Hunter (geo@barryhunter.co.uk)
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

$param = array('execute'=>0,'reset'=>0);

chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

#####################################################

                        require_once('geograph/conversions.class.php');
                        $conv = new Conversions;


$recordSet = &$db->Execute("SELECT content_id,source, foreign_id, gridsquare_id FROM content WHERE wgs84_lat = 0 AND (gridsquare_id > 0 OR source = 'trip')");
while (!$recordSet->EOF) {
	$row = $recordSet->fields;

	$lat = null;

	if ($row['source'] == 'trip') {
		$track = $db->getRow("SELECT bbox FROM geotrips WHERE id = {$row['foreign_id']}");

		$bbox=explode(' ',$track['bbox']);
	        $e=(int)(($bbox[0]+$bbox[2])/2);
	        $n=(int)(($bbox[1]+$bbox[3])/2);

		list($lat,$long) = $conv->national_to_wgs84($e, $n, 1); //geotrips are all re=1

	} elseif (!empty($row['gridsquare_id'])) {

                $square=new GridSquare;
                $square->loadFromId($row['gridsquare_id']);

                list($lat,$long) = $conv->gridsquare_to_wgs84($square);
	}

	if (!empty($lat)) {
		$sql = "UPDATE content SET wgs84_lat=$lat, wgs84_long=$long WHERE content_id = {$row['content_id']}";

		if ($param['execute']) {
			$db->Execute($sql);
			print "$sql  == ".mysql_affected_rows()."\n";
		} else {
			print "$sql\n";
		}
	}

	$recordSet->MoveNext();
}
$recordSet->Close();


###############################################################
# Cut down version of full sequence system. (this works ok, but not effienct enough for millions of rows!)
#   ... also this version doesnt try to maniuplate which is chosen first (because no mysql WITHIN GROUP ORDER BY)

if (!$param['execute'])
	die();


if ($param['reset']) {
	$db->Execute("update content set sequence = NULL");
	$rows = mysql_affected_rows();
	print "Reset, ".date('r')." F=".$rows."\n";
}


//need the unique index no content_id, as we abuse a auto-incrment key to get a running sequence
$db->Execute($sql = "create temporary table square1 (content_id int unsigned unique, sequence int unsigned not null auto_increment primary key)");

$group = 'round( (wgs84_long+90.0)*pow($d+1,1.4) ), round( (wgs84_lat)*pow($d+1,1.4) )';
$max =30;

##print "$sql;\n\n";

$d = 0;
$loop=0;
while(1) {
	// need to insert into a new table, as it cant insert into same table as selecting

	$db->Execute($sql = "create temporary table square2
			select content_id
			from content left join square1 using (content_id)
			where wgs84_lat > 0 AND square1.content_id IS NULL
			group by ".str_replace('$d',$d,$group)."
			order by rand()");

//print "$sql;\n\n";

        $rows = mysql_affected_rows();
        print "$loop, ".date('r')." F1=".$rows." ";

	$db->Execute($sql = "INSERT INTO square1 SELECT content_id,NULL AS sequence FROM square2");

        $rows = mysql_affected_rows();
        print "F2=".$rows."\n";

##print "$sql;\n\n";

	$db->Execute("DROP temporary TABLE square2");

##exit;
        if (empty($rows))
                break;

        if ($d < $max)
                $d++;

        $loop++;
}


$db->Execute("update content inner join square1 using (content_id) set content.sequence = square1.sequence");
$rows = mysql_affected_rows();
print "Finished, ".date('r')." F=".$rows."\n";

