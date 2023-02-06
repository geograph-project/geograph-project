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

	$param = array('table'=>'feature_item', 'debug'=>1, 'limit'=>10, 'ri'=>1, 'd'=>250, 'views'=>false);

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

		queryExecute("alter table {$key}_images add primary key(gridimage_id), add index(natnorthings), comment=".$db->Quote($sql),true);
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
	$or[] = "nearby_images IS NULL"; //this allows us to 'prime' the table
	$or[] = "stat_updated < date_sub(now(),interval 30 day)";
	//$or[] = "gridimage_id = 0";  //will check the same row over and over!

$where[] = "(".implode(' OR ',$or).")";

if (isset($columns['reference_index']))
	$where[] = "reference_index = {$param['ri']}";

$c=0;

$where[] = "(e > 0 OR wgs84_lat > 0)";

$sql = "SELECT $col,gridref,wgs84_lat,wgs84_long,radius,reference_index,e,n,gridimage_id FROM {$param['table']}
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
	$iamges_table = ($param['ri'] == 2)?'ie_images':'gb_images';

	if (!empty($param['debug']))
		print "Start = {$recordSet->fields[$col]} via $iamges_table: ";

	$cols = array();
	if (isset($columns['images']))		$cols[] = 'COUNT(*) as images';
	if (isset($columns['nearby_images']))	$cols[] = 'COUNT(*) as nearby_images';
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
		##################
		//compute missing e/n (from lat/long)

		if (empty($r['e']) && !empty($r['wgs84_lat'])) { //remember longitude could be 0!

			list($e,$n,$reference_index) = $conv->wgs84_to_national($r['wgs84_lat'],$r['wgs84_long'],true);
			if ($reference_index != $param['ri']) {
				//todo, we could still store the coordinate! having computed it!
				$recordSet->MoveNext();
				continue;
			}
			$e = intval($e);
			$n = intval($n);

			//put in $cols so they get fed though to $updates!
			$r['e'] = $e; $cols['e'] = "$e as e";
			$r['n'] = $n; $cols['n'] = "$n as n";
			$cols['reference_index'] = "$reference_index as reference_index";

		} else {
			unset($cols['e']);
			unset($cols['n']);
			unset($cols['reference_index']);
		}
		##################
		//compute missing gridref

		if (empty($r['gridref'])) {
			list($cols['gridref'],) = $conv->national_to_gridref($r['e'],$r['n'],8,$r['reference_index']); //8 shouldnt be harccoded!
			$cols['gridref'] = "'{$cols['gridref']}' as gridref";
		} else {
			unset($cols['gridref']);
		}
		##################
		//find nearest image (needs dynamic coordinate!) but also only if there ISNT an image already!

		if (isset($columns['gridimage_id']) && empty($r['gridimage_id'])) {
			//$cols['gridimage_id'] = 'MIN(gridimage_id) as gridimage';
			$dist_sq = "pow(nateastings-{$r['e']},2)+pow(natnorthings-{$r['n']},2)"; //no bother sqrt as we only order anyway!
			$cols['gridimage_id'] = "GROUP_CONCAT(gridimage_id ORDER BY $dist_sq LIMIT 1) AS gridimage_id";
		} else {
			unset($cols['gridimage_id']);
		}
		##################

		$d = $r['radius'] ?? $param['d'];
		$r['mbr_ymin'] = $r['n'] - $d;
		$r['mbr_ymax'] = $r['n'] + $d;
		$r['mbr_xmin'] = $r['e'] - $d;
		$r['mbr_xmax'] = $r['e'] + $d;

		$colstr = implode(', ',$cols);
		$sql = "SELECT $colstr from $iamges_table where natnorthings between {$r['mbr_ymin']} and {$r['mbr_ymax']} AND nateastings between {$r['mbr_xmin']} and {$r['mbr_xmax']}";

		if (!empty($param['debug']) && !$c)
			print "$sql\n";

		$updates = $db->getRow($sql);

		if (empty($updates['nearby_images']))
			$updates['nearby_images'] = 0; //if there are no images, get null, but we use null to track progress!


		$sql = "UPDATE {$param['table']} SET `".implode('` = ?,`',array_keys($updates))."` = ? WHERE {$col} = ".$db->Quote($recordSet->fields[$col]);

		if (!empty($param['debug']) && !$c) {
			print "$sql\n";
			print_r($updates);
		}

		$db->Execute($sql,array_values($updates)) or die("$sql\n".$db->ErrorMsg()."\n\n");;
		$recordSet->MoveNext();
		$c++;
		if (!empty($param['debug']) && !($c%100)) print "$c. ";
	}
}

##########################################

if (!empty($param['debug']))
	print "Done $c\n";

$recordSet->Close();
