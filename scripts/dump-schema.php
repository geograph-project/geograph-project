<?php
/**
 * $Project: GeoGraph $
 * $Id: conversion.php 2960 2007-01-15 14:33:27Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2008 Barry Hunter (geo@barryhunter.co.uk)
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

$param = array(
	'execute' => false,
	'simple' => true,
);

chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$rows = $db->getAll("select table_name,type,backup,TABLE_ROWS,DATA_LENGTH from _tables inner join information_schema.tables using (table_name) WHERE TABLE_SCHEMA = DATABASE() order by type,(backup = 'N'),table_name");

$list = array();
foreach ($rows as $row) {
	if (empty($row['type']))
		$row['type'] = 'unknown';

	if ($param['simple']) {
		print "{$row['table_name']},{$row['type']}: ";
		$keys = $db->getAssoc("DESCRIBE {$row['table_name']}");
		print implode(',',array_keys($keys))."\n";
		continue;
	}

	$key = array($CONF['db_db'],$row['type'],($row['backup']=='N')?'skipped':'backup');
	@$list[implode('.',$key)][] = $row['table_name'];
}

if ($param['simple'])
	exit;


$crit = "-h{$CONF['db_connect']} -u{$CONF['db_user']} -p{$CONF['db_pwd']} {$CONF['db_db']}";

foreach ($list as $key => $tables) {
	$cmd = "mysqldump $crit --no-data ".implode(' ',$tables)." > ../schema/schema.$key.mysql";

	print "$cmd\n";
}
