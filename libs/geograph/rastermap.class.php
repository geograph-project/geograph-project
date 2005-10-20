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
				$width = 300;
				
				$e1 = $east - 500;
				$e2 = $e1 + 2000;

				$n1 = $nort - 500;
				$n2 = $n1 + 2000;

				//Use of this URL is not permitted outside of geograph.org.uk
				$mapurl = "http://vision.edina.ac.uk/cgi-bin/wms-vision?version=1.1.0&request=getMap&layers=newpop%2Csmall_1920%2Cmed_1904&styles=&SRS=EPSG:27700&Format=image/png&width=$width&height=$width&bgcolor=cfd6e5&bbox=$e1,$n1,$e2,$n2&exception=application/vnd.ogc.se_inimage";
				
				if (0 && $this->nateastings - $east == 500 && $this->natnorthings - $nort == 500) {
					return "<img src=\"$mapurl\" width=\"$width\" height=\"$width\" border=\"1\" alt=\"Historical Map &copy; VisionOfBritain.org.uk\">";
				} else {
					$left = ($width/4) + ( ($this->nateastings - $east) * ($width/2) / 1000 ) - 8;
					$top = $width - ( ($width/4) + ( ($this->natnorthings - $nort) * ($width/2) / 1000 ) ) - 8;
				
					$str = "<div style=\"position:relative;height:".($width+22)."\">";
					
					$str .= "<img src=\"$mapurl\" width=\"$width\" height=\"$width\" border=\"1\" alt=\"Historical Map &copy; VisionOfBritain.org.uk\">";
					
					$str .= "<div style=\"position:absolute;top:".($width)."px;left:0px;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <small>&lt;- Drag to mark photographer position.</small></div>";
					
					$str .= "<div style=\"position:absolute;top:".($width+5)."px;left:5px;\" id=\"marker2\"><img src=\"/templates/basic/img/camera.gif\" alt=\"+\" width=\"16\" height=\"16\"/></div>";
					
					$str .= "<div style=\"position:absolute;top:{$top}px;left:{$left}px;\" id=\"marker1\"><img src=\"/templates/basic/img/crosshairs.gif\" alt=\"+\" width=\"16\" height=\"16\"/></div>";
					
					$str .= "<div style=\"position:absolute;top:0px;left:0px;\"><img src=\"/img/blank.gif\" width=\"$width\" height=\"".($width+22)."\" border=\"1\" alt=\"OVERLAY Historical Map &copy; VisionOfBritain.org.uk\" name=\"map\" galleryimg=\"no\"></div>";
					
					return "$str</div>";
				//	return $east." == ".$this->nateastings;
				}
		}
	}
	
	function getScriptTag()
	{
		switch ($this->service) {
		
			case 'vob': 
				$east = (floor($this->nateastings/1000) * 1000) + 500;
				$nort = (floor($this->natnorthings/1000) * 1000) + 500;
				return "
		<script type=\"text/javascript\">
			var cene = {$east};
			var cenn = {$nort};
			var maph = 300;
			var mapw = 300;
			var mapb = 1;
			</script>
		<script type=\"text/javascript\" src=\"/mapping.js\"></script>
		<script type=\"text/javascript\">document.images['map'].onmousemove = overlayMouseMove;
		document.images['map'].onmouseup = overlayMouseUp;
		document.images['map'].onmousedown = overlayMouseDown;
		</script>";
		}
	}
	
	function getTitle($gridref) 
	{
		switch ($this->service) {
		
			case 'vob': 
				return "1940s OS New Popular Edition";
		}
	}
	function getFootNote() 
	{
		switch ($this->service) {

			case 'vob': 
				return "<br/>Historical Map provided by <a href=\"http://www.visionofbritain.org.uk/\" title=\"Vision of Britain\">VisionOfBritain.org.uk</a>";
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