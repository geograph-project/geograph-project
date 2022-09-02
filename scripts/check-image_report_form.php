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
	'execute'=>false,
	'single'=>true,
        'start'=>6618237,
	'limit'=>50,
	'id'=>false,
);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

############################################
$s3fs = false;

if (file_exists("/mnt/s3-photos-production-testing/robots.txt")) {
	print "Using /mnt/s3-photos-production-testing/\n";
	$s3fs = true;
} elseif (!is_dir("{$_SERVER['DOCUMENT_ROOT']}/photos/01/"))
	die("photos folder not mounted?\n");

if (trim(`whoami`) != 'www-data')
	die("must be run as www-data\n");

############################################

if (!empty($param['id']))
	$where = "gridimage_id = ".$param['id'];
else
	$where = "`status` = 'new'";


$where .= " and image_report_form.created > '2020-09-26'"; //cloud migration!


//$rows = $db->getAll("select * from submission_method where gridimage_id > 6618237 limit 50");
$rows = $db->getAll("
select gridimage_id,preview_key,largestsize,gi.user_id, moderation_status,
	 original_width, width, affected, page_url, image_url, status
 from image_report_form
 left join submission_method using (gridimage_id)
 inner join gridimage gi using (gridimage_id)
  left join gridimage_size using (gridimage_id)
 where {$where} order by report_id desc limit {$param['limit']}");

foreach ($rows as $row) {
	if ($row['gridimage_id'] >= $param['start']) {
		if (empty($row['user_id'])) {
        	        $a = '?';
	        } else {
        	        $a = $row['user_id']%10;
	        }
		$cmd = "find {$CONF['photo_upload_dir']}*/$a/ -name '*{$row['preview_key']}*'";
		print "$cmd  ##for {$row['gridimage_id']}\n";
	}

	print_r($row);
	print "https://www.geograph.org.uk/stuff/image-file-viewer.php?id={$row['gridimage_id']}\n";

	$image = new Gridimage($row['gridimage_id']);
		        if (!empty($CONF['enable_cluster'])) {
                                $server= str_replace('1',($image->gridimage_id%$CONF['enable_cluster']),$CONF['STATIC_HOST']);
                        } else {
                                $server= $CONF['STATIC_HOST'];
                        }
	$path = $image->_getFullpath(true);
	print "$path\n";
		if (basename($path) == 'error.jpg') {
			print "Image not found? expected path=".$image->_getFullpath(false)."\n";
		}

	if (strpos($row['affected'],'Image Shown on Photo Page') !== FALSE) {
		check_path($CONF['STATIC_HOST'],$path, $row);

		if ($row['largestsize'] > 640 || $row['original_width'] > 20) {

			if (empty($image->original_width))
				$image->_getFullSize(); //just sets orginal_width

			$path = $image->getLargestPhotoPath(false); //true, gets the URL

			check_path($server,$path, $row);
		}
	}
	$thumbw = null;
	if (strpos($row['affected'],'120px')!== FALSE) {
		$thumbw=120; $thumbh=120;

		$resized = $image->getThumbnail($thumbw,$thumbh, 2);
		print_r($resized);
		$path = $resized['url']; //its actully only the path compoent of the URL.
		check_path($server,$path, $row);

		print "php scripts/test-s3-invalidation.php --path=$path --dir={$param['dir']}\n";

	}
	$thumbw = null;
	if (strpos($row['affected'],'213px')!== FALSE) {
		$thumbw=213; $thumbh=160;

		$resized = $image->getThumbnail($thumbw,$thumbh, 2);
		print_r($resized);
		$path = $resized['url']; //its actully only the path compoent of the URL.
		check_path($server,$path, $row);
	}

	$r = readline("Reply? (enter text to add to log table)");
	if (strlen(trim($r)))
		 update_status($row['gridimage_id'], $r);

	print "\n";
	if ($param['single'])
		die();
}

########################################

function check_path($server,$path, $row) {
	global $param,$s3fs;
	if (basename($path) == 'error.jpg')
		die("unable to process error.jpg\n");

	if ($s3fs) {
		$fullpath = "/mnt/s3-photos-production-testing$path";
	} else {
		$fullpath = "{$_SERVER['DOCUMENT_ROOT']}$path";
	}
	$id = intval(basename($path));
	$size = "F";
	if (preg_match('/(\d+x\d+)/',$path,$m))
		$size = $m[1];
	else
		print "WARNING does NOT appear to be a thumbnail!\n";

	print "\n";
	$cmd = "ls -l $fullpath";
        print "$cmd\n";
	passthru($cmd);

	$size = filesize($fullpath);
	if ($size) {
		$url = $server.$path;
		print "$url\n";

		$str= file_get_contents($url);
		if (strlen($str) == $size) {

			print `identify $fullpath`;
			$r = readline("Does that look valid? (if not 'y' will delete the file)");
			if ($r != 'y') {
				print "\n";
				print "sudo -u www-data rm $fullpath\n";
				print "https://{$_SERVER['HTTP_HOST']}/admin/memcache.php?image_id=$id&size=$size&action=delete&cleardb=on\n";
				print "php scripts/test-s3-invalidation.php --path=$path --dir={$param['dir']}\n";
				print "https://api.geograph.org.uk/api/oembed?url=$url\n";
				exit;
			}

			update_status($row['gridimage_id'], basename($path).":okurl");
		} else {
			update_status($row['gridimage_id'], "failed", false);
		}
	} else {
		update_status($row['gridimage_id'], "failed", false);
		print "https://{$_SERVER['HTTP_HOST']}/admin/memcache.php?image_id=$id&size=$size&action=delete&cleardb=on\n";
		print "https://api.geograph.org.uk/api/oembed?url=$url\n";
	}
}


function update_status($gridimage,$str, $execute=true) {
	global $db;
	$str .= ", ".date('Y-m-d H:i:s')."\n";
	$str = $db->Quote($str);
	$sql = "UPDATE image_report_form SET notes = CONCAT(notes,$str) WHERE gridimage_id = $gridimage";
	print "$sql;\n";
	if ($execute)
		$db->Execute($sql);
	else
		print "#Note SQL is not run!\n";
}
