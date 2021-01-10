<?php
/**
 * $Project: GeoGraph $
 * $Id: uploadmanager.class.php 8867 2018-10-18 11:02:35Z barry $
 *
 * GeoGraph geographic photo archive project
 * http://geograph.sourceforge.net/
 *
 * This file copyright (C) 2005 Paul Dixon (paul@elphin.com)
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
* Provides the UploadManager class
*
* @package Geograph
* @author Paul Dixon <paul@elphin.com>
* @version $Revision: 8867 $
*/

/**
* Upload manager class
* This class simply aims to separate the processing of a new image
* upload from the actual presentation of that process
*/
class UploadManager
{
	var $db=null;
	var $errormsg="";
	var $upload_id="";
	var $upload_width=0;
	var $upload_height=0;
	var $square=null;

	var $imageclass=''; //we specifically initialize these, as they are rarely set on new uploads any more.
	var $user_status='';
	var $realname=''; //credit not needed, but can be provided

	var $tmppath="";

	/**
	* Constructor
	*/
	function UploadManager()
	{
		global $CONF,$USER;
		$this->db = NewADOConnection($GLOBALS['DSN']);
		if (!$this->db) die('Database connection failed');

		$this->tmppath=isset($CONF['photo_upload_dir'])?$CONF['photo_upload_dir']:'/tmp';

		//todo make this a dedicated config var!
		$this->use_new_upload = !empty($CONF['filesystem_dsn']);

		if ($this->use_new_upload) {

			$a = $USER->user_id%10;
	                $b = intval($USER->user_id/10)%10;

	                $dir = "{$this->tmppath}/$a/$b/{$USER->user_id}";

			if (!is_dir($dir)) {
        	                mkdir($dir,0755,true);
	                }

			//to be removed.
			$dir = str_replace('upload_tmp_dir','upload_tmp_dir_old',$dir);

                        if (!is_dir($dir)) {
                                mkdir($dir,0755,true);
                        }
		} else {
			$a = $USER->user_id%10;
			$dir = "{$this->tmppath}/$a";
			if (!is_dir($dir)) {
                                mkdir($dir,0755,true);
                        }
		}
	}

	/**
	* Store the GridSquare we are uploading
	*/
	function setSquare(&$square)
	{
		$this->square=&$square;
	}

	/**
	* return full path to temporary image file
	*/
	function _pendingJPEG($id)
	{
		global $USER;

		if ($this->use_new_upload) {
			$u = $USER->user_id;
			$a = $USER->user_id%10;
                        $b = intval($USER->user_id/10)%10;
			return "{$this->tmppath}/$a/$b/$u/newpic_u{$u}_{$id}.jpeg";
		}

		return $this->tmppath.'/'.($USER->user_id%10).'/newpic_u'.$USER->user_id.'_'.$id.'.jpeg';
	}

	/**
	* return full path to temporary image file
	*/
	function _originalJPEG($id)
	{
		global $USER;
		if ($this->use_new_upload) {
			$u = $USER->user_id;
			$a = $USER->user_id%10;
                        $b = intval($USER->user_id/10)%10;
			return "{$this->tmppath}/$a/$b/$u/newpic_u{$u}_{$id}.original.jpeg";
		}
		return $this->tmppath.'/'.($USER->user_id%10).'/newpic_u'.$USER->user_id.'_'.$id.'.original.jpeg';
	}

	/**
	* return full path to temporary file for EXIF data
	*/
	function _pendingEXIF($id)
	{
		global $USER;
		if ($this->use_new_upload) {
			$u = $USER->user_id;
			$a = $USER->user_id%10;
                        $b = intval($USER->user_id/10)%10;
			return "{$this->tmppath}/$a/$b/$u/newpic_u{$u}_{$id}.exif";
		}
		return $this->tmppath.'/'.($USER->user_id%10).'/newpic_u'.$USER->user_id.'_'.$id.'.exif';
	}

	/**
	* Check upload identifier
	*/
	function validUploadId($id)
	{
		return preg_match('/^[a-f0-9]{32}$/',$id);
	}

