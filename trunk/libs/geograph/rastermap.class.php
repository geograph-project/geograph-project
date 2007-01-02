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
	function RasterMap(&$square,$issubmit = false, $useExact = true)
	{
		global $CONF;
		$this->enabled = false;
		if (!empty($square) && isset($square->grid_reference)) {
			$this->square &= $square;
			
			$this->exactPosition = $useExact && !empty($square->natspecified);
			
			//just in case we passed an exact location
			$this->nateastings = $square->getNatEastings();
			$this->natnorthings = $square->getNatNorthings();
			$this->reference_index = $square->reference_index;
			
			$this->issubmit = $issubmit;
			$services = explode(',',$CONF['raster_service']);
			$this->width = 250;

			if ($square->reference_index == 1) {
				if (in_array('OS50k',$services)) {
					$this->enabled = true;
					$this->service = 'OS50k';
					
					if ($this->issubmit && in_array('VoB',$services)) {
						$this->service2 = 'VoB';
					}
				} elseif($this->issubmit && in_array('VoB',$services)) {
					$this->enabled = true;
					$this->service = 'VoB';
				} 
			} elseif(($this->exactPosition || in_array('Grid',$services)) && in_array('Google',$services)) {
				//$this->enabled = true;
				$this->service = 'Google';
			} 
		}
	} 
	
	function addLatLong($lat,$long) {
		if ($this->service == 'Google') {
			$this->enabled = true;
		}
		$this->lat = floatval($lat);
		$this->long = floatval($long);
	}
	
	function addViewpoint($viewpoint_eastings,$viewpoint_northings) {
		$this->viewpoint_eastings = $viewpoint_eastings;
		$this->viewpoint_northings = $viewpoint_northings;
	}
	
	function getImageTag() 
	{
		$east = floor($this->nateastings/1000) * 1000;
		$nort = floor($this->natnorthings/1000) * 1000;
		
		$width = $this->width;
		
		if ($this->service == 'Google') {
			return "<div id=\"map\" style=\"width:{$this->width}px; height:{$this->width}px\">Loading map...</div>";
		} elseif ($this->service == 'OS50k') {
			#$mappath = $this->getOS50kMapPath();

			$mapurl = "/tile.php?r=".$this->getToken();

			$title = "1:50,000 Modern Day Landranger(TM) Map &copy; Crown Copyright";
		}
		if ($this->service == 'VoB' || $this->service2 == 'VoB' ) {
			$e1 = $east - 500;
			$e2 = $e1 + 2000;

			$n1 = $nort - 500;
			$n2 = $n1 + 2000;

			//Use of this URL is not permitted outside of geograph.org.uk
			$mapurl2 = "http://vision.edina.ac.uk/cgi-bin/wms-vision?version=1.1.0&request=getMap&layers=newpop%2Csmall_1920%2Cmed_1904&styles=&SRS=EPSG:27700&Format=image/png&width=$width&height=$width&bgcolor=cfd6e5&bbox=$e1,$n1,$e2,$n2&exception=application/vnd.ogc.se_inimage";

			$title2 = "1940s OS New Popular Edition Historical Map &copy; VisionOfBritain.org.uk";
			
			if ($this->service == 'VoB') {
				$title = $title2;
				$mapurl = $mapurl2;
			}
		}
		
		if (isset($title)) {
			$extra = ($this->issubmit)?22:0;
						
			$str = "<div style=\"position:relative;height:".($width+$extra)."px;width:{$this->width}px;\">";

			$str .= "<div style=\"top:0px;left:0px;width:{$width}px;height:{$width}px\"><img src=\"$mapurl\" style=\"width:{$width}px;height:{$width}px\" border=\"1\" name=\"tile\" alt=\"$title\"/></div>";

			if ($this->issubmit)
				$str .= "<div style=\"position:absolute;top:".($width)."px;left:0px; font-size:0.8em;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <small>&lt;- Drag to mark photographer position.</small></div>";
			
			$widthby2 = ($width/2);
			
			$e = $this->nateastings;	$n = $this->natnorthings;
			if ($e%100 == 0 && $n%100 == 0 && $this->exactPosition) {
				$e +=50; $n += 50;
			} 
			$left = ($width/4) + ( ($e - $east) * $widthby2 / 1000 );
			$top = $width - ( ($width/4) + ( ($n - $nort) * $widthby2 / 1000 ) );
				
			
			
			if (!empty($this->viewpoint_northings)) {
				$e = $this->viewpoint_eastings;	$n = $this->viewpoint_northings;
				if ($e%100 == 0 && $n%100 == 0) {
					$e +=50; $n += 50;
				} 
				$vleft = ($width/4) + ( ($e - $east) * $widthby2 / 1000 );
				$vtop = $width - ( ($width/4) + ( ($n - $nort) * $widthby2 / 1000 ) );
				
				if ( ($vleft < -8) || ($vleft > ($width+8)) || ($vtop < -8) || ($vtop > ($width+8)) ) {
					//interpolate between centerpoint and real position 
					
					if ( abs($left - $vleft) < abs($top - $vtop) ) {
						// top/bottom edge
						
						$realangle = atan2( $left - $vleft, $top - $vtop );
						
						$vtop = ($top < $vtop)?($width+16):-16;
					
						$vleft = ( tan($realangle)*($top - $vtop)*-1 ) + $left;
					} else {
						// left/right edge
						
						$realangle = atan2( $top - $vtop, $left - $vleft );
						
						$vleft = ($left < $vleft)?($width+16):-16;
						
						$vtop = ( tan($realangle)*($left - $vleft)*-1 ) + $top;
					}
				}
				
			} else {
				$vleft = 13;
				$vtop = $width+13;
			}
			$this->displayMarker2 = ($this->issubmit || (!empty($this->viewpoint_northings) && ($vleft != $left) && ($vtop != $top)))?1:0;
			$str .= "<div style=\"position:absolute;top:".($vtop-8)."px;left:".($vleft-8)."px;".( $this->displayMarker2 ?'':'display:none')."\" id=\"marker2\"><img src=\"/templates/basic/img/camera.gif\" alt=\"+\" width=\"16\" height=\"16\"/></div>";
			
			if ($this->issubmit) {
				$this->displayMarker1 = 1;
				$str .= "<div style=\"position:absolute;top:".($top-8)."px;left:".($left-8)."px;\" id=\"marker1\"><img src=\"/templates/basic/img/crosshairs.gif\" alt=\"+\" width=\"16\" height=\"16\"/></div>";
			} else {
				$this->displayMarker1 = ($this->exactPosition)?1:0;
				$str .= "<div style=\"position:absolute;top:".($top-14)."px;left:".($left-14)."px;".((($this->exactPosition)?'':'display:none'))."\" id=\"marker1\"><img src=\"/templates/basic/img/circle.png\" alt=\"+\" width=\"29\" height=\"29\"/></div>";
			}

			$str .= "<div style=\"position:absolute;top:0px;left:0px;\"><img src=\"/img/blank.gif\" style=\"width:{$width}px;height:".($width+$extra)."px\" border=\"1\" alt=\"$title\" title=\"$title\" name=\"map\" galleryimg=\"no\"/></div>";

			$str .= "</div>";
			
			if ($this->service2) {
				return "$str
				<br/>
				<div class=\"interestBox\" style=\"font-size:0.8em\">Switch to <a href=\"javascript:switchTo(2);\"  id=\"mapSwitcherOS50k\">Historic Map</a><a href=\"javascript:switchTo(1);\" id=\"mapSwitcherVoB\" style=\"display:none\">Modern Map</a>, <small>create <span id=\"fig6\"><a href=\"javascript:useGR(6);\">6</a>/<b>8</b></span><span id=\"fig8\" style=\"display:none\"><b>6</b>/<a href=\"javascript:useGR(8);\">8</a></span> figure grid references</small></div>
				<script type=\"text/javascript\">
				function switchTo(too) {
					showOS50k = (too == 1)?'':'none';
					showVoB = (too == 2)?'':'none';
					
					document.getElementById('mapSwitcherOS50k').style.display = showOS50k;
					document.getElementById('mapTitleOS50k').style.display = showOS50k;
					document.getElementById('mapFootNoteOS50k').style.display = showOS50k;
					
					document.getElementById('mapSwitcherVoB').style.display = showVoB;
					document.getElementById('mapTitleVoB').style.display = showVoB;
					document.getElementById('mapFootNoteVoB').style.display = showVoB;
					
					if (too == 1) {
						document.images['tile'].src = '$mapurl';
						document.images['map'].title = '$title';
					} else {
						document.images['tile'].src = '/img/blank.gif';
						document.images['tile'].src = '$mapurl2';
						document.images['map'].title = '$title2';
					}
				}
				
				function useGR(too) {
					if (too == 8) {
						void(grlen = 4);
						void(mult = 10);
						document.getElementById('fig6').style.display = '';
						document.getElementById('fig8').style.display = 'none';
					} else {
						void(grlen = 3);
						void(mult = 100);
						document.getElementById('fig6').style.display = 'none';
						document.getElementById('fig8').style.display = '';
					}
				}
				
				</script>";
			} else {
				return $str;
			}
		}
	}
	
	function getFooterTag()
	{
		global $CONF;
		//defer the tag to the last minute, to help prevent the page pausing mid load
		if ($this->service == 'Google') {
			return "<script src=\"http://maps.google.com/maps?file=api&amp;v=2&amp;key={$CONF['google_maps_api_key']}\" type=\"text/javascript\"></script>";
		}
	}

	function getPolyLineBlock(&$conv,$e1,$n1,$e2,$n2) {
		list($lat1,$long1) = $conv->national_to_wgs84($e1,$n1,$this->reference_index);
		list($lat2,$long2) = $conv->national_to_wgs84($e2,$n2,$this->reference_index);
		return "			var polyline = new GPolyline([
				new GLatLng($lat1,$long1),
				new GLatLng($lat2,$long2)
			], \"#0000FF\", 1);
			map.addOverlay(polyline);\n";
	}
	
	function getScriptTag()
	{
		global $CONF;
		if ($this->service == 'Google') {
			if (strpos($CONF['raster_service'],'Grid') !== FALSE) {
				$e = floor($this->nateastings/1000) * 1000;
				$n = floor($this->natnorthings/1000) * 1000;
				
				require_once('geograph/conversions.class.php');
				$conv = new Conversions;
			
				$block = $this->getPolyLineBlock($conv,$e-1000,$n,$e+2000,$n);
				$block .= $this->getPolyLineBlock($conv,$e-1000,$n+1000,$e+2000,$n+1000);
				$block .= $this->getPolyLineBlock($conv,$e,$n-1000,$e,$n+2000);
				$block .= $this->getPolyLineBlock($conv,$e+1000,$n-1000,$e+1000,$n+2000);
				
				if (!empty($this->viewpoint_northings)) {
					list($lat,$long) = $conv->national_to_wgs84($this->viewpoint_eastings,$this->viewpoint_northings,$this->reference_index);
					$block .= "
					var ppoint = new GLatLng({$lat},{$long});
					map.addOverlay(createPMarker(ppoint));\n";
				}
			} else {
				$block = '';
			}
			if ($this->exactPosition) {
				$block.= "map.addOverlay(createMarker(point));";
			}
			return "
				<script type=\"text/javascript\">
				//<![CDATA[
					function createMarker(point) {
						var marker = new GMarker(point, {draggable: true});
						GEvent.addListener(marker, \"dragend\", function() {
								marker.setPoint(point);
							});
						return marker;
					}
					
					function createPMarker(ppoint) {
						var picon = new GIcon();
						picon.image =\"/templates/basic/img/camicon.png\";
						picon.shadow = \"http://labs.google.com/ridefinder/images/mm_20_shadow.png\";
						picon.iconSize = new GSize(12, 20);
						picon.shadowSize = new GSize(22, 20);
						picon.iconAnchor = new GPoint(6, 20);
						var marker = new GMarker(ppoint,{draggable: true, icon:picon});
						GEvent.addListener(marker, \"dragend\", function() {
								marker.setPoint(ppoint);
							});
						return marker;
					}
					function loadmap() {
						if (GBrowserIsCompatible()) {
							var map = new GMap2(document.getElementById(\"map\"));
							map.addControl(new GSmallZoomControl());
							map.addControl(new GMapTypeControl(true));
							map.disableDragging();
							var point = new GLatLng({$this->lat},{$this->long});
							map.setCenter(point, 13);
							$block 
						}
					}
					window.onload = loadmap;
					window.onunload = GUnload;
				//]]>
	    	</script>";
		} else {
				$east = (floor($this->nateastings/1000) * 1000) + 500;
				$nort = (floor($this->natnorthings/1000) * 1000) + 500;
			$str = "
			<script type=\"text/javascript\" language=\"JavaScript\">
				var cene = {$east};
				var cenn = {$nort};
				var maph = {$this->width};
				var mapw = {$this->width};
				var mapb = 1;
			</script>
			<script type=\"text/javascript\" src=\"/mapping.js?v={$CONF['javascript_version']}\"></script>";

			if ($this->issubmit) {
				return "$str
			<script type=\"text/javascript\">
				document.images['map'].onmousemove = overlayMouseMove;
				document.images['map'].onmouseup = overlayMouseUp;
				document.images['map'].onmousedown = overlayMouseDown;
			</script>";
			} else {
				return "$str
			<script type=\"text/javascript\">
				var displayMarker1 = {$this->displayMarker1};
				var displayMarker2 = {$this->displayMarker2};
				document.images['map'].onmousemove = overlayHideMarkers;
			</script>";
			}
		}
	}

	function getTitle($gridref) 
	{
		return "<span id=\"mapTitleOS50k\"".($this->service == 'OS50k'?'':' style="display:none"').">1:50,000 Modern Day Landranger&trade; Map<span style=\"font-size:0.8em;color:red\"><br/>OS Maps are still in testing, please visit forum for more info.</span></span>".
		"<span id=\"mapTitleVoB\"".($this->service == 'VoB'?'':' style="display:none"').">1940s OS New Popular Edition".(($this->issubmit)?"<span style=\"font-size:0.8em;color:red\"><br/><b>Please confirm positions on the modern map, as accuracy may be limited.</b></span>":'')."</span>";
	}

	function getFootNote() 
	{
		return "<span id=\"mapFootNoteOS50k\"".($this->service == 'OS50k'?'':' style="display:none"')."><br/>OS Maps are still in testing, please visit forum for more info.</span>".
		"<span id=\"mapFootNoteVoB\"".($this->service == 'VoB'?'':' style="display:none"')."><br/>Historical Map provided by <a href=\"http://www.visionofbritain.org.uk/\" title=\"Vision of Britain\">VisionOfBritain.org.uk</a></span>";
	}

	function getOS50kMapPath($create = true) {
		$path = $this->getOSGBStorePath('pngs-2k-'.$this->width.'/',0,0,true);
		if (file_exists($path) || ($create && $this->combineTiles($this->square,$path)) ) {
			return $path;
		} else {
			return false;
		}
	}

	//take number of 1km tiles and create a 2km tile
	function combineTiles(&$gr,$path = false) {
		global $CONF,$USER;
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
						$tilelist[] = $CONF['os50kimgpath']."blank{$this->tilewidth}.png";
						if (!empty($_GET['debug']) && $USER->hasPerm('admin'))
							print "$newpath not found<br/>\n";
					}
					$c++;
				}
			}
			
			if (!$found) {
				if (!empty($_GET['debug']) && $USER->hasPerm('admin'))
					print "No content tiles found<br/>\n";
				return false;
			}
			
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
			if (!empty($_GET['debug']) && $USER->hasPerm('admin'))
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
		$token->setValue("e", floor($this->nateastings /1000));
		$token->setValue("n", floor($this->natnorthings /1000));
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