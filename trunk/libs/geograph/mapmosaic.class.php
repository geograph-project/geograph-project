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
require_once('geograph/geographmap.class.php');

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
	* Constructor - if you don't initialise it further, you get a full map
	* @access public
	*/
	function GeographMapMosaic()
	{
		$this->setOrigin(0,0);
		$this->setMosaicSize(400,400);
		$this->setScale(0.3);
		$this->setMosaicFactor(1);
	}
	
	
	/**
	* Set origin of map in internal coordinates, returns true if valid
	* @access public
	*/
	function setOrigin($x,$y)
	{
		$this->origin_x=intval($x);
		$this->origin_y=intval($y);
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
			$ok=$token->getValue("x") &&
				$token->getValue("y") &&
				$token->getValue("w") &&
				$token->getValue("h") &&
				$token->getValue("s") &&
				$token->getValue("f");
			if ($ok)
			{
				$this->setOrigin($token->getValue("x"), $token->getValue("y"));
				$this->setMosaicSize($token->getValue("w"), $token->getValue("h"));
				$this->setScale($token->getValue("s"));
				$this->setMosaicFactor($token->getValue("f"));
			}
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
		
		//left to right
		for ($i=0; $i<$this->mosaic_factor; $i++)
		{
			$images[$i]=array();
			
			//top to bottom
			for ($j=0; $j<$this->mosaic_factor; $j++)
			{
				$img=new GeographMap;	
				
				//to calc the origin we need to know
				//how many internal units in each image
				$img_w_km=($this->image_w / $this->pixels_per_km) / $this->mosaic_factor;
				$img_h_km=($this->image_h / $this->pixels_per_km) / $this->mosaic_factor;
				
				$img->setOrigin(
					$this->map_x + $i*$img_w_km,
					$this->map_y + ($this->mosaic_factor-$j-1)*$img_h_km);
					
				$img->setImageSize(
					$this->image_w/$this->mosaic_factor,
					$this->image_h/$this->mosaic_factor);
				
				$img->setScale($this->pixels_per_km);
		
				$images[$i][$j]=&$img;
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
		$db=&$this->_getDB();
		
		//invert the y coordinate
		$y=$this->image_h-$y;
		
		//convert pixel pos to internal coordinates
		$x_km=$this->origin_x + floor($x/$this->pixels_per_km);
		$y_km=$this->origin_y + floor($y/$this->pixels_per_km);
		
		//this could be done in one query, but it's a funky join for something so simple
		$reference_index=$db->GetOne("select reference_index from gridsquare where x=$x_km and y=$y_km");
				
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
	function getPanUrl($xdir,$ydir)
	{
	}
	
	/**
	* get a url that will zoom us out one level of this mosaic
	* @access public
	*/
	function getZoomOutUrl()
	{
	
	}

	/**
	* Given index of a mosaic image, and a pixel position on that image handle a zoom
	* If the zoom level is 2, this needs to perform a redirect to the gridsquare page
	* otherwise, it reconfigures the instance for the zoomed in map
	* @access public
	*/
	function zoomIn($i, $j, $x, $y)
	{
	
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
