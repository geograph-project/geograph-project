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
$param=array('model'=>'clip','table'=>'tmp_label_clip','minimum'=>100000, 'start'=>8000000, 'end'=>9000000,
	'execute'=>false, 'purge'=>false, 'check'=>false, 'redo'=>false);


chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

##################################

if ($param['redo']) {
	$row = $db->getRow("SELECT * FROM tmp_embedding_check WHERE failed > 0 LIMIT 1");
	if (empty($row))
		die("noen\n");
	$param['start'] = $row['first'];
	$param['end'] = $row['last'];

	//delete the failed records, otherwise, wont get injected into tmp_label_clip
	$query = "DELETE FROM gridimage_label WHERE model = 'clip' AND label = 'Failed' AND gridimage_id BETWEEN {$param['start']} AND {$param['end']}";
	print "$query;\n";
		if ($param['execute'])
			$db->Execute($query);
	//delete these, because tehre isa  fair chance this flag was wrong, and there isnt a thumb available, hence the failure. This allows retry!
	$query = "DELETE FROM images_with_224 WHERE gridimage_id BETWEEN {$param['start']} AND {$param['end']}";
	print "$query;\n";
		if ($param['execute'])
			$db->Execute($query);
	//finally update this, so this can move progress!
	$query = "UPDATE tmp_embedding_check SET failed=NULL WHERE shard={$row['shard']}"; //need to reset this other will just keep repeating!
	print "$query;\n";
		if ($param['execute'])
			$db->Execute($query);
	//note falls thoguh and does the actual update. ...
}

if ($param['check']) {
        $max_id = $db->getOne("SELECT max(gridimage_id) FROM gridimage_search");
        $total = 0;

	$sql = "select gi.gridimage_id div 50000 as shard, min(gi.gridimage_id) as first, max(gi.gridimage_id) as last, count(distinct gi.gridimage_id) as count
	 ,count(distinct l.gridimage_id) as l_count, count(distinct e.gridimage_id) as e_count, count(distinct if(l.label = 'Failed',l.gridimage_id,NULL)) as failed
	from gridimage_search gi
	 left join gridimage_label l on (l.gridimage_id = gi.gridimage_id and l.model = 'clip')
         left join gridimage_embedding e on (e.gridimage_id = gi.gridimage_id and e.model = 'clip' and e.type='image')
	where gi.gridimage_id between \$start and \$end group by gridimage_id div 50000";

	//create table tmp_embedding_check (primary key (shard))

        for($start = 0; $start < $max_id; $start+=500000) {
                $end = $start + 500000 - 1;
		$query = "REPLACE INTO tmp_embedding_check ".str_replace('$end',$end,str_replace('$start',$start,$sql));
		print "$query;";
		if ($param['execute']) {
			$db->Execute($query);
			$aff = $db->Affected_Rows();
			print " =$aff";
			$total += $aff;
		}
		print "\n";
	}
	print "total=$total\n";
	exit;
}

##################################
// becaues embedding process is actully tracked by gridimage_label, but there was some labels inserted, without embedding, need to purge them, so both clip-labels AND clip-embedding can be done

//this should be a ONE OFF process. jsut here, as want to do it as a series of little queries rather than one big one!

if ($param['purge']) {
	$max_id = $db->getOne("SELECT max(gridimage_id) FROM gridimage_label");
	$total = 0;

	for($start = 1; $start < $max_id; $start+=20000) {
		$end = $start + 20000 - 1;
		$sql = "delete l.* from  gridimage_label l left join gridimage_embedding e using (gridimage_id) where l.model = 'clip' and e.gridimage_id is null and l.gridimage_id between $start and $end";
		print "$sql; ";
		if ($param['execute']) {
			$db->Execute($sql);
			$deleted = $db->Affected_Rows();
			print " =$deleted";
			$total += $deleted;
		}
		print "\n";
	}
	print "total=$total\n";
	exit;
}

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

//todo, figure out a way to make {$param['start'] dynamic
// ... something like select MAX(gridimage_id) from gridimage_label where model = 'clip'
// but might need to allow for out of order processing? 


	$sql.= " select gi.gridimage_id, user_id ,realname, width, height, original_width, title, grid_reference, if(ii.gridimage_id is null, 0, 1) as skip_fs
	 from gridimage_search gi inner join gridimage_size using (gridimage_id)
	 left join gridimage_label l on (l.gridimage_id = gi.gridimage_id and `model` = $model)
	 left join images_with_224 ii on (ii.gridimage_id = gi.gridimage_id)
	 where l.gridimage_id is null and gi.gridimage_id between {$param['start']} and {$param['end']} limit $minimum";

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
