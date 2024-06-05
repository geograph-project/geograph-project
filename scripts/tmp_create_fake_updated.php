<?php
/**
 * $Project: GeoGraph $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2022 Barry Hunter (geo@barryhunter.co.uk)
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

//these are the arguments we expect
$param=array('execute'=>false);

$current = getcwd();

chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

##################################

$dir = "backups/by-table/";

$rows = $db->getAll("
select table_name,column_name,TABLE_ROWS,UPDATE_TIME,backup
from information_schema.columns
 inner join information_schema.tables using (TABLE_SCHEMA,TABLE_NAME)
 inner join _tables using (table_name)
where TABLE_SCHEMA = DATABASE() AND column_default = 'current_timestamp()' AND extra = 'on update current_timestamp()'
 and UPDATE_TIME IS NULL and ENGINE != 'MRG_MyISAM' AND TABLE_ROWS > 0
 AND fake_updated IS NULL
");


foreach ($rows as $row) {
	print implode("\t",$row)."\n";
	$sql = "SELECT MAX({$row['column_name']}) FROM {$row['table_name']}";
	print "$sql;\n";

	$date = $db->getOne($sql);

	$sql = "UPDATE _tables SET fake_updated = '{$date}' WHERE table_name = '{$row['table_name']}'";
	print "$sql;\n";

	if ($param['execute'])
		$db->Execute($sql);

	print "\n";
//	exit;
}
