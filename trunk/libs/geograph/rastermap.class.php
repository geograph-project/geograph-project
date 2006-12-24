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
	function RasterMap(&$square,$issubmit = false)
	{
		global $CONF;
		$this->enabled = false;
		if (!empty($square) && isset($square->grid_reference)) {
			$this->square &= $square;
			//just in case we passed an exact location
			$this->nateastings = $square->getNatEastings();
			$this->natnorthings = $square->getNatNorthings();
			$this->issubmit = $issubmit;
			$services = explode(',',$CONF['raster_service']);
			if ($square->reference_index == 1) {
				if (in_array('OS50k',$services) && $issubmit == false) {
					$this->service = 'OS50k';
					$this->width = 250;
				} elseif(in_array('vob',$services)) {
					$this->service = 'vob';
					$this->width = 300;
				} 
				$this->enabled = true;
			}
		}
	} 
	
	function getImageTag() 
	{
		$east = floor($this->nateastings/1000) * 1000;
		$nort = floor($this->natnorthings/1000) * 1000;
		
		$exactPosition = ($this->nateastings - $east != 500 || $this->natnorthings - $nort != 500);
		
		$width = $this->width;
		
		switch ($this->service) {
		
			case 'OS50k': 
				#$mappath = $this->getOS50kMapPath();
				
				$mapurl = "/tile.php?r=".$this->getToken();
				
				
				$extra = ($this->issubmit)?22:0;
				
				$str = "<div style=\"position:relative;height:".($width+$extra)."\">";

				$str .= "<div style=\"top:0px;left:0px;width:{$width}px;height:{$width}px\"><img src=\"$mapurl\" width=\"$width\" height=\"$width\" border=\"1\" alt=\"1:50,000 Modern Day Landranger(TM) Map &copy; Crown Copyright\"></div>";

				if ($this->issubmit)
					$str .= "<div style=\"position:absolute;top:".($width)."px;left:0px;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <small>&lt;- Drag to mark photographer position.</small></div>";

				$str .= "<div style=\"position:absolute;top:".($width+5)."px;left:5px;".((($this->issubmit)?'':'display:none'))."\" id=\"marker2\"><img src=\"/templates/basic/img/camera.gif\" alt=\"+\" width=\"16\" height=\"16\"/></div>";

				$left = ($width/4) + ( ($this->nateastings - $east) * ($width/2) / 1000 ) - 8;
				$top = $width - ( ($width/4) + ( ($this->natnorthings - $nort) * ($width/2) / 1000 ) ) - 8;
				$str .= "<div style=\"position:absolute;top:{$top}px;left:{$left}px;".((($this->issubmit || $exactPosition)?'':'display:none'))."\" id=\"marker1\"><img src=\"/templates/basic/img/crosshairs.gif\" alt=\"+\" width=\"16\" height=\"16\"/></div>";

				$str .= "<div style=\"position:absolute;top:0px;left:0px;\"><img src=\"/img/blank.gif\" width=\"$width\" height=\"".($width+$extra)."\" border=\"1\" alt=\"1:50,000 Modern Day Landranger(TM) Map &copy; Crown Copyright\" name=\"map\" galleryimg=\"no\"></div>";

				return "$str</div>";
				
				break;
			case 'vob': 
				$e1 = $east - 500;
				$e2 = $e1 + 2000;

				$n1 = $nort - 500;
				$n2 = $n1 + 2000;

				//Use of this URL is not permitted outside of geograph.org.uk
				$mapurl = "http://vision.edina.ac.uk/cgi-bin/wms-vision?version=1.1.0&request=getMap&layers=newpop%2Csmall_1920%2Cmed_1904&styles=&SRS=EPSG:27700&Format=image/png&width=$width&height=$width&bgcolor=cfd6e5&bbox=$e1,$n1,$e2,$n2&exception=application/vnd.ogc.se_inimage";
				
				if (0 && $exactPosition) {
					return "<img src=\"$mapurl\" width=\"$width\" height=\"$width\" border=\"1\" alt=\"Historical Map &copy; VisionOfBritain.org.uk\">";
				} else {
					$left = ($width/4) + ( ($this->nateastings - $east) * ($width/2) / 1000 ) - 8;
					$top = $width - ( ($width/4) + ( ($this->natnorthings - $nort) * ($width/2) / 1000 ) ) - 8;
				
					$str = "<div style=\"position:relative;height:".($width+22)."\">";
					
					$str .= "<div style=\"top:0px;left:0px;width:{$width}px;height:{$width}px\"><img src=\"$mapurl\" width=\"$width\" height=\"$width\" border=\"1\" alt=\"Historical Map &copy; VisionOfBritain.org.uk\"></div>";
					
					$str .= "<div style=\"position:absolute;top:".($width)."px;left:0px;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <small>&lt;- Drag to mark photographer position.</small></div>";
					
					$str .= "<div style=\"position:absolute;top:".($width+5)."px;left:5px;\" id=\"marker2\"><img src=\"/templates/basic/img/camera.gif\" alt=\"+\" width=\"16\" height=\"16\"/></div>";
					
					$str .= "<div style=\"position:absolute;top:{$top}px;left:{$left}px;\" id=\"marker1\"><img src=\"/templates/basic/img/crosshairs.gif\" alt=\"+\" width=\"16\" height=\"16\"/></div>";
					
					$str .= "<div style=\"position:absolute;top:0px;left:0px;\"><img src=\"/img/blank.gif\" width=\"$width\" height=\"".($width+22)."\" border=\"1\" alt=\"OVERLAY Historical Map &copy; VisionOfBritain.org.uk\" title=\"1940s OS New Popular Edition Historical Map &copy; VisionOfBritain.org.uk\" name=\"map\" galleryimg=\"no\"></div>";
					
					return "$str</div>";
				}
				break;
		}
	}
	
	function getScriptTag()
	{
		global $CONF;
		
		switch ($this->service) {
		
			case 'vob': 
				$east = (floor($this->nateastings/1000) * 1000) + 500;
				$nort = (floor($this->natnorthings/1000) * 1000) + 500;
				return "
		<script type=\"text/javascript\">
			var cene = {$east};
			var cenn = {$nort};
			var maph = {$this->width};
			var mapw = {$this->width};
			var mapb = 1;
			</script>
		<script type=\"text/javascript\" src=\"/mapping.js?v={$CONF['javascript_version']}\"></script>
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
				return "1940s OS New Popular Edition".(($this->issubmit)?"<span style=\"font-size:0.8em;color:red\"><br/><b>Please confirm positions on a modern map, as accuracy may be limited.</b></span>":'');
		}
	}
	function getFootNote() 
	{
		switch ($this->service) {

			case 'vob': 
				return "<br/>Historical Map provided by <a href=\"http://www.visionofbritain.org.uk/\" title=\"Vision of Britain\">VisionOfBritain.org.uk</a>";
		}
	}

	function getOS50kMapPath() {
		$path = $this->getOSGBStorePath('pngs-2k-'.$this->width.'/',0,0,true);
		if (file_exists($path) || $this->combineTiles($this->square,$path)) {
			return $path;
		} else {
			return false;
		}
	}

	//take number of 1km tiles and create a 2km tile
	function combineTiles(&$gr,$path = false) {
		global $CONF;
		if (is_string($gr)) {
			$square=new GridSquare;

			if (!$square->setByFullGridRef($gr)) {
				return false;
			}
		} else {//already a gridsquare object
			$square &= $gr;
		}

		$ll = $square->gridsquare;
		
		//$this->width = 250;
		$this->tilewidth = 200;

		//this isn't STRICTLY needed as getOSGBStorePath does the same floor, but do so in case we do exact calculations
		$east = floor($this->nateastings/1000) * 1000;
		$nort = floor($this->natnorthings/1000) * 1000;

		if (strlen($CONF['imagemagick_path'])) {
			$tilelist = array();
			$c = 0;
			$found = 0;
			foreach(range(	$nort+1000 ,
							$nort-1000 ,
							-1000 ) as $n) {
				foreach(range(	$east-1000 ,
								$east+1000 ,
								1000 ) as $e) {
					$newpath = $this->getOSGBStorePath('pngs-1k-'.$this->tilewidth.'/',$e,$n);
					
					if (file_exists($newpath)) {
						$tilelist[] = $newpath;
						$found = 1;
					} else {
						$tilelist[] = 'null';
					}
					$c++;
				}
			}
			
			if (!$found)
				return false;
			
			if (!$path) 
				$path = $this->getOSGBStorePath('pngs-2k-250/',$east,$nort,true);

			$cmd = sprintf('%s"%smontage" -geometry +0+0 %s -tile 3x3 png:- | "%sconvert" - -crop %ldx%ld+%ld+%ld +repage -thumbnail %ldx%ld -colors 128 -font "%s" -fill "#eeeeff" -draw "roundRectangle 6,230 155,243 3,3" -fill "#000066" -pointsize 10 -draw "text 10,240 \'© Crown Copyright %s\'" -colors 128 -depth 8 -type Palette png:%s', 
				isset($_GET['nice'])?'nice ':'',
				$CONF['imagemagick_path'],
				implode(' ',$tilelist),
				$CONF['imagemagick_path'],
				$this->tilewidth*2, $this->tilewidth*2, 
				$this->tilewidth/2, $this->tilewidth/2,
				$this->width, $this->width, 
				$CONF['imagemagick_font'],
				$CONF['OS_licence'],
				$path);

			if (isset($_ENV["OS"]) && strpos($_ENV["OS"],'Windows') !== FALSE) 
				$cmd = str_replace('/','\\',$cmd);

			exec ($cmd);
			print "<pre>$cmd</pre>";
			
			if (file_exists($path)) {
				return true;
			} else {
				return false;
			}
		} else {
			//generate resized image
			die("gd not implemented!");
		}
	}

	/**
	* returns an image with appropriate headers
	* @access public
	*/
	function returnImage()
	{
		$mappath = $this->getOS50kMapPath();

		if (!file_exists($mappath))
			$mappath=$_SERVER['DOCUMENT_ROOT']."/maps/errortile.png";

		header("Content-Type: image/png");
		
		$size=filesize($mappath);		
		header("Content-Size: $size");

		$expires=strftime("%a, %d %b %Y %H:%M:%S GMT", time()+604800);
		header("Expires: $expires");

		readfile($mappath);
	}
	
	/**
	* Return an opaque, url-safe token representing this map
	* @access public
	*/
	function getToken()
	{
		$token=new Token;
		$token->setValue("e", $this->nateastings /1000);
		$token->setValue("n", $this->natnorthings /1000);
		$token->setValue("w", $this->width);
		$token->setValue("s", $this->service);
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
			$ok=$token->hasValue("e") &&
				$token->hasValue("n") &&
				$token->hasValue("w") &&
				$token->hasValue("s");
			if ($ok)
			{
				$this->nateastings = $token->getValue("e") * 1000;
				$this->natnorthings = $token->getValue("n") * 1000;
				$this->width = $token->getValue("w");
				$this->service = $token->getValue("s");
			}
		}
		return $ok;
	}

	function getOSGBStorePath($folder = 'pngs-2k-250/',$e = 0,$n = 0,$create = false) {
		global $CONF;

		if ($e && $n) {
			$e2 = floor($e /10000);
			$n2 = floor($n /10000);
			$e3 = floor($e /1000);
			$n3 = floor($n /1000);
		} else {
			$e2 = floor($this->nateastings /10000);
			$n2 = floor($this->natnorthings /10000);
			$e3 = floor($this->nateastings /1000);
			$n3 = floor($this->natnorthings /1000);
		}

		$dir=$CONF['os50kimgpath'].$folder;
		
		$dir.=$e2.'/';
		if ($create && !is_dir($dir))
			mkdir($dir);

		$dir.=$n2.'/';
		if ($create && !is_dir($dir))
			mkdir($dir);

		return $dir.$e3.'-'.$n3.'.png';
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