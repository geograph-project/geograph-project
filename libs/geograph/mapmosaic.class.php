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
	* width/height of mosaic
	*/
	var $mosaic_factor=0;
	
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
	* Constructor - if you don't initialise it further, you get a full map
	* @access public
	*/
	function GeographMapMosaic()
	{
		$this->setOrigin(-210,-50);
		$this->setMosaicSize(400,400);
		$this->setScale(0.3);
		$this->setMosaicFactor(3);
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
	* Set center of map in internal coordinates, returns true if valid
	* @access public
	*/
	function reCenter($x,$y)
	{
		return $this->setAlignedOrigin(intval($x - $this->image_w / $this->pixels_per_km/2),intval($y - $this->image_h / $this->pixels_per_km/2) );
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
		$this->pixels_per_km=floatval($pixels_per_km);
		return true;
	}

	/**
	* How many images across/down will the mosaic be?
	* @access public
	*/
	function setMosaicFactor($factor)
	{
		$this->mosaic_factor=intval($factor);
		return true;
	}

	/**
	* get the bounding box in pixels in terms of another mosaic
	* @access public
	*/
	function getBoundingBox($mosaic) {
		$R = $this->pixels_per_km / $mosaic->pixels_per_km;
	

		$bounds = new BoundingBox;
		$bounds->width = $mosaic->image_w * $R;
		$bounds->height = $mosaic->image_h * $R;
		
		$bounds->left = ($mosaic->map_x - $this->map_x) * $this->pixels_per_km;
		$bounds->top = ($mosaic->map_y - $this->map_y) * $this->pixels_per_km;
		
		$bounds->top =$this->image_h - $bounds->top - $bounds->height;
		
		return $bounds;
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
		$token->setValue("f",  $this->mosaic_factor);
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
				$token->hasValue("s") &&
				$token->hasValue("f");
			if ($ok)
			{
				$this->setOrigin($token->getValue("x"), $token->getValue("y"));
				$this->setMosaicSize($token->getValue("w"), $token->getValue("h"));
				$this->setScale($token->getValue("s"));
				$this->setMosaicFactor($token->getValue("f"));
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
		
		//top to bottom
		for ($j=0; $j<$this->mosaic_factor; $j++)
		{
			$images[$j]=array();
			
			//left to right
			for ($i=0; $i<$this->mosaic_factor; $i++)
			{
				$images[$j][$i]=new GeographMap;	
				
				//to calc the origin we need to know
				//how many internal units in each image
				$img_w_km=($this->image_w / $this->pixels_per_km) / $this->mosaic_factor;
				$img_h_km=($this->image_h / $this->pixels_per_km) / $this->mosaic_factor;
				
				$images[$j][$i]->enableCaching($this->caching);
				
				$images[$j][$i]->setOrigin(
					$this->map_x + $i*$img_w_km,
					$this->map_y + ($this->mosaic_factor-$j-1)*$img_h_km);
					
				$images[$j][$i]->setImageSize(
					$this->image_w/$this->mosaic_factor,
					$this->image_h/$this->mosaic_factor);
				
				$images[$j][$i]->setScale($this->pixels_per_km);
		
			}
		
		}
		
		return $images;
	}

	/**
	* get grid reference for pixel position on mosaic
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
				
		##But what to do when the square is not on land??
		if (!$reference_index) {
			$reference_index = "1";
		}
				
		$sql="select prefix,origin_x,origin_y from gridprefix ".
			"where $x_km between origin_x and (origin_x+width-1) and ".
			"$y_km between origin_y and (origin_y+height-1) and ".
			"reference_index=$reference_index";
		$prefix=$db->GetRow($sql);
		
		$n=$y_km-$prefix['origin_y'];
		$e=$x_km-$prefix['origin_x'];
		return sprintf('%s%02d%02d', $prefix['prefix'], $n, $e);
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
		if ($this->pixels_per_km >=1)
		{
			//start with same params
			$out->setScale($this->pixels_per_km);
			$out->setMosaicFactor($this->mosaic_factor);
			$out->setMosaicSize($this->image_w, $this->image_h);

			//pan half a map
			//figure out image size in km
			$mapw=$out->image_w/$out->pixels_per_km;
			$maph=$out->image_h/$out->pixels_per_km;

			//figure out how many pixels to pan by
			$panx=round($mapw/$out->mosaic_factor);
			$pany=round($maph/$out->mosaic_factor);

			$out->setAlignedOrigin(
				$this->map_x + $panx*$xdir,
				$this->map_y + $pany*$ydir);
		}
		return $out->getToken();
	}
	
	/**
	* get a url that will zoom us out one level of this mosaic
	* @access public
	*/
	function getZoomOutToken()
	{
		$out=new GeographMapMosaic;
			
		
		//if we're zoomed out 1 pixel per km, then we only need
		//zoom out to a default map, otherwise, we need to zoom
		//out keeping vaguely centred on current position
		if ($this->pixels_per_km > 1)
		{
			//figure out central point
			$centrex=$this->map_x + ($this->image_w / $this->pixels_per_km)/2;
			$centrey=$this->map_y + ($this->image_h / $this->pixels_per_km)/2;

		
			//decide scale factor
			if ($this->pixels_per_km == 4)
			{
				//zoom out to 1 pixel per km
				$scale=1;

			}
			elseif ($this->pixels_per_km == 40)
			{
				//zoom out to 4 pixel per km
				$scale=4;
			}
			else
			{
				//zoom out to 40 pixel per km
				$scale=40;
				
			}
			
			$out->setScale($scale);
			
			//stick with current mosaic factor
			$out->setMosaicFactor($this->mosaic_factor);
			
			//figure out what the perfect origin would be
			$mapw=$this->image_w/$scale;
			$maph=$this->image_h/$scale;
			
			$bestoriginx=$centrex - $mapw/2;
			$bestoriginy=$centrey - $maph/2;
			
			$out->setAlignedOrigin($bestoriginx, $bestoriginy);
			
		}
		
		return $out->getToken();
	}

	/**
	* get a url that will zoom us out one level from this gridsquare
	* @access public
	*/
	function getGridSquareToken($gridsquare) 
	{
		if (is_numeric($gridsquare)) {
			$id = $gridsquare;
			$gridsquare = new GridSquare;
			$gridsquare->loadFromId($id);
		}
		
		$out=new GeographMapMosaic;	
		
		//start with same params
		$out->setScale(40);
		$out->setMosaicFactor(2);

		$out->reCenter($gridsquare->x,$gridsquare->y);
	
		return $out->getToken();		
	}

	/**
	* Sets the origin, but aligns the origin on particular boundaries to
	* reduce the number of image tiles that get generated
	*/
	function setAlignedOrigin($bestoriginx, $bestoriginy)
	{
		//figure out image size in km
		$mapw=$this->image_w/$this->pixels_per_km;
		$maph=$this->image_h/$this->pixels_per_km;
		
		
		//figure out an alignment factor - here we align on tile
		//boundaries so that panning the image allows reuse of tiles
		$walign=$mapw/$this->mosaic_factor;
		$halign=$maph/$this->mosaic_factor;
		
		//dividing by 2 makes for more accurate clicking
		$walign=round($walign/2);
		$halign=round($halign/2);
		
		//range check the bestorigin - we've got some hard coded
		//values here
		$bestoriginx=max($bestoriginx, 0);
		$bestoriginx=min($bestoriginx, 860);
		$bestoriginy=max($bestoriginy, 0);
		$bestoriginy=min($bestoriginy, 1220);
		
		//find closest aligned origin
		$originx=round($bestoriginx/$walign)*$walign;
		$originy=round($bestoriginy/$halign)*$halign;

		$this->setOrigin($originx, $originy);
	}

	/**
	* Given index of a mosaic image, and a pixel position on that image handle a zoom
	* If the zoom level is 2, this needs to perform a redirect to the gridsquare page
	* otherwise, it reconfigures the instance for the zoomed in map
	* @access public
	*/
	function zoomIn($i, $j, $x, $y)
	{
		//we got the click coords x,y on mosaic i,j
		$imgw=$this->image_w / $this->mosaic_factor;
		$imgh=$this->image_h / $this->mosaic_factor;
		$x+=$i*$imgw;
		$y+=$j*$imgh;
		
		//$this->_trace("zoomIn mosaic factor {$this->mosaic_factor}");
		
		
		//$this->_trace(sprintf("zoomIn: mosaic size %0.2f x %0.2f", $imgw, $imgh));
		
		//remap origin from top left to bottom left
		$y=$this->image_h-$y;
		
		//$this->_trace(sprintf("zoomIn: pixel click %0.2f x %0.2f", $x, $y));
		
		//lets figure out internal coords
		$clickx=floor($this->map_x + $x/$this->pixels_per_km);
		$clicky=floor($this->map_y + $y/$this->pixels_per_km);
		
		//$this->_trace(sprintf("zoomIn: map click %0.2f x %0.2f", $clickx, $clicky));
		
		//what's our zoom level going to be?
		if ($this->pixels_per_km < 1)
		{
			$scale=1;
			$mosaic=2;
			
		}
		elseif ($this->pixels_per_km == 1)
		{
			$scale=4;
			$mosaic=2;
		}
		elseif ($this->pixels_per_km == 4)
		{
			$scale=40;
			$mosaic=2;
		}
		#elseif ($this->pixels_per_km == 40)
		#{
		#	$scale=100;
		#	$mosaic=2;
		#}
		else
		{
			
			//we're going to zoom into a grid square
			$square=new GridSquare;
			if ($square->loadFromPosition($clickx, $clicky))
			{
				
				
				$images=$square->getImages();
				
				//if the image count is 1, we'll go straight to the image
				if (count($images)==1)
				{
					$url="http://".$_SERVER['HTTP_HOST'].'/view.php?id='.
						$images[0]->gridimage_id;
				}
				else
				{
					//lets go to the grid reference
					$url="http://".$_SERVER['HTTP_HOST'].'/gridref/'.$square->grid_reference;
				}
				
				header("Location:$url");
				exit;
			}
			else
			{
				//stay where we are
				$scale=100;
				$mosaic=2;
			}
			
		}

		
		//size of new map in km
		$mapw=$this->image_w/$scale;
		$maph=$this->image_h/$scale;
			
		//here's the perfect origin
		$bestoriginx=$clickx-$mapw/2;
		$bestoriginy=$clicky-$maph/2;

		$this->setScale($scale);
		$this->setMosaicFactor($mosaic);
		$this->setAlignedOrigin($bestoriginx, $bestoriginy);
	}

	/**
	* Given a coordinate, this ensures that any cached map images are expired
	* This should really be static
	* @access public
	*/
	function expirePosition($x,$y)
	{
	
	}
	
	/**
	* Expires all cached maps
	* Base maps (blue/green raster) are not expired unless you pass true as the 
	* first parameter
	* @access public
	*/
	function expireAll($expire_basemaps=false)
	{
		$dir=$_SERVER['DOCUMENT_ROOT'].'/maps/detail';
		`rm -Rf $dir`;
		
		if ($expire_basemaps)
		{
			$dir=$_SERVER['DOCUMENT_ROOT'].'/maps/base';
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
