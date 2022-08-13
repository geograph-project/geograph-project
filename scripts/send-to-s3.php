<?php
/**
 * $Project: GeoGraph $
 *
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2021 Barry Hunter (geo@barryhunter.co.uk)
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

$param = array('src'=>false, 'dst'=>false, 'move'=>false,
	'include'=>'*.gz', 'acl'=>'private', 'storage'=>'STANDARD_IA',
	'dry'=>true, 'log'=>false);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

if (empty($filesystem)) //eventually gloabl will do this!
	$filesystem = new FileSystem(); //sets up configuation automagically

if (!empty($param['log'])) $filesystem->log = true;

############################################

if (empty($param['src']) || !is_dir($param['src'])) {
	die("please specify folder (use --help)\n");
}

//todo, validate dest!

############################################
//we actully need to pass a path to the filesystem class, which behaves like LIKE its mounted into S3. SO by copying files, we actully triggering an upload!

list($bucket,$prefix) = $r = $filesystem->getBucketPath($param['dst']);
        print_r($r);


//getBucket is the S3 function, it doesnt have our own wrapper, so need to supply the full bucket&prefix
// public static function getBucket($bucket, $prefix = null, $marker = null, $maxKeys = null, $delimiter = null, $returnCommonPrefixes = false, $v2 = true)

//we set unlimited, but make sure to only specify a deep folder.
$list = $filesystem->getBucket($bucket, $prefix, null, null, '/', true, true);

############################################

foreach(glob($param['src'].$param['include']) as $filename) {
	if (is_file($filename)) {
		$base = basename($filename);

		if (isset($list[$prefix.$base])) {

			//so can run move, to just delete files, even if uploaded the files already
			if ($param['move']) {
				if ($list[$prefix.$base]['hash'] = md5_file($filename)) {
					print "unlink($filename); #local file, matches hash!\n";
					if (empty($param['dry'])) {
						unlink($filename);
					}
				} else {
					print "HASH OF $filename does NOT appear to match!!\n";
					print_r($list[$prefix.$base]);
					exit;
				}
			} else {
				print "#$filename already done!\n";
			}

		} else {
			print "copy($filename,{$param['dst']}$base)\n";

			if (empty($param['dry'])) {
				//function copy($local, $destination, $acl = null, $storage = null) {
				$r = $filesystem->copy($filename, $param['dst'].$base, $param['acl'], $param['storage']);
				if ($r) {
					if ($param['move']) {
						if (filesize($filename) == $filesystem->filesize($param['dst'].$base)
						&& md5_file($filename) == $filesystem->md5_file($param['dst'].$base)) { //(note ->md5_file uses the 'hash' from S3, rather than downloading it fresh!!
							unlink($filename);
						} else {
							print "HASH OF $filename does NOT appear to match!!\n";
							print_r($filesystem->stat($param['dst'].$base));
							exit;
						}
					}
				} else {
					die("failed??\n");
				}
			}
		}
	}
}


