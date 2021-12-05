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
	'limit'=>50,
        'start'=>6618237,
	'big'=>true,
	'print'=>false,
	'execute'=>false,
	'progress'=>false,
);

chdir(__DIR__);
require "./_scripts.inc.php";

if (empty($param['execute']))
	$param['print'] = true; //so prints if dont specify execute. This way can do print+execute if want.

############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

############################################

$where = 1; //rely on the inner join gridimage_size

if (!empty($param['start']))
	$where = "gridimage_id > {$param['start']} and gridimage_size.width > 120";

if (!empty($param['big']))
	$where .= " and gridimage_size.original_width > 640";

$where .= " and purged IS NULL";

if ($param['print'])
        print "# $where\n";

$rows = $db->getAll($sql = "
select gridimage_id,preview_key,user_id from submission_method
 inner join gridimage gi using (gridimage_id)
 inner join gridimage_size using (gridimage_id)
 inner join gridimage_exif using (gridimage_id)
 where {$where} limit {$param['limit']}");

if ($param['progress'])
	print "got ".count($rows)." rows. ";

$exts = array('.jpeg','.original.jpeg','.exif');

$c =0;
$d = intval(sqrt($param['limit']));
foreach ($rows as $row) {
        $a = $row['user_id']%10;

	///var/www/geograph_live/upload_tmp_dir_old/2/newpic_u13502_8e38e115b94a86e275cb0658a97c3503.exif
	$path = "{$CONF['photo_upload_dir']}_old/$a/newpic_u{$row['user_id']}_{$row['preview_key']}";

	if (!file_exists("$path.exif")) {
		//just set to something, to show processed, even if WHEN purged is now unknown
		$db->Execute("UPDATE submission_method SET purged = '0000-00-00' WHERE gridimage_id = {$row['gridimage_id']}");
		if ($param['print'])
			print ".";
		continue;
	}

	foreach ($exts as $ext) {
		if (file_exists("$path$ext")) {
			if ($param['print'])
				print "unlink $path$ext\n";
			if ($param['execute'])
				unlink("$path$ext");
		}
	}
	if ($param['execute'])
		$db->Execute("UPDATE submission_method SET purged = NOW() WHERE gridimage_id = {$row['gridimage_id']}");
	if ($param['progress'] && !($c%$d))
		print " $c.";

	/* ... this works, but 'find' can be slow if lots of files in folder (ultimately it scans each filename in folder!)
	$cmd = "find {$CONF['photo_upload_dir']}_old/$a/ -name 'newpic_u{$row['user_id']}_{$row['preview_key']}*'";

	if ($param['print'])
		print "$prefix$cmd -delete\n";
	if ($param['execute'])
		passthru("$prefix$cmd -delete");
	*/
	$c++;
}


print "\n\n";

