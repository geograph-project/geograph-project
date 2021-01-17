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

	$param = array('table'=>'os_open_places', 'debug'=>1, 'limit'=>10, 'ri'=>1, 'd'=>250, 'views'=>false);

	chdir(__DIR__);
	require "./_scripts.inc.php";


##########################################

/*

implements
update os_open_places
	set images = (select count(*) from gb_images where natnorthings between mbr_ymin and mbr_ymax AND nateastings between mbr_xmin and mbr_xmax)
	where images IS NULL limit 10;

because mysql cant use indexes in such example!
*/

##########################################

	$db = GeographDatabaseConnection(false);

	/*
	//concoct a special writable connection to SECOND slave!
	$DSN_READ = $CONF['db_read_driver'].'://'.
                $CONF['db_user'].':'.$CONF['db_pwd'].
                '@'.$CONF['db_read_connect2'].
                '/'.$CONF['db_db'].$CONF['db_read_persist'];

	$db=NewADOConnection($DSN_READ);
	*/

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
$where[] = "first IS NULL"; //this allows us to 'prime' the table, later will be something like 'images_updated < date_sub(now(),interval 30 day)";
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
		if (empty($updates['first']))
			$updates['first'] = 0; //if there are no images, get null, but we use null to track progress!

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



/*

this query should be fast, as the subquery is fast with constants!

select *,(select count(*) from gb_images where natnorthings between mbr_ymin and mbr_ymax AND nateastings between mbr_xmin and mbr_xmax) as images from os_open_places limit 10;
10 rows in set (3 min 20.47 sec)


... see here uses INDEX!

mysql> explain select count(*) as images,grid_reference from gb_images where natnorthings between 1205180 and 1206421 and nateastings between 456837 and 457523;
+----+-------------+-----------+-------+---------------+--------------+---------+------+------+------------------------------------+
| id | select_type | table     | type  | possible_keys | key          | key_len | ref  | rows | Extra                              |
+----+-------------+-----------+-------+---------------+--------------+---------+------+------+------------------------------------+
|  1 | SIMPLE      | gb_images | range | natnorthings  | natnorthings | 3       | NULL |  934 | Using index condition; Using where |
+----+-------------+-----------+-------+---------------+--------------+---------+------+------+------------------------------------+
1 row in set (0.00 sec)

mysql> explain select *,(select count(*) from gb_images where natnorthings between mbr_ymin and mbr_ymax AND nateastings between mbr_xmin and mbr_xmax) as images from os_open_places limit 10;
+----+--------------------+----------------+------+---------------+------+---------+------+---------+------------------------------------------------+
| id | select_type        | table          | type | possible_keys | key  | key_len | ref  | rows    | Extra                                          |
+----+--------------------+----------------+------+---------------+------+---------+------+---------+------------------------------------------------+
|  1 | PRIMARY            | os_open_places | ALL  | NULL          | NULL | NULL    | NULL |   43976 | NULL                                           |
|  2 | DEPENDENT SUBQUERY | gb_images      | ALL  | natnorthings  | NULL | NULL    | NULL | 5856435 | Range checked for each record (index map: 0x1) |
+----+--------------------+----------------+------+---------------+------+---------+------+---------+------------------------------------------------+
2 rows in set (0.00 sec)

mysql> select mbr_ymin,mbr_ymax,mbr_xmin,mbr_xmax from os_open_places limit 10;
+----------+----------+----------+----------+
| mbr_ymin | mbr_ymax | mbr_xmin | mbr_xmax |
+----------+----------+----------+----------+
|  1205180 |  1206421 |   456837 |   457523 |
|  1203776 |  1204276 |   453587 |   454087 |
|  1204466 |  1204979 |   450536 |   451106 |
|  1200655 |  1201155 |   456310 |   456810 |
|  1204253 |  1204753 |   452545 |   453147 |
|  1200630 |  1201942 |   458600 |   460570 |
|  1201993 |  1203727 |   453678 |   454766 |
|  1216174 |  1216674 |   465051 |   466114 |
|  1208684 |  1210146 |   459745 |   460844 |
|  1213343 |  1213843 |   461049 |   461687 |
+----------+----------+----------+----------+
10 rows in set (0.00 sec)


*/
