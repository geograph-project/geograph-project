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
* Provides the GridImage class
*
* @package Geograph
* @author Paul Dixon <paul@elphin.com>
* @version $Revision$
*/

/**
* GridImage class
* Provides an abstraction of a grid image, providing all the
* obvious functions you'd expect
*/
class GridImage
{
	/**
	* internal db handle
	*/
	var $db;

	/**
	* image id
	*/
	var $gridimage_id;

	/**
	* image sequence number for associated square
	*/
	var $seq_no;
		
	/**
	* user id of submitter
	*/
	var $user_id;
	
	/**
	* first to find?
	*/
	var $ftf;

	/**
	* moderation status - 'pending', 'accepted', 'rejected'
	*/
	var $moderation_status;

	/**
	* image title
	*/
	var $title;

	/**
	* image comment
	*/
	var $comment;

	/**
	* serialize exif data
	*/
	var $exif;

	/**
	* submission date
	*/
	var $submitted;

	/**
	* user real name
	*/
	var $realname;
	
	/**
	* user email address
	*/
	var $email;

	/**
	* user website
	*/
	var $website;
	
	    
	/**
	* constructor
	*/
	function GridImage()
	{
	
	}
	
	/**
	 * get stored db object, creating if necessary
	 * @access private
	 */
	function &_getDB()
	{
		if (!is_object($this->db))
			$this->db=NewADOConnection($GLOBALS['DSN']);
		if (!$this->db) die('Database connection failed');  
		return $this->db;
	}

	/**
	 * set stored db object
	 * @access private
	 */
	function _setDB(&$db)
	{
		$this->db=$db;
	}
	
	/**
	 * clear all member vars
	 * @access private
	 */
	function _clear()
	{
		$vars=get_object_vars($this);
		foreach($vars as $name=>$val)
		{
			if ($name!="db")
				unset($this->$name);
		}
	}
	
	/**
	* assign members from array containing required members
	*/
	function _initFromArray(&$arr)
	{
		foreach($arr as $name=>$value)
		{
			if (!is_numeric($name))
				$this->$name=$value;
													
		}
		
		$sq=new GridSquare;
		$sq->loadFromId($this->gridsquare_id);
		$this->grid_reference=$sq->grid_reference;
		
		
		if (strlen($this->title)==0)
			$this->title="Untitled photograph for {$this->gridref}";
	}
	
	/**
	* return true if instance references a valid grid image
	*/
	function isValid()
	{
		return isset($this->gridimage_id) && ($this->gridimage_id>0);
	}
	
	/**
	* assign members from recordset containing required members
	*/
	function loadFromRecordset(&$rs)
	{
		$this->_clear();
		$this->_initFromArray($rs->fields);
		return $this->isValid();
	}
	
	/**
	* assign members from gridimage_id
	*/
	function loadFromId($gridimage_id)
	{
		$db=&$this->_getDB();
		
		$this->_clear();
		if (preg_match('/^\d+$/', $gridimage_id))
		{
			$row = &$db->GetRow("select gridimage.*,user.realname,user.email,user.website ".
				"from gridimage ".
				"inner join user using(user_id) ".
				"where gridimage_id={$gridimage_id}");
			if (is_array($row))
			{
				$this->_initFromArray($row);
			}
		}
		
		return $this->isValid();
	}
	
	/**
	* calculate a hash to prevent easy downloading of every image in sequence
	*/
	function _getAntiLeechHash()
	{
		global $CONF;
		return substr(md5($this->gridimage_id.$this->user_id.$CONF['photo_hashing_secret']), 0, 8);
	}
	
	/**
	* given a temporary file, transfer to final destination for the image
	*/
	function storeImage($srcfile, $movefile=false)
	{
		$ab=sprintf("%02d", floor($this->gridimage_id/10000));
		$cd=sprintf("%02d", floor(($this->gridimage_id%10000)/100));
		$abcdef=sprintf("%06d", $this->gridimage_id);
		$hash=$this->_getAntiLeechHash();

		$base=$_SERVER['DOCUMENT_ROOT'].'/photos';
		if (!is_dir("$base/$ab"))
			mkdir("$base/$ab");
		if (!is_dir("$base/$ab/$cd"))
			mkdir("$base/$ab/$cd");

		$dest="$base/$ab/$cd/{$abcdef}_{$hash}.jpg";
		if ($movefile)
			return @rename($srcfile, $dest);
		else
			return @copy($srcfile, $dest);
	}
	
