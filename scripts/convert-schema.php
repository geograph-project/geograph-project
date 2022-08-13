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
	's' => 'MyISAM',
	'd' => 'InnoDB',
	'type' => '',
	'q' => '',
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

print $DSN."\n";

print "database: ".$db->getOne("SELECT DATABASE()")."\n";

############################################

$where = array();
$where[] = "table_schema = DATABASE()";
//$where[] = "type+0 <=4";
if (!empty($param['rows']))
	$where[] = "TABLE_ROWS > ".$param['rows'];
if (!empty($param['s']))
        $where[] = "ENGINE = ".$db->Quote($param['s']);
else
	$where[] = "ENGINE != ".$db->Quote($param['d']);
if (!empty($param['type']))
	$where[] = "type LIKE ".$db->Quote($param['type']);

if (!empty($param['q']))
	$where[] = "table_name LIKE ".$db->Quote($param['q']);

if ($param['d'] == 'InnoDB')
	$where[] = "toidb_seconds IS NULL";

############################################

$where = implode(" AND ",$where);
$rows = $db->getAll("select table_name,title,type,backup,`sensitive`,ENGINE,TABLE_ROWS,DATA_LENGTH,INDEX_LENGTH,UPDATE_TIME
 from information_schema.tables left join _tables using (table_name)
 where $where order by type+0,table_name");

############################################

$r = '';
foreach ($rows as $row) {
	if ($row['table_name'] == 'gridimage_queue'
		 || strpos($row['table_name'],'checksum') !== FALSE
		 || strpos($row['table_name'],'tmp') === 0
		 || preg_match('/_(old|backup|back|merge|test|tmp|tmp2)$/',$row['table_name'])) {
		print "SKIPPING {$row['table_name']}\n\n";
		continue;
	}

	$bits = $db->getRow("SHOW CREATE TABLE {$row['table_name']}");
	$create = array_pop($bits);
	print "/* $create; */\n";

	$describe = $db->getAssoc("DESCRIBE {$row['table_name']}");

	$sep = "ALTER TABLE {$row['table_name']}\n";

	print "/* SELECT * FROM {$row['table_name']} PROCEDURE ANALYSE() */ \n";
$result = $db->getAll("SELECT * FROM {$row['table_name']} PROCEDURE ANALYSE()");

/*
             Field_name: geograph_live.gridimage_tag.updated
              Min_value: 0000-00-00 00:00:00
              Max_value: 2022-08-12 12:14:27
             Min_length: 19
             Max_length: 19
       Empties_or_zeros: 0
                  Nulls: 0
Avg_value_or_avg_length: 19.0000
                    Std: NULL
      Optimal_fieldtype: CHAR(19) NOT NULL
*/
	$sql = '';
	foreach ($result as $row) {
		$bits = explode('.',$row['Field_name']);
		$column = array_pop($bits);
		$sql .= "$sep  MODIFY `$column` {$row['Optimal_fieldtype']}";

		if (!empty($describe[$column]['Extra'])) {
			//the suggested type, does not include this, eg auto_increment
			 $sql .= " ".$describe[$column]['Extra'];
		}
		$sep = ",\n";
	}
	print "$sql;\n\n";

        if ($r != 'a')
	        $r = readline('Execute (n/y)? ');

        if ($r == 'y' || $r == 'a') {

                $start = microtime(true);
                $db->Execute($sql);
                $end = microtime(true);
                print date('r').sprintf(', took %.3f seconds, %d affected rows.',$end-$start,$db->Affected_Rows())."\n\n";

	}
}

