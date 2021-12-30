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

$param = array('execute'=>false, 'limit' => 10, 'auto' => false, 'cache'=>false,'rerun'=>false);

chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$filesystem = GeographFileSystem();

$destination = "/mnt/s3/fake/"; //this path is never written to, ony used in mapping filesnames! (the filesystem class works with temp files)
$filesystem->buckets[$destination] = "data.geograph.org.uk/exif/";

######################################################

	if ($param['auto']) {
		$row = array();
		$row['min'] = $db->getOne("SELECT max(gridimage_id) FROM exif_rotated WHERE extracted = ''");
	} else {
	        $row = $db->getRow("SELECT MIN(gridimage_id) as min,MAX(gridimage_id) AS max FROM gridimage_search WHERE gridimage_id > 0");
	}

	$row['max'] = $db->getOne("SELECT min(gridimage_id) FROM gridimage_exif") + 10000;

$row['min'] = 2703000;

        print_r($row);

        $from = intval($row['min']/1000)*1000;
        $stop = intval(($row['max'] - 10000)/1000)*1000; //stops a bit early, and rounds DOWN to nearest 1000, in general avoid writing partial files!!

	$i = 0;
        for($start = $from; $start < $stop; $start+=1000) {
                $end = $start+999;
                //print "#($start,$end)\n";
                process_file($start,$end);

	        if (!empty($killed))
        	        die("killed\n");
		$i++;
		if ($i == $param['limit'])
        	        die("done\n");

                if (!($i%10)) {

	                $filesystem->shutdown_function(); //filesystem class only deletes the temp files on shutdown. We to clear them out as go along
        	        $filesystem->filecache = array(); //the class doesnt bother clearing the array (as it normally on shutdown anyway)
                	print "$i. ";

	                if (!($i%100)) {
        	                //the S3 token doesnt last forever, so recreate the object periodically to get a new secruity token!
                	        //S3::putObject(): [ExpiredToken] The provided token has expired.
                        	$filesystem = new FileSystem(); // dont use GeographFileSystem as it return the same object!
				$filesystem->buckets[$destination] = "data.geograph.org.uk/exif/";
	                }
		}

        }


######################################################

function process_file($start,$end) {
	global $param,$db,$filesystem,$destination;

	if (empty($param['rerun']) && $db->getOne("SELECT gridimage_id FROM exif_rotated WHERE gridimage_id = $start") == $start) {
		print "Already done file starting with $start\n";
		return;
	}

	if (file_exists("/tmp/exif.$start.gz")) {
		$filename = "/tmp/exif.$start.gz";

		print "<!-- $filename --> :: ";

	} else {

	        $dir1 = sprintf("%02d", floor($start/1000000)%100);
        	$dir2 = sprintf("%02d", floor($start/10000)%100);
	        $filename = "$destination$dir1/$dir2/$start.exif.gz";

		print "<!-- $filename --> :: ";


	        list($sbucket, $sfilename) = $filesystem->getBucketPath($filename);
        	if ($sbucket) {
                	//download!
	                $filename = $filesystem->_get_remote_as_tempfile($sbucket, $sfilename);
		}

		//print "<!-- $filename ( $sbucket, $sfilename) -->\n";

		if ($param['cache'])
			copy($filename,"/tmp/exif.$start.gz");
	}


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
if (empty($string))
	continue;

		/*
                //use prefix compare, rather than split then compare, to avoid splitting all strings.
                if (strncmp($string, $prefix, $prefixlen) === 0) {
                        list($id2,$encoded) = explode("\t",$string,2);
                        return unserialize(base64_decode($encoded));
                }*/

		list($id,$encoded) = explode("\t",$string,2);
		$exif = @unserialize(base64_decode($encoded));

		if (empty($exif)) {
			$decoded = base64_decode($encoded);
		//	print "decoded = ".$decoded."\n\n";

			$exif = array();
			$exif['IFD0'] = array();
			//we can't decode the the whole string, but might still be there
				//:"FinePix S3Pro  ";s:11:"Orientation";i:8;s:11:"XResolution";s:4
			if (preg_match('/"Orientation";i:(\d+);/',$decoded,$m)) {
				$exif['IFD0']['Orientation'] = $m[1];
			} else {
				$exif['IFD0']['Orientation'] = '?'; //we just dont know, so will have to check!
			}
			print "$id : Orientation = {$exif['IFD0']['Orientation']}\n";
		} elseif ($param['rerun']) {
			$exif['IFD0']['Orientation'] = null; //we doing a 'rerun' so dont need to save it again!
		}

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


