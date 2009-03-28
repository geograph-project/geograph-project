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
	* bounding rectangles for labels, in an attempt to prevent collisions
	*/
	var $labels = array();
	
	/*
	 * palette index, see setPalette for documentation
	 */
	var $palette=0;
	
	/*
	 * array of colour values, initialised by setPalette
	 */
	var $colour=array();
	
	/**
	* Constructor
	*/
	function GeographMap()
	{
		$this->setOrigin(0,0);
		$this->setImageSize(400,400);
		$this->setScale(0.3);
		$this->setPalette(0);
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
		if (!empty($this->type_or_user))
			$token->setValue("t",  $this->type_or_user);
		if (isset($this->reference_index))
			$token->setValue("r",  $this->reference_index);
		if ($this->palette)
			$token->setValue("p",  $this->palette);
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
				$this->type_or_user = ($token->hasValue("t"))?$token->getValue("t"):0;
				if ($token->hasValue("r")) 
					$this->reference_index = $token->getValue("r");
				if ($token->hasValue("p")) 
					$this->setPalette($token->getValue("p"));
			}
			
		}
		
		return $ok;
	}

	/*
	 * Sets the colour palette to use when rendering the map
	 * 0 = basic: pale blue sea, green land, red dots
	 * 1 = charcoal: dark grey sea, green land, red dots
	 */
	function setPalette($idx)
	{
		$this->palette=$idx;
		
		$this->colour=array();
		
		//common to all
		$this->colour['marker']=array(255,0,0);
		$this->colour['suppmarker']=array(236,206,64);
		$this->colour['border']=array(255,255,255);
		$this->colour['land']=array(117,255,101);
		//$this->colour['land']=array(102,204,102);
		
		//specific to a palette...
		switch ($idx)
		{
			case 1:
				//charcoal
				$this->colour['sea']=array(51,51,51);
				break;
				
			case 0:
			default:
				//basic
				$this->colour['sea']=array(101,117,255);
				break;
		}
		
	}

	/**
	* get pan url, if possible - return empty string if limit is hit
	* @param xdir = amount of left/right panning, e.g. -1 to pan left
	* @param ydir = amount of up/down panning, e.g. 1 to pan up
	* @access public
	*/
	function getPanToken($xdir,$ydir)
	{
		$out=new GeographMap;
		
		//no panning unless you are zoomed in
		if ($this->pixels_per_km >=1)
		{
			//start with same params
			$out->setScale($this->pixels_per_km);
			$out->setImageSize($this->image_w, $this->image_h);
			$out->type_or_user = $this->type_or_user;

			//pan half a map
			//figure out image size in km
			$mapw=$out->image_w/$out->pixels_per_km;
			$maph=$out->image_h/$out->pixels_per_km;

			//figure out how many pixels to pan by
			$panx=round($mapw/2);
			$pany=round($maph/2);

			$out->setOrigin(
				$this->map_x + $panx*$xdir,
				$this->map_y + $pany*$ydir,true);
		}
		return $out->getToken();
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
		} else {
			//invert the y coordinate
			$y=$this->image_h-$y;
		}
		$db=&$this->_getDB();
		
		//convert pixel pos to internal coordinates
		$x_km=$this->map_x + floor($x/$this->pixels_per_km);
		$y_km=$this->map_y + floor($y/$this->pixels_per_km);
		
		$row=$db->GetRow("select reference_index,grid_reference from gridsquare where CONTAINS( GeomFromText('POINT($x_km $y_km)'),point_xy )");
			
		if (!empty($row['reference_index'])) {
			$this->gridref = $row['grid_reference'];
			$this->reference_index = $row['reference_index'];
		} else {
			if ($this->reference_index) {
				//so it can be set from above (mapmosaic!)
				
				$order_by = "(reference_index = {$this->reference_index}) desc, landcount desc, reference_index";
			} else {
				//But what to do when the square is not on land??

				//when not on land just try any square!
				// but favour the _smaller_ grid - works better, now use SPATIAL index
				$order_by = "landcount desc, reference_index";
			}
			
			$sql="select prefix,origin_x,origin_y,reference_index from gridprefix 
				where CONTAINS( geometry_boundary,	GeomFromText('POINT($x_km $y_km)'))
				order by $order_by limit 1";

			$prefix=$db->GetRow($sql);

			if (empty($prefix['prefix'])) { 
				//if fails try a less restrictive search
				$sql="select prefix,origin_x,origin_y,reference_index from gridprefix 
					where $x_km between origin_x and (origin_x+width-1) and 
					$y_km between origin_y and (origin_y+height-1)
					order by landcount desc, reference_index limit 1";
				$prefix=$db->GetRow($sql);
			}
			
			if (!empty($prefix['prefix'])) { 
				$n=$y_km-$prefix['origin_y'];
				$e=$x_km-$prefix['origin_x'];
				$this->gridref = sprintf('%s%02d%02d', $prefix['prefix'], $e, $n);
				$this->reference_index = $prefix['reference_index'];
			} else {
				$this->gridref = "unknown";
			}
		}
		
		return $this->gridref;
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
		
		//for palette 0 we use the older, palette free filename
		$palette="";
		if ($this->palette>0)
			$palette="_".$this->palette;
		
		if (!empty($this->minimum)) {
			$palette .= "_n{$this->minimum}";
		}
		
		$extension = ($this->pixels_per_km > 40 || $this->type_or_user < -20)?'jpg':'png';
		
		$file="detail_{$this->map_x}_{$this->map_y}_{$this->image_w}_{$this->image_h}_{$this->pixels_per_km}_{$this->type_or_user}{$palette}.$extension";
		
		if (!empty($this->mapDateCrit)) {
			$file=preg_replace('/\./',"-{$this->mapDateStart}.",$file);
		}
		if (!empty($this->displayYear)) {
			$file=preg_replace('/\./',"-y{$this->displayYear}.",$file);
		}
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
		
		//for palette 0 we use the older, palette free filename
		$palette="";
		if ($this->palette>0)
			$palette="_".$this->palette;
		
		$file="base_{$this->map_x}_{$this->map_y}_{$this->image_w}_{$this->image_h}_{$this->pixels_per_km}{$palette}.gd";
		
		
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
		global $CONF;
		
		if ($this->type_or_user == -1 && $this->pixels_per_km >4) {
			$this->type_or_user =0;
			$real = -1;			
		}
		//always given dynamic url, that way cached HTML can 
		//always get an image
		$token=$this->getToken();
		$file="http://{$CONF['TILE_HOST']}/tile.php?map=$token";

		if (isset($CONF['curtail_level']) && $CONF['curtail_level'] > 2 && empty($GLOBALS['USER']->user_id)) {
			$file = cachize_url($file);
		}

		if (isset($real)) 
			 $this->type_or_user = $real;

		return $file;
		
	}
	
	/**
	* returns an image with appropriate headers
	* @access public
	*/
	function returnImage()
	{
		//if thumbs level on depeth map, can just use normal render.
		if ($this->type_or_user == -1 && $this->pixels_per_km >4) {
			$this->type_or_user = 0;
		}
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
		
		//use the filename as a hash
		//can use if-last-mod as file is not unique per user
		customCacheControl($t,$full,true);	
		customExpiresHeader(3600*6,true);
		
		
		
		$size=filesize($full);
		header("Content-Type: $type");
		header("Content-Size: $size");
		header("Content-Length: $size");
		
		
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
	
	#STANDARD MAP
		if ($this->type_or_user == 0) {
			$ok = $this->_renderImage();
		} else if ($this->type_or_user < 0) {
	
	
	#TEMPORYAY
			if ($this->type_or_user == -3) {
				$ok = $this->_renderDepthImage();
				
	#TEMPORYAY
			} elseif ($this->type_or_user == -4) {
				$ok = $this->_renderImage();
				
				
	#DEPTH MAP (_renderDepthImage also understands date maps)
			} elseif ($this->type_or_user == -1) {
				//if thumbs level can just use normal render. 
				if ($this->pixels_per_km<=4) {
					$ok = $this->_renderDepthImage();
				} else {
					$ok = $this->_renderImage();
				}
	
	#ONLY INCLUDE PHOTOS UPTO CERTAIN DATE
			} elseif ($this->type_or_user == -2) {
				$ok = $this->_renderDateImage();
	
	#BLANK (base) MAP
			} elseif ($this->type_or_user == -10) {
				//normal render image, understands type_or_user = -10 and just draws empty tile
				$ok = $this->_renderImage();
	
	#RANDOM SELECTION OF THUMBS OF A LARGEMAP
			} else  {
				$ok = $this->_renderRandomGeographMap();
			}
			
	#PERSONAL MAP
		} else if ($this->type_or_user > 0) {
			//normal render image, understands type_or_user > 0!
			$ok = $this->_renderImage();
		} 

		//save it for rerendering later. 
		if ($ok) {
			$db=&$this->_getDB();

			$sql=sprintf("replace into mapcache set map_x=%d,map_y=%d,image_w=%d,image_h=%d,pixels_per_km=%f,type_or_user=%d",$this->map_x,$this->map_y,$this->image_w,$this->image_h,$this->pixels_per_km,$this->type_or_user);

			$db->Execute($sql);
		}
		return $ok;
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
		$blue=imagecolorallocate ($img, $this->colour['sea'][0],$this->colour['sea'][1],$this->colour['sea'][2]);
		imagefill($img,0,0,$blue);
		
		$rmin=$this->colour['sea'][0];
		$rmax=$this->colour['land'][0];
		$gmin=$this->colour['sea'][1];
		$gmax=$this->colour['land'][1];
		$bmin=$this->colour['sea'][2];
		$bmax=$this->colour['land'][2];
		
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
			
		$rectangle = "'POLYGON(($left $bottom,$right $bottom,$right $top,$left $top,$left $bottom))'";
		
		//now plot all squares in the desired area
		$sql="select x,y,percent_land,reference_index from gridsquare where 
			CONTAINS( GeomFromText($rectangle),	point_xy)";

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
	* checks if specified user has any images on map, (so can use standard blank tile otherwise) 
	* @access private
	*/
	function needUserTile($user_id)
	{
		global $CONF;
		
		//figure out what we're mapping in internal coords
		$db=&$this->_getDB();
		
		$dbImg=NewADOConnection($GLOBALS['DSN']);

		$left=$this->map_x;
		$bottom=$this->map_y;
		$right=$left + floor($this->image_w/$this->pixels_per_km)-1;
		$top=$bottom + floor($this->image_h/$this->pixels_per_km)-1;

		//size of a marker in pixels
		$markerpixels=$this->pixels_per_km;
		
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

		$rectangle = "'POLYGON(($scanleft $scanbottom,$scanright $scanbottom,$scanright $scantop,$scanleft $scantop,$scanleft $scanbottom))'";

		//because we are only interested if any photos on tile, use limit 1 (added by getOne) rather than a count(*)
		$sql="select gridimage_id from gridimage_search where 
				CONTAINS( GeomFromText($rectangle),	point_xy) and
				user_id = $user_id";
		$id = $db->getOne($sql);
		
		return !empty($id);
	}

	/**
	* render the image to cached file if not already available
	* @access private
	*/
	function _renderImage()
	{
		global $CONF;
		$root=&$_SERVER['DOCUMENT_ROOT'];
		
		$ok = true;
		
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
		
		if (!$img) {
			return false;
		}
		
		$colMarker=imagecolorallocate($img, $this->colour['marker'][0],$this->colour['marker'][1],$this->colour['marker'][2]);
		$colSuppMarker=imagecolorallocate($img, $this->colour['suppmarker'][0],$this->colour['suppmarker'][1],$this->colour['suppmarker'][2]);
		$colBorder=imagecolorallocate($img, $this->colour['border'][0],$this->colour['border'][1],$this->colour['border'][2]);
		
		//if we operating at less than 1 pixel per km,
		//we need some colours for aliasing
		if ($this->pixels_per_km < 1 && $this->type_or_user != -10)
		{
			//we want a range of aliases from 117,255,101 to 255,0,0
			$rmin=$this->colour['land'][0];
			$gmin=$this->colour['land'][1];
			$bmin=$this->colour['land'][2];
			$rmax=$this->colour['marker'][0];
			$gmax=$this->colour['marker'][1];
			$bmax=$this->colour['marker'][2];
			
			//we can use the scale to figure out how many square a single image
			//pixel accounts for
			$alias_count = ceil(1/$this->pixels_per_km);
			
			//seems to help
			if ($this->type_or_user > 0)
				$alias_count/=2;
			elseif ($this->pixels_per_km<=0.18)
				$alias_count*=7;
			elseif ($this->pixels_per_km==0.3)
				$alias_count*=3;
			
			
			$colAliasedMarker=array();
			for ($p=0; $p<$alias_count; $p++)
			{
				$scale=($p+1)/$alias_count;
				
				$r=round($rmin + ($rmax-$rmin)*$scale);
				$g=round($gmin + ($gmax-$gmin)*$scale);
				$b=round($bmin + ($bmax-$bmin)*$scale);
				
				$colAliasedMarker[$p]=imagecolorallocate($img, $r,$g,$b);
	
			}
			
			$nextAlias=array();
			foreach($colAliasedMarker as $idx=>$col)
			{
				if ($idx==$alias_count-1)
					$nextAlias[$col]=$col;
				else
					$nextAlias[$col]=$colAliasedMarker[$idx+1];
				
			}
		}
		
		//figure out what we're mapping in internal coords
		$db=&$this->_getDB();
		
		$dbImg=NewADOConnection($GLOBALS['DSN']);
		

		$left=$this->map_x;
		$bottom=$this->map_y;
		$right=$left + floor($this->image_w/$this->pixels_per_km)-1;
		$top=$bottom + floor($this->image_h/$this->pixels_per_km)-1;

		//size of a marker in pixels
		$markerpixels=$this->pixels_per_km;
		
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
		
		//setup ready to plot squares
		$this->_plotGridLines($img,$scanleft,$scanbottom,$scanright,$scantop,$bottom,$left,true);
		
		
		$rectangle = "'POLYGON(($scanleft $scanbottom,$scanright $scanbottom,$scanright $scantop,$scanleft $scantop,$scanleft $scanbottom))'";
				
		if (!empty($this->type_or_user)) {
			if ($this->type_or_user == -10) {
				//we want a blank map!
				$sql = "select 0 limit 0";
			} elseif ($this->type_or_user == -4) {
				//todo doesnt use the where clause!
				$sql="select x,y,gridsquare_id,max(created) > date_sub(now(),interval 30 day) as has_geographs from gridsquare3
					inner join mapfix_log using (gridsquare_id)  
					group by gridsquare_id";
			} else {
				$sql="select x,y,grid_reference,sum(moderation_status = 'geograph') as has_geographs from gridimage_search where 
					CONTAINS( GeomFromText($rectangle),	point_xy) and
					user_id = {$this->type_or_user} group by grid_reference";
			}
		} else {
			$number = !empty($this->minimum)?intval($this->minimum):0;
			$sql="select x,y,gridsquare_id,has_geographs from gridsquare where 
				CONTAINS( GeomFromText($rectangle),	point_xy)
				and imagecount>$number";
		}

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
				
			$color = ($recordSet->fields[3])?$colMarker:$colSuppMarker;	
				
			//if less than 1 pixel per km, use our aliasing scheme	
			if ($this->pixels_per_km<1)
			{
				$rgb = imagecolorat($img, $imgx1, $imgy1);
				if (isset($nextAlias[$rgb]))
				{
					imagesetpixel($img,$imgx1, $imgy1,$nextAlias[$rgb]);
				}
				else
				{
					imagesetpixel($img,$imgx1, $imgy1,$colAliasedMarker[0]);
				}
				
			}
			elseif ($this->pixels_per_km==1)
			{
				//easy!
				imagesetpixel($img,$imgx1, $imgy1,$color);
			}
			elseif ($this->pixels_per_km<=4)
			{
				//nice large marker
				imagefilledrectangle ($img, $imgx1, $imgy1, $imgx2, $imgy2, $color);
			}
			else
			{
				//thumbnail
				if (!empty($this->type_or_user)) {
					$grid_reference=$recordSet->fields[2];
			
					$sql="select * from gridimage_search where grid_reference='$grid_reference' 
					and user_id = {$this->type_or_user} order by moderation_status+0 desc,seq_no limit 1";
				} else {
					$gridsquare_id=$recordSet->fields[2];
			
					$sql="select * from gridimage where gridsquare_id=$gridsquare_id 
					and moderation_status in ('accepted','geograph') order by moderation_status+0 desc,seq_no limit 1";
				
				}
				
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

						if (!$recordSet->fields[3]) {
							imagefilledrectangle ($img, $imgx1+2, $imgy1-4, $imgx1+6, $imgy1-6, $colSuppMarker);
							imagefilledrectangle ($img, $imgx1+3, $imgy1-3, $imgx1+5, $imgy1-7, $colSuppMarker);
						}
					} else {
						$ok = false;
					}


				}

			}
			
			
			
			$recordSet->MoveNext();
		}
		$recordSet->Close(); 

		if ($img) {
			//ok being false isnt fatal, as we can create a tile, however we should use it to try again later!
			
			//plot grid square?
			if ($this->pixels_per_km>=0)
			{
				$this->_plotGridLines($img,$scanleft,$scanbottom,$scanright,$scantop,$bottom,$left);
			}

			if ($this->pixels_per_km>=1  && $this->pixels_per_km<=40 && isset($CONF['enable_newmap']))
			{
				$this->_plotPlacenames($img,$left,$bottom,$right,$top,$bottom,$left);
			}				
			
			$target=$this->getImageFilename();
			if (preg_match('/jpg/',$target)) {
				$ok = (imagejpeg($img, $root.$target) && $ok);
			} else {
				$ok = (imagepng($img, $root.$target) && $ok);
			}

			imagedestroy($img);
			return $ok;
		} else {
			return false;
		}
	}	


	function _outputDepthKey()
	{
		$imgkey=imagecreatetruecolor(400,20);

		$green=imagecolorallocate ($imgkey, $this->colour['land'][0],$this->colour['land'][1],$this->colour['land'][2]);
		imagefill($imgkey,0,0,$green);

		foreach (array(0,1,2,3,4,5,7,10,20,40) as $idx => $o) {
			switch (true) {
				case $o == 0: $r=$this->colour['land'][0]; $g=$this->colour['land'][1]; $b=$this->colour['land'][2]; break; 
				//case $o == 1: $r=255; $g=255; $b=0; break; 
				//case $o == 2: $r=255; $g=196; $b=0; break; 
				//case $o == 3: $r=255; $g=132; $b=0; break; 
				case $o == 1: $r=255; $g=196; $b=0; break; 
				case $o == 2: $r=255; $g=154; $b=0; break; 
				case $o == 3: $r=255; $g=110; $b=0; break; 
				case $o == 4: $r=255; $g=64; $b=0; break; 
				case $o <  7: $r=225; $g=0; $b=0; break; 
				case $o < 10: $r=200; $g=0; $b=0; break; 
				case $o < 20: $r=168; $g=0; $b=0; break; 
				case $o < 40: $r=136; $g=0; $b=0; break; 
				case $o < 80: $r=112; $g=0; $b=0; break; 
			}
			$back=imagecolorallocate($imgkey, $r,$g,$b);
			$text=imagecolorallocate($imgkey, 255-$r,255-$g,255-$b);

			imagefilledrectangle($imgkey, ($idx*40), 0, ($idx*40)+40, 20, $back);
			imagestring($imgkey, 5, ($idx*40)+9, 3, $o<10?" $o":$o, $text);
		}
		header("Content-Type: image/png");
		imagepng($imgkey);
		exit;
	}

	/**
	* render the image to cached file if not already available
	* @access private
	*/
	function _renderDepthImage()
	{
		global $CONF;
		$root=&$_SERVER['DOCUMENT_ROOT'];
		$ok = true;
		
		if ($this->pixels_per_km < 1) {
			//render at 1px/km and scale...
			$this->real_pixels_per_km = $this->pixels_per_km;
			$this->real_image_w = $this->image_w;
			$this->real_image_h = $this->image_h;
			
			//need to change the actual values as need to fool other functions too
			$this->image_w = floor($this->image_w/$this->pixels_per_km);
			$this->image_h = floor($this->image_h/$this->pixels_per_km);
			$this->pixels_per_km = 1;
		}
		
		$basemap=$this->getBaseMapFilename();
		if ($this->caching && @file_exists($root.$basemap)) {
			$img=imagecreatefromgd($root.$basemap);
		} else {
			$img=&$this->_createBasemap($root.$basemap);
		}
		
		if (!$img) {
			return false;
		}
		
		$db=&$this->_getDB();
		
		if ($this->type_or_user == -3) {
			
			$sql="select imagecount from gridsquare_group_count group by imagecount";
		} else {
			$sql="select imagecount from gridsquare group by imagecount";
		}
		$counts = $db->cacheGetCol(3600,$sql);

		$colour=array();
		$last=$lastcolour=null;
		for ($p=0; $p<count($counts); $p++)
		{
			$o = $counts[$p];
			//standard green, yellow => red
			switch (true) {
				case $o == 0: $r=$this->colour['land'][0]; $g=$this->colour['land'][1]; $b=$this->colour['land'][2]; break; 
				//case $o == 1: $r=255; $g=255; $b=0; break; 
				//case $o == 2: $r=255; $g=196; $b=0; break; 
				//case $o == 3: $r=255; $g=132; $b=0; break; 
				case $o == 1: $r=255; $g=196; $b=0; break; 
				case $o == 2: $r=255; $g=154; $b=0; break; 
				case $o == 3: $r=255; $g=110; $b=0; break; 
				case $o == 4: $r=255; $g=64; $b=0; break; 
				case $o <  7: $r=225; $g=0; $b=0; break; #5-6
				case $o < 10: $r=200; $g=0; $b=0; break; #7-9
				case $o < 20: $r=168; $g=0; $b=0; break; #10-19
				case $o < 40: $r=136; $g=0; $b=0; break; #20-39
				case $o < 80: $r=112; $g=0; $b=0; break; #40-79
				default: $r=80; $g=0; $b=0; break;
			}
			$key = "$r,$g,$b";
			if ($key == $last) {
				$colour[$o] = $lastcolour;
			} else {
				$lastcolour = $colour[$o]=imagecolorallocate($img, $r,$g,$b);
			}
			$last = $key;
		}

		//figure out what we're mapping in internal coords
		$left=$this->map_x;
		$bottom=$this->map_y;
		$right=$left + floor($this->image_w/$this->pixels_per_km)-1;
		$top=$bottom + floor($this->image_h/$this->pixels_per_km)-1;

		//size of a marker in pixels
		$markerpixels=$this->pixels_per_km;

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

		if (!(isset($this->real_pixels_per_km) && $this->real_pixels_per_km < 1) )
			$this->_plotGridLines($img,$scanleft,$scanbottom,$scanright,$scantop,$bottom,$left,true);

		$rectangle = "'POLYGON(($scanleft $scanbottom,$scanright $scanbottom,$scanright $scantop,$scanleft $scantop,$scanleft $scanbottom))'";

		$number = !empty($this->minimum)?intval($this->minimum):0;

		if ($this->type_or_user == -3) {
			$sql="select x,y,gs.gridsquare_id,count(distinct label) as imagecount
				from 
				gridsquare gs 
				inner join gridimage2 gi using(gridsquare_id)
				inner join gridimage_group gg using(gridimage_id)
				
			group by gi.gridsquare_id "; #where CONTAINS( GeomFromText($rectangle),	point_xy) 
			
			$sql="select * from gridsquare_group_count";
			
		} elseif (!empty($this->mapDateCrit)) {
		$sql="select x,y,gs.gridsquare_id,count(*) as imagecount
			from 
			gridsquare gs 
			inner join gridimage gi using(gridsquare_id)
			where CONTAINS( GeomFromText($rectangle),	point_xy) and
			submitted < '{$this->mapDateStart}'
			group by gi.gridsquare_id ";
		} else {
		$sql="select x,y,gridsquare_id,imagecount from gridsquare where 
			CONTAINS( GeomFromText($rectangle),	point_xy)
			and imagecount>$number"; #and percent_land = 100  #can uncomment this if using the standard green base
		}



		$recordSet = &$db->Execute($sql);
		while (!$recordSet->EOF) 
		{
			$gridx=$recordSet->fields[0];
			$gridy=$recordSet->fields[1];

			$imgx1=round(($gridx-$left) * $this->pixels_per_km);
			$imgy1=round(($this->image_h-($gridy-$bottom+1)* $this->pixels_per_km));
	
			$color = $colour[$recordSet->fields[3]];

			if ($this->pixels_per_km==1) {
				imagesetpixel($img,$imgx1, $imgy1,$color);
			} else {
				$imgx2=$imgx1 + $this->pixels_per_km;
				$imgy2=$imgy1 + $this->pixels_per_km;
				imagefilledrectangle ($img, $imgx1, $imgy1, $imgx2, $imgy2, $color);
			}
	
			$recordSet->MoveNext();
		}
		$recordSet->Close(); 

		if (isset($this->real_pixels_per_km) && $this->real_pixels_per_km < 1) {
			//render at 1px/km and scale...

			$resized = imagecreatetruecolor($this->real_image_w, $this->real_image_h);
			imagecopyresampled($resized, $img, 0, 0, 0, 0, 
						$this->real_image_w, $this->real_image_h, $this->image_w, $this->image_h);

			imagedestroy($img);

			$img = $resized;

			$this->pixels_per_km = $this->real_pixels_per_km;
			$this->image_w = $this->real_image_w;
			$this->image_h = $this->real_image_h;
			
			$this->_plotGridLines($img,$scanleft,$scanbottom,$scanright,$scantop,$bottom,$left,true);
		}

		if ($img) {
			$this->_plotGridLines($img,$scanleft,$scanbottom,$scanright,$scantop,$bottom,$left);
			
			if ($this->pixels_per_km>=1  && $this->pixels_per_km<40 && isset($CONF['enable_newmap'])) {
				$this->_plotPlacenames($img,$left,$bottom,$right,$top,$bottom,$left);
			}				
			
			$target=$this->getImageFilename();
			
			if (!empty($this->mapDateCrit)) {
				$black = imagecolorallocate ($img, 70, 70, 0);
				imagestring($img, 5, 3, $this->image_h-30, $this->mapDateStart, $black);
			}
			
			if (preg_match('/jpg/',$target)) {
				$ok = (imagejpeg($img, $root.$target) && $ok);
			} else {
				$ok = (imagepng($img, $root.$target) && $ok);
			}

			imagedestroy($img);
			return $ok;
		} else {
			return false;
		}
	}
	
	/**
	* render the image to cached file if not already available
	* @access private
	*/
	function _renderDateImage()
	{
		global $CONF;
		$root=&$_SERVER['DOCUMENT_ROOT'];
		$ok = true;

		$basemap=$this->getBaseMapFilename();
		if (!empty($this->displayYear)) {
			$img=imagecreate($this->image_w,$this->image_h);
			$colBackground=imagecolorallocate($img, 255,255,255);
			imagecolortransparent($img,$colBackground);
		} elseif ($this->caching && @file_exists($root.$basemap)) {
			$img=imagecreatefromgd($root.$basemap);
		} else {
			$img=&$this->_createBasemap($root.$basemap);
		}

		if (!$img) 
			return false;

		$colMarker=imagecolorallocate($img, 255,0,0);
		$colSuppMarker=imagecolorallocate($img,236,206,64);
		$colBorder=imagecolorallocate($img, 255,255,255);
		$black = imagecolorallocate ($img, 70, 70, 0);

		$db=&$this->_getDB();

		#$sql="select imagecount from gridsquare group by imagecount";
		#$counts = $db->getCol($sql);

		//figure out what we're mapping in internal coords
		$left=$this->map_x;
		$bottom=$this->map_y;
		$right=$left + floor($this->image_w/$this->pixels_per_km)-1;
		$top=$bottom + floor($this->image_h/$this->pixels_per_km)-1;

		//size of a marker in pixels
		$markerpixels=$this->pixels_per_km;

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

		if (empty($this->displayYear)) {
			$this->_plotGridLines($img,$scanleft,$scanbottom,$scanright,$scantop,$bottom,$left,true);
		}
		$rectangle = "'POLYGON(($scanleft $scanbottom,$scanright $scanbottom,$scanright $scantop,$scanleft $scantop,$scanleft $scanbottom))'";

		if (!empty($this->displayYear)) {
			$sql="select x,y,'' as dummy
				from 
				gridsquare gs 
				inner join gridimage gi using(gridsquare_id)
				where CONTAINS( GeomFromText($rectangle),	point_xy) and
				imagetaken LIKE '{$this->displayYear}%'
				group by gi.gridsquare_id ";
		
		} else {
			$sql="select x,y,sum(submitted > '$mapDateCrit')
				from 
				gridsquare gs 
				inner join gridimage gi using(gridsquare_id)
				where CONTAINS( GeomFromText($rectangle),	point_xy) and
				submitted < '{$this->mapDateStart}'
				group by gi.gridsquare_id ";
		}


		$recordSet = &$db->Execute($sql);
		while (!$recordSet->EOF) 
		{
			$gridx=$recordSet->fields[0];
			$gridy=$recordSet->fields[1];

			$imgx1=round(($gridx-$left) * $this->pixels_per_km);
			$imgy1=round(($this->image_h-($gridy-$bottom+1)* $this->pixels_per_km));
	
			$color = ($recordSet->fields[2])?$colSuppMarker:$colMarker;	

			if ($this->pixels_per_km==1) {
				imagesetpixel($img,$imgx1, $imgy1,$color);
			} else {
				$imgx2=$imgx1 + $this->pixels_per_km;
				$imgy2=$imgy1 + $this->pixels_per_km;
				imagefilledrectangle ($img, $imgx1, $imgy1, $imgx2, $imgy2, $color);
			}

			$recordSet->MoveNext();
		}
		$recordSet->Close(); 

		if ($img) {
			if (empty($this->displayYear)) {
				$this->_plotGridLines($img,$scanleft,$scanbottom,$scanright,$scantop,$bottom,$left);

				imagestring($img, 5, 3, $this->image_h-30, $this->mapDateStart, $black);

				if ($this->pixels_per_km>=1  && $this->pixels_per_km<40 && isset($CONF['enable_newmap']))
					$this->_plotPlacenames($img,$left,$bottom,$right,$top,$bottom,$left);
			}
			$target=$this->getImageFilename();

			if (preg_match('/jpg/',$target)) {
				$ok = (imagejpeg($img, $root.$target) && $ok);
			} else {
				$ok = (imagepng($img, $root.$target) && $ok);
			}

			imagedestroy($img);
			return $ok;
		} else {
			return false;
		}
	}
	
	/**
	* render the the special Random Thumbnail Map (in its many variations)
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
		
		$target=$this->getImageFilename();
		
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
		
		//plot grid square?
		if ($this->pixels_per_km>=0)
		{
			$this->_plotGridLines($img,$scanleft,$scanbottom,$scanright,$scantop,$bottom,$left);
		}
		
		$imagemap = fopen( $root.$target.".html","w");
		fwrite($imagemap,"<map name=\"imagemap\">\n");
		
		$rectangle = "'POLYGON(($scanleft $scanbottom,$scanright $scanbottom,$scanright $scantop,$scanleft $scantop,$scanleft $scanbottom))'";
				
		if ($this->type_or_user < -2000) {
			if ($this->type_or_user < -2007) {
			$sql="select x,y,gi.gridimage_id from gridimage_search gi
			where 
			CONTAINS( GeomFromText($rectangle),	point_xy) and imagetaken = '".($this->type_or_user * -1)."-12-25'
			and( gi.imageclass LIKE '%christmas%') 
			order by rand()";
			} else {
			$sql="select x,y,gi.gridimage_id from gridimage_search gi
			where 
			CONTAINS( GeomFromText($rectangle),	point_xy) and imagetaken = '".($this->type_or_user * -1)."-12-25'
			 order by ( (gi.title LIKE '%xmas%' OR gi.comment LIKE '%xmas%' OR gi.imageclass LIKE '%xmas%') OR (gi.title LIKE '%christmas%' OR gi.comment LIKE '%christmas%' OR gi.imageclass LIKE '%christmas%') ), rand()";
			} 
		} elseif (1) {
			$sql="select x,y,gi.gridimage_id from gridimage_search gi
			where 
			CONTAINS( GeomFromText($rectangle),	point_xy)
			and seq_no = 1 group by FLOOR(x/10),FLOOR(y/10) order by rand() limit 600";
			#inner join gridimage_post gp on (gi.gridimage_id = gp.gridimage_id and gp.topic_id = 1006)
			
			
		
		} elseif (1) {
			$sql="select x,y,gi.gridimage_id from gridimage_search gi
			where gridimage_id in (80343,74737,74092,84274,80195,48940,46618,73778,47029,82007,39195,76043,57771,28998,18548,12818,7932,81438,16764,84846,73951,79510,15544,73752,86199,4437,87278,53119,29003,36991,74330,29732,16946,10613,87284,52195,41935,26237,30008,10252,62365,83753,67060,34453,20760,26759,59465,118,12449,4455,46898,12805,87014,401,36956,8098,44193,63206,42732,26145,86473,17469,3323,26989,3324,40212,63829,30948,165,41865,36605,25736,68318,26849,51771,30986,27174,37470,31098,65191,44406,82224,71627,22968,59008,35468,7507,53228,80854,10669,47604,75018,42649,9271,1658,11741,60793,78903,22198,7586,88164,12818,14981,21794,74790,3386,40974,72850,77652,47982,39894,38897,25041,81392,63186,81974,41373,86365,44388,80376,13506,42984,45159,14837,71377,35108,84318,84422,36640,2179,22317,5324,32506,20690,71588,85859,50813,19358,84848,18141,78772,21074,13903,39376,45795,88385,55327,907,37266,82510,78594,17708,84855,7175,85453,23513,18493,68120,26201,18508,32531,84327,88204,55537,41942,47117,22922,22315,46412,88542,46241,67475,63752,63511,98) order by rand()";
		} else {
		
		$sql="select x,y,grid_reference from gridsquare where 
			CONTAINS( GeomFromText($rectangle),	point_xy)
			and imagecount>0 group by FLOOR(x/30),FLOOR(y/30) order by rand() limit 500";
		}
		
		$usercount=array();
		$recordSet = &$db->Execute($sql);
		$lines = array();
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
				
				
			$gridimage_id=$recordSet->fields[2];

			$sql="select * from gridimage_search where gridimage_id='$gridimage_id' and moderation_status<>'rejected' limit 1";

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
				
					$lines[] = "<area shape=\"rect\" coords=\"$imgx1,$imgy1,$imgx2,$imgy2\" href=\"/photo/{$rec['gridimage_id']}\" title=\"".htmlentities("{$rec['grid_reference']} : {$rec['title']} by {$rec['realname']}")."\">"; 

				}
				$usercount[$rec['realname']]++;
			}

			
			
			
			
			$recordSet->MoveNext();
		}
		$recordSet->Close(); 
			
			fwrite($imagemap,implode("\n",array_reverse($lines)));
			fwrite($imagemap,"</map>\n");
			fclose($imagemap);
		
		
		
			$h = fopen("imagemap.csv",'w');
			foreach ($usercount as $user => $uses) {
				fwrite($h,"$user,$uses\n");
			}
			fclose($h);
		
		
		if (preg_match('/jpg/',$target)) {
			imagejpeg($img, $root.$target);
		} else {
			imagepng($img, $root.$target);
		}
		
		imagedestroy($img);
		
	}		
	
	function _plotPlacenames(&$img,$scanleft,$scanbottom,$scanright,$scantop,$bottom,$left) {			
		$db=&$this->_getDB();

		$black=imagecolorallocate ($img, 0,64,0);

		require_once('geograph/conversions.class.php');
		$conv = new Conversions;

			if (!$this->reference_index) {
				$this->getGridRef(-1,-1);
				if (!$this->reference_index) {
					$this->getGridRef(-1,-1);
					$this->reference_index = 1;
				}
			}
			
		$reference_index = $this->reference_index;
		
		$gridcol=imagecolorallocate ($img, 109,186,178);

		list($natleft,$natbottom) = $conv->internal_to_national($scanleft,$scanbottom,$reference_index);
		list($natright,$nattop) = $conv->internal_to_national($scanright,$scantop,$reference_index);

		if ($this->pixels_per_km < 1) {
			$div = 500000; //1 per 500k square
			$crit = "s = '1' AND";
			$cityfont = 3;
		} elseif ($this->pixels_per_km == 1) {
			$div = 100000; 
			$crit = "(s = '1' OR s = '2') AND";
			$cityfont = 3;
		} elseif ($this->pixels_per_km == 4) {
			$div = 30000;
		#	$crit = "(s = '1' OR s = '2') AND";
			$cityfont = 3;
		} else {
			$div = 10000;
			$cityfont = 3;
		}

		$rectangle = "'POLYGON(($natleft $natbottom,$natright $natbottom,$natright $nattop,$natleft $nattop,$natleft $natbottom))'";
		

if ($reference_index == 1 || ($reference_index == 2 && $this->pixels_per_km == 1 )) {
	//$countries = "'EN','WA','SC'";
$sql = <<<END
SELECT name,e,n,s,quad,reference_index
FROM loc_towns
WHERE 
 $crit
CONTAINS( GeomFromText($rectangle),	point_en) 
ORDER BY s
END;
#GROUP BY FLOOR(e/$div),FLOOR(n/$div)
} else {
	$countries = "'NI','RI'";
	$div *= 1.5; //becuase the irish data is more dence

$sql = <<<END
SELECT e,n,full_name as name,reference_index
FROM loc_placenames
INNER JOIN `loc_wikipedia` ON ( full_name = text ) 
WHERE dsg = 'PPL' AND
loc_wikipedia.country IN ($countries) AND 
CONTAINS( GeomFromText($rectangle),	point_en) 
GROUP BY gns_ufi
ORDER BY RAND()
END;
}
		$squares=array();
		$recordSet = &$db->Execute($sql);
		while (!$recordSet->EOF) 
		{
			$e=$recordSet->fields['e'];
			$n=$recordSet->fields['n'];
			
			$str = floor($e/$div) .' '. floor($n/$div*1.4);
			if (!$squares[$str]) {// || $recordSet->fields['s'] ==1) {
				$squares[$str]++;
			
				list($x,$y) = $conv->national_to_internal($e,$n,$recordSet->fields['reference_index'] );

				$imgx1=($x-$left) * $this->pixels_per_km;
				$imgy1=($this->image_h-($y-$bottom+1)* $this->pixels_per_km);
				
				if ($this->pixels_per_km<=4) {				
					imagefilledrectangle ($img, $imgx1-1, $imgy1-2, $imgx1+1, $imgy1+2, $black);
					imagefilledrectangle ($img, $imgx1-2, $imgy1-1, $imgx1+2, $imgy1+1, $black);
				}
				$font = ($recordSet->fields['s'] ==1)?$cityfont:2;
				$img1 = $this->_posText( $imgx1, $imgy1, $font, $recordSet->fields['name'],$recordSet->fields['quad']);
				if (count($img1))
					imageGlowString($img, $font, $img1[0], $img1[1], $recordSet->fields['name'], $gridcol);
			}
			
			$recordSet->MoveNext();
		}
		if ($_GET['d'])
			exit;
		$recordSet->Close(); 
	}
	
	/*********************************************
	* attempts to place the label so doesnt get obscured, 
	* alogirthm isnt perfect but works quite well.
	*********************************************/
	function _posText($x,$y,$font,$text,$quad = 0) {
		$stren = imagefontwidth($font)*strlen($text);
		$strhr = imagefontheight($font);
		$xy = array($x,$y);
		if ($quad == 0) {
			for ($quad = 1; $quad < 5; ++$quad) {
				list($x,$y) = $xy;
				if ($quad%2 != 1) {
					$x = $x - $stren; //FIXME one pixel too much
				}
				if ($quad <= 2) {
					$y = $y - $strhr; //FIXME one pixel too much
				}
				$thisrect = array($x,$y,$x + $stren,$y + $strhr); //FIXME one pixel too much
				//$thisrect = array($x-3,$y-3,$x + $stren+3,$y + $strhr+3);
				if ($x <= 0 || $y <= 0 || $x + $stren >= $this->image_w || $y + $strhr >= $this->image_h) { // "=" => one pixel more than neccessary
					$intersect = true;
				} else {
					$intersect = false;
					reset($this->labels);
					foreach ($this->labels as $a1) {
						if (rectinterrect($a1,$thisrect)) {
							$intersect = true;
							break;
						}
					}
				}
				if (!$intersect) {
					#trigger_error("label: " . $text . ": " . $stren . "px: " . $x . "..." . ($x + $stren - 1) . " / " . $y . "..." . ($y + $strhr - 1), E_USER_NOTICE);
					break;
				}
			}
			if ($intersect) {
				#$quad=0;
				return array();
			}
		}
		list($x,$y) = $xy;
		if (
		($quad%2 == 1)
			||
		( $quad <= 0 && ($x < ($this->image_w - $stren)) )
		) {
		} else {
			list($x,$d) = $xy;
			$x = $x - $stren;
		}
		if (
		($quad > 2)
			||
		( $quad <= 0 && ($y < ($this->image_h - $strhr)) )
		) {
		} else {
			list($d,$y) = $xy;
			$y = $y - $strhr;
		}
		if ($x > 0 && $y > 0 && ($x < ($this->image_w - $stren)) && ($y < ($this->image_h - $strhr))) {
			$thisrect = array($x-3,$y-3,$x + $stren+3,$y + $strhr+3);

			array_push($this->labels,$thisrect);
			#trigger_error("label pushed: " . $text . ": " . $stren . "px: " . $x . "..." . ($x + $stren - 1) . " / " . $y . "..." . ($y + $strhr - 1), E_USER_NOTICE);

			return array($x,$y);
		} else {
			return array();
		}
	}
	
	
	/**
	* plot the gridlines
	* @access private
	*/	
	function _plotGridLines(&$img,$scanleft,$scanbottom,$scanright,$scantop,$bottom,$left,$pre = false) {			
		static $gridcol,$gridcol2,$text1,$text2; //these are static so they can be assigned before the images are added
		global $CONF;
		if ($pre) {
			if ($this->pixels_per_km >= 40) {
				$gridcol=imagecolorallocate ($img, 89,126,118);
				$gridcol2=imagecolorallocate ($img, 60,205,252);
				//$gridcol2=imagecolorallocate ($img, 102,173,166);
				
				//plot the individual lines
				for($i=0;$i<$this->image_w;$i+=$this->pixels_per_km) {
					imageline($img,$i,0,$i,$this->image_w,$gridcol2);
				}
				for($j=0;$j<$this->image_h;$j+=$this->pixels_per_km) {
					imageline($img,0,$j,$this->image_h,$j,$gridcol2);
				}
			} else if (0 && $this->pixels_per_km == 4) {
				//todo : currently disabled as doesnt work when the map straddles the Irish Sea :-(
				// needs to only draw lines within the manually defined boundary, but cant (yet) think of a alogorim ???
				//could enable for when map is wholely one grid, but a bodge and result inconsistent...
				
				$gridcol=imagecolorallocate ($img, 89,126,118);
				$gridcol2=imagecolorallocate ($img, 60,205,252);
				//$gridcol2=imagecolorallocate ($img, 102,173,166);
				
				if (!$this->reference_index) {
					$this->getGridRef(-1,-1);
				}
				
				//plot the individual lines
				$s = ($left- $CONF['origins'][$this->reference_index][0])%10;
				for($i=$s*$this->pixels_per_km;$i<$this->image_w;$i+=$this->pixels_per_km*10) {
					imageline($img,$i,0,$i,$this->image_w,$gridcol2);
				}
				$s = ($bottom- $CONF['origins'][$this->reference_index][1])%10;
				for($j=$s*$this->pixels_per_km;$j<$this->image_h;$j+=$this->pixels_per_km*10) {
					imageline($img,0,$j,$this->image_h,$j,$gridcol2);
				}
			} else {
				$gridcol=imagecolorallocate ($img, 109,186,178);
			}
			if ($this->pixels_per_km < 1 || $this->pixels_per_km >= 40) {
				$text1=imagecolorallocate ($img, 255,255,255);
				$text2=imagecolorallocate ($img, 0,64,0);
			} else {
				$text1=$gridcol;
			}
			return;
		}
		
		$db=&$this->_getDB();

		
		//TODO  - HARD CODED VALUES!!
		$width = 100;
		$scanleft -= $width;
		$scanbottom -= $width;
		$rectangle = "'POLYGON(($scanleft $scanbottom,$scanright $scanbottom,$scanright $scantop,$scanleft $scantop,$scanleft $scanbottom))'";
		
		$sql="select * from gridprefix where ".
			"CONTAINS( GeomFromText($rectangle),	point_origin_xy) ".
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
				
				$txtw = imagefontwidth($font)*strlen($text);
				$txth = imagefontheight($font);
				

				$txtx=round($labelx - $txtw/2);
				$txty=round($labely - $txth/2);
				if ($this->pixels_per_km < 1) {
					imagestring ($img, $font, $txtx+1,$txty+1, $text, $text2);
					imagestring ($img, $font, $txtx,$txty, $text, $text1);
				} else {
					imagestring ($img, $font, $txtx,$txty, $text, $text1);
				}
				$thisrect = array($txtx,$txty,$txtx + $txtw,$txty + $txth);
				array_push($this->labels,$thisrect);
				if ($_GET['d']) {
					print "$text";var_dump($thisrect); print "<BR>";
				}
			}
			$recordSet->MoveNext();
		}

		$recordSet->Close(); 		
		
		//plot the number labels
		if ($this->pixels_per_km >= 40) {
			$gridref = $this->getGridRef(0, $this->image_h); //origin of image is tl, map is bl
			if (preg_match('/^([A-Z]{1,2})(\d\d)(\d\d)$/',$gridref, $matches))
			{
				$gridsquare=$matches[1];
				$eastings=$matches[2];
				$northings=$matches[3];
				$gran = 10;
				
				$font = 3;
				$me = imagefontwidth($font);
				$mn = floor(imagefontheight($font)/2);
				
				$e5 = floor($eastings/$gran)*$gran; if ($e5 < $eastings) $e5 +=$gran;
				$n5 = floor($northings/$gran)*$gran; if ($n5 < $northings) $n5 +=$gran;
				$ed = (($e5 - $eastings) * $this->pixels_per_km) + ($this->pixels_per_km / 2) - $me;
				$nd = $this->image_h - (($n5 - $northings)* $this->pixels_per_km) - ($this->pixels_per_km / 2) - $mn;
				
				
				$e = $eastings;
				for($i=1-$me;$i<=$this->image_w;$i+=$this->pixels_per_km) {
					imagestring($img,$font,$i+1,$nd+1,$e,$text2);
					imagestring($img,$font,$i,$nd,$e,$text1);
					$e=sprintf("%02d",($e+1)%100);
				}
				
				$n = $northings;
				for($j=$this->image_h-$mn;$j>=0-$mn;$j-=$this->pixels_per_km) {
					imagestring($img,$font,$ed+1,$j+1,$n,$text2);
					imagestring($img,$font,$ed,$j,$n,$text1);
					$n=sprintf("%02d",($n+1)%100);
				}
			}
		}
	}


	/**
	* return a sparse 2d array for every grid on the map
	* @access private
	*/
	function& getGridArray($isimgmap = false)
	{
		global $memcache;

		if ($this->type_or_user == -10) {
			//we want a blank map!
			return array();
		} 

		if ($memcache->valid) {
			//we only use cache imagemap as they invalidate correctly - and checksheets get smarty cached anyways

			$mkey = $this->getImageFilename();
			$mnamespace = $isimgmap?'mi':'ms';
			$grid =& $memcache->name_get($mnamespace,$mkey);
			if ($grid) {
				return $grid;
			}
			$mperiod = $isimgmap?($memcache->period_long*4):($memcache->period_short);
		}

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
		
		$rectangle = "'POLYGON(($scanleft $scanbottom,$scanright $scanbottom,$scanright $scantop,$scanleft $scantop,$scanleft $scanbottom))'";
		if (!empty($this->type_or_user) && $this->type_or_user > 0) {
			$where_crit = " and gi2.user_id = {$this->type_or_user}";
			$where_crit2 = " and gi.user_id = {$this->type_or_user}";
			$columns = ", sum(moderation_status='geograph') as has_geographs, sum(moderation_status IN ('accepted','geograph')) as imagecount";
		} else {
			$where_crit = '';
			$where_crit2 = '';
			$columns = '';
		}
		if ($isimgmap) {
			//yes I know the imagecount is possibly strange in the join, but does speeds it up, having it twice speeds it up even more! (by preference have the second one, speed wise!), also keeping the join on gridsquare_id really does help too for some reason! 
			$sql="select gs.*,gridimage_id,gi.realname as credit_realname,if(gi.realname!='',gi.realname,user.realname) as realname,title 
				from gridsquare gs
				left join gridimage gi ON 
				(imagecount > 0 AND gi.gridsquare_id = gs.gridsquare_id $where_crit2 AND imagecount > 0 AND gridimage_id = 
					(select gridimage_id from gridimage_search gi2 where gi2.grid_reference=gs.grid_reference 
					 $where_crit order by moderation_status+0 desc,seq_no limit 1)
				) 
				left join user using(user_id)
				where 
				CONTAINS( GeomFromText($rectangle),	point_xy)
				and percent_land<>0 
				group by gs.grid_reference order by y,x";
		} else {
			$sql="select gs.* $columns,
				sum(moderation_status='accepted') as accepted, sum(moderation_status='pending') as pending,
				DATE_FORMAT(MAX(if(moderation_status!='rejected',imagetaken,null)),'%d/%m/%y') as last_date
				from gridsquare gs
				left join gridimage gi on(gi.gridsquare_id = gs.gridsquare_id $where_crit2 )
				where 
				CONTAINS( GeomFromText($rectangle),	point_xy)
				and percent_land<>0 
				group by gs.gridsquare_id order by y,x";
		}
		$recordSet = &$db->Execute($sql);
		while (!$recordSet->EOF) 
		{
			$gridx=$recordSet->fields['x'];
			$gridy=$recordSet->fields['y'];

			$posx=$gridx-$this->map_x;
			$posy=($top-$bottom) - ($gridy-$bottom);
			$recordSet->fields['geographs'] = $recordSet->fields['imagecount'] - $recordSet->fields['accepted'];
			$grid[$posx][$posy]=$recordSet->fields;
			
			$recordSet->MoveNext();
		}
		$recordSet->Close();

		if ($memcache->valid)
			$memcache->name_set($mnamespace,$mkey,$grid,$memcache->compress,$mperiod);
		
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

/**
 * Draw a GlowString at the specified location 
 */
	
function imageGlowString($img, $font, $xx, $yy, $text, $color) {
	$width = imagefontwidth($font)*strlen($text);
	$height = imagefontheight($font);
	
	$text_image = imagecreatetruecolor($width, $height);

		$white = imagecolorallocate ($text_image, 255, 255, 255);
		$gray = imagecolorallocate ($text_image, 80, 12, 200);

		imagestring($text_image, $font, 0, 0, $text, $gray);

	$out_image = imagecreatetruecolor($width+6, $height+6);

		$white = imagecolorallocate ($out_image, 255, 255, 255);
		$black = imagecolorallocate ($out_image, 70, 70, 0);

		imagefill($out_image, $width, $height, $white);
		$white = imagecolortransparent($out_image, $white);

    $dist = 1;
    $numelements = 9;
    for ($x =-1; $x < $width+1; ++$x) 
        for ($y =-1; $y < $height+1; ++$y) {
            $newr = 0;
            $newg = 0;
            $newb = 0;

            for ($k = $x - 1; $k <= $x + 1; ++$k)
                for ($l = $y - 1; $l <= $y + 1; ++$l) {
                    $colour = imagecolorat($text_image, $k, $l);
                    
                    $newr += ($colour >> 16) & 0xFF;
                    $newg += ($colour >> 8) & 0xFF;
                    $newb += $colour & 0xFF;
                }

            $newcol = imagecolorclosest($out_image, 255-$newr/$numelements, 255-$newg/$numelements, 255-$newb/$numelements);

            imagesetpixel($out_image, $x+3, $y+3, $newcol);
        }

	imagestring($out_image, $font, 3, 3, $text, $black);

	imagecopymerge($img, $out_image, $xx-3, $yy-3, 0, 0, $width+6, $height+6,90);
	

	
	imagedestroy($text_image);
	imagedestroy($out_image);
}



function rectinterrect($a1,$a2) {
	return !($a1[0] > $a2[2] || $a1[2] < $a2[0] ||
	         $a1[1] > $a2[3] || $a1[3] < $a2[1]);
	#$xl = max($a1[0], $a2[0]);
	#$xr = min($a1[2], $a2[2]);
	#$yt = max($a1[1], $a2[1]);
	#$yb = min($a1[3], $a2[3]);
	#return $yt <= $yb && $xl <= $xr;
}

?>
