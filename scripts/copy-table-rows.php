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
	'step'=>10000,
	's' => '',
	'd' => '',
);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

print "databaes: ".$db->getOne("SELECT DATABASE()")."\n";

if (empty($param['s']) || empty($param['d']))
	die("specify tables. Try --help\n");

############################################


	$table = $param['s'];

	$sql = "select TABLE_NAME,INDEX_NAME,SEQ_IN_INDEX,COLUMN_NAME,INDEX_TYPE,CARDINALITY,DATA_TYPE 
	from information_schema.STATISTICS inner join information_schema.columns using (TABLE_SCHEMA,TABLE_NAME,COLUMN_NAME) 
	where TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ".$db->Quote($table);
	$data = $db->getAll($sql);

	$primary = 0;
	$pcolumn = null;
	if ($data) foreach ($data as $index) {
		if ($index['INDEX_NAME'] == 'PRIMARY')
			$primary++;
		if ($index['INDEX_NAME'] == 'PRIMARY' &&  $index['SEQ_IN_INDEX'] == 1 && strpos($index['DATA_TYPE'],'int') !== FALSE)
			$pcolumn = $index['COLUMN_NAME'];
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
		die("No Keys at all!\n");
	elseif (empty($primary) || empty($pcolumn))
		die("No Primary Key!\n");


	$skeys = implode(', ',array_keys($db->getAssoc("DESCRIBE {$param['s']}")));
	$dkeys = implode(', ',array_keys($db->getAssoc("DESCRIBE {$param['d']}")));

	print "Source: $skeys\n";
	print "Dest  : $dkeys\n";

	if ($skeys != $dkeys)
		die("Columns dont match!\n");

	$data = $db->getRow("SELECT MIN($pcolumn) as mn, MAX($pcolumn) AS mx FROM $table");
print_r($data);
	if ($data['mx']-$data['mn'] < 10)
		die("not enough rows\n");

	$sqls = array();
	foreach (range($data['mn'],$data['mx'],$param['step']) as $start) {
		$end = $start + $param['step'] -1;

		$sqls[] = "INSERT INTO {$param['d']} SELECT * FROM $table WHERE $pcolumn BETWEEN $start AND $end";
	}

	foreach ($sqls as $sql)
		print "$sql;\n";

	$r = readline('Execute? ');
	if ($r == 'y') {
		foreach ($sqls as $sql) {
			$start = microtime(true);
			$db->Execute($sql);
			$end = microtime(true);
			print date('r').sprintf(', took %.3f seconds, %d affected rows.',$end-$start,$db->Affected_Rows())."\n\n";
		}
	}


