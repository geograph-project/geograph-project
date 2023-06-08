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

	$param = array('debug'=>1, 'limit'=>10, 'views'=>false, 'execute'=>0);

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

if (!empty($param['views']) && $param['views'] === 'delta') {
	foreach (array('gb'=>1,'ie'=>2) as $key => $ri) {
		print "#  ---- spatial for $key -> $ri\n";

		$last = $db->getOne("SELECT MAX(gridimage_id) FROM {$key}_images");

		queryExecute($sql = "replace into {$key}_images
		select gridimage_id,user_id,imagetaken,nateastings,natnorthings, GEOMFROMTEXT(CONCAT('POINT(',nateastings,' ',natnorthings,')')) AS point_en
		from gridimage inner join gridsquare using (gridsquare_id)
		where reference_index = $ri and nateastings > 0 and moderation_status in ('geograph') AND gridimage_id > $last",true);
		print "\n\n";
	}

} elseif (!empty($param['views'])) {
	foreach (array('gb'=>1,'ie'=>2) as $key => $ri) {
		print "#  ---- spatial for $key -> $ri\n";

		queryExecute("DROP TABLE IF EXISTS {$key}_images",true);
		queryExecute($sql = "create table {$key}_images select gridimage_id,user_id,imagetaken,nateastings,natnorthings from gridimage inner join gridsquare using (gridsquare_id) where reference_index = $ri and nateastings > 0 and moderation_status in ('geograph')",true);

		// make nateastings/narnortings SIGNED - so that easier to do maths without casting
		queryExecute("alter table {$key}_images modify `nateastings` mediumint(8) NOT NULL DEFAULT 0, modify `natnorthings` mediumint(8) NOT NULL DEFAULT 0, add primary key(gridimage_id), add index(natnorthings), comment=".$db->Quote($sql),true);

		//need to add the column, and spatial index. Can't add they spatial index while still empty
		queryExecute("alter table {$key}_images add point_en POINT not null",true);
		queryExecute("update {$key}_images set point_en = GEOMFROMTEXT(CONCAT('POINT(',nateastings,' ',natnorthings,')'))",true);
		queryExecute("alter table {$key}_images add SPATIAL INDEX(point_en)",true);
		print "\n\n";
	}
}

##########################################
// lookup the key in the main table (not the table containing the WKT column)

	//technically we dont 'need' the hectad column, but only do it on the setup subdevided ones
$sql = "select table_schema,table_name,group_concat(column_name),count(*) from information_schema.columns
 where column_name in ('WKT','bound_images','auto_id','hectad','stat_updated') group by  table_schema,table_name having count(*) = 5";
$sql .= " and table_schema = DATABASE()";
$tables = $db->getAll($sql);

$c = 0;
foreach ($tables as $rr) {
	$param['table'] = $rr['table_schema'].'.'.$rr['table_name'];

	$columns = $db->getAssoc("DESCRIBE {$param['table']}");

	$param['ri'] = preg_match('/(_|\b)(ie|ni|ireland)(\b|_)/',$rr['table_name'])?2:1;

	$function = ($param['ri'] == 2)?'GetCountIE':'GetCountGB';

	//shouldnt need to run this, but this is a convenient place to list them
	if ($param['debug']>1)
		print "php scripts/create_hectad_table.php --table={$rr['table_name']} --ri={$param['ri']} --limit=1000\n";

	##########################################

	$where = array();
		$or = array();
		$or[] = "bound_images IS NULL"; //this allows us to 'prime' the table
		$or[] = "stat_updated < date_sub(now(),interval 90 day)";

	$where[] = "(".implode(' OR ',$or).")";

	if (isset($columns['reference_index']) && $param['ri'])
		$where[] = "reference_index = {$param['ri']}";

	##########################################

	$sql = "UPDATE {$param['table']} SET bound_images = $function(WKT) WHERE ".implode(" AND ",$where)." LIMIT {$param['limit']}";

	if (!empty($param['debug']))
		print "$sql;\n";

	if ($param['execute']) {
		$db->Execute($sql);
		$c+=$db->Affected_Rows();


		$t2 = str_replace('_hectad','',$param['table']);
		$t3 = $t2."_counter";
		//cant run a update with a group by query!
		$sql1 = "create temporary table $t3 (primary key (auto_id)) SELECT auto_id,SUM(bound_images) AS bound_images FROM {$param['table']} GROUP BY auto_id";

		//in theory should always be a non-hectad table (that the hectad table was created from!
		if ($db->getOne("show columns from $t2 like 'bound_images'")) {
			print "$sql1;\n";
			$db->Execute($sql1);
			$sql = "UPDATE {$t2} INNER JOIN {$t3} USING (auto_id) SET $t2.bound_images = $t3.bound_images";
			print "$sql;\n";
		}
		/* if ($type_id = $db->getOne("SELECT feature_type_id FROM feature_type WHERE source_table = '$t2' and item_columns LIKE '%bound_images%'"))
			$db->Execute($sql1);
			$sql = "UPDATE feature_item INNER JOIN {$t3} ON (feature_type_id = $type_id AND table_id = auto_id) SET feature_item.bound_images = $t3.bound_images"; //TODO check what timestamp columns need updatig!
			$db->Execute($sql);
		*/
	}

	##########################################

	if (!empty($param['debug']) && $param['execute'])
		print "Done $c\n";
}
