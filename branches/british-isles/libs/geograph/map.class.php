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
		if (!empty($this->topicId))
			$token->setValue("f",  $this->topicId);
		if (!empty($this->tagId))
			$token->setValue("a",  $this->tagId);
		if (!empty($this->searchId))
			$token->setValue("i",  $this->searchId);
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
				if ($token->hasValue("f")) {
					$this->transparent = true; //TODO - this should be better!
					$this->topicId = $token->getValue("f");
				}
				if ($token->hasValue("a")) {
					$this->transparent = true; //TODO - this should be better!
					$this->tagId = $token->getValue("a");
				}
				if ($token->hasValue("i")) {
					$this->transparent = true; //TODO - this should be better!
					$this->searchId = $token->getValue("i");
				}
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
		$db=&$this->_getDB(true);

split_timer('map'); //starts the timer
	
		//convert pixel pos to internal coordinates
		$x_km=$this->map_x + floor($x/$this->pixels_per_km);
		$y_km=$this->map_y + floor($y/$this->pixels_per_km);
		
		$row=$db->GetRow("select reference_index,grid_reference from gridsquare where CONTAINS( GeomFromText('POINT($x_km $y_km)'),point_xy )");
			
		if (!empty($row['reference_index'])) {
			$this->gridref = $row['grid_reference'];
			$this->reference_index = $row['reference_index'];
		} else {
			if (!empty($this->reference_index)) {
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
		
split_timer('map','getGridRef',"$x, $y"); //logs the wall time
		
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

split_timer('map'); //starts the timer
		
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
		
		if (!empty($this->topicId)) {
			$palette .= "_t{$this->topicId}";
		}
		if (!empty($this->tagId)) {
			$palette .= "_a{$this->tagId}";
		}
		if (!empty($this->searchId)) {
			$palette .= "_i{$this->searchId}";
		}
		
		$extension = ($this->pixels_per_km > 40 || $this->type_or_user < -20)?'jpg':'png';
		
		$file="detail_{$this->map_x}_{$this->map_y}_{$this->image_w}_{$this->image_h}_{$this->pixels_per_km}_{$this->type_or_user}{$palette}.$extension";
		
		if (!empty($this->mapDateCrit)) {
			$file=preg_replace('/\./',"-{$this->mapDateStart}.",$file);
		}
		if (!empty($this->displayYear)) {
			$file=preg_replace('/\./',"-y{$this->displayYear}.",$file);
		}


split_timer('map','getImageFilename',"$file"); //logs the wall time

		return $dir.$file;
	}

	/**
	* calc filename to an image which can form the base of the map
	* @access public
	*/
	function getBaseMapFilename()
	{
		$root=&$_SERVER['DOCUMENT_ROOT'];
	
split_timer('map'); //starts the timer

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
		
split_timer('map','getBaseMapFilename',"$file"); //logs the wall time
		
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

split_timer('map'); //starts the timer
		
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
		header("Content-Length: $size");
		
		
		//header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1 
		//header("Cache-Control: post-check=0, pre-check=0", false); 
		//header("Pragma: no-cache");         

split_timer('map','returnImage',$file); //logs the wall time
		
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
	#MAP FIXING ACTIVITY  (via mapfix_log) 
			if ($this->type_or_user == -4
	#ADOPTION MAP
				|| $this->type_or_user == -20
	#ROUTE MAP
				|| $this->type_or_user == -12
	#RECENT ONLY MAP
				|| $this->type_or_user == -6) {

				$ok = $this->_renderImage();

	#GROUP DEPTH - (via gridimage_group/gridsquare_group_count) 
			} elseif ($this->type_or_user == -3
	#PHOTO VIEWING  (via gridimage_log) 
				|| $this->type_or_user == -5
	#CENTISQUARE DEPTH MAP
				|| $this->type_or_user == -8
	#PHOTO AGE MAP
				|| $this->type_or_user == -7
	#USER DEPTH MAP
				|| $this->type_or_user == -13
	#QUADS DEPTH MAP
				|| $this->type_or_user == -9) {
				
				$ok = $this->_renderDepthImage();

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
		//if ($ok) {
			$db=&$this->_getDB(false);
			
			$age = ($ok)?0:-1;

			$sql=sprintf("replace into mapcache set map_x=%d,map_y=%d,image_w=%d,image_h=%d,pixels_per_km=%f,type_or_user=%d,palette=%d,age=%d",$this->map_x,$this->map_y,$this->image_w,$this->image_h,$this->pixels_per_km,$this->type_or_user,$this->palette,$age);

			$db->Execute($sql);
		//}
		return $ok;
	}
	
	/**
	* create basemap, save as gd image and return the image resource
	* @access private
	*/
	function& _createBasemap($file)
	{
	
split_timer('map'); //starts the timer

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
		$land[-1]=imagecolorallocate($img, $rmin,$gmin,$bmin);
		
		//paint the land
		$db=&$this->_getDB(true);
			
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
		if (!empty($recordSet))
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

split_timer('map','_createBasemap-sized',$file); //logs the wall time
			
			return $resized;
		}
		else
		{
			//image is correct size, save it and return
			imagegd($img, $file);

split_timer('map','_createBasemap',$file); //logs the wall time

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
		$db=&$this->_getDB(true);
		
split_timer('map'); //starts the timer

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

split_timer('map','needUserTile',$user_id); //logs the wall time

		
		return !empty($id);
	}

	/**
	* render the image to cached file if not already available
	* @access private
	*/
	function _renderImage()
	{
		global $CONF;
		static $counter = 0;
		
		$root=&$_SERVER['DOCUMENT_ROOT'];
		
		$ok = true;
		
		//first of all, generate or pull in a cached based map
		$basemap=$this->getBaseMapFilename();
		if (!empty($this->transparent)) 
		{
			$img=imagecreate($this->image_w,$this->image_h);
			$colBackground=imagecolorallocate($img, 255,255,255);
			imagecolortransparent($img,$colBackground);
		} 
		elseif ($this->caching && @file_exists($root.$basemap))
		{
			split_timer('map'); //starts the timer

			//load it up!
			$img=imagecreatefromgd($root.$basemap);

			split_timer('map','loadbasemap',$basemap); //logs the wall time

		}
		else
		{
			//we need to generate a basemap
			$img=&$this->_createBasemap($root.$basemap);
		}
		
		if (!$img) {
			return false;
		}
		
		split_timer('map'); //starts the timer
		
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
		$db=&$this->_getDB(true);
		
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
		
		if (empty($this->transparent)) {
			//setup ready to plot squares
			$this->_plotGridLines($img,$scanleft,$scanbottom,$scanright,$scantop,$bottom,$left,true);
		}
		
		$rectangle = "'POLYGON(($scanleft $scanbottom,$scanright $scanbottom,$scanright $scantop,$scanleft $scantop,$scanleft $scanbottom))'";
				
		if (!empty($this->type_or_user)) {
			if ($this->type_or_user == -10) {
				//we want a blank map!
				$sql = "select 0 limit 0";
			} elseif ($this->type_or_user == -4) {
				//todo doesnt use the where clause!
				$sql="select x,y,max(created) > date_sub(now(),interval 30 day) as has_geographs from gridsquare3
					inner join mapfix_log using (gridsquare_id)  
					group by gridsquare_id";
			} else {
				if ($this->pixels_per_km<40) {
					
					if ($this->type_or_user == -12) {
						//todo doesnt use the where clause!
						if (!empty($this->searchId)) {
							
							require_once('geograph/searchcriteria.class.php');
							require_once('geograph/searchengine.class.php');

							$engine = new SearchEngine($this->searchId);
							if (empty($engine->criteria)) {
								print "Invalid search";
								exit;
							}
							$engine->criteria->getSQLParts();
							if (!empty($engine->criteria->sphinx['no_legacy']) || empty($engine->criteria->sphinx['compatible'])) {
								print "Unable to run this search (no sphinx)";
								exit;
							}
							extract($engine->criteria->sql,EXTR_PREFIX_ALL^EXTR_REFS,'sql');

							if (preg_match("/(left |inner |)join ([\w\,\(\) \.\'!=]+) where/i",$sql_where,$matches)) {
								$sql_where = preg_replace("/(left |inner |)join ([\w\,\(\) \.!=\']+) where/i",'',$sql_where);
								$sql_from .= " {$matches[1]} join {$matches[2]}";
							}

							if (preg_match("/group by ([\w\,\(\) ]+)/i",$sql_where)) {
								print "Unable to run on this search (special search)";
								exit;
							}

							if (!empty($sql_where)) {
								$sql_where = "AND $sql_where";
								$engine->islimited = true;
							} else {
								print "Unable to run on this search (no filter)";
								exit;
							}

							if (strpos($sql_where,'gs') !== FALSE) {
								$sql_where = str_replace('gs.','gi.',$sql_where);
							}
							if (strpos($sql_from,'gs') !== FALSE) {
								$sql_from = str_replace('gs.','gi.',$sql_from);
							}
		
							$sql = "select x,y,1 as has_geographs from gridimage_search as gi $sql_from where 1 $sql_where group by x,y order by null";
							
						} elseif (!empty($this->tagId)) {
							$sql="select x,y,1 as has_geographs from gridimage_tag inner join gridimage_search using (gridimage_id) where status = 2 and tag_id = {$this->tagId} group by x,y order by null";
						} elseif ($this->topicId == -1) {
							$sql="select x,y,1 as has_geographs from gridimage_post inner join gridimage_search using (gridimage_id) group by x,y order by null";
						} else {
							$sql="select x,y,1 as has_geographs from gridimage_post inner join gridimage_search using (gridimage_id) where topic_id = {$this->topicId} group by x,y order by null";
						}
					} elseif ($this->type_or_user == -6) {
						$sql="select x,y,gridsquare_id,has_recent as has_geographs from gridsquare where 
							CONTAINS( GeomFromText($rectangle),	point_xy)
							and imagecount>0";					
					} else {//type_or_user > 0
						$sql="select x,y,sum(moderation_status = 'geograph') as has_geographs from gridimage_search where 
							CONTAINS( GeomFromText($rectangle),	point_xy) and
							user_id = {$this->type_or_user} group by x,y";
					}
				} elseif ($this->type_or_user == -20) {
				
					#$hectad_assignment_id = $db->getOne("SELECT * FROM hectad_assignment WHERE status = 'accepted' AND hectad = '$hectad'");
				
					$table = $CONF['db_tempdb'].".gi_render$counter"; $counter++;
					
					//TODO - doesnt cope with multiple assignments for a given hectad!
					$sql="CREATE TEMPORARY TABLE $table ENGINE HEAP
						SELECT gi.gridimage_id,x,y,user_id,1 AS has_geographs 
						FROM gridimage_search gi
						INNER JOIN gridsquare_assignment ga ON (gi.gridimage_id = ga.gridimage_id)
						WHERE CONTAINS( GeomFromText($rectangle),	point_xy)";
					$db->Execute($sql);

					$sql="INSERT INTO $table 
						SELECT gridimage_id,x,y,user_id,(moderation_status = 'geograph') AS has_geographs
						FROM gridimage_search WHERE 
						CONTAINS( GeomFromText($rectangle),	point_xy)
						AND ftf <= 1
						ORDER BY moderation_status+0 DESC,seq_no";
					$db->Execute($sql);
				
				
					$sql="ALTER IGNORE TABLE $table ADD PRIMARY KEY (x,y)";
					$db->Execute($sql);
					
					$sql="SELECT x,y,has_geographs,user_id,gridimage_id
						FROM $table";
				
				} else {
					if ($this->type_or_user == -6) {
						$crit = "imagetaken > DATE(DATE_SUB(NOW(), INTERVAL 5 YEAR))";
					} else {//type_or_user > 0
						$crit = "user_id = {$this->type_or_user}";
					}
					$table = $CONF['db_tempdb'].".gi_render$counter"; $counter++;
					$sql="CREATE TEMPORARY TABLE $table ENGINE HEAP
						SELECT gridimage_id,x,y,user_id,moderation_status FROM gridimage_search WHERE 
						CONTAINS( GeomFromText($rectangle),	point_xy) AND $crit
						ORDER BY moderation_status+0 DESC,seq_no";
					$db->Execute($sql);

					$sql="ALTER IGNORE TABLE $table ADD PRIMARY KEY (x,y)";
					$db->Execute($sql);
					
					//if the image is a sup then there cant be any geos, due to sort order!
					$sql="SELECT x,y,(moderation_status = 'geograph') as has_geographs,user_id,gridimage_id
						FROM $table";
				}
			}
		} else {
			$number = !empty($this->minimum)?intval($this->minimum):0;
			if ($this->pixels_per_km<40) {
				$sql="select x,y,gridsquare_id,has_geographs from gridsquare where 
					CONTAINS( GeomFromText($rectangle),	point_xy)
					and imagecount>$number";
			} else {
				$table = $CONF['db_tempdb'].".gi_render$counter"; $counter++;
				
				if (true) {
					$sql="CREATE TEMPORARY TABLE $table ENGINE HEAP
						SELECT gridimage_id,grid_reference,moderation_status,user_id,x,y FROM gridimage_persquare WHERE 
						CONTAINS( GeomFromText($rectangle),	point_xy)";
					$db->Execute($sql);
				} else {
					$sql="CREATE TEMPORARY TABLE $table ENGINE HEAP
						SELECT gridimage_id,grid_reference,moderation_status,user_id,x,y FROM gridimage_search WHERE 
						CONTAINS( GeomFromText($rectangle),	point_xy)
						AND ftf <= 1
						ORDER BY moderation_status+0 DESC,seq_no";
					$db->Execute($sql);

					$sql="ALTER IGNORE TABLE $table ADD PRIMARY KEY (x,y)";
					$db->Execute($sql);
				}
				
				if ($number) {
					$sql="SELECT gridsquare.x,gridsquare.y,has_geographs,user_id,gridimage_id
					FROM gridsquare 
					INNER JOIN $table USING (grid_reference)
					WHERE 
						CONTAINS( GeomFromText($rectangle),	point_xy)
						AND imagecount>$number";
				} else {
					$sql="SELECT x,y,(moderation_status = 'geograph') as has_geographs,user_id,gridimage_id
					FROM $table";
				}
			}
		}
		$prev_fetch_mode = $db->SetFetchMode(ADODB_FETCH_ASSOC);
		$recordSet = &$db->Execute($sql);
		$db->SetFetchMode($prev_fetch_mode);
		while (!$recordSet->EOF) 
		{
			$gridx=$recordSet->fields['x'];
			$gridy=$recordSet->fields['y'];

			$imgx1=($gridx-$left) * $this->pixels_per_km;
			$imgy1=($this->image_h-($gridy-$bottom+1)* $this->pixels_per_km);

			$imgx1=round($imgx1);
			$imgy1=round($imgy1);

			$imgx2=$imgx1 + $this->pixels_per_km;
			$imgy2=$imgy1 + $this->pixels_per_km;
				
			$color = ($recordSet->fields['has_geographs'])?$colMarker:$colSuppMarker;
				
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
			elseif ($recordSet->fields['gridimage_id']) 
			{
				//thumbnail

				$gridimage=new GridImage;
				$gridimage->fastInit($recordSet->fields);

				$photo=$gridimage->getSquareThumb($this->pixels_per_km);
				if (!is_null($photo))
				{
					imagecopy ($img, $photo, $imgx1, $imgy1, 0,0, $this->pixels_per_km,$this->pixels_per_km);
					imagedestroy($photo);

				//	imagerectangle ($img, $imgx1, $imgy1, $imgx2, $imgy2, $colBorder);
				//	imagerectangle ($img, $imgx1+1, $imgy1+1, $imgx2-1, $imgy2-1, $colBorder);

					if (!$recordSet->fields['has_geographs']) {
                                               imagefilledrectangle ($img, $imgx1+2, $imgy1+3, $imgx1+6, $imgy1+5, $colSuppMarker);
                                               imagefilledrectangle ($img, $imgx1+3, $imgy1+2, $imgx1+5, $imgy1+6, $colSuppMarker);					
					}
				} else {
					$ok = false;
				}

			}
			
			
			$recordSet->MoveNext();
		}
		if (!empty($recordSet))
			$recordSet->Close(); 

		if ($img) {
			//ok being false isnt fatal, as we can create a tile, however we should use it to try again later!
			
			//plot grid square?
			if ($this->pixels_per_km>=0)
			{
				$this->_plotGridLines($img,$scanleft,$scanbottom,$scanright,$scantop,$bottom,$left);
			}

			if ($this->pixels_per_km>=1  && $this->pixels_per_km<=40 && isset($CONF['enable_newmap']) && empty($this->transparent))
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
			
			split_timer('map','_renderImage',$target); //logs the wall time

			
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
				case $o == 1: $r=255; $g=255; $b=0; break; 
				case $o == 2: $r=255; $g=196; $b=0; break; 
				case $o == 3: $r=255; $g=132; $b=0; break; 
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
			
			split_timer('map'); //starts the timer
			
			$img=imagecreatefromgd($root.$basemap);
			
			split_timer('map','loadbasemap',$basemap); //logs the wall time
		} else {
			$img=&$this->_createBasemap($root.$basemap);
		}
		
		if (!$img) {
			return false;
		}
		
		$db=&$this->_getDB(true);

split_timer('map'); //starts the timer

		if ($this->type_or_user == -7 || $this->type_or_user == -8 || $this->type_or_user == -13) {

			set_time_limit(600);

			$counts = range(0,130);
		} else {
			if ($this->type_or_user == -3) {
				$sql="select imagecount from gridsquare_group_count group by imagecount";
			} elseif ($this->type_or_user == -5) {
				$sql="select distinct round(log10(hits)*2) from gridsquare_log order by hits";
			} else {
				$sql="select imagecount from gridsquare group by imagecount";
			}
			$counts = $db->cacheGetCol(3600,$sql);
		}
		$colour=array();
		$last=$lastcolour=null;
		for ($p=0; $p<count($counts); $p++)
		{
			$o = $counts[$p];
			//standard green, yellow => red
			switch (true) {
				case $o == 0: $r=$this->colour['land'][0]; $g=$this->colour['land'][1]; $b=$this->colour['land'][2]; break; 
				case $o == 1: $r=255; $g=255; $b=0; break; 
				case $o == 2: $r=255; $g=196; $b=0; break; 
				case $o == 3: $r=255; $g=132; $b=0; break; 
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
	
		if ($this->type_or_user == -13) {
			$sql="select x,y,0 as 'd',max(ftf) as imagecount
				from gridimage_search
				group by x,y
				 having imagecount>0
				 order by null";

		} elseif ($this->type_or_user == -9) {
			$sql="select x,y,gs.gridsquare_id,(count(distinct nateastings DIV 500, natnorthings DIV 500) - (sum(nateastings = 0) > 0) )  as imagecount
				from 
				gridsquare gs 
				inner join gridimage gi using(gridsquare_id)
				where CONTAINS( GeomFromText($rectangle),	point_xy)
				and moderation_status in ('accepted','geograph')
				group by gi.gridsquare_id ";

		} elseif ($this->type_or_user == -8) {
			$sql="select x,y,gs.gridsquare_id,(count(distinct nateastings DIV 100, natnorthings DIV 100) - (sum(nateastings = 0) > 0) )  as imagecount
				from 
				gridsquare gs 
				inner join gridimage gi using(gridsquare_id)
				where CONTAINS( GeomFromText($rectangle),	point_xy)
				and moderation_status in ('accepted','geograph')
				group by gi.gridsquare_id ";

		} elseif ($this->type_or_user == -7) {
			$sql="select x,y,gs.gridsquare_id,ceil(datediff(now(),max(imagetaken)) / 356) as imagecount
				from 
				gridsquare gs 
				inner join gridimage gi using(gridsquare_id)
				where CONTAINS( GeomFromText($rectangle),	point_xy)
				and moderation_status in ('accepted','geograph')
				group by gi.gridsquare_id ";

		} elseif ($this->type_or_user == -3) {
			$sql="select x,y,gs.gridsquare_id,count(distinct label) as imagecount
				from 
				gridsquare gs 
				inner join gridimage2 gi using(gridsquare_id)
				inner join gridimage_group gg using(gridimage_id)
			group by gi.gridsquare_id "; #where CONTAINS( GeomFromText($rectangle),	point_xy) 
			
			$sql="select * from gridsquare_group_count";

		} elseif ($this->type_or_user == -5) {
			$sql="select x,y,gs.gridsquare_id,round(log10(hits)*2) as imagecount
				from
				gridsquare gs
				inner join gridsquare_log using (gridsquare_id)"; 

		} elseif (!empty($this->mapDateCrit)) {
			$sql="select x,y,gs.gridsquare_id,count(*) as imagecount
				from 
				gridsquare gs 
				inner join gridimage gi using(gridsquare_id)
				where CONTAINS( GeomFromText($rectangle),	point_xy)
				and submitted < '{$this->mapDateStart}'
				and moderation_status in ('accepted','geograph')
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
		if (!empty($recordSet))
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
			
			if ($this->pixels_per_km>=1  && $this->pixels_per_km<40 && isset($CONF['enable_newmap']) && empty($this->transparent)) {
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
			
split_timer('map','_renderDepthImage',$target); //logs the wall time

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
		if (!empty($this->transparent)) {
			$img=imagecreate($this->image_w,$this->image_h);
			$colBackground=imagecolorallocate($img, 255,255,255);
			imagecolortransparent($img,$colBackground);
		} elseif ($this->caching && @file_exists($root.$basemap)) {
		
			split_timer('map'); //starts the timer
			
			$img=imagecreatefromgd($root.$basemap);
			
			split_timer('map','loadbasemap',$basemap); //logs the wall time
		} else {
			$img=&$this->_createBasemap($root.$basemap);
		}

		if (!$img) 
			return false;

		$colMarker=imagecolorallocate($img, 255,0,0);
		$colSuppMarker=imagecolorallocate($img,236,206,64);
		$colBorder=imagecolorallocate($img, 255,255,255);
		$black = imagecolorallocate ($img, 70, 70, 0);

		$db=&$this->_getDB(true);

split_timer('map'); //starts the timer

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

		if (empty($this->transparent)) {
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
			$sql="select x,y,sum(submitted > '{$this->mapDateCrit}')
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
		if (!empty($recordSet))
			$recordSet->Close(); 

		if ($img) {
			if (empty($this->transparent)) {
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
			
split_timer('map','_renderDateImage',$target); //logs the wall time
	
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
		global $CONF;
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
		
		if (true) {
			$width = imagesx($img);
			$height = imagesy($img);
			$imgTC = imagecreatetruecolor($width, $height);
			imagecopy($imgTC, $img, 0, 0, 0, 0, $width, $height);

			imagedestroy($img);
			$img =& $imgTC;
		}
		
		$target=$this->getImageFilename();
		
		$colMarker=imagecolorallocate($img, 255,0,0);
		$colBorder=imagecolorallocate($img, 255,255,255);
		
		//figure out what we're mapping in internal coords
		$db=&$this->_getDB(true);
		
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
			$year = $this->type_or_user * -1;
			
			if ($year >= 2007) {
				//tofix - removed the $rectangle - pretty much we only do national xmas maps anyway...
				$sql="select x,y,gi.gridimage_id,gi.user_id,realname,gi.title,gi.grid_reference from gridimage_search gi
				left join gridimage_snippet gs using (gridimage_id)
				left join snippet s using (snippet_id)
				where imagetaken = '$year-12-25'
				and ( gi.imageclass = 'christmas day $year' OR s.title = 'midday christmas $year') 
				group by gi.gridimage_id
				order by rand()";
			} else {
				$sql="select x,y,gi.gridimage_id,gi.user_id,realname,title,grid_reference from gridimage_search gi
				where 
				CONTAINS( GeomFromText($rectangle),	point_xy) and imagetaken = '$year-12-25'
				 order by ( (gi.title LIKE '%xmas%' OR gi.comment LIKE '%xmas%' OR gi.imageclass LIKE '%xmas%') OR (gi.title LIKE '%christmas%' OR gi.comment LIKE '%christmas%' OR gi.imageclass LIKE '%christmas%') ), rand()";
			} 
		} elseif (false) {
			$sql="select x,y,gi.gridimage_id,gi.user_id,realname,title,grid_reference from gridimage_search gi
			where 
			CONTAINS( GeomFromText($rectangle),	point_xy)
			and seq_no = 1 group by FLOOR(x/10),FLOOR(y/10) order by rand() limit 600";
			#inner join gridimage_post gp on (gi.gridimage_id = gp.gridimage_id and gp.topic_id = 1006)
			
			
		
		} elseif (1) {
			//temp test - but inaccessible
			#$sql="select x,y,gi.gridimage_id,gi.user_id,realname,title,grid_reference from gridimage_search gi
			#where gridimage_id in (80343,74737,74092,84274,80195,48940,46618,73778,47029,82007,39195,76043,57771,28998,18548,12818,7932,81438,16764,84846,73951,79510,15544,73752,86199,4437,87278,53119,29003,36991,74330,29732,16946,10613,87284,52195,41935,26237,30008,10252,62365,83753,67060,34453,20760,26759,59465,118,12449,4455,46898,12805,87014,401,36956,8098,44193,63206,42732,26145,86473,17469,3323,26989,3324,40212,63829,30948,165,41865,36605,25736,68318,26849,51771,30986,27174,37470,31098,65191,44406,82224,71627,22968,59008,35468,7507,53228,80854,10669,47604,75018,42649,9271,1658,11741,60793,78903,22198,7586,88164,12818,14981,21794,74790,3386,40974,72850,77652,47982,39894,38897,25041,81392,63186,81974,41373,86365,44388,80376,13506,42984,45159,14837,71377,35108,84318,84422,36640,2179,22317,5324,32506,20690,71588,85859,50813,19358,84848,18141,78772,21074,13903,39376,45795,88385,55327,907,37266,82510,78594,17708,84855,7175,85453,23513,18493,68120,26201,18508,32531,84327,88204,55537,41942,47117,22922,22315,46412,88542,46241,67475,63752,63511,98) order by rand()";
		
		
		
			//select count(*),group_concat(substring_index(url,'/',-1) order by baysian) from gallery_image where baysian > 4 limit 1\G
			$ids = '699660,1047342,2782603,2787182,306970,847625,373398,1088465,425265,1688160,343229,2783908,1109962,180971,195466,271205,186420,1040650,2255330,1601117,1101116,614340,2235299,2780710,1039479,384580,1015491,2783710,1609416,456604,296018,2376890,953784,2656243,1082957,2779852,2785847,2258753,1938411,242390,319597,2222899,908634,100147,797780,2560,2789164,2787994,2788106,512364,152207,436794,471557,649882,56,18320,2746881,2595703,2554991,2492216,2497732,2313658,2179284,2181690,2167744,2108712,2057546,1720161,1704046,1592638,1583136,1392214,1358072,1328224,1293473,1259170,1151715,1075402,1062306,1044247,1015596,771735,762780,731739,646844,630165,569251,491737,457764,439408,371273,353247,139039,229506,110869,110262,101359,1022390,876039,875121,581545,1108587,343600,7684,302987,358891,1116063,2765140,2727727,992788,481701,8,493668,671289,622265,325464,21638,431523,14913,93462,161671,120000,62895,93952,74038,7139,55357,45682,135532,403975,656352,16403,282204,477547,53634,168445,2799,104189,627885,631112,472971,448280,343598,2328985,483989,349897,1040073,965776,805945,1688754,2258168,2271975,2809903,683771,203208,1389999,2752745,2753250,684128,768859,127023,669428,300236,170185,2737941,2730773,2693792,2482622,2374165,2340659,2327704,2284123,2226009,2207766,2214400,2149275,2113842,2105666,1945341,1927195,1811775,1818967,1783653,1770167,1721268,1697814,1644464,1613182,1590730,1594080,1594995,1560124,1511367,1464930,1371221,1282570,1214428,1152298,1154752,1114074,1108211,1102829,1032488,1006473,1006514,973215,922592,917061,909599,800428,804612,782403,656013,639632,636807,592522,570963,541646,503498,451474,446176,418057,405055,320944,314051,218351,217086,199955,177667,177755,174418,170391,130503,101306,1089320,777259,1152589,1072504,443246,238828,263478,575762,995741,1127749,834400,469760,2549765,808157,133762,551029,444674,478690,590613,29919,448754,81799,333568,108306,135199,89457,127074,136269,46062,99433,176365,180686,41050,30966,26169,42534,9656,60560,190016,164608,92533,14038,90642,70008,114974,347940,624520,328050,567771,527413,427773,130193,601187,96244,16055,625848,619512,608066,588320,355817,302473,226477,127547,2343314,923232,708455,106593,621559,95200,2468783,660224,1280485,1627328,2858301,2855658,2830423,2822077,123456,672765,10570,542205,149304,947101,897161,1320716,667620,494982,42842,624629,265332,257426,283554,91275,107657,2765440,2765273,2686277,2674903,2671658,2663607,2607294,2548478,2536439,2540793,2528177,2505724,2501284,2500883,2425931,2420673,2312431,2289036,2288936,2202224,2144684,2139196,2136458,2126050,2075183,2051119,1998966,2000271,1967794,1964064,1949000,1938257,1913580,1873341,1861721,1853416,1859389,1804248,1786804,1780211,1784681,1770834,1618361,1569216,1574095,1573538,1512498,1507519,1415710,1362918,1361394,1337105,1334670,1312934,1312278,1303391,1300062,1288238,1291684,1276846,1269102,1263034,1240629,1240484,1204412,1193880,1180584,1181499,1181115,1161202,1140442,1127977,1064369,1066339,1069135,1056020,1051590,1034984,1021709,1024039,1016050,971961,957745,939439,933008,919074,920170,901766,906655,893648,892558,893252,865424,847135,846563,837623,813850,815985,768618,750366,727450,724070,708133,698017,703105,683493,681876,671572,642079,630720,614335,586937,573908,545098,547666,518784,466344,446678,426734,404605,352495,353651,333711,332790,322237,317131,299503,258365,256633,229528,209835,174372,170480,161911,144299,143189,128012,106035,98790,71782,272992,225182,970597,117638,1140993,131183,8694,509339,140391,129190,139514,105332,16950,134282,132102,126493,101589,47112,12969,172135,117248,953060,927768,542752,2683913,2711093,836599,849428,59517,2789432,638256,423817,203815,2513148,2423620,2413910,2352157,2223292,2067695,1688906,1654865,1560527,1425829,1158460,1072833,844394,721212,709934,652287,631297,614553,566199,529175,429691,295447,108914,101323,2296143,94513,1027464,405436,1118011,1094237,1146610,1330958,404667,337829,124800,2684336,1236089,206700,378394,397219,168397,11985,181474,587748,457241,130732,190080,27722,6017,165770,553797,387252,335145,2749072,667524,663523,2735741,2714308,1084125,2797808,150584,46489,311779,2577190,2274143,2237135,2210618,2021998,1967228,1833076,1810274,1763600,1628081,1624647,1546797,1547810,1553280,1521543,1488119,1446168,1167290,1140044,1090618,1065280,1005400,959772,783744,754035,734007,689842,601808,594926,569869,512477,511604,402620,311949,236050,213378,214192,126231,455949,648303,210140,412327,197028,911863,1059676,2756341,66,259066,137326,143243,526291,458735,251099,76205,136084,12851,163405,72833,126340,103364,136680,28896,20334,40458,113725,180436,129327,205911,580730,259946,123986,479614,532832,112926,2788886,83253,831210,1781154,780820,463159,269119,39680,2510396,2469931,2299684,1625914,1155164,1162996,630195,470165,105254,691778,956530,1138057,735083,2670149,2049159,2684385,2684332,1081887,584101,132030,206349,95257,2708101,2854464,2747357,2740247,569896,332123,287071,253391,131934,106589,163368,2772221,2704105,2694832,2608818,2218693,2149692,2111685,1938125,1811804,1816294,1815870,1766714,1756537,1706776,1676504,1643613,1636428,1546962,1455654,1354612,1312480,1287617,1295126,1290422,1285209,1263735,1176310,1071956,1074617,1046650,1052953,1029624,997391,1005539,1005224,897704,890275,845487,852233,837754,791423,794861,752732,736728,688457,693509,684750,659356,640126,644518,638796,635325,632825,630440,606440,607930,611312,604993,596563,578010,564869,553681,534571,526956,516505,504196,479796,465683,447958,453063,444945,442190,373111,373020,367598,349207,340536,341745,342386,318498,308582,294936,294674,271298,274342,267464,263802,245629,239191,213286,209804,180949,178208,177234,175255,173979,168359,164888,131661,132887,130040,256724,122825,1095309,484161,499034,988400,125968,468675,129329,969587,282025,401350,333227,1117939,1111474,758061,453923,123481,102545,31999,13137,24504,92955,17111,39338,203062,376007,504643,2668290,1962571,1322344,1189702,1062570,448822,395864,274056,266455,227224,101352,102511,949082,761496,822704,1054660,2683984,1630374,2489145,181533,93087,83416,50191,161105,15472,172068,136646,2782266,1553126,1015619,543238,153469,20982';
		
		
			//select count(*),group_concat(substring_index(url,'/',-1) order by baysian) from gallery_image where baysian > 3 limit 1\G
			$ids = '2508280,2714308,2616531,2656243,2583753,2258168,2787627,1714967,387783,1335507,2782957,529843,2773582,2748827,2771049,1500287,2770425,2735741,2271975,2380813,2314442,2468783,2420121,95200,17633,117857,372064,125879,618796,640001,640550,649876,634036,636023,639918,630261,640483,638100,641134,601,3324,23896,44952,52740,67554,82264,27966,243818,495224,525328,611217,20922,57291,121989,182846,266211,454878,557236,229857,83253,436429,367928,941,243429,458660,347758,16977,327716,348088,61648,141023,625318,93068,456385,626598,649525,514671,646066,68345,121861,517614,621559,176763,336842,667129,612127,198535,199910,198594,221422,268047,411436,664700,416124,428728,472802,394511,319598,106593,211568,336566,234134,519627,269730,158891,290740,592742,245966,220151,393750,386495,425845,358168,635173,649859,675126,660224,674647,662510,684787,699660,722930,708455,711296,731924,737694,746412,757198,766180,771385,784250,797780,805945,809690,827526,836599,847673,847625,858849,871875,881451,881893,896796,908634,908120,923232,927768,937005,945029,953784,967205,985344,989915,1000969,1015491,1016430,1024050,1030762,1047342,1051432,1056230,1074971,1070934,1093200,1106612,1688754,2343314,2761420,2719550,456482,2222899,2758569,2783710,2773272,2780710,2704894,2485426,2789954,2779852,2774993,2226934,2781327,2131441,1997402,9611,2367946,2776867,2784663,1533829,2782603,2786421,2279947,1094777,2777654,2788699,2550080,1506853,2769370,2781100,2199908,2055497,2789837,2788886,784421,2564633,2711093,2749072,2789971,2789982,2787084,2787188,2787217,2787252,2787275,2787337,2787858,2788424,2789199,2784406,2784430,2784455,2785050,1833566,112926,127547,138928,157180,181783,193985,202801,226477,237266,266366,284594,288824,310897,302473,314253,319597,329784,335145,343598,343229,355817,359202,367143,373239,387252,390381,409660,397749,417332,425265,428305,442245,440778,448280,456604,469360,472971,483989,483788,490322,497951,511756,515023,520177,529617,532832,542752,545872,553797,561589,567510,579117,580850,588320,593586,600114,608066,616855,619512,638224,631112,627885,625848,647771,2708101,1908040,1609416,2235299,479614,96914,144887,119861,189345,104189,5775,170732,69874,37264,165770,46477,2799,6017,16055,27722,30307,40123,50601,96244,123986,190080,195466,199582,120047,174728,6139,331966,410614,601187,602068,130193,427773,464439,597023,434313,187789,406875,473,168445,527413,130732,53634,387536,457241,53530,491084,285758,477547,211905,626420,567771,612413,282204,320831,428392,203152,436768,264936,425272,587748,519203,328050,196947,184431,491265,600231,512959,286983,259946,624520,545939,124463,654841,16403,633116,191505,601895,656352,347940,580730,238532,533856,290657,339142,547670,317703,665611,458069,355224,189791,581530,103320,114974,223939,556540,403975,480759,598067,262732,255499,166036,84836,95257,42772,2848,153863,102540,138958,112928,135532,180560,95084,70008,137829,205911,201631,129327,197907,45682,77838,55357,65218,90642,7139,14038,7586,96946,106535,74038,92533,4018,155326,51853,84086,186420,11760,93383,131267,136646,93952,172068,190685,183782,4995,62895,84483,88009,108559,40691,94046,167045,80143,137877,180436,74635,95455,183575,113725,203062,40458,195964,20334,169815,15472,116939,114874,126708,28896,181474,199685,188232,89885,136680,11985,120000,164608,103364,122773,158452,126340,185189,64483,107200,118484,168397,161570,161671,186235,37727,40145,42695,62372,76012,93294,111289,117248,133997,163121,172135,182941,85285,190016,60560,82525,139300,12969,39338,9656,88054,18197,47112,42534,206639,161105,26169,30966,41050,101589,126493,74317,132102,74877,32746,176538,19502,17111,101015,14412,123312,138488,130329,89413,50191,22683,42123,180686,134282,186204,16950,109795,105332,104016,139514,151059,171749,196117,197174,71761,47626,72833,138329,92081,176365,170098,83416,141297,109706,203096,99433,99924,1424,2127,109029,73716,93462,148183,163405,153864,20982,31,109272,11492,122847,206349,36433,154934,96826,46062,136269,127074,157177,79757,22733,52852,142991,88911,82033,92955,129190,118922,20833,97554,24504,120111,13137,43766,49930,40591,89457,31999,148078,156239,31755,73729,117644,135199,72085,13887,86067,168525,44592,123867,14913,65454,29749,181015,167776,205963,18785,16935,108306,102545,66125,93087,31659,12851,123481,136084,677301,225497,529084,233199,540350,333568,634545,556508,290102,102479,570739,397259,305085,305221,491448,660914,190938,349897,571606,633536,453923,179296,606487,140391,509339,76205,10991,325763,367894,464204,547588,2121,48935,80750,160212,618005,132030,188506,353411,37189,437573,323836,431523,669029,142904,207713,365005,81799,194044,223096,334380,86525,21638,629344,333044,606251,136337,448754,95147,251099,633561,39302,29919,325464,368261,510780,392163,306106,397219,379956,653988,74007,668064,458735,417642,181533,622265,270676,102635,482609,56540,463269,17517,25344,526291,341944,506195,553989,671289,248916,20543,590613,373398,478690,460563,176688,245103,586520,523665,553894,157556,274637,202089,143243,406144,7197,51521,221571,359944,373191,394239,247680,370392,444674,54650,515409,156664,180971,25894,365323,372383,493668,616402,568033,584101,551029,248121,378394,335943,532762,364049,117035,526423,331721,483703,612943,137326,317506,358888,41588,206700,579177,534802,211089,189505,614361,372681,435980,349883,378196,536087,296018,580634,259066,436419,133762,565667,156780,1088465,8,84,66,2489145,814101,1008879,1081887,1236089,1630374,1627328,2683984,2548508,2684336,2684332,2684385,124800,808157,137981,337829,2049159,481701,2670149,2549765,9153,1084125,1009353,404667,2710589,957159,992788,47517,2727727,2748112,2756341,2768489,1330958,2765140,2767083,1054660,822704,660740,735083,153469,1116063,758061,318593,10454,1059676,906620,987899,967300,1109962,303937,358891,1111474,8694,1146610,829263,680143,262301,916014,434574,387171,469760,834400,855755,761496,1117939,1094237,1127749,995741,568814,559502,54428,85954,1020668,726492,333227,1118011,1072678,993570,522947,175296,302987,405436,575762,870024,911863,7684,343600,51340,131183,197028,1140993,1108587,794288,1142757,1100581,999204,883303,1082957,72796,117638,1015544,646725,958015,262998,919626,642720,126686,227012,63964,7014,755293,953060,189469,401350,1101116,412327,786227,1029685,892998,465366,1027464,282025,263478,949082,1138057,39484,661144,969587,129329,1151610,1017851,1120541,863342,1108720,238828,443246,999672,14079,384580,544136,468675,899005,384826,306970,956530,35153,663523,35,970597,125968,988400,115984,168711,218540,591524,581545,180024,1154611,499034,875121,1072504,1093500,1152589,777259,1033097,449371,876039,578108,360686,935901,1022390,98703,691778,225182,844546,1071871,1076495,210140,831210,102511,153718,484161,647867,648303,1133732,377477,851553,1154798,137719,94513,203634,455949,974809,1099920,1095309,799900,272992,71782,303280,1089320,1138675,2296143,102714,100147,102718,100319,102795,100351,100420,98588,102863,98594,101306,102915,98790,101323,102967,98832,101352,102977,98901,101359,99132,101454,105404,99405,101601,106283,106022,102347,99422,102132,99423,99520,102365,99890,102515,108577,108598,108604,110193,108620,110262,108678,110334,110362,108914,110370,109099,109122,110532,110583,109378,110668,107212,109461,110795,109478,110869,107863,108203,109852,108215,109867,104656,105254,106035,111843,112170,122825,256724,146137,223458,229506,114899,115959,118585,118650,118775,118974,122941,122226,122776,121707,126231,128012,130040,128237,130503,128798,128810,132208,132887,131661,137162,139039,140394,142471,143189,145829,145951,144274,144299,147391,144760,150689,147902,148487,149567,149981,146936,151560,154600,159176,159781,162988,163078,167406,161911,164888,168359,170391,170480,171321,168258,171858,172171,173979,174418,170776,173059,174372,177755,175255,177234,177667,179810,180149,178208,180949,177047,186705,187588,191541,196866,198364,199818,196257,199955,200519,207464,207539,206263,209804,209835,214166,210337,208975,216978,217086,214192,218351,213286,213378,216513,219654,229528,224078,227224,236050,238906,239191,242255,242390,248659,245629,246571,256633,254644,254853,258365,260784,263802,267464,269487,266455,274056,271205,274342,271298,272124,275950,280435,282346,285192,285251,285752,288916,288301,290708,292280,292389,291483,295447,294324,294674,292690,292974,293111,294936,294947,297993,296508,298166,296703,295366,296062,299503,301769,300382,298836,305159,305212,306362,305747,308582,309290,311949,312028,309769,308502,308941,318420,317131,317266,317665,314051,319533,322237,325826,320167,318498,319125,320944,332790,336441,333711,337050,342386,338391,343793,341745,340297,340418,340536,342021,345780,349207,344005,350992,353247,353651,352302,352495,354143,362603,354509,358270,359339,364571,364612,370010,367598,371273,377699,375273,373020,373111,375557,379739,378509,387415,387502,395864,392191,394571,394533,399542,409392,409444,402620,403626,408262,404386,404605,405055,416970,417696,418057,415948,420964,424642,425205,422803,426734,429691,440535,438914,441446,439056,439082,437189,439408,442190,444945,446176,446678,453063,450372,447958,451384,451474,454348,452265,448822,466344,457764,471321,465683,470165,478471,482119,479796,480306,480477,480673,487874,490585,491737,485389,486117,486606,490223,487691,490164,496023,496335,501031,501206,503498,508226,503898,504196,513524,518784,516505,514768,511604,517340,512477,519453,524629,524812,528851,526808,529175,526956,529444,531319,524953,526061,532789,534571,535051,530926,536881,539758,540584,543012,543238,538493,543787,541646,544032,553756,546337,547666,551125,545098,549373,558252,553864,554921,553564,553681,558008,560320,562986,561322,564869,571737,566199,569251,566875,569842,569869,567773,570963,578010,574076,573309,573908,583230,583576,585356,586937,589334,592522,591059,586021,592847,596563,598900,594624,594884,594926,605810,601808,604993,611312,609311,607724,607930,606268,606440,616561,614553,612082,614335,612797,614340,618358,618570,615786,616649,617082,620470,621033,619416,623725,624445,622334,630376,630440,630720,629375,631075,627493,630165,630195,633872,632825,632869,634939,631297,635325,638796,636807,638203,640996,637708,638702,640863,646327,642079,644518,643023,639632,639927,640126,645714,651881,653938,652209,652287,652524,655250,646546,655454,646844,646906,660327,656013,658576,659356,656660,665585,667524,665109,671572,674675,673701,671292,675234,681876,683493,681376,689842,684750,691349,693509,688457,695994,697376,703105,698017,702854,709934,703080,706849,708133,708760,711452,713496,726012,724070,721212,728255,728389,730334,725675,726769,727450,731739,736728,740528,734007,734841,743875,745253,736302,750366,745044,746818,752732,755973,754035,764619,764799,768289,768618,771473,766707,762780,763410,778062,769726,781584,771735,782403,775347,791172,779356,783744,787847,789359,784885,785361,790477,794861,791423,789095,792771,796153,804612,800428,800714,815985,810096,813678,813766,813850,808248,829933,821530,822205,822878,830053,833783,833850,825446,830262,825858,831430,838411,843639,843734,848842,837623,841140,837754,846544,850860,846563,847135,844394,849520,844819,852233,852557,854118,845487,866678,858794,871723,865424,869386,875322,867475,867575,878419,879722,883147,887534,893252,884513,885319,890275,892558,893648,909174,909472,909599,906655,901766,897704,903339,906031,910888,910016,920170,922410,917061,922592,923402,919074,919341,926820,935106,930617,936983,933008,929782,939943,940049,942564,939390,939439,944571,949678,945129,959772,965216,957467,961349,957745,961822,954980,973215,968533,965075,965776,971961,971106,979854,984699,992508,982902,992540,985076,994884,998803,1005224,1005400,1005539,1006514,997391,1000297,1016050,1006473,1010018,1015619,1019968,1011144,1017531,1015596,1031513,1024039,1028429,1024951,1021709,1029624,1040650,1032488,1041568,1033991,1041939,1041947,1037537,1034984,1039479,1040073,1044247,1042178,1047315,1051590,1052953,1046650,1063976,1058324,1067524,1055273,1062306,1056020,1062570,1060102,1062810,1074617,1069135,1066339,1075402,1064369,1065280,1071956,1078784,1072833,1071139,1077734,1080341,1082857,1082865,1091205,1087644,1090618,1102829,1097263,1108211,1100704,1105530,1101422,1107079,1108608,1108865,1114074,1120093,1123703,1126907,1120543,1118420,1122908,1136344,1131265,1131620,1127977,1140442,1131982,1144694,1132913,1141650,1141782,1140044,1138869,1151715,1156204,1144174,1158460,1154752,1162996,1165257,1155164,1152298,1157815,1161202,1165784,1175039,1167036,1170752,1176310,1167290,1170975,1176807,1171214,1176820,1163652,1181115,1181499,1181787,1176208,1180066,1180584,1185717,1190082,1190304,1187632,1193880,1188744,1195313,1189702,1194206,1199333,1202939,1202947,1212055,1204412,1213182,1205453,1217298,1210363,1217434,1218235,1214428,1226794,1231191,1238226,1225392,1228044,1226576,1237681,1238477,1239562,1240484,1240629,1242443,1247585,1261799,1254873,1263034,1259170,1259952,1274329,1263735,1269102,1275915,1282570,1276846,1285209,1278226,1288061,1280485,1293473,1290422,1295126,1291684,1287617,1297727,1287802,1292698,1288238,1293053,1300062,1306377,1303391,1311320,1310600,1316043,1312278,1312480,1312934,1318829,1305926,1314096,1322344,1319582,1328224,1346880,1334670,1340477,1337105,1358072,1352361,1354612,1347490,1355866,1357143,1357195,1357574,1361394,1355014,1359226,1356936,1362918,1363336,1371221,1389239,1386804,1392214,1402794,1406493,1406852,1406986,1408123,1407299,1415359,1412903,1418154,1413297,1414307,1414325,1415339,1415710,1419019,1422817,1419852,1425829,1434157,1426693,1436946,1441175,1445875,1450097,1446168,1444846,1447698,1450895,1455654,1453366,1453523,1469904,1470878,1464930,1477762,1479876,1480444,1487888,1488092,1498533,1488119,1500906,1497739,1503676,1507275,1502736,1507519,1511367,1521543,1512498,1513468,1515359,1525176,1521802,1525376,1531495,1535236,1538852,1539474,1535716,1539631,1540604,1545355,1552906,1546962,1553126,1553280,1547011,1547810,1546797,1555524,1560124,1560527,1566489,1573538,1573663,1574095,1569216,1563147,1566647,1581357,1583136,1585935,1573833,1577685,1589733,1594995,1586520,1592638,1592951,1599753,1597317,1594080,1590730,1603830,1603837,1603957,1601117,1604986,1605003,1610211,1609365,1614878,1616964,1618361,1608370,1619237,1619690,1609347,1613182,1624647,1618218,1623383,1626384,1616214,1626462,1624295,1628081,1632724,1625914,1626451,1636428,1638985,1642380,1644464,1637165,1636237,1648605,1643613,1649244,1644570,1642108,1642684,1655569,1655683,1669734,1664255,1664775,1654865,1668085,1668479,1668651,1675874,1687562,1683836,1676504,1685162,1677330,1677781,1686009,1695164,1688160,1688906,1697575,1697814,1692287,1702575,1698856,1706125,1706419,1700141,1704046,1708298,1700541,1706477,1710568,1715562,1706776,1707111,1708276,1711385,1717783,1720057,1720049,1724555,1720161,1721074,1721268,1725745,1719414,1724502,1731690,1731717,1732657,1730345,1734608,1755316,1756537,1756833,1746534,1743453,1759784,1756976,1755339,1751021,1773771,1770834,1768366,1775552,1763600,1768951,1764864,1769491,1770167,1766714,1782960,1783653,1784681,1780007,1780211,1787146,1773105,1781154,1793506,1803221,1785685,1796693,1786804,1796757,1786822,1811371,1811540,1814007,1804248,1818967,1827133,1815711,1815870,1828121,1810274,1816294,1811775,1811804,1828203,1833076,1837005,1830593,1837976,1850527,1837006,1843615,1849986,1859389,1853416,1857095,1862782,1863206,1863783,1864375,1866326,1872419,1861721,1862420,1876749,1873341,1879968,1889797,1875574,1883898,1884380,1886166,1901824,1909069,1913357,1909264,1913436,1916005,1916852,1911414,1913580,1920957,1911762,1917386,1914784,1912187,1926900,1927195,1933185,1938125,1929388,1922980,1935345,1938257,1938387,1947239,1938411,1934900,1945149,1942122,1955764,1952304,1952307,1959200,1948309,1959521,1948398,1949000,1945341,1949414,1964064,1959011,1962087,1962571,1957403,1967228,1981185,1969632,1981454,1967703,1967794,1982446,1978678,1988884,2000271,1998966,2003842,2009089,2006492,2011320,2023395,2017492,2021998,2018540,2027601,2043008,2046831,2051119,2044681,2048706,2053105,2058939,2066490,2061263,2057546,2063329,2067168,2070111,2067695,2075183,2070309,2076877,2065094,2066448,2062000,2079711,2078307,2076694,2091463,2095599,2100883,2100976,2097633,2107469,2107933,2110781,2107241,2111105,2111685,2105666,2108712,2118008,2104574,2104648,2115774,2122019,2117547,2113842,2133805,2139578,2129229,2126050,2132047,2136458,2139196,2144684,2136463,2140161,2145702,2150234,2158564,2149275,2149692,2163154,2163212,2166714,2167487,2167744,2179834,2175441,2172286,2181427,2184049,2181690,2179284,2179580,2188830,2181343,2189318,2191597,2186946,2187452,2192229,2190500,2188072,2191255,2196474,2202224,2198657,2196126,2203800,2211353,2211824,2205452,2210618,2214400,2218693,2207766,2208220,2212460,2222421,2220549,2226617,2223292,2221754,2224034,2224538,2227347,2227789,2225832,2228328,2225928,2224083,2226009,2239494,2237135,2237209,2249319,2246463,2249913,2258753,2255999,2256840,2251254,2251262,2270548,2263426,2265594,2260508,2267672,2274143,2267146,2280988,2284123,2284293,2285187,2285487,2299684,2288936,2289036,2309196,2300325,2308175,2312231,2312431,2305253,2308875,2322506,2327704,2327936,2318663,2328985,2313658,2321222,2327383,2316735,2338024,2333708,2351848,2344512,2352157,2340659,2342738,2350710,2357682,2357704,2353798,2354811,2349289,2364107,2376890,2377280,2372772,2379418,2374131,2374165,2371725,2378326,2375841,2379085,2388481,2377145,2377146,2377305,2380318,2392000,2378272,2400738,2400287,2400336,2412104,2413910,2402520,2420673,2416824,2423442,2418234,2423977,2419631,2425931,2434697,2423620,2429092,2450203,2456446,2446687,2452448,2456551,2463774,2459048,2455355,2473188,2481668,2474471,2475283,2469931,2478954,2480226,2492609,2489200,2482622,2489924,2500883,2495343,2497472,2499096,2510396,2501284,2505724,2497546,2511130,2497732,2511275,2513148,2502821,2503693,2493062,2499091,2491048,2495087,2492216,2515499,2519698,2520536,2521668,2521803,2520921,2528177,2540793,2539263,2536439,2536483,2540126,2548478,2554991,2577190,2578986,2595703,2592469,2597811,2587056,2607294,2608347,2603633,2608818,2607093,2612485,2608353,2614361,2632126,2626314,2632778,2627457,2642591,2627414,2639366,2648948,2652574,2640060,2651155,2651488,2647139,2637970,2663607,2668290,2674831,2675190,2666654,2671058,2671658,2684198,2678068,2674903,2682264,2690908,2686277,2683913,2689660,2694832,2693792,2694154,2694274,2704534,2710362,2704105,2699806,2714413,2720218,2715061,2715941,2711601,2721996,2727847,2719555,2724027,2725253,2730200,2730773,2728158,2738634,2738640,2739353,2737941,2738242,2743717,2746881,2747088,2744772,2739901,2765981,2764254,2759818,2762496,2773678,2765273,2765440,2763548,2772221,2775013,2780698,2777037,2773456,155129,23322,54676,18320,196102,43023,43440,24122,107657,210394,210276,152349,163368,75155,23534,203815,14343,193530,94154,106589,88193,158555,87330,170185,169438,57224,208582,178450,213722,228193,94302,131934,85579,115643,117169,170072,191000,130859,282342,64759,8273,91275,253391,319550,9682,250962,8631,190887,19846,318854,376289,184175,287071,276432,385185,409450,208475,388818,360976,357567,266193,64277,262178,423817,198510,88927,116631,323178,335262,118482,421587,95480,145715,398549,283554,374994,283982,277578,353791,145311,311779,238782,375159,398125,126234,296635,298526,15710,314512,56,505537,488547,151589,430704,352924,26657,39680,300236,262761,504643,505709,426033,401153,505636,76396,332123,89094,407775,293319,257426,418192,513260,640250,309080,322892,186992,334883,369697,412701,19210,66806,29934,267015,149681,568130,175301,649882,585157,666526,630753,130474,235379,296143,663741,471557,596529,401320,489439,31609,130341,179336,25579,46489,83650,356458,638256,94009,640628,226809,344514,312179,333897,56636,517821,185535,223731,265332,476936,338173,487698,669428,185349,624629,127023,569896,320841,254645,143642,190295,150834,1293,645066,220673,463004,17371,204145,389255,670416,500482,520611,436794,42842,583538,453455,238839,849428,644558,353046,152207,885423,862882,168872,865892,252833,768859,494982,704475,763550,847583,243590,192935,705047,749485,22629,590285,376007,211309,588138,765519,853603,326311,328314,635448,340078,684128,165464,407104,520,854587,677621,507378,530193,890915,133186,181470,851900,692094,809844,558063,859004,342871,667620,245464,506532,512364,1320716,1319553,628917,897161,538670,947101,269119,639275,830117,320155,333046,816745,700051,149304,791833,18084,542205,1340545,331903,489267,2191709,20516,50714,665205,722330,48641,463159,596974,773166,10570,150584,410811,492041,629663,620402,592025,498499,244109,672765,780820,232449,256699,2019467,287254,332174,2793722,2783186,2783558,2782854,2782428,2782264,2782266,2782150,2781950,2791665,2787634,2785847,2783304,2787957,2787704,2785676,2785380,2784901,2786252,2789432,2788106,2787994,2787713,2787182,2789286,2789164,2793406,2793284,2792890,2792587,2791111,2790629,2790062,2789670,2794723,2793874,2791138,2787365,2790415,2783908,2790213,2787233,2783447,2789791,2793101,2790816,2787536,2786189,2258,2560,2794265,1036122,59517,2797703,2255330,2180698,2792956,2794995,2181174,2791576,2138601,435967,2753250,2798598,2752745,2794779,2740247,1389999,2797184,2791177,2802012,2772681,69636,2797808,2803050,203208,2799924,105265,2747357,2138672,2751002,511771,2181603,14734,683771,2794539,2057756,658828,2794568,1777345,2660272,349116,1213011,2832859,2832459,2812196,636020,674024,123456,2819831,2819941,2820001,2820606,2821110,2821112,2820917,2821361,2821537,2821895,2821953,2822007,2822048,2822077,2822347,2822597,2823580,2823894,2824192,2824436,2824937,2825156,2825563,2825695,2825850,2825866,2826271,2826408,2826622,2826645,2826840,2826844,2826973,2827027,2827299,2828275,2828341,2829675,2828501,2829696,2829808,2830392,2834866,2830423,2835666,2835850,2836152,2836163,2836456,2835262,2809466,2809903,2810514,2811048,2811552,2811675,2811799,2811853,2811902,2812147,2813442,2813476,2813701,2814491,2815130,2815346,2815369,2815522,2815810,2816165,2816297,2816849,2818154,2818533,2818566,2818642,2819057,2819226,2819350,2820044,2820408,2820579,2820949,2821310,2821818,2823472,2824916,2820209,2814753,2825023,2821333,2815845,2815146,2812489,1529029,2854464,2855658,2858301';
		
			$sql="select x,y,gi.gridimage_id,gi.user_id,realname,title,grid_reference from gridimage_search gi
			where gridimage_id in ($ids) order by field(gridimage_id,$ids)";
			
		
			//one per hectad
			$sql="select x,y,gi.gridimage_id,gi.user_id,realname,title,grid_reference,reference_index from one_per_hectad gi";
		
			
			//selection
			$sql="select x,y,gi.gridimage_id,gi.user_id,realname,title,grid_reference from gridimage_search gi inner join random_selection using (gridimage_id) order by promoted,moderation_status+0";
				
			
			set_time_limit(6000); 
		} else {
			//broken - but inaccessible
		$sql="select x,y,grid_reference from gridsquare where 
			CONTAINS( GeomFromText($rectangle),	point_xy)
			and imagecount>0 group by FLOOR(x/30),FLOOR(y/30) order by rand() limit 500";
		}
		#print $sql;
		$usercount=array();
		$recordSet = &$db->Execute($sql);
				
		print $recordSet->RecordCount();
		#flush();
		#exit;
		
		$lines = array();
		$photopixels = (isset($_GET['huge2']))?80:40;
		while (!$recordSet->EOF) 
		{
			$gridx=$recordSet->fields[0];
			$gridy=$recordSet->fields[1];

			if (isset($recordSet->fields[7])) {
				$rii = $recordSet->fields['reference_index'];
				$gridx = ( intval(($gridx - $CONF['origins'][$rii][0])/10)*10 ) +  $CONF['origins'][$rii][0] +5;
				$gridy = ( intval(($gridy - $CONF['origins'][$rii][1])/10)*10 ) +  $CONF['origins'][$rii][1] +4;
			}

			$imgx1=($gridx-$left) * $this->pixels_per_km;
			$imgy1=($this->image_h-($gridy-$bottom+1)* $this->pixels_per_km);

			
			$imgx1=round($imgx1) - (0.5 * $photopixels);
			$imgy1=round($imgy1) - (0.5 * $photopixels);

			$imgx2=$imgx1 + $photopixels;
			$imgy2=$imgy1 + $photopixels;
				
				
			$gridimage_id=$recordSet->fields[2];

			$gridimage=new GridImage;
			$gridimage->fastInit($rec = $recordSet->fields);
			
			$photo=$gridimage->getSquareThumb($photopixels);
			if (!is_null($photo))
			{
				imagecopy ($img, $photo, $imgx1, $imgy1, 0,0, $photopixels,$photopixels);
				imagedestroy($photo);

				imagerectangle ($img, $imgx1, $imgy1, $imgx2, $imgy2, $colBorder);

				#$lines[] = "<area shape=\"rect\" coords=\"$imgx1,$imgy1,$imgx2,$imgy2\" href=\"/photo/{$rec['gridimage_id']}\" title=\"".htmlentities("{$rec['grid_reference']} : {$rec['title']} by {$rec['realname']}")."\">"; 
				fwrite($imagemap, "<area shape=\"rect\" coords=\"$imgx1,$imgy1,$imgx2,$imgy2\" href=\"/photo/{$rec['gridimage_id']}\" title=\"".htmlentities("{$rec['grid_reference']} : {$rec['title']} by {$rec['realname']}")."\">\n");
			} else {
				print $recordSet->fields['gridimage_id']." ";
			}
			$usercount[$rec['realname']]++;
			
			
			$recordSet->MoveNext();
		}
		if (!empty($recordSet))
			$recordSet->Close(); 
			
			#fwrite($imagemap,implode("\n",array_reverse($lines)));
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
		$db=&$this->_getDB(true);

split_timer('map'); //starts the timer

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

		$crit = '';
		if ($this->pixels_per_km < 1) {
			$div = 500000; //1 per 500k square
			$crit = "s = '1' AND";
			$cityfont = 3;
		} elseif ($this->pixels_per_km == 1) {
			$div = 100000; 
			$crit = "(s = '1' OR s = '2') AND";
			$cityfont = 3;
		} elseif ($this->pixels_per_km == 4 || $this->pixels_per_km == 2) {
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
		if (!empty($_GET['d']))
			exit;
		if (!empty($recordSet))
			$recordSet->Close(); 
	
split_timer('map','_plotPlacenames'); //logs the wall time

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
			$intersect = true;
			$thisrect = array($x,$y,$x + $stren,$y + $strhr);
			while ($quad < 5 && $intersect) {
				$intersect = false;
				reset($this->labels);
				foreach ($this->labels as $a1) {
					if (rectinterrect($a1,$thisrect)) {
						$intersect = true;
						break;
					}
				}

				if ($intersect) {
					$quad++;
					list($x,$y) = $xy;
					if ($quad%2 == 1) {
					} else {
						$x = $x - $stren;
					}
					if ($quad > 2) {
					} else {
						$y = $y - imagefontheight($font); 
					}
					$thisrect = array($x,$y,$x + $stren,$y + $strhr);
					//$thisrect = array($x-3,$y-3,$x + $stren+3,$y + $strhr+3);
				}
			}
		}
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
		
		$db=&$this->_getDB(true);

	split_timer('map'); //starts the timer
	
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
				if (!empty($_GET['d'])) {
					print "$text";var_dump($thisrect); print "<BR>";
				}
			}
			$recordSet->MoveNext();
		}

		if (!empty($recordSet))
			$recordSet->Close();
		
		//plot the number labels
		if ($this->pixels_per_km >= 40) {
			$gridref = $this->getGridRef(0, $this->image_h); //origin of image is tl, map is bl
			if (preg_match('/^([A-Z]{1,3})(\d\d)(\d\d)$/',$gridref, $matches))
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
		
		split_timer('map','_plotGridLines'); //logs the wall time

	}


	/**
	* return a sparse 2d array for every grid on the map
	* @access private
	*/
	function& getGridArray($isimgmap = false)
	{
		global $memcache,$CONF;
		static $counter = 0;

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
		$db=&$this->_getDB(true);

split_timer('map'); //starts the timer
		
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
		$where_crit = '';
		$columns = '';
		if ($isimgmap) {
			if (!empty($this->type_or_user)) {
				if ($this->type_or_user > 0) {
					$where_crit = " and user_id = {$this->type_or_user}";
					$columns = ',0 as imagecount';
				} elseif ($this->type_or_user == -6) {
					$where_crit = " and imagetaken > DATE(DATE_SUB(NOW(), INTERVAL 5 YEAR))";
					$columns = ',0 as imagecount';
				} 
			}
			$table = $CONF['db_tempdb'].".gi_grid$counter"; $counter++;
			
			if ($this->type_or_user == -20) {
				//TODO - doesnt cope with multiple assignments for a given hectad!
				$sql="CREATE TEMPORARY TABLE $table ENGINE HEAP
					SELECT gi.gridimage_id,x,y 
					FROM gridimage_search gi
					INNER JOIN gridsquare_assignment ga ON (gi.gridimage_id = ga.gridimage_id)
					WHERE CONTAINS( GeomFromText($rectangle),	point_xy)";
				$db->Execute($sql);

				$sql="INSERT INTO $table 
					SELECT gridimage_id,x,y
					FROM gridimage_search WHERE 
					CONTAINS( GeomFromText($rectangle),	point_xy)
					AND ftf <= 1
					ORDER BY moderation_status+0 DESC,seq_no";
				$db->Execute($sql);
					
				$sql="ALTER IGNORE TABLE $table ADD PRIMARY KEY (x,y),ADD UNIQUE (gridimage_id)";
				$db->Execute($sql);
				
			} elseif (true && empty($where_crit)) {
				$sql="CREATE TEMPORARY TABLE $table ENGINE HEAP
					SELECT gridimage_id,grid_reference,x,y $columns FROM gridimage_persquare WHERE 
					CONTAINS( GeomFromText($rectangle),	point_xy)";
				$db->Execute($sql);
				
			} else {
				if (empty($where_crit)) {
					$where_crit = "AND ftf <= 1";
				}
				$sql="CREATE TEMPORARY TABLE $table ENGINE HEAP
					SELECT gridimage_id,grid_reference,x,y $columns FROM gridimage_search WHERE 
					CONTAINS( GeomFromText($rectangle),	point_xy) $where_crit
					ORDER BY moderation_status+0 DESC,seq_no";
				$db->Execute($sql);
			
				if (!empty($this->type_or_user) && ($this->type_or_user > 0 || $this->type_or_user == -6)) {
					$table2 = $table."tmp2";
					$sql="CREATE TEMPORARY TABLE $table2 ENGINE HEAP 
						SELECT x,y,count(*) as imagecount 
						FROM $table GROUP BY x,y ORDER BY null";
					$db->Execute($sql);

					$sql="UPDATE $table2,$table SET $table.imagecount = $table2.imagecount 
						WHERE $table.x = $table2.x AND $table.y = $table2.y";
					$db->Execute($sql);
					$columns = ", $table.imagecount";
				}

				$sql="ALTER IGNORE TABLE $table ADD PRIMARY KEY (x,y),ADD UNIQUE (gridimage_id)";
				$db->Execute($sql);
			}
			
			$sql="SELECT gs.* $columns,gi.gridimage_id,gi.realname AS credit_realname,IF(gi.realname!='',gi.realname,user.realname) AS realname,title 
				FROM gridsquare gs
				LEFT JOIN gridimage gi USING (gridsquare_id)
				INNER JOIN $table USING (gridimage_id)
				INNER JOIN user ON(gi.user_id = user.user_id)
				WHERE 
				CONTAINS( GeomFromText($rectangle),	point_xy)
				AND percent_land<>0 
				GROUP BY gs.grid_reference ORDER BY y,x";
		} elseif ($this->pixels_per_km == 4) {
			if (!empty($this->type_or_user)) {
				if ($this->type_or_user > 0) {
					$sql="select x,y,ug.imagecount,ug.has_geographs,reference_index,
						(ug.has_geographs=0 and ug.imagecount > 0) as accepted, 0 as pending
						from gridsquare gs
						left join user_gridsquare ug on (gs.grid_reference = ug.grid_reference and user_id = {$this->type_or_user})
						where 
						CONTAINS( GeomFromText($rectangle),	point_xy)
						and percent_land<>0";
						
				} elseif ($this->type_or_user == -6) {
					$sql="select x,y,0 as imagecount,has_recent as has_geographs,reference_index,
						imagecount as accepted, 0 as pending
						from gridsquare gs
						where 
						CONTAINS( GeomFromText($rectangle),	point_xy)
						and percent_land<>0";
				} elseif ($this->type_or_user == -1) {
					$sql="select x,y,imagecount,has_geographs,reference_index,
						(has_geographs=0 and imagecount > 0) as accepted, 0 as pending
						from gridsquare gs
						where 
						CONTAINS( GeomFromText($rectangle),	point_xy)
						and percent_land<>0";
				}elseif ($this->type_or_user == -13) {
					$sql="select x,y,imagecount,max(ftf) as max_ftf,reference_index
						from gridsquare gs
						left join gridimage gi on(gi.gridsquare_id = gs.gridsquare_id and moderation_status='geograph' )
						where 
						CONTAINS( GeomFromText($rectangle),	point_xy)
						and percent_land<>0
						group by gs.gridsquare_id
						order by null";
				} 
			} else {
				$sql="select x,y,imagecount,has_geographs,reference_index,
					(has_geographs=0 and imagecount > 0) as accepted, 0 as pending
					from gridsquare gs
					where 
					CONTAINS( GeomFromText($rectangle),	point_xy)
					and percent_land<>0";
			}
		} elseif ($this->type_or_user == -13) {
			$sql="select gs.* $columns,
				max(ftf) as max_ftf, sum(moderation_status='pending') as pending,
				DATE_FORMAT(MAX(if(moderation_status!='rejected',imagetaken,null)),'%d/%m/%y') as last_date
				from gridsquare gs
				left join gridimage gi on(gi.gridsquare_id = gs.gridsquare_id and moderation_status='geograph' )
				where 
				CONTAINS( GeomFromText($rectangle),	point_xy)
				and percent_land<>0 
				group by gs.gridsquare_id order by y,x";
		} else {
			if (!empty($this->type_or_user)) {
				if ($this->type_or_user > 0) {
					$where_crit = " and gi.user_id = {$this->type_or_user}";
					$columns = ", sum(moderation_status='geograph') as has_geographs, sum(moderation_status IN ('accepted','geograph')) as imagecount";
				} elseif ($this->type_or_user == -6) {
					$where_crit = " and imagetaken > DATE(DATE_SUB(NOW(), INTERVAL 5 YEAR))";
					$columns = ", sum(moderation_status='geograph') as has_geographs, sum(moderation_status IN ('accepted','geograph')) as imagecount";
				} 
			}
			$sql="select gs.* $columns,
				sum(moderation_status='accepted') as accepted, sum(moderation_status='pending') as pending,
				DATE_FORMAT(MAX(if(moderation_status!='rejected',imagetaken,null)),'%d/%m/%y') as last_date
				from gridsquare gs
				left join gridimage gi on(gi.gridsquare_id = gs.gridsquare_id $where_crit )
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
		if (!empty($recordSet))
			$recordSet->Close();

split_timer('map','getGridArray'.$isimgmap,$mkey); //logs the wall time


		if ($memcache->valid)
			$memcache->name_set($mnamespace,$mkey,$grid,$memcache->compress,$mperiod);
		
		return $grid;
	}


	/**
	 * get stored db object, creating if necessary
	 * @access private
	 */
	function &_getDB($allow_readonly = false)
	{
		//check we have a db object or if we need to 'upgrade' it
		if (!is_object($this->db) || ($this->db->readonly && !$allow_readonly) ) {
			$this->db=GeographDatabaseConnection($allow_readonly);
		}
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
                    $colour = @imagecolorat($text_image, $k, $l);
                    
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
}

