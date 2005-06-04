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
	* the 'host' grid square
	*/
	var $grid_square;

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
	* moderation status - 'pending', 'accepted', 'rejected' or 'geograph'
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
	* Get an array of current image classes
	*/
	function& getImageClasses()
	{
		$db=&$this->_getDB();
		
		$arr = $db->CacheGetAssoc(24*3600,"select imageclass,imageclass from gridimage ".
			"where length(imageclass)>0 and moderation_status in ('accepted','geograph') ".
			"group by imageclass");
		
		//temp 'defaults' until the group by will pick them up!
		foreach(array('Urban Landscape',
		'Urban Landmark',
		'Open Countryside',
		'Farmland',
		'Woodland',
		'Water Bodies - Lakes and Rivers',
		'Mountains',
		'Marshland',
		'Coastline/Beaches') as $val) {
			if(!$arr[$val]) 
				$arr[$val]=$val;
		}
		natcasesort($arr);
		
		return $arr;
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
		
		$this->grid_square=new GridSquare;
		$this->grid_square->loadFromId($this->gridsquare_id);
		$this->grid_reference=$this->grid_square->grid_reference;
		if ($this->nateastings) {
			$this->grid_square->nateastings=$this->nateastings;
			$this->grid_square->natnorthings=$this->natnorthings;
		}
		
		if (strlen($this->title)==0)
			$this->title="Untitled photograph for {$this->grid_reference}";
	}
	
	/**
	* advanced method which sets up a gridimage without a gridsquare instance
	* only use this method if you know what you are doing
	*/
	function fastInit(&$arr)
	{
		$this->grid_square=null;
		$this->grid_reference='';
		foreach($arr as $name=>$value)
		{
			if (!is_numeric($name))
				$this->$name=$value;
		}
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
	* trim members to make object as small as possible
	*/
	function compact()
	{
		unset($this->db);
		unset($this->exif);
		if (is_object($this->grid_square))
		{
			unset($this->grid_square->db);
		}
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

						
						require_once('geograph/image.inc.php');
						UnsharpMask($resized,100,0.5,3);
						
							
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
	* returns a GD image instance for a square thumbnail of the image
	*/
	function getSquareThumb($size)
	{
		$ab=sprintf("%02d", floor($this->gridimage_id/10000));
		$cd=sprintf("%02d", floor(($this->gridimage_id%10000)/100));
		$abcdef=sprintf("%06d", $this->gridimage_id);
		$hash=$this->_getAntiLeechHash();
		$img=null;
		
		
		$base=&$_SERVER['DOCUMENT_ROOT'];
		$thumbpath="/photos/$ab/$cd/{$abcdef}_{$hash}_{$size}x{$size}.gd";
		if (!file_exists($base.$thumbpath))
		{
		
			$fullpath="/photos/$ab/$cd/{$abcdef}_{$hash}.jpg";
		
			if (file_exists($base.$fullpath))
			{
				
		
				//generate resized image
				$fullimg = @imagecreatefromjpeg($base.$fullpath); 
				if ($fullimg)
				{
					$srcw=imagesx($fullimg);
					$srch=imagesy($fullimg);
					
					//crop percentage is how much of the
					//image to keep in the thumbnail
					$crop=0.75;
					
					//figure out size of image we'll keep
					if ($srcw>$srch)
					{
						//landscape
						$s=$srch*$crop;
						
						
					}
					else
					{
						//portrait
						$s=$srcw*$crop;
					}

					$srcx = round(($srcw-$s)/2);
					$srcy = round(($srch-$s)/2);
					$srcw = $s;
					$srch=$s;
					
					$img = imagecreatetruecolor($size, $size);
					imagecopyresampled($img, $fullimg, 0, 0, $srcx, $srcy, 
								$size,$size, $srcw, $srch);

					UnsharpMask($img,200,0.5,3);

					imagedestroy($fullimg);

					//save the thumbnail
					imagegd($img, $base.$thumbpath);
						
					
				}
				else
				{
					//couldn't load full jpeg
					$img=null;
				}
			}
			else
			{
				//no original image!
				$img=null;
		
			}
			
		}
		else
		{
			$img=imagecreatefromgd($base.$thumbpath);
		}
		return $img;
	}
	

	/**
	* returns HTML img tag to display a thumbnail that would fit the given dimensions
	* If the required thumbnail doesn't exist, it is created. This method is really
	* handy helper for Smarty templates, for instance, given an instance of this
	* class, you can use this {$image->getThumbnail(213,160)} to show a thumbnail
	*/
	function getThumbnail($maxw, $maxh)
	{
		global $CONF;
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
				if (strlen($CONF['imagemagick_path'])) {
								
					list($width, $height, $type, $attr) = getimagesize($_SERVER['DOCUMENT_ROOT'].$fullpath);

					if (($width>$maxw) || ($height>$maxh)) {
						//figure out size of image we'll keep
						if ($width>$height)
						{
							//landscape
							$destw=$maxw;
							$desth=round(($destw * $height)/$width);
						}
						else
						{
							//portrait
							$desth=$maxh;
							$destw=round(($desth * $width)/$height);
						}
					
					
						$cmd = sprintf ("\"%sconvert\" -thumbnail %ldx%ld -unsharp 0x1+1.4+0.1 -raise 2x2 -quality 87 jpg:%s jpg:%s", 
							$CONF['imagemagick_path'],
							$maxw, $maxh, 
							$_SERVER['DOCUMENT_ROOT'].$fullpath,
							$_SERVER['DOCUMENT_ROOT'].$thumbpath);
						passthru ($cmd);

					} else {
						//requested thumb is larger than original - stick with original
						copy($_SERVER['DOCUMENT_ROOT'].$fullpath, $_SERVER['DOCUMENT_ROOT'].$thumbpath);
					}											
				} else {
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
	

							require_once('geograph/image.inc.php');
							UnsharpMask($resized,100,0.5,3);

							imagedestroy($fullimg);

							//save the thumbnail
							imagejpeg ($resized, $_SERVER['DOCUMENT_ROOT'].$thumbpath,85);
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
	* returns a textual describing the action taken
	*
	* This is all quite hairy stuff, as we maintain need to maintain a number of 
	* counts and status fields in the database
	*/
	function setModerationStatus($status, $moderator_id)
	{
		$valid_status=array('accepted', 'rejected', 'geograph');
		
		if (!$this->isValid())
			return "Invalid image";
		
		if ($status==$this->moderation_status)
			return "No change, still {$this->moderation_status}";
		
		if (!in_array($status, $valid_status))
			return "Bad status $status";
		
		//to get this far, the image is valid, the status
		//is valid, and it is a definite change of status
		$db=&$this->_getDB();
		
		
		//we want to detect changes in ftf status...a pending image is always ftf 0
		$original_ftf=$this->ftf;
		
		//you only get ftf if new status is 'geograph' and there are no other 
		//geograph images
		$geographs= $db->GetOne("select count(*) from gridimage ".
					"where gridsquare_id={$this->gridsquare_id} and moderation_status='geograph'");
		$this->ftf=0;
		if (($status=='geograph') && ($geographs==0))
		{
			$this->ftf=1;
			$geographs=1;
		}
					
			
			
		//ok, update the image
		$this->moderation_status=$status;

		//update image status and ftf flag
		$sql="update gridimage set ".
			"moderation_status='$status',".
			"moderator_id='$moderator_id',".
			"moderated=now(),".
			"ftf={$this->ftf},".
			"seq_no={$this->seq_no} ".
			"where gridimage_id={$this->gridimage_id}";
		$db->query($sql);
		
		//if we've just cleared the ftf flag, we should check to see
		//the square contains other geographs, in which case, we award ftf to the
		//first one submitted
		if ($original_ftf && !$this->ftf)
		{
			$next_geograph= $db->GetOne("select gridimage_id from gridimage ".
				"where gridsquare_id={$this->gridsquare_id} and moderation_status='geograph' ".
				"order by submitted");
			if ($next_geograph)
			{
				$db->Query("update gridimage set ftf=1 where gridimage_id={$next_geograph}");
			}

		}
			
		//finally, we update status information for the gridsquare
		$this->grid_square->updateCounts();
		
		return "Status is now $status";	
			
		
	}

	/**
	* Reassigns the reference of this image - callers of this are responsible for ensuring
	* only authorized calls can be made, but the method performs full error checking of 
	* the supplied reference
	*/
	function reassignGridsquare($grid_reference, &$error)
	{
		$ok=false;
		
		//is the reference valid?
		//old one is in $this->grid_square
		$newsq=new GridSquare;
		if ($newsq->setGridRef($grid_reference))
		{
			$db=&$this->_getDB();
			
			//ensure this is a real change
			if ($newsq->gridsquare_id == $this->gridsquare_id)
				return true;
			
			//get sequence number of target square - for a rejected image
			//we use a negative sequence number
			if ($this->moderation_status!='rejected')
			{
				$seq_no = $this->db->GetOne("select max(seq_no) from gridimage ".
					"where gridsquare_id={$newsq->gridsquare_id}");
				$seq_no=max($seq_no+1, 0);
			}
			else
			{
				$seq_no = $this->db->GetOne("select min(seq_no) from gridimage ".
					"where gridsquare_id={$newsq->gridsquare_id}");
				$seq_no=min($seq_no-1, -1);
			}
			
			//was this image ftf? 
			if ($this->ftf)
			{
				//reset the ftf flag
				$this->ftf=0;
				
				//need to assign ftf to another image in the square if possible
				$next_geograph= $db->GetOne("select gridimage_id from gridimage ".
					"where gridsquare_id={$this->gridsquare_id} and moderation_status='geograph' ".
					"and gridimage_id<>{$this->gridimage_id} ".
					"order by submitted");
				if ($next_geograph)
				{
					$db->Query("update gridimage set ftf=1 where gridimage_id={$next_geograph}");
				}
			
			}
			
			//does the image get ftf in the target square?
			if ($this->moderation_status=='geograph')
			{
				$geographs= $db->GetOne("select count(*) from gridimage ".
					"where gridsquare_id={$newsq->gridsquare_id} and moderation_status='geograph'");
				if ($geographs==0)
					$this->ftf=1;
			}
			
			$east=$newsq->getNatEastings();
			$north=$newsq->getNatNorthings();
			
			//reassign image
			$db->Execute("update gridimage set gridsquare_id='{$newsq->gridsquare_id}',".
				"seq_no=$seq_no,ftf=$this->ftf, ".
				"nateastings='$east',natnorthings='$north' ".
				"where gridimage_id='$this->gridimage_id'");

			//update cached data for old square and new square
			$this->grid_square->updateCounts();
			$newsq->updateCounts();
			
			
			//invalidate any cached maps
			require_once('geograph/mapmosaic.class.php');
			$mosaic=new GeographMapMosaic;
			
			$mosaic->expirePosition($this->grid_square->x,$this->grid_square->y);
			
			$mosaic->expirePosition($newsq->x,$newsq->y);
					
			
			$ok=true;
		}
		else
		{
			//bad grid reference
			$ok=false;
			$error=$newsq->errormsg;
		}
		return $ok;			
	}
	
	
	/**
	* gets a human readable version of the potentially part date
	*/
	function getFormattedTakenDate()
	{
		list($y,$m,$d)=explode('-', $this->imagetaken);
		$date="";
		if ($d>0)
		{
			if ($y>1970)
			{
				//we can use strftime
				$t=strtotime($this->imagetaken);
				$date=strftime("%A, %d %B, %Y", $t);   //%e doesnt seem to work here? changed to %d ????
			}
			else
			{
				//oh my!
				$t=strtotime("2000-$m-%d");
				$date=strftime("%e %B", $t)." $y";
			}
			
		}
		elseif ($m>0)
		{
			//well, it saves having an array of months...
			$t=strtotime("2000-$m-01");
			if ($y > 0) {
				$date=strftime("%B", $t)." $y";
			} else {
				$date=strftime("%B", $t);
			}
		}
		elseif ($y>0)
		{
			$date=$y;
		}
		
		
		
		
		return $date;
	}
	
	/**
	* Saves selected members to the gridimage record
	*/
	function commitChanges()
	{
		$db=&$this->_getDB();
		
		$sql="update gridimage set title=".$db->Quote($this->title).
			", comment=".$db->Quote($this->comment).
			", imageclass=".$db->Quote($this->imageclass).
			", imagetaken=".$db->Quote($this->imagetaken).
			"where gridimage_id = '{$this->gridimage_id}'";
		$db->Execute($sql);
			
		
	}
	
}
?>
