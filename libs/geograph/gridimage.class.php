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
		$this->gridref=$sq->gridref;
		
		
		if (strlen($this->title)==0)
			$this->title="Untitled photograph for {$this->gridref}";
	}
	
	/**
	* assign members from recordset containing required members
	*/
	function loadFromRecordset(&$rs)
	{
		$this->_initFromArray($rs->fields);
	}
	
	/**
	* assign members from gridimage_id
	*/
	function loadFromId($gridimage_id)
	{
		$db=&$this->_getDB();
		
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
	}
	
	function _getAntiLeechHash()
	{
		global $CONF;
		return substr(md5($this->gridimage_id.$this->user_id.$CONF['photo_hashing_secret']), 0, 8);
	}
	
	function storeImage($srcfile)
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
		return @copy($srcfile, $dest);
	}
	
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
	
	function getFull()
	{
		$fullpath=$this->_getFullpath();
		$title=htmlentities($this->title);
		
		$size=getimagesize($_SERVER['DOCUMENT_ROOT'].$fullpath);
		$html="<img alt=\"$title\" src=\"$fullpath\" {$size[3]} border=\"0\"/>";
			
		return $html;
	}
	
	function isLandscape()
	{
		$fullpath=$this->_getFullpath();
		$size=getimagesize($_SERVER['DOCUMENT_ROOT'].$fullpath);
		return $size[0]>$size[1];
		
	}
	
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
	
}
?>
