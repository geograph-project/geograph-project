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
	'exif'=>false,
	'compact'=>false,
);

if (!empty($_SERVER['argv'][1]) && is_numeric($_SERVER['argv'][1])) {
	$param['id'] = $_SERVER['argv'][1];
	unset($_SERVER['argv'][1]); //avoid confusing hte normal parser!

	if (!empty($_SERVER['argv'][2]) && is_numeric($_SERVER['argv'][2])) {
		$param['compact'] = 1;
		unset($_SERVER['argv'][2]); //avoid confusing hte normal parser!
	}
}

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

if ($param['compact']) {
	$tables = array('gridimage','gridimage_size','gridimage_thumbsize','gridimage_pending','submission_method');
} else {

	$tables = $db->getCol("SELECT TABLE_NAME FROM information_schema.columns where table_schema = DATABASE() AND column_name = '{$param['column']}' and COLUMN_KEY != ''");
	if ($param['column'] == 'gridimage_id')
		$tables[] = 'tag_public'; //its actully a view!
}

foreach ($tables as $table) {
	$rows = $db->getAll("SELECT * FROM {$table} WHERE {$param['column']} = {$param['id']} LIMIT 10");
	if (!empty($rows)) {
		print str_repeat('-',80)."\n";
		print "TABLE = $table\n";
		if (count($rows) == 1) {
			foreach ($rows[0] as $key => $value) {
				if (strpos($key,'point_')===0)
					$value = urlencode($value);
				printf("%40s : %s\n", $key, $value);
			}
			if ($param['exif'] && !empty($rows[0]['exif']))
				print_r(unserialize($rows[0]['exif']));
			if (!empty($rows[0]['upload_id']) && !empty($rows[0]['user_id'])) {
				 check_keys($rows[0]['upload_id'],$rows[0]['user_id']);
			} elseif(!empty($rows[0]['preview_key'])) {
				$user_id = $db->getOne("SELECT user_id FROM gridimage WHERE {$param['column']} = {$param['id']}");

				check_keys($rows[0]['preview_key'],$user_id);
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
