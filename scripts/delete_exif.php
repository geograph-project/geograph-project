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

$param=array(
        'mode'=>'unknown',
);


chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	$final_folder = "/mnt/combined/geograph_live/exif/";
	$temp_folder = "/tmp/exif/";

	$logfile = $temp_folder."export.log";

	if (file_exists($logfile)) {
		$h = fopen($logfile,'r');
		while($h && !feof($h)) {
			$line = fgetcsv($h);
			//	fputcsv($h,array(date('r'),$filename,$start,$end,$table,$c));
			//                          0         1        2     3     4     5

			$filename = $line[1];
			$start = $line[2];
			$end = $line[3];
			$table = $line[4];
			$c = $line[5];

			if (!file_exists($filename))
				continue;

			$sql = "DELETE FROM $table WHERE gridimage_id BETWEEN $start AND $end";

			$line = `wc -l $filename`;
			list($lines,$filename) = preg_split('/\s+/',trim(`wc -l $filename`));
			if ($lines == $c && filesize($filename) > 100000) {
				print "$sql;\n";

				if ($param['mode'] == 'run') {
					$db->Execute($sql);
					$done = $db->Affected_Rows();
					if ($done != $c)
						print "WARNING, DELETED $done ROWS, BUT EXEPECTED $c\n";
				}
			} else {
				print "file=$filename $c!=$lines  (via $table)\n";
			}
		}
		fclose($h);
	}