	/**
	* calculate the path to the full size photo image
	*/
	function _getFullpath()
	{
		$ab=sprintf("%02d", floor($this->gridimage_id/10000));
		$cd=sprintf("%02d", floor(($this->gridimage_id%10000)/100));
		$abcdef=sprintf("%06d", $this->gridimage_id);
		$hash=$this->_getAntiLeechHash();
		$fullpath="/photos/$ab/$cd/{$abcdef}_{$hash}.jpg";
		if (!file_exists($_SERVER['DOCUMENT_ROOT'].$fullpath))
		{
					$fullpath="/photos/error.jpg";
		}
		return $fullpath;
	}
	
	/**
	* returns HTML img tag to display this image at full size
	*/
	function getFull()
	{
		$fullpath=$this->_getFullpath();
		$title=htmlentities($this->title);
		
		$size=getimagesize($_SERVER['DOCUMENT_ROOT'].$fullpath);
		$html="<img alt=\"$title\" src=\"$fullpath\" {$size[3]} border=\"0\"/>";
			
		return $html;
	}
	
	/**
	* returns true if picture is wider than it is tall
	*/
	function isLandscape()
	{
		$fullpath=$this->_getFullpath();
		$size=getimagesize($_SERVER['DOCUMENT_ROOT'].$fullpath);
		return $size[0]>$size[1];
		
	}
	
	/**
	* returns HTML img tag to display a square thumbnail that would fit the given dimensions
	* If the required thumbnail doesn't exist, it is created. This method is really
	* handy helper for Smarty templates, for instance, given an instance of this
	* class, you can use this {$image->getSquareThumbnail(100,100)} to show a thumbnail
	*/
	function getSquareThumbnail($maxw, $maxh)
	{
		
		//establish whether we have a cached thumbnail
		$ab=sprintf("%02d", floor($this->gridimage_id/10000));
		$cd=sprintf("%02d", floor(($this->gridimage_id%10000)/100));
		$abcdef=sprintf("%06d", $this->gridimage_id);
		$hash=$this->_getAntiLeechHash();

		$base=$_SERVER['DOCUMENT_ROOT'].'/photos';
		$thumbpath="/photos/$ab/$cd/{$abcdef}_{$hash}_{$maxw}XX{$maxh}.jpg"; ##two XX's as windows isnt case sensitive!
		if (!file_exists($_SERVER['DOCUMENT_ROOT'].$thumbpath))
		{
			$fullpath="/photos/$ab/$cd/{$abcdef}_{$hash}.jpg";
			if (file_exists($_SERVER['DOCUMENT_ROOT'].$fullpath))
			{
				//generate resized image
				$fullimg = @imagecreatefromjpeg($_SERVER['DOCUMENT_ROOT'].$fullpath); 
				if ($fullimg)
				{
					$srcw=imagesx($fullimg);
					$srch=imagesy($fullimg);

					if (($srcw>$maxw) || ($srch>$maxh))
					{
						//figure out size of image we'll keep
						if ($srcw>$srch)
						{
							//landscape
							
							$srcx = round(($srcw - $srch)/2);
							$srcy = 0;
							
							$srcw = $srch;
						}
						else
						{
							//portrait
							
							$srcx = 0;
							$srcy = round(($srch - $srcw)/2);
							
							$srch = $srcw;
						}


						$resized = imagecreatetruecolor($maxw, $maxh);
						imagecopyresampled($resized, $fullimg, 0, 0, $srcx, $srcy, 
									$maxw,$maxh, $srcw, $srch);

						imagedestroy($fullimg);

						//save the thumbnail
						imagejpeg ($resized, $_SERVER['DOCUMENT_ROOT'].$thumbpath);
						imagedestroy($resized);
					}
					else
					{
						//requested thumb is larger than original - stick with original
						copy($_SERVER['DOCUMENT_ROOT'].$fullpath, $_SERVER['DOCUMENT_ROOT'].$thumbpath);
					}
				}
				else
				{
					//couldn't load full jpeg
					$thumbpath="/photos/error.jpg";
				}
			}
			else
			{
				//no original image! - return link to error image
				$thumbpath="/photos/error.jpg";
		
			}
		}
		
		
		if ($thumbpath=='/photos/error.jpg')
		{
			$html="<img src=\"$thumbpath\" width=\"$maxw\" height=\"$maxh\" border=\"0\"/>";
		}
		else
		{
			$title=htmlentities($this->title);
			
			$size=getimagesize($_SERVER['DOCUMENT_ROOT'].$thumbpath);
			$html="<img alt=\"$title\" src=\"$thumbpath\" {$size[3]} border=\"0\"/>";
		}
		
		
		
		return $html;
	}


