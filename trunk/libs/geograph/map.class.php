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
	* Constructor
	*/
	function GeographMap()
	{
	
	}

	/**
	* Set origin of map in internal coordinates
	* @access public
	*/
	function setOrigin($x,$y)
	{

	}

	/**
	* Set size image
	* @access public
	*/
	function setImageSize($w,$h)
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
	* Return an opaque, url-safe token representing this image
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
	* get grid reference for pixel position on image
	* @access public
	*/
	function getGridRef($x, $y)
	{
	
	}

	/**
	* calc filename to image, whether it exists or not
	* @access public
	*/
	function getImageFilename()
	{
	
	}

	/**
	* if a cached image is available, this could return a direct url
	* otherwise it can return a url which will generate the required
	* image 
	* @access public
	*/
	function getImageUrl()
	{
	
	}
	
	/**
	* render the image to cached file if not already available
	* @access public
	*/
	function renderImage()
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
