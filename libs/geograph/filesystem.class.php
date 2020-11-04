
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

#########################################################
// the main workhorse for uploading a file!

	//copy a local file to remote. Can copy local->local, but cant copy from remote.
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

			//we do this ourselfs, as S3 class built in detection works on source file, not destination! We tend to have files using known extensions anyway, so works (because cant use fileinfo)
			$headers['Content-Type'] = parent::__getMIMEType($destination);

			/* we cant use putObjectFile as it doesnt have storage class param!*/
			//  putObject($input, $bucket, $uri, $acl = self::ACL_PRIVATE, $metaHeaders = array(), $requestHeaders = arr
			return parent::putObject(self::inputFile($local), $bucket, $destination, $acl, array(), $headers, $storageClass);
		} else {
			//fall back basic filesystem operation
			return copy($local, $destination);
			//todo, do touch?
		}
	}

	//so can write without a temporally file
        function file_put_contents($destination, &$data) {
                list($bucket, $destination) = $this->getBucketPath($destination);
                if ($bucket) {
			$headers = array();

                        $storageClass = empty($storage)?$this->defaultStorage:$storage;
                        //small files, store as STANDARD rather than STANDARD_IA, as there is a minumum of 120kb. 50k is used, because IA is still 40% cost of STD
                        if ($storageClass == 'STANDARD_IA' && strlen($data) < 50000)
                                $storageClass = 'STANDARD';

			$headers['x-amz-meta-mtime'] = time();

                        //we do this ourselfs, as S3 class built in detection works on source file, not destination! We tend to have files using known extensions anyway, so works (because cant use fileinfo)
                        $headers['Content-Type'] = parent::__getMIMEType($destination);

			return parent::putObject($data, $bucket, $destination, $acl, array(), $headers, $storageClass);
                } else {
                        return file_put_contents($destination, $data);
                }
        }

#########################################################

	//really a copy+delete
	function rename($local, $destination, $acl = self::ACL_FULL_CONTROL, $storage = null) {

		//normal filesystems will do this, but we need to do it. In case of S3, the dir wont exist, so can't actully use is_dir($dest) type logic!
		if (substr($destination,-1) == '/')
			$destination .= basename($local);

		list($bucket, $destination2) = $this->getBucketPath($destination);

		if ($bucket) {
			//may as well use logic already in copy.
			$r = $this->copy($local, $destination, $acl, $storage);

			if ($r) //todo, not sure how robust this is!
				unlink($local);
		} else {
			rename($local, $destination);
		}
	}

	function move_uploaded_file($local, $destination, $acl = self::ACL_FULL_CONTROL, $storage = null) {
		if (!is_uploaded_file($local)) {
			return FALSE;
		}
		$this->copy($local, $destination, $acl, $storage);
	}

#########################################################

	//executes command, assumes that the command is WRITING a file to fileystem
	//appending the temporally fielname to the end. Its assumes the comand will write to the file, which is then copied/uploaded
	//this was the original version, but only dealt with using a local file for 'source'
	function execute2($cmd, $destination, $acl = self::ACL_FULL_CONTROL, $storage = null) {
		$tmpfname = tempnam("/tmp", "FOO");
		passthru($cmd.$tmpfname); //todo, maybe passthur not right version
		if (filesize($tmpfname)) //dont bother copying empyu files - probably a command failure
			$this->copy($tmpfname, $destination, $acl, $storage);

		//always, delete, even if failed
		unlink($tmpfname);
	}