	/**
	* returns HTML img tag to display a thumbnail that would fit the given dimensions
	* If the required thumbnail doesn't exist, it is created. This method is really
	* handy helper for Smarty templates, for instance, given an instance of this
	* class, you can use this {$image->getThumbnail(213,160)} to show a thumbnail
	*/
	function getThumbnail($maxw, $maxh)
	{
		
		//establish whether we have a cached thumbnail
		$ab=sprintf("%02d", floor($this->gridimage_id/10000));
		$cd=sprintf("%02d", floor(($this->gridimage_id%10000)/100));
		$abcdef=sprintf("%06d", $this->gridimage_id);
		$hash=$this->_getAntiLeechHash();

		$base=$_SERVER['DOCUMENT_ROOT'].'/photos';
		$thumbpath="/photos/$ab/$cd/{$abcdef}_{$hash}_{$maxw}x{$maxh}.jpg";
		if (!file_exists($_SERVER['DOCUMENT_ROOT'].$thumbpath))
		{
			$fullpath="/photos/$ab/$cd/{$abcdef}_{$hash}.jpg";
			if (file_exists($_SERVER['DOCUMENT_ROOT'].$fullpath))
			{
				//generate resized image
				$fullimg = @imagecreatefromjpeg($_SERVER['DOCUMENT_ROOT'].$fullpath); 
				if ($fullimg)
				{
					$srcw=imagesx($fullimg);
					$srch=imagesy($fullimg);

					if (($srcw>$maxw) || ($srch>$maxh))
					{
						//figure out size of image we'll keep
						if ($srcw>$srch)
						{
							//landscape
							$destw=$maxw;
							$desth=round(($destw * $srch)/$srcw);
						}
						else
						{
							//portrait
							$desth=$maxh;
							$destw=round(($desth * $srcw)/$srch);
						}


						$resized = imagecreatetruecolor($destw, $desth);
						imagecopyresampled($resized, $fullimg, 0, 0, 0, 0, 
									$destw,$desth, $srcw, $srch);

						imagedestroy($fullimg);

						//save the thumbnail
						imagejpeg ($resized, $_SERVER['DOCUMENT_ROOT'].$thumbpath);
						imagedestroy($resized);
					}
					else
					{
						//requested thumb is larger than original - stick with original
						copy($_SERVER['DOCUMENT_ROOT'].$fullpath, $_SERVER['DOCUMENT_ROOT'].$thumbpath);
					}
				}
				else
				{
					//couldn't load full jpeg
					$thumbpath="/photos/error.jpg";
				}
			}
			else
			{
				//no original image! - return link to error image
				$thumbpath="/photos/error.jpg";
		
			}
		}
		
		
		if ($thumbpath=='/photos/error.jpg')
		{
			$html="<img src=\"$thumbpath\" width=\"$maxw\" height=\"$maxh\" />";
		}
		else
		{
			$title=htmlentities($this->title);
			
			$size=getimagesize($_SERVER['DOCUMENT_ROOT'].$thumbpath);
			$html="<img alt=\"$title\" src=\"$thumbpath\" {$size[3]} />";
		}
		
		
		
		return $html;
	}
	
	/**
	* Sets the moderation status for the image, intelligently updating user stats appropriately
	* status must either 'accepted' or 'rejected'
	*/
	function setModerationStatus($status)
	{
		$valid_status=array('accepted', 'rejected');
		
		//is this a valid instance with a definite change of status?
		if ($this->isValid() && 
		    ($status!=$this->moderation_status) &&
		    in_array($status, $valid_status)
		   )
		{
			if ($status=='rejected')
			{
				//lower image count for this square...
				$this->db->Query("update gridsquare set imagecount=imagecount-1 where gridsquare_id={$this->gridsquare_id}");
						
				//we always clear the ftf flag on rejected images...
				//we also give rejected images a negative sequence number
				$this->ftf=0;
				
				$min = $this->db->GetOne("select min(seq_no) from gridimage where gridsquare_id={$this->gridsquare_id} and moderation_status='rejected'");
				if (!isset($min))
					$min=0;
					
				$this->seq_no=$min-1;
			}
			
			//if we're going from rejected to accepted, we must undo the above
			if (($status=='accepted') && ($this->moderation_status=='rejected'))
			{
				//lower image count for this square...
				$this->db->Query("update gridsquare set imagecount=imagecount+1 where gridsquare_id={$this->gridsquare_id}");
				
				//figure out a sequence number and ftf status
				$this->seq_no = $this->db->GetOne("select count(*) from gridimage where gridsquare_id={$this->gridsquare_id} and moderation_status<>'rejected'");
				$this->ftf=($this->seq_no==0)?1:0;
		
			}
			
			$this->moderation_status=$status;
			
			//update image status and ftf flag
			$sql="update gridimage set ".
				"moderation_status='$status',".
				"ftf={$this->ftf},".
				"seq_no={$this->seq_no} ".
				"where gridimage_id={$this->gridimage_id}";
				
			
			$db=&$this->_getDB();
			$db->query($sql);
			
			
			
		}
	}
	
}
?>
