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
	* the type of map or user its tailered to
	*/
	var $type_or_user=0;
	
	
	/**
	* should the map be cached?
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
		$this->type_or_user = 0;
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
		$token->setValue("t",  $this->type_or_user);
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
				if ($token->hasValue("t")) {
					$this->type_or_user = $token->getValue("t");
				}
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
		if ($x == -1 && $y == -1) {
			$x = intval($this->image_w / 2);
			$y = intval($this->image_h / 2);
		}
		$db=&$this->_getDB();

		//invert the y coordinate
		$y=$this->image_h-$y;

		//convert pixel pos to internal coordinates
		$x_km=$this->map_x + floor($x/$this->pixels_per_km);
		$y_km=$this->map_y + floor($y/$this->pixels_per_km);


		//this could be done in one query, but it's a funky join for something so simple
		$reference_index=$db->GetOne("select reference_index from gridsquare where x=$x_km and y=$y_km");

		//But what to do when the square is not on land??

		if ($reference_index) {
			$where_crit =  "and reference_index=$reference_index";
		} else {
			//when not on land just try any square!
			// but favour the _smaller_ grid - works better, but still not quite right where the two grids almost overlap
			$where_crit =  "order by reference_index desc";
		}

		$sql="select prefix,origin_x,origin_y from gridprefix ".
			"where $x_km between origin_x and (origin_x+width-1) and ".
			"$y_km between origin_y and (origin_y+height-1) $where_crit";
		$prefix=$db->GetRow($sql);
		if ($prefix['prefix']) { 
			$n=$y_km-$prefix['origin_y'];
			$e=$x_km-$prefix['origin_x'];
			return sprintf('%s%02d%02d', $prefix['prefix'], $e, $n);
		} else {
			return "unknown";
		}
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
		
		$file="detail_{$this->map_x}_{$this->map_y}_{$this->image_w}_{$this->image_h}_{$this->pixels_per_km}_{$this->type_or_user}.png";
		
		
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
		
		//
		/*
		$file=$this->getImageFilename();
		$full=$_SERVER['DOCUMENT_ROOT'].$file;
		
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
			$this->_renderMap();			
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
	* render the map to a file
	* @access private
	*/
	function& _renderMap() {
		if ($this->type_or_user == -1) {
			$this->_renderRandomGeographMap();
		} else if ($this->type_or_user > 0) {
			//todo
			//$this->_renderUserMap();
		} else {
			$this->_renderImage();
		}

		$db=&$this->_getDB();

		$sql=sprintf("replace into mapcache set map_x=%d,map_y=%d,image_w=%d,image_h=%d,pixels_per_km=%f,type_or_user=%d",$this->map_x,$this->map_y,$this->image_w,$this->image_h,$this->pixels_per_km,$this->type_or_user);

		$db->Execute($sql);
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
		$colAlias=imagecolorallocate($img, 182,163,57);
		
		
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

			$imgx2=$imgx1 + $this->pixels_per_km;
			$imgy2=$imgy1 + $this->pixels_per_km;
				
				
			if ($this->pixels_per_km<0.3)
			{
				imagesetpixel($img,$imgx1, $imgy1,$colMarker);
			}
			elseif ($this->pixels_per_km<1)
			{
				//plot a simple cross
				imageline ($img, $imgx1-1, $imgy1, $imgx1+1, $imgy1, $colMarker);
				imageline ($img, $imgx1, $imgy1-1, $imgx1, $imgy1+1, $colMarker);
				
				//antialias corners if not already marked
				$rgb = imagecolorat($img, $imgx1-1, $imgy1-1);
				if ($rgb!=$colMarker)
					imagesetpixel($img,$imgx1-1, $imgy1-1,$colAlias);
				
				$rgb = imagecolorat($img, $imgx1+1, $imgy1-1);
				if ($rgb!=$colMarker)
					imagesetpixel($img,$imgx1+1, $imgy1-1,$colAlias);
				
				$rgb = imagecolorat($img, $imgx1-1, $imgy1+1);
				if ($rgb!=$colMarker)
					imagesetpixel($img,$imgx1-1, $imgy1+1,$colAlias);
				
				$rgb = imagecolorat($img, $imgx1+1, $imgy1+1);
				if ($rgb!=$colMarker)
					imagesetpixel($img,$imgx1+1, $imgy1+1,$colAlias);
			}
			elseif ($this->pixels_per_km<=4)
			{
				//nice large marker
				imagefilledrectangle ($img, $imgx1-1, $imgy1, $imgx2+1, $imgy2, $colMarker);
				imagefilledrectangle ($img, $imgx1, $imgy1-1, $imgx2, $imgy2+1, $colMarker);
			}
			else
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

					//	imagerectangle ($img, $imgx1, $imgy1, $imgx2, $imgy2, $colBorder);
					//	imagerectangle ($img, $imgx1+1, $imgy1+1, $imgx2-1, $imgy2-1, $colBorder);



					}


				}

			}
			
			
			
			$recordSet->MoveNext();
		}
		$recordSet->Close(); 

		//plot grid square?
		if ($this->pixels_per_km>=0)
		{
			$this->_plotGridLines($img,$scanleft,$scanbottom,$scanright,$scantop,$bottom,$left);
		}
				
		$target=$this->getImageFilename();
		imagepng($img, $root.$target);
		
		imagedestroy($img);
		
	}	
	
	/**
	* render the image to cached file if not already available
	* @access private
	*/
	function _renderRandomGeographMap()
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
		$colAlias=imagecolorallocate($img, 182,163,57);
		
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
		
		//plot grid square?
		if ($this->pixels_per_km>=0)
		{
			$this->_plotGridLines($img,$scanleft,$scanbottom,$scanright,$scantop,$bottom,$left);
		}
		
		$sql="select x,y,gridsquare_id from gridsquare where ".
			"(x between $scanleft and $scanright) and ".
			"(y between $scanbottom and $scantop) ".
			"and imagecount>0 order by rand() limit 500";

		$recordSet = &$db->Execute($sql);
		while (!$recordSet->EOF) 
		{
			$gridx=$recordSet->fields[0];
			$gridy=$recordSet->fields[1];

			$imgx1=($gridx-$left) * $this->pixels_per_km;
			$imgy1=($this->image_h-($gridy-$bottom+1)* $this->pixels_per_km);

			$photopixels = 40;

			$imgx1=round($imgx1) - (0.5 * $photopixels);
			$imgy1=round($imgy1) - (0.5 * $photopixels);

			$imgx2=$imgx1 + $photopixels;
			$imgy2=$imgy1 + $photopixels;
				
				
			$gridsquare_id=$recordSet->fields[2];

			$sql="select * from gridimage where gridsquare_id=$gridsquare_id ".
				"and moderation_status<>'rejected' order by moderation_status+0 desc,seq_no limit 1";

			//echo "$sql\n";	
			$rec=$dbImg->GetRow($sql);
			if (count($rec))
			{
				$gridimage=new GridImage;
				$gridimage->fastInit($rec);

				$photo=$gridimage->getSquareThumb($photopixels);
				if (!is_null($photo))
				{
					imagecopy ($img, $photo, $imgx1, $imgy1, 0,0, $photopixels,$photopixels);
					imagedestroy($photo);

					imagerectangle ($img, $imgx1, $imgy1, $imgx2, $imgy2, $colBorder);
				//	imagerectangle ($img, $imgx1+1, $imgy1+1, $imgx2-1, $imgy2-1, $colBorder);



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
	* render the image to cached file if not already available
	* @access private
	*/	
	function _plotGridLines(&$img,$scanleft,$scanbottom,$scanright,$scantop,$bottom,$left) {			
		//figure out what we're mapping in internal coords
		$db=&$this->_getDB();
				
		$gridcol=imagecolorallocate ($img, 109,186,178);

		$text1=imagecolorallocate ($img, 255,255,255);
		$text2=imagecolorallocate ($img, 0,64,0);



		$sql="select * from gridprefix where ".
			"origin_x between $scanleft-width and $scanright and ".
			"origin_y between $scanbottom-height and $scantop ".
			"and landcount>0";

		$recordSet = &$db->Execute($sql);
		while (!$recordSet->EOF) 
		{
			$origin_x=$recordSet->fields['origin_x'];
			$origin_y=$recordSet->fields['origin_y'];
			$w=$recordSet->fields['width'];
			$h=$recordSet->fields['height'];

			//get polygon of boundary relative to corner of square
			if (strlen($recordSet->fields['boundary']))
			{
				$polykm=explode(',', $recordSet->fields['boundary']);
				$labelkm=explode(',', $recordSet->fields['labelcentre']);
			}
			else
			{
				$polykm=array(0,0, 0,100, 100,100, 100,0);
				$labelkm=array(50,50);
			}

			//now convert km to pixels
			$poly=array();
			$label=array();
			$pts=count($polykm)/2;
			for($i=0; $i<$pts; $i++)
			{
				$poly[$i*2]=round(($polykm[$i*2]+$origin_x-$left)* $this->pixels_per_km);
				$poly[$i*2+1]=round(($this->image_h-($polykm[$i*2+1]+$origin_y-$bottom)* $this->pixels_per_km));
			}

			$labelx=round(($labelkm[0]+$origin_x-$left)* $this->pixels_per_km);
			$labely=round(($this->image_h-($labelkm[1]+$origin_y-$bottom)* $this->pixels_per_km));


			imagepolygon($img, $poly,$pts,$gridcol);



			if($this->pixels_per_km>=0.3)
			{
				//font size 1= 4x6
				//font size 2= 6x8 normal
				//font size 3= 6x8 bold
				//font size 4= 7x10 normal
				//font size 5= 8x10 bold

				if($this->pixels_per_km>=1)
					$font=5;
				else
					$font=3;


				$text=$recordSet->fields['prefix'];

				switch($font)
				{
					case 3:
						$txtw=strlen($text)*7;
						$txth=8;
						break;
					case 5:
						$txtw=strlen($text)*8;
						$txth=10;
						break;
				}

				$txtx=round($labelx - $txtw/2);
				$txty=round($labely - $txth/2);

				imagestring ($img, $font, $txtx+1,$txty+1, $text, $text2);
				imagestring ($img, $font, $txtx,$txty, $text, $text1);
			}

			$recordSet->MoveNext();
		}
		$recordSet->Close(); 		
	}
	


	/**
	* return a sparse 2d array for every grid on the map
	* @access private
	*/
	function& getGridArray()
	{
		//figure out what we're mapping in internal coords
		$db=&$this->_getDB();
		
		$grid=array();
		

		$left=$this->map_x;
		$bottom=$this->map_y;
		$right=$left + floor($this->image_w/$this->pixels_per_km)-1;
		$top=$bottom + floor($this->image_h/$this->pixels_per_km)-1;

		$overscan=0;
		$scanleft=$left-$overscan;
		$scanright=$right+$overscan;
		$scanbottom=$bottom-$overscan;
		$scantop=$top+$overscan;
		
		$sql="select * from gridsquare where ".
			"(x between $scanleft and $scanright) and ".
			"(y between $scanbottom and $scantop)";
			//"and imagecount>0";

		$recordSet = &$db->Execute($sql);
		while (!$recordSet->EOF) 
		{
			$gridx=$recordSet->fields['x'];
			$gridy=$recordSet->fields['y'];

			$posx=$gridx-$this->map_x;
			$posy=($top-$bottom) - ($gridy-$bottom);
			
			$grid[$posx][$posy]=$recordSet->fields;
			
			
			
			$recordSet->MoveNext();
		}
		$recordSet->Close(); 

		return $grid;
		
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