#########################################################
// special execute wrapper

	//execute, command,  assumes that the command is WRITING a file to fileystem
	function execute($cmd, $local, $destination, $acl = self::ACL_FULL_CONTROL, $storage = null) {
		if (!empty($local)) {
			list($sbucket, $sfilename) = $this->getBucketPath($local);
			if ($sbucket) {
				//download!
				$tmp_src = $this->_get_remote_as_tempfile($sbucket, $sfilename);
				$cmd = str_replace('%s',$tmp_src, $cmd);
			} else {
				$cmd = str_replace('%s',$local, $cmd);
			}
		}

		if (!empty($destination)) {
			if (strpos($cmd,'%d')===FALSE)
				$cmd.="%d"; //add to end!
			list($dbucket, $dfilename) = $this->getBucketPath($destination);
			if ($dbucket) {
				//upload
				$tmp_dst = tempnam("/tmp", "l".getmypid());
				$cmd = str_replace('%d',$tmp_dst, $cmd);
			} else {
				$cmd = str_replace('%d',$destination, $cmd);
			}
		}

print "$cmd\n";

                passthru($cmd); //todo, maybe passthur not right version

		if (!empty($tmp_dst)) {
	                if (filesize($tmp_dst)) //dont bother copying empyu files - probably a command failure
				$this->copy($tmp_dst, $destination, $acl, $storage);

			//always, delete, even if failed
	                unlink($tmp_dst);
		} else {
			//if writing to local file, nothing more todo?
		}
	}

	//
	function _get_remote_as_tempfile($bucket, $filename) {
		//todo add caching
		$tmpfname = tempnam("/tmp", "r".getmypid());

		$this->getObject($bucket, $filename, $tmpfname);

		//todo, register shutdown function to unlink it!
		return $tempfname;
	}

#########################################################
// functions to work on files

	function is_dir($filename) {
		list($bucket, $filename) = $this->getBucketPath($filename);
		if ($bucket) {
			return true; //fake, most likely use for is_dir, is to check if need to mkdir, which dont with S3!
		} else {
			return is_dir($filename);
		}
	}

	function mkdir($filename, $mode = 0777, $recursive = FALSE) {
		list($bucket, $filename) = $this->getBucketPath($filename);
		if ($bucket) {
			return true; //fake, S3 doesnt have real directions, can write any key
		} else {
			return mkdir($filename, $mode, $recursive);
		}
	}

#########################################################
// function to READ files from remote.

	function file_exists($filename) {
		list($bucket, $filename) = $this->getBucketPath($filename);
		if ($bucket) {
			//for photos, use our getimagesize, which is optmized to use memcache etc to avoid FS calls where possible.
			if (strpos($bucket,'photos') !== FALSE && strpos($filename,'.jpg')) {
				return $this->getimagesize($filename)?1:0;
			}
			//getObjectInfo
		} else {
			return file_exists($filename);
		}
	}

	function getimagesize($filename) {
		return true; //todo!
	}


	//dont support additional params for now
	function file_get_contents($filename) {
		list($bucket, $filename) = $this->getBucketPath($filename);
		if ($bucket) {
			//use temp file for now. maybe could use getObject directly, to avoid wrtiing to a temp file?
			$tmpfname = $this->_get_remote_as_tempfile($bucket, $filename)
			return file_get_contents($tmpfname);
		} else {
			return file_get_contents($filename);
		}
	}
	function file($filename) {
		list($bucket, $filename) = $this->getBucketPath($filename);
		if ($bucket) {
			$tmpfname = $this->_get_remote_as_tempfile($bucket, $filename)
			return file($tmpfname);
		} else {
			return file($filename);
		}
	}
	function readfile($filename) {
		list($bucket, $filename) = $this->getBucketPath($filename);
		if ($bucket) {
			$tmpfname = $this->_get_remote_as_tempfile($bucket, $filename)
			return readfile($tmpfname);
		} else {
			return readfile($filename);
		}
	}

	function imagecreatefromjpeg($filename) {
		list($bucket, $filename) = $this->getBucketPath($filename);
		if ($bucket) {
			$tmpfname = $this->_get_remote_as_tempfile($bucket, $filename)
			return imagecreatefromjpeg($tmpfname);
		} else {
			return imagecreatefromjpeg($filename);
		}
	}

	function imagecreatefromgd($filename) {
		list($bucket, $filename) = $this->getBucketPath($filename);
		if ($bucket) {
			$tmpfname = $this->_get_remote_as_tempfile($bucket, $filename)
			return imagecreatefromgd($tmpfname);
		} else {
			return imagecreatefromgd($filename);
		}
	}

#########################################################


#########################################################

/*
	function ($filename) {
		list($bucket, $filename) = $this->getBucketPath($filename);
		if ($bucket) {
			$tmpfname = $this->_get_remote_as_tempfile($bucket, $filename)
			return ($tmpfname);
		} else {
			return ($filename);
		}
	}

	//just a tempalte function showing how to work with a single filename
	function template($filename) {
		list($bucket, $filename) = $this->getBucketPath($filename);
		if ($bucket) {
			//do magic
		} else {
			//call normal filesystem version
			return template($filename);
		}
	}


*/

#########################################################
}


