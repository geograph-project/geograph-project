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

$param = array('path'=>'','dst'=>'', 'verbose'=>0, 'log'=>0, 'execute'=>false);

//has to tbe FIRST!
if (!empty($argv[1]) && strpos($argv[1],'/') === 0)
	$_SERVER['argv'][1] = "--path=".$argv[1]; //our parser doesnt understand unprefixed options, so convert to a proper option

//hast to be LAST!
$last = count($_SERVER['argv']);
if (strpos($argv[$last-1],'/') === 0) {
	$_SERVER['argv'][$last-1] = "--dst=".$argv[$last-1];
}

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

list($bucket,$filename) = $r = $filesystem->getBucketPath($param['path']);
//print_r($r);
print "# Bucket = $bucket\n";
print "# filename = $filename\n";

if (empty($bucket))
	die("unknown bucket\n");

############################################

$dir = dirname($param['dst']);

if (is_dir($param['dst']))
	$dst = rtrim($param['dst'],'/').'/'.basename($filename);
elseif(is_dir($dir) && is_writable($dir))
	$dst = rtrim($dir,'/').'/'.basename($filename);

print "# dest = $dst\n";

if (!empty($dst) && !empty($param['execute'])) {
	$tmp_dst = $filesystem->_get_remote_as_tempfile($bucket, $filename);
	if ($tmp_dst && file_exists($tmp_dst)) {
		rename($tmp_dst, $dst);
	} else {
		die("unable to fetch file?\n");
	}
}

