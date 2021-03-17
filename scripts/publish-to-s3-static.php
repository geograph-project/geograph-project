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

$param = array('verbose'=>0, 'log'=>0, 'headers'=>0, 'execute'=>0);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$CONF['s3_cachecontrol'] = 'max-age='.(3600*24*180);

if (empty($filesystem)) //eventually gloabl will do this!
	$filesystem = new FileSystem(); //sets up configuation automagically

//ultimately should be dynamic, but the main class doesnt do this yet
$filesystem->buckets["/var/www/geograph_svn/public_html/"] = 'uk-org-geograph-staging-photos/';
$filesystem->buckets["/var/www/geograph_live/public_html/"] = 'photos.geograph.org.uk/';

if (!empty($param['log'])) $filesystem->log = true;

//if (!empty($param['verbose'])) {
//	$GLOBALS['curl_verbose'] = $param['verbose'];
//	print_r($filesystem);
//}

############################################

chdir($_SERVER['DOCUMENT_ROOT']);



$h = popen('find -xdev -type f -name "*.png" -or -name "*.gif" -or -name "*.jpg" -or -name "blank.html" -or -name "*.bmp" -or -name "*.ico"','r');

$last = '';
while ($h && !feof($h)) {
	$filename = preg_replace('/^\.\//','',trim(fgets($h))); //want filename without the initial slash.
	if (empty($filename))
		break; //get a newline at the end!

	$dir = dirname($filename);
	$destination = "/".$filename; //but want one with slash sometimes

	if ($dir != $last) {
		if ($dir == '.') {
		        list($bucket,$prefix) = $filesystem->getBucketPath($_SERVER['DOCUMENT_ROOT']."/");
		} else {
		        list($bucket,$prefix) = $filesystem->getBucketPath($_SERVER['DOCUMENT_ROOT']."/".$dir."/");
		}
		if (!empty($bucket)) {
		        // public static function getBucket($bucket, $prefix = null, $marker = null, $maxKeys = null, $delimiter = null, $returnCommonPrefixes = fa$

	                //we set unlimited, but make sure to only specify a deep folder.
        	        $list = $filesystem->getBucket($bucket, $prefix, null, null, '/', true, true);
		}
		$last = $dir;
	}

	############################################

	$upload = false;

	if (empty($param['execute']))
		print "$destination";

	if (empty($list[$filename])) { //lets not bother with memcache here!
		$upload = 'missing from listing';

	############################################

	} else {
		
                if ($param['headers'] && !$memcache->name_get('fs_full',$destination)) {

                        if ($param['headers']==='http') {
                                //use a normal HTTP request, so checks cloudfront
                                $content = file_get_contents($CONF['STATIC_HOST'].$destination);
                                if (empty($content)) {
                                        $upload="not found via cloudfront";
                                } elseif ($http_response_header) {
                                        $max = false;
                                        foreach ($http_response_header as $c => $header) {
                                                if (preg_match('/^HTTP\/\d+.\d+ +(\d+)/i',$header,$m)) {
                                                        if ($m[1] != 200)
                                                                $upload = "non200";
                                                } elseif(preg_match('/^X-Amz-Storage-Class:(.*)/i',$header,$m)) {
                                                        if (trim($m[1]) != 'STORAGE' && $_SERVER['HTTP_HOST'] != 'staging.geograph.org.uk')
                                                                $upload = $m[1];
                                                } elseif(preg_match('/^Cache-Control: max-age=(\d+)/',$header,$m)) {
                                                        $max = $m[1];
                                                }
                                        }
                                        if (!$max && !$upload)
                                                $upload = "no cachecontrol";
                                }
                        } else {
                                list($bucket, $uri) = $filesystem->getBucketPath($_SERVER['DOCUMENT_ROOT'].$destination);
                                $rr = $filesystem->getObjectInfo($bucket, $uri);
                                if (empty($rr))
                                        $upload="not found via API";
                                else {
                                        if (empty($rr['cache']))
                                                $upload = "no cachecontrol";
                                        if (!empty($rr['class']) && $rr['class'] != 'STORAGE' && $_SERVER['HTTP_HOST'] != 'staging.geograph.org.uk') //the header typically isnt provided if STANDARD
                                                $upload = $rr['class'];
                                        if (empty($rr['size']) || $rr['size'] != filesize(".".$destination))
                                                $upload = "empty";
                                }
                        }
		}

		//todo, check if changed? (compare md5 ?)
		if (empty($param['execute']))
			print " already exists ";
	}

	############################################

	if ($upload) {

		if (!empty($param['execute'])) {
			print "$destination";
			print " ... uploading due to $upload";

			//src us using a relative path deliberately (so doesnt use 'remote')
			//dst will is a full absolute path, so works on the bucket!
			$r = $filesystem->copy(".".$destination, $_SERVER['DOCUMENT_ROOT'].$destination, null, 'STANDARD'); //use standard, as might be small, and going to be used by cloudfront, so probably not IA anyway!
			if ($r)
                       	        $memcache->name_set('fs_full',$destination,$upload,false,$memcache->period_long);
		} else {
			print " ... uploading due to $upload";
		}
		print "\n";

	} elseif (empty($param['execute'])) {
		print "\n";
	}
}
