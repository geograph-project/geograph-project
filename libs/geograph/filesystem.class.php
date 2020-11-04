<?php

/**
 * $Project: GeoGraph $
 * $Id: filesystem.class.php $
 *
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2020 Barry Hunter (geo@barryhunter.co.uk)
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

/**
 * Abstraction over the local filesystem, ultimately so can write files directly to S3 avoid posix/s3fs etc.
 * Allows calling relatively 'standard' filesystem commands, but redirects them to use S3 API directly (in PHP)
 * ... avoids need to use s3fs etc....
 * which has several drawbacks, mainly that because uses posix standards, can result in double put on new files
 * but also reading folders can be very slow (S3 doesnt have real folders, and s3fs tries to be compatible with idfferent standards, so uses lots of GET's lookinf for possible folders)
 * plus of course we only implement functions we NEED. and avoid loading dedicated binary.
 * finally, we can use memcache/redis for perofrmance, avoiding the fs based cahcing that s3fs does.
 */

require_once "3rdparty/S3.php";

class FileSystem extends S3 {

	var $defaultStorage = self::STORAGE_CLASS_IT;

	var $buckets = array();

	function __construct() {
		global $CONF;

		//fetch folder first to find the only creds available?
		$iamrole = file_get_contents("http://169.254.169.254/latest/meta-data/iam/security-credentials/");

		if (empty($iamrole))
			return; //no more construction needed, the class can run without S3.

		$json = file_get_contents("http://169.254.169.254/latest/meta-data/iam/security-credentials/$iamrole");

		// assume it never fails for now! tofix (use memcache/apc??)
		$decode = json_decode($json,true);

//todo, get buckets from $CONF ?
if (!empty($_SERVER['BASE_DIR'])) {//running inside a container
	$this->buckets = array(
                "{$_SERVER['BASE_DIR']}/public_html/" => $CONF['s3_photos_bucket_path'],
		"{$_SERVER['BASE_DIR']}/rastermaps/" => $CONF['s3_rastermaps_bucket_path'],
	);
}

		parent::__construct($decode['AccessKeyId'], $decode['SecretAccessKey'], false);

			//curl http://169.254.169.254/latest/meta-data/placement/region --although we want the region of the bucket(s), not the current instance!
		$this->setRegion('eu-west-1'); //can also pass this in construct, todo, shouoldnt be harcdoed.

		if (!empty($decode["Token"])) {
			$this->setSignatureVersion('v4');
			self::$securityToken = $decode["Token"];
		}
	}

	function getBucketPath($filename) {
		foreach ($this->buckets as $prefix => $bucket) {
			if (strpos($filename,$prefix) === 0) {
				$bits = explode("/",$bucket,2);
				return array($bits[0],str_replace($prefix,$bits[1],$filename));
			}
		}
		return array(null, $filename);
	}

	function copy($local, $destination, $acl = self::ACL_FULL_CONTROL, $storage = null) {

		//normal filesystems will do this, but we need to do it. In case of S3, the dir wont exist, so can't actully use is_dir($dest) type logic!
		if (substr($destination,-1) == '/')
			$destination .= basename($local);


		list($bucket, $destination) = $this->getBucketPath($destination);

		if ($bucket) {
			$headers = array();

			//set the storage class
			$storageClass = empty($storage)?$this->defaultStorage:$storage;
			//small files, store as STANDARD rather than STANDARD_IA, as there is a minumum of 120kb. 50k is used, because IA is still 40% cost of STD
			if ($storageClass == 'STANDARD_IA' && filesize($local) < 50000)
				$storageClass = 'STANDARD';

			//set the mtime, for compatiblity with s3fs etc
			$headers['x-amz-meta-mtime'] = filemtime($local);

			/* we cant use putObjectFile as it doesnt have storage class param!*/

			//  putObject($input, $bucket, $uri, $acl = self::ACL_PRIVATE, $metaHeaders = array(), $requestHeaders = arr
			return parent::putObject(self::inputFile($local), $bucket, $destination, $acl, array(), $headers, $storageClass);
		} else {
			//fall back basic filesystem operation
			return copy($local, $destination);
			//todo, do touch?
		}
	}

	//executes command, appending the temporally fielname to the end. Its assumes the comand will write to the file, which is then copied/uploaded
	function execute($cmd, $destination, $acl = self::ACL_FULL_CONTROL, $storage = null) {
		$tmpfname = tempnam("/tmp", "FOO");
		passthru($cmd.$tmpfname); //todo, maybe passthur not right version
		if (filesize($tmpfname)) //dont bother copying empyu files - probably a command failure 
			copy($tmpfname, $destination);

		//always, delete, even if failed
		unlink($tmpfname);
	}

}


