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
* Provides the GeographMap class
*
* @package Geograph
* @author Paul Dixon <paul@elphin.com>
* @version $Revision$
*/

/**
* Needs the Token class, so we pull that in here
*/
require_once('geograph/token.class.php');
require_once('geograph/gridimage.class.php');


/**
* Geograph Map class
*
* Provides an abstraction of map for browsing the database
*
* @package Geograph
*/
class GeographMap
{
	/**
	* db handle
	*/
	var $db=null;

	/**
	* x origin of map in internal coordinates
	*/
	var $map_x=0;
	
	/**
	* y origin of map in internal coordinates
	*/
	var $map_y=0;
	
	/**
	* height of map in pixels
	*/
	var $image_w=0;
	
	/**
	* width of map in pixels
	*/
	var $image_h=0;
	
	/**
	* scale in pixels per kilometre
	*/
	var $pixels_per_km=0;
	
	/**
	* scale in pixels per kilometre
	*/
	var $caching=true;
	
	/**
	* Constructor
	*/
	function GeographMap()
	{
		$this->setOrigin(0,0);
		$this->setImageSize(400,400);
		$this->setScale(0.3);
	}


	/**
	* Disable caching - turn off for debugging
	* Cache files are still written, just never used
	* @access public
	*/
	function enableCaching($enable)
	{
		$this->caching=$enable;
	}
	
	/**
	* Set origin of map in internal coordinates, returns true if valid
	* @access public
	*/
	function setOrigin($x,$y)
	{
		$this->map_x=intval($x);
		$this->map_y=intval($y);
		return true;
	}

	/**
	* Set size of map image
	* @access public
	*/
	function setImageSize($w,$h)
	{
		$this->image_w=intval($w);
		$this->image_h=intval($h);
		return true;
	}

	/**
	* Set desired scale in pixels per km
	* @access public
	*/
	function setScale($pixels_per_km)
	{
		$this->pixels_per_km=floatval($pixels_per_km);
		return true;
	}

	/**
	* Return an opaque, url-safe token representing this mosaic
	* @access public
	*/
	function getToken()
	{
		$token=new Token;
		$token->setValue("x", $this->map_x);
		$token->setValue("y", $this->map_y);
		$token->setValue("w",  $this->image_w);
		$token->setValue("h",  $this->image_h);
		$token->setValue("s",  $this->pixels_per_km);
		return $token->getToken();
	}

	/**
	* Initialise class from a token
	* @access public
	*/
	function setToken($tokenstr)
	{
		$ok=false;
		
		$token=new Token;
		if ($token->parse($tokenstr))
		{
			$ok=$token->hasValue("x") &&
				$token->hasValue("y") &&
				$token->hasValue("w") &&
				$token->hasValue("h") &&
				$token->hasValue("s");
			if ($ok)
			{
				$this->setOrigin($token->getValue("x"), $token->getValue("y"));
				$this->setImageSize($token->getValue("w"), $token->getValue("h"));
				$this->setScale($token->getValue("s"));
			}
		}
		else
		{
		
		}
		
		return $ok;
	}


	/**
	* get grid reference for pixel position on image
	* @access public
	*/
	function getGridRef($x, $y)
	{
	
	}

	/**
	* calc filename to image, whether it exists or not
	* filename is from document root and includes leading slash
	* @access public
	*/
	function getImageFilename()
	{
		$root=&$_SERVER['DOCUMENT_ROOT'];
		
		$dir="/maps/detail/";
		if (!is_dir($root.$dir))
			mkdir($root.$dir);
		
		$dir.="{$this->map_x}/";
		if (!is_dir($root.$dir))
			mkdir($root.$dir);
		
		$dir.="{$this->map_y}/";
		if (!is_dir($root.$dir))
			mkdir($root.$dir);
		
		$file="detail_{$this->map_x}_{$this->map_y}_{$this->image_w}_{$this->image_h}_{$this->pixels_per_km}.png";
		
		
		return $dir.$file;
	}

	/**
	* calc filename to an image which can form the base of the map
	* @access public
	*/
	function getBaseMapFilename()
	{
		$root=&$_SERVER['DOCUMENT_ROOT'];
		
		$dir="/maps/base/";
		if (!is_dir($root.$dir))
			mkdir($root.$dir);
		
		$dir.="{$this->map_x}/";
		if (!is_dir($root.$dir))
			mkdir($root.$dir);
		
		$dir.="{$this->map_y}/";
		if (!is_dir($root.$dir))
			mkdir($root.$dir);
		
		$file="base_{$this->map_x}_{$this->map_y}_{$this->image_w}_{$this->image_h}_{$this->pixels_per_km}.gd";
		
		
		return $dir.$file;
	}

