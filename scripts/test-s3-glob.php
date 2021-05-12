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

$param = array('path'=>'','d'=>'/','c'=>true,'max'=>10, 'verbose'=>0, 'log'=>0, 'command'=>false);

if (!empty($argv[1]) && strpos($argv[1],'/') === 0)
	$_SERVER['argv'][1] = "--path=".$argv[1]; //our parser doesnt understand unprefixed options, so convert to a proper option

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

if (empty($filesystem)) //eventually gloabl will do this!
	$filesystem = new FileSystem(); //sets up configuation automagically

if (!empty($param['log'])) $filesystem->log = true;

if (!empty($param['verbose'])) {
	$GLOBALS['curl_verbose'] = $param['verbose'];
	print_r($filesystem);
}

//$filesystem->setSignatureVersion('v2');

############################################

list($bucket,$prefix) = $r = $filesystem->getBucketPath($param['path']);
//print_r($r);
print "# Bucket = $bucket\n";

############################################

// public static function getBucket($bucket, $prefix = null, $marker = null, $maxKeys = null, $delimiter = null, $returnCommonPrefixes = false)

if (!empty($bucket)) {
	$marker = null;

//$i = $filesystem->getBucket($bucket);


//$i = $filesystem->getBucket($bucket, $prefix, $marker, null, '/');

	$list = $filesystem->getBucket($bucket, $prefix, $marker, $param['max'], $param['d'], $param['c']);
	foreach ($list as $filename => $row) {
		if (!empty($row['prefix'])) { //a virtual directory!
			printf("%s %10s %16s %16s %s\n",
				 'd', '', '', '', $filename);
		} else { // else a file
			printf("%s %10d %16s %16s %s   %s\n",
				 '-', $row['size'], date('Y-m-d H:i:s',$row['time']), $row['hash'], $filename, $row['class']);

			if ($param['command']) {
				//we dont use $filesystem->command(..) becuase we dont have a local path, we only have the internal bucket key
				$cmd = $param['command'];
				if (strpos($cmd,'%d')===FALSE)
	                                $cmd.=" %d"; //add to end!

				$tmp_dst = $filesystem->_get_remote_as_tempfile($bucket, $filename);
				$cmd = str_replace('%d',$tmp_dst, $cmd);
				print "$cmd\n";
				passthru($cmd); //todo, maybe passthur not right version
				print "\n";
			}
		}
	}
}

