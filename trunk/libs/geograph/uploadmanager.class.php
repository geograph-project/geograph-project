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
		$this->db = NewADOConnection($GLOBALS['DSN']);
		if (!$this->db) die('Database connection failed');   
		
		$this->tmppath=isset($_ENV['TMP'])?$_ENV['TMP']:'/tmp';
		 
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
		if (($date = $exif['IFD0']['DateTime']) ||
		    ($date = $exif['EXIF']['DateTimeOriginal']) ||
		    ($date = $exif['EXIF']['DateTimeDigitized']) ) 
		{
			//Example: ["DateTimeOriginal"]=> string(19) "2004:07:09 14:05:19"
			 list($date,$time) = explode(' ',$date);
			 $dates = explode(':',$date);
			 $this->exifdate = implode('-',$dates);
		}
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
				//save the exif data for the loaded image
				$exif = @exif_read_data($pendingfile,0,true); 
				
				if ($exif!==false)
				{
					$this->trySetDateFromExif($exif);
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
				
				if (strlen($CONF['imagemagick_path'])) {
				
					list($width, $height, $type, $attr) = getimagesize($pendingfile);
				
					if ($width > $max_dimension || $height > $max_dimension) {

						//removed the unsharp as it makes some images worse - needs to be optional
						// best fit found so far: -unsharp 0x1+0.8+0.1 -blur 0x.1
						$cmd = sprintf ("\"%smogrify\" -filter Lanczos -sample %ldx%ld -quality 87 jpg:%s", $CONF['imagemagick_path'],$max_dimension, $max_dimension, $pendingfile);

						passthru ($cmd);
						
						list($width, $height, $type, $attr) = getimagesize($pendingfile);
					}
					
					//remember useful stuff
					$this->upload_id=$upload_id;
					$this->upload_width=$width;
					$this->upload_height=$height;
					$ok=true;
					
				} else {
					//generate a resized image
					$uploadimg = @imagecreatefromjpeg ($pendingfile); 
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

							require_once('geograph/image.inc.php');

							UnsharpMask($resized,100,0.5,3);

							imagedestroy($uploadimg);

							//overwrite the upload
							imagejpeg ($resized, $pendingfile, 85);
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
						$this->upload_id=$upload_id;
						$this->upload_width=$destw;
						$this->upload_height=$desth;
						$ok=true;
					}
					else
					{
						$this->error("Unable to load image - we can only accept valid JPEG images");
					}
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
			$this->error("We only accept JPEG images - your upload did not appear to be a valid JPEG file");
		}
		
		return $ok;
	}
	
	/**
	* commit the upload process
	*/
	function commit()
	{
		global $USER,$CONF;
		
		if(!$this->validUploadId($this->upload_id))
		{
			die("Must assign upload id");
		}
						
		if(!is_object($this->square))
		{
			die("Must assign square");
		}
		
		
		if ($this->viewpoint_gridreference) {
			$viewpoint = new GridSquare;
			$ok= $viewpoint->setByFullGridRef($this->viewpoint_gridreference);
			$viewpoint_eastings = $viewpoint->getNatEastings();
			$viewpoint_northings = $viewpoint->getNatNorthings();
		}
		
		
		//get sequence number
		$seq_no = $this->db->GetOne("select max(seq_no) from gridimage ".
			"where gridsquare_id={$this->square->gridsquare_id}");
		$seq_no=max($seq_no+1, 0);
		
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
		#$this->db->debug = true;
		//create record
		// nateasting/natnorthings will only have values if getNatEastings has been called (in this case because setByFullGridRef has been called IF an exact location is specifed)
		$sql=sprintf("insert into gridimage(".
			"gridsquare_id, seq_no, user_id, ftf,".
			"moderation_status,title,comment,exif,nateastings,natnorthings,imageclass,imagetaken,".
			"submitted,viewpoint_eastings,viewpoint_northings) values ".
			"(%d,%d,%d,%d,".
			"'pending',%s,%s,%s,%d,%d,%s,%s,".
			"now(),%d,%d)",
			$this->square->gridsquare_id, $seq_no,$USER->user_id, $ftf,
			$this->db->Quote($this->title), $this->db->Quote($this->comment), $this->db->Quote($exif),
			$this->square->nateastings,$this->square->natnorthings,
			$this->db->Quote($this->imageclass), $this->db->Quote($this->imagetaken),
			$viewpoint_eastings,$viewpoint_northings);
		
		$this->db->Query($sql);
		
		//increment image count
		$this->db->Query("update gridsquare set imagecount=imagecount+1 ".
			"where gridsquare_id={$this->square->gridsquare_id}");
		
		//invalidate any cached maps
		require_once('geograph/mapmosaic.class.php');
		$mosaic=new GeographMapMosaic;
		$mosaic->expirePosition($this->square->x,$this->square->y);
		
		//get the id
		$gridimage_id=$this->db->Insert_ID();
		
		//copy image to correct area
		$src=$this->_pendingJPEG($this->upload_id);
		
		$image=new GridImage;
		$image->loadFromId($gridimage_id);
		$image->storeImage($src);
		
		//update cached tables
		$image->updateCachedTables();	
		
		//update placename cached column
		$image->updatePlaceNameId();
		
		$this->cleanUp();

	}
	
	/**
	* clean up filesystem after completed or abandoned upload
	*/
	function cleanUp()
	{
		$jpeg = $this->_pendingJPEG($this->upload_id);
		$exif = $this->_pendingEXIF($this->upload_id);
		@unlink($jpeg);
		@unlink($exif);
	}
		
	
	
}


?>