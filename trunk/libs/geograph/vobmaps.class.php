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
* Provides the VisionOfBritainMaps class
*
* @package Geograph
* @author Barry Hunter <geo@barryhunter.co.uk>
* @version $Revision$
*/


/**
* VisionOfBritainMaps
*
* 
* @package Geograph
*/
class VisionOfBritainMaps
{
	var $db=null;
	
	/**
	* national easting/northing (ie not internal)
	*/
	var $nateastings;
  	var $natnorthings;
	
	
	/**
	* setup the values
	*/
	function VisionOfBritainMaps($easting,$northing)
	{
		//just in case we passed an exact location
		$this->nateastings = intval($easting/1000) * 1000;
		$this->natnorthings = intval($northing/1000) * 1000;
	} 
	
	function getImageTag() 
	{
		$e1 = $this->nateastings - 500;
		$e2 = $e1 + 2000;
		
		$n1 = $this->natnorthings - 500;
		$n2 = $n1 + 2000;
				
		//Use of this URL is not permitted outside of geograph.co.uk
		$mapurl = "http://vision.edina.ac.uk/cgi-bin/wms-vision?version=1.1.0&request=getMap&layers=newpop%2Csmall_1920%2Cmed_1904&styles=&SRS=EPSG:27700&Format=image/png&width=300&height=300&bgcolor=cfd6e5&bbox=$e1,$n1,$e2,$n2&exception=application/vnd.ogc.se_inimage";
		
		return "<input type=\"image\" src=\"$mapurl\" width=\"300\" height=\"300\" border=0 alt=\"Map Image\" name=\"map\" onclick=\"return false;\" style=\"cursor:default\">";

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