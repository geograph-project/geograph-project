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
$param=array('model'=>'clip','table'=>'tmp_label_clip','minimum'=>100000,
	'execute'=>false);


chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

##################################
$table = $param['table'];
$model = $db->Quote($param['model']);
$minimum = intval($param['minimum']);

$deleted = $added = null;
if ($db->getOne("SHOW TABLES LIKE '$table'")) {
	$sql = "delete t.* from $table t inner join gridimage_label using (gridimage_id) where model = $model";

	if ($param['execute']) {
		$db->Execute($sql);
		$deleted = $db->Affected_Rows();
	} else print "$sql;\n";

	$count = $db->getOne("SELECT COUNT(*) FROM $table");
} else  $count = -1;

if ($count < $minimum) {
	//$minimum -= $count; -- COULD only insert rows to being back to count!
	$sql = ($count < 0)?"create table $table (primary key(gridimage_id))":"insert ignore into $table";

	$sql.= " select gi.gridimage_id, user_id ,realname, width, height, original_width, title, grid_reference, if(ii.gridimage_id is null, 0, 1) as skip_fs
	 from gridimage_search gi inner join gridimage_size using (gridimage_id)
	 left join gridimage_label l on (l.gridimage_id = gi.gridimage_id and `model` = $model)
	 left join images_with_224 ii on (ii.gridimage_id = gi.gridimage_id)
	 where l.gridimage_id is null limit $minimum";

	if ($param['execute']) {
//print "$sql;\n";

		$db->Execute($sql);
		$added = $db->Affected_Rows();
	} else print "$sql;\n";

}

if ($deleted || $added) {
	print "Deleted: $deleted; Added: $added;\n";
}




/*

Primary.geograph_live>create table images_with_224 (gridimage_id int unsigned not null primary key);
Query OK, 0 rows affected (0.024 sec)

Primary.geograph_live>insert ignore into images_with_224 select gridimage_id from tag_dataset_top;
Query OK, 880003 rows affected (11.140 sec)
Records: 880003  Duplicates: 0  Warnings: 0

Primary.geograph_live>insert ignore into images_with_224 select gridimage_id from gridimage_label_archive where model in ('city','imageclass','subject','subjectv2','top','type','typev2');
Query OK, 2745871 rows affected, 65535 warnings (11 min 15.408 sec)
Records: 78762555  Duplicates: 76016684  Warnings: 76016684

*/
