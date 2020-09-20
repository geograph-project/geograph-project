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
        'id'=>2405926,
	'column'=>'gridimage_id',
);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;



$tables = $db->getCol("SELECT TABLE_NAME FROM information_schema.columns where table_schema = DATABASE() AND column_name = '{$param['column']}' and COLUMN_KEY != ''");

if ($param['column'] == 'gridimage_id')
	$tables[] = 'tag_public'; //its actully a view!

foreach ($tables as $table) {
	$rows = $db->getAll("SELECT * FROM {$table} WHERE {$param['column']} = {$param['id']} LIMIT 10");
	if (!empty($rows)) {
		print str_repeat('-',80)."\n";
		print "TABLE = $table\n";
		if (count($rows) == 1) {
			foreach ($rows[0] as $key => $value) {
				printf("%40s : %s\n", $key, $value);
			}
		} else {
			print implode("\t",array_keys($rows[0]))."\n";
			foreach ($rows as $row) {
				print implode("\t",$row)."\n";
			}
		}
		print "\n";
	}
}
