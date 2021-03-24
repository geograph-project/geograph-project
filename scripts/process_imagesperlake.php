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

	$param = array('table'=>'gblakes', 'debug'=>1, 'limit'=>10, 'ri'=>1, 'd'=>250, 'views'=>false);

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
		if (!($r =& $db->Execute($sql)))
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
$where[] = "images IS NULL"; //this allows us to 'prime' the table, later will be something like 'images_updated < date_sub(now(),interval 30 day)";
if (isset($columns['reference_index']))
	$where[] = "reference_index = {$param['ri']}";

$c=0;
if (empty($columns['mbr_ymax'])) {
	$d = $param['d'];

	$sql = "SELECT $col,n-$d AS mbr_ymin,n+$d AS mbr_ymax,e-$d AS mbr_xmin,e+$d AS mbr_xmax FROM {$param['table']}
		WHERE ".implode(" AND ",$where)." LIMIT {$param['limit']}";

} else {

	$sql = "SELECT $col,mbr_ymin,mbr_ymax,mbr_xmin,mbr_xmax FROM {$param['table']} WHERE ".implode(" AND ",$where)." LIMIT {$param['limit']}";
}

if (!empty($param['debug']))
	print "$sql\n";

##########################################

$recordSet = &$db->Execute($sql) or die("$sql\n".$db->ErrorMsg()."\n\n");
if ($recordSet->RecordCount()) {

	//use a special table, precompiled, pre-filted by RI and ony nateastings>0
	$iamges_table = ($param['ri'] == 2)?'ie_images':'gb_images';

	if (!empty($param['debug']))
		print "Start = {$recordSet->fields[$col]} via $iamges_table: ";

	$cols = array();
	if (isset($columns['images']))		$cols[] = 'COUNT(*) as images';
	if (isset($columns['images_updated']))  $cols[] = 'NOW() AS images_updated';
	if (isset($columns['first']))		$cols[] = 'MIN(gridimage_id) as first';
	if (isset($columns['last']))		$cols[] = 'MAX(gridimage_id) as last';
	if (isset($columns['recent']))		$cols[] = 'MAX(imagetaken) as recent';
	if (isset($columns['users']))		$cols[] = 'COUNT(DISTINCT user_id) as users';
	if (isset($columns['centis']))		$cols[] = 'COUNT(DISTINCT nateastings DIV 100, natnorthings DIV 100) as centis';
	$cols = implode(', ',$cols);

	#########################

	while (!$recordSet->EOF) {
		$r = $recordSet->fields;

		$updates = $db->getRow($sql = "SELECT $cols from $iamges_table where natnorthings between {$r['mbr_ymin']} and {$r['mbr_ymax']} AND nateastings between {$r['mbr_xmin']} and {$r['mbr_xmax']}");
		if (empty($updates['images']))
			$updates['images'] = 0; //if there are no images, get null, but we use null to track progress!

		if (!empty($param['debug']) && !$c)
			print "$sql\n";

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
