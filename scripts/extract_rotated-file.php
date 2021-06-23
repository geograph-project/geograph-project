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

######################################################

        declare(ticks = 1);
        $killed = false;

        pcntl_signal(SIGINT, "signal_handler");

        function signal_handler($signal) {
                global $killed;
                if ($signal == SIGINT) {
                        print "Caught SIGINT\n";
                        $GLOBALS['killed']=1; // we dont exit here, rather let the script kill the script at the right moment, using $killed
                }
        }

######################################################

$param = array('execute'=>false, 'limit' => 10);

chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$filesystem = GeographFileSystem();

$destination = "/mnt/s3/fake/"; //this path is never written to, ony used in mapping filesnames! (the filesystem class works with temp files)
$filesystem->buckets[$destination] = "data.geograph.org.uk/exif/";

######################################################

	$table = "gridimage_search";


        $row = $db->getRow("SELECT COUNT(*) as count,MIN(gridimage_id) as min,MAX(gridimage_id) AS max FROM $table WHERE gridimage_id > 0");
        print_r($row);

        if ($row['count'] < 10000)
                die("only {$row['count']} rows exist\n");

        $from = intval($row['min']/1000)*1000;
        $stop = intval(($row['max'] - 10000)/1000)*1000; //stops a bit early, and rounds DOWN to nearest 1000, in general avoid writing partial files!!

	$i = 0;
        for($start = $from; $start < $stop; $start+=1000) {
                $end = $start+999;
                print "#($start,$end,$table)\n";
                process_file($start,$end,$table);

	        if (!empty($killed))
        	        die("killed\n");
		$i++;
		if ($i > $param['limit'])
        	        die("done\n");
        }


######################################################

function process_file($start,$end,$table) {
	global $param,$db,$filesystem,$destination;

	if ($db->getOne("SELECT gridimage_id FROM exif_rotated WHERE gridimage_id = $start") == $start) {
		print "Already done file starting with $start\n";
		return;
	}

        $dir1 = sprintf("%02d", floor($start/1000000)%100);
        $dir2 = sprintf("%02d", floor($start/10000)%100);
        $filename = "$destination$dir1/$dir2/$start.exif.gz";

print "<!-- $filename -->\n";

        list($sbucket, $sfilename) = $filesystem->getBucketPath($filename);
        if ($sbucket) {
                //download!
                $filename = $filesystem->_get_remote_as_tempfile($sbucket, $sfilename);
	}

//print "<!-- $filename ( $sbucket, $sfilename) -->\n";

        if (file_exists("$filename"))
                $h = gzopen($opened = $filename,'rb'); //still use gzopen as it reads uncompressed anyway, and needed for gzgets/gzclose
        else {
		print "$filename not found\n";
                return false;
	}

	$c = $d = 0;
        //$prefix = "$id\t";
        //$prefixlen = strlen($prefix);
        while ($h && !feof($h)) {
                $string = gzgets($h);

		/*
                //use prefix compare, rather than split then compare, to avoid splitting all strings.
                if (strncmp($string, $prefix, $prefixlen) === 0) {
                        list($id2,$encoded) = explode("\t",$string,2);
                        return unserialize(base64_decode($encoded));
                }*/

		list($id,$encoded) = explode("\t",$string,2);
		$exif = unserialize(base64_decode($encoded));

if (empty($exif)) {
	continue;
	print "enocded = $encoded\n\n";
	print "decoded = ".base64_decode($encoded)."\n\n";
	die();
}

//print "$id,len:".strlen($encoded).",count:".count($exif)."\n";

		if (!empty($exif['IFD0']['Orientation']) && $exif['IFD0']['Orientation']!==1) {
			$sql = "INSERT IGNORE INTO exif_rotated SET gridimage_id = {$id}, extracted = ".$db->Quote($exif['IFD0']['Orientation']);
			if ($param['execute']) {
				$db->Execute($sql);
				$d++;
				if (!$c && ($start != $id)) { //always insert the 'start' image too
					$sql = "INSERT IGNORE INTO exif_rotated SET gridimage_id = $start";
					$db->Execute($sql);
				}
			} else
				print_r("$sql;\n");

		} elseif (!$c && $param['execute']) { //always insert the 'start' image too
			$sql = "INSERT IGNORE INTO exif_rotated SET gridimage_id = $start";
			$db->Execute($sql);
		}

		$c++;
        }
	print "Scanned $c images, found $d images\n";
}

print ".\n";

exit;


