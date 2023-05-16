<?php
/**
 * $Project: GeoGraph $
 * $Id: conversion.php 5502 2009-05-13 14:18:23Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 BArry Hunter (geo@barryhunter.co.uk)
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

	$param = array('table'=>'feature_item', 'debug'=>1, 'limit'=>10, 'ri'=>0, 'views'=>false,
		'where'=>'', 'type'=>15, 'source'=>'nature_reserves_england');

	chdir(__DIR__);
	require "./_scripts.inc.php";

##########################################

	$db = GeographDatabaseConnection(false);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

function queryExecute($sql,$debug) {
	global $db;

	if ($debug) {
		print date('r')."\n$sql;\n";
		$start= microtime(true);
		if (!($r = $db->Execute($sql)))
			 die("$sql\n".$db->ErrorMsg()."\n\n");
		$end= microtime(true);
		print date('r').sprintf(', took %.3f seconds, %d affected rows.',$end-$start,$db->Affected_Rows())."\n\n";
		return $r;
	} else {
		return $db->Execute($sql) or die("$sql\n".$db->ErrorMsg()."\n\n");
	}
}

##########################################

if (!empty($param['views'])) {
	foreach (array('gb'=>1,'ie'=>2) as $key => $ri) {
		queryExecute("DROP TABLE IF EXISTS {$key}_images",true);
		queryExecute($sql = "create table {$key}_images select gridimage_id,user_id,imagetaken,nateastings,natnorthings from gridimage inner join gridsquare using (gridsquare_id) where reference_index = $ri and nateastings > 0 and moderation_status in ('geograph')",true);
//todo, should make nateastings/narnortings SIGNED - so that easier to do maths without casting
//alter table gb_images modify `nateastings` mediumint(8) NOT NULL DEFAULT 0, modify `natnorthings` mediumint(8) NOT NULL DEFAULT 0;

		queryExecute("alter table {$key}_images modify `nateastings` mediumint(8) NOT NULL DEFAULT 0, modify `natnorthings` mediumint(8) NOT NULL DEFAULT 0, add primary key(gridimage_id), add index(natnorthings), comment=".$db->Quote($sql),true);

		//need to add the column, and spatial index. Can't add they spatial index while still empty
		queryExecute("alter table {$key}_images add point_en POINT not null");
		queryExecute("update {$key}_images set point_en = GEOMFROMTEXT(CONCAT('POINT(',nateastings,' ',natnorthings,')'))");
		queryExecute("alter table ie_images add SPATIAL INDEX(point_en)");

	}
}

##########################################

$columns = $db->getAssoc("DESCRIBE {$param['table']}");

foreach ($columns as $key => $row)
	if ($row['Key'] == 'PRI')
		$col = $key;

if (empty($col))
	$col = $param['col'];

##########################################

$where = array();
	$or = array();
	$or[] = "bound_images IS NULL"; //this allows us to 'prime' the table
	$or[] = "stat_updated < date_sub(now(),interval 90 day)";
	//$or[] = "gridimage_id = 0";  //will check the same row over and over!

$where[] = "(".implode(' OR ',$or).")";

if (isset($columns['reference_index']) && $param['ri'])
	$where[] = "reference_index = {$param['ri']}";

if ($param['type'])
	$where[] = "feature_type_id = {$param['type']}";

$c=0;

$where[] = "(e > 0 and n > 0)";

if (!empty($param['where']))
	$where[] = $param['where'];

$sql = "SELECT $col,feature_type_id,gridref,wgs84_lat,wgs84_long,radius,reference_index,e,n,gridimage_id,ST_ASTEXT(WKT) as geom
	FROM {$param['table']}
	INNER JOIN {$param['source']} ON (table_id = auto_id)
	WHERE ".implode(" AND ",$where)." LIMIT {$param['limit']}";

if (!empty($param['debug']))
	print "$sql\n";

##########################################

require_once('geograph/conversions.class.php');
require_once('geograph/conversionslatlong.class.php');
$conv = new ConversionsLatLong;


$recordSet = $db->Execute($sql) or die("$sql\n".$db->ErrorMsg()."\n\n");
if ($recordSet->RecordCount()) {

	//use a special table, precompiled, pre-filted by RI and ony nateastings>0

	if (!empty($param['debug']))
		print "Start = {$recordSet->fields[$col]}: ";

	$cols = array();
	if (isset($columns['bound_images']))	$cols[] = 'COUNT(*) as bound_images';
	if (isset($columns['stat_updated']))    $cols[] = 'NOW() AS stat_updated';
	if (isset($columns['first']))		$cols[] = 'MIN(gridimage_id) as first';
	if (isset($columns['last']))		$cols[] = 'MAX(gridimage_id) as last';
	if (isset($columns['recent']))		$cols[] = 'MAX(imagetaken) as recent';
	if (isset($columns['users']))		$cols[] = 'COUNT(DISTINCT user_id) as users';
	if (isset($columns['centis']))		$cols[] = 'COUNT(DISTINCT nateastings DIV 100, natnorthings DIV 100) as centis';

	//if (isset($columns['gridimage_id']))	$cols['gridimage_id'] = .. set dynamically below!

	#########################

	while (!$recordSet->EOF) {
		$r = $recordSet->fields;

		//NOTE!! Careful NOT to use continue here, as need to call MoveNext, but allow need the unset($col's)

		##################
		//find nearest image (needs dynamic coordinate!) but also only if there ISNT an image already!

		$iamges_table = ($r['reference_index'] == 2)?'ie_images':'gb_images';

		if (isset($columns['gridimage_id']) && empty($r['gridimage_id'])) {
			//$cols['gridimage_id'] = 'MIN(gridimage_id) as gridimage';
			$dist_sq = "pow(nateastings-{$r['e']},2)+pow(natnorthings-{$r['n']},2)"; //no bother sqrt as we only order anyway!

			$cols['gridimage_id'] = "GROUP_CONCAT(gridimage_id ORDER BY $dist_sq LIMIT 1) AS gridimage_id";
		}
		##################

		//here we rely entirely on the point_en SPATIAL index!



		$colstr = implode(', ',$cols);
		$sql = "SELECT $colstr from $iamges_table where ST_CONTAINS(ST_GeomFromText(".$db->Quote($r['geom'])."),point_en)";

		if (!empty($param['debug']) && !$c) {
			print_r($r);
			print "$sql\n";
		}

		$updates = $db->getRow($sql);

		if (empty($updates['bound_images']))
			$updates['bound_images'] = 0; //if there are no images, get null, but we use null to track progress!

		$sql = "UPDATE {$param['table']} SET `".implode('` = ?,`',array_keys($updates))."` = ? WHERE {$col} = ".$db->Quote($recordSet->fields[$col]);

		if (!empty($param['debug']) && !$c) {
			print "$sql\n";
			print_r($updates);
		}

		$db->Execute($sql,array_values($updates)) or die("$sql\n".$db->ErrorMsg()."\n\n");;
		$recordSet->MoveNext();
		$c++;
		if (!empty($param['debug']) && !($c%100)) print "$c. ";

		//these only for the one image
		unset($cols['gridimage_id']);
	}
}

##########################################

if (!empty($param['debug']))
	print "Done $c\n";

$recordSet->Close();
