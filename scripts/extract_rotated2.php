<?php
/**
 * $Project: GeoGraph $
 * $Id: recreate_maps.php 2996 2007-01-20 21:39:07Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2011 Barry Hunter (geo@barryhunter.co.uk)
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

$param = array('limit' => 10, 'sleep'=>1);

chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

######################################################################################################################################################
# alter table exif_rotated add orient_full varchar(128) default null, add orient_mid varchar(128) default null, add orient_original varchar(128) default null;

$filesystem = GeographFileSystem();
##$filesystem->log = true;

$data= $db->getAll("SELECT * FROM exif_rotated INNER JOIN gridimage_size USING (gridimage_id)
			LEFT JOIN gridimage_pending USING (gridimage_id)
			 WHERE orient_full IS NULL AND orient_mid IS NULL AND orient_original IS NULL AND extracted != '1' and extracted != '' LIMIT {$param['limit']}");

$c=0;
foreach ($data as $row) {
	if (!($c%10)) {
		if (disk_free_space('/tmp') < 20000000)
			die("Not enough freespace on /tmp\n");
	}

	//print_r($row);
		/*
			  $orient = `exiftool -Orientation -n $to`;
                                if (strpos($orient,'Orientation') !== FALSE && strpos($orient,'1') === FALSE)
                                        `exiftool -Orientation=1 -n -overwrite_original $to`;
		*/

	$image = new GridImage($row['gridimage_id']);

	$updates = array();

	if (!$row['original_width'] || $row['upload_id']) {
	        $path = $image->_getFullpath(FALSE, false);
        	$updates['orient_full'] = process($path,'unknown');
	}


	if ($row['original_width']) {

		if (max($row['original_width'],$row['original_height']) > 800) {
			$path = $image->_getOriginalpath(FALSE, false, '_800x800');

			$updates['orient_mid'] = process($path,'');
		}

		if (empty($updates['orient_mid']) && max($row['original_width'],$row['original_height']) > 1024) {
			$path = $image->_getOriginalpath(FALSE, false, '_1024x1024');

			$updates['orient_mid'] = process($path,'');
		}

		if (empty($updates['orient_mid'])) {
			$path = $image->_getOriginalpath(FALSE, false);
			$updates['orient_original'] = process($path,'unknown');
		}

	}

	print "{$row['gridimage_id']} ({$row['original_width']}) ";
	print_r($updates);
	if (!empty($updates)) {
		$db->Execute('UPDATE exif_rotated SET orient_date = NOW(), `'.implode('` = ?,`',array_keys($updates)).
		'` = ? WHERE gridimage_id = '.$db->Quote($row['gridimage_id']),array_values($updates));
	}

	if (!empty($param['sleep']))
		sleep($param['sleep']);

	$c++;
	if (!($c%10)) {
		$filesystem->shutdown_function(); //filesystem class only deletes the temp files on shutdown. We to clear them out as go along
		$filesystem->filecache = array(); //the class doesnt bother clearing the array (as it normally on shutdown anyway)

                if (!($c%100)) {
       	                //the S3 token doesnt last forever, so recreate the object periodically to get a new secruity token!
               	        //S3::putObject(): [ExpiredToken] The provided token has expired.
                       	$filesystem = new FileSystem(); // dont use GeographFileSystem as it return the same object!
                }
	}
}


function process($path,$unknown_return) {
	global $filesystem;

	print "$path\n";

	if (!empty($path) && basename($path) != 'error.jpg') {

		$local = $_SERVER['DOCUMENT_ROOT'].$path;
		list($sbucket, $sfilename) = $filesystem->getBucketPath($local);
       	        if ($sbucket) {
              	        //download!
                        $tmp_src = $filesystem->_get_remote_as_tempfile($sbucket, $sfilename);

if (empty($tmp_src) || !file_exists($tmp_src)) {
	return $unknown_return;
	die("unable to read $tmp_src from $sbucket, $sfilename\n");
}
			$cmd = "exiftool -Orientation -n $tmp_src";

			//print "$cmd\n";

			$result = `$cmd`;



if (false && empty($result)) {
	print "------- $tmp_src -> $result\n";
	print `exiftool $tmp_src`;
	exit;
}

			return trim($result);
		}
	}
	return '';
}
