<?php
/**
 * $Project: GeoGraph $
 * $Id: recreate_maps.php 2996 2007-01-20 21:39:07Z barry $
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

############################################

//these are the arguments we expect
$param=array(
	'host'=>'',
	'rows'=>0,
	's' => 'MRG_MyISAM',
	'd' => 'InnoDB',
);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$host = $CONF['db_connect'];
if ($param['host']) {
    $host = $param['host'];
}
print(date('H:i:s')."\tUsing server: $host\n");
$DSN = str_replace($CONF['db_connect'],$host,$DSN);

//uses $GLOBALS['DSN']
$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$database = $db->getOne("SELECT DATABASE()");
print "database: $database\n";

############################################

$where = array();
$where[] = "table_schema = DATABASE()";
$where[] = "table_name like '%\\_archive'";


############################################

$where = implode(" AND ",$where);
$rows = $db->getAll("select table_name,title,type,backup,`sensitive`,ENGINE,TABLE_ROWS,DATA_LENGTH,INDEX_LENGTH,UPDATE_TIME
 from information_schema.tables left join _tables using (table_name)
 where $where order by type+0,table_name");

############################################

foreach ($rows as $row) {

	restart:
	print str_repeat('#',80)."\n";
	print implode("\t",$row)."\n";


	$row['table_name'] = str_replace('_archive','',$row['table_name']);

	if ($row['table_name'] == 'user_archive' || $row['table_name'] == 'gridimage_daily_archive' || $row['table_name'] == 'gridimage_tag' || $row['table_name'] == 'autologin') {
		print "SKIPPING {$row['table_name']}\n\n";
		continue;
	}


	$sql = "select TABLE_NAME,INDEX_NAME,SEQ_IN_INDEX,COLUMN_NAME,INDEX_TYPE,CARDINALITY,DATA_TYPE 
	from information_schema.STATISTICS inner join information_schema.columns using (TABLE_SCHEMA,TABLE_NAME,COLUMN_NAME) 
	where TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ".$db->Quote($row['table_name']);
	$data = $db->getAll($sql);
	$primary = 0;
	if ($data) foreach ($data as $index) {
		if ($index['INDEX_NAME'] == 'PRIMARY') {
			$primary++;
			$pkey = $index['COLUMN_NAME'];
		}
		if ($index['INDEX_NAME'] == 'PRIMARY' && $index['SEQ_IN_INDEX'] > 1)
			print "Compound Primary key ({$index['COLUMN_NAME']})\n";
		if ($index['INDEX_NAME'] == 'PRIMARY' && strpos($index['DATA_TYPE'],'int') === FALSE)
			print "Non-Int Primary key ({$index['COLUMN_NAME']} : {$index['DATA_TYPE']})\n";

		if ($index['INDEX_TYPE'] == 'SPATIAL')
			print "Has Spatial Index ({$index['COLUMN_NAME']})\n";
		elseif ($index['INDEX_TYPE'] == 'FULLTEXT')
			print "Has Spatial Index ({$index['COLUMN_NAME']})\n";
	}
	if (empty($data))
		print "No Keys at all!\n";
	elseif (empty($primary))
		print "No Primary Key!\n";

	#################################################################

	$table = $row['table_name'];

	$count = $db->getOne("SELECT COUNT(*) FROM {$table}")+$db->getOne("SELECT COUNT(*) FROM {$table}_archive");
	$log = floor(log10($count));
	$div = pow(10,$log);

print "$count = $log = $div\n";

	$bits = array();
	foreach (range(0,ceil($count/$div)) as $p) {
		$max = ($p+1)*$div;
		$bits[] = "PARTITION p$p VALUES LESS THAN ($max)";
	}
	$bits[count($bits)-1] = preg_replace('/\(\d+\)/','MAXVALUE',$bits[count($bits)-1]);

	$sqls = array();
	if ($db->getOne("SHOW TABLES LIKE '{$table}_part'"))
		$sqls[] = "DROP TABLE {$table}_part";

	print "USE $database;\n"; //just to make sure if copy/pasting!
	$sqls[] = "CREATE TABLE {$table}_part LIKE {$table}";
	$sqls[] = "ALTER TABLE {$table}_part ENGINE {$param['d']} PARTITION BY RANGE($pkey) (\n".implode(",\n",$bits).")";


	//The correct way to use LOCK TABLES and UNLOCK TABLES with transactional tables, such as InnoDB tables, is to begin a transaction with SET autocommit = 0 (not START TRANSACTION) followed by LOCK TABLES, and to not call UNLOCK TABLES until you commit the transaction explicitly.

	$sqls[] = "SET autocommit = 0";
	if ($db->getOne("SHOW TABLES LIKE '{$table}_merge'")) {
		$sqls[] = "LOCK TABLES {$table} WRITE, {$table}_archive WRITE, {$table}_part WRITE, {$table}_merge WRITE";
		$sqls[] = "RENAME TABLE {$table}_merge TO {$table}_merge_old";
	} else
		$sqls[] = "LOCK TABLES {$table} WRITE, {$table}_archive WRITE, {$table}_part WRITE";

	$sqls[] = "INSERT INTO {$table}_part SELECT * FROM {$table}_archive";
	$sqls[] = "INSERT INTO {$table}_part SELECT * FROM {$table}";

	$sqls[] = "RENAME TABLE {$table}_archive TO {$table}_archive_old";
	$sqls[] = "RENAME TABLE {$table} TO {$table}_old";
	$sqls[] = "RENAME TABLE {$table}_part TO {$table}";
	$sqls[] = "COMMIT";
	$sqls[] = "UNLOCK TABLES";

	#################################################################

	foreach ($sqls as $sql)
		print "$sql;\n";

	$r = readline('Execute? ');
	if ($r == 'c') {
		$c = $db->getRow("SHOW CREATE TABLE `{$row['table_name']}`");
		print array_pop($c).";\n";
		goto restart;

	} elseif ($r == 'l') {
		$r = $db->getRow("SHOW TABLE STATUS FROM `geograph_live` LIKE '{$row['table_name']}'");
		print_r($r);
		goto restart;

	} elseif ($r == 'y') {
		foreach ($sqls as $sql) {
			print "$sql;\n";
			$start = microtime(true);
			$db->Execute($sql);
			$end = microtime(true);
			print date('r').sprintf(', took %.3f seconds, %d affected rows.',$end-$start,$db->Affected_Rows())."\n\n";
		}
	}
}