	/**
	* if a cached image is available, this could return a direct url
	* otherwise it can return a url which will generate the required
	* image 
	* @access public
	*/
	function getImageUrl()
	{
		
		$file=$this->getImageFilename();
		$full=$_SERVER['DOCUMENT_ROOT'].$file;
		
		//
		/*
		if ($this->caching && @file_exists($full))
		{
			//we can just return file!
		}
		else
		{
			$token=$this->getToken();
			$file="/mapbrowse.php?map=$token";
		}
		*/
		
		//always given dynamic url, that way cached HTML can 
		//always get an image
		$token=$this->getToken();
		$file="/mapbrowse.php?map=$token";
		
		return $file;
		
	}
	
	/**
	* returns an image with appropriate headers
	* @access public
	*/
	function returnImage()
	{
		$file=$this->getImageFilename();
		$full=$_SERVER['DOCUMENT_ROOT'].$file;
		if (!$this->caching || !@file_exists($full))
		{
			$this->_renderImage();
		}
		
		if (!@file_exists($full))
			$full=$_SERVER['DOCUMENT_ROOT']."/maps/errortile.png";
			
		$type="image/png";
		if (strpos($full, ".jpg")>0)
			$type="image/jpeg";
			
		//Last-Modified: Sun, 20 Mar 2005 18:19:58 GMT
		$t=filemtime($full);
		$lastmod=strftime("%a, %d %b %Y %H:%M:%S GMT", $t);
		
		$t=time()+3600;
		$expires=strftime("%a, %d %b %Y %H:%M:%S GMT", $t);
		
		
		$size=filesize($full);
		header("Content-Type: $type");
		header("Content-Size: $size");
		header("Last-Modified: $lastmod");
		header("Expires: $expires");
		//header("Cache-Control: public");
		//header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1 
		//header("Cache-Control: post-check=0, pre-check=0", false); 
		//header("Pragma: no-cache");         
		
		readfile($full);
		
		
	}
	
	/**
	* create basemap, save as gd image and return the image resource
	* @access private
	*/
	function& _createBasemap($file)
	{
		//figure out what we're mapping in internal coords
		$left=$this->map_x;
		$bottom=$this->map_y;
		$right=$left + floor($this->image_w/$this->pixels_per_km)-1;
		$top=$bottom + floor($this->image_h/$this->pixels_per_km)-1;
		
		//if the scale <0 we generate the image at 1pix/km and then rescale it
		if ($this->pixels_per_km < 1)
		{
			$imgw=$right-$left;
			$imgh=$top-$bottom;
			$pixels_per_km=1;
		}
		else
		{
			$imgw=$this->image_w;
			$imgh=$this->image_h;
			$pixels_per_km=$this->pixels_per_km;
		}
		
		
		$img=imagecreatetruecolor($imgw,$imgh);
		
		//fill in with sea
		$blue=imagecolorallocate ($img, 101,117,255);
		imagefill($img,0,0,$blue);
		
		$rmin=117;
		$rmax=117;
		$gmin=101;
		$gmax=255;
		$bmin=255;
		$bmax=101;
		
		//set greens to use for percentages
		$land=array();
		for ($p=0; $p<=100; $p++)
		{
			$scale=$p/100;
			
			$r=round($rmin + ($rmax-$rmin)*$scale);
			$g=round($gmin + ($gmax-$gmin)*$scale);
			$b=round($bmin + ($bmax-$bmin)*$scale);
			
			$land[$p]=imagecolorallocate($img, $r,$g,$b);

		}
		
		//paint the land
		$db=&$this->_getDB();
			
		//now plot all squares in the desired area
		$sql="select x,y,percent_land,reference_index from gridsquare where ".
			"(x between $left and $right) and ".
			"(y between $bottom and $top)";

		$recordSet = &$db->Execute($sql);
		while (!$recordSet->EOF) 
		{
			$gridx=$recordSet->fields[0];
			$gridy=$recordSet->fields[1];

			$imgx1=($gridx-$left) * $pixels_per_km;
			//$imgy1=(($gridy-$bottom)* $pixels_per_km);
			$imgy1=($imgh-($gridy-$bottom+1)* $pixels_per_km);

			if ($pixels_per_km==1)
			{
				imagesetpixel($img, $imgx1, $imgy1, $land[$recordSet->fields[2]]);
			}
			else
			{
				$imgx2=$imgx1 + $pixels_per_km;
				$imgy2=$imgy1 + $pixels_per_km;
				imagefilledrectangle ($img, $imgx1, $imgy1, $imgx2, $imgy2, $land[$recordSet->fields[2]]);
			}
			
			$recordSet->MoveNext();
		}
		$recordSet->Close(); 
		
		//resample?
		if ($imgw!=$this->image_w)
		{
			//resample image, save it and return
			$resized = imagecreatetruecolor($this->image_w,$this->image_h);
			imagecopyresampled($resized, $img, 0, 0, 0, 0, 
					$this->image_w,$this->image_h, $imgw, $imgh);
			imagegd($resized, $file);
			
			imagedestroy($img);
			
			return $resized;
		}
		else
		{
			//image is correct size, save it and return
			imagegd($img, $file);
			return $img;
			
		}
	}
	
