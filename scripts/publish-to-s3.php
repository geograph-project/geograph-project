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

$param = array('verbose'=>0, 'log'=>0, 'headers'=>0, 'execute'=>1);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

if (empty($filesystem)) //eventually gloabl will do this!
	$filesystem = new FileSystem(); //sets up configuation automagically

if (!empty($param['log'])) $filesystem->log = true;

############################################

chdir($_SERVER['DOCUMENT_ROOT']);

foreach ($REVISIONS as $filename => $vid) {
	if (file_exists(".".$filename)) { //specifically checking there is a local file!

		$destination = preg_replace('/\.(js|css)$/',".v{$REVISIONS[$filename]}.$1",$filename);

		######################
		//special indepth test, checking all the headers are correct (not just that exists!)
		// not for regular use (eg on every container startup) but can be used on setup or after a major change
		if ($param['headers'] && !$memcache->name_get('fs_full',$destination)) {
			print "$destination";
			$upload = false;

			if ($param['headers']==='http') {
				//use a normal HTTP request, so checks cloudfront
				$content = file_get_contents($CONF['STATIC_HOST'].$destination);
				if (empty($content)) {
					$upload="missing";
			        } elseif ($http_response_header) {
					$max = false;
		        	        foreach ($http_response_header as $c => $header) {
        		        	        if (preg_match('/^HTTP\/\d+.\d+ +(\d+)/i',$header,$m)) {
							if ($m[1] != 200)
								$upload = "non200";
	                        		} elseif(preg_match('/^X-Amz-Storage-Class:(.*)/i',$header,$m)) {
							if (trim($m[1]) != 'STORAGE')
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
					$upload="missing";
				else {
					if (empty($rr['cache']))
						$upload = "no cachecontrol";
					if (!empty($rr['class']) && $rr['class'] != 'STORAGE') //the header typically isnt provided if STANDARD
						$upload = $rr['class'];
					if (empty($rr['size']) || $rr['size'] != filesize(".".$filename))
						$upload = "empty";
				}
			}
			if ($upload) {
				print " ... uploading due to $upload\n";
				//upload it now, as hard to force teh code below to upload it
				$r = $filesystem->copy(".".$filename, $_SERVER['DOCUMENT_ROOT'].$destination, null, 'STANDARD');
				if ($r)
					$memcache->name_set('fs_full',$destination,$upload,false,$memcache->period_long);
				print "\n$r\n";
				continue;
			}

			//if got this far, then it was ok!
			$ok = 1; //need to pass variable, as by reference
			$memcache->name_set('fs_full',$destination,$ok,false,$memcache->period_long);
			print " ..ok\n";
			//... now goes on to be checked like normal
                }
		######################

		$version = $memcache->name_get('fs',$filename);
		if ($version && $version == $vid) {
			if ($param['verbose'])
				print "$destination -> $version online already\n";
		} else {
			print "$destination";

			print " [uploading ...";
			//this actully checks the ONLINE file
			if ($filesystem->file_exists($_SERVER['DOCUMENT_ROOT'].$destination)) {
				print " Already found!";
				$memcache->name_set('fs',$filename,$vid,false,$memcache->period_long);
			} else {
				print " copying...";
				if ($param['execute']) {

					//use a relative local path deliberetly, to avoid it picking up as a bucket path!
					$r = $filesystem->copy(".".$filename, $_SERVER['DOCUMENT_ROOT'].$destination, null, 'STANDARD');

					if ($r)
						$memcache->name_set('fs',$filename,$vid,false,$memcache->period_short);
				}
			}

			print "\n";
		}
	} elseif ($param['verbose']) {
		print "# $filename not found\n";
	}
}