	/**
	* Set upload identifier after validating
	*/
	function setUploadId($id,$load_size = true)
	{
		if ($this->validUploadId($id))
		{
			$this->upload_id=$id;
			if ($load_size)
				$this->initUploadSize();
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	* set title
	*/
	function setTitle($title)
	{
		$this->title=$title;
	}

	/**
	* set comment
	*/
	function setComment($comment)
	{
		$this->comment=$comment;
	}

	/**
	* set tags
	*/
	function setTags($tags,$prefix='')
	{
		$this->tags=$tags;
		$this->tagsPrefix=$prefix;
	}

	/**
	* set tags
	*/
	function setSubject($subject)
	{
		$this->subject=$subject;
	}

	/**
	* set tags
	*/
	function setContexts($contexts)
	{
		$this->contexts=$contexts;
	}

	/**
	* set image taken date
	*/
	function setTaken($taken)
	{
		$this->imagetaken=$taken;
	}

	/**
	* set imageclass
	*/
	function setClass($imageclass)
	{
		$this->imageclass=$imageclass;
	}

	/**
	* set viewpoint_gridreference
	*/
	function setViewpoint($viewpoint_gridreference)
	{
		$this->viewpoint_gridreference=$viewpoint_gridreference;
	}

	/**
	* set view_direction
	*/
	function setDirection($view_direction)
	{
		$this->view_direction=$view_direction;
	}
	/**
	* set use6fig
	*/
	function setUse6fig($use6fig)
	{
		$this->use6fig=$use6fig;
	}

	/**
	* set largestsize
	*/
	function setLargestSize($largestsize)
	{
		$this->largestsize=$largestsize;
	}

	/**
	* set credit
	*/
	function setCredit($realname)
	{
		$this->realname = $realname;
	}

	/**
	* set user_status
	*/
	function setUserStatus($user_status)
	{
		$this->user_status=$user_status;
	}


	function rotateUpload($id,$degrees,$force=false) {
		global $CONF,$USER;

		if($this->validUploadId($id))
                {
                        $uploadfile = $this->_pendingJPEG($id);
			$orginalfile = $this->_originalJPEG($id);
			$exiffile = $this->_pendingEXIF($id);

                        //generate a unique "upload id" - we use this to hold the image until
                        //they've confirmed they want to submit
                        $upload_id=md5(uniqid('upload'));

                        $uploadfile2 = $this->_pendingJPEG($upload_id);
			$exiffile2 = $this->_pendingEXIF($upload_id);

			//if have large image, rotate it
                        if (file_exists($orginalfile)) {
				//rotate the largest - and recreate the midsize!
				$orginalfile2 = $this->_originalJPEG($upload_id);

				$from = $orginalfile;
				$to = $orginalfile2;
			} else {
				//otherwise rotate the as provided image
				$from = $uploadfile;
				$to = $uploadfile2;
			}

			//NOTE, `exiftran` is NOT used, as it only does lossless rotation, if not right size creates odd edgeing
			//... so use `jpegtran` instead, which allows exact control, but doesnt reset exif, so have to do that manually with exiftool
			//`jpegexiforient` sounds perfect, but doesnt seem to work
			// imagemagick is used to do lossy rotation, and can reset the exif itself.

			$lossy = false;
			//do the actual rotation, lossless if possible
			$cmd = "jpegtran -rotate $degrees -perfect -copy all -outfile $to $from";
			passthru($cmd);

			if (file_exists($to)) {
				//it rotated, but we MAY need to manually reset the rotation flag! (-copy all copied everything including the oreitentation flag!)
				$orient = `exiftool -Orientation -n $to`;
				if (strpos($orient,'Orientation') !== FALSE && strpos($orient,'1') === FALSE)
					`exiftool -Orientation=1 -n -overwrite_original $to`;
			} else {
				//perfect rotation failed, so do imperfect!

				$lossy = true;

				if (empty($force)) {
					return array('lossy'=>$lossy);
				}

				//note, could not set quality, and would try to maintain current or fallback to 92
				// also -orient only UPDATES the Orientation tag, if none existing, its not added, but thats OK!
				$cmd = sprintf ("\"%sconvert\" jpg:%s -rotate $degrees -orient TopLeft -quality 87 jpg:%s", $CONF['imagemagick_path'], $from, $to);
				passthru($cmd);
			}

			//if have large image, it been rotated, and now need to create new midsize image
			if (file_exists($orginalfile)) {
				$max_dimension = 640;

				$ok = $this->_downsizeFile($uploadfile2,$max_dimension,$orginalfile2);
			}

			//move the EXIF too, and delete the old versions.
			rename($exiffile,$exiffile2); //may as well just rename this, partly as the multi-submit uses the timestamp of exif to show when uploaded, so dont want copy it
			unlink($uploadfile);
			if (file_exists($orginalfile))
				unlink($orginalfile);

	                //reassign the snippets and tags
                		$gid1 = crc32($id)+4294967296;
		                $gid1 += $USER->user_id * 4294967296;
                		$gid1 = sprintf('%0.0f',$gid1);

                		$gid2 = crc32($upload_id)+4294967296;
		                $gid2 += $USER->user_id * 4294967296;
                		$gid2 = sprintf('%0.0f',$gid2);

			        $this->db->Execute($sql = "UPDATE gridimage_snippet SET gridimage_id = $gid2 WHERE gridimage_id = ".$gid1);
			        $this->db->Execute($sql = "UPDATE gridimage_tag SET gridimage_id = $gid2 WHERE gridimage_id = ".$gid1);

			$size = getimagesize($uploadfile2);
                        return array('upload_id'=>$upload_id,'width'=>$size[0],'height'=>$size[1],'lossy'=>$lossy);
		}
	}


	/**
	* outputs jpeg data for upload id $id and exits
	*/
	function outputPreviewImage($id)
	{
		global $USER;
		$err="";

		if($this->validUploadId($id))
		{
			$uploadfile = $this->_pendingJPEG($id);
			if (file_exists($uploadfile))
			{
				customExpiresHeader(3600*48);
				header("Content-Type:image/jpeg");
				readfile($uploadfile);
				exit;
			}
			else
			{
				$err="Upload image not found";
			}
		}
		else
		{
			$err="Bad preview id";
		}

		//generate an error message image if we reach here///
		$im  = imagecreate (320, 240);
		$bgc = imagecolorallocate ($im, 255, 255, 255);
		$tc  = imagecolorallocate ($im, 0, 0, 0);
		imagefilledrectangle ($im, 0, 0, 320, 240, $bgc);
		imagestring ($im, 1, 5, 5, $err, $tc);

		imagejpeg($im);
		imagedestroy($im);

		exit;
	}

	/**
	* initialise size members for preview Image
	*/
	function initUploadSize()
	{
		$this->upload_width=0;
		$this->upload_height=0;
		$ok=false;

		if($this->validUploadId($this->upload_id))
		{
			$uploadfile = $this->_pendingJPEG($this->upload_id);
			if (@file_exists($uploadfile))
			{
				$s=getimagesize($uploadfile);
				$this->upload_width=$s[0];
				$this->upload_height=$s[1];
				$ok=true;
			}
		}
		return $ok;
	}

	/**
	* store error message
	*/
	function error($msg)
	{
		$this->errormsg=$msg;
	}

	/**
	* See if file is a JPEG
	*/
	function _isJpeg($file)
	{
		$is_jpeg=false;

		//use built in mime_content_type if available...
		if (function_exists('mime_content_type'))
		{
			$is_jpeg= mime_content_type($file)=='image/jpeg';

			if (!$is_jpeg) {
                                        ob_start();
                                        print_r(mime_content_type($file));print "\n";
					print_r($_FILES);
					print_r($_POST);
                                        $con = ob_get_clean();
                                        debug_message('[Geograph] JPEG Detection Failed',$con);
			}

		}
		else
		{
			//basic home grown version
			$fp=fopen($file, 'rb');
			if ($fp)
			{
				$sig=fread($fp,2);
				fclose($fp);

				$b1=ord($sig{0});
				$b2=ord($sig{1});
				$is_jpeg=($b1=255) && ($b2==216);
			}
		}
		return $is_jpeg;
	}

	function trySetDateFromExif($exif)
	{
		//dont know yet which of these is best but they all seem to be the same on my test images
		if (($date = @$exif['EXIF']['DateTimeOriginal']) ||
		    ($date = @$exif['EXIF']['DateTimeDigitized']) ||
		    ($date = @$exif['IFD0']['DateTime']) )
		{
			//Example: ["DateTimeOriginal"]=> string(19) "2004:07:09 14:05:19"
			 list($date,$time) = explode(' ',$date);
			 $dates = explode(':',$date);
			 $this->exifdate = implode('-',$dates);
		}
	}

	function fetch_remote_file($url,$filename) {
		$data = file_get_contents($url);
		if (strlen($data) > 0) {
			file_put_contents($filename,$data);

			$GLOBALS['http_response_header'] = $http_response_header;

			return true;
		}
		return false;
	}

	function processURL($url)
	{
		global $USER,$CONF;
		$ok=false;

	split_timer('upload'); //starts the timer

		//generate a unique "upload id" - we use this to hold the image until
		//they've confirmed they want to submit
		$upload_id=md5(uniqid('upload'));

		$temp_file = tempnam("/tmp",'upload');

		if (preg_match('/^https?:\/\/[\w\.-]+\/[\w\.\/~-]+\.jpg$/',$url)
		 || preg_match('/^https?:\/\/www\.picnik\.com\/file\/\d+$/',$url)
		 || preg_match('/^https?:\/\/cdn\.filestackcontent\.com\/\w+$/',$url)
		 || preg_match('/^https?:\/\/ucarecdn.com\/\w[\w-]+\//',$url)
                 || preg_match('/^https:\/\/lh3.googleusercontent.com\//',$url)
		 || preg_match('/^https?:\/\/www\.filepicker\.io\//',$url))
		{
			if ($this->fetch_remote_file($url, $temp_file))
			{
				if ($this->_isJpeg($temp_file))
				{
					$ok = $this->_processFile($upload_id,$temp_file,false);
				}
				else
				{
					$this->error("We only accept JPEG images - your upload did not appear to be a valid JPEG file");
				}
			}
			else
			{
				//playing silly buggers?
				$this->error("There were problems processing your upload - please contact us");
			}
		}
		else
		{
			//playing silly buggers?
			$this->error("We where unable to fetch that image - please contact us");
		}

	split_timer('upload','processURL',$url); //logs the wall time

		return $ok;
	}

	function processUpload($upload_file,$all_non_upload = false)
	{
		global $USER,$CONF;
		$ok=false;

	split_timer('upload'); //starts the timer

		if ($this->_isJpeg($upload_file))
		{
			//generate a unique "upload id" - we use this to hold the image until
			//they've confirmed they want to submit
			$upload_id=md5(uniqid('upload'));

			if ($all_non_upload)
			{
				$ok = $this->_processFile($upload_id,$upload_file,false);
			}
			elseif (is_uploaded_file($upload_file))
			{
				$ok = $this->_processFile($upload_id,$upload_file,true);
			}
			else
			{
				//playing silly buggers?
				$this->error("There were problems processing your upload - please contact us");
			}
		}
		else
		{
			$this->error("We only accept JPEG images - your upload did not appear to be a valid JPEG file");
		}

	split_timer('upload','processUpload',$upload_file); //logs the wall time

		return $ok;
	}

	function _processFile($upload_id,$upload_file,$is_upload = true) {
		global $USER,$CONF;
		$ok = false;
		//save the exif data for the loaded image
		$exif = @exif_read_data($upload_file,0,true);

		if ($exif!==false)
		{
			//some cameras produce a MakerNote, that only PARTLY read. The original issue (below) had empty makernotes
			// see https://www.geograph.org.uk/discuss/index.php?&action=vthread&forum=4&topic=29948 for this new variation
                        if (preg_match('/MAKERNOTE$/', $exif['FILE']['SectionsFound']) && !empty($exif['MAKERNOTE']) ) {
                                foreach ($exif['MAKERNOTE'] as $key => $value) {
                                        if (strpos($key,'UndefinedTag:') === 0) {
                                                //has broken values, possibly curruptd!
                                                unset($exif['EXIF']['MakerNote']); // so remove it the next if statement will strip fully
                                                break;
                                        }
                                }
                        }

			//create a new exif file without Makernote, if it caused PHP to fail to read EXIF completely
			if (preg_match('/MAKERNOTE$/', $exif['FILE']['SectionsFound']) && !isset($exif['EXIF']['MakerNote']) ) {
				//if MAKERNOTE is listed as a section, but its not present, then probably exif_read_data failed due to PHP bug
				// https://bugs.php.net/bug.php?id=72682
				// http://www.geograph.org.uk/discuss/index.php?&action=vthread&forum=4&topic=29257
				// http://cake.geograph.org.uk/exif/_exif_test-andrew.php
				// [Tiger Computing Ltd #19872] PHP bug introduced, possible to fix?

				$filename = tempnam("/tmp",'exif');
				@unlink($filename); //exif wont overwrite the file
				`exiftool -o $filename -m -makernotes= $upload_file`;
				$exif2 = @exif_read_data($filename,0,true);
				if ($exif2!==false)
					$exif = $exif2;
				@unlink($filename);
			}

			$this->trySetDateFromExif($exif);
			$this->rawExifData = $exif;
			$strExif=serialize($exif);
			$exif =  $this->_pendingEXIF($upload_id);
			$f=fopen($exif, 'w');
			if ($f)
			{
				fwrite($f, $strExif);
				fclose($f);
			}
		}
		$max_dimension=640;

		$pendingfile = $this->_pendingJPEG($upload_id);

		list($width, $height, $type, $attr) = getimagesize($upload_file);

		if ($width > $max_dimension || $height > $max_dimension) {

			//create the 'main' photo
			if ($ok = $this->_downsizeFile($pendingfile,$max_dimension,$upload_file)) {
				//remember useful stuff
				$this->upload_id=$upload_id;
				$this->original_width=$width;
				$this->original_height=$height;
			}

			//save as 'original'
			$orginalfile = $this->_originalJPEG($upload_id);

			if ($is_upload) {
				move_uploaded_file($upload_file,$orginalfile);
			} else {
				rename($upload_file,$orginalfile);
			}

			$this->hasoriginal = true;

		} else {
			//put the file in the right place...
			if ($is_upload == 'upload') {
				move_uploaded_file($upload_file,$pendingfile);
			} else {
				rename($upload_file,$pendingfile);
			}

			$ok = true;
			$this->upload_id=$upload_id;
			$this->upload_width=$width;
			$this->upload_height=$height;
		}
		return $ok;
	}

	function initOriginalUploadSize()
	{
		$this->original_width=0;
		$this->original_height=0;
		$ok=false;

		if($this->validUploadId($this->upload_id))
		{
			$orginalfile = $this->_originalJPEG($this->upload_id);
			if (@file_exists($orginalfile))
			{
				$this->hasoriginal = true;
				$s=getimagesize($orginalfile);
				$this->original_width=$s[0];
				$this->original_height=$s[1];
				$ok=true;
			}
		}
		return $ok;
	}

	function _downsizeFile($filename,$max_dimension,$source = '') {
		global $USER,$CONF;

                if(extension_loaded('newrelic'))
                        newrelic_ignore_transaction();

	split_timer('upload'); //starts the timer

		if (strlen($CONF['imagemagick_path'])) {
			//try imagemagick first
			list($width, $height, $type, $attr) = getimagesize($source?$source:$filename);

			if ($width > $max_dimension || $height > $max_dimension) {

				//removed the unsharp as it makes some images worse - needs to be optional
				// best fit found so far: -unsharp 0x1+0.8+0.1 -blur 0x.1

				if ($source) {
					$cmd = sprintf ("\"%sconvert\" -resize %ldx%ld -quality 87 -strip jpg:%s jpg:%s", $CONF['imagemagick_path'],$max_dimension, $max_dimension, $source, $filename);
				} else {
					$cmd = sprintf ("\"%smogrify\" -resize %ldx%ld -quality 87 -strip jpg:%s", $CONF['imagemagick_path'],$max_dimension, $max_dimension, $filename);
				}
				passthru ($cmd);

				list($width, $height, $type, $attr) = getimagesize($filename);
			}

			if ($width && $height && $width <= $max_dimension && $height <= $max_dimension) {
				//check it did actully work

				$this->upload_width=$width;
				$this->upload_height=$height;
				$ok=true;
			}
		}

		if (!$ok) {
			//generate a resized image
			$uploadimg = @imagecreatefromjpeg ($source?$source:$filename);
			if ($uploadimg)
			{
				$srcw=imagesx($uploadimg);
				$srch=imagesy($uploadimg);

				if (($srcw>$max_dimension) || ($srch>$max_dimension))
				{
					//figure out size of image we'll keep
					if ($srcw>$srch)
					{
						//landscape
						$destw=$max_dimension;
						$desth=round(($destw * $srch)/$srcw);
					}
					else
					{
						//portrait
						$desth=$max_dimension;
						$destw=round(($desth * $srcw)/$srch);
					}

					$resized = imagecreatetruecolor($destw, $desth);
					imagecopyresampled($resized, $uploadimg, 0, 0, 0, 0,
								$destw,$desth, $srcw, $srch);

					#require_once('geograph/image.inc.php');

					#UnsharpMask($resized,100,0.5,3);

					imagedestroy($uploadimg);

					//overwrite the upload
					imagejpeg ($resized, $filename, 87);
					imagedestroy($resized);

				}
				else
				{
					//don't need it anymore
					imagedestroy($uploadimg);
					$desth=$srch;
					$destw=$srcw;

				}

				//remember useful stuff
				$this->upload_width=$destw;
				$this->upload_height=$desth;
				$ok=true;
			}
			else
			{
				$this->error("Unable to load image - we can only accept valid JPEG images");
			}
		}

		split_timer('upload','_downsizeFile',"{$this->upload_width},{$max_dimension}"); //logs the wall time

		return $ok;
	}

	function reReadExifFile()
	{
		split_timer('upload');

		//get the exif data
		$exiffile=$this->_pendingEXIF($this->upload_id);
		$exif="";
		$f=@fopen($exiffile, 'r');
		if ($f)
		{
			$exif = fread ($f, filesize($exiffile));
			fclose($f);
			$strExif=unserialize($exif);
			if ($strExif!==false)
			{
				$this->trySetDateFromExif($strExif);
				$this->rawExifData = $strExif;
			}
		}

		split_timer('upload','reReadExifFile',$this->upload_id); //logs the wall time
	}

	/**
	* commit the upload process
	*/
	function commit($method = '',$skip_cleanup = false)
	{
		global $USER,$CONF,$memcache;

		if($this->validUploadId($this->upload_id))
		{
			$uploadfile = $this->_pendingJPEG($this->upload_id);
			if (!file_exists($uploadfile))
			{
				return "Upload image not found";
			}
		}
		else
		{
			return ("Must assign upload id");
		}

		if(!is_object($this->square))
		{
			return("Must assign square");
		}

		if (!empty($CONF['use_insertionqueue'])) {
			$existing = $this->db->getOne("select gridimage_id from gridimage_queue g inner join submission_method m using (gridimage_id) where submitted > date_sub(now(), interval 1 hour) and preview_key = '{$this->upload_id}'");
		}

		if (empty($existing)) {
			$existing = $this->db->getOne("select gridimage_id from gridimage g inner join submission_method m using (gridimage_id) where submitted > date_sub(now(), interval 1 hour) and preview_key = '{$this->upload_id}'");
		}

		if (!empty($existing)) {
					ob_start();
                                        print "\nOriginal: $existing\n";
                                        print_r($_FILES);
                                        print_r($_POST);
                                        $con = ob_get_clean();
                                        debug_message('[Geograph] Duplicate Submission',$con);

			return("Duplicate Submission detected. This image appears to have already been submitted. If you think this is an error, please press F5 key in about 5 minutes time to try again.");
		}

		$viewpoint = new GridSquare;
		if ($this->viewpoint_gridreference) {
			$ok= $viewpoint->setByFullGridRef($this->viewpoint_gridreference,true,true);
		}

		//get sequence number
	split_timer('upload'); //starts the timer

		$mkey = $this->square->gridsquare_id;
		$seq_no =& $memcache->name_get('sid2',$mkey);

		if (empty($seq_no) && !empty($CONF['use_insertionqueue'])) {
			$seq_no = $this->db->GetOne("select max(seq_no) from gridimage_queue where gridsquare_id={$this->square->gridsquare_id}");
		}
		if (empty($seq_no)) {
			$seq_no = $this->db->GetOne("select max(seq_no) from gridimage where gridsquare_id={$this->square->gridsquare_id}");
		}
		$seq_no=max($seq_no+1, 0);

		$memcache->name_set('sid2',$mkey,$seq_no,false,$memcache->period_long);

	split_timer('upload','startup',"$mkey"); //logs the wall time

		//ftf is zero under image is moderated
		$ftf=0;

		//get the exif data
		$exiffile=$this->_pendingEXIF($this->upload_id);
		$exif="";
		$f=@fopen($exiffile, 'r');
		if ($f)
		{
			$exif = fread ($f, filesize($exiffile));
			fclose($f);
		}

		if (!empty($CONF['use_insertionqueue'])) {
			$table = "gridimage_queue";
		} else {
			$table = "gridimage";
		}

	split_timer('upload'); //starts the timer

		//create record
		// nateasting/natnorthings will only have values if getNatEastings has been called (in this case because setByFullGridRef has been called IF an exact location is specifed)
		$sql=sprintf("insert into $table (".
			"gridsquare_id, seq_no, user_id, ftf,".
			"moderation_status,title,comment,nateastings,natnorthings,natgrlen,imageclass,imagetaken,".
			"submitted,viewpoint_eastings,viewpoint_northings,viewpoint_grlen,view_direction,use6fig,user_status,realname) values ".
			"(%d,%d,%d,%d,".
			"'pending',%s,%s,%d,%d,'%d',%s,%s,".
			"now(),%d,%d,'%d',%d,%d,%s,%s)",
			$this->square->gridsquare_id, $seq_no,$USER->user_id, $ftf,
			$this->db->Quote($this->title), $this->db->Quote($this->comment), 
			$this->square->nateastings,$this->square->natnorthings,$this->square->natgrlen,
			$this->db->Quote($this->imageclass), $this->db->Quote($this->imagetaken),
			$viewpoint->nateastings,$viewpoint->natnorthings,$viewpoint->natgrlen,$this->view_direction,
			$this->use6fig,$this->db->Quote($this->user_status),$this->db->Quote($this->realname));

		$this->db->Query($sql);

		//get the id
		$gridimage_id=$this->db->Insert_ID();

		//save the exif
		$sql=sprintf("insert into gridimage_exif (".
			"gridimage_id,exif) values ".
			"(%d,%s)",$gridimage_id,$this->db->Quote($exif));
		$this->db->Query($sql);

	split_timer('upload','insert',"$gridimage_id"); //logs the wall time


		//run this now, before FS operations, so that re record the preview_key, even if ultimately these commands fail!
		$this->db->Execute("INSERT INTO submission_method SET gridimage_id = $gridimage_id,method='$method',preview_key='{$this->upload_id}',largestsize = '{$this->largestsize}',hostname = '".trim(`hostname`)."'");

		//copy image to correct area
		$src=$this->_pendingJPEG($this->upload_id);

		$image=new GridImage;
		$image->gridimage_id = $gridimage_id;
		$image->user_id = $USER->user_id;

	split_timer('upload'); //starts the timer

		$storedoriginal = false;
		if ($ok = $image->storeImage($src,$this->use_new_upload)) {

	split_timer('upload','store',"$gridimage_id"); //logs the wall time

			$orginalfile = $this->_originalJPEG($this->upload_id);
			if (file_exists($orginalfile) && $this->largestsize && $this->largestsize > 640) {

				$this->_downsizeFile($orginalfile,$this->largestsize);

		split_timer('upload'); //starts the timer

				$storedoriginal =$image->storeOriginal($orginalfile,$this->use_new_upload);

		split_timer('upload','storeOriginal',"$gridimage_id"); //logs the wall time

			}

			if (!$skip_cleanup)
				$this->cleanUp();
		}

		//fire an event
		require_once('geograph/event.class.php');
		new Event(EVENT_NEWPHOTO, $gridimage_id.','.$USER->user_id.','.$storedoriginal);

	split_timer('upload'); //starts the timer

		//assign the snippets now we know the real id.
		$gid = crc32($this->upload_id)+4294967296;
		$gid += $USER->user_id * 4294967296;
		$gid = sprintf('%0.0f',$gid);

		$this->db->Execute($sql = "UPDATE gridimage_snippet SET gridimage_id = $gridimage_id WHERE gridimage_id = ".$gid);

		//assign the vision descriptions now we know the real id.
		$this->db->Execute($sql = "UPDATE vision_results SET id = $gridimage_id WHERE id = ".$gid);

		//assign the tags now we know the real id.
		require_once('geograph/tags.class.php');
		$tags = new Tags;
		$tags->promoteUploadTags($gridimage_id,$this->upload_id,$USER->user_id);

		//make sure any tags we have are added too
		if (!empty($this->tags)) {
			$tags->addTags($this->tags,$this->tagsPrefix);
		}
		if (!empty($this->subject)) {
			$tags->addSubject($this->subject);
		}
		if (!empty($this->contexts)) {
			$tags->addTags($this->contexts,'top');
		}
		if (!empty($this->tags) || !empty($this->subject)) {
			$tags->commit($gridimage_id,true);
		}

		$this->gridimage_id = $gridimage_id;

		if (!empty($method)) {
			if (!empty($GLOBALS['STARTTIME'])) {

				list($usec, $sec) = explode(' ',microtime());
				$endtime = ((float)$usec + (float)$sec);
				$timetaken = $endtime - $GLOBALS['STARTTIME'];

				$this->db->Execute("REPLACE INTO submission_method SET gridimage_id = $gridimage_id,method='$method',timetaken=$timetaken,preview_key='{$this->upload_id}',largestsize = '{$this->largestsize}',hostname = '".trim(`hostname`)."'");
			}
		}

	split_timer('upload','update_snippet',"$gridimage_id"); //logs the wall time

	}


	/**
	* add a high res image
	*/
	function addOriginal($image)
	{
		global $USER,$CONF,$memcache;

		split_timer('upload'); //starts the timer

		if($this->validUploadId($this->upload_id))
		{
			$uploadfile = $this->_pendingJPEG($this->upload_id);
			if (!file_exists($uploadfile))
			{
				return "Upload image not found";
			}
		}
		else
		{
			return ("Must assign upload id");
		}

		$src=$this->_pendingJPEG($this->upload_id);

			##################
			// we do this outselves (rather than inside $filesystem->copy(), used by StoreImage)
			// ... because we warn to invalidate multiple paths, and ONLY if they exist. Invalidation is expensive, so only do if needed
			if (!empty($CONF['s3_photos_bucket_path'])) {
				$image->previewUrl =    $image->_getOriginalpath(true,false,'_preview');
				$image->pendingUrl =    $image->_getOriginalpath(true,false,'_pending');

				$paths = array();
				if (basename($image->previewUrl) != "error.jpg")
					 $paths[]= $image->previewUrl;
				if (basename($image->pendingUrl) != "error.jpg")
					 $paths[]= $image->pendingUrl;

				if (!empty($paths)) {
					$filesystem = GeographFileSystem();
					$filesystem->_invalidate(trim($CONF['s3_photos_bucket_path'],'/'), $paths);
				}
			}

			##################

		//store the resized version - just for the moderator to use as a preview
		if ($ok = $image->storeImage($src,$this->use_new_upload,'_preview')) {

			$orginalfile = $this->_originalJPEG($this->upload_id);

			if (file_exists($orginalfile) && $this->largestsize && $this->largestsize > 640) {

				$this->_downsizeFile($orginalfile,$this->largestsize);

				//store the new original file
				$ok =$image->storeImage($orginalfile,$this->use_new_upload,'_pending');
			}
		}

		if ($ok) {
			$sql = sprintf("insert into gridimage_pending (gridimage_id,upload_id,user_id,suggested,type) ".
				"values (%s,%s,%s,now(),'original')",
				$this->db->Quote($image->gridimage_id),
				$this->db->Quote($this->upload_id),
				$this->db->Quote($USER->user_id));

			$this->db->Query($sql);

			$this->cleanUp();

			split_timer('upload','addOriginal',"{$image->gridimage_id}"); //logs the wall time

		} else {
			return "unable to store file";
		}
	}

	/**
	* clean up filesystem after completed or abandoned upload
	*/
	function cleanUp()
	{
		@rename($from=$this->_pendingJPEG($this->upload_id), str_replace('upload_tmp_dir','upload_tmp_dir_old',$from));
		@rename($from=$this->_pendingEXIF($this->upload_id), str_replace('upload_tmp_dir','upload_tmp_dir_old',$from));
		@rename($from=$this->_originalJPEG($this->upload_id), str_replace('upload_tmp_dir','upload_tmp_dir_old',$from));

		//@unlink($this->_pendingJPEG($this->upload_id));
		//@unlink($this->_pendingEXIF($this->upload_id));
		//@unlink($this->_originalJPEG($this->upload_id));
	}

	function getUploadedFiles()
	{
		global $CONF,$USER;

		$data = array();
		$conv = new Conversions;

		if ($this->use_new_upload && !empty($CONF['filesystem_dsn'])) { // && strpos($this->tmppath,'/mnt/combined') === 0 ??

		        $db=NewADOConnection($CONF['filesystem_dsn']);
			$GLOBALS['ADODB_FETCH_MODE'] = ADODB_FETCH_ASSOC;

                        $a = $USER->user_id%10;
                        $b = intval($USER->user_id/10)%10;

                        $dir = "{$this->tmppath}/$a/$b/{$USER->user_id}";

			$dir = str_replace('/mnt/combined','',$dir);

			$folder_id = $db->getOne("SELECT folder_id FROM folder WHERE folder = ".$db->Quote($dir));

			if (empty($folder_id))
				return $data; //if there is no folder, there can't be any files!

			$sql = "SELECT filename,UNIX_TIMESTAMP(file_modified) AS uploaded FROM file WHERE folder_id = $folder_id
				AND filename LIKE '%newpic_u{$USER->user_id}_%.exif' AND replica_count > 0 AND size>0";

			foreach ($db->getAll($sql) as $row) {
                                if (preg_match('/^newpic_u(\d+)_(\w+).exif$/',basename($row['filename']),$m)) {
                                        if ($m[1] != $USER->user_id)
                                                continue;
                                        $row['transfer_id'] = $m[2];

                                        $data[] = $this->setRowFromEXIF('/mnt/combined'.$row['filename'],$row,$conv);
                                }
                        }

		} else {

			chdir($CONF['photo_upload_dir'].'/'.($USER->user_id%10));

			if (isset($_ENV["OS"]) && strpos($_ENV["OS"],'Windows') !== FALSE) {
				$files = glob("newpic_u{$USER->user_id}_*.exif");
			} else {
				//in theory using shell expansion should be faster than glob
				$files = explode(" ",trim(`echo newpic_u{$USER->user_id}_*.exif`,"\n"));
			}

			foreach ($files as $file) {
				if (preg_match('/^newpic_u(\d+)_(\w+).exif$/',$file,$m)) {
					if ($m[1] != $USER->user_id)
						continue;
					$row = array('transfer_id'=>$m[2],'uploaded'=>filemtime($file));

					$data[] = $this->setRowFromEXIF($file,$row,$conv);
				}
			}
		}

		return $data;
	}

	function setRowFromEXIF($file,$row,$conv) {

				if ($exif = file_get_contents($file)) {
					$exif=unserialize($exif);

					if (!empty($exif['GPS'])) {
						list($e,$n,$reference_index) = ExifToNational($exif);
						list ($row['photographer_gridref'],$len) = $conv->national_to_gridref(intval($e),intval($n),0,$reference_index);
						list ($row['grid_reference'],$len) = $conv->national_to_gridref(intval($e),intval($n),4,$reference_index);
						$row['gridsquare'] = preg_replace('/^([A-Z]+).*$/','',$row['grid_reference']);
					}

					if (!empty($exif['COMMENT']) && preg_match("/(\b|_)([B-DF-JL-OQ-TV-X]|[HNST][A-Z]|MC|OV)[ \._-]?(\d{2,5})[ \._-]?(\d{2,5})(\b|[A-Za-z_])/i",implode(' ',$exif['COMMENT']),$m)) {
						if (strlen($m[3]) == strlen($m[4]) || (strlen($m[3])+strlen($m[4]))%2==0) {
							$row['grid_reference'] = $m[2].$m[3].$m[4];
						}
					}
					//dont know yet which of these is best but they all seem to be the same on my test images
					if (($date = $exif['EXIF']['DateTimeOriginal']) ||
					    ($date = $exif['EXIF']['DateTimeDigitized']) ||
					    ($date = $exif['IFD0']['DateTime']) )
					{
						//Example: ["DateTimeOriginal"]=> string(19) "2004:07:09 14:05:19"
						 list($date,$time) = explode(' ',$date);
						 $dates = explode(':',$date);
						 $row['imagetaken'] = implode('-',$dates).(($time)?' '.$time:'');
					}
				}
		return $row;
	}

}

