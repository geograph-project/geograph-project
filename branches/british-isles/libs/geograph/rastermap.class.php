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
	* the version of mapping to display
	*/
	var $epoch = 'latest';

	/**
	* is this class in use (ie is a valid service specified)
	*/
	var $enabled = false;

	var $caching = true;

	var $folders = array(
			'OS50k-source'=>'pngs-1k-200/',
			'OS50k'=>'pngs-2k-250/',
			'OS50k-mapper'=>'pngs-2k-125/',
			'OS50k-mapper2'=>'pngs-4k-250/',
			'OS50k-mapper3'=>'pngs-2k-250b/',
			'OS50k-small'=>'pngs-1k-125/',
			'OS250k-m10k'=>'pngs-10k-250/',
			'OS250k-m40k'=>'pngs-40k-250/'
		);
	var $tilewidth=array(
			'OS50k-source'=>200,
			'OS50k'=>250,
			'OS50k-mapper'=>125,
			'OS50k-mapper2'=>250,
			'OS50k-mapper3'=>250,
			'OS50k-small'=>125,
			'NPE'=>250,
			'Google'=>250,
			'OSOS'=>350,
			'OSM-Static-Dev'=>250,
			'OS250k-m10k'=>250,
			'OS250k-m40k'=>250
		);
	var $divisor = array(
			'OS50k-source'=>1000,
			'OS50k'=>1000,
			'OS50k-mapper'=>1000,
			'OS50k-mapper2'=>1000,
			'OS50k-mapper3'=>1000,
			'OS50k-small'=>100,
			'OS250k-m10k'=>10000,
			'OS250k-m40k'=>10000
		);

	/**
	* setup the values
	*/
	function RasterMap(&$square,$issubmit = false, $useExact = true,$includeSecondService = false,$epoch = 'latest')
	{
		global $CONF;
		$this->enabled = false;
		if (!empty($square) && isset($square->grid_reference)) {
			$this->square =& $square;

			$this->exactPosition = $useExact && !empty($square->natspecified);

			//just in case we passed an exact location
			$this->nateastings = $square->getNatEastings();
			$this->natnorthings = $square->getNatNorthings();
			$this->natgrlen = $square->natgrlen;
			$this->reference_index = $square->reference_index;

			$this->issubmit = $issubmit;
			$services = explode(',',$CONF['raster_service']);

			if ($square->reference_index == 1) {
				if ($this->issubmit === true && in_array('OSOS',$services) && $square->x > 0) {
					$this->enabled = true;
					$this->service = 'OSOS';

				} elseif (in_array('OS50k',$services)) {
					$this->enabled = true;
					$this->service = 'OS50k';

					if (($this->issubmit === true || $includeSecondService) && in_array('NPE',$services)) {
						$this->service2 = 'NPE';
					}
				} elseif($this->issubmit && in_array('NPE',$services)) {
					$this->enabled = true;
					$this->service = 'NPE';
				}
			} elseif($CONF['template'] == 'archive') {
				$this->service = 'OSM-Static-Dev';
			} elseif(($this->exactPosition || in_array('Grid',$services)) && in_array('Google',$services)) {
				//$this->enabled = true;
				$this->service = 'Google';
				$this->inline = 1; //no need for none inline anymore...
			}
			if (isset($this->tilewidth[$this->service])) {
				$this->width = $this->tilewidth[$this->service];
			}
			if (!empty($epoch) && $epoch != 'latest' && preg_match('/^[\w]+$/',$epoch) ) {
				$this->epoch = $epoch;
			}
		}
	}

	function setService($service) {
		global $CONF;
		$services = explode(',',$CONF['raster_service']);
		if ($this->reference_index == 1 && $service == 'OS50k' && in_array('OS50k',$services)) {
			$this->service = 'OS50k';

			if ($this->issubmit === true && in_array('NPE',$services)) {
				$this->service2 = 'NPE';
			}
		} elseif($this->reference_index == 1 && $service == 'OSOS' && in_array('OSOS',$services)) {
			$this->service = 'OSOS';
		} elseif($service == 'Google' && in_array('Google',$services)) {
			$this->service = 'Google';
			$this->inline = 1; //no need for none inline anymore...
			if ($this->issubmit) {
				$this->tilewidth[$this->service] = 350;
			}
		}
		if (isset($this->tilewidth[$this->service])) {
			$this->width = $this->tilewidth[$this->service];
		}
	}

	function addLatLong($lat,$long) {
		if ($this->service == 'Google' || $this->service == 'OSM-Static-Dev') {
			$this->enabled = true;
		}
		$this->lat = floatval($lat);
		$this->long = floatval($long);
	}

	function addViewpoint($viewpoint_eastings,$viewpoint_northings,$viewpoint_grlen,$view_direction = -1) {
		$this->viewpoint_eastings = $viewpoint_eastings;
		$this->viewpoint_northings = $viewpoint_northings;
		$this->viewpoint_grlen = $viewpoint_grlen;
		$this->view_direction = $view_direction;
	}
	function addViewDirection($view_direction = -1) {
		$this->view_direction = $view_direction;
	}

	function getImageTag($gridref = '')
	{
		global $CONF;
		$east = floor($this->nateastings/1000) * 1000;
		$nort = floor($this->natnorthings/1000) * 1000;

		$width = $this->width;

		if ($this->service == 'OSM-Static-Dev') {
			//params mirror http://old-dev.openstreetmap.org/~ojw/StaticMap/?mode=API&
			//we use a proxy url primarlly because old-dev disallows robots.
			$mapurl = "{$CONF['TILE_HOST']}/tile-static.php?source=OSM-cycle&lat={$this->lat}&lon={$this->long}&z=13&w={$width}&h={$width}";

			require_once('geograph/conversions.class.php');
			$conv = new Conversions;

			$e = floor($this->nateastings/1000) * 1000;
			$n = floor($this->natnorthings/1000) * 1000;

			$mapurl .= $this->getStaticLineParam($conv,0,$e-1000,$n,$e+2000,$n);
			$mapurl .= $this->getStaticLineParam($conv,1,$e-1000,$n+1000,$e+2000,$n+1000);
			$mapurl .= $this->getStaticLineParam($conv,2,$e,$n-1000,$e,$n+2000);
			$mapurl .= $this->getStaticLineParam($conv,3,$e+1000,$n-1000,$e+1000,$n+2000);

			if ($this->exactPosition) {
				$mapurl .= "&mlat0={$this->lat}&mlon0={$this->long}&mico0=27710";
			}

			if (!empty($this->viewpoint_northings)) {
				$different_square_true = (intval($this->nateastings/1000) != intval($this->viewpoint_eastings/1000)
					|| intval($this->natnorthings/1000) != intval($this->viewpoint_northings/1000));

				$show_viewpoint = (intval($this->viewpoint_grlen) > 4) || ($different_square_true && ($this->viewpoint_grlen == '4'));

				if ($show_viewpoint) {
					$ve = $this->viewpoint_eastings;	$vn = $this->viewpoint_northings;
					if (false) { //this isn't done by gridsquare_to_wgs84 - so doesnt make sence to do it here...
						if ($this->viewpoint_grlen == '4') {
							$ve +=500; $vn += 500;
						}
						if ($this->viewpoint_grlen == '6') {
							$ve +=50; $vn += 50;
						}
					}
					list($lat,$long) = $conv->national_to_wgs84($ve,$vn,$this->reference_index,true,true);

					$mapurl .= "&mlat1=$lat&mlon1=$long&mico1=8513";
				}
			}

			$title = "Map data (c) openstreetmap.org  CC-BY-SA 2.0";

			return "<div style=\"top:0px;left:0px;width:{$width}px;height:{$width}px\"><img name=\"tile\" src=\"$mapurl\" style=\"width:{$width}px;height:{$width}px\" border=\"1\" alt=\"$title\" nopin=\"true\"/></div>";

		} elseif ($this->service == 'OSOS') {
			if ($this->nateastings < 0) {
				return "Unable to display a map for this location";
			}
			$s = ($this->exactPosition || !$this->issubmit)?'':"Drag the circles from the green box!<br/>";
			return "$s<div id=\"map\" style=\"width:{$width}px; height:{$width}px\"></div>";
		} elseif ($this->service == 'Google') {
			if (!empty($this->inline) || !empty($this->issubmit)) {
				return "<div id=\"map\" style=\"width:{$width}px; height:{$width}px\">Loading map... (JavaScript required)</div>";
			} else {
				$token=new Token;

				foreach ($this as $key => $value) {
					if (is_scalar($value)) {
						$token->setValue($key, $value);
					}
				}
				$token = $token->getToken();

				return "<iframe src=\"/map_frame.php?t=$token\" id=\"map\" width=\"{$width}\" height=\"{$width}\" scrolling=\"no\">Loading map... (JavaScript required)</iframe>";
			}
		} elseif ($this->service == 'OS50k-small') {
			static $idcounter = 1;

			if ($this->natgrlen == 4) {
				$this->nateastings = $east + 500;
				$this->natnorthings = $nort + 500;
			}

			$mapurl = "{$CONF['TILE_HOST']}/tile.php?r=".$this->getToken();

			$gr= str_replace(' ','+',!empty($this->square->grid_reference_full)?$this->square->grid_reference_full:$this->square->grid_reference);

			$title = "1:50,000 Modern Day Landranger(TM) Map &copy; Crown Copyright";

			$this->displayMarker1 = $this->exactPosition;

			if ($this->displayMarker1 && !($this->natgrlen > 6) ) {
				//nice central marker

				$padding = intval(($width-29)/2);
				$str .= "<a href=\"/gridref/$gr\" title=\"$title\" onmouseover=\"document.getElementById('marker$idcounter').src='{$CONF['STATIC_HOST']}/img/blank.gif'\" onmouseout=\"document.getElementById('marker$idcounter').src='{$CONF['STATIC_HOST']}/img/icons/circle.png'\"><img src=\"{$CONF['STATIC_HOST']}/img/icons/circle.png\" style=\"padding:{$padding}px;width:29px;height:29px;background-image:url($mapurl);\" border=\"1\" alt=\"$title\" galleryimg=\"no\" id=\"marker$idcounter\" nopin=\"true\"/></a>";

			} elseif ($this->displayMarker1) {
				//need to manipualte the marker position

				$top=$width/2;
				$left=$width/2;

				$widthby20 = ($width/20); //remove the automatic 50m centering
				$top += $widthby20;
				$left -= $widthby20;

				$widthby10 = ($width/10); //assubuing 1km width
				#100m = 1 unit
				$left += intval($this->nateastings%100 /100 * $widthby10);
				$top -= intval($this->natnorthings%100 /100 * $widthby10);

				//top,left contain center point
				$widthby2 = ($width/2);

				$movedown = intval($top - $widthby2);
				$movetoright = intval($left - $widthby2);

				$padding = intval(($width-29)/2);

				$ptop = $padding + $movedown;
				$pbottom = $padding - $movedown;

				$pleft = $padding + $movetoright;
				$pright = $padding - $movetoright;


				$padding = "padding:{$ptop}px {$pright}px {$pbottom}px {$pleft}px";

				$str .= "<a href=\"/gridref/$gr\" title=\"$title\" onmouseover=\"document.getElementById('marker$idcounter').src='{$CONF['STATIC_HOST']}/img/blank.gif'\" onmouseout=\"document.getElementById('marker$idcounter').src='{$CONF['STATIC_HOST']}/img/icons/circle.png'\"><img src=\"{$CONF['STATIC_HOST']}/img/icons/circle.png\" style=\"{$padding};width:29px;height:29px;background-image:url($mapurl);\" border=\"0\" alt=\"$title\" galleryimg=\"no\" id=\"marker$idcounter\" nopin=\"true\"/></a>";

			} else {
				//no marker

				$str .= "<a href=\"/gridref/$gr\" title=\"$title\"><img src=\"{$CONF['STATIC_HOST']}/img/blank.gif\" style=\"width:{$width}px;height:{$width}px;background-image:url($mapurl);\" border=\"1\" alt=\"$title\" galleryimg=\"no\" nopin=\"true\" id=\"marker$idcounter\"/></a>";
			}

			$idcounter++;
			return $str;

		} elseif ($this->service == 'OS50k') {
			if (!empty($CONF['fetch_on_demand'])) {
				$mapurl = "http://{$CONF['fetch_on_demand']}/tile.php?r=".$this->getToken();
			} else {
				$mapurl = "{$CONF['TILE_HOST']}/tile.php?r=".$this->getToken();
			}
			#$this->mapurl = $mapurl;
			$title = "1:50,000 Modern Day Landranger(TM) Map &copy; Crown Copyright";
		}
		if ($this->service == 'NPE' || $this->service2 == 'NPE' ) {
			$e1 = $east - 500;
			$e2 = $e1 + 2000;

			$n1 = $nort - 500;
			$n2 = $n1 + 2000;

			$mapurl2 = "http://www.getmapping.com/iedirectimage/getmappingwms.aspx?LAYERS=npeoocmap&VERSION=1.1.0&UNITS=meters&SERVICE=WMS&REQUEST=GetMap&STYLES=&EXCEPTIONS=application%2Fvnd.ogc.se_inimage&FORMAT=image%2Fjpeg&SRS=EPSG%3A27700&BBOX=$e1,$n1,$e2,$n2&WIDTH=256&HEIGHT=256";

			$title2 = "1940s OS New Popular Edition Historical Map &copy; npemap.org.uk";

			if ($this->service == 'NPE') {
				$title = $title2;
				$mapurl = $mapurl2;
			}
		}


		if (isset($title)) {
			$extra = ($this->issubmit === true)?44:(($this->issubmit)?22:0);

	//container
			$str = "<div style=\"position:relative;height:".($width+$extra)."px;width:{$this->width}px;\" id=\"rastermap\">";

	//map image
			$str .= "<div style=\"top:0px;left:0px;width:{$width}px;height:{$width}px\"><img name=\"tile\" src=\"$mapurl\" style=\"width:{$width}px;height:{$width}px\" border=\"1\" alt=\"$title\" nopin=\"true\"/></div>";

	//drag prompt
			if ($this->issubmit)
				$str .= "<div style=\"position:absolute;top:".($width)."px;left:0px; font-size:0.8em;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <small style=\"color:#0018F8\">&lt;- Drag to mark subject position.</small></div>";
			if ($this->issubmit === true)
				$str .= "<div style=\"position:absolute;top:".($width+22)."px;left:0px; font-size:0.8em;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <small style=\"color:#002E73\">&lt;- Drag to mark camera position.</small></div>";

			$widthby2 = ($width/2);

	//calculate subject position
			if ($this->issubmit && !$this->exactPosition) {
			//ready to drag position
				$left = 13;
				$top = $width+10;
			} else {
				$e = $this->nateastings;	$n = $this->natnorthings;
				if ($this->natgrlen == '6' && $this->exactPosition) {
					$e +=50; $n += 50;
				}
				$left = ($width/4) + ( ($e - $east) * $widthby2 / 1000 );
				$top = $width - ( ($width/4) + ( ($n - $nort) * $widthby2 / 1000 ) );
			}

	//choose photographer icon
			$prefix = $this->issubmit?'viewc':'camicon';
			if (isset($this->view_direction) && strlen($this->view_direction) && $this->view_direction != -1)
				$iconfile = "$prefix-".intval($this->view_direction).".png";
			else
				$iconfile = "$prefix--1.png";

			$different_square_true = (intval($this->nateastings/1000) != intval($this->viewpoint_eastings/1000)
						|| intval($this->natnorthings/1000) != intval($this->viewpoint_northings/1000));

			$show_viewpoint = (intval($this->viewpoint_grlen) > 4) || ($different_square_true && ($this->viewpoint_grlen == '4'));

	//calculate photographer position
			if (!$this->issubmit && $show_viewpoint) {
				$e = $this->viewpoint_eastings;	$n = $this->viewpoint_northings;
				if ($this->viewpoint_grlen == '4') {
					$e +=500; $n += 500;
				}
				if ($this->viewpoint_grlen == '6') {
					$e +=50; $n += 50;
				}
				$vleft = ($width/4) + ( ($e - $east) * $widthby2 / 1000 );
				$vtop = $width - ( ($width/4) + ( ($n - $nort) * $widthby2 / 1000 ) );

				if ( ($vleft < -8) || ($vleft > ($width+8)) || ($vtop < -8) || ($vtop > ($width+8)) || ($different_square_true && $this->viewpoint_grlen == '4') ) {
		//if outside the map extents clamp to an edge
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
					$iconfile = "camera.png";
				}
			} else {
		//ready to drag position
				$vleft = 13;
				$vtop = $width+32;
			}

			$this->displayMarker1 = ($this->issubmit || $this->exactPosition)?1:0;
			$this->displayMarker2 = ($this->issubmit === true || ( $show_viewpoint && (($vleft != $left) || ($vtop != $top)) ) )?1:0;

			if ((!$this->displayMarker2 || $iconfile == "camera.png") && !$this->issubmit) {
				$prefix = 'subc';
				if (isset($this->view_direction) && strlen($this->view_direction) && $this->view_direction != -1)
					$subfile = "$prefix-".intval($this->view_direction).".png";
				else
					$subfile = "$prefix--1.png";
			} else {
				$subfile = 'circle.png';
			}

	//subject icon
			$str .= "<div style=\"position:absolute;top:".($top-14)."px;left:".($left-14)."px;".( $this->displayMarker1 ?'':'display:none')."\" id=\"marker1\"><img src=\"{$CONF['STATIC_HOST']}/img/icons/$subfile\" alt=\"+\" width=\"29\" height=\"29\" name=\"subicon\"/></div>";

	//photographer icon
			if ($this->issubmit) {
				$str .= "<div style=\"position:absolute;top:".($vtop-14)."px;left:".($vleft-14)."px;".( $this->displayMarker2 ?'':'display:none')."\" id=\"marker2\"><img src=\"{$CONF['STATIC_HOST']}/img/icons/$iconfile\" alt=\"+\" width=\"29\" height=\"29\" name=\"camicon\"/></div>";
			} else {
				$str .= "<div style=\"position:absolute;top:".($vtop-20)."px;left:".($vleft-9)."px;".( $this->displayMarker2 ?'':'display:none')."\" id=\"marker2\"><img src=\"{$CONF['STATIC_HOST']}/img/icons/$iconfile\" alt=\"+\" width=\"20\" height=\"31\" name=\"camicon\"/></div>";
			}

	//overlay (for dragging)
			$str .= "<div style=\"position:absolute;top:0px;left:0px;z-index:3\">";
			$imagestr = "<img src=\"{$CONF['STATIC_HOST']}/img/blank.gif\" class=\"mapmask\" style=\"width:{$width}px;height:".($width+$extra)."px\" border=\"1\" alt=\"$title\" title=\"$title\" name=\"map\" galleryimg=\"no\" nopin=\"true\"/>";
			if (!empty($gridref) && $this->nateastings > 0) {
				$this->clickable = true;
				$str .= smarty_function_getamap(array('text'=>$imagestr,'gridref'=>$gridref,'title'=>$title,'icon'=>'no'));
			} else {
				$str .= $imagestr;
			}
			$str .= "</div>";

			$str .= "</div>";

	//map switcher
			if ($this->service2) {
				return "$str
				<br/>
				<div class=\"interestBox\" style=\"font-size:0.8em\">Switch to <a href=\"javascript:switchTo(2);\"  id=\"mapSwitcherOS50k\">Historic Map</a><a href=\"javascript:switchTo(1);\" id=\"mapSwitcherNPE\" style=\"display:none\">Modern Map</a><sup style=color:red>fixed!</sup>.</div>
				<script type=\"text/javascript\">
				function switchTo(too) {
					showOS50k = (too == 1)?'':'none';
					showNPE = (too == 2)?'':'none';

					document.getElementById('mapSwitcherOS50k').style.display = showOS50k;
					document.getElementById('mapTitleOS50k').style.display = showOS50k;
					document.getElementById('mapFootNoteOS50k').style.display = showOS50k;

					document.getElementById('mapSwitcherNPE').style.display = showNPE;
					document.getElementById('mapTitleNPE').style.display = showNPE;
					document.getElementById('mapFootNoteNPE').style.display = showNPE;

					if (too == 1) {
						document.images['tile'].src = '$mapurl';
						document.images['map'].title = '$title';
					} else {
						document.images['tile'].src = '{$CONF['STATIC_HOST']}/img/blank.gif';
						document.images['tile'].src = '$mapurl2';
						document.images['map'].title = '$title2';
					}
				}
				</script>";
			} else {
				return $str;
			}
	//end
		}
	}

	function getFooterTag()
	{
		global $CONF;
		//defer the tag to the last minute, to help prevent the page pausing mid load
		if ((!empty($this->inline) || !empty($this->issubmit)) && $this->service == 'Google') {
			///key={$CONF['google_maps_api_key']}&amp;
			return "<script src=\"//maps.googleapis.com/maps/api/js?sensor=false\" type=\"text/javascript\"></script>";
		} elseif ($this->service == 'OSOS') {
			if (strpos($CONF['raster_service'],'OSOSPro') !== FALSE) {
				return "<script src=\"https://osopenspacepro.ordnancesurvey.co.uk/osmapapi/openspace.js?key={$CONF['OS_OpenSpace_Licence']}\" type=\"text/javascript\"></script>";
			} else {
				return "<script src=\"http://openspace.ordnancesurvey.co.uk/osmapapi/openspace.js?key={$CONF['OS_OpenSpace_Licence']}\" type=\"text/javascript\"></script>";
			}
		}
	}

	function getStaticLineParam(&$conv,$l,$e1,$n1,$e2,$n2) {
		list($lat1,$long1) = $conv->national_to_wgs84($e1,$n1,$this->reference_index,true,true);
		list($lat2,$long2) = $conv->national_to_wgs84($e2,$n2,$this->reference_index,true,true);
		return "&d{$l}p0lat=$lat1&d{$l}p0lon=$long1&d{$l}p1lat=$lat2&d{$l}p1lon=$long2";
	}

	function getPolyLineBlock(&$conv,$e1,$n1,$e2,$n2,$op=1) {
		list($lat1,$long1) = $conv->national_to_wgs84($e1,$n1,$this->reference_index);
		list($lat2,$long2) = $conv->national_to_wgs84($e2,$n2,$this->reference_index);

		return "
			var polyline = new google.maps.Polyline({
			path: [
				new google.maps.LatLng($lat1,$long1),
				new google.maps.LatLng($lat2,$long2)
			],
			strokeColor: \"#0000FF\",
			strokeWeight: 1,
			strokeOpacity: $op,
			map: map});\n";
	}

	function getPolySquareBlock(&$conv,$e1,$n1,$e2,$n2) {
		list($lat1,$long1) = $conv->national_to_wgs84($e1,$n1,$this->reference_index);
		list($lat2,$long2) = $conv->national_to_wgs84($e2,$n2,$this->reference_index);
		return "
			pickupbox = new google.maps.Polygon({
			paths: [
				new google.maps.LatLng($lat1,$long1),
				new google.maps.LatLng($lat1,$long2),
				new google.maps.LatLng($lat2,$long2),
				new google.maps.LatLng($lat2,$long1),
				new google.maps.LatLng($lat1,$long1)
			],
			strokeColor: \"#0000FF\",
			strokeWeight: 1,
			strokeOpacity: 0.7,
			fillColor: \"#00FF00\",
			fillOpacity: 0.5,
			clickable: false,
			map: map});\n";
	}

	function getPolySquareBlockOS($e1,$n1,$e2,$n2) {
		return "var points = [];
			points.push(new OpenLayers.Geometry.Point($e1, $n1));
			points.push(new OpenLayers.Geometry.Point($e1, $n2));
			points.push(new OpenLayers.Geometry.Point($e2, $n2));
			points.push(new OpenLayers.Geometry.Point($e2, $n1));
			points.push(new OpenLayers.Geometry.Point($e1, $n1));

		pickupbox = new OpenLayers.Layer.Vector(\"Vector Layer\");
		var style_green = {strokeColor: \"#00FF00\", strokeOpacity: 1, strokeWidth: 6};

		var lineString = new OpenLayers.Geometry.LineString(points);
		var lineFeature = new OpenLayers.Feature.Vector(lineString, null, style_green);
		pickupbox.addFeatures([lineFeature]);
		//add it to the map
		map.addLayer(pickupbox);
		";
	}

	function getScriptTag()
	{
		global $CONF;
		if ($this->service == 'OSM-Static-Dev') {

		} elseif ($this->service == 'OSOS') {
			$block = $p1 = '';
			$e = floor($this->nateastings/1000) * 1000;
			$n = floor($this->natnorthings/1000) * 1000;

			if ($this->issubmit) {
				$p1 = "<script type=\"text/javascript\" src=\"".smarty_modifier_revision("/mapper/geotools2.js")."\"></script>";
			}

			if ($this->exactPosition) {
				if (!empty($this->natgrlen) && $this->natgrlen == '6') {
					$this->nateastings +=50;
					$this->natnorthings += 50;
				} else {
					$this->nateastings = intval($this->nateastings);
					$this->natnorthings = intval($this->natnorthings); // to avoid 011260 being seen as octal to javascript
				}
				$block.= "map.addOverlay(createMarker(new OpenSpace.MapPoint({$this->nateastings}, {$this->natnorthings})));";
			} elseif ($this->issubmit) {
				$block .= "map.addOverlay(createMarker(new OpenSpace.MapPoint($e-50, $n+150)));\n";
			}

			if (empty($lat) && $this->issubmit) {
				$block .= "map.addOverlay(createPMarker(new OpenSpace.MapPoint($e-50, $n+50)));\n";
			}

			if ($this->issubmit) {
				$block .= $this->getPolySquareBlockOS($e-100,$n+200,$e,$n);
			}

			$e+=375;
			$n+=450;

			return "
			$p1
			<script type=\"text/javascript\" src=\"".smarty_modifier_revision("/js/mappingOS.js")."\"></script>
			<script type=\"text/javascript\">

			var issubmit = {$this->issubmit}+0;
			var map;
			var static_host = '{$CONF['STATIC_HOST']}';

			function loadmap() {
				var options = {controls: [], products: [\"OV0\", \"OV1\", \"OV2\", \"MSR\", \"MS\", \"250KR\", \"250K\", \"50KR\", \"50K\", \"25KR\", \"25K\", \"VMLR\", \"VML\"]};
				//sometimes, VML layers are currupted, can uncomment this line to get roughtly equivlient layers
				//var options = {controls: [], products: [\"OV0\", \"OV1\", \"OV2\", \"MSR\", \"MS\", \"250KR\", \"250K\", \"50KR\", \"50K\", \"25KR\", \"25K\", \"CS09\", \"CS10\"]};
				map = new OpenSpace.Map('map',options);

				map.addControl(new OpenSpace.Control.PoweredBy());
				map.addControl(new OpenSpace.Control.CopyrightCollection());
				map.addControl(new OpenLayers.Control.Navigation()); // not keyboard
				map.addControl(new OpenSpace.Control.LargeMapControl());

				map.setCenter(new OpenSpace.MapPoint($e, $n), 9);

				$block

				if (typeof updateMapMarkers == 'function') {
					updateMapMarkers();
				}
			}

			AttachEvent(window,'load',loadmap,false);

			</script>
			";

		} elseif ($this->service == 'Google') {
			if (empty($this->inline) && empty($this->issubmit)) {
				//its now handled by the 'childmap'
				return;
			}
			require_once('geograph/conversions.class.php');
			$conv = new Conversions;

			$e = floor($this->nateastings/1000) * 1000;
			$n = floor($this->natnorthings/1000) * 1000;

			if (strpos($CONF['raster_service'],'Grid') !== FALSE) {

				$block = $this->getPolyLineBlock($conv,$e-1000,$n,$e+2000,$n);
				$block .= $this->getPolyLineBlock($conv,$e-1000,$n+1000,$e+2000,$n+1000);
				$block .= $this->getPolyLineBlock($conv,$e,$n-1000,$e,$n+2000);
				$block .= $this->getPolyLineBlock($conv,$e+1000,$n-1000,$e+1000,$n+2000);

				if (!empty($this->viewpoint_northings)) {
					$different_square_true = (intval($this->nateastings/1000) != intval($this->viewpoint_eastings/1000)
						|| intval($this->natnorthings/1000) != intval($this->viewpoint_northings/1000));

					$show_viewpoint = (intval($this->viewpoint_grlen) > 4) || ($different_square_true && ($this->viewpoint_grlen == '4'));

					if ($show_viewpoint) {
						$ve = $this->viewpoint_eastings;	$vn = $this->viewpoint_northings;
						if (false) { //this isn't done by gridsquare_to_wgs84 - so doesnt make sence to do it here...
							if ($this->viewpoint_grlen == '4') {
								$ve +=500; $vn += 500;
							}
							if ($this->viewpoint_grlen == '6') {
								$ve +=50; $vn += 50;
							}
						}
						list($lat,$long) = $conv->national_to_wgs84($ve,$vn,$this->reference_index);
						$block .= "
						var ppoint = new google.maps.LatLng({$lat},{$long});
						createPMarker(ppoint);\n";
					}
				}

				if (empty($lat) && $this->issubmit) {
					list($lat,$long) = $conv->national_to_wgs84($e-660,$n-520,$this->reference_index);
					$block .= "
						var ppoint = new google.maps.LatLng({$lat},{$long});
						createPMarker(ppoint);\n";
				}
			} else {
				$block = '';
			}
			if ($this->exactPosition) {
				$block.= "createMarker(point);";
			} elseif ($this->issubmit) {
				list($lat,$long) = $conv->national_to_wgs84($e-380,$n-520,$this->reference_index);
				$block .= "
					var point2 = new google.maps.LatLng({$lat},{$long});
					createMarker(point2);\n";
			}
			if ($this->issubmit) {
				$zoom=13;
			} else {
				$zoom=14;
			}
			if ($this->issubmit) {
				$block .= $this->getPolySquareBlock($conv,$e-800,$n-600,$e-200,$n-100);

				for ($i=100; $i<=900; $i+=100) {
					$block .= $this->getPolyLineBlock($conv,$e,   $n+$i,$e+1000,$n+$i,   0.25);
					$block .= $this->getPolyLineBlock($conv,$e+$i,$n,   $e+$i,  $n+1000, 0.25);
				}
			}
			if (empty($this->lat)) {
				list($this->lat,$this->long) = $conv->national_to_wgs84($this->nateastings,$this->natnorthings,$this->reference_index);
			}
			$p1 = $p2 = '';
			if ($this->issubmit) {
				$p1 = "<script type=\"text/javascript\" src=\"".smarty_modifier_revision("/mapper/geotools2.js")."\"></script>";

				if ($this->reference_index == 1) {
					//$p1 .= "<script type=\"text/javascript\" src=\"http://nls.tileserver.com/api.js\"></script>";
					$p1 .= "<script type=\"text/javascript\" src=\"".smarty_modifier_revision("/js/nls.tileserver.com-api.js")."\"></script>";

					$block .= " setupNLSTiles(map); \n";
				}

				$block .= '
				var panorama = map.getStreetView();
				google.maps.event.addListener(panorama, "position_changed", function() {
					if (!panorama.getVisible())
						return false;

					marker2.setPosition(panorama.getPosition());
					google.maps.event.trigger(marker2,"drag");
				});
				//todo, listen for pov_changed, and set view direction (if no subject location?)
				';
			}
			return "
				<script type=\"text/javascript\" src=\"".smarty_modifier_revision("/js/mappingG3.js")."\"></script>
				$p1
				<script type=\"text/javascript\">
				//<![CDATA[
					var issubmit = {$this->issubmit}+0;
					var ri = {$this->reference_index};
					var map = null;

					function loadmap() {
						var point = new google.maps.LatLng({$this->lat},{$this->long});
						var newtype = readCookie('GMapType');
						var mapTypeId = google.maps.MapTypeId.TERRAIN;

						mapTypeId = firstLetterToType(newtype);

						map = new google.maps.Map(
							document.getElementById('map'), {
							center: point,
							zoom: 13,
							mapTypeId: mapTypeId,
							streetViewControl: issubmit?true:false,
							mapTypeControlOptions: {
								mapTypeIds: mapTypeIds,
		                                                style: google.maps.MapTypeControlStyle.DROPDOWN_MENU
                		                        }
							$p2
						});

						$block

						setupOSMTiles(map);

						google.maps.event.addListener(map, \"maptypeid_changed\", function() {
							var t = map.getMapTypeId().substr(0,1);
							createCookie('GMapType',t,10);
						});

						if (typeof updateMapMarkers == 'function') {
							updateMapMarkers();
						}
						if (typeof Attribution == 'function') {
							Attribution(map,mapTypeId);
						}
					}
					AttachEvent(window,'load',loadmap,false);

					var static_host = '{$CONF['STATIC_HOST']}';
				//]]>
				</script>";
		} else {
				$east = (floor($this->nateastings/1000) * 1000) + 500;
				$nort = (floor($this->natnorthings/1000) * 1000) + 500;
			$str = "
			<script type=\"text/javascript\">
				var cene = {$east};
				var cenn = {$nort};
				var maph = {$this->width};
				var mapw = {$this->width};
				var mapb = 1;
				var static_host = '{$CONF['STATIC_HOST']}';
			</script>";

			if ($this->issubmit) {
				return "$str
			<script type=\"text/javascript\" src=\"".smarty_modifier_revision("/mapping.js")."\"></script>
			<script type=\"text/javascript\">
				document.images['map'].onmousemove = overlayMouseMove;
				document.images['map'].onmouseout = overlayMouseOut;
				document.images['map'].onmouseup = overlayMouseUp;
				document.images['map'].onmousedown = overlayMouseDown;

				function loadmap() {
					if (typeof updateMapMarkers == 'function') {
						updateMapMarkers();
					}
				}
				AttachEvent(window,'load',loadmap,false);
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
		if ($this->service == 'Google' || $this->service == 'OSM-Static-Dev') {
			return '';
		}
		return "<span id=\"mapTitleOS50k\"".($this->service == 'OS50k'?'':' style="display:none"').">1:50,000 Modern Day Landranger&trade; Map</span>".
		"<span id=\"mapTitleNPE\"".($this->service == 'NPE'?'':' style="display:none"').">1940s OS New Popular Edition".(($this->issubmit)?"<span style=\"font-size:0.8em;color:red\"><br/><b>Please confirm positions on the modern map, as accuracy may be limited.</b></span>":'')."</span>";
	}

	function getFootNote()
	{
		global $CONF;
		if ($this->service == 'OSM-Static-Dev') {
			return 'Subject Location: <img src="http://data.geograph.org.uk/symbols/27710.png" width="12" height="12"/>, Camera Location: <img src="http://data.geograph.org.uk/symbols/8513.png" width="12" height="12"/><br/>
			&copy; OpenStreetMap and contributors, <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap" about="">CC-BY-SA</a>';
		} elseif ($this->service == 'OSOS') {
			if ($this->issubmit) {
				return '<br/>Centre the blue circle on the subject and mark the camera position with the black circle. <b style=\"color:red\">The circle centre marks the spot.</b> <a href="javascript:void(enlargeMap());">Enlarge Map</a>';
			} else
				return '';
		} elseif ($this->service == 'Google') {
			return '';
		} elseif ($this->issubmit) {
			return "<span id=\"mapFootNoteOS50k\"".(($this->service == 'OS50k' && $this->issubmit)?'':' style="display:none"')."><br/>Centre the blue circle on the subject and mark the camera position with the black circle. <b style=\"color:red\">The circle centre marks the spot.</b> The red arrow will then show view direction.</span>".
			"<span id=\"mapFootNoteNPE\"".($this->service == 'NPE'?'':' style="display:none"')."><br/>Historical Map provided by <a href=\"http://www.npemap.org.uk/tileLicence.html\">npemap.org.uk</a> and <a href=\"http://www.getmapping.com/\">getmapping.com</a></span>";
		} elseif ($this->service == 'OS50k' && $CONF['template']!='archive') {
			$str = '';
			if (empty($this->service2)) {
				$token=new Token;

				foreach ($this as $key => $value) {
					if (is_scalar($value)) {
						$token->setValue($key, $value);
					}
				}
				$token->setValue("service",'Google');
				$token = $token->getToken();

				$width = $this->width;
				$iframe = rawurlencode("<iframe src=\"/map_frame.php?t=$token\" id=\"map\" width=\"{$width}\" height=\"{$width}\" scrolling=\"no\">Loading map...</iframe>");

				$str = "<br><a href=\"#\" onclick=\"document.getElementById('rastermap').innerHTML = rawurldecode('$iframe'); if (document.getElementById('mapFootNoteOS50k')) { document.getElementById('mapFootNoteOS50k').style.display = 'none';} return false;\">Change to interactive Map &gt;</a>";
			}

			if (!empty($this->clickable)) {
				return "<span id=\"mapFootNoteOS50k\">TIP: Click the map for Large scale mapping$str</span><span id=\"mapFootNoteNPE\"></span>";
			} else {
				return "<span id=\"mapFootNoteOS50k\"".(($this->displayMarker1 || $this->displayMarker2)?'':' style="display:none"').">TIP: Hover over the icons to hide$str</span><span id=\"mapFootNoteNPE\"></span>";
			}
		} else {
			return '';
		}
	}

	function createTile($service,$path = null) {
		$ret = false;

		//no start split_timer becase getMapPath will have called it.

		if ($service == 'OS50k') {
			$ret = $this->combineTiles($this->square,$path);
		} elseif (preg_match('/OS50k-mapper\d?/',$service)) {
			$ret = $this->combineTilesMapper($this->square,$path);
		} elseif ($service == 'OS50k-small') {
			if ($sourcepath = $this->getMapPath('OS50k',true)) {
				$ret = $this->createSmallExtract($sourcepath,$path);
			}
		} elseif ($service == 'OS250k-m40k') {
			$ret = $this->combineTilesMapper($this->square,$path);
		}

		split_timer('rastermap','created',$path); //logs the wall time

		return $ret;
	}

	function getMapPath($service,$create = true) {
		$path = $this->getOSGBStorePath($service);

		split_timer('rastermap','foundpath',$path); //logs the wall time

		if (($this->caching && file_exists($path)) || ($create && $this->createTile($service,$path)) ) {
			return $path;
		} else {
			return false;
		}
	}

	//take 1km tiles and create a 2/4km tile
	function combineTilesMapper(&$gr,$path = false) {
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

		if($this->service == 'OS250k-m40k') {
			$service = 'OS250k-m10k';
		} else {
			$service = 'OS50k-source';
		}
		$div = $this->divisor[$service];

		$tilewidth = $this->tilewidth[$service];
		list($source,$dummy) = explode('-',$service);

		//this isn't STRICTLY needed as getOSGBStorePath does the same floor, but do so in case we do exact calculations
		$east = floor($this->nateastings/$div) * $div;
		$nort = floor($this->natnorthings/$div) * $div;

		preg_match('/-(\d)0?k-/',$this->folders[$this->service],$m);
		$numtiles = $m[1];
		$stepdist = ($m[1]-1)*$div;

		if (strlen($CONF['imagemagick_path'])) {
			$tilelist = array();
			$c = 0;
			$found = 0;
			foreach(range(	$nort+$stepdist ,
							$nort ,
							-1*$div ) as $n) {
				foreach(range(	$east ,
								$east+$stepdist ,
								$div ) as $e) {
					$newpath = $this->getOSGBStorePath($service,$e,$n);

					if (file_exists($newpath)) {
						$tilelist[] = $newpath;
						$found = 1;
					} else {
						$tilelist[] = $CONF['rastermap'][$source]['path'].$this->epoch.'/'."blank{$tilewidth}.png";
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
				$path = $this->getOSGBStorePath($service,$east,$nort,true);

			$cmd = sprintf('%s"%smontage" -geometry +0+0 %s -tile %dx%d png:- | "%sconvert" - -thumbnail %ldx%ld -colors 128 -depth 8 -type Palette png:%s &1>1 &2>1', 
				isset($_GET['nice'])?'nice ':'',
				$CONF['imagemagick_path'],
				implode(' ',$tilelist),
				$numtiles,$numtiles,
				$CONF['imagemagick_path'],
				$this->width, $this->width,
				$path);

			if (isset($_ENV["OS"]) && strpos($_ENV["OS"],'Windows') !== FALSE)
				$cmd = str_replace('/','\\',$cmd);

			print exec ($cmd);

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

	//take nine 1km tiles and create a 2km tile
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

		$service = 'OS50k-source';
		$tilewidth = $this->tilewidth[$service];
		list($source,$dummy) = explode('-',$service);

		$outputwidth = $this->tilewidth['OS50k'];

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
					$newpath = $this->getOSGBStorePath($service,$e,$n);

					if (file_exists($newpath)) {
						$tilelist[] = $newpath;
						$found = 1;
					} else {
						$tilelist[] = $CONF['rastermap'][$source]['path'].$this->epoch.'/'."blank{$tilewidth}.png";
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
				$path = $this->getOSGBStorePath('OS50k',$east,$nort,true);

			$cmd = sprintf('%s"%smontage" -geometry +0+0 %s -tile 3x3 png:- | "%sconvert" - -crop %ldx%ld+%ld+%ld +repage -thumbnail %ldx%ld -colors 128 -font "%s" -fill "#eeeeff" -draw "roundRectangle 6,230 160,243 3,3" -fill "#000066" -pointsize 10 -draw "text 10,240 \'(c) Crown Copyright %s\'" -colors 128 -depth 8 -type Palette png:%s', 
				isset($_GET['nice'])?'nice ':'',
				$CONF['imagemagick_path'],
				implode(' ',$tilelist),
				$CONF['imagemagick_path'],
				$tilewidth*2, $tilewidth*2,
				$tilewidth/2, $tilewidth/2,
				$outputwidth, $outputwidth,
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

	function createSmallExtract($input,$output) {
		global $CONF,$USER;

		$east = floor($this->nateastings/100)%10/10;
		$nort = floor($this->natnorthings/100)%10/10;

		$by20 = $this->width/20; //to center on the centisquare

		$cmd = sprintf('%s"%sconvert" png:%s -gravity SouthWest -crop %ldx%ld+%ld+%ld +repage -crop %ldx%ld +repage -thumbnail %ldx%ld +repage -colors 128 -font "%s" -fill "#eeeeff" -draw "roundRectangle 13,114 118,130 3,3" -fill "#000066" -pointsize 10 -draw "text 14,123 \'(c) OSGB %s\'" -colors 128 -depth 8 -type Palette png:%s', 
			isset($_GET['nice'])?'nice ':'',
			$CONF['imagemagick_path'],
			$input,
			$this->width, $this->width,
			($this->width*$east)+$by20, ($this->width*$nort)+$by20,
			$this->width, $this->width,
			$this->width, $this->width,
			$CONF['imagemagick_font'],
			$CONF['OS_licence'],
			$output);

		if (isset($_ENV["OS"]) && strpos($_ENV["OS"],'Windows') !== FALSE)
			$cmd = str_replace('/','\\',$cmd);

		exec ($cmd);
		if (!empty($_GET['debug']) && $USER->hasPerm('admin'))
			die("<pre>$cmd</pre>");

		if (file_exists($path)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	* returns an image with appropriate headers
	* @access public
	*/
	function returnImage()
	{
		split_timer('rastermap'); //starts the timer

		$mappath = $this->getMapPath($this->service);

		if (!$mappath || !file_exists($mappath)) {
			$expires=strftime("%a, %d %b %Y %H:%M:%S GMT", time()+3600*6);
			header("Expires: $expires");

			header("Location: /maps/errortile.png");
			exit;
		}

		#there is a suggestion that as we setting an expires, we can do without mod checks (by not sending etag/lastmod), and avoid needless 304's 
		#//Last-Modified: Sun, 20 Mar 2005 18:19:58 GMT
		#$t=filemtime($mappath);

		#//use the filename as a hash (md5'ed)
		#//can use if-last-mod as file is not unique per user
		#customCacheControl($t,$mappath,true);

		customExpiresHeader(15552000,true);

		header("Content-Type: image/png");

		$size=filesize($mappath);
		header("Content-Length: $size");

		split_timer('rastermap','sending',$mappath); //logs the wall time

		readfile($mappath);
	}

	/**
	* Return an opaque, url-safe token representing this map
	* @access public
	*/
	function getToken()
	{
		$token=new Token;
		$token->setValue("e", floor($this->nateastings /$this->divisor[$this->service]));
		$token->setValue("n", floor($this->natnorthings /$this->divisor[$this->service]));
		$token->setValue("s", $this->service);
		if ($this->epoch != 'latest') {
			$token->setValue("r", $this->epoch);
		}
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
				$token->hasValue("s");
			if ($ok)
			{
				$this->service = $token->getValue("s");
				$this->nateastings = $token->getValue("e") * $this->divisor[$this->service];
				$this->natnorthings = $token->getValue("n") * $this->divisor[$this->service];
				$this->width = $this->tilewidth[$this->service];

				if ($token->hasValue("r")) {
					$this->epoch = $token->getValue("r");
				}
			}
		}
		return $ok;
	}

	function getOSGBStorePath($service,$e = 0,$n = 0,$create = true) {
		global $CONF;

		$folder = $this->folders[$service];
		$div = $this->divisor[$service];
		$div2 = max(10000,$div*10);
		if ($e || $n) {
			$e2 = floor($e /$div2);
			$n2 = floor($n /$div2);
			$e3 = floor($e /$div);
			$n3 = floor($n /$div);
		} else {
			$e2 = floor($this->nateastings /$div2);
			$n2 = floor($this->natnorthings /$div2);
			$e3 = floor($this->nateastings /$div);
			$n3 = floor($this->natnorthings /$div);
		}

		list($source,$dummy) = explode('-',$service);

		$dir=$CONF['rastermap'][$source]['path'].$this->epoch.'/'.$folder;
		$dir2='/mnt/secondry/rastermaps-OS-50k-'.$this->epoch.'-'.str_replace('/','-',$folder);
		$dir.=$e2.'/';
		$dir2.=$e2.'-';
		if ($create && !is_dir($dir))
			mkdir($dir);

		$dir.=$n2.'/';
		$dir2.=$n2.'-';
		if ($create && !is_dir($dir))
			mkdir($dir);

		$file = $dir.$e3.'-'.$n3.'.png';
		$file2 = $dir2.$e3.'-'.$n3.'.png';

if ($_GET['debug'])
	print "'$file2'";

		if (!file_exists($file) && file_exists($file2)) {
			return $file2;
		} else {
			return $file;
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


