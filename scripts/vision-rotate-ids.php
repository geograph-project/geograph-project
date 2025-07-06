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

	$sql.= " select gi.gridimage_id, user_id ,realname, width, height, original_width, title, grid_reference
	 from gridimage_search gi inner join gridimage_size using (gridimage_id)
	 left join gridimage_label l on (l.gridimage_id = gi.gridimage_id and `model` = $model)
	 where l.gridimage_id is null limit $minimum";

	if ($param['execute']) {
		$db->Execute($sql);
		$added = $db->Affected_Rows();
	} else print "$sql;\n";

}

if ($deleted || $added) {
	print "Deleted: $deleted; Added: $added;\n";
}
