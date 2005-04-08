<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005  Barry Hunter (geo@barryhunter.co.uk)
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
* Provides the RasterMap class
*
* @package Geograph
* @author Barry Hunter <geo@barryhunter.co.uk>
* @version $Revision$
*/


/**
* RasterMap
*
* 
* @package Geograph
*/
class RasterMap
{
	var $db=null;
	
	/**
	* national easting/northing (ie not internal)
	*/
	var $nateastings;
  	var $natnorthings;
	
	/**
	* the mapping service used to display maps
	*/
	var $service;
	
	/**
	* is this class in use (ie is a valid service specified)
	*/	
	var $enabled = false;
	
	/**
	* setup the values
	*/
	function RasterMap(&$square)
	{
		global $CONF;
		//just in case we passed an exact location
		$this->nateastings = $square->getNatEastings();
		$this->natnorthings = $square->getNatNorthings();
		$this->service = $CONF['raster_service'];
		if ($this->service == 'vob') 
			$this->enabled = ($square->reference_index == 1)?true:false;
	} 
	
	function getImageTag() 
	{
		$east = floor($this->nateastings/1000) * 1000;
		$nort = floor($this->natnorthings/1000) * 1000;
		switch ($this->service) {
		
			case 'vob': 
				$e1 = $east - 500;
				$e2 = $e1 + 2000;

				$n1 = $nort - 500;
				$n2 = $n1 + 2000;

				//Use of this URL is not permitted outside of geograph.co.uk
				$mapurl = "http://vision.edina.ac.uk/cgi-bin/wms-vision?version=1.1.0&request=getMap&layers=newpop%2Csmall_1920%2Cmed_1904&styles=&SRS=EPSG:27700&Format=image/png&width=300&height=300&bgcolor=cfd6e5&bbox=$e1,$n1,$e2,$n2&exception=application/vnd.ogc.se_inimage";
				
				if ($this->nateastings - $east == 500) {
					return "<img src=\"$mapurl\" width=\"300\" height=\"300\" border=\"1\" alt=\"Historical Map &copy; VisionOfBritain.org.uk\">";
				} else {
					return $east." == ".$this->nateastings;
				}
		}
	}
	
	function getTitle($gridref) 
	{
		switch ($this->service) {
		
			case 'vob': 
				return "Map from mid 20<sup>th</sup> century";
		}
	}
	function getFootNote() 
	{
		switch ($this->service) {

			case 'vob': 
				return "Historical Map provided by <a href=\"http://www.visionofbritain.org.uk/\" title=\"Vision of Britain\">VisionOfBritain.org.uk</a>";
		}
	}

	function _trace($msg)
	{
		echo "$msg<br/>";
		flush();
	}	
	function _err($msg)
	{
		echo "<p><b>Error:</b> $msg</p>";
		flush();
	}
	
}

?>