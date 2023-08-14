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

/* Having Scanned EFS with scan-efs-hashes.php / scan-efs-hashes_old.php
... then need to run a few cleanups ...

1) Repair failed submissions (run revive-temp-uploads.php)
2) Remove sucessful submissions (done by remove-temp-uploads.php directly for now)
3) Remove duplicates in temp folder (same image uploaded multiple times!)
4) Remove temp where duplicates a submission (probably uploaded multiple times, and only one submitted)

5) Finally we should perhaps delete tmp uploads over 90 days. (atlhoguh only after running 1 above!)

At the moment dont look for dupictes bedeteen tmp and old, although could clear them too?
*/

############################################

$param=array(
	'step' => 1,
	'limit' => 1,
	'print'=>false,
	'execute'=>false,
	'progress'=>false,
);

chdir(__DIR__);
require "./_scripts.inc.php";

if (empty($param['execute']))
	$param['print'] = true; //so prints if dont specify execute. This way can do print+execute if want.

############################################

if (!is_writable("{$CONF['photo_upload_dir']}_old/0/"))
	die("unable to write to {$CONF['photo_upload_dir']}_old/0/\n");

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

############################################
// 1) Repair failed submissions (run revive-temp-uploads.php)

if ($param['step'] == 1) {
	//revive-temp-uploads.php DOES have its own scanner, but we can use the cache table

	//for now just looking at rejects, WITHOUT a row in gridimage_size (which IMPLIES there is no full-size image!)
		//technically should also look for missing larger, that is harder to scan for.
		// but could at least look where largestsize>640 but original_width = 0

	function revive_files($table, $where, $extra = '') {
		global $db,$param;
		$sql = "select gridimage_id,gi.user_id,preview_key,width
		 from gridimage gi inner join submission_method using (gridimage_id) inner join $table using (preview_key) left join gridimage_size s using (gridimage_id)
		 where moderation_status = 'rejected' and $where order by gridimage_id desc limit {$param['limit']}";

		$data = $db->getAll($sql);
		foreach ($data as $row) {
			//todo, check the image doesnt exist!
			$cmd = "php scripts/revive-temp-uploads.php --id={$row['gridimage_id']} ".$extra;
			if ($param['print'])
				print "$cmd\n";
		}
		if (empty($data) && $param['print'])
			print "No Rows for $table\n";
	}

	revive_files("tmp_upload_dir", " s.gridimage_id is null");
	revive_files("tmp_upload_dir_old", " s.gridimage_id is null", ' --old');

############################################
// 2) Remove sucessful submissions (done by remove-temp-uploads.php directly for now)

} elseif ($param['step'] == 2) {

	print "Done by remove-temp-uploads.php for now (which purges tmp_upload_dir_old)\n";

	//step4 will actull purge tmp_upload_dir of successful uploads (ie by finding content match, not preview_key match)
	//select * from submission_method inner join tmp_upload_dir using (preview_key) might also find them!

############################################
// 3) Remove duplicates in temp folder (same image uploaded multiple times!)

} elseif ($param['step'] == 3) {

	function find_duplicates($table, $folder) {
		global $db,$param,$CONF;
		$sql = "SELECT user_id,preview_key,COUNT(*) as cnt,md5sum FROM $table
		WHERE status = 2 AND created < date_sub(now(),interval 1 day)
		GROUP BY user_id,md5sum,filesize HAVING cnt>1 ORDER BY NULL limit {$param['limit']}";
			//the created filter, its help prevent deleting a file the user is in the middle of submitting!
			/// technicall will still have the md5sum in table, so could fetch from the other, but that complicated

		$data = $db->getAll($sql);
		foreach ($data as $row) {
			// we just get ONE of the rows, if Cnt>2 could run a loop?
			$a = $row['user_id']%10;

		        ///var/www/geograph_live/upload_tmp_dir_old/2/newpic_u13502_8e38e115b94a86e275cb0658a97c3503.exif
		        $path = "$folder/$a/newpic_u{$row['user_id']}_{$row['preview_key']}";

			$cmd = "unlink $path*";
			$sql = "UPDATE $table SET status = 0 WHERE user_id = {$row['user_id']} AND preview_key = '{$row['preview_key']}'";

			if ($param['print']) {
				print "$cmd\n";
				print "$sql;\n";
			}
		}
		if (empty($data) && $param['print'])
			print "No Rows for $table\n";
	}

	find_duplicates("tmp_upload_dir", $CONF['photo_upload_dir']);
	find_duplicates("tmp_upload_dir_old", "{$CONF['photo_upload_dir']}_old");

############################################
// 4) Remove temp where duplicates a submission (probably uploaded multiple times, and only one submitted)

} elseif ($param['step'] == 4) {

	// TODO - we should check if original_width=0 before deleting the temp upload. (as it could otherwise be used to revive larger)
		//	... in the unlikly event that the fullsize was writtem, but the orgiinal was not!

	function find_submitted($table, $folder) {
		global $db,$param,$CONF;
		$sql = "SELECT user_id,preview_key,md5sum, basename
		FROM $table
		inner join full_md5 using (md5sum)
		WHERE status = 2
		AND class = 'full.jpg'
		AND s3_size = filesize
		LIMIT {$param['limit']}";

		$data = $db->getAll($sql);
		$done = array();
		foreach ($data as $row) {
			//skip duplicates could add GROUP BY to above query, but suspect this way is more effient
			if (!empty($done[$row['preview_key']]))
				continue;
			$done[$row['preview_key']]=1;
			if (preg_match('/^(\d+)_\w{8}\.jpg$/',$row['basename'],$m)) {
				$id = intval($m[1]);
				$check = $db->getRow("select user_id,m.*,s.*,gridimage_id from gridimage left join submission_method m using (gridimage_id)
					 left join gridimage_size s using (gridimage_id) where gridimage_id = $id");
				if ($check['user_id'] != $row['user_id']) {
					print_r($row);
					print_r($check);
					die("User Mismatch\n");
				} else {
					if (empty($check['original_width']) && $check['largestsize'] > 640) {
						//todo check file_exists('.original.jpeg')
						print_r($row);
						print_r($check);
						die("Appears the larger image is missing\n");
						//TODO call revive-temp-uploads.php!
						// but we have to be careful, $id is final submitted id, but where we might of found a image with DIFFERENT preview_key
						//   ... which revive-temp-uploads.php wouldnt cope with!
					}
				}
				print "deleting {$row['user_id']}_{$row['preview_key']} as it already submitted as {$row['basename']} (was using {$check['preview_key']})\n"; //preview_key may or may not be the same. Doesnt matter!
			} else {
				print_r($row);
				die("Not a image?\n");
			}

			$a = $row['user_id']%10;
		        $path = "$folder/$a/newpic_u{$row['user_id']}_{$row['preview_key']}";

			$cmd = "unlink $path*";
			$sql = "UPDATE $table SET status = 0 WHERE user_id = {$row['user_id']} AND preview_key = '{$row['preview_key']}'";

			if ($param['print']) {
				print "$cmd\n";
				print "$sql;\n\n";
			}
		}
		if (empty($data) && $param['print'])
			print "No Rows for $table\n";
	}

	find_submitted("tmp_upload_dir", $CONF['photo_upload_dir']);
	find_submitted("tmp_upload_dir_old", "{$CONF['photo_upload_dir']}_old");

############################################
}
