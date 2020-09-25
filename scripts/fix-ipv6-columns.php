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
);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

############################################


$data = $db->getAll("select TABLE_SCHEMA,TABLE_NAME,COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,COLUMN_DEFAULT,COLUMN_COMMENT
	 from information_schema.columns
	 where column_name like '%ip%' and table_schema like 'geograph%'
	and column_name not like '%snippet%' and column_name not like '%descript%' and column_name not like '%skip%'");


foreach ($data as $row) {
	print str_repeat('#',80)."\n";
	print implode("\t",$row)."\n\n";
	$table = "`".$row['TABLE_SCHEMA']."`.`".$row['TABLE_NAME']."`";

	$sql = null;
	if (preg_match('/varchar\((\d+)\)/',$row['COLUMN_TYPE'],$m)) {
			//https://stackoverflow.com/questions/1076714/max-length-for-client-ip-address/1076749
		if ($m[1] < 45) {
			new_type($table,$row,"varchar(45)");
		}

	} elseif(preg_match('/varbinary\((\d+)\)/',$row['COLUMN_TYPE'],$m)) {
			//https://mariadb.com/kb/en/inet6_aton/
		if ($m[1] < 16) {
                        new_type($table,$row,"varbinary(16)");
                }

		if ($count = $db->getOne("SELECT COUNT(*) FROM $table WHERE `{$row['COLUMN_NAME']}` regexp binary '^[[:digit:]]+$'")) {

			$sql = "UPDATE $table SET `{$row['COLUMN_NAME']}` = inet6_aton(inet_ntoa(`{$row['COLUMN_NAME']}`))";
			$sql .=  get_keep_timestamp($table);
			$sql .= " WHERE `{$row['COLUMN_NAME']}` regexp binary '^[[:digit:]]+$'";
			print "$sql; #modifies $count rows\n";
		}

	} elseif (preg_match('/int\((\d+)\)/',$row['COLUMN_TYPE'],$m)) {

			//add temporally column
				$sql = "ALTER TABLE $table ADD `{$row['COLUMN_NAME']}_string` VARCHAR(32) NOT NULL";
			print "$sql;\n";
			//fill will data
				$sql = "UPDATE $table SET `{$row['COLUMN_NAME']}_string` = inet_ntoa(`{$row['COLUMN_NAME']}`)";
				$sql .=  get_keep_timestamp($table);
				$sql .= " WHERE `{$row['COLUMN_NAME']}` > 0";
			print "$sql;\n";

			print "\n";

		//modify original
			new_type($table,$row,"varbinary(16)");

		//updated it in place
		$sql = "UPDATE $table SET `{$row['COLUMN_NAME']}` = inet6_aton(inet_ntoa(`{$row['COLUMN_NAME']}`))";
		$sql .=  get_keep_timestamp($table);
		$sql .= " WHERE `{$row['COLUMN_NAME']}` regexp binary '^[[:digit:]]+$'";
		print "$sql;\n";

			print "\n";

			//check it
			$sql = "SELECT * FROM $table WHERE inet6_ntoa(`{$row['COLUMN_NAME']}`) != `{$row['COLUMN_NAME']}_string`";
			print "$sql;\n";

			//drop the temporally column
				 $sql = "ALTER TABLE $table DROP `{$row['COLUMN_NAME']}_string`";
			print "$sql;\n";
	} else {
		print "unknown type?\n";
	}

	if ($sql) {
	$cmd = "ack '\b{$row['TABLE_NAME']}\b' -B4 -A10 --group --color | grep --color -e '^' -e '{$row['COLUMN_NAME']}' -e 'getRemoteIP' -e 'inet_aton' -i";
	print "\n$cmd\n";
	passthru($cmd);

	print "\n\n";
	exit;
	}
}



###################################################

function get_keep_timestamp($table) {
	global $db;
	$rows = $db->getAll("DESCRIBE $table");
	foreach ($rows as $row)
		if (stripos($row['Extra'],'current_timestamp') !== FALSE)
			return ", `{$row['Field']}` = `{$row['Field']}`";
	return '';
}

function new_type($table,$row,$newtype) {
	global $db;
	$sql = "ALTER TABLE $table MODIFY `{$row['COLUMN_NAME']}` ";

	$sql .= $newtype;

	if ($row['IS_NULLABLE'] == 'NO')
		$sql .= " NOT NULL";

	if (!is_null($row['COLUMN_DEFAULT']))
		$sql .= " DEFAULT ".($row['COLUMN_DEFAULT']); //it comes out quoted (so can be string NULL ! for sql null, getting null means NO default, rather than null default!

	if (!empty($row['COLUMN_COMMENT']))
		$sql .= " COMMENT ".$db->Quote($row['COLUMN_COMMENT']);

	print "$sql;\n";
	return $sql;
}



