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
	* Set origin of map in internal coordinates
	* @access public
	*/
	function setOrigin($x,$y)
	{

	}

	/**
	* Set size of mosaic image
	* @access public
	*/
	function setMosaicSize($w,$h)
	{

	}

	/**
	* Set desired scale in pixels per km
	* @access public
	*/
	function setScale($pixels_per_km)
	{

	}

	/**
	* How many images across/down will the mosaic be?
	* @access public
	*/
	function setMosaicFactor($factor)
	{

	}

	/**
	* Return an opaque, url-safe token representing this mosaic
	* @access public
	*/
	function getToken()
	{

	}

	/**
	* Initialise class from a token
	* @access public
	*/
	function setToken($token)
	{

	}


	/**
	* return 2d array of GeographMap objects for the mosaic
	* @access public
	*/
	function getImageArray()
	{
	
	}

	/**
	* get grid reference for pixel position on mosaic
	* @access public
	*/
	function getGridRef($x, $y)
	{
	
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
