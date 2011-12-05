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
* Provides the GeographMapMosaic class
*
* @package Geograph
* @author Paul Dixon <paul@elphin.com>
* @version $Revision$
*/


/**
* Needs the GeographMap class, so we pull that in here
*/
require_once('geograph/map.class.php');

/**
* Geograph Bounding Box class
*
* method to pass a rectangle around
*
* @package Geograph
*/
class BoundingBox {
	var $top;
	var $left;
	var $height;
	var $width;
}

/**
* Geograph Map Mosaic class
*
* Provides an abstraction of a group of GeographMap objects which can
* be combined  together into one map
*
* @package Geograph
*/
class GeographMapMosaic
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
	* x origin of map tile in internal coordinates
	*/
	var $tile_x=0;
	
	/**
	* y origin of map tiles in internal coordinates
	*/
	var $tile_y=0;
	
	/**
	* height of map in pixels
	*/
	var $image_w=0;
	
	/**
	* width of map in pixels
	*/
	var $image_h=0;
	
	/**
	* scale in pixels per kilometre or mercator zoom level
	*/
	# FIXME get rid of that or rename to $scale
	var $pixels_per_km=0;

	/** 
	* list of valid scales for this mosaic	
	*/
	var $scales;
	
	/**
	* width/height of mosaic
	*/
	var $mosaic_factor_x=0;
	var $mosaic_factor_y=0;
	//FIXME token,...
	var $dx=0;
	var $dy=0;
	var $dx2=0;
	var $dy2=0;
	var $tilesize=0;
	var $mercator=false;
	var $level=0;
	var $mosaictype=0; // 1: overview 2: full 0: one custom level
	var $pixels_per_unit=0; // pixels per kilometre or pixels per level 19 tile
	var $units_per_tile=0; // km or l19 tiles per tile
	var $shift_x=0;
	var $shift_y=0;
	
	/**
	* enable caching?
	*/
	var $caching=true;
	
	/**
	* debug text contains debugging traces suitable for html output
	*/
	var $debugtrace="";
	
	/**
	* enable debug tracing?
	*/
	var $debug=true;
	
	/**
	 * palette index, see GeographMap::setPalette for documentation
	 */
	var $palette=0; 
	 
	/**
	* Constructor - pass true to get a small overview map, otherwise you get a full map
	* @access public
	*/
	# presets:
	#  ./public_html/index.php:  overview_large/overview_ireland/overview_charcoal depending on template. should be replaced with conf variable
	#  ./public_html/juppy.php:  overview_charcoal/overview depending on template. should be replaced with conf variable
	#  different places: overview, largeoverview
	#setPreset():
	# ./public_html/mapbrowse.php:    $overview->setPreset('overview');
	#setScale():
	# ./public_html/maplarge.php
	# ./public_html/mapprint.php
	function GeographMapMosaic($preset='full', $xcenter=null, $ycenter=null)
	{
		global $CONF;
		$this->enableCaching($CONF['smarty_caching']);
		$this->setPreset($preset, $xcenter, $ycenter);
	}

	/**
	* configure map to use a hard coded configuration accessed by name
	* @access public
	*/
	function setPreset($name, $xcenter=null, $ycenter=null) # FIXME better solution needed (configurable?)
	{
		global $CONF;
		$this->tilesize=0;
		$this->mercator=false;
		if (is_null($ycenter)) {
			$xm = $CONF['xmrange'][0]-262144;
			$ym = 262143-$CONF['ymrange'][1];
			$xo = 0;
			$yo = -10;
			$xl = -210;
			$yl = -15;
		} else {
			$xm = $xcenter;
			$ym = $ycenter;
			$xo = $xcenter;
			$yo = $ycenter;
			$xl = $xcenter;
			$yl = $ycenter;
		}
		#FIXME configurable non mercator origin
		switch ($name)
		{
			case 'full_t':
				$this->mosaictype = 2;
				$this->scales = array(0 => 0.3, 1 => 1, 2 => 4, 3 => 40);
				$this->initTiles(200,$xl,$yl,400,400,0.3,false,false,false,!is_null($ycenter));
				break;
			case 'full_tm':
				$this->mosaictype = 2;
				$this->scales = array(0 => 5, 1 => 7, 2 => 9, 3 => 12);
				$this->initTiles(256,$xm,$ym,400,400,5,true,false,false,!is_null($ycenter));#FIXME
				break;
			case 'full':
				$this->mosaictype = 2;
				$this->scales = array(0 => 0.3, 1 => 1, 2 => 4, 3 => 40);
				$this->setMosaicSize(400,400);
				$this->setScale(0.3);
				$this->setMosaicFactor(3);
				if (is_null($ycenter))
					$this->setOrigin($xl,$yl);
				else
					$this->setCentre($xcenter, $ycenter);
				break;
			case 'overview_t':
				$this->mosaictype = 1;
				$this->scales = array(0 => 0.13, 1 => 0.13, 2 => 0.13, 3 => 1);
				$this->initTiles(200,$xo,$yo,120,170,0.13,false,false,false,!is_null($ycenter));
				break;
			case 'overview_tm':
				$this->mosaictype = 1;
				$this->scales = array(0 => 4, 1 => 4, 2 => 4, 3 => 7);
				$this->initTiles(256,$xm,$ym,120,170,4,true,false,false,!is_null($ycenter));#FIXME
				break;
			case 'overview':
				$this->mosaictype = 1;
				$this->scales = array(0 => 0.13, 1 => 0.13, 2 => 0.13, 3 => 1);
				$this->setMosaicSize(120,170);
				$this->setScale(0.13);
				$this->setMosaicFactor(1);
				if (is_null($ycenter))
					$this->setOrigin($xo,$yo);
				else
					$this->setCentre($xcenter, $ycenter);
				break;
			case 'zoomedin_t':
				$this->mosaictype = 2;
				$this->scales = array(0 => 0.3, 1 => 1, 2 => 4, 3 => 40);
				$this->initTiles(200,$xl,$yl,400,400,40,false,false,!is_null($ycenter),!is_null($ycenter));
				break;
			case 'zoomedin_tm':
				$this->mosaictype = 2;
				$this->scales = array(0 => 5, 1 => 7, 2 => 9, 3 => 12);
				$this->initTiles(256,$xm,$ym,400,400,12,true,false,!is_null($ycenter),!is_null($ycenter));#FIXME
				break;
			case 'zoomedin':
				$this->mosaictype = 2;
				$this->scales = array(0 => 0.3, 1 => 1, 2 => 4, 3 => 40);
				$this->setMosaicSize(400,400);
				$this->setScale(40);
				$this->setMosaicFactor(2);
				if (is_null($ycenter))
					$this->setOrigin($xl,$yl);
				else
					$this->setCentre($xcenter, $ycenter, true); //true to align to 10x10 map
				break;
			case 'geograph':
				$this->mosaictype = 0;
				$this->scales = array(0 => 5); #FIXME?
				$this->setOrigin(-10,-30);
				$this->setMosaicSize(4615,6538);
				$this->setScale(5);
				$this->setMosaicFactor(3);
				break;
			case 'overview_large':
				$this->mosaictype = 0;
				$this->scales = array(0 => 0.2); #FIXME?
				$this->setOrigin(0,-10);
				$this->setMosaicSize(183,263);
				$this->setScale(0.20);
				$this->setMosaicFactor(1);
				break;
			case 'homepage':
				$this->mosaictype = 0;
				$this->scales = array(0 => 0.2); #FIXME?
				$this->setMosaicSize($CONF['home_map_width'],$CONF['home_map_height']);
				$this->setScale(0.2);
				$this->setMosaicFactor(1);
				if (is_null($ycenter))
					$this->setOrigin($xo,$yo);
				else
					$this->setCentre($xcenter, $ycenter);
				break;
			case 'homepage_t':
				$this->mosaictype = 0;
				$this->scales = array(0 => 0.2);
				$this->initTiles(200,$xo,$yo,$CONF['home_map_width'],$CONF['home_map_height'],0.2,false,false,false,!is_null($ycenter)); # we assume '_t' and '' to have the same layout
				break;
			case 'homepage_tm':
				$this->mosaictype = 0;
				$this->scales = array(0 => 5);
				$this->initTiles(256,$xm,$ym,$CONF['home_map_width_tm'],$CONF['home_map_height_tm'],5,true,false,false,!is_null($ycenter));
				# FIXME overlay -> overlay_type
				#       false   -> 0             == normal map
				#       true    -> 1             == as used as googlemaps tile overlays
				#                  2             == as used on homepage? [ no myriad names on map? ]
				break;
			case 'overview_ireland':
				$this->mosaictype = 0;
				$this->scales = array(0 => 0.3); #FIXME?
				$this->setOrigin(-5,110);
				$this->setMosaicSize(120,170);
				$this->setScale(0.3);
				$this->setMosaicFactor(1);
				break;
			case 'overview_charcoal':
				$this->mosaictype = 0;
				$this->scales = array(0 => 0.16);
				$this->setOrigin(0,-10);
				$this->setMosaicSize(144,210);
				$this->setScale(0.16);
				$this->setMosaicFactor(1);
				$this->setPalette(1);
				break;
			case 'largeoverview':
				$this->mosaictype = 0;
				#$this->scales = array(1 => 1); #FIXME?
				$this->scales = array(0 => 1); #FIXME?
				$this->setOrigin(0,-10);//will get recented
				$this->setMosaicSize(120,170);
				$this->setScale(1);
				$this->setMosaicFactor(1);
				if (is_null($ycenter))
					$this->setOrigin(0,-10);
				else
					$this->setCentre($xcenter, $ycenter);
				break;
			case 'largeoverview_t':
				$this->mosaictype = 0;
				$this->scales = array(0 => 1);
				$this->initTiles(200,$xo,$yo,120,170,1,false,false,false,!is_null($ycenter));
				break;
			case 'largeoverview_tm':
				$this->mosaictype = 0;
				$this->scales = array(0 => 7);
				$this->initTiles(256,$xm,$ym,120,170,7,true,false,false,!is_null($ycenter));#FIXME
				break;
			case 'largemap':
				$this->mosaictype = 0;
				$this->scales = array(0 => 80);
				$this->setScale(80);
				$this->setMosaicFactor(2);
				$this->setMosaicSize(800,800);
				break;
			default:
				trigger_error("GeographMapMosaic::setPreset unknown preset $name", E_USER_ERROR);
				break;
			
		}
		
	}
	
	function setPalette($idx)
	{
		$this->palette=$idx;
	}
	
	function _trace($msg)
	{
		if ($this->debug)
		{
			$this->debugtrace.="<li>$msg</li>";
		}
	}
	
	function _err($msg)
	{
		if ($this->debug)
		{
			$this->debugtrace.="<li style=\"color:#880000;\">$msg</li>";
		}
	}
	
	function enableCaching($enable)
	{
		$this->caching=$enable;
	}
	
	/**
	* Assigns useful stuff to the given smarty object
	* basename = 2d array of image tiles (see getImageArray)
	* basename_width and basename_height contain dimensions of mosaic
	* basename_token contains mosaic token
	* @access public
	*/
	function assignToSmarty(&$smarty, $basename)
	{
		//setup the overview variables
		$overviewimages =& $this->getImageArray();
		$smarty->assign_by_ref($basename, $overviewimages);
		$smarty->assign($basename.'_width', $this->image_w);
		$smarty->assign($basename.'_height', $this->image_h);
		$smarty->assign($basename.'_ri', $this->reference_index);
		$smarty->assign($basename.'_token', $this->getToken());
		$smarty->assign($basename.'_updated', $this->getUpdateDateString());
		$smarty->assign($basename.'_clip', $this->tilesize?1:0);
	}




	/**
	* Set origin of map in internal coordinates, returns true if valid
	* @access public
	*/
	function setOrigin($x,$y)
	{
		$this->map_x=intval($x);
		$this->map_y=intval($y);
		$this->tile_x=$this->map_x;
		$this->tile_y=$this->map_y;
		return true;
	}
	
	/**
	* Set size of mosaic image
	* @access public
	*/
	function setMosaicSize($w,$h)
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
		$pixels_per_km = floatval($pixels_per_km); # FIXME rename to $scale
		#$level = array_search($pixels_per_km,$this->scales);
		#for ($level = 0; $level < count($this->scales); ++$level) {
		foreach ($this->scales as $level=>$pixperkm) {
			if (abs($pixperkm-$pixels_per_km) < .0001) {
				$this->level = $level;
				$this->pixels_per_km = $pixels_per_km; # FIXME get rid of that or rename to $scale
				if ($this->mercator) { # unit: level 19 tiles (origin: lat,lon = 0), we save the actual zoom level in $pixels_per_km
					$this->pixels_per_unit = $this->tilesize / pow(2, 19-$pixels_per_km);
					$this->units_per_tile = pow(2, 19-$pixels_per_km); # level 19 tiles per tile
					$this->shift_x = pow(2, $pixels_per_km-1);
					$this->shift_y = pow(2, $pixels_per_km-1)-1;
				} else { #unit: 1km
					$this->pixels_per_unit = $pixels_per_km;
					$this->units_per_tile = $this->tilesize/$pixels_per_km; # km per tile
					$this->shift_x = 0;
					$this->shift_y = 0;
				}
				return true;
			}
		}
		trigger_error("GeographMapMosaic::setScale invalid scale $pixels_per_km", E_USER_WARNING);
		$level = 0;
		$this->scales[$level] = $pixels_per_km; #FIXME
		#FIXME $this->pixels_per_km,$this->pixels_per_unit,$this->level,$this->shift_x,$this->shift_y
		return false;
		#$this->pixels_per_km = $pixels_per_km;
		#$this->level = -1;# $level;
	}

	/**
	* How many images across/down will the mosaic be?
	* @access public
	*/
	function setMosaicFactor($factor_x, $factor_y=0)
	{
		if ($this->tilesize)
			return false;
		if (!$factor_y)
			$factor_y = $factor_x;
		$this->mosaic_factor_x=intval($factor_x);
		$this->mosaic_factor_y=intval($factor_y);
		$this->dx=0;
		$this->dy=0;
		$this->dx2=0;
		$this->dy2=0;
		#$this->tilesize=0;
		return true;
	}

	/**
	 * Use square tiles.
	 * @access public
	 */
	function initTiles($tilesize,$x,$y,$w,$h,$pixels_per_km,$mercator=false,$exact=false,$ispantoken=false,$center=false)
	{
		#trigger_error("<$tilesize,$x,$y,$w,$h,$pixels_per_km <{$this->mercator}>", E_USER_NOTICE);
		$this->mercator = !empty($mercator);
		$this->tilesize=intval($tilesize);
		$this->image_w=intval($w);
		$this->image_h=intval($h);
		if ($this->mercator)
			$pixels_per_km=intval($pixels_per_km);
		else
			$pixels_per_km=floatval($pixels_per_km);
		if (!$this->setScale($pixels_per_km))#FIXME
			return false;
		#trigger_error("$x,$y ---", E_USER_NOTICE);
		$x = intval($x);
		$y = intval($y);
		if ($center) {
			if ($this->mercator) {
				require_once('geograph/conversionslatlong.class.php');
				$conv = new ConversionsLatLong;
				list($lat, $lon) = $conv->internal_to_wgs84($x,$y,false);
				list($x, $y) = $conv->wgs84_to_lev19($lat, $lon);
			}
			$x -= $this->image_w / $this->pixels_per_unit / 2;
			$y -= $this->image_h / $this->pixels_per_unit / 2;
			$x = round($x);
			$y = round($y);
		}
		$orig = $this->getAlignedOrigin($x, $y, $ispantoken, $exact);
		#trigger_error("$x,$y --- {$orig[0]},{$orig[1]}", E_USER_NOTICE);
		$this->map_x=$orig[0];
		$this->map_y=$orig[1];
		$origx = floor($this->map_x/$this->units_per_tile)*$this->units_per_tile;
		$origy = floor($this->map_y/$this->units_per_tile)*$this->units_per_tile;
		/* shift in pixels */
		$this->dx=round(($this->map_x-$origx)*$this->pixels_per_unit);
		$this->dy=round(($this->map_y-$origy)*$this->pixels_per_unit);
		/* map count */
		$this->mosaic_factor_x = ceil(($this->map_x+$this->image_w/$this->pixels_per_unit-$origx)/$this->units_per_tile);
		$this->mosaic_factor_y = ceil(($this->map_y+$this->image_h/$this->pixels_per_unit-$origy)/$this->units_per_tile);
		/* map origin (km) */
		$this->tile_x = $origx;
		$this->tile_y = $origy;
		/* shift in pixels, other side */
		$this->dx2 = $this->mosaic_factor_x*$this->tilesize-$this->dx-$this->image_w;
		$this->dy2 = $this->mosaic_factor_y*$this->tilesize-$this->dy-$this->image_h;
		#trigger_error(">$tilesize,$x,$y,$w,$h,$pixels_per_km/{$this->pixels_per_unit} <{$this->mercator}> => t(km) {$this->units_per_tile} => $origx,$origy  {$this->mosaic_factor_x},{$this->mosaic_factor_y} {$this->dx},{$this->dy} {$this->dx2},{$this->dy2}", E_USER_NOTICE);
		return true;
	}

	/**
	* get the bounding box in pixels in terms of another mosaic
	* @access public
	*/
	function getBoundingBox($mosaic) {
		if ($this->mercator) {
			$R = pow(2.0, $this->pixels_per_km - $mosaic->pixels_per_km);
		} else {
			$R = $this->pixels_per_km / $mosaic->pixels_per_km;
		}

		$bounds = new BoundingBox;
		$bounds->width = round($mosaic->image_w * $R);
		$bounds->height = round($mosaic->image_h * $R);
		
		$bounds->left = round(($mosaic->map_x - $this->map_x) * $this->pixels_per_unit);
		$bounds->top = round(($mosaic->map_y - $this->map_y) * $this->pixels_per_unit);
		
		$bounds->top =$this->image_h - $bounds->top - $bounds->height;
		
		return $bounds;
	}
	

	/**
	* get position in pixels in terms of a gridsquare
	* @access public
	*/
	function getSquarePoint($square) {
		$point = new BoundingBox;

		$x = $square->x;
		$y = $square->y;

		if ($this->mercator) {
			require_once('geograph/conversionslatlong.class.php');
			$conv = new ConversionsLatLong;
			#trigger_error("sp $lat $lon  <- $x $y", E_USER_NOTICE);
			list($lat, $lon) = $conv->internal_to_wgs84($x,$y);
			list($x, $y) = $conv->wgs84_to_lev19($lat, $lon);
			#trigger_error("sp $lat $lon  -> $x $y", E_USER_NOTICE);
		}

		$point->left = round(($x - $this->map_x) * $this->pixels_per_unit);
		$point->top = round(($y - $this->map_y) * $this->pixels_per_unit);
		
		$point->top =$this->image_h - $point->top;
		
		return $point;
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
		if (!$this->tilesize) {
			$token->setValue("f",  $this->mosaic_factor_x);
			$token->setValue("g",  $this->mosaic_factor_y);
		}
		if ($this->mosaictype) {
			$token->setValue("M", $this->mosaictype);
		}
		if ($this->mercator) {
			$token->setValue("m", 1);
		}
		$token->setValue("z",  $this->tilesize);

		if ($this->palette)
			$token->setValue("p",  $this->palette);
		
		if (!empty($this->type_or_user))
			$token->setValue("t",  $this->type_or_user);
		return $token->getToken();
	}

	/**
	* Initialise class from a token
	* @access public
	*/
	function setToken($tokenstr,$allowWithoutMosaic = false)
	{
		$ok=false;
		
		$token=new Token;
		if ($token->parse($tokenstr))
		{
			$ok1=$token->hasValue("x") &&
				$token->hasValue("y") &&
				$token->hasValue("w") &&
				$token->hasValue("h") &&
				$token->hasValue("s") &&
				($allowWithoutMosaic || $token->hasValue("f") );
			$ok2=$token->hasValue("x") &&
				$token->hasValue("y") &&
				$token->hasValue("w") &&
				$token->hasValue("h") &&
				$token->hasValue("s") &&
				$token->hasValue("z") &&
				$token->getValue("z");
			$this->mosaictype = $token->hasValue("M") ? $token->getValue("M") : 0;
			$ok = $ok1||$ok2;
			if ($ok2) {
				$this->mercator = $token->hasValue("m") && $token->getValue("m");
				if (!$this->mercator) {
					if ($this->mosaictype == 1) #FIXME conf
						$this->scales = array(0 => 0.13, 1 => 0.13, 2 => 0.13, 3 => 1);
					elseif ($this->mosaictype == 2)
						$this->scales = array(0 => 0.3, 1 => 1, 2 => 4, 3 => 40);
					else
						$this->scales = array(0 => $token->getValue("s"));
				} else {
					if ($this->mosaictype == 1) #FIXME conf
						$this->scales = array(0 => 4, 1 => 4, 2 => 4, 3 => 7);
					elseif ($this->mosaictype == 2)
						$this->scales =  array(0 => 5, 1 => 7, 2 => 9, 3 => 12);
					else
						$this->scales = array(0 => $token->getValue("s"));
				}
				$this->initTiles($token->getValue("z"),
				                 $token->getValue("x"), $token->getValue("y"),
				                 $token->getValue("w"), $token->getValue("h"),
				                 $token->getValue("s"),
				                 $this->mercator,
				                 true);
				$this->type_or_user = ($token->hasValue("t"))?$token->getValue("t"):0;
				$this->palette = ($token->hasValue("p"))?$token->getValue("p"):0;
			}
			elseif ($ok1)
			{
				if ($this->mosaictype == 1) #FIXME conf
					$this->scales = array(0 => 0.13, 1 => 0.13, 2 => 0.13, 3 => 1);
				elseif ($this->mosaictype == 2)
					$this->scales = array(0 => 0.3, 1 => 1, 2 => 4, 3 => 40);
				else
					$this->scales = array($token->getValue("s"));
				$this->setOrigin($token->getValue("x"), $token->getValue("y"));
				$this->setMosaicSize($token->getValue("w"), $token->getValue("h"));
				$this->setScale($token->getValue("s"));
				$this->setMosaicFactor(($token->hasValue("f"))?$token->getValue("f"):2,$token->hasValue("g")?$token->getValue("g"):0);
				$this->type_or_user = ($token->hasValue("t"))?$token->getValue("t"):0;
				$this->palette = ($token->hasValue("p"))?$token->getValue("p"):0;
			}
			else
			{
				$info="";
				foreach($token->data as $name=>$value)
				{
					$info.="$name=$value ";
				}
				$this->_err("setToken: missing elements ($info)");
			}
		}
		else
		{
			$this->_err("setToken: parse failure");
		
		}
		
		return $ok;
	}


	/**
	* return 2d array of GeographMap objects for the mosaic
	* @access public
	*/
	function& getImageArray()
	{
		$images=array();
		
		//to calc the origin we need to know
		//how many internal units in each image
		$img_w = $this->tilesize ? $this->tilesize : $this->image_w / $this->mosaic_factor_x;
		$img_h = $this->tilesize ? $this->tilesize : $this->image_h / $this->mosaic_factor_y;
		$img_w_unit = $img_w/$this->pixels_per_unit;
		$img_h_unit = $img_h/$this->pixels_per_unit;

		#trigger_error("{$this->tilesize}/[{$this->mercator}]: {$this->tile_x},{$this->tile_y} ({$this->pixels_per_km}) {$img_w}x{$img_h}  {$img_w_unit}x{$img_h_unit}  {$this->mosaic_factor_x}x{$this->mosaic_factor_y} {$this->dx},{$this->dy} {$this->dx2},{$this->dy2}   {$this->units_per_tile}  {$this->shift_x},{$this->shift_y}", E_USER_NOTICE);

		//top to bottom
		for ($j=0; $j<$this->mosaic_factor_y; $j++)
		{
			$images[$j]=array();
			
			//left to right
			for ($i=0; $i<$this->mosaic_factor_x; $i++)
			{
				$images[$j][$i]=new GeographMap;
				
				$images[$j][$i]->enableCaching($this->caching);
				$images[$j][$i]->enableMercator($this->mercator);

				$map_x = $this->tile_x + $i*$img_w_unit;
				$map_y = $this->tile_y + ($this->mosaic_factor_y-$j-1)*$img_h_unit;
				if ($this->mercator) {
					$map_x = floor($map_x / $this->units_per_tile) + $this->shift_x;
					$map_y = $this->shift_y - floor($map_y / $this->units_per_tile);

					$images[$j][$i]->overlay = 0;
					$images[$j][$i]->layers = 31;
				}
	
				$images[$j][$i]->setScale($this->pixels_per_km);
		
				$images[$j][$i]->setOrigin($map_x, $map_y);

				$images[$j][$i]->setImageSize($img_w, $img_h);
				
				$images[$j][$i]->setPalette($this->palette);

				if ($this->tilesize) {
					$dx1 = 0;
					$dx2 = 0;
					$dy1 = 0;
					$dy2 = 0;
					if ($i == 0)
						$dx1 = $this->dx;
					if ($i == $this->mosaic_factor_x-1)
						$dx2 = $this->dx2;
					if ($j == 0)
						$dy2 = $this->dy2;
					if ($j == $this->mosaic_factor_y-1)
						$dy1 = $this->dy;
					$images[$j][$i]->setClip($dx1, $dx2, $dy1, $dy2);
					#trigger_error("$i/$j: {$images[$j][$i]->tile_x}/{$images[$j][$i]->tile_y}: $dx1, $dx2, $dy1, $dy2", E_USER_NOTICE);
				}
		
				if (isset($this->reference_index))
					$images[$j][$i]->reference_index = $this->reference_index;
				if (!empty($this->type_or_user)) {
					if ($this->type_or_user > 0) {
						$images[$j][$i]->type_or_user = $images[$j][$i]->needUserTile($this->type_or_user)?$this->type_or_user:-10;
					} else {
						$images[$j][$i]->type_or_user = $this->type_or_user;
					}
				}
			}
		
		}
		$this->imagearray =& $images;
		return $images;
	}

	/**
	* @access public
	*/
	function fillGridMap($isimgmap = false)
	{
		if (empty($this->imagearray))
			$this->getImageArray();

		foreach ($this->imagearray as $j => $row)
			foreach ($row as $i => $map)
				$this->imagearray[$j][$i]->grid = $map->getGridArray($isimgmap);
	}

	/**
	* get information on when the maps where last updated
	* @access public
	*/
	function getUpdateDateString()
	{
		global $CONF;
		$root=&$_SERVER['DOCUMENT_ROOT'];
		
		if (empty($this->imagearray))
			$this->getImageArray();
			
		$recent = 0;	
		$oldest = 999999999999;	
		foreach ($this->imagearray as $j => $row)
			foreach ($row as $i => $map) {
				if ($map->type_or_user != -10) {
					$filename = $root.$map->getImageFilename();
					if (file_exists($filename) && ($date = filemtime($filename)) != FALSE) {
						$recent = max($recent,$date);
						$oldest = min($oldest,$date);
					}
				}
			}
		if ($recent) {
			if ( abs($recent-$oldest) < 1000) {
				if ($CONF['lang'] == 'de')
					return "Karten aktualisiert am ".strftime("%A, %d.%m. um %H:%M",$recent);
				else
					return "Maps last updated at: ".strftime("%A, %d %b at %H:%M",$recent);
			} else {
				if ($CONF['lang'] == 'de')
					return "Karten aktualisiert zwischen ".strftime("%A, %d.%m. um %H:%M",$oldest)." und ".strftime("%A, %d.%m. um %H:%M",$recent);
				else
					return "Maps updated between: ".strftime("%A, %d %b at %H:%M",$oldest)." and ".strftime("%A, %d %b at %H:%M",$recent);
			}
		}
	}

	/**
	* get grid reference for pixel position on mosaic
	* @access public
	*/
	function getGridRef($x, $y, $map_x=null, $map_y=null)
	{
		global $CONF;
		if ($x == -1 && $y == -1) {
			$x = intval($this->image_w / 2);
			$y = intval($this->image_h / 2);
		} else {
			//invert the y coordinate
			$y=$this->image_h-$y; # FIXME off by one?
		}
		if (is_null($map_y)) {
			$map_x = $this->map_x;
			$map_y = $this->map_y;
		}
		$db=&$this->_getDB();
		
		//convert pixel pos to internal coordinates
		if ($this->mercator) {
			require_once('geograph/conversionslatlong.class.php');
			$conv = new ConversionsLatLong;
			list($lat, $lon) = $conv->lev19_to_wgs84($map_x + $x/$this->pixels_per_unit,$map_y + $y/$this->pixels_per_unit);
			list($x_km, $y_km) = $conv->wgs84_to_internal($lat, $lon, false);
			$x_km = round($x_km);
			$y_km = round($y_km);
		} else {
			#$x_km=$map_x + floor($x/$this->pixels_per_unit);
			#$y_km=$map_y + floor($y/$this->pixels_per_unit);
			$x_km=$map_x + round($x/$this->pixels_per_unit);
			$y_km=$map_y + round($y/$this->pixels_per_unit);
		}
		
		$row=$db->GetRow("select reference_index,grid_reference from gridsquare where CONTAINS( GeomFromText('POINT($x_km $y_km)'),point_xy )");
			
		if (!empty($row['reference_index'])) {
			$this->gridref = $row['grid_reference'];
			$this->reference_index = $row['reference_index'];
		} else {
			//But what to do when the square is not on land??
		
			//when not on land just try any square, but why not use land to decide if the square is in use? (works well with the spatial index)
			// but favour the _smaller_ grid 
			#if (isset($this->old_centrex)) {
			#	//if zooming out use the old grid!
			#	//or in then use the click point grid
			#	$x_point=$this->old_centrex;
			#	$y_point=$this->old_centrey;
			#} else {
				$x_point=$x_km;
				$y_point=$y_km;
			#}
			$x_lim=$x_point-100;
			$y_lim=$y_point-100;
			$point = "'POINT($x_point $y_point)'";
			$sql="select prefix,origin_x,origin_y,reference_index from gridprefix 
				where CONTAINS(geometry_boundary, GeomFromText($point)) and (origin_x > $x_lim) and (origin_y > $y_lim)
				order by landcount desc, reference_index desc limit 1";
			$prefix=$db->GetRow($sql);
			
			#if (empty($prefix['prefix'])) { 
			#	//if fails try a less restrictive search
			#	if (isset($this->old_centrex)) {
			#		$sql="select prefix,origin_x,origin_y,reference_index from gridprefix 
			#			where {$this->old_centrex} between origin_x and (origin_x+width-1) and 
			#			{$this->old_centrey} between origin_y and (origin_y+height-1)
			#			order by landcount desc, reference_index limit 1";
			#	} else {
			#		$sql="select prefix,origin_x,origin_y,reference_index from gridprefix 
			#			where $x_km between origin_x and (origin_x+width-1) and 
			#			$y_km between origin_y and (origin_y+height-1)
			#			order by landcount desc, reference_index limit 1";
			#	}
			#	$prefix=$db->GetRow($sql);
			#}
							
			if (!empty($prefix['prefix'])) { 
				$n=$y_km-$prefix['origin_y'];
				$e=$x_km-$prefix['origin_x'];
				#trigger_error("E/N: " . $e . "/" . $n ." <-- ".$x_point. "/" .$y_point."  |  " . $x_km . "/" . $y_km."  |  " .$this->old_centrex."/" .$this->old_centrey , E_USER_NOTICE);
				# [Mon Mar 02 09:24:22 2009] [error] [client 129.69.47.178] PHP Notice:  E/N: 0/100 [-- 601/396  |  600/400  |  601/396 in /is/htdocs/wp1036181_DDJ1DZ0I8N/geo/libs/geograph/mapmosaic.class.php on line 556, referer: http://geo.hlipp.de/mapbrowse.php
				# http://geo.hlipp.de/mapbrowse.php?t=toVJ5oOXXJ0oX.VJFoOXXJfo-lNXJqo-NMJL5405olhNNhOV8wjNbjXww&i=1&j=2&zoomin=1?110,10
				#  Grid Reference at centre UUR00100
				#  Map width 400 km
				#  => prefix must not be calculated from old_centre, or use x_point/y_point instead of x_km,y_km
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
	* get pan url, if possible - return empty string if limit is hit
	* @param xdir = amount of left/right panning, e.g. -1 to pan left
	* @param ydir = amount of up/down panning, e.g. 1 to pan up
	* @access public
	*/
	function getPanToken($xdir,$ydir)
	{
		$out=new GeographMapMosaic;
		
		//no panning unless you are zoomed in
		if ($this->level > 0)
		{
			//pan half a map # FIXME only true if mosaic_factor==2
			//figure out image size in km
			$mapw=$this->image_w/$this->pixels_per_unit;
			$maph=$this->image_h/$this->pixels_per_unit;

			//figure out how many pixels to pan by
			if (!$this->tilesize) {
				$panx=round($mapw/$this->mosaic_factor_x);
				$pany=round($maph/$this->mosaic_factor_y);
			} elseif ($this->mercator) {
				$panx=$mapw/2;
				$pany=$maph/2;
			} else {
				$panx=round($mapw/2);
				$pany=round($maph/2);
			}

			$out->mosaictype = $this->mosaictype;
			$out->scales = $this->scales;
			if ($this->tilesize) {
				$out->initTiles($this->tilesize, $this->map_x + $panx*$xdir, $this->map_y + $pany*$ydir, $this->image_w, $this->image_h, $this->pixels_per_km, $this->mercator, true);#FIXME
				$out->type_or_user = $this->type_or_user;
			} else {
				$out->setScale($this->pixels_per_km);
				$out->setMosaicFactor($this->mosaic_factor_x,$this->mosaic_factor_y);
				$out->setMosaicSize($this->image_w, $this->image_h);
				$out->setAlignedOrigin(
					$this->map_x + $panx*$xdir,
					$this->map_y + $pany*$ydir,true);
				$out->type_or_user = $this->type_or_user;
			}
		}
		return $out->getToken();
	}
	
	
	/**
	* get big token
	* @access public
	*/
	function getBigToken()
	{
		$out=new GeographMapMosaic;

		//figure out image size in km
		$mapw=$this->image_w/$this->pixels_per_km;
		$maph=$this->image_h/$this->pixels_per_km;

		//figure out how many pixels to pan by
		if (!$this->tilesize) {
			$panx=round($mapw/$this->mosaic_factor_x);
			$pany=round($maph/$this->mosaic_factor_y);
		} elseif ($this->mercator) {
			$panx=$mapw/2;
			$pany=$maph/2;
		} else {
			$panx=round($mapw/2);
			$pany=round($maph/2);
		}

		if ($this->tilesize) {
			$out->initTiles($this->tilesize, $this->map_x - $panx, $this->map_y - $pany, $this->image_w*2, $this->image_h*2, $this->pixels_per_km);#FIXME
			$out->type_or_user = $this->type_or_user;
		} else {
			$out->setScale($this->pixels_per_km);
			$out->setMosaicFactor($this->mosaic_factor_x*2,$this->mosaic_factor_y*2);
			$out->setMosaicSize($this->image_w*2, $this->image_h*2);
			$out->type_or_user = $this->type_or_user;
			$out->setAlignedOrigin(
				$this->map_x - $panx,
				$this->map_y - $pany,true);
		}
		
		return $out->getToken();
	}
		
		
	
	
	/**
	* get a url that will zoom us out one level of this mosaic
	* @access public
	*/
	function getZoomOutToken()
	{
		//if at full extent then dont want a zoom out token
		if ($this->mosaictype != 2 || $this->level <=  0) {
			return FALSE;
		} 

		$out=new GeographMapMosaic;

		//if we're zoomed out 1 pixel per km, then we only need
		//zoom out to a default map, otherwise, we need to zoom
		//out keeping vaguely centred on current position
		if ($this->tilesize || $this->level > 1) # FIXME move here
		{
			#$zoomindex = array_search($this->pixels_per_km,$this->scales);
			#$zoomindex--;
			$level = $this->level - 1;
			
			#if ($zoomindex >=1)
			#{
				//figure out central point
				$centrex=$this->map_x + ($this->image_w / $this->pixels_per_unit)/2;
				$centrey=$this->map_y + ($this->image_h / $this->pixels_per_unit)/2;
				
				//store the current center xy - as can be useful figuring out the the ri 
				$out->old_centrex = $centrex;
				$out->old_centrey = $centrey;
				
				$scale = $this->scales[$level];
				#trigger_error("--------------$level $scale <{$this->mercator}> {$this->tilesize}", E_USER_NOTICE);
			
				$pixels_per_unit = $this->mercator ? $this->tilesize / pow(2, 19-$scale) : $scale;

				//figure out what the perfect origin would be
				$mapw=$this->image_w/$pixels_per_unit;
				$maph=$this->image_h/$pixels_per_unit;

				$bestoriginx=$centrex - $mapw/2;
				$bestoriginy=$centrey - $maph/2;

				$out->mosaictype = $this->mosaictype;
				$out->scales = $this->scales;
				if ($this->tilesize) {
					$out->initTiles($this->tilesize, round($bestoriginx), round($bestoriginy), $this->image_w, $this->image_h, $scale, $this->mercator);#FIXME
					$out->type_or_user = $this->type_or_user;
				} else {
					$out->setScale($scale);
					#FIXME $out->setMosaicSize($this->image_w, $this->image_h);
					$out->setMosaicSize($this->image_w, $this->image_h);

					//stick with current mosaic factor
					$out->setMosaicFactor($this->mosaic_factor_x,$this->mosaic_factor_y);
					$out->type_or_user = $this->type_or_user;

					$out->setAlignedOrigin($bestoriginx, $bestoriginy);
				}
			#}
		} else {
			$out->setMosaicFactor(3);
			$out->type_or_user = $this->type_or_user;
		}
		#trigger_error("==== <{$out->mercator}> {$out->tilesize} {$out->level} {$out->pixels_per_km} {$out->type_or_user} {$out->image_w}x{$out->image_h} {$out->map_x}/{$out->map_y}", E_USER_NOTICE);
		return $out->getToken();
	}

	/**
	* get a token that will zoom us one level into this mosaic
	* @access public
	*/
	function getZoomInToken()
	{
		$out=new GeographMapMosaic;
			
		#$zoomindex = array_search($this->pixels_per_km,$this->scales);
		#if ($zoomindex === FALSE) 
		#	$zoomindex = 0;
		#$zoomindex++;
		$level = $this->level + 1;

		if ($level < count($this->scales))
		{
			//figure out central point
			$centrex=$this->map_x + ($this->image_w / $this->pixels_per_unit)/2;
			$centrey=$this->map_y + ($this->image_h / $this->pixels_per_unit)/2;

			$scale = $this->scales[$level];

			$pixels_per_unit = $this->mercator ? $this->tilesize / pow(2, 19-$scale) : $scale;

			//figure out what the perfect origin would be
			$mapw=$this->image_w/$pixels_per_unit;
			$maph=$this->image_h/$pixels_per_unit;

			$bestoriginx=$centrex - $mapw/2;
			$bestoriginy=$centrey - $maph/2;
			
			$out->mosaictype = $this->mosaictype;
			$out->scales = $this->scales;
			if ($this->tilesize) {
				$out->initTiles($this->tilesize, round($bestoriginx), round($bestoriginy), $this->image_w, $this->image_h, $scale, $this->mercator);#FIXME
				$out->type_or_user = $this->type_or_user;
			} else {
				$out->setScale($scale);
				#FIXME $out->setMosaicSize($this->image_w, $this->image_h);
				$out->setMosaicSize($this->image_w, $this->image_h);

				$out->setMosaicFactor(2);
				$out->type_or_user = $this->type_or_user;

				$out->setAlignedOrigin($bestoriginx, $bestoriginy);
			}
			return $out->getToken();
		}
		else 
		{
			return FALSE;
		}
	}
	
	/**
	* get a url that will zoom us out one level from this gridsquare
	* @access public
	*/
	function getGridSquareToken($gridsquare, $mmap=false)
	{
		if (is_numeric($gridsquare)) {
			$id = $gridsquare;
			$gridsquare = new GridSquare;
			$gridsquare->loadFromId($id);
		}
		$out=new GeographMapMosaic($mmap?'zoomedin_tm':'zoomedin', $gridsquare->x,$gridsquare->y);
		return $out->getToken();		
	}

	/**
	* Calculates new origin, aligning it on particular boundaries to
	* reduce the number of image tiles that get generated
	*/
	function getAlignedOrigin($bestoriginx, $bestoriginy, $ispantoken = false, $exact = false)
	{
		global $CONF;

		//figure out image size in map units
		$mapw = $this->image_w / $this->pixels_per_unit;
		$maph = $this->image_h / $this->pixels_per_unit;

		if ($this->mercator) {
			$x1 = $CONF['xmrange'][0]-262144;
			$x2 = $CONF['xmrange'][1]-262144;
			$y1 = 262143-$CONF['ymrange'][1];
			$y2 = 262143-$CONF['ymrange'][0];
		} else {
			$x1 = $CONF['minx'];
			$x2 = $CONF['maxx'];
			$y1 = $CONF['miny'];
			$y2 = $CONF['maxy'];

		}

		if (!$exact) {
			# this way, we have the chance to find a cached version of the map
			# we'd need to put a lot of things (clipping, imagemap, ...) in
			# {dynamic} tags otherwise and we couldn't use the tokens as cache id then.
			if ($this->mercator) {
				$origin = array(0, 0);

				$divx = 4;
				$divy = 4;
			} else {
				if (!$this->reference_index) {
					//this sets the most likly reference_index for the center of the map
					$this->getGridRef(-1,-1,$bestoriginx, $bestoriginy);
				}

				if ($this->reference_index) {
					$origin = $CONF['origins'][$this->reference_index];
				} else {
					$origin = array(0, 0);
				}

				//figure out an alignment factor - here we align on tile
				//boundaries so that panning the image allows reuse of tiles
				if (!$this->tilesize) {
					$divx = $this->mosaic_factor_x;
					$divy = $this->mosaic_factor_y;
				} elseif ($this->mosaictype==2) { # large mosaic on mapbrowse page
					$divx = 2;
					$divy = 2;
				} else {
					#FIXME 4?
					$divx = 2;
					$divy = 2;
				}
				if (!$ispantoken) {
					$divx *= 2;
					$divy *= 2;
				}
			}
			$walign = round($mapw / $divx);
			$halign = round($maph / $divy);
			$bestoriginx = floor(($bestoriginx - $origin[0]) / $walign) * $walign + $origin[0];
			$bestoriginy = floor(($bestoriginy - $origin[1]) / $halign) * $halign + $origin[1];
		}

		$dx = $x2 - $x1 + 1;
		$dy = $y2 - $y1 + 1;

		if ($mapw >= $dx) {
			$bestoriginx = round(0.5 * ($x2 + $x1 - $mapw));
		} elseif ($bestoriginx < $x1) {
			$bestoriginx = $x1;
		} elseif ($bestoriginx + $mapw > $x2) {
			$bestoriginx = ceil($x2 - $mapw);
		} else {
			$bestoriginx = $bestoriginx;
		}

		if ($maph >= $dy) {
			$bestoriginy = round(0.5 * ($y2 + $y1 - $maph));
		} elseif ($bestoriginy < $y1) {
			$bestoriginy = $y1;
		} elseif ($bestoriginy + $maph > $y2) {
			$bestoriginy = ceil($y2 - $maph);
		} else {
			$bestoriginy = $bestoriginy;
		}

		return array(intval($bestoriginx), intval($bestoriginy));
	}

	/**
	* Sets the origin, but aligns the origin on particular boundaries to
	* reduce the number of image tiles that get generated
	*/
	function setAlignedOrigin($bestoriginx, $bestoriginy, $ispantoken = false, $exact = false )
	{
		$orig = $this->getAlignedOrigin($bestoriginx, $bestoriginy, $ispantoken, $exact);
		$this->setOrigin($orig[0],$orig[1]);
	}


	/**
	* get internal/level 19 coordinates of a mosaic click
	* @access public
	*/
	function getClickCoordinates($i, $j, $x, $y, $calcinternal=false)
	{
		//we got the click coords x,y on mosaic i,j
		$imgw = $this->tilesize ? $this->tilesize : $this->image_w / $this->mosaic_factor_x;
		$imgh = $this->tilesize ? $this->tilesize : $this->image_h / $this->mosaic_factor_y;
		$x+=$i*$imgw;
		$y+=$j*$imgh;
		
		//remap origin from top left to bottom left
		$y = $this->tilesize ? $this->mosaic_factor_y*$this->tilesize-1-$y : $this->image_h-$y;
		
		//lets figure out internal coords
		$coord=array();
		$coord[0]=floor($this->tile_x + $x/$this->pixels_per_unit);
		$coord[1]=floor($this->tile_y + $y/$this->pixels_per_unit);

		if ($calcinternal && $this->mercator) {
			require_once('geograph/conversionslatlong.class.php');
			$conv = new ConversionsLatLong;
			list($lat, $lon) = $conv->lev19_to_wgs84($coord[0],$coord[1]);
			$coord = $conv->wgs84_to_internal($lat, $lon);
		}

		return $coord;
	}
	
	/**
	* Set center of map in internal coordinates, returns true if valid
	* @access public
	*/
	function setCentre($x,$y, $ispantoken = false, $exact = false)
	{
		return $this->setAlignedOrigin(
			intval($x - ($this->image_w / $this->pixels_per_km)/2),
			intval($y - ($this->image_h / $this->pixels_per_km)/2), $ispantoken, $exact);
	}
	
	/**
	* Get center of map in internal/level 19 coordinates
	* @access public
	*/
	function getCentre($calcinternal=false)
	{
		$x = $this->map_x + intval(($this->image_w / 2) / $this->pixels_per_unit);
		$y = $this->map_y + intval(($this->image_h / 2) / $this->pixels_per_unit);

		if ($calcinternal && $this->mercator) {
			require_once('geograph/conversionslatlong.class.php');
			$conv = new ConversionsLatLong;
			list($lat, $lon) = $conv->lev19_to_wgs84($x,$y);
			list($x, $y) = $conv->wgs84_to_internal($lat, $lon);
		}
		return array($x, $y);
	}

	/**
	* Get center of map tile in pixels
	* @access public
	*/
	function getTileCentre($i, $j)
	{
		if ($this->tilesize) {
			$x=round(($this->tilesize+(i==0?$this->dx :0)-(i==$this->mosaic_factor_x-1?$this->dx2:0))/2);
			$y=round(($this->tilesize+(j==0?$this->dy2:0)-(j==$this->mosaic_factor_y-1?$this->dy :0))/2);
		} else {
			$x=round(($this->image_w/$this->mosaic_factor_x)/2);
			$y=round(($this->image_h/$this->mosaic_factor_y)/2);
		}
		return array($x,$y);
	}

	/**
	* Recenter map
	* @access public
	*/
	function recenter($x, $y, $scale = null, $mosaicfactor = null, $exact = false, $kilometres = false)
	{
		#FIXME Align!
		if ($this->mercator && $kilometres) {
			require_once('geograph/conversionslatlong.class.php');
			$conv = new ConversionsLatLong;
			trigger_error("sp $lat $lon  <- $x $y", E_USER_NOTICE);
			list($lat, $lon) = $conv->internal_to_wgs84($x,$y,0,false);
			list($x, $y) = $conv->wgs84_to_lev19($lat, $lon);
			trigger_error("sp $lat $lon  -> $x $y", E_USER_NOTICE);
		}
		if ($this->tilesize) {
			if (is_null($scale))
				$scale = $this->pixels_per_km;
			$pixels_per_unit = $this->mercator ? $this->tilesize / pow(2, 19-$scale) : $scale;
			#trigger_error("$x,$y ($scale) <--  {$this->map_x},{$this->map_y} {$this->image_w}x{$this->image_h}, {$this->pixels_per_unit} : $pixels_per_unit", E_USER_NOTICE);

			$this->initTiles($this->tilesize,
					   round($x-$this->image_w/$pixels_per_unit/2),
					   round($y-$this->image_h/$pixels_per_unit/2),
					   $this->image_w,$this->image_h,$scale,$this->mercator,$exact);#FIXME max xy
			#trigger_error("                    {$this->map_x},{$this->map_y} {$this->image_w}x{$this->image_h}, {$this->pixels_per_unit}", E_USER_NOTICE);
		} else {
			if (!is_null($mosaicfactor))
				$this->setMosaicFactor($mosaicfactor);
			if (!is_null($scale))
				$this->setScale($scale);
			$this->setCentre($x, $y, false, $exact);
		}
	}

	/**
	* Given index of a mosaic image, and a pixel position on that image handle a zoom
	* If the zoom level is 2, this needs to perform a redirect to the gridsquare page
	* otherwise, it reconfigures the instance for the zoomed in map
	* @access public
	*/
	function zoomIn($i, $j, $x, $y)
	{
		
		$level = $this->level + 1;
		if ($level >= count($this->scales) || !$this->mercator && $this->pixels_per_km > 40 || $this->mercator && $this->pixels_per_km > 13)#FIXME
		{
			//so where did we click?
			list($clickx, $clicky)=$this->getClickCoordinates($i, $j, $x, $y, true);
			
			//we're going to zoom into a grid square
			$square=new GridSquare;
			if ($square->loadFromPosition($clickx, $clicky))
			{
				$images=$square->getImages(false,'','order by moderation_status+0 desc,seq_no limit 2');
				
				//if the image count is 1, we'll go straight to the image
				if (count($images)==1)
				{
					$url="http://".$_SERVER['HTTP_HOST'].'/photo/'.
						$images[0]->gridimage_id;
				}
				else
				{
					//lets go to the grid reference
					$url="http://".$_SERVER['HTTP_HOST'].'/gridref/'.$square->grid_reference;
				}
				
			}
			else
			{
				require_once('geograph/conversions.class.php');
				$conv = new Conversions;
		
				list($gr,$len) = $conv->internal_to_gridref($clickx,$clicky,0);
				$url="http://".$_SERVER['HTTP_HOST'].'/gridref/'.$gr;
			}
			header("Location:$url");
			exit;
		} else {
			$scale = $this->scales[$level];
		}
		//so where did we click?
		#trigger_error("<- $i, $j, $x, $y / {$this->map_x} {$this->map_y}", E_USER_NOTICE);
		list($clickx, $clicky)=$this->getClickCoordinates($i, $j, $x, $y);
		#trigger_error("-> $clickx, $clicky     $scale", E_USER_NOTICE);

		//store the clicked position to make a better estimate at the required grid
		$this->old_centrex = $clickx;
		$this->old_centrey = $clicky;

		$pixels_per_unit = $this->mercator ? $this->tilesize / pow(2, 19-$scale) : $scale;

		//size of new map in map units

		$mapw=$this->image_w/$pixels_per_unit;
		$maph=$this->image_h/$pixels_per_unit;

		//here's the perfect origin
		$bestoriginx=$clickx-$mapw/2;
		$bestoriginy=$clicky-$maph/2;

		if ($this->tilesize) {
			$this->initTiles($this->tilesize, $bestoriginx, $bestoriginy, $this->image_w, $this->image_h, $scale, $this->mercator);
		} else {
			$this->setScale($scale);
			$this->setMosaicFactor(2);
			$this->setAlignedOrigin($bestoriginx, $bestoriginy);
		}
	}

	## FIXME All expirePosition/.../getXxxFilename/... functions should go to map.class.php.
	##       We should get rid of any getXxxFilename function outside map.class.php to avoid inconsistent file names.

	/**
	* Given a coordinate, this ensures that any cached map images are expired
	* This should really be static
	* @access public
	*/
	function expirePosition($x,$y,$user_id = 0,$expire_basemaps=false)
	{
		global $memcache;
		
		$db=&$this->_getDB();
		
		$and_crit = " and type_or_user IN (-1,0";
		if ($user_id > 0) {
			$and_crit .= ",$user_id";
		}
		$and_crit .= ")";

		$xycrit = "mercator='0' and '$x' between map_x and max_x and '$y' between map_y and max_y";
		$sql = "select gxlow,gylow,gxhigh,gyhigh from gridsquare gs inner join gridsquare_gmcache gm using (gridsquare_id) where x='$x' and y='$y' limit 1";
		$mercator = $db->GetRow($sql);
		$havemercator = $mercator !== false && count($mercator);
		if ($havemercator) {
			$MCscale = 524288/(2*6378137.*M_PI);
			$xMC_min = floor($mercator['gxlow'] * $MCscale);
			$yMC_min = floor($mercator['gylow'] * $MCscale);
			$xMC_max = ceil ($mercator['gxhigh'] * $MCscale);
			$yMC_max = ceil ($mercator['gyhigh'] * $MCscale);
			$xycrit .= " or mercator='1' and '$xMC_min'<=max_x and '$xMC_max'>=map_x and '$yMC_min'<=max_y and '$yMC_max'>=map_y";
		}

		$deleted = 0;
		$root=&$_SERVER['DOCUMENT_ROOT'];

		if ($memcache->valid) {
			$sql="select * from mapcache where ($xycrit) $and_crit";
			
			$recordSet = &$db->Execute($sql);
			while (!$recordSet->EOF) 
			{
				$mkey = $this->getImageFilename($recordSet->fields);
				//memcache delete
				$memcache->name_delete('mi',$mkey);
				$memcache->name_delete('mc',$mkey);
				
				if ($expire_basemaps) {
					$file = $this->getBaseMapFilename($recordSet->fields);
					if (file_exists($root.$file)) {
						unlink($root.$file);
						$deleted++;
					} 
					$file = $this->getLabelMapFilename($recordSet->fields, false, true);
					if (file_exists($root.$file)) {
						unlink($root.$file);
						$deleted++;
					} 
					$file = $this->getLabelMapFilename($recordSet->fields, false, false);
					if (file_exists($root.$file)) {
						unlink($root.$file);
						$deleted++;
					} 
					$file = $this->getLabelMapFilename($recordSet->fields, true, false);
					if (file_exists($root.$file)) {
						unlink($root.$file);
						$deleted++;
					} 
				}
				$recordSet->MoveNext();
			}
			$recordSet->Close();
		}
		
		$sql="update mapcache set age=age+1 where ($xycrit) $and_crit";
		$db->Execute($sql);
		
		if ($expire_basemaps && !$memcache->valid) {
			
			$sql="select * from mapcache where ($xycrit)";
			$recordSet = &$db->Execute($sql);
			while (!$recordSet->EOF) 
			{
				$file = $this->getBaseMapFilename($recordSet->fields);
				if (file_exists($root.$file)) {
					unlink($root.$file);
					$deleted++;
				} 
				$file = $this->getLabelMapFilename($recordSet->fields, false, true);
				if (file_exists($root.$file)) {
					unlink($root.$file);
					$deleted++;
				} 
				$file = $this->getLabelMapFilename($recordSet->fields, false, false);
				if (file_exists($root.$file)) {
					unlink($root.$file);
					$deleted++;
				} 
				$file = $this->getLabelMapFilename($recordSet->fields, true, false);
				if (file_exists($root.$file)) {
					unlink($root.$file);
					$deleted++;
				} 
				$recordSet->MoveNext();
			}
			$recordSet->Close();
		}
		return $deleted;
	}
	
	
	/**
	* Given a sql criteria against mapcache, this ensures that any cached map images are deleted!
	* This should really be static
	* @access public
	*/
	function deleteBySql($crit,$dummy=false,$expire_basemaps=false)
	{
		global $memcache;
		$db=&$this->_getDB();
		
		$root=&$_SERVER['DOCUMENT_ROOT'];

		$sql="select * from mapcache where $crit";
		$deleted = 0;
		$recordSet = &$this->db->Execute($sql);
		while (!$recordSet->EOF) 
		{
			$file = $this->getImageFilename($recordSet->fields);
			if (file_exists($root.$file)) {
				if (!$dummy)
					unlink($root.$file);
				$deleted++;
			}
			$memcache->name_delete('mi',$file);
			$memcache->name_delete('ms',$file);

			if (!$dummy && $expire_basemaps) {
				$file = $this->getBaseMapFilename($recordSet->fields);
				if (file_exists($root.$file)) {
					unlink($root.$file);
				}
				$file = $this->getLabelMapFilename($recordSet->fields, false, true);
				if (file_exists($root.$file)) {
					unlink($root.$file);
				}
				$file = $this->getLabelMapFilename($recordSet->fields, false, false);
				if (file_exists($root.$file)) {
					unlink($root.$file);
				}
				$file = $this->getLabelMapFilename($recordSet->fields, true, false);
				if (file_exists($root.$file)) {
					unlink($root.$file);
				}
			}
			$recordSet->MoveNext();
		}
		$recordSet->Close();
		if (!$dummy)
			$db->Execute("delete from mapcache where $crit");
		return $deleted;
	}
	
	function getBaseMapFilename($row) # FIXME map.class.php?
	{
		$dir="/maps/base/";

		if (empty($row['mercator'])) {
			$map_x = $row['map_x'];
			$map_y = $row['map_y'];
			$ext = 'gd';
		} else {
			$map_x = $row['tile_x'];
			$map_y = $row['tile_y'];
			$ext = 'png';
		}

		$dir.="{$map_x}/";
		
		$dir.="{$map_y}/";

		$param = "";
		//FIXME palette?
		if (!empty($row['force_ri'])) {
			$param .= "_i{$row['force_ri']}";
		}

		if (empty($row['mercator'])) {
			$scale = $row['pixels_per_km'];
		} else {
			$scale = $row['level'];
			$param .= "_m";
		}

		$file="base_{$map_x}_{$map_y}_{$row['image_w']}_{$row['image_h']}_{$scale}$param.$ext";
		
		return $dir.$file;
	}
	function getLabelMapFilename($row, $towns, $regions) # FIXME map.class.php?
	{
		$dir="/maps/label/";

		if (empty($row['mercator'])) {
			$map_x = $row['map_x'];
			$map_y = $row['map_y'];
		} else {
			$map_x = $row['tile_x'];
			$map_y = $row['tile_y'];
		}

		$dir.="{$map_x}/";
		
		$dir.="{$map_y}/";

		$param = "";
		//FIXME palette?
		if (!empty($row['force_ri'])) {
			$param .= "_i{$row['force_ri']}";
		}
		if ($towns) {
			$param .= "_t";
		}
		if ($regions) {
			$param .= "_r";
		}

		if (empty($row['mercator'])) {
			$scale = $row['pixels_per_km'];
		} else {
			$scale = $row['level'];
			$param .= "_m";
			if (!empty($row['overlay'])) {
				$param .= "_o";
			}
		}

		$file="label_{$map_x}_{$map_y}_{$row['image_w']}_{$row['image_h']}_{$scale}$param.png";
		
		return $dir.$file;
	}
	function getImageFilename($row, $layers = -1) # FIXME map.class.php?
	{
		if ($layers == -1) {
			$layers == $row['layers'];
		}
		if ($layers == 1)
			return $this->getBaseMapFilename($row);
		elseif ($layers == 4)
			return $this->getLabelMapFilename($row, false, true);
		elseif ($layers == 8)
			return $this->getLabelMapFilename($row, false, false);
		elseif ($layers == 16)
			return $this->getLabelMapFilename($row, true, false);

		$dir="/maps/detail/";
		
		if (empty($row['mercator'])) {
			$map_x = $row['map_x'];
			$map_y = $row['map_y'];
		} else {
			$map_x = $row['tile_x'];
			$map_y = $row['tile_y'];
		}

		$dir.="{$map_x}/";
		
		$dir.="{$map_y}/";

		$param = "";
		//FIXME palette?
		if (!empty($row['force_ri'])) {
			$param .= "_i{$row['force_ri']}";
		}

		if (empty($row['mercator'])) {
			$scale = $row['pixels_per_km'];
			$extension = ($row['pixels_per_km'] > 64 || $row['type_or_user'] < -20)?'jpg':'png';
		} else {
			$scale = $row['level'];
			$extension = 'png';
			$param .= "_m";
			$param .= "_l{$layers}";
			if (!empty($row['overlay'])) {
				$param .= "_o";
			}
		}


		$file="detail_{$map_x}_{$map_y}_{$row['image_w']}_{$row['image_h']}_{$scale}_{$row['type_or_user']}$param.$extension";

		return $dir.$file;
	}
	
	/**
	* Invalidates all cached maps - recommended above expireAll as the recreation will be handled by the deamon
	* @access public
	*/
	function invalidateAll()
	{
		$db=&$this->_getDB();
		$sql="update mapcache set age=age+1";
		$db->Execute($sql);
	}
	
	/**
	* delete and Invalidates all cached maps - use this to clear the whole cache, but maps will slowlly be recreated by deamon
	* @access public
	*/
	function deleteAndInvalidateAll()
	{
		$dir=$_SERVER['DOCUMENT_ROOT'].'/maps/detail';
				
		//todo *nix specific
		`rm -Rf $dir`;
		
		$db=&$this->_getDB();
		$sql="update mapcache set age=age+1";
		$db->Execute($sql);
	}
	
	/**
	* Expires all cached maps
	* Base maps (blue/green raster)  and label maps are not expired unless you pass true as
	* parameter(s).
	* @access public
	*/
	function expireAll($expire_basemaps=false, $expire_labels=false)
	{
		$dir=$_SERVER['DOCUMENT_ROOT'].'/maps/detail';
		
		//todo *nix specific
		`rm -Rf $dir`;
		
		//clear out the table as well!
		$db=&$this->_getDB();
		$sql="delete from mapcache";
		$db->Execute($sql);
		
		if ($expire_basemaps)
		{
			$dir=$_SERVER['DOCUMENT_ROOT'].'/maps/base';
			
			//todo *nix specific
			`rm -Rf $dir`;		
		}
		if ($expire_labels)
		{
			$dir=$_SERVER['DOCUMENT_ROOT'].'/maps/label';
			
			//todo *nix specific
			`rm -Rf $dir`;		
		}
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
