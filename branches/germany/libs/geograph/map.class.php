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
	* x origin of map in Mercator coordinates
	*/
	var $map_xM=0;
	
	/**
	* y origin of map in Mercator coordinates
	*/
	var $map_yM=0;

	/**
	* only given reference index
	*/
	var $force_ri=0;
	
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
	var $caching_squaremap=true;

	/**
	 * Spherical mercator?
	 */
	var $mercator=false;

	/**
	 * Zoom level if spherical mercator
	 */
	var $level=0;
	
	/**
	 * Tile coordinates if spherical mercator
	 */
	var $tile_x=0;
	var $tile_y=0;

	/**
	 * Base map size and margins if spherical mercator
	 */
	var $base_margin = 0;
	var $base_width = 0;
	var $render_margin = 0;

	/**
	 * Tile width (m) if spherical mercator
	 */
	var $map_wM=0.0;

	/**
	 * Layers if spherical mercator
	 *
	 * 1: base map
	 * 2: squares
	 * 4: regions
	 * 8: grid labels
	 * 16: town labels
	 */

	var $layers = 31;

	/**
	 * Overlay mode if spherical mercator
	 */
	var $overlay = false;

	/**
	* bounding rectangles for labels, in an attempt to prevent collisions
	*/
	var $labels = array();
	var $gridlabels = array();
	
	/*
	 * palette index, see setPalette for documentation
	 */
	var $palette=0;

	/*
	 * clipping
	 */
	var $cliptop = 0;
	var $clipbottom = 0;
	var $clipright = 0;
	var $clipleft = 0;
	
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
	function enableCaching($enable, $enablesquaremap = null)
	{
		$this->caching=$enable;
		if (is_null($enablesquaremap))
			$enablesquaremap=$enable;
		$this->caching_squaremap=$enablesquaremap;
	}
	
	/**
	* Change map type to spherical mercator or back
	* @access public
	*/
	function enableMercator($enable)
	{
		$this->mercator=$enable;
	}

	/**
	* Set origin of map in internal coordinates, returns true if valid
	* @access private
	*/
	function _calcXY()
	{
		if ($this->mercator) {
			$dx = pow(2.0,$this->level-1);
			$dy = $dx - 1;
			$this->map_xM=($this->tile_x - $dx)*$this->map_wM;
			$this->map_yM=($dy - $this->tile_y)*$this->map_wM;
		}
		return true;
	}

	
	/**
	* Set origin of map in internal coordinates, returns true if valid
	* @access public
	*/
	function setOrigin($x,$y)
	{
		if ($this->mercator) {
			$this->tile_x=intval($x);
			$this->tile_y=intval($y);
			$this->_calcXY();
		} else {
			$this->map_x=intval($x);
			$this->map_y=intval($y);
		}
		return true;
	}

	/**
	* Set size of map image
	* @access public
	*/
	function setImageSize($w,$h)
	{
		if ($this->mercator) {
			#FIXME hard coded width
			$this->image_w=256;
			$this->image_h=256;
		} else {
			$this->image_w=intval($w);
			$this->image_h=intval($h);
		}
		return true;
	}

	/**
	* Set desired scale in pixels per km
	* @access public
	*/
	function setScale($pixels_per_km)
	{
		if ($this->mercator) {
			$width = 256; #FIXME hard coded width
			#$FIXME allow levels _and_ pixels_per_km
			$this->level = intval($pixels_per_km);
			$this->map_wM = M_PI*2*6378137.000/pow(2,$this->level);
			#$this->pixels_per_km = 128/(M_PI*6378.137)*pow(2,$this->level);
			$this->pixels_per_km = $width/$this->map_wM*1000.0; # only right at equator
			$this->_calcXY();
			$this->image_w=$width;
			$this->image_h=$width;
			$this->base_width = $width;
			$this->base_margin = $width/4;#0;
			$this->render_margin= $width/4;#0;
			if ($this->level <= 9) {
				$this->base_margin *= 4;
				$this->base_width *= 4;
			}
		} else {
			$this->pixels_per_km=floatval($pixels_per_km);
		}
		return true;
	}

	/**
	* Set clipping parameters
	* @access public
	*/
	function setClip($clipleft, $clipright, $clipbottom, $cliptop)
	{
		$this->cliptop = $cliptop;
		$this->clipbottom = $clipbottom;
		$this->clipright = $clipright;
		$this->clipleft = $clipleft;
	}

	/**
	* Return an opaque, url-safe token representing this mosaic
	* @access public
	*/
	function getToken()
	{
		$token=new Token;
		if (empty($this->mercator)) {
			$token->setValue("x", $this->map_x);
			$token->setValue("y", $this->map_y);
		} else {
			$token->setValue("x", $this->tile_x);
			$token->setValue("y", $this->tile_y);
		}
		$token->setValue("w",  $this->image_w);
		$token->setValue("h",  $this->image_h);
		if (empty($this->mercator))
			$token->setValue("s",  $this->pixels_per_km);
		else {
			$token->setValue("s",  $this->level);
			$token->setValue("m", 1);
			$token->setValue("l", $this->layers);
			if (!empty($this->overlay))
				$token->setValue("o", 1);
		}
		if (!empty($this->force_ri))
			$token->setValue("i",  $this->force_ri);
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
				$this->enableMercator($token->hasValue("m") &&  $token->getValue("m"));
				$this->setOrigin($token->getValue("x"), $token->getValue("y"));
				$this->setImageSize($token->getValue("w"), $token->getValue("h"));
				$this->setScale($token->getValue("s"));
				$this->type_or_user = ($token->hasValue("t"))?$token->getValue("t"):0;
				if ($token->hasValue("r")) 
					$this->reference_index = $token->getValue("r");
				if ($token->hasValue("p")) 
					$this->setPalette($token->getValue("p"));
				$this->force_ri = ($token->hasValue("i"))?$token->getValue("i"):0;
				$this->layers = ($token->hasValue("l"))?$token->getValue("l"):7;
				$this->overlay = $token->hasValue("o") && $token->getValue("o");
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
	function getPanToken($xdir,$ydir) // FIXME mercator
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
	function getGridRef($x, $y) // FIXME mercator
	{
		global $CONF;
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
			
			$x_lim=$x_km-100;
			$y_lim=$y_km-100;
			$sql="select prefix,origin_x,origin_y,reference_index from gridprefix 
				where CONTAINS( geometry_boundary,	GeomFromText('POINT($x_km $y_km)'))
				and (origin_x > $x_lim) and (origin_y > $y_lim)
				order by $order_by limit 1";

			$prefix=$db->GetRow($sql);

			#if (empty($prefix['prefix'])) { 
			#	//if fails try a less restrictive search
			#	$sql="select prefix,origin_x,origin_y,reference_index from gridprefix 
			#		where $x_km between origin_x and (origin_x+width-1) and 
			#		$y_km between origin_y and (origin_y+height-1)
			#		order by landcount desc, reference_index limit 1";
			#	$prefix=$db->GetRow($sql);
			#}
			
			if (!empty($prefix['prefix'])) { 
				$n=$y_km-$prefix['origin_y'];
				$e=$x_km-$prefix['origin_x'];
				$this->gridref = sprintf('%s%02d%02d', $prefix['prefix'], $e, $n);
				$this->reference_index = $prefix['reference_index'];
			} elseif ($x_km >= $CONF['minx'] && $y_km >= $CONF['miny'] && $x_km <= $CONF['maxx'] && $y_km <= $CONF['maxy']) {
				$xofs = $x_km-$CONF['minx'];
				$yofs = $y_km-$CONF['miny'];
				$this->gridref = '!'.$CONF['xnames'][floor($xofs/100)].$CONF['ynames'][floor($yofs/100)].sprintf('%02d%02d',$xofs%100,$yofs%100);
				$this->reference_index = null;
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
	function getImageFilename($layers = -1)
	{
		if ($layers == -1) {
			$layers = $this->layers;
		}
		if ($layers == 1)
			return $this->getBaseMapFilename();
		elseif ($layers == 4)
			return $this->getLabelMapFilename(false,true);
		elseif ($layers == 8)
			return $this->getLabelMapFilename(false,false);
		elseif ($layers == 16)
			return $this->getLabelMapFilename(true,false);

		$root=&$_SERVER['DOCUMENT_ROOT'];
		
		$dir="/maps/detail/";
		if (!is_dir($root.$dir))
			mkdir($root.$dir);

		$emptyimage = false;
		if (empty($this->mercator)) {
			$map_x = $this->map_x;
			$map_y = $this->map_y;
			$scale = $this->pixels_per_km;
			$extension = ($this->pixels_per_km > 64 || $this->type_or_user < -20)?'jpg':'png';
		} else {
			if ($layers == 2 && $this->type_or_user == -10) { # only one empty tile needed...
				$map_x = 0;
				$map_y = 0;
				$scale = 0;
				$emptyimage = true;
			} else {
				$map_x = $this->tile_x;
				$map_y = $this->tile_y;
				$scale = $this->level;
			}
			$extension = 'png';
		}
		
		$dir.="{$map_x}/";
		if (!is_dir($root.$dir))
			mkdir($root.$dir);
		
		$dir.="{$map_y}/";
		if (!is_dir($root.$dir))
			mkdir($root.$dir);
		
		$palette="";
		if (!$emptyimage) {
			//for palette 0 we use the older, palette free filename
			if ($this->palette>0)
				$palette="_".$this->palette;
			
			if (!empty($this->minimum)) {
				$palette .= "_n{$this->minimum}";
			}

			if (!empty($this->force_ri)) {
				$palette .= "_i{$this->force_ri}";
			}
		}

		if ($this->mercator) {
			$palette .= "_m";
			$palette .= "_l{$layers}";
			if ($this->overlay && !$emptyimage) {
				$palette .= "_o";
			}
		}
		
		$file="detail_{$map_x}_{$map_y}_{$this->image_w}_{$this->image_h}_{$scale}_{$this->type_or_user}{$palette}.$extension";
		
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
		
		if (empty($this->mercator)) { #FIXME tilecache
			$map_x = $this->map_x;
			$map_y = $this->map_y;
			$ext = 'gd';
		} else {
			$map_x = $this->tile_x;
			$map_y = $this->tile_y;
			$ext = 'png';
		}
		
		$dir.="{$map_x}/";
		if (!is_dir($root.$dir))
			mkdir($root.$dir);
		
		$dir.="{$map_y}/";
		if (!is_dir($root.$dir))
			mkdir($root.$dir);
		
		//for palette 0 we use the older, palette free filename
		$palette="";
		if ($this->palette>0)
			$palette="_".$this->palette;

		if (!empty($this->force_ri)) {
			$palette .= "_i{$this->force_ri}";
		}

		if (empty($this->mercator)) { #FIXME tilecache
			$scale = $this->pixels_per_km;
		} else {
			$scale = $this->level;
			$palette .= "_m";
		}
		
		$file="base_{$map_x}_{$map_y}_{$this->image_w}_{$this->image_h}_{$scale}{$palette}.{$ext}";
		
		
		return $dir.$file;
	}

	/**
	* calc filename to an image which can form the labels of the map
	* @access public
	*/
	function getLabelMapFilename($towns, $regions)
	{
		$root=&$_SERVER['DOCUMENT_ROOT'];
		
		$dir="/maps/label/";
		if (!is_dir($root.$dir))
			mkdir($root.$dir);
		
		if (empty($this->mercator)) { #FIXME tilecache
			$map_x = $this->map_x;
			$map_y = $this->map_y;
		} else {
			$map_x = $this->tile_x;
			$map_y = $this->tile_y;
		}
		
		$dir.="{$map_x}/";
		if (!is_dir($root.$dir))
			mkdir($root.$dir);
		
		$dir.="{$map_y}/";
		if (!is_dir($root.$dir))
			mkdir($root.$dir);
		
		//for palette 0 we use the older, palette free filename
		$palette="";
		if ($this->palette>0)
			$palette="_".$this->palette;

		if (!empty($this->force_ri)) {
			$palette .= "_i{$this->force_ri}";
		}
		if ($towns) {
			$palette .= "_t";
		}
		if ($regions) {
			$palette .= "_r";
		}

		if (empty($this->mercator)) { #FIXME tilecache
			$scale = $this->pixels_per_km;
		} else {
			$scale = $this->level;
			$palette .= "_m";
			if ($this->overlay) {
				$palette .= "_o";
			}
		}
		
		$file="label_{$map_x}_{$map_y}_{$this->image_w}_{$this->image_h}_{$scale}{$palette}.png";
		
		
		return $dir.$file;
	}


	/**
	* Try to unset type_or_user, overlay, ...
	* @access private
	*/
	function _simplifyParameters()
	{
		if ($this->mercator) {
			if ($this->level > 11 && $this->type_or_user == -1 && !$this->overlay) $this->type_or_user = 0;
			if ($this->type_or_user > 0 && !$this->needUserTile($this->type_or_user)) $this->type_or_user = -10;
			# FIXME have a look at  _plotGridLinesM, whether we can set $this->overlay = false for this zoom level!
		} else {
			//if thumbs level on depeth map, can just use normal render.
			if ($this->type_or_user == -1 && $this->pixels_per_km >= 32) {
				$this->type_or_user = 0;
			}
		}
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

		$realtype = $this->type_or_user;
		$realov = $this->overlay;
		$this->_simplifyParameters();
		//always given dynamic url, that way cached HTML can 
		//always get an image
		$token=$this->getToken();
		$file="http://{$CONF['TILE_HOST']}/tile.php?map=$token";

		if (isset($CONF['curtail_level']) && $CONF['curtail_level'] > 2 && empty($GLOBALS['USER']->user_id)) {
			$file = cachize_url($file);
		}

		$this->type_or_user = $realtype;
		$this->overlay = $realov;

		return $file;
		
	}
	
	/**
	* returns an image with appropriate headers
	* @access public
	*/
	function returnImage()
	{
		$this->_simplifyParameters();
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
		if ($this->mercator) {
			//trigger_error("-> {$this->map_x} {$this->map_y} {$this->level} {$this->image_h} {$this->image_w}", E_USER_NOTICE);
			#if ($this->type_or_user == 0) {
			#	$ok = $this->_renderImageM();
			#} elseif ($this->type_or_user == -1) {
			#	//if thumbs level can just use normal render. 
			#	if ($this->level <= 11) {
			#		$ok = $this->_renderDepthImageM(); #FIXME
			#	} else {
			#		$ok = $this->_renderImageM();
			#	}
			#} elseif ($this->type_or_user > 0) {
			#	//normal render image, understands type_or_user > 0!
			#	$ok = $this->_renderImageM();
			#} 
			$ok = $this->_renderImageM();
			if ($ok) {
				$db=&$this->_getDB();
				$widthMC  = pow(2, 19-$this->level);
				$leftMC   = +$widthMC*$this->tile_x - 262144;
				$topMC    = -$widthMC*$this->tile_y + 262144;
				$rightMC  = $leftMC   + $widthMC;
				$bottomMC = $topMC    - $widthMC;
				#$widthM=$this->map_wM;
				#$leftM=$this->map_xM;
				#$bottomM=$this->map_yM;
				#$rightM=$leftM+$widthM;
				#$topM=$bottomM+$widthM;
				##$sql="select min(x) as min_x, min(y) as min_y, max(x)+1 as max_x, max(y)+1 as max_y from gridsquare_gmcache inner join gridsquare using(gridsquare_id) where gxlow <= $rightM and gxhigh >= $leftM and gylow <= $topM and gyhigh >= $bottomM";
				# use db values if != NULL?
				#require_once('geograph/conversionslatlong.class.php');
				#$conv = new ConversionsLatLong;
				#list($glatTL, $glonTL) = $conv->sm_to_wgs84($leftM, $topM);
				#list($glatTR, $glonTR) = $conv->sm_to_wgs84($rightM, $topM);
				#list($glatBL, $glonBL) = $conv->sm_to_wgs84($leftM, $bottomM);
				#list($glatBR, $glonBR) = $conv->sm_to_wgs84($rightM, $bottomM);
				#list($xTL, $yTL) = $conv->wgs84_to_internal($glatTL, $glonTL);
				#list($xTR, $yTR) = $conv->wgs84_to_internal($glatTR, $glonTR);
				#list($xBL, $yBL) = $conv->wgs84_to_internal($glatBL, $glonBL);
				#list($xBR, $yBR) = $conv->wgs84_to_internal($glatBR, $glonBR);
				#$min_x = min($xTL, $xTR, $xBL, $xBR);
				#$min_y = min($yTL, $yTR, $yBL, $yBR);
				#$max_x = max($xTL, $xTR, $xBL, $xBR)+1;
				#$max_y = max($yTL, $yTR, $yBL, $yBR)+1;
				#$dx = ceil(($max_x - $min_x) * 0.125); //FIXME good value?
				#$dy = ceil(($max_y - $min_y) * 0.125); //FIXME good value?
				#$max_x += $dx;
				#$max_y += $dy;
				#$min_x -= $dx;
				#$min_y -= $dy;

				$sql=sprintf("replace into mapcache set map_x=%d,map_y=%d,image_w=%d,image_h=%d,pixels_per_km=%F,type_or_user=%d,force_ri=%d,mercator=%u,overlay=%u,layers=%u,level=%d,tile_x=%d,tile_y=%d,max_x=%d,max_y=%d",$leftMC,$bottomMC,$this->image_w,$this->image_h,$this->pixels_per_km,$this->type_or_user,$this->force_ri, $this->mercator?1:0, $this->overlay?1:0, $this->layers, $this->level, $this->tile_x, $this->tile_y,$rightMC,$topMC);

				$db->Execute($sql);
			}
			return $ok;
		}
	
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
				if ($this->pixels_per_km<32) { //FIXME limit?
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

			$sql=sprintf("replace into mapcache set map_x=%d,map_y=%d,image_w=%d,image_h=%d,pixels_per_km=%F,type_or_user=%d,force_ri=%d,max_x=%d,max_y=%d,level=%d,tile_x=%d,tile_y=%d",$this->map_x,$this->map_y,$this->image_w,$this->image_h,$this->pixels_per_km,$this->type_or_user,$this->force_ri,$this->map_x+ceil($this->image_w/$this->pixels_per_km)-1,$this->map_y+ceil($this->image_h/$this->pixels_per_km)-1,round($this->pixels_per_km*100),$this->map_x,$this->map_y);

			$db->Execute($sql);
		}
		return $ok;
	}

	function _vcut_convexpoly($poly, $xc)
	{
		$found = false;
		$point = end($poly);
		$x = $point[0];
		$y = $point[1];
		foreach ($poly as &$point) {
			$xp = $x;
			$yp = $y;
			$x = $point[0];
			$y = $point[1];
			if ($x == $xp) {
				if ($x != $xc)
					continue;
				$curymin = min($y, $yp);
				$curymax = max($y, $yp);
				$curxmin = $x;
				$curxmax = $x;
				if (!$found) {
					$xmin = $curxmin;
					$ymin = $curymin;
					$xmax = $curxmax;
					$ymax = $curymax;
					$found = true;
				} else {
					if ($curymin < $ymin) {
						$xmin = $curxmin;
						$ymin = $curymin;
					}
					if ($curymax > $ymax) {
						$xmax = $curxmax;
						$ymax = $curymax;
					}
				}
			}
			if ($x < $xp) {
				if ($xc < $x || $xc > $xp)
					continue;
			} else {
				if ($xc < $xp || $xc > $x)
					continue;
			}
			$yc = $yp + ($xc - $xp) * ($y - $yp) / ($x - $xp);
			if (!$found) {
				$xmin = $xc;
				$ymin = $yc;
				$xmax = $xc;
				$ymax = $yc;
				$found = true;
			} else {
				if ($yc < $ymin) {
					$xmin = $xc;
					$ymin = $yc;
				}
				if ($yc > $ymax) {
					$xmax = $xc;
					$ymax = $yc;
				}
			}
		}

		if ($found)
			return array(array($xmin, $ymin), array($xmax, $ymax));
		else
			return array();
	}

	function _hcut_convexpoly($poly, $yc)
	{
		$found = false;
		$point = end($poly);
		$x = $point[0];
		$y = $point[1];
		foreach ($poly as &$point) {
			$xp = $x;
			$yp = $y;
			$x = $point[0];
			$y = $point[1];
			if ($y == $yp) {
				if ($y != $yc)
					continue;
				$curxmin = min($x, $xp);
				$curxmax = max($x, $xp);
				$curymin = $y;
				$curymax = $y;
				if (!$found) {
					$xmin = $curxmin;
					$ymin = $curymin;
					$xmax = $curxmax;
					$ymax = $curymax;
					$found = true;
				} else {
					if ($curxmin < $xmin) {
						$xmin = $curxmin;
						$ymin = $curymin;
					}
					if ($curxmax > $xmax) {
						$xmax = $curxmax;
						$ymax = $curymax;
					}
				}
			}
			if ($y < $yp) {
				if ($yc < $y || $yc > $yp)
					continue;
			} else {
				if ($yc < $yp || $yc > $y)
					continue;
			}
			$xc = $xp + ($yc - $yp) * ($x - $xp) / ($y - $yp);
			if (!$found) {
				$xmin = $xc;
				$ymin = $yc;
				$xmax = $xc;
				$ymax = $yc;
				$found = true;
			} else {
				if ($xc < $xmin) {
					$xmin = $xc;
					$ymin = $yc;
				}
				if ($xc > $xmax) {
					$xmax = $xc;
					$ymax = $yc;
				}
			}
		}

		if ($found)
			return array(array($xmin, $ymin), array($xmax, $ymax));
		else
			return array();
	}

	function _split_polygon(&$poly, $mult, $closed = true)
	{
		if (count($poly)) {
			$tmppoly = array ();
			if ($closed) {
				$point = end($poly);
				$x = $point[0];
				$y = $point[1];
				$first = false;
			} else {
				$first = true;
			}
			foreach ($poly as &$point) {
				if (!$first) {
					$xp = $x;
					$yp = $y;
				}
				$x = $point[0];
				$y = $point[1];
				if ($first) {
					$tmppoly[] = array($x, $y);
					$first = false;
					continue;
				}
				$dx = ($x - $xp)/$mult;
				$dy = ($y - $yp)/$mult;
				for ($i = 1; $i < $mult; ++$i) {
					$xn = $xp + $dx*$i;
					$yn = $yp + $dy*$i;
					$tmppoly[] = array($xn, $yn);
				}
				#$tmppoly[] = array($x, $y);
				$tmppoly[] = $point;
			}
			$poly = $tmppoly;
		}
	}

	function _clip_polygon(&$clippoly, $xmin, $xmax, $ymin, $ymax, $closed = true)
	{
		if (count($clippoly)) {
			$tmppoly = array ();
			if ($closed) {
				$point = end($clippoly);
				$x = $point[0];
				$y = $point[1];
				$inside = $x >= $xmin;
				$first = false;
			} else {
				$first = true;
			}
			foreach ($clippoly as &$point) {
				if (!$first) {
					$insidep = $inside;
					$xp = $x;
					$yp = $y;
				}
				$x = $point[0];
				$y = $point[1];
				$inside = $x >= $xmin;
				if (!$first && $insidep != $inside) {
					$xI = $xmin;
					$yI = $y + ($xI - $x) * ($yp - $y) / ($xp - $x);
					$tmppoly[] = array($xI, $yI);
				}
				if ($inside) {
					$tmppoly[] = $point;#array($x, $y);
				}
				$first = false;
			}
			$clippoly = $tmppoly;
		}

		if (count($clippoly)) {
			$tmppoly = array ();
			if ($closed) {
				$point = end($clippoly);
				$x = $point[0];
				$y = $point[1];
				$inside = $y >= $ymin;
				$first = false;
			} else {
				$first = true;
			}
			foreach ($clippoly as &$point) {
				if (!$first) {
					$insidep = $inside;
					$xp = $x;
					$yp = $y;
				}
				$x = $point[0];
				$y = $point[1];
				$inside = $y >= $ymin;
				if (!$first && $insidep != $inside) {
					$yI = $ymin;
					$xI = $x + ($yI - $y) * ($xp - $x) / ($yp - $y);
					$tmppoly[] = array($xI, $yI);
				}
				if ($inside) {
					$tmppoly[] = $point;#array($x, $y);
				}
				$first = false;
			}
			$clippoly = $tmppoly;
		}

		if (count($clippoly)) {
			$tmppoly = array ();
			if ($closed) {
				$point = end($clippoly);
				$x = $point[0];
				$y = $point[1];
				$inside = $x <= $xmax;
				$first = false;
			} else {
				$first = true;
			}
			foreach ($clippoly as &$point) {
				if (!$first) {
					$insidep = $inside;
					$xp = $x;
					$yp = $y;
				}
				$x = $point[0];
				$y = $point[1];
				$inside = $x <= $xmax;
				if (!$first && $insidep != $inside) {
					$xI = $xmax;
					$yI = $y + ($xI - $x) * ($yp - $y) / ($xp - $x);
					$tmppoly[] = array($xI, $yI);
				}
				if ($inside) {
					$tmppoly[] = $point;#array($x, $y);
				}
				$first = false;
			}
			$clippoly = $tmppoly;
		}

		if (count($clippoly)) {
			$tmppoly = array ();
			if ($closed) {
				$point = end($clippoly);
				$x = $point[0];
				$y = $point[1];
				$inside = $y <= $ymax;
				$first = false;
			} else {
				$first = true;
			}
			foreach ($clippoly as &$point) {
				if (!$first) {
					$insidep = $inside;
					$xp = $x;
					$yp = $y;
				}
				$x = $point[0];
				$y = $point[1];
				$inside = $y <= $ymax;
				if (!$first && $insidep != $inside) {
					$yI = $ymax;
					$xI = $x + ($yI - $y) * ($xp - $x) / ($yp - $y);
					$tmppoly[] = array($xI, $yI);
				}
				if ($inside) {
					$tmppoly[] = $point;#array($x, $y);
				}
				$first = false;
			}
			$clippoly = $tmppoly;
		}
		#return $clippoly;
	}

	function rebuildGMcache()
	{
		//php file -> set_time_limit(0)
		global $CONF;
		$db=&$this->_getDB();
		$dbsq=NewADOConnection($GLOBALS['DSN']);
		
		$db->Execute("DROP TABLE IF EXISTS gridsquare_gmcache_tmp");
		
		// alpha, sinalpha, cosalpha, width
		//`real_percent_land` tinyint(4) NOT NULL,
		//`arearatio` double(7,6) NOT NULL,
		$db->Execute('CREATE TABLE gridsquare_gmcache_tmp (
			        `gridsquare_id` int(11) NOT NULL,
				`gxlow` int(11) NOT NULL,
				`gylow` int(11) NOT NULL,
				`gxhigh` int(11) NOT NULL,
				`gyhigh` int(11) NOT NULL,
				`area` double(9,6) NOT NULL,
				`cliparea` double(9,6) NOT NULL,
				`rotangle` double(8,6) NOT NULL,
				`scale` double(8,6) NOT NULL,
				`cgx` double(10,2) NOT NULL,
				`cgy` double(10,2) NOT NULL,
				`polycount` tinyint(1) NOT NULL,
				`poly1gx` double(10,2),
				`poly1gy` double(10,2),
				`poly2gx` double(10,2),
				`poly2gy` double(10,2),
				`poly3gx` double(10,2),
				`poly3gy` double(10,2),
				`poly4gx` double(10,2),
				`poly4gy` double(10,2),
				`poly5gx` double(10,2),
				`poly5gy` double(10,2),
				`poly6gx` double(10,2),
				`poly6gy` double(10,2),
				`poly7gx` double(10,2),
				`poly7gy` double(10,2),
				`poly8gx` double(10,2),
				`poly8gy` double(10,2),
				PRIMARY KEY (`gridsquare_id`),
				KEY `gxlow` (`gxlow`),
				KEY `gxhigh` (`gxhigh`),
				KEY `gylow` (`gylow`),
				KEY `gyhigh` (`gyhigh`)
			) ENGINE=MyISAM DEFAULT CHARSET=latin1;
		');
		require_once('geograph/conversionslatlong.class.php');
		$conv = new ConversionsLatLong;
		#require_once('geograph/conversions.class.php');
		#$conv = new Conversions;
		foreach ($CONF['references'] as $ri => $rname) {
			$x0 = $CONF['origins'][$ri][0];
			$y0 = $CONF['origins'][$ri][1];
			$latmin = $CONF['latrange'][$ri][0];
			$latmax = $CONF['latrange'][$ri][1];
			$lonmin = $CONF['lonrange'][$ri][0];
			$lonmax = $CONF['lonrange'][$ri][1];
			$riwhere = "(reference_index = '{$ri}')";

			$sql="select x,y,gridsquare_id from gridsquare where $riwhere";

			$recordSet = &$db->Execute($sql);
			while (!$recordSet->EOF) {
				$geBL=($recordSet->fields[0] - $x0) * 1000;
				$gnBL=($recordSet->fields[1] - $y0) * 1000;
				$geTR=$geBL+1000;
				$gnTR=$gnBL+1000;
				$geC=$geBL+500;
				$gnC=$gnBL+500;
				list($glatTL, $glonTL) = $conv->national_to_wgs84($geBL, $gnTR, $ri);
				list($glatBL, $glonBL) = $conv->national_to_wgs84($geBL, $gnBL, $ri);
				list($glatTR, $glonTR) = $conv->national_to_wgs84($geTR, $gnTR, $ri);
				list($glatBR, $glonBR) = $conv->national_to_wgs84($geTR, $gnBL, $ri);
				list($glatC,  $glonC ) = $conv->national_to_wgs84($geC,  $gnC,  $ri);

				list($xMTL,$yMTL) = $conv->wgs84_to_sm($glatTL, $glonTL);
				list($xMBL,$yMBL) = $conv->wgs84_to_sm($glatBL, $glonBL);
				list($xMTR,$yMTR) = $conv->wgs84_to_sm($glatTR, $glonTR);
				list($xMBR,$yMBR) = $conv->wgs84_to_sm($glatBR, $glonBR);
				list($xMC, $yMC ) = $conv->wgs84_to_sm($glatC,  $glonC );

				$dxM = $xMBR - $xMBL;
				$dyM = $yMBR - $yMBL;
				$scale = hypot($dyM, $dxM) / 1000;
				$rotangle = atan2($dyM, $dxM);
				$area = (($yMTL-$yMBR)*($xMTR-$xMBL)+($yMBL-$yMTR)*($xMTL-$xMBR))/2E6;

				$clippoly = array(array($glatTL, $glonTL), array($glatBL, $glonBL), array($glatBR, $glonBR), array($glatTR, $glonTR));
				$this->_clip_polygon($clippoly, $latmin, $latmax, $lonmin, $lonmax);


				#$drawpoly = array();
				#foreach ($clippoly as &$ll) {
				#	$drawpoly[] = $conv->wgs84_to_sm($ll[0], $ll[1]);
				#}
				if (count($clippoly)) {
					$ll = end($clippoly);
					$point = $conv->wgs84_to_sm($ll[0], $ll[1]);
					$xM = $point[0];
					$yM = $point[1];
					$xMmin = $xM;
					$yMmin = $yM;
					$xMmax = $xM;
					$yMmax = $yM;
					$cliparea = 0.0;

					$head = array();
					$tail = array();
					foreach ($clippoly as &$ll) {
						$xMp = $xM;
						$yMp = $yM;
						$point = $conv->wgs84_to_sm($ll[0], $ll[1]);
						$xM = $point[0];
						$yM = $point[1];
						$cliparea += $yM * $xMp - $xM * $yMp;
						if ($xM < $xMmin) {
							$xMmin = $xM;
							$tail = array_merge($tail, $head);
							$head = array();
						}
						if ($xM > $xMmax)
							$xMmax = $xM;
						if ($yM < $yMmin)
							$yMmin = $yM;
						if ($yM > $yMmax)
							$yMmax = $yM;
						$head[] = $point;
					}
					$drawpoly = array_merge($head, $tail);# first element: leftmost point
					$cliparea /= 2E6;
				} else {
					$cliparea = 0.0;
					$xMmin = 0;
					$yMmin = 0;
					$xMmax = 0;
					$yMmax = 0;
					$drawpoly = array();
				}

				$numpoints = count($drawpoly);
				$dbpoly = $drawpoly;
				for($i = $numpoints; $i < 8; ++$i) {
					$dbpoly[] = array(0,0);
				}
				$sql = sprintf("INSERT INTO gridsquare_gmcache_tmp (gridsquare_id, gxlow, gylow, gxhigh, gyhigh, area, cliparea, rotangle, scale, cgx, cgy, polycount, poly1gx, poly1gy, poly2gx, poly2gy, poly3gx, poly3gy, poly4gx, poly4gy, poly5gx, poly5gy, poly6gx, poly6gy, poly7gx, poly7gy, poly8gx, poly8gy) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)",
					$db->Quote($recordSet->fields[2]),
					$db->Quote(floor($xMmin)),
					$db->Quote(floor($yMmin)),
					$db->Quote(ceil ($xMmax)),
					$db->Quote(ceil ($yMmax)),
					$db->Quote($area),
					$db->Quote($cliparea),
					$db->Quote($rotangle),
					$db->Quote($scale),
					$db->Quote($xMC),
					$db->Quote($yMC),
					$db->Quote($numpoints),
					$db->Quote($dbpoly[0][0]),
					$db->Quote($dbpoly[0][1]),
					$db->Quote($dbpoly[1][0]),
					$db->Quote($dbpoly[1][1]),
					$db->Quote($dbpoly[2][0]),
					$db->Quote($dbpoly[2][1]),
					$db->Quote($dbpoly[3][0]),
					$db->Quote($dbpoly[3][1]),
					$db->Quote($dbpoly[4][0]),
					$db->Quote($dbpoly[4][1]),
					$db->Quote($dbpoly[5][0]),
					$db->Quote($dbpoly[5][1]),
					$db->Quote($dbpoly[6][0]),
					$db->Quote($dbpoly[6][1]),
					$db->Quote($dbpoly[7][0]),
					$db->Quote($dbpoly[7][1])
				);
				$dbsq->Execute($sql);//FIXME another db connection?

				$recordSet->MoveNext();
			}
			$recordSet->Close();
		}
		
		$db->Execute("DROP TABLE IF EXISTS gridsquare_gmcache");
		$db->Execute("RENAME TABLE gridsquare_gmcache_tmp TO gridsquare_gmcache");
		
		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}

	function updateGMcache($addlimit = -1, $remlimit = -1)
	{
		global $CONF;
		$db=&$this->_getDB();
		$dbsq=NewADOConnection($GLOBALS['DSN']);
		require_once('geograph/conversionslatlong.class.php');
		require_once('geograph/mapmosaic.class.php');
		$mosaic = new GeographMapMosaic;
		$conv = new ConversionsLatLong;

		$sqladd="select gs.x,gs.y,gs.gridsquare_id,gs.reference_index from gridsquare as gs left join gridsquare_gmcache as gc using  (gridsquare_id) where gc.gridsquare_id is null";
		$sqlrem="select gc.gridsquare_id,gc.cgx,gc.cgy from gridsquare_gmcache as gc left join gridsquare as gs using  (gridsquare_id) where gs.gridsquare_id is null";
		if ($addlimit >= 0)
			$sqladd .= " limit $addlimit";
		if ($remlimit >= 0)
			$sqlrem .= " limit $remlimit";

		$recordSet = &$db->Execute($sqlrem);
		while (!$recordSet->EOF) {
			$sql="DELETE FROM gridsquare_gmcache WHERE gridsquare_id = {$recordSet->fields[0]} LIMIT 1";
			$dbsq->Execute($sql);//FIXME another db connection?
			list($glatC, $glonC)   = $conv->sm_to_wgs84($recordSet->fields[1],$recordSet->fields[2]);
			list($geC, $gnC, $ri) = $conv->wgs84_to_national($glatC, $glonC);
			$x0 = $CONF['origins'][$ri][0];
			$y0 = $CONF['origins'][$ri][1];
			$x = round(($geC - 500) / 1000 + $x0);
			$y = round(($gnC - 500) / 1000 + $y0);
			$mosaic->expirePosition($x,$y,0,true);
			$recordSet->MoveNext();
		}
		$recordSet->Close();

		$recordSet = &$db->Execute($sqladd);
		while (!$recordSet->EOF) {
			$ri = $recordSet->fields[3];
			$x0 = $CONF['origins'][$ri][0];
			$y0 = $CONF['origins'][$ri][1];
			$latmin = $CONF['latrange'][$ri][0];
			$latmax = $CONF['latrange'][$ri][1];
			$lonmin = $CONF['lonrange'][$ri][0];
			$lonmax = $CONF['lonrange'][$ri][1];
			####### see rebuildGMcache # cleanup needed #######
				$geBL=($recordSet->fields[0] - $x0) * 1000;
				$gnBL=($recordSet->fields[1] - $y0) * 1000;
				$geTR=$geBL+1000;
				$gnTR=$gnBL+1000;
				$geC=$geBL+500;
				$gnC=$gnBL+500;
				list($glatTL, $glonTL) = $conv->national_to_wgs84($geBL, $gnTR, $ri);
				list($glatBL, $glonBL) = $conv->national_to_wgs84($geBL, $gnBL, $ri);
				list($glatTR, $glonTR) = $conv->national_to_wgs84($geTR, $gnTR, $ri);
				list($glatBR, $glonBR) = $conv->national_to_wgs84($geTR, $gnBL, $ri);
				list($glatC,  $glonC ) = $conv->national_to_wgs84($geC,  $gnC,  $ri);

				list($xMTL,$yMTL) = $conv->wgs84_to_sm($glatTL, $glonTL);
				list($xMBL,$yMBL) = $conv->wgs84_to_sm($glatBL, $glonBL);
				list($xMTR,$yMTR) = $conv->wgs84_to_sm($glatTR, $glonTR);
				list($xMBR,$yMBR) = $conv->wgs84_to_sm($glatBR, $glonBR);
				list($xMC, $yMC ) = $conv->wgs84_to_sm($glatC,  $glonC );

				$dxM = $xMBR - $xMBL;
				$dyM = $yMBR - $yMBL;
				$scale = hypot($dyM, $dxM) / 1000;
				$rotangle = atan2($dyM, $dxM);
				$area = (($yMTL-$yMBR)*($xMTR-$xMBL)+($yMBL-$yMTR)*($xMTL-$xMBR))/2E6;

				$clippoly = array(array($glatTL, $glonTL), array($glatBL, $glonBL), array($glatBR, $glonBR), array($glatTR, $glonTR));
				$this->_clip_polygon($clippoly, $latmin, $latmax, $lonmin, $lonmax);


				#$drawpoly = array();
				#foreach ($clippoly as &$ll) {
				#	$drawpoly[] = $conv->wgs84_to_sm($ll[0], $ll[1]);
				#}
				if (count($clippoly)) {
					$ll = end($clippoly);
					$point = $conv->wgs84_to_sm($ll[0], $ll[1]);
					$xM = $point[0];
					$yM = $point[1];
					$xMmin = $xM;
					$yMmin = $yM;
					$xMmax = $xM;
					$yMmax = $yM;
					$cliparea = 0.0;

					$head = array();
					$tail = array();
					foreach ($clippoly as &$ll) {
						$xMp = $xM;
						$yMp = $yM;
						$point = $conv->wgs84_to_sm($ll[0], $ll[1]);
						$xM = $point[0];
						$yM = $point[1];
						$cliparea += $yM * $xMp - $xM * $yMp;
						if ($xM < $xMmin) {
							$xMmin = $xM;
							$tail = array_merge($tail, $head);
							$head = array();
						}
						if ($xM > $xMmax)
							$xMmax = $xM;
						if ($yM < $yMmin)
							$yMmin = $yM;
						if ($yM > $yMmax)
							$yMmax = $yM;
						$head[] = $point;
					}
					$drawpoly = array_merge($head, $tail);# first element: leftmost point
					$cliparea /= 2E6;
				} else {
					$cliparea = 0.0;
					$xMmin = 0;
					$yMmin = 0;
					$xMmax = 0;
					$yMmax = 0;
					$drawpoly = array();
				}

				$numpoints = count($drawpoly);
				$dbpoly = $drawpoly;
				for($i = $numpoints; $i < 8; ++$i) {
					$dbpoly[] = array(0,0);
				}
				$sql = sprintf("INSERT INTO gridsquare_gmcache (gridsquare_id, gxlow, gylow, gxhigh, gyhigh, area, cliparea, rotangle, scale, cgx, cgy, polycount, poly1gx, poly1gy, poly2gx, poly2gy, poly3gx, poly3gy, poly4gx, poly4gy, poly5gx, poly5gy, poly6gx, poly6gy, poly7gx, poly7gy, poly8gx, poly8gy) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)",
					$db->Quote($recordSet->fields[2]),
					$db->Quote(floor($xMmin)),
					$db->Quote(floor($yMmin)),
					$db->Quote(ceil ($xMmax)),
					$db->Quote(ceil ($yMmax)),
					$db->Quote($area),
					$db->Quote($cliparea),
					$db->Quote($rotangle),
					$db->Quote($scale),
					$db->Quote($xMC),
					$db->Quote($yMC),
					$db->Quote($numpoints),
					$db->Quote($dbpoly[0][0]),
					$db->Quote($dbpoly[0][1]),
					$db->Quote($dbpoly[1][0]),
					$db->Quote($dbpoly[1][1]),
					$db->Quote($dbpoly[2][0]),
					$db->Quote($dbpoly[2][1]),
					$db->Quote($dbpoly[3][0]),
					$db->Quote($dbpoly[3][1]),
					$db->Quote($dbpoly[4][0]),
					$db->Quote($dbpoly[4][1]),
					$db->Quote($dbpoly[5][0]),
					$db->Quote($dbpoly[5][1]),
					$db->Quote($dbpoly[6][0]),
					$db->Quote($dbpoly[6][1]),
					$db->Quote($dbpoly[7][0]),
					$db->Quote($dbpoly[7][1])
				);
				$dbsq->Execute($sql);//FIXME another db connection?
			####### see rebuildGMcache # cleanup needed #######
			$mosaic->expirePosition($recordSet->fields[0],$recordSet->fields[1],0,true);
			$recordSet->MoveNext();
		}
		$recordSet->Close();
		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}

	/**
	* create tlabelmap, save as png image and return the image resource
	* @access private
	*/
	function& _createTLabelmapM($file)
	{
		global $CONF;
		$destw = $this->image_w;
		$destbdry = $this->render_margin;
		$img=imagecreatetruecolor($destw+2*$destbdry,$destw+2*$destbdry);

		$back=imagecolorallocatealpha ($img, 0, 0, 0, 127);
		imagefill($img,0,0,$back);

		$widthM=$this->map_wM;
		$dM=$widthM/8;
		$leftM=$this->map_xM;
		$bottomM=$this->map_yM;
		$rightM=$leftM+$widthM;
		$topM=$bottomM+$widthM;

		##//plot grid square?
		#if ($this->level >= 5)
		#{
		#	$this->_plotGridLinesM($img,$leftM-$dM,$bottomM-$dM,$rightM+$dM,$topM+$dM);
		#}

		#if ($this->pixels_per_km>=1  && $this->pixels_per_km<=64 && isset($CONF['enable_newmap'])) {
		if ($this->level >= 6  /*&& $this->level <= 13*/ && isset($CONF['enable_newmap'])) {
			$this->_plotPlacenamesM($img,$leftM,$bottomM,$rightM,$topM);
		}

		$this->_resizeImageM($img, $destw, $destbdry, $destw, 0);
		imagesavealpha($img, true);
		imagealphablending($img, false);
		#imagegd($img, $file); # gd does not like alpha components. I really like php.
		imagepng($img, $file);
		return $img;
	}

	/**
	* create labelmap, save as png image and return the image resource
	* @access private
	*/
	function& _createLabelmapM($file)
	{
		global $CONF;
		$destw = $this->image_w;
		$destbdry = $this->render_margin;
		$img=imagecreatetruecolor($destw+2*$destbdry,$destw+2*$destbdry);

		$back=imagecolorallocatealpha ($img, 0, 0, 0, 127);
		imagefill($img,0,0,$back);

		$widthM=$this->map_wM;
		$dM=$widthM/8;
		$leftM=$this->map_xM;
		$bottomM=$this->map_yM;
		$rightM=$leftM+$widthM;
		$topM=$bottomM+$widthM;

		#//plot grid square?
		if ($this->level >= 4)
		{
			$this->_plotGridLinesM($img,$leftM-$dM,$bottomM-$dM,$rightM+$dM,$topM+$dM);
		}

		##if ($this->pixels_per_km>=1  && $this->pixels_per_km<=64 && isset($CONF['enable_newmap'])) {
		#if ($this->level >= 6  && $this->level <= 13 && isset($CONF['enable_newmap'])) {
		#	$this->_plotPlacenamesM($img,$leftM,$bottomM,$rightM,$topM);
		#}

		$this->_resizeImageM($img, $destw, $destbdry, $destw, 0);
		imagesavealpha($img, true);
		imagealphablending($img, false);
		#imagegd($img, $file); # gd does not like alpha components. I really like php.
		imagepng($img, $file);
		return $img;
	}

	/**
	* create regionmap, save as png image and return the image resource
	* @access private
	*/
	function& _createRegionmapM($file)
	{
		global $CONF;
		$bdry = $this->base_margin;
		$imgw = $this->base_width;
		$destw = $this->image_w;

		$img=imagecreatetruecolor($imgw+2*$bdry,$imgw+2*$bdry);
		imagealphablending($img, false);
		
		//fill in with sea
		$back=imagecolorallocatealpha ($img, 0, 0, 0, 127);
		imagefill($img,0,0,$back);
		if (isset($CONF['gmhierlevels'][$this->level])) {
			$hierlevel = $CONF['gmhierlevels'][$this->level];
		
			//set greens to use for percentages
			$land=array( 0 => $back );
			for ($p=1; $p<=127; $p++)
			{
				$land[$p]=imagecolorallocatealpha($img, 0, 0, 0, 127-$p);
			}

			$widthM=$this->map_wM;
			$leftM=$this->map_xM;
			$bottomM=$this->map_yM;
			$rightM=$leftM+$widthM;
			$topM=$bottomM+$widthM;
			$db=&$this->_getDB();
			$sql="select percent_land,mappal,norm,cliparea,area,polycount,poly1gx,poly1gy,poly2gx,poly2gy,poly3gx,poly3gy,poly4gx,poly4gy,poly5gx,poly5gy,poly6gx,poly6gy,poly7gx,poly7gy,poly8gx,poly8gy from gridsquare_gmcache inner join gridsquare using(gridsquare_id) inner join gridsquare_mappal using(gridsquare_id) where gxlow <= $rightM and gxhigh >= $leftM and gylow <= $topM and gyhigh >= $bottomM and level = $hierlevel";
			$recordSet = &$db->Execute($sql);
			while (!$recordSet->EOF) {
				$percent_land = $recordSet->fields[0];
				$land_part = $percent_land < 0 ? 0. : $percent_land/100.;
				$zone_part = $recordSet->fields[3]/$recordSet->fields[4];
				$pal = $recordSet->fields[1];
				$pal_norm = $recordSet->fields[2];
				if ($pal_norm < .00001) {
					$pal = 0;
				} elseif ($land_part < $pal_norm) {
					$pal *= $land_part/$pal_norm;
				}
				if ($zone_part > 0) {
					$pal /= $zone_part;
				}
				$pal = max(0, min(127, round($pal*8)));

				$points = $recordSet->fields[5];
				$drawpoly = array();
				for ($i = 0; $i < $points; ++$i) {
					$xM = $recordSet->fields[6+$i*2];
					$yM = $recordSet->fields[7+$i*2];
					$drawpoly[] = $bdry + round(($xM - $leftM)   / $widthM * $imgw);
					$drawpoly[] = $bdry + $imgw - 1 - round(($yM - $bottomM) / $widthM * $imgw);
				}

				imagefilledpolygon($img, $drawpoly, $points, $land[$pal]);

				$recordSet->MoveNext();
			}
			$recordSet->Close();
		}

		$this->_resizeImageM($img, $imgw, $bdry, $destw, 0);
		imagesavealpha($img, true);
		imagealphablending($img, false);
		imagepng($img, $file);
		return $img;
	}

	/**
	* create basemap, save as gd image and return the image resource
	* @access private
	*/
	function& _createBasemapM($file)
	{
		global $CONF;
		$bdry = $this->base_margin;
		$imgw = $this->base_width;
		$destw = $this->image_w;

		$img=imagecreatetruecolor($imgw+2*$bdry,$imgw+2*$bdry);
		
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

		$widthM=$this->map_wM;
		$leftM=$this->map_xM;
		$bottomM=$this->map_yM;
		$rightM=$leftM+$widthM;
		$topM=$bottomM+$widthM;
		$db=&$this->_getDB();
#SELECT * FROM `gridsquare_gmcache` mc INNER JOIN `gridsquare` gs ON (mc.gridsquare_id = gs.gridsquare_id AND `gxlow` <=1010000 AND `gxhigh` >=1000000 AND `gylow` <=6510000 AND `gyhigh` >=6500000)
#Zeige Datensätze 0 - 29 (56 insgesamt, die Abfrage dauerte 0.1857 sek.)
#
#
#SELECT * FROM `gridsquare_gmcache`  INNER JOIN `gridsquare` USING (`gridsquare_id`) WHERE `gxlow` <=1010000 AND `gxhigh` >=1000000 AND `gylow` <=6510000 AND `gyhigh` >=6500000
#Zeige Datensätze 0 - 29 (56 insgesamt, die Abfrage dauerte 0.1767 sek.)
		$sql="select percent_land,cliparea,area,polycount,poly1gx,poly1gy,poly2gx,poly2gy,poly3gx,poly3gy,poly4gx,poly4gy,poly5gx,poly5gy,poly6gx,poly6gy,poly7gx,poly7gy,poly8gx,poly8gy from gridsquare_gmcache inner join gridsquare using(gridsquare_id) where gxlow <= $rightM and gxhigh >= $leftM and gylow <= $topM and gyhigh >= $bottomM";
		$recordSet = &$db->Execute($sql);
		while (!$recordSet->EOF) {
			$percent_land = $recordSet->fields[0];
			$zone_part = $recordSet->fields[1]/$recordSet->fields[2];
			if ($zone_part > 0 && $percent_land > 0) {
				$percent_land /= $zone_part;
				$percent_land = min(100,$percent_land);
			}

			$points = $recordSet->fields[3];
			$drawpoly = array();
			for ($i = 0; $i < $points; ++$i) {
				$xM = $recordSet->fields[4+$i*2];
				$yM = $recordSet->fields[5+$i*2];
				$drawpoly[] = $bdry + round(($xM - $leftM)   / $widthM * $imgw);
				$drawpoly[] = $bdry + $imgw - 1 - round(($yM - $bottomM) / $widthM * $imgw);
			}

			imagefilledpolygon($img, $drawpoly, $points, $land[$percent_land]);

			$recordSet->MoveNext();
		}
		$recordSet->Close();

		$this->_resizeImageM($img, $imgw, $bdry, $destw, 0);
		imagepng($img, $file);
		return $img;
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
		$land[-1]=imagecolorallocate($img, $rmin,$gmin,$bmin);

		if (empty($this->force_ri)) {
			$riwhere = '';
		} else {
			$riwhere = "(reference_index = '{$this->force_ri}') and ";
		}

		//paint the land
		$db=&$this->_getDB();
			
		$rectangle = "'POLYGON(($left $bottom,$right $bottom,$right $top,$left $top,$left $bottom))'";
		
		//now plot all squares in the desired area
		$sql="select x,y,percent_land,reference_index from gridsquare where $riwhere
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
		
		if ($this->mercator) {
			$widthM=$this->map_wM;
			$leftM=$this->map_xM;
			$bottomM=$this->map_yM;
			$rightM=$leftM+$widthM;
			$topM=$bottomM+$widthM;
			$sql="select gridimage_id from gridimage_search inner join gridsquare_gmcache using(gridsquare_id) where user_id = $user_id and gxlow <= $rightM and gxhigh >= $leftM and gylow <= $topM and gyhigh >= $bottomM";
		} else {
			//$dbImg=NewADOConnection($GLOBALS['DSN']);//FIXME?

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
		}
		$id = $db->getOne($sql);
		
		return !empty($id);
	}

	/**
	* resize image. assumptions: $destbdry == 0 _OR_ $bdry/$width == $destbdry/$destwidth
	* @access private
	*/
	function _resizeImageM(&$img, $width, $bdry, $destwidth, $destbdry)
	{
		if ($width != $destwidth) { # rescale
			if ($bdry != 0 && $destbdry == 0) { # remove bdry
				$resized = imagecreatetruecolor($destwidth,$destwidth);
				imagealphablending($resized, false);
				imagecopyresampled($resized, $img, 0, 0, $bdry, $bdry, 
						$destwidth,$destwidth, $width, $width);
			} else {
				$destwb = $destwidth+2*$destbdry;
				$wb = $width+2*$bdry;
				$resized = imagecreatetruecolor($destwb,$destwb);
				imagealphablending($resized, false);
				imagecopyresampled($resized, $img, 0, 0, 0, 0, 
						$destwb,$destwb, $wb, $wb);
			}
			imagedestroy($img);
			$img = $resized;
		} elseif ($bdry != 0 && $destbdry == 0) { # remove bdry
			$resized = imagecreatetruecolor($destwidth,$destwidth);
			imagealphablending($resized, false);
			imagecopy($resized, $img, 0, 0, $bdry, $bdry, 
					$destwidth,$destwidth);
			
			imagedestroy($img);
			$img = $resized;
		} else { #nothing to do
		}
	}

	/**
	* create squaremap, save as png image and return the image resource
	* @access private
	*/
	function& _createSquaremapM($file)
	{
		global $CONF;
		$ok = true;

		if ($this->type_or_user == -10) {
			$imgw = $this->image_w;
			$img = imagecreatetruecolor($imgw, $imgw);
			if (!$img) {
				return img; #FIXME
			}
			imagealphablending($img, true);
			imagesavealpha($img, true);
			$back = imagecolorallocatealpha ($img, 0, 0, 0, 127);
			imagefill($img, 0, 0, $back);
			imagealphablending($img, false);
			imagepng($img, $file);
			return $img; #FIXME $ok?
		}

		$bdry = $this->base_margin;
		$imgw = $this->base_width;
		$img=imagecreatetruecolor($imgw+2*$bdry,$imgw+2*$bdry);
		if (!$img) {
			return img; #FIXME
		}
		imagealphablending($img, true);
		imagesavealpha($img, true);
		$back=imagecolorallocatealpha ($img, 0, 0, 0, 127);
		imagefill($img,0,0,$back);

		$widthM=$this->map_wM;
		$dM=$widthM/8;
		$leftM=$this->map_xM;
		$bottomM=$this->map_yM;
		$rightM=$leftM+$widthM;
		$topM=$bottomM+$widthM;
		$colMarker=imagecolorallocate($img, $this->colour['marker'][0],$this->colour['marker'][1],$this->colour['marker'][2]);
		$colSuppMarker=imagecolorallocate($img, $this->colour['suppmarker'][0],$this->colour['suppmarker'][1],$this->colour['suppmarker'][2]);
		$colBorder=imagecolorallocate($img, $this->colour['border'][0],$this->colour['border'][1],$this->colour['border'][2]);
		if ($this->type_or_user == -1) {
			$maxcount = 80;
			$colours=array();
			$last=$lastcolour=null;
			for ($o = 0; $o <= $maxcount; $o++) {
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
					$colours[$o] = $lastcolour;
				} else {
					$lastcolour = $colours[$o] = imagecolorallocate($img, $r,$g,$b);
				}
				$last = $key;
			}
		}

		$db=&$this->_getDB();
		if ($this->level >= 12) {
			$dbImg=NewADOConnection($GLOBALS['DSN']);
			imagealphablending($img, true);
			#$gridcol2=imagecolorallocate ($img, 60,205,252);
		}

		if ($this->type_or_user == 0) {
			# coverage map
			$number = !empty($this->minimum)?intval($this->minimum):0;
			#$sql="select x,y,gridsquare_id,has_geographs from gridsquare where $riwhere
			#	CONTAINS( GeomFromText($rectangle),	point_xy)
			#	and imagecount>$number";
			#FIXME remove reference_index,x,y
			$whereuser = '';
			$sql="select gridsquare_id,has_geographs,scale,rotangle,cgx,cgy,polycount,poly1gx,poly1gy,poly2gx,poly2gy,poly3gx,poly3gy,poly4gx,poly4gy,poly5gx,poly5gy,poly6gx,poly6gy,poly7gx,poly7gy,poly8gx,poly8gy from gridsquare_gmcache inner join gridsquare using(gridsquare_id) where gxlow <= $rightM and gxhigh >= $leftM and gylow <= $topM and gyhigh >= $bottomM and imagecount > $number";
		} else if ($this->type_or_user > 0) {
			# personal map
			#$sql="select x,y,grid_reference,sum(moderation_status = 'geograph') as has_geographs from gridimage_search where $riwhere
			#	CONTAINS( GeomFromText($rectangle),	point_xy) and
			#	user_id = {$this->type_or_user} group by grid_reference";
			$whereuser = "and user_id = {$this->type_or_user}";
			$sql="select gridsquare_id,sum(moderation_status = 'geograph') as has_geographs,scale,rotangle,cgx,cgy,polycount,poly1gx,poly1gy,poly2gx,poly2gy,poly3gx,poly3gy,poly4gx,poly4gy,poly5gx,poly5gy,poly6gx,poly6gy,poly7gx,poly7gy,poly8gx,poly8gy from gridsquare_gmcache inner join gridimage_search using(gridsquare_id) where gxlow <= $rightM and gxhigh >= $leftM and gylow <= $topM and gyhigh >= $bottomM $whereuser group by gridsquare_id";
		} else if ($this->type_or_user == -1) {
			# depth map
			$number = !empty($this->minimum)?intval($this->minimum):0;
			#$sql="select x,y,gridsquare_id,imagecount from gridsquare where $riwhere
			#	CONTAINS( GeomFromText($rectangle),	point_xy)
			#	and imagecount>$number"; #and percent_land = 100  #can uncomment this if using the standard green base
			$whereuser = '';
			$sql="select gridsquare_id,imagecount,scale,rotangle,cgx,cgy,polycount,poly1gx,poly1gy,poly2gx,poly2gy,poly3gx,poly3gy,poly4gx,poly4gy,poly5gx,poly5gy,poly6gx,poly6gy,poly7gx,poly7gy,poly8gx,poly8gy from gridsquare_gmcache inner join gridsquare using(gridsquare_id) where gxlow <= $rightM and gxhigh >= $leftM and gylow <= $topM and gyhigh >= $bottomM and imagecount > $number";
		}
		$recordSet = &$db->Execute($sql);
		while (!$recordSet->EOF) {
			if ($this->type_or_user < 0) {
				$imgcount = $recordSet->fields[1];
				$color = $colours[$imgcount <= $maxcount ? $imgcount : $maxcount];
			} else {
				$has_geographs = $recordSet->fields[1];
				$color = $has_geographs ? $colMarker : $colSuppMarker;
			}

			if ($this->level <= 11 || $this->overlay || $this->type_or_user < 0) {
				$points = $recordSet->fields[6];
				$drawpoly = array();
				for ($i = 0; $i < $points; ++$i) {
					$xM = $recordSet->fields[7+$i*2];
					$yM = $recordSet->fields[8+$i*2];
					$drawpoly[] = $bdry + round(($xM - $leftM)   / $widthM * $imgw);
					$drawpoly[] = $bdry + $imgw - 1 - round(($yM - $bottomM) / $widthM * $imgw);
				}
				imagefilledpolygon($img, $drawpoly, $points, $color);
			} else {
				#if (!empty($this->type_or_user)) { #FIXME
				#	$grid_reference=$recordSet->fields[2];
				#
				#	$sql="select * from gridimage_search where grid_reference='$grid_reference' 
				#	and user_id = {$this->type_or_user} order by moderation_status+0 desc,seq_no limit 1";
				#} else {
					$gridsquare_id=$recordSet->fields[0];
			
					$sql="select * from gridimage where gridsquare_id=$gridsquare_id 
					and moderation_status in ('accepted','geograph') $whereuser order by moderation_status+0 desc,seq_no limit 1";
				
				#}

				//echo "$sql\n";	
				$rec=$dbImg->GetRow($sql);
				if (count($rec)) {
					$gridimage=new GridImage;
					$gridimage->fastInit($rec);
					$crop = 1.0;
					$thumbsize = $CONF['gmthumbsize12'] * pow(2,$this->level-12);
					#######################################################FIXME
					#$ri = $recordSet->fields[21]; #FIXME
					#$x0 = $CONF['origins'][$ri][0];
					#$y0 = $CONF['origins'][$ri][1];
					#$eC=($recordSet->fields[22] - $x0) * 1000 + 500;
					#$nC=($recordSet->fields[23] - $y0) * 1000 + 500;
					#require_once('geograph/conversionslatlong.class.php');
					#$conv = new ConversionsLatLong;
					#list($latC, $lonC) = $conv->national_to_wgs84($eC, $nC, $ri);
					#list($xMC,$yMC) = $conv->wgs84_to_sm($latC, $lonC);#FIXME $xMC,$yMC -> database
					#######################################################
					$xMC = $recordSet->fields[4];
					$yMC = $recordSet->fields[5];
					$xc = $bdry + round(($xMC - $leftM)   / $widthM * $imgw);
					$yc = $bdry + $imgw - 1 - round(($yMC - $bottomM) / $widthM * $imgw);
					$xi = $xc - floor($thumbsize/2);
					$yi = $yc - floor($thumbsize/2);
					#trigger_error("ph {$gridimage->gridimage_id}", E_USER_NOTICE);
					$points = $recordSet->fields[6];
					$photopoly = array();
					#$framepoly = array();
					for ($i = 0; $i < $points; ++$i) {
						$xM = $recordSet->fields[7+$i*2];
						$yM = $recordSet->fields[8+$i*2];
						$xf = $bdry + round(($xM - $leftM)   / $widthM * $imgw);
						$yf = $bdry + $imgw - 1 - round(($yM - $bottomM) / $widthM * $imgw);
						$xp = $xf - $xi;
						$yp = $yf - $yi;
						#$framepoly[] = $xf;
						#$framepoly[] = $yf;
						#trigger_error("ph<-$xp $yp", E_USER_NOTICE);
						$p = array($xp, $yp);
						$photopoly[] = $p;
					}
					#trigger_error("ph->".implode($photopoly, ', '), E_USER_NOTICE);
					#$ps = '';
					#foreach ($photopoly as &$p) {
					#	$ps .= '(' . implode($p, ', ') . '), ';
					#}
					#trigger_error("ph-> $ps", E_USER_NOTICE);
					if ($has_geographs) {
						$photo=$gridimage->getPolyThumb($thumbsize,$crop,$photopoly,"M{$this->tile_x}-{$this->tile_y}");
					} else {
						$scale = $recordSet->fields[2];
						$rotangle = $recordSet->fields[3];
						#trigger_error("pd-- $scale $rotangle", E_USER_NOTICE);
						$sinrot = sin($rotangle);
						$cosrot = cos($rotangle);
						$radius = 9*pow(2,$this->level-12)*$scale;
						$xmarker = $radius*(-$sinrot-$cosrot)+floor($thumbsize/2);
						$ymarker = $radius*(+$sinrot-$cosrot)+floor($thumbsize/2);
						$mp = array($xmarker, $ymarker);
						$photo=$gridimage->getPolyThumb($thumbsize,$crop,$photopoly,"MS{$this->tile_x}-{$this->tile_y}", $mp, $this->colour['suppmarker']);
					}
					if (!is_null($photo)) {
						imagealphablending($photo, true);
						imagecopy($img, $photo, $xi, $yi, 0, 0, $thumbsize, $thumbsize);
						imagedestroy($photo);
						#$framepoints = count($framepoly)/2;
						#if ($framepoints) {
						#	imagepolygon($img, $framepoly, count($framepoly)/2, $gridcol2);
						#}
					} else {
						$ok = false;
					}
				}
			}
			$recordSet->MoveNext();
		}
		$recordSet->Close();

		$destw = $this->image_w;
		$this->_resizeImageM($img, $imgw, $bdry, $destw, 0);
		imagesavealpha($img, true);
		imagealphablending($img, false);
		imagepng($img, $file);
		return $img; #FIXME $ok?
	}

	/**
	* render the image to cached file if not already available
	* @access private
	*/
	function _renderImageM()
	{
		global $CONF;
		$root=&$_SERVER['DOCUMENT_ROOT'];
		
		if (($this->layers & 31) == 0) {
			return false;
		}
		$ok = true;
		$baseimg = null;
		$labelimg = null;
		$squareimg = null;
		$regionimg = null;
		$layers = array();

		if ($this->layers & 1) {
			//first of all, generate or pull in a cached based map
			$basemap=$this->getBaseMapFilename();
			if ($this->caching && @file_exists($root.$basemap))
			{
				//load it up!
				$baseimg=imagecreatefrompng($root.$basemap);

			}
			else
			{
				//we need to generate a basemap
				$baseimg=&$this->_createBasemapM($root.$basemap);
			}
			
			if (!$baseimg) {
				return false;
			}
			$layers[] =& $baseimg;
		}
		if (($this->layers & 2) && ($this->type_or_user != -10 || $this->layers == 2)) {
			$squaremap=$this->getImageFilename(2);
			if ($this->caching_squaremap && @file_exists($root.$squaremap))
			{
				//load it up!
				$squareimg=imagecreatefrompng($root.$squaremap);
			}
			else
			{
				//we need to generate a squaremap
				$squareimg=&$this->_createSquaremapM($root.$squaremap);
				#FIXME tilecache
			}
			
			if (!$squareimg) {
				if (!is_null($baseimg)) {
					imagedestroy($baseimg);
				}
				#if (!is_null($labelimg)) {
				#	imagedestroy($labelimg);
				#}
				return false;
			}
			$layers[] =& $squareimg;
		}
		if ($this->layers & 4) {
			$regionmap=$this->getLabelMapFilename(false,true);
			if ($this->caching && @file_exists($root.$regionmap))
			{
				//load it up!
				$regionimg=imagecreatefrompng($root.$regionmap);
			}
			else
			{
				//we need to generate a regionmap
				$regionimg=&$this->_createRegionmapM($root.$regionmap);
			}
			
			if (!$regionimg) {
				if (!is_null($squareimg)) {
					imagedestroy($squareimg);
				}
				if (!is_null($baseimg)) {
					imagedestroy($baseimg);
				}
				return false;
			}
			$layers[] =& $regionimg;
		}
		if ($this->layers & 8) {
			$labelmap=$this->getLabelMapFilename(false,false);
			if ($this->caching && @file_exists($root.$labelmap))
			{
				//load it up!
				$labelimg=imagecreatefrompng($root.$labelmap);
			}
			else
			{
				//we need to generate a labelmap
				$labelimg=&$this->_createLabelmapM($root.$labelmap);
			}
			
			if (!$labelimg) {
				if (!is_null($regionimg)) {
					imagedestroy($regionimg);
				}
				if (!is_null($squareimg)) {
					imagedestroy($squareimg);
				}
				if (!is_null($baseimg)) {
					imagedestroy($baseimg);
				}
				return false;
			}
			$layers[] =& $labelimg;
		}
		if ($this->layers & 16) {
			$tlabelmap=$this->getLabelMapFilename(true,false);
			if ($this->caching && @file_exists($root.$tlabelmap))
			{
				//load it up!
				$tlabelimg=imagecreatefrompng($root.$tlabelmap);
			}
			else
			{
				//we need to generate a tlabelmap
				$tlabelimg=&$this->_createTLabelmapM($root.$tlabelmap);
			}
			
			if (!$tlabelimg) {
				if (!is_null($labelimg)) {
					imagedestroy($labelimg);
				}
				if (!is_null($regionimg)) {
					imagedestroy($regionimg);
				}
				if (!is_null($squareimg)) {
					imagedestroy($squareimg);
				}
				if (!is_null($baseimg)) {
					imagedestroy($baseimg);
				}
				return false;
			}
			$layers[] =& $tlabelimg;
		}
		if (count($layers) == 1) {
			return $ok;
		}

		#foreach ($layers as &$layerimg) {
		#	imagealphablending($layerimg, true);
		#}
		$destw = $this->image_w;
		imagealphablending($layers[0], true);
		for ($i = 1; $i < count($layers);++$i) {
			imagealphablending($layers[$i], true);
			imagecopy($layers[0], $layers[$i],      0, 0, 0, 0, $destw, $destw);
			imagedestroy($layers[$i]);
		}

		$target=$this->getImageFilename();
		
		#if (preg_match('/jpg/',$target)) {
		#	$ok = (imagejpeg($layers[0], $root.$target) && $ok); # FIXME remove?
		#} else {
			imagesavealpha($layers[0], true);
			imagealphablending($layers[0], false);
			$ok = (imagepng($layers[0], $root.$target) && $ok);
		#}

		return $ok;
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
			/*
			if ($this->type_or_user > 0)
				$alias_count/=2;
			elseif ($this->pixels_per_km<=0.18)
				$alias_count*=7;
			elseif ($this->pixels_per_km==0.3)
				$alias_count*=3;
			*/
			
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
		
		if (empty($this->force_ri)) {
			$riwhere = '';
		} else {
			$riwhere = "(reference_index = '{$this->force_ri}') and ";
		}

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
				//FIXME what is gridsquare3?
				$sql="select x,y,gridsquare_id,max(created) > date_sub(now(),interval 30 day) as has_geographs from gridsquare3
					inner join mapfix_log using (gridsquare_id)  
					group by gridsquare_id";
			} else {
				$sql="select x,y,grid_reference,sum(moderation_status = 'geograph') as has_geographs from gridimage_search where $riwhere
					CONTAINS( GeomFromText($rectangle),	point_xy) and
					user_id = {$this->type_or_user} group by grid_reference";
			}
		} else {
			$number = !empty($this->minimum)?intval($this->minimum):0;
			$sql="select x,y,gridsquare_id,has_geographs from gridsquare where $riwhere
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
			elseif ($this->pixels_per_km<32)
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
							imagefilledrectangle ($img, $imgx1+2, $imgy1+3, $imgx1+6, $imgy1+5, $colSuppMarker);
							imagefilledrectangle ($img, $imgx1+3, $imgy1+2, $imgx1+5, $imgy1+6, $colSuppMarker);
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
			//trigger_error("->img: pix: " . $this->pixels_per_km .", newmap: " . $CONF['enable_newmap'], E_USER_NOTICE);

			//ok being false isnt fatal, as we can create a tile, however we should use it to try again later!
			
			//plot grid square?
			if ($this->pixels_per_km>=0)
			{
				$this->_plotGridLines($img,$scanleft,$scanbottom,$scanright,$scantop,$bottom,$left);
			}

			if ($this->pixels_per_km>=1  && $this->pixels_per_km<=64 && isset($CONF['enable_newmap']))
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
			//trigger_error("->!img: pix: " . $this->pixels_per_km .", newmap: " . $CONF['enable_newmap'], E_USER_NOTICE);
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

		if (empty($this->force_ri)) {
			$riwhere = '';
		} else {
			$riwhere = "(reference_index = '{$this->force_ri}') and ";
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
			where $riwhere CONTAINS( GeomFromText($rectangle),	point_xy) and
			submitted < '{$this->mapDateStart}'
			group by gi.gridsquare_id ";
		} else {
		$sql="select x,y,gridsquare_id,imagecount from gridsquare where $riwhere
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
			
			if ($this->pixels_per_km>=1  && $this->pixels_per_km<32 && isset($CONF['enable_newmap'])) {
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

		if (empty($this->force_ri)) {
			$riwhere = '';
		} else {
			$riwhere = "(reference_index = '{$this->force_ri}') and ";
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

		if (empty($this->displayYear)) {
			$this->_plotGridLines($img,$scanleft,$scanbottom,$scanright,$scantop,$bottom,$left,true);
		}
		$rectangle = "'POLYGON(($scanleft $scanbottom,$scanright $scanbottom,$scanright $scantop,$scanleft $scantop,$scanleft $scanbottom))'";

		if (!empty($this->displayYear)) {
			$sql="select x,y,'' as dummy
				from 
				gridsquare gs 
				inner join gridimage gi using(gridsquare_id)
				where $riwhere CONTAINS( GeomFromText($rectangle),	point_xy) and
				imagetaken LIKE '{$this->displayYear}%'
				group by gi.gridsquare_id ";
		
		} else {
			$sql="select x,y,sum(submitted > '$mapDateCrit')
				from 
				gridsquare gs 
				inner join gridimage gi using(gridsquare_id)
				where $riwhere CONTAINS( GeomFromText($rectangle),	point_xy) and
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

				if ($this->pixels_per_km>=1  && $this->pixels_per_km<32 && isset($CONF['enable_newmap']))
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

		if (empty($this->force_ri)) {
			$riwhere = '';
		} else {
			$riwhere = "(reference_index = '{$this->force_ri}') and ";
		}
		

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

		if (empty($this->force_ri)) {
			if (!$this->reference_index) {
				$this->getGridRef(-1,-1);
				if (!$this->reference_index) {
					$this->getGridRef(-1,-1);
					$this->reference_index = 1;
				}
			}
			
			$reference_index = $this->reference_index;
			$riwhere = '';
		} else {
			$reference_index = $this->force_ri;
			$riwhere = "(reference_index = '{$this->force_ri}') and ";
		}
		
		$gridcol=imagecolorallocate ($img, 109,186,178);

		list($natleft,$natbottom) = $conv->internal_to_national($scanleft,$scanbottom,$reference_index);
		list($natright,$nattop) = $conv->internal_to_national($scanright,$scantop,$reference_index);

		if ($this->pixels_per_km < 1) {
			$div = 500000; //1 per 500k square
			$crit = "s = '1' AND";
			$cityfont = 3;
		} elseif ($this->pixels_per_km <= 2) { //FIXME limit?
			$div = 100000; 
			$crit = "(s = '1' OR s = '2') AND";
			$cityfont = 3;
		} elseif ($this->pixels_per_km <= 4) { //FIXME limit?
			$div = 30000;
		#	$crit = "(s = '1' OR s = '2') AND";
			$crit = "(s IN ('1','2','3')) AND";
			$cityfont = 3;
		} else {
			$div = 10000;
			$crit = "(s IN ('1','2','3','4')) AND";
			$cityfont = 3;
		}

		$crit = $riwhere.$crit;

		$intleft=$scanleft*1000;
		$intright=$scanright*1000;
		$intbottom=$scanbottom*1000;
		$inttop=$scantop*1000;
		$rectangle = "'POLYGON(($natleft $natbottom,$natright $natbottom,$natright $nattop,$natleft $nattop,$natleft $natbottom))'";
		$rectanglexy = "'POLYGON(($intleft $intbottom,$intright $intbottom,$intright $inttop,$intleft $inttop,$intleft $intbottom))'";
		#trigger_error($rectanglexy, E_USER_NOTICE);
		

if ($reference_index == 1 || ($reference_index == 2 && $this->pixels_per_km == 1 ) || $reference_index >= 3) {
	//$countries = "'EN','WA','SC'";
	//FIXME either use 
        //   CONTAINS( GeomFromText($rectangle),	point_en) 
        //   AND reference_index = $reference_index
	//(some towns are missing, then) or
        //   CONTAINS( GeomFromText($rectanglexy),	point_xy) 
#CONTAINS( GeomFromText($rectangle),	point_en) 
#AND reference_index = $reference_index
$sql = <<<END
SELECT short_name as name,e,n,s,quad,reference_index
FROM loc_towns
WHERE 
 $crit
CONTAINS( GeomFromText($rectanglexy),	point_xy) 
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
				
				//trigger_error("---->city: " . ($recordSet->fields['name']) . " w/h: " . ($this->image_w) . "/" . ($this->image_h) . " x/y: " . $imgx1 . "/" . $imgy1, E_USER_NOTICE);
				if ($this->pixels_per_km<32) { //FIXME limit?
					imagefilledrectangle ($img, $imgx1-1, $imgy1-2, $imgx1+1, $imgy1+2, $black);
					imagefilledrectangle ($img, $imgx1-2, $imgy1-1, $imgx1+2, $imgy1+1, $black);
				}
				$font = ($recordSet->fields['s'] ==1)?$cityfont:2;
				$img1 = $this->_posText( $imgx1, $imgy1, $font, $recordSet->fields['name'],$recordSet->fields['quad']);
				if (count($img1))
					imageGlowString($img, $font, $img1[0], $img1[1], $recordSet->fields['name'], $gridcol);
				//else trigger_error("------>fail", E_USER_NOTICE);
			} //else trigger_error("---->skip: " . ($recordSet->fields['name']), E_USER_NOTICE);
			
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
	function _posText($x,$y,$font,$text,$quad = 0,$bdry=0) {
		$stren = imagefontwidth($font)*strlen($text);
		$strhr = imagefontheight($font);
		//trigger_error("--------> w/h: " . $stren . "/" . $strhr .  ", labels: " . count($this->labels), E_USER_NOTICE);
		//reset($this->labels);
		//foreach ($this->labels as $a1) {
		//	trigger_error("-----------> " . $a1[0] . "/" .  $a1[1] . "/" . $a1[2] . "/" . $a1[3], E_USER_NOTICE);
		//}
		$xy = array($x,$y);
		if ($quad == 0) {
			for ($run = 0; $run < 2; ++$run) {
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
					if ($x <= $bdry || $y <= $bdry || $x + $stren >= $this->image_w+$bdry || $y + $strhr >= $this->image_h+$bdry) { // "=" => one pixel more than neccessary
						$intersect = true;
					} else {
						$intersect = false;
						if ($run == 0) {
							reset($this->gridlabels);
							foreach ($this->gridlabels as $a1) {
								if (rectinterrect($a1,$thisrect)) {
									$intersect = true;
									break;
								}
							}
						}
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
				if (!$intersect) {
					break;
				}
			}
			if ($intersect) {
				#$quad=0;
				//trigger_error("------>intersect", E_USER_NOTICE);
				return array();
			}
		}
		list($x,$y) = $xy;
		if (
		($quad%2 == 1)
			||
		( $quad <= 0 && ($x-$bdry < ($this->image_w - $stren)) )
		) {
		} else {
			list($x,$d) = $xy;
			$x = $x - $stren;
		}
		if (
		($quad > 2)
			||
		( $quad <= 0 && ($y-$bdry < ($this->image_h - $strhr)) )
		) {
		} else {
			list($d,$y) = $xy;
			$y = $y - $strhr;
		}
		if ($x > $bdry && $y > $bdry && ($x < ($this->image_w - $stren + $bdry)) && ($y < ($this->image_h - $strhr + $bdry))) {
			$thisrect = array($x-3,$y-3,$x + $stren+3,$y + $strhr+3);

			array_push($this->labels,$thisrect);
			#trigger_error("label pushed: " . $text . ": " . $stren . "px: " . $x . "..." . ($x + $stren - 1) . " / " . $y . "..." . ($y + $strhr - 1), E_USER_NOTICE);

			return array($x,$y);
		} else {
			//trigger_error("------>bound", E_USER_NOTICE);
			return array();
		}
	}
	
	function _plotPlacenamesM(&$img,$left,$bottom,$right,$top)
	{
		global $CONF;
		$db=&$this->_getDB();

		$black=imagecolorallocate ($img, 0,64,0);

		require_once('geograph/conversionslatlong.class.php');
		$conv = new ConversionsLatLong;
		$gridcol=imagecolorallocate ($img, 109,186,178);
		if ($this->level < 7) { //FIXME limit?
			$div = 500000; //1 per 500k square
			$scrit = "s = '1' AND";
			$cityfont = 3;
		} elseif ($this->level < 9) { //FIXME limit?
			$div = 100000;
			$scrit = "(s = '1' OR s = '2') AND";
			$cityfont = 3;
		} elseif ($this->level < 10) { //FIXME limit?
			$div = 30000;
			$scrit = "(s IN ('1','2','3')) AND";
			$cityfont = 3;
		} else {
			$div = 10000;
			$scrit = "(s IN ('1','2','3','4')) AND";
			$cityfont = 3;
		}
		list($glatTL, $glonTL) = $conv->sm_to_wgs84($left, $top);
		list($glatTR, $glonTR) = $conv->sm_to_wgs84($right, $top);
		list($glatBL, $glonBL) = $conv->sm_to_wgs84($left, $bottom);
		list($glatBR, $glonBR) = $conv->sm_to_wgs84($right, $bottom);
		$bdry = $this->render_margin;
		$imgw = $this->image_w;
		$widthM=$this->map_wM;
		$leftM=$this->map_xM;
		$bottomM=$this->map_yM;
		#$rightM=$leftM+$widthM;
		#$topM=$bottomM+$widthM;

		foreach ($CONF['references'] as $ri => $rname) {
			$x0 = $CONF['origins'][$ri][0];
			$y0 = $CONF['origins'][$ri][1];
			$latmin = $CONF['latrange'][$ri][0];
			$latmax = $CONF['latrange'][$ri][1];
			$lonmin = $CONF['lonrange'][$ri][0];
			$lonmax = $CONF['lonrange'][$ri][1];
			list($eTL,$nTL,$riX1) = $conv->wgs84_to_national($glatTL, $glonTL, true, $ri);
			list($eTR,$nTR,$riX2) = $conv->wgs84_to_national($glatTR, $glonTR, true, $ri);
			list($eBL,$nBL,$riX3) = $conv->wgs84_to_national($glatBL, $glonBL, true, $ri);
			list($eBR,$nBR,$riX4) = $conv->wgs84_to_national($glatBR, $glonBR, true, $ri);
			$emin = min($eTL, $eTR, $eBL, $eBR);
			$emax = max($eTL, $eTR, $eBL, $eBR);
			$nmin = min($nTL, $nTR, $nBL, $nBR);
			$nmax = max($nTL, $nTR, $nBL, $nBR);
			$xmin = $emin + $x0*1000;
			$xmax = $emax + $x0*1000;
			$ymin = $nmin + $y0*1000;
			$ymax = $nmax + $y0*1000;
			$reference_index = $ri;
			$riwhere = "(reference_index = '$ri') and ";
			
			$crit = $riwhere.$scrit;

			$rectangle = "'POLYGON(($emin $nmin,$emax $nmin,$emax $nmax,$emin $nmax,$emin $nmin))'";
			$rectanglexy = "'POLYGON(($xmin $ymin,$xmax $ymin,$xmax $ymax,$xmin $ymax,$xmin $ymin))'";

if ($reference_index == 1 || ($reference_index == 2 && $this->pixels_per_km == 1 ) || $reference_index >= 3) {
	//$countries = "'EN','WA','SC'";
	//FIXME either use 
	//   CONTAINS( GeomFromText($rectangle),	point_en) 
	//   AND reference_index = $reference_index
	//(some towns are missing, then) or
	//   CONTAINS( GeomFromText($rectanglexy),	point_xy) 
#CONTAINS( GeomFromText($rectangle),	point_en) 
#AND reference_index = $reference_index
$sql = <<<END
SELECT short_name as name,e,n,s,quad
FROM loc_towns
WHERE 
 $crit
CONTAINS( GeomFromText($rectanglexy),	point_xy) 
ORDER BY s
END;
#GROUP BY FLOOR(e/$div),FLOOR(n/$div)
} else {
	$countries = "'NI','RI'";
	$div *= 1.5; //becuase the irish data is more dence

$sql = <<<END
SELECT e,n,full_name as name
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
			while (!$recordSet->EOF) {
				$e=$recordSet->fields['e'];
				$n=$recordSet->fields['n'];
				
				$str = floor($e/$div) .' '. floor($n/$div*1.4);
				if (!$squares[$str]) {// || $recordSet->fields['s'] ==1) {
					$squares[$str]++;
				
					$ll = $conv->national_to_wgs84($e, $n, $ri);
					list($xM, $yM) = $conv->wgs84_to_sm($ll[0], $ll[1]);
					$imgx1=$bdry + round(($xM - $leftM)   / $widthM * $imgw);
					$imgy1=$bdry + $imgw - 1 - round(($yM - $bottomM) / $widthM * $imgw);

					if ($imgx1 >= $bdry && $imgx1 < $imgw+$bdry && $imgy1 >=  $bdry && $imgy1 < $imgw+$bdry) {
					
						//trigger_error("---->city: " . ($recordSet->fields['name']) . " w/h: " . ($this->image_w) . "/" . ($this->image_h) . " x/y: " . $imgx1 . "/" . $imgy1, E_USER_NOTICE);
						if ($this->level<=11) { //FIXME limit?
							imagefilledrectangle ($img, $imgx1-1, $imgy1-2, $imgx1+1, $imgy1+2, $black);
							imagefilledrectangle ($img, $imgx1-2, $imgy1-1, $imgx1+2, $imgy1+1, $black);
						}
						$font = ($recordSet->fields['s'] ==1)?$cityfont:2;
						$img1 = $this->_posText( $imgx1, $imgy1, $font, $recordSet->fields['name'],$recordSet->fields['quad'], $bdry);
						if (count($img1))
							imageGlowString($img, $font, $img1[0], $img1[1], $recordSet->fields['name'], $gridcol);
						//else trigger_error("------>fail", E_USER_NOTICE);
					}
				} //else trigger_error("---->skip: " . ($recordSet->fields['name']), E_USER_NOTICE);
				$recordSet->MoveNext();
			}
			$recordSet->Close(); 
		}
	}

	/**
	* plot the gridlines
	* @access private
	*/	
	function _plotGridLinesM(&$img, $left, $bottom, $right, $top) {
		global $CONF;
		if ($this->overlay) {
			if ($this->level <= 7) {
				$gridcol=imagecolorallocate ($img, 109,186,178);
				$text1=$gridcol;
			} elseif ($this->level <= 8) {
				$gridcol=imagecolorallocate ($img, 120,205,196);
				$text1=$gridcol;
			} else {
				$gridcol=imagecolorallocate ($img, 89,126,118);
				$gridcol2=imagecolorallocate ($img, 60,205,252);
				$text1=imagecolorallocate ($img, 255,255,255);
			}
			$text2=imagecolorallocate ($img, 0,64,0);
		} else {
			if ($this->level <= 8) {
				/*if ($this->overlay) {
					//$gridcol=imagecolorallocate ($img, 60,224,255);
					//$gridcol=imagecolorallocate ($img, 60,205,252);
				} else {*/
				$gridcol=imagecolorallocate ($img, 109,186,178);
			} else {
				$gridcol=imagecolorallocate ($img, 89,126,118);
				$gridcol2=imagecolorallocate ($img, 60,205,252);
			}
			if ($this->level <= 8 && $this->level >= 6) {
				$text1=$gridcol;
			} else {
				$text1=imagecolorallocate ($img, 255,255,255);
				$text2=imagecolorallocate ($img, 0,64,0);
			}
		}
		require_once('geograph/conversionslatlong.class.php');
		$conv = new ConversionsLatLong;
		list($glatTL, $glonTL) = $conv->sm_to_wgs84($left, $top);
		list($glatTR, $glonTR) = $conv->sm_to_wgs84($right, $top);
		list($glatBL, $glonBL) = $conv->sm_to_wgs84($left, $bottom);
		list($glatBR, $glonBR) = $conv->sm_to_wgs84($right, $bottom);
		$bdry = $this->render_margin;
		$imgw = $this->image_w;
		$widthM=$this->map_wM;
		$leftM=$this->map_xM;
		$bottomM=$this->map_yM;
		$rightM=$leftM+$widthM;
		$topM=$bottomM+$widthM;
		#imageantialias ($img, true); does not work with alpha components. I like php so much...
		$db=&$this->_getDB();
		if ($this->level >= 9 && $this->level <= 11) { //FIXME
			// draw hectad boundaries
			foreach ($CONF['references'] as $ri => $rname) {
				$x0 = $CONF['origins'][$ri][0];
				$y0 = $CONF['origins'][$ri][1];
				$latmin = $CONF['latrange'][$ri][0];
				$latmax = $CONF['latrange'][$ri][1];
				$lonmin = $CONF['lonrange'][$ri][0];
				$lonmax = $CONF['lonrange'][$ri][1];
				$riwhere = "(reference_index = '{$ri}')";
				list($eTL,$nTL,$riX1) = $conv->wgs84_to_national($glatTL, $glonTL, true, $ri);
				list($eTR,$nTR,$riX2) = $conv->wgs84_to_national($glatTR, $glonTR, true, $ri);
				list($eBL,$nBL,$riX3) = $conv->wgs84_to_national($glatBL, $glonBL, true, $ri);
				list($eBR,$nBR,$riX4) = $conv->wgs84_to_national($glatBR, $glonBR, true, $ri);
				$xmin = min($eTL, $eTR, $eBL, $eBR)/1000. + $x0;
				$xmax = max($eTL, $eTR, $eBL, $eBR)/1000. + $x0;
				$ymin = min($nTL, $nTR, $nBL, $nBR)/1000. + $y0;
				$ymax = max($nTL, $nTR, $nBL, $nBR)/1000. + $y0;
				$xmin -= 100;
				$ymin -= 100;
				$rectangle = "'POLYGON(($xmin $ymin,$xmax $ymin,$xmax $ymax,$xmin $ymax,$xmin $ymin))'";#FIXME
				$sql="select * from gridprefix where ".
					"$riwhere and CONTAINS( GeomFromText($rectangle),	point_origin_xy) ".
					"and landcount>0";

				$recordSet = &$db->Execute($sql);
				while (!$recordSet->EOF) {
					$origin_x=$recordSet->fields['origin_x'];
					$origin_y=$recordSet->fields['origin_y'];
					$w=$recordSet->fields['width'];
					$h=$recordSet->fields['height'];

					//get polygon of boundary relative to corner of square
					if (strlen($recordSet->fields['boundary'])) {
						$polykm=explode(',', $recordSet->fields['boundary']);
					} else {
						$polykm=array(0,0, 0,100, 100,100, 100,0);
					}
					// convert to east/north
					$enpoly=array();
					$pts=count($polykm)/2;
					for ($i=0; $i<$pts; $i++) {
						$e = ($polykm[$i*2]+$origin_x-$x0)*1000;
						$n = ($polykm[$i*2+1]+$origin_y-$y0)*1000;
						$enpoly[] = array($e, $n);
					}
					for ($delta = 10; $delta <= 90; $delta += 10) {
						for ($dir = 0; $dir <= 1; ++$dir) {
							// intersect with horizontal/vertical lines
							if ($dir == 0) {
								$hectadpoly = $this->_hcut_convexpoly($enpoly, 1000 * ($delta + $origin_y-$y0));
							} else {
								$hectadpoly = $this->_vcut_convexpoly($enpoly, 1000 * ($delta + $origin_x-$x0));
							}
							//now convert km to pixels
							//if ($this->level >= 10)
								$this->_split_polygon($hectadpoly, 10, false);
							$llpoly=array();
							foreach ($hectadpoly as &$en) {
								$llpoly[] = $conv->national_to_wgs84($en[0], $en[1], $ri);
							}
							$this->_clip_polygon($llpoly, $latmin, $latmax, $lonmin, $lonmax, false);
							$poly=array();
							foreach ($llpoly as &$ll) {
								list($xM, $yM) = $conv->wgs84_to_sm($ll[0], $ll[1]);
								$x=$bdry + round(($xM - $leftM)   / $widthM * $imgw);
								$y=$bdry + $imgw - 1 - round(($yM - $bottomM) / $widthM * $imgw);
								$poly[] = array($x, $y);
							}
							if ($bdry) {
								$this->_clip_polygon($poly, $bdry/2, $imgw+$bdry+$bdry/2, $bdry/2, $imgw+$bdry+$bdry/2, false);
							}
							//draw
							if (count($poly)) {
								$curp = array_pop($poly);
								while(count($poly)) {
									$prevp = $curp;
									$curp = array_pop($poly);
									imageline($img, $prevp[0], $prevp[1], $curp[0], $curp[1], $gridcol2);
								}
							}
						}
					}
					$recordSet->MoveNext();
				}
				$recordSet->Close();
			}
		} elseif ($this->level >= 12) {
			$sql="select polycount,poly1gx,poly1gy,poly2gx,poly2gy,poly3gx,poly3gy,poly4gx,poly4gy,poly5gx,poly5gy,poly6gx,poly6gy,poly7gx,poly7gy,poly8gx,poly8gy from gridsquare_gmcache inner join gridsquare using(gridsquare_id) where gxlow <= $rightM and gxhigh >= $leftM and gylow <= $topM and gyhigh >= $bottomM";
			$recordSet = &$db->Execute($sql);
			while (!$recordSet->EOF) {
				$points = $recordSet->fields[0];
				$drawpoly = array();
				for ($i = 0; $i < $points; ++$i) {
					$xM = $recordSet->fields[1+$i*2];
					$yM = $recordSet->fields[2+$i*2];
					$drawpoly[] = $bdry + round(($xM - $leftM) / $widthM * $imgw);
					$drawpoly[] = $bdry + $imgw - 1 - round(($yM - $bottomM) / $widthM * $imgw);
				}
				imagepolygon($img, $drawpoly, $points, $gridcol2);
				$recordSet->MoveNext();
			}
			$recordSet->Close();
		}
		foreach ($CONF['references'] as $ri => $rname) {
			$x0 = $CONF['origins'][$ri][0];
			$y0 = $CONF['origins'][$ri][1];
			$latmin = $CONF['latrange'][$ri][0];
			$latmax = $CONF['latrange'][$ri][1];
			$lonmin = $CONF['lonrange'][$ri][0];
			$lonmax = $CONF['lonrange'][$ri][1];
			$riwhere = "(reference_index = '{$ri}')";
			list($eTL,$nTL,$riX1) = $conv->wgs84_to_national($glatTL, $glonTL, true, $ri);
			list($eTR,$nTR,$riX2) = $conv->wgs84_to_national($glatTR, $glonTR, true, $ri);
			list($eBL,$nBL,$riX3) = $conv->wgs84_to_national($glatBL, $glonBL, true, $ri);
			list($eBR,$nBR,$riX4) = $conv->wgs84_to_national($glatBR, $glonBR, true, $ri);
			$xmin = min($eTL, $eTR, $eBL, $eBR)/1000. + $x0;
			$xmax = max($eTL, $eTR, $eBL, $eBR)/1000. + $x0;
			$ymin = min($nTL, $nTR, $nBL, $nBR)/1000. + $y0;
			$ymax = max($nTL, $nTR, $nBL, $nBR)/1000. + $y0;
			$xmin -= 100;
			$ymin -= 100;
			$rectangle = "'POLYGON(($xmin $ymin,$xmax $ymin,$xmax $ymax,$xmin $ymax,$xmin $ymin))'";#FIXME
			$sql="select * from gridprefix where ".
				"$riwhere and CONTAINS( GeomFromText($rectangle),	point_origin_xy) ".
				"and landcount>0";

			$recordSet = &$db->Execute($sql);
			while (!$recordSet->EOF) {
				$origin_x=$recordSet->fields['origin_x'];
				$origin_y=$recordSet->fields['origin_y'];
				$w=$recordSet->fields['width'];
				$h=$recordSet->fields['height'];
				$labelminwidth=$recordSet->fields['labelminwidth'];

				//get polygon of boundary relative to corner of square
				if (strlen($recordSet->fields['boundary'])) {
					$polykm=explode(',', $recordSet->fields['boundary']);
					$labelkm=explode(',', $recordSet->fields['labelcentre']);
					$issquare = false;
				} else {
					$polykm=array(0,0, 0,100, 100,100, 100,0);
					$labelkm=array(50,50);
					$issquare = true;
				}

				//now convert km to pixels
				$enpoly=array();
				$pts=count($polykm)/2;
				for ($i=0; $i<$pts; $i++) {
					$e = ($polykm[$i*2]+$origin_x-$x0)*1000;
					$n = ($polykm[$i*2+1]+$origin_y-$y0)*1000;
					$enpoly[] = array($e, $n);
				}
				if ($this->level >= 9)
					$this->_split_polygon($enpoly, 10);
				elseif ($this->level >= 7)
					$this->_split_polygon($enpoly, 5);
				elseif ($this->level >= 6)
					$this->_split_polygon($enpoly, 2);
				$llpoly=array();
				foreach ($enpoly as &$en) {
					$llpoly[] = $conv->national_to_wgs84($en[0], $en[1], $ri);
				}
				$this->_clip_polygon($llpoly, $latmin, $latmax, $lonmin, $lonmax);
				$poly=array();
				foreach ($llpoly as &$ll) {
					list($xM, $yM) = $conv->wgs84_to_sm($ll[0], $ll[1]);
					$x=$bdry + round(($xM - $leftM)   / $widthM * $imgw);
					$y=$bdry + $imgw - 1 - round(($yM - $bottomM) / $widthM * $imgw);
					$poly[] = array($x, $y);
				}
				if ($bdry) {
					$this->_clip_polygon($poly, $bdry/2, $imgw+$bdry+$bdry/2, $bdry/2, $imgw+$bdry+$bdry/2);
				}
				$numpoints=count($poly);
				if ($numpoints) {
					$drawpoly=array();
					foreach ($poly as &$p) {
						$drawpoly[] = $p[0];
						$drawpoly[] = $p[1];
					}
					imagepolygon($img, $drawpoly, $numpoints, $gridcol);
				}

				if($issquare && $this->level >= 5 && 100*$this->pixels_per_km>=$labelminwidth) {
					$e = ($labelkm[0]+$origin_x-$x0)*1000;
					$n = ($labelkm[1]+$origin_y-$y0)*1000;
					$ll = $conv->national_to_wgs84($e, $n, $ri);
					list($xM, $yM) = $conv->wgs84_to_sm($ll[0], $ll[1]);
					$labelx =  $bdry + round(($xM - $leftM)   / $widthM * $imgw);
					$labely =  $bdry + $imgw - 1 - round(($yM - $bottomM) / $widthM * $imgw);
					//font size 1= 4x6
					//font size 2= 6x8 normal
					//font size 3= 6x8 bold
					//font size 4= 7x10 normal
					//font size 5= 8x10 bold

					if($this->level >=6)
						$font=5;
					else
						$font=3;


					$text=$recordSet->fields['prefix'];

					$txtw = imagefontwidth($font)*strlen($text);
					$txth = imagefontheight($font);
					

					$txtx=round($labelx - $txtw/2);
					$txty=round($labely - $txth/2);
					if ($this->level <= 5 || $this->overlay) {
						imagestring ($img, $font, $txtx+1,$txty+1, $text, $text2);
						imagestring ($img, $font, $txtx,$txty, $text, $text1);
					} else {
						imagestring ($img, $font, $txtx,$txty, $text, $text1);
					}
					$thisrect = array($txtx,$txty,$txtx + $txtw,$txty + $txth);
					array_push($this->gridlabels,$thisrect);
				}
				$recordSet->MoveNext();
			}
			$recordSet->Close();
		}
		//imageantialias ($img, false);
		if ($this->level >= 9 && $this->level <= 11) { //FIXME
			$font = 3;
			$dx = floor(imagefontwidth($font)/2);
			$dy = floor(imagefontheight($font)/2);
			foreach ($CONF['references'] as $ri => $rname) {
				$x0 = $CONF['origins'][$ri][0];
				$y0 = $CONF['origins'][$ri][1];
				$sql="select x,y,scale,rotangle,cgx,cgy from gridsquare_gmcache inner join gridsquare using(gridsquare_id) where gxlow <= $rightM and gxhigh >= $leftM and gylow <= $topM and reference_index=$ri and (((x - $x0)%100 = 5 and (y - $y0)%10 = 0) or ((y - $y0)%100 = 5 and (x - $x0)%10 = 0)) and abs(cliparea/area-1) < 0.01";
				$recordSet = &$db->Execute($sql);
				while (!$recordSet->EOF) {
					$x = $recordSet->fields[0];
					$y = $recordSet->fields[1];
					$e = floor((($x-$x0) % 100) / 10);
					$n = floor((($y-$y0) % 100) / 10);
					$scale = $recordSet->fields[2];
					$angle = $recordSet->fields[3];
					$cosangle = cos($angle);
					$sinangle = sin($angle);
					$xMC = $recordSet->fields[4];
					$yMC = $recordSet->fields[5];
					if (($y-$y0) % 100 == 5 /*$n == 0*/) { # label on left side
						$xM = $xMC - 500*$scale*$cosangle;
						$yM = $yMC - 500*$scale*$sinangle;
						$xl = $bdry + round(($xM - $leftM) / $widthM * $imgw) - $dx;
						$yl = $bdry + $imgw - 1 - round(($yM - $bottomM) / $widthM * $imgw) - $dy;
						$text = sprintf("%01d", $e);
						imagestring($img, $font, $xl+1, $yl+1, $text, $text2);
						imagestring($img, $font, $xl,   $yl,   $text, $text1);
					}
					if (($x-$x0) % 100 == 5 /*$e == 0*/) { # label on bottom
						$xM = $xMC + 500*$scale*$sinangle;
						$yM = $yMC - 500*$scale*$cosangle;
						$xl = $bdry + round(($xM - $leftM) / $widthM * $imgw) - $dx;
						$yl = $bdry + $imgw - 1 - round(($yM - $bottomM) / $widthM * $imgw) - $dy;
						$text = sprintf("%01d", $n);
						imagestring($img, $font, $xl+1, $yl+1, $text, $text2);
						imagestring($img, $font, $xl,   $yl,   $text, $text1);
					}
					$recordSet->MoveNext();
				}
				$recordSet->Close();
			}
		} elseif ($this->level >= 12) {
			$font = 3;
			$dx = imagefontwidth($font);
			$dy = floor(imagefontheight($font)/2);
			foreach ($CONF['references'] as $ri => $rname) {
				$x0 = $CONF['origins'][$ri][0];
				$y0 = $CONF['origins'][$ri][1];
				$sql="select x,y,scale,rotangle,cgx,cgy from gridsquare_gmcache inner join gridsquare using(gridsquare_id) where gxlow <= $rightM and gxhigh >= $leftM and gylow <= $topM and reference_index=$ri and ((x - $x0)%10 = 0 or (y - $y0)%10 = 0) and abs(cliparea/area-1) < 0.01";
				$recordSet = &$db->Execute($sql);
				while (!$recordSet->EOF) {
					$x = $recordSet->fields[0];
					$y = $recordSet->fields[1];
					$e = ($x-$x0) % 100;
					$n = ($y-$y0) % 100;
					$scale = $recordSet->fields[2];
					$angle = $recordSet->fields[3];
					$cosangle = cos($angle);
					$sinangle = sin($angle);
					$xMC = $recordSet->fields[4];
					$yMC = $recordSet->fields[5];
					if ($n%10 == 0) { # label on left side
						$xM = $xMC - 500*$scale*$cosangle;
						$yM = $yMC - 500*$scale*$sinangle;
						$xl = $bdry + round(($xM - $leftM) / $widthM * $imgw) - $dx;
						$yl = $bdry + $imgw - 1 - round(($yM - $bottomM) / $widthM * $imgw) - $dy;
						$text = sprintf("%02d", $e);
						imagestring($img, $font, $xl+1, $yl+1, $text, $text2);
						imagestring($img, $font, $xl,   $yl,   $text, $text1);
					}
					if ($e%10 == 0) { # label on bottom
						$xM = $xMC + 500*$scale*$sinangle;
						$yM = $yMC - 500*$scale*$cosangle;
						$xl = $bdry + round(($xM - $leftM) / $widthM * $imgw) - $dx;
						$yl = $bdry + $imgw - 1 - round(($yM - $bottomM) / $widthM * $imgw) - $dy;
						$text = sprintf("%02d", $n);
						imagestring($img, $font, $xl+1, $yl+1, $text, $text2);
						imagestring($img, $font, $xl,   $yl,   $text, $text1);
					}
					$recordSet->MoveNext();
				}
				$recordSet->Close();
			}
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
			if ($this->pixels_per_km >= 32) {
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
			if ($this->pixels_per_km < 1 || $this->pixels_per_km >= 32) {
				$text1=imagecolorallocate ($img, 255,255,255);
				$text2=imagecolorallocate ($img, 0,64,0);
			} else {
				$text1=$gridcol;
			}
			return;
		}
		
		$db=&$this->_getDB();

		if (empty($this->force_ri)) {
			$riwhere = '';
		} else {
			$riwhere = "(reference_index = '{$this->force_ri}') and ";
		}

		
		//TODO  - HARD CODED VALUES!!
		$width = 100;
		$scanleft -= $width;
		$scanbottom -= $width;
		$rectangle = "'POLYGON(($scanleft $scanbottom,$scanright $scanbottom,$scanright $scantop,$scanleft $scantop,$scanleft $scanbottom))'";
		
		$sql="select * from gridprefix where ".
			"$riwhere CONTAINS( GeomFromText($rectangle),	point_origin_xy) ".
			"and landcount>0";

		$recordSet = &$db->Execute($sql);
		while (!$recordSet->EOF) 
		{
			$origin_x=$recordSet->fields['origin_x'];
			$origin_y=$recordSet->fields['origin_y'];
			$w=$recordSet->fields['width'];
			$h=$recordSet->fields['height'];
			$labelminwidth=$recordSet->fields['labelminwidth'];

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



			if($this->pixels_per_km>=0.3 && 100*$this->pixels_per_km>=$labelminwidth)
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
				array_push($this->gridlabels,$thisrect);
				if ($_GET['d']) {
					print "$text";var_dump($thisrect); print "<BR>";
				}
			}
			$recordSet->MoveNext();
		}

		$recordSet->Close(); 		
		
		//plot the number labels
		if ($this->pixels_per_km >= 32) {
			$gridref = $this->getGridRef(0, $this->image_h); //origin of image is tl, map is bl
			if (preg_match('/^([!A-Z]{1,3})(\d\d)(\d\d)$/',$gridref, $matches))
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
	* return a sparse array for every grid on the map
	* @access private
	*/
	function& getGridInfo()
	{
		global $memcache;
		global $CONF;

		if ($this->type_or_user == -10) {
			//we want a blank map!
			return array();
		} 

		if ($memcache->valid) {
			//we only use cache imagemap as they invalidate correctly - and checksheets get smarty cached anyways

			$mkey = $this->getImageFilename()."..{$this->cliptop}.{$this->clipbottom}.{$this->clipright}.{$this->clipleft}";
			$mnamespace = 'mI';
			$grid =& $memcache->name_get($mnamespace,$mkey);
			if ($grid) {
				return $grid;
			}
			$mperiod = $memcache->period_long*4;
		}

		//figure out what we're mapping in internal coords
		$db=&$this->_getDB();
		
		$grid=array();

		$tstart = microtime(true);
		$tcalc = 0;
		$imgw = $this->image_w;
		$imgh = $this->image_h;
		if ($this->mercator) {
			$widthM=$this->map_wM;
			$leftM=$this->map_xM;
			$bottomM=$this->map_yM;
			$rightM=$leftM+$widthM;
			$topM=$bottomM+$widthM;
			$clipleftM =   floor($leftM   + $this->clipleft   * $widthM / $imgw);
			$cliprightM =  ceil ($rightM  - $this->clipright  * $widthM / $imgw);
			$cliptopM =    ceil ($topM    - $this->cliptop    * $widthM / $imgw);
			$clipbottomM = floor($bottomM + $this->clipbottom * $widthM / $imgw);
			trigger_error("->CP $cliprightM $clipleftM $cliptopM  $clipbottomM", E_USER_NOTICE);
			# Trying to solve our mysql performance issue:
			#   select * from
			#       gridsquare_gmcache gmc inner join gridsquare gs using (gridsquare_id)
			#   where gmc.gxlow <= $cliprightM and gmc.gxhigh >= $clipleftM
			#     and gmc.gylow <= $cliptopM and gmc.gyhigh >= $clipbottomM
			# should use _all_ conditions on gmc, reducing the number of squares a lot _before_ joining.
			# Moving the conditions to "on (...)" or a combined index makes no difference.
			#
			# Current workaround: We know that gxright - gxleft is limited (size of a square), i.e.
			#    gxright - gxleft < D
			# so we can add _more_ conditions to improve performance by using
			#    gxlow between ($clipleftM-$D) and $cliprightM
			# instead of
			#    gxlow <= $cliprightM
			#
			# This reduces the number of rows to join quite a lot; but still way too much rows...
			# Next test: (select * from gridsquare_gmcache ... where ...) inner join gridsquare
			# [Probably a bad idea because join cant't use index now?]
			#
			# How to estimate D?
			#  use $CONF['gmthumbsize12'] = maximal (gxhigh-gxlow)*256/4.0e7*POW(2,12) + some pixels for cropping
			#  =>  $CONF['gmthumbsize12'] > maximal (gxhigh-gxlow)*256/4.0e7*POW(2,12)
			#  => maximal (gxhigh-gxlow)  < $CONF['gmthumbsize12']*4.0e7/256/POW(2,12)
			#                             = $CONF['gmthumbsize12']*4.0e7/POW(2,20)
			#                             = $CONF['gmthumbsize12']*4.0e7/1048576
			#
			# What else to test?
			# * Get rid of gridsquare_gmcache and add its columns to gridsquare?
			# * Create a temporary table from gridsquare_gmcache inner join gridsquare every night?

			$squaresize = $CONF['gmthumbsize12'] * 4.0e7 / 1048576;
			$clipleftMD   = $clipleftM  -$squaresize;
			$clipbottomMD = $clipbottomM-$squaresize;
			$cliprightMD  = $cliprightM +$squaresize;
			$cliptopMD    = $cliptopM   +$squaresize;

			if (!empty($this->type_or_user) && $this->type_or_user > 0) {
				$where_crit = " and gi2.user_id = {$this->type_or_user}";
				$where_crit2 = " and gi.user_id = {$this->type_or_user}";
			} else {
				$where_crit = '';
				$where_crit2 = '';
			}
			$sql="select polycount,poly1gx,poly1gy,poly2gx,poly2gy,poly3gx,poly3gy,poly4gx,poly4gy,poly5gx,poly5gy,poly6gx,poly6gy,poly7gx,poly7gy,poly8gx,poly8gy, "
				."gs.*,gridimage_id,gi.realname as credit_realname,if(gi.realname!='',gi.realname,user.realname) as realname,title,title2 "
				#."from gridsquare_gmcache gmc "
				."from (select * from gridsquare_gmcache where "
				."gxlow between $clipleftMD and $cliprightM and gxhigh between $clipleftM and $cliprightMD and "
				."gylow between $clipbottomMD and $cliptopM and gyhigh between $clipbottomM and $cliptopMD "
				.") gmc "
				."inner join gridsquare gs on(gs.gridsquare_id=gmc.gridsquare_id) left join gridimage gi ON "
					."(imagecount > 0 AND gi.gridsquare_id = gs.gridsquare_id $where_crit2 AND imagecount > 0 AND gridimage_id = 
						(select gridimage_id from gridimage_search gi2 where gi2.gridsquare_id=gs.gridsquare_id 
						 $where_crit order by moderation_status+0 desc,seq_no limit 1)
					) 
					left join user using(user_id) "
				."where "
				##."gxlow <= $cliprightM and gxhigh >= $clipleftM and gylow <= $cliptopM and gyhigh >= $clipbottomM and "
				#."gxlow between $clipleftMD and $cliprightM and gxhigh between $clipleftM and $cliprightMD and "
				#."gylow between $clipbottomMD and $cliptopM and gyhigh between $clipbottomM and $cliptopMD and "
				."percent_land<>0 group by gs.grid_reference order by y,x";
		} else {
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
			//yes I know the imagecount is possibly strange in the join, but does speeds it up, having it twice speeds it up even more! (by preference have the second one, speed wise!), also keeping the join on gridsquare_id really does help too for some reason! 
			$sql="select gs.*,gridimage_id,gi.realname as credit_realname,if(gi.realname!='',gi.realname,user.realname) as realname,title,title2 
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
		}
		$recordSet = &$db->Execute($sql);
		while (!$recordSet->EOF) 
		{
			$recordSet->fields['geographs'] = $recordSet->fields['imagecount'] - $recordSet->fields['accepted'];
			$recordSet->fields['title1'] = $recordSet->fields['title'];
			$recordSet->fields['title'] = combineTexts($recordSet->fields['title1'], $recordSet->fields['title2']);
			$tcalc -= microtime(true);
			$poly = array();
			if ($this->mercator) {
				$points = $recordSet->fields[0];
				$factor = $imgw / $widthM;
				$dx = $leftM;
				#$dy = $bottomM + ($imgw - 1) * $widthM / $imgw;
				$dy = $bottomM + $widthM - 1./$factor;
				for ($i = 0; $i < $points; ++$i) {
					$poly[] = round(($recordSet->fields[1+$i*2] - $dx)*$factor);
					$poly[] = round(($dy - $recordSet->fields[2+$i*2])*$factor);
				}
			} else {
				#FIXME clipping?
				$gridx=$recordSet->fields['x'];
				$gridy=$recordSet->fields['y'];

				$posx=$gridx-$left;
				$posy=$top-$gridy;

				$x1 = $posx * $this->pixels_per_km;
				$x2 = $x1   + $this->pixels_per_km;
				$y1 = $posy * $this->pixels_per_km;
				$y2 = $y1   + $this->pixels_per_km;

				$poly[] = $x1;
				$poly[] = $y1;
				$poly[] = $x2;
				$poly[] = $y1;
				$poly[] = $x2;
				$poly[] = $y2;
				$poly[] = $x1;
				$poly[] = $y2;
			}
			#FIXME intersection w. rectangle 0,0...$imgw-1,$imgh-1? seems to work without that...
			#if (count($poly)) {
				$recordSet->fields['poly'] = $poly;
				$grid[]=$recordSet->fields;
			#}
			$tcalc += microtime(true);
			
			$recordSet->MoveNext();
		}
		$recordSet->Close();
		$ttotal = microtime(true) - $tstart;
		trigger_error("->t $ttotal, $tcalc", E_USER_NOTICE);

		if ($memcache->valid)
			$memcache->name_set($mnamespace,$mkey,$grid,$memcache->compress,$mperiod);
		
		return $grid;
	}

	/**
	* return a sparse 2d array for every grid on the map
	* @access private
	*/
	function& getGridArray($isimgmap = false)
	{
		global $memcache;

		if ($this->mercator) # use getGridInfo() instead of getGridArray(true), e.g. in mapbrowse2.tpl!
			return array();

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
			$sql="select gs.*,gridimage_id,gi.realname as credit_realname,if(gi.realname!='',gi.realname,user.realname) as realname,title,title2 
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
			$recordSet->fields['title1'] = $recordSet->fields['title'];
			$recordSet->fields['title'] = combineTexts($recordSet->fields['title1'], $recordSet->fields['title2']);
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
	#imagestring($out_image, $font, 3, 3, utf8_encode($text), $black);

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
