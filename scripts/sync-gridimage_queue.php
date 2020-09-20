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
        'user_id'=>0,
        'right'=>'',
);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

############################################

$one = get_create($CONF['db_db'], 'gridimage');
$two = get_create($CONF['db_db'], 'gridimage_queue');

if ($one == $two) {
	print "Both tables appear in sync\n";
	print "$one;\n";
} else {
	preg_match('/AUTO_INCREMENT=(\d+)/',$one,$m);
	$inc = $m[1];



	if (!empty($two)) {
		$f1 = tempnam("/tmp",'t1');
		$f2 = tempnam("/tmp",'t2');

		file_put_contents($f1,$one);
		file_put_contents($f2,$two);
		$cmd = "diff -u $f1 $f2";
		print "$cmd;\n";
		print `$cmd | colordiff`;

		$sql = "ALTER TABLE gridimage_queue {$m[0]}";
	} else
		$sql = "CREATE TABLE {$CONF['db_db']}.gridimage_queue LIKE {$CONF['db_db']}.gridimage"; //this SHOULD copy auto-incement too!

	print "$sql;\n\n";
}



############################################

function get_create($database, $table) {
	global $db;

	$row = $db->getRow("SHOW CREATE TABLE $database.$table");

	return array_pop($row); //last value in row!
}
