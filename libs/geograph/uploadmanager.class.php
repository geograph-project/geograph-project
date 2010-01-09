<?php
/**
 * $Project: GeoGraph $
 * $Id$
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
* @version $Revision$
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
	
	var $tmppath="";
	
	/**
	* Constructor
	*/
	function UploadManager()
	{
		global $CONF;
		$this->db = NewADOConnection($GLOBALS['DSN']);
		if (!$this->db) die('Database connection failed: '.mysql_error());   
		
		$this->tmppath=isset($CONF['photo_upload_dir'])?$CONF['photo_upload_dir']:'/tmp';
		 
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
		return $this->tmppath.'/newpic_u'.$USER->user_id.'_'.$id.'.jpeg';
	}

	/**
	* return full path to temporary image file
	*/
	function _originalJPEG($id)
	{
		global $USER;
		return $this->tmppath.'/newpic_u'.$USER->user_id.'_'.$id.'.original.jpeg';
	}

	/**
	* return full path to temporary file for EXIF data
	*/
	function _pendingEXIF($id)
	{
		global $USER;
		return $this->tmppath.'/newpic_u'.$USER->user_id.'_'.$id.'.exif';
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
	function setUploadId($id)
	{
		if ($this->validUploadId($id))
		{
			$this->upload_id=$id;
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
		if (($date = $exif['EXIF']['DateTimeOriginal']) ||
		    ($date = $exif['EXIF']['DateTimeDigitized']) ||
		    ($date = $exif['IFD0']['DateTime']) ) 
		{
			//Example: ["DateTimeOriginal"]=> string(19) "2004:07:09 14:05:19"
			 list($date,$time) = explode(' ',$date);
			 $dates = explode(':',$date);
			 $this->exifdate = implode('-',$dates);
		}
	}

	function processURL($url)
	{
		global $USER,$CONF;
		$ok=false;
		
		//generate a unique "upload id" - we use this to hold the image until
		//they've confirmed they want to submit
		$upload_id=md5(uniqid('upload'));
			
		$pendingfile = $this->_pendingJPEG($upload_id);
	
			function fetch_remote_file($url,$filename) {
				$data = file_get_contents($url);
				if (strlen($data) > 0) {
					file_put_contents($filename,$data);
					return true;
				}
				return false;
			}

		if (preg_match('/^http:\/\/[\w\.-]+\/[\w\.\/-]+\.jpg$/',$url) || preg_match('/^http:\/\/www\.picnik\.com\/file\/\d+$/',$url)) 
		{	
			if (fetch_remote_file($url, $pendingfile)) 
			{	
				if ($this->_isJpeg($pendingfile))
				{
					$ok = $this->_processFile($upload_id,$pendingfile);
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
								
		return $ok;
	}


	
	function processUpload($upload_file)
	{
		global $USER,$CONF;
		$ok=false;
		
		if ($this->_isJpeg($upload_file))
		{
			//generate a unique "upload id" - we use this to hold the image until
			//they've confirmed they want to submit
			$upload_id=md5(uniqid('upload'));

			$pendingfile = $this->_pendingJPEG($upload_id);
			
			if (move_uploaded_file($upload_file, $pendingfile)) 
			{
				$ok = $this->_processFile($upload_id,$pendingfile);
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
				
		return $ok;
	}
			

	function _processFile($upload_id,$pendingfile) {
		global $USER,$CONF;
		$ok = false;
		//save the exif data for the loaded image
		$exif = @exif_read_data($pendingfile,0,true); 

		if ($exif!==false)
		{
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

		list($width, $height, $type, $attr) = getimagesize($pendingfile);
		
		if ($width > $max_dimension || $height > $max_dimension) {
		
			//save a copy
			$orginalfile = $this->_originalJPEG($upload_id);
			copy($pendingfile,$orginalfile);
			$this->hasoriginal = true;
		
			//resize image to required size
			if ($ok = $this->_downsizeFile($pendingfile,$max_dimension)) {
				//remember useful stuff
				$this->upload_id=$upload_id;
				$this->original_width=$width;
				$this->original_height=$height;
			}
		} else {
			$ok = true;
			$this->upload_id=$upload_id;
			$this->upload_width=$width;
			$this->upload_height=$height;
		}
		return $ok;
	}

	function _downsizeFile($filename,$max_dimension) {
		global $USER,$CONF;
		
		if (strlen($CONF['imagemagick_path'])) {
			//try imagemagick first
			list($width, $height, $type, $attr) = getimagesize($filename);

			if ($width > $max_dimension || $height > $max_dimension) {
				
				//removed the unsharp as it makes some images worse - needs to be optional
				// best fit found so far: -unsharp 0x1+0.8+0.1 -blur 0x.1
				$cmd = sprintf ("\"%smogrify\" -resize %ldx%ld -quality 87 -strip jpg:%s", $CONF['imagemagick_path'],$max_dimension, $max_dimension, $filename);

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
			$uploadimg = @imagecreatefromjpeg ($filename); 
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
		return $ok;
	}
	
	function reReadExifFile() 
	{
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
	}
	
	/**
	* commit the upload process
	*/
	function commit()
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
		
		
		$viewpoint = new GridSquare;
		if ($this->viewpoint_gridreference) {
			$ok= $viewpoint->setByFullGridRef($this->viewpoint_gridreference,true);
		}
		
		
		//get sequence number
		
		$mkey = $this->square->gridsquare_id;
		$seq_no =& $memcache->name_get('sid',$mkey);
		
		if (empty($seq_no) && !empty($CONF['use_insertionqueue'])) {
			$seq_no = $this->db->GetOne("select max(seq_no) from gridimage_queue where gridsquare_id={$this->square->gridsquare_id}");
		} 
		if (empty($seq_no)) {
			$seq_no = $this->db->GetOne("select max(seq_no) from gridimage where gridsquare_id={$this->square->gridsquare_id}");
		}
		$seq_no=max($seq_no+1, 0);
		
		$memcache->name_set('sid',$mkey,$seq_no,false,$memcache->period_long);
		
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
		
		//copy image to correct area
		$src=$this->_pendingJPEG($this->upload_id);		
		
		$image=new GridImage;
		$image->gridimage_id = $gridimage_id;
		$image->user_id = $USER->user_id;
		
		if ($ok = $image->storeImage($src)) {
		
			$orginalfile = $this->_originalJPEG($this->upload_id);
			
			if (file_exists($orginalfile) && $this->largestsize && $this->largestsize > 640) {
				
				$this->_downsizeFile($orginalfile,$this->largestsize);
				
				$ok =$image->storeOriginal($orginalfile);
			}
		
			$this->cleanUp();
		}
		
		
		//assign the snippets now we know the real id. 
		$gid = crc32($this->upload_id)+4294967296;
		$gid += $USER->user_id * 4294967296;
		
		$this->db->Execute($sql = "UPDATE gridimage_snippet SET gridimage_id = $gridimage_id WHERE gridimage_id = ".$gid);
		
		$this->gridimage_id = $gridimage_id;
	}


	/**
	* add a high res image
	*/
	function addOriginal($image)
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

		$src=$this->_pendingJPEG($this->upload_id);	

		if ($ok = $image->storeImage($src)) {
		
			//store the resized version - just for the moderator to use as a preview
			$ok = $image->storeImage($orginalfile,false,'_preview')

			$orginalfile = $this->_originalJPEG($this->upload_id);

			if (file_exists($orginalfile) && $this->largestsize && $this->largestsize > 640) {

				$this->_downsizeFile($orginalfile,$this->largestsize);
				
				//store the new original file
				$ok =$image->storeImage($orginalfile,false,'_pending')
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
		} else {
			return "unable to store file";
		}

	}
	
	/**
	* clean up filesystem after completed or abandoned upload
	*/
	function cleanUp()
	{
		@unlink($this->_pendingJPEG($this->upload_id));
		@unlink($this->_pendingEXIF($this->upload_id));
		@unlink($this->_originalJPEG($this->upload_id));
	}
	
}


?>