	/**
	* render the image to cached file if not already available
	* @access private
	*/
	function _renderImage()
	{
		$root=&$_SERVER['DOCUMENT_ROOT'];
		
		//first of all, generate or pull in a cached based map
		$basemap=$this->getBaseMapFilename();
		if ($this->caching && @file_exists($root.$basemap))
		{
			//load it up!
			$img=imagecreatefromgd($root.$basemap);

		}
		else
		{
			//we need to generate a basemap
			$img=&$this->_createBasemap($root.$basemap);
		}
		
		$colMarker=imagecolorallocate($img, 255,0,0);
		$colBorder=imagecolorallocate($img, 255,255,255);
		
		//figure out what we're mapping in internal coords
		$db=&$this->_getDB();
		
		$dbImg=NewADOConnection($GLOBALS['DSN']);
		

		$left=$this->map_x;
		$bottom=$this->map_y;
		$right=$left + floor($this->image_w/$this->pixels_per_km)-1;
		$top=$bottom + floor($this->image_h/$this->pixels_per_km)-1;

		//size of a marker in pixels
		$markerpixels=5;
		
		//size of marker in km
		$markerkm=ceil($markerpixels/$this->pixels_per_km);
		
		//we scan for images a little over the edges so that if
		//an image lies on a mosaic edge, we still plot the point
		//on both mosaics
		$overscan=$markerkm;
		$scanleft=$left-$overscan;
		$scanright=$right+$overscan;
		$scanbottom=$bottom-$overscan;
		$scantop=$top+$overscan;
		
		$sql="select x,y,gridsquare_id from gridsquare where ".
			"(x between $scanleft and $scanright) and ".
			"(y between $scanbottom and $scantop) ".
			"and imagecount>0";

		$recordSet = &$db->Execute($sql);
		while (!$recordSet->EOF) 
		{
			$gridx=$recordSet->fields[0];
			$gridy=$recordSet->fields[1];

			$imgx1=($gridx-$left) * $this->pixels_per_km;
			$imgy1=($this->image_h-($gridy-$bottom+1)* $this->pixels_per_km);

			$imgx1=round($imgx1);
			$imgy1=round($imgy1);

			if ($this->pixels_per_km<=1)
			{
				//imagesetpixel($img, $imgx1, $imgy1, $colMarker);
				
				//nice large marker
				imagefilledrectangle ($img, $imgx1-2, $imgy1-1, $imgx1+2, $imgy1+1, $colMarker);
				imagefilledrectangle ($img, $imgx1-1, $imgy1-2, $imgx1+1, $imgy1+2, $colMarker);
			}
			else
			{
				$imgx2=$imgx1 + $this->pixels_per_km;
				$imgy2=$imgy1 + $this->pixels_per_km;
				
				if ($this->pixels_per_km>=40)
				{
					$gridsquare_id=$recordSet->fields[2];
				
					$sql="select * from gridimage where gridsquare_id=$gridsquare_id ".
						"and moderation_status<>'rejected' order by moderation_status+0 desc,seq_no limit 1";
						
					//echo "$sql\n";	
					$rec=$dbImg->GetRow($sql);
					if (count($rec))
					{
						$gridimage=new GridImage;
						$gridimage->fastInit($rec);
						
						$photo=$gridimage->getSquareThumb($this->pixels_per_km);
						if (!is_null($photo))
						{
							imagecopy ($img, $photo, $imgx1, $imgy1, 0,0, $this->pixels_per_km,$this->pixels_per_km);
						 	imagedestroy($photo);
						 	
						 	imagerectangle ($img, $imgx1, $imgy1, $imgx2, $imgy2, $colBorder);
						 	imagerectangle ($img, $imgx1+1, $imgy1+1, $imgx2-1, $imgy2-1, $colBorder);
						 	
							

						}
						

					}
					
				}
				else
				{
					//just mark the square
					imagefilledrectangle ($img, $imgx1-1, $imgy1, $imgx2+1, $imgy2, $colMarker);
					imagefilledrectangle ($img, $imgx1, $imgy1-1, $imgx2, $imgy2+1, $colMarker);
				}
			}
			
			$recordSet->MoveNext();
		}
		$recordSet->Close(); 
				
		$target=$this->getImageFilename();
		imagepng($img, $root.$target);
		
		imagedestroy($img);
		
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
	
}
?>
