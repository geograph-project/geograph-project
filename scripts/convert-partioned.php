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

	'table'=>'',

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


if (!empty($param['table'])) {
	$where[] = "table_name = ".$db->Quote($param['table']);

} else {
	$where[] = "table_name like '%\\_archive'";
}

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
	$table = $row['table_name'];

	if ($db->getOne("SHOW TABLES LIKE '{$table}_old'"))
		die("{$table}_old already exists!\n");
	if ($db->getOne("SHOW TABLES LIKE '{$table}_archive_old'"))
		die("{$table}_archive_old already exists!\n");
	if ($db->getOne("SHOW TABLES LIKE '{$table}_part'"))
		print("{$table}_part already exists - will be deleted!\n");

	#################################################################

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


//	$count = $db->getOne("SELECT COUNT(*) FROM {$table}")+$db->getOne("SELECT COUNT(*) FROM {$table}_archive");
	$max_id = max($db->getOne("SELECT MAX($pkey) FROM {$table}"),$db->getOne("SELECT MAX($pkey) FROM {$table}_archive"));
	$log = floor(log10($max_id))-1;
	$div = pow(10,$log);

print "Max: $max_id = $log = $div\n";

	$bits = array();
	foreach (range(0,ceil($max_id/$div)) as $p) {
		$max = ($p+1)*$div;
		$bits[] = "PARTITION p$p VALUES LESS THAN ($max)";
	}
	$bits[count($bits)-1] = preg_replace('/\(\d+\)/','MAXVALUE',$bits[count($bits)-1]);

	$sqls = array();
	if ($db->getOne("SHOW TABLES LIKE '{$table}_part'"))
		$sqls[] = "DROP TABLE {$table}_part";

	#################################################################
	//copy data first, may be really slow!

	print "USE $database;\n"; //just to make sure if copy/pasting!
	$sqls[] = "CREATE TABLE {$table}_part LIKE {$table}";
	$sqls[] = "ALTER TABLE {$table}_part ENGINE {$param['d']} PARTITION BY RANGE($pkey) (\n".implode(",\n",$bits)."\n)";




$where = "WHERE (user_id > 0 AND use_timestamp > DATE_SUB(NOW(),INTERVAL 10 YEAR))
OR favorite = 'Y'
OR searchclass = 'Special'
OR use_timestamp > DATE_SUB(NOW(),INTERVAL 1 YEAR)";
//keep 'special' as they are the ones that join on gridimage_query

	$sqls[] = "INSERT INTO {$table}_part SELECT * FROM {$table}_archive $where";
	$sqls[] = "REPLACE INTO {$table}_part SELECT * FROM {$table} $where";

	#################################################################
	//then do the final reorg

	//The correct way to use LOCK TABLES and UNLOCK TABLES with transactional tables, such as InnoDB tables, is to begin a transaction with SET autocommit = 0 (not START TRANSACTION) followed by LOCK TABLES, and to not call UNLOCK TABLES until you commit the transaction explicitly.



	$sqls[] = "SET autocommit = 0";


	if ($db->getOne("SHOW TABLES LIKE '{$table}_merge'")) {
		$sqls[] = "LOCK TABLES {$table} WRITE, {$table}_archive WRITE, {$table}_part WRITE, {$table}_merge WRITE";
		$sqls[] = "RENAME TABLE {$table}_merge TO {$table}_merge_old";
	} else
		$sqls[] = "LOCK TABLES {$table} WRITE, {$table}_archive WRITE, {$table}_part WRITE";

	//might be slow but unavoidable?
	$sqls[] = "REPLACE INTO {$table}_part SELECT * FROM {$table} WHERE $pkey > $max_id";
	$sqls[] = "REPLACE INTO {$table}_part SELECT * FROM {$table} WHERE use_timestamp > DATE_SUB(NOW(),INTERVAL 1 HOUR)";

	$sqls[] = "UNLOCK TABLES"; //but cant rename while have the table lock!

	$sqls[] = "RENAME TABLE {$table}_archive TO {$table}_archive_old "
			. ", {$table} TO {$table}_old"
			. ", {$table}_part TO {$table}";
	$sqls[] = "COMMIT";

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

