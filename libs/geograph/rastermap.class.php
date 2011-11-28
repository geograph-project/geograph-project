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
			'VoB'=>250,
			'Google'=>250,
			'OLayers'=>250,
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
	function RasterMap(&$square,$issubmit = false, $useExact = true,$includeSecondService = false,$epoch = 'latest',$serviceid = -1,$iscmap=false)
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
			
			$this->issubmit = $issubmit||$iscmap;
			$this->iscmap = $iscmap;
			$this->serviceid = '';
			$this->maplink = true;
			$this->grid = false;
			$services = explode(',',$CONF['raster_service']);

			if ($serviceid == -1) {
				if ($square->reference_index == 1) {
					if (in_array('OS50k',$services)) {
						$this->enabled = true;
						$this->service = 'OS50k';
						
						if (($this->issubmit === true || $includeSecondService) && in_array('VoB',$services)) {
							$this->service2 = 'VoB';
						}
					} elseif($this->issubmit && in_array('VoB',$services)) {
						$this->enabled = true;
						$this->service = 'VoB';
					} 
				} elseif(in_array('Grid',$services) && in_array('OLayers',$services)) {
					#$this->enabled = true; ##FIXME
					$this->service = 'OLayers';
				} elseif(($this->exactPosition || in_array('Grid',$services)) && in_array('Google',$services)) {
					#$this->enabled = true; ##FIXME
					$this->service = 'Google';
				} 
				if (isset($this->tilewidth[$this->service])) {
					$this->width = $this->tilewidth[$this->service];
				}
				if (!empty($epoch) && $epoch != 'latest' && preg_match('/^[\w]+$/',$epoch) ) {
					$this->epoch = $epoch;
				}
			} elseif ($serviceid >= 0) { //FIXME $serviceid in CONF?
				foreach($CONF['mapservices'][$serviceid] as $name=>$value) // FIXME database?
				{
					if (!is_numeric($name))
						$this->$name=$value;
				}
				#$this->enabled = true;
				$this->serviceid = $serviceid;
				if ($square->reference_index == 3) {
					$this->zone = 32;
				} elseif ($square->reference_index == 4) {
					$this->zone = 33;
				} elseif ($square->reference_index == 5) {
					$this->zone = 31;
				}
				if ($this->service == 'WMS') {
					if ($this->servicegk === false) {
						$this->delmeri = 0;
					} else {
						$this->delmeri = (2 * $this->zone - $this->servicegk - 61) * 3;
					}
				}
				if ($this->service != 'Google' && $this->service != 'OLayers') {
					$this->enabled = true;
				}
				if (isset($this->tilewidth[$this->service])) {
					$this->width = $this->tilewidth[$this->service];
				}
				#$this->divisor = 1000;
			} elseif (0)/*($serviceid == 0)*/ {
					#$this->enabled = true; ##FIXME
					$this->service = 'Google';
			} elseif (0) { #FIXME
				if ($serviceid == 1) {
					$this->service = 'WMS';
					$this->servicegk = 3;
					$this->serviceurl='http://www.lv-bw.de/dv/service/getrds.asp?request=GetMap&layers=DVTK50K&format=PNG&width=%s&height=%s&srs=EPSG:31467&bbox=%s,%s,%s,%s&login=dv&pw=anonymous';
					$this->width = 300;
					$this->title = 'TK 1:50000 &copy; Landesvermessungsamt Baden-W&uuml;rttemberg';
					$this->footnote = 'TK 1:50000 &copy; Landesvermessungsamt Baden-W&uuml;rttemberg';
					$this->maplink = false;
					$this->grid = true;
				} elseif ($serviceid == 2) {
					$this->service = 'WMS';
					$this->servicegk = 3;
					$this->serviceurl='http://www.geodaten.bayern.de/ogc/getogc.cgi?REQUEST=GetMap&VERSION=1.1.1&LAYERS=TK50&SRS=EPSG:31467&WIDTH=%s&HEIGHT=%s&BBOX=%s,%s,%s,%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=';
					$this->width = 300;
					$this->title = 'TK 1:50000 &copy; Bayerische Vermessungsverwaltung';
					$this->footnote = 'TK 1:50000 &copy; Bayerische Vermessungsverwaltung';
					$this->maplink = false;
					$this->grid = true;
				} elseif ($serviceid == 3) {
					$this->service = 'WMS';
					$this->servicegk = 4;
					$this->serviceurl='http://www.geodaten.bayern.de/ogc/getogc.cgi?REQUEST=GetMap&VERSION=1.1.1&LAYERS=TK50&SRS=EPSG:31468&WIDTH=%s&HEIGHT=%s&BBOX=%s,%s,%s,%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=';
					$this->width = 300;
					$this->title = 'TK 1:50000 &copy; Bayerische Vermessungsverwaltung';
					$this->footnote = 'TK 1:50000 &copy; Bayerische Vermessungsverwaltung';
					$this->maplink = false;
					$this->grid = true;
				} elseif ($serviceid == 4) {
					$this->service = 'WMS';
					$this->servicegk = false;
					$this->serviceurl='http://www.geodaten.bayern.de/ogc/getogc.cgi?REQUEST=GetMap&VERSION=1.1.1&LAYERS=TK50&SRS=EPSG:25832&WIDTH=%s&HEIGHT=%s&BBOX=%s,%s,%s,%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=';
					$this->width = 300;
					$this->title = 'TK 1:50000 &copy; Bayerische Vermessungsverwaltung';
					$this->footnote = 'TK 1:50000 &copy; Bayerische Vermessungsverwaltung';
					$this->maplink = false;
					$this->grid = true;
				} elseif ($serviceid == 23) {
					$this->service = 'WMS';
					$this->servicegk = 4;
					$this->serviceurl='http://localhost/img/testmap2.png?ignore=%s,%s,%s,%s,%s,%s';
					$this->width = 300;
					$this->title = 'TK 1:50000 &copy; Bayerische Vermessungsverwaltung';
					$this->footnote = 'TK 1:50000 &copy; Bayerische Vermessungsverwaltung';
					$this->maplink = false;
					$this->grid = true;
					# TPT2870 photo/29
				} elseif ($serviceid == 21) {
					$this->service = 'WMS';
					$this->servicegk = 3;
					$this->serviceurl='http://localhost/img/testmap.png?ignore=%s,%s,%s,%s,%s,%s';
					$this->width = 300;
					$this->title = 'TK 1:50000 &copy; Landesvermessungsamt Baden-W&uuml;rttemberg';
					$this->footnote = 'TK 1:50000 &copy; Landesvermessungsamt Baden-W&uuml;rttemberg';
					$this->maplink = false;
					$this->grid = true;
					# UNV1930 photo/2
				}

				$this->enabled = true;
				$this->serviceid = $serviceid;
				if ($square->reference_index == 3) {
					$this->zone = 32;
				} elseif ($square->reference_index == 4) {
					$this->zone = 33;
				} elseif ($square->reference_index == 5) {
					$this->zone = 31;
				}
				if ($this->servicegk === false) {
					$this->delmeri = 0;
				} else {
					$this->delmeri = (2 * $this->zone - $this->servicegk - 61) * 3;
				}
				#$this->divisor = 1000;
			}
		}
	} 
	
	function addLatLong($lat,$long) {
		if ($this->service == 'Google' || $this->service == 'OLayers') {
			$this->enabled = true;
		}
		$this->lat = floatval($lat);
		$this->long = floatval($long);
	}
	
	function addViewpoint($viewpoint_ri, $viewpoint_eastings,$viewpoint_northings,$viewpoint_grlen,$view_direction = -1) {
		$this->viewpoint_eastings = $viewpoint_eastings;
		$this->viewpoint_northings = $viewpoint_northings;
		$this->viewpoint_grlen = $viewpoint_grlen;
		$this->view_direction = $view_direction;
		$this->viewpoint_ri = $viewpoint_ri;
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

		if ($this->service == 'Google') {
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
		} elseif ($this->service == 'OLayers') {
			if (!empty($this->inline) || !empty($this->issubmit)) {
				return "<div id=\"map\" style=\"width:{$width}px; height:{$width}px\"></div>";// FIXME Loading map... (JavaScript required)
			} else {
				$token=new Token;
				
				foreach ($this as $key => $value) {
					if (is_scalar($value)) {
						$token->setValue($key, $value);
					}
				}
				$token = $token->getToken();
				
				return "<iframe src=\"/map_frame.php?t=$token\" id=\"map\" width=\"{$width}\" height=\"{$width}\" scrolling=\"no\"></iframe>";// FIXME Loading map... (JavaScript required)
			}
		} elseif ($this->service == 'OS50k-small') {
			static $idcounter = 1;
			
			if ($this->natgrlen == 4) {
				$this->nateastings = $east + 500;
				$this->natnorthings = $nort + 500;
			}
			
			$mapurl = "http://{$CONF['TILE_HOST']}/tile.php?r=".$this->getToken();
			if (isset($CONF['curtail_level']) && $CONF['curtail_level'] > 3 && empty($GLOBALS['USER']->user_id)) {
				$mapurl = cachize_url($mapurl);
			}
			$gr= str_replace(' ','+',!empty($this->square->grid_reference_full)?$this->square->grid_reference_full:$this->square->grid_reference);
			
			$title = "1:50,000 Modern Day Landranger(TM) Map &copy; Crown Copyright";
			
			$this->displayMarker1 = $this->exactPosition;
			
			if ($this->displayMarker1 && !($this->natgrlen > 6) ) {
				//nice central marker

				$padding = intval(($width-29)/2);
				$str .= "<a href=\"/gridref/$gr\" title=\"$title\" onmouseover=\"document.getElementById('marker$idcounter').src='http://{$CONF['STATIC_HOST']}/img/blank.gif'\" onmouseout=\"document.getElementById('marker$idcounter').src='http://{$CONF['STATIC_HOST']}/img/icons/circle.png'\"><img src=\"http://{$CONF['STATIC_HOST']}/img/icons/circle.png\" style=\"padding:{$padding}px;width:29px;height:29px;background-image:url($mapurl);\" border=\"1\" alt=\"$title\" galleryimg=\"no\" id=\"marker$idcounter\"/></a>";

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

				$str .= "<a href=\"/gridref/$gr\" title=\"$title\" onmouseover=\"document.getElementById('marker$idcounter').src='http://{$CONF['STATIC_HOST']}/img/blank.gif'\" onmouseout=\"document.getElementById('marker$idcounter').src='http://{$CONF['STATIC_HOST']}/img/icons/circle.png'\"><img src=\"http://{$CONF['STATIC_HOST']}/img/icons/circle.png\" style=\"{$padding};width:29px;height:29px;background-image:url($mapurl);\" border=\"0\" alt=\"$title\" galleryimg=\"no\" id=\"marker$idcounter\"/></a>";

			} else {
				//no marker

				$str .= "<a href=\"/gridref/$gr\" title=\"$title\"><img src=\"http://{$CONF['STATIC_HOST']}/img/blank.gif\" style=\"width:{$width}px;height:{$width}px;background-image:url($mapurl);\" border=\"1\" alt=\"$title\" galleryimg=\"no\" id=\"marker$idcounter\"/></a>";
			}
		
			$idcounter++;
			
			return $str;
		
		} elseif ($this->service == 'OS50k') {
			if (!empty($CONF['fetch_on_demand'])) {
				$mapurl = "http://{$CONF['fetch_on_demand']}/tile.php?r=".$this->getToken();
			} else {
				$mapurl = "http://{$CONF['TILE_HOST']}/tile.php?r=".$this->getToken();
			}
			if (isset($CONF['curtail_level']) && $CONF['curtail_level'] > 3 && empty($GLOBALS['USER']->user_id)) {
				$mapurl = cachize_url($mapurl);
			}
			#$this->mapurl = $mapurl;
			$title = "1:50,000 Modern Day Landranger(TM) Map &copy; Crown Copyright";
		} elseif ($this->service == 'WMS') { #FIXME
			if ($this->servicegk === false) {
				$mapurl=sprintf($this->serviceurl, $width, $width, $east - 500, $nort - 500, $east + 1500, $nort + 1500);
			} else {
				require_once('geograph/conversionslatlong.class.php');
				$conv = new ConversionsLatLong;
				list ($lat,$long) = $conv->national_to_wgs84($east+500,$nort+500,$this->reference_index);
				list ($ge, $gn) = $conv->wgs84_to_gk($lat,$long, $this->servicegk);
				$mapurl=sprintf($this->serviceurl, $width, $width, $ge - 1000, $gn - 1000, $ge + 1000, $gn + 1000);
			}
			$title = $this->title;
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
			$mericonv = $this->delmeri * sin(deg2rad($this->lat));// FIXME is lat always set?
			$cosrot = cos(deg2rad($mericonv));
			$sinrot = sin(deg2rad($mericonv));
			$extra = ($this->issubmit === true)?44:(($this->issubmit)?22:0);

	//container
			$str = "<div style=\"position:relative;height:".($width+$extra)."px;width:{$this->width}px;\" id=\"rastermap\">";

	//map image
			$str .= "<div style=\"top:0px;left:0px;width:{$width}px;height:{$width}px\"><img name=\"tile\" src=\"$mapurl\" style=\"width:{$width}px;height:{$width}px\" border=\"1\" alt=\"$title\"/></div>";

	//drag prompt
			if ($this->issubmit)
				$str .= "<div style=\"position:absolute;top:".($width)."px;left:0px; font-size:0.8em;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <small style=\"color:#0018F8\">&lt;- Drag to mark subject position.</small></div>";
			if ($this->issubmit === true)
				$str .= "<div style=\"position:absolute;top:".($width+22)."px;left:0px; font-size:0.8em;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <small style=\"color:#002E73\">&lt;- Drag to mark photographer position.</small></div>";

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
				#$left = ($width/4) + ( ($e - $east) * $widthby2 / 1000 );
				#$top = $width - ( ($width/4) + ( ($n - $nort) * $widthby2 / 1000 ) );
				$dx = ($e - 500 - $east) * $widthby2 / 1000;
				$dy = ($n - 500 - $nort) * $widthby2 / 1000;
				$left = $widthby2 + ($dx*$cosrot - $dy*$sinrot);
				$top  = $widthby2 - ($dx*$sinrot + $dy*$cosrot);
			}

	//choose photographer icon
			$prefix = $this->issubmit?'viewc':'camicon';
			if (isset($this->view_direction) && strlen($this->view_direction) && $this->view_direction != -1)
				$iconfile = "$prefix-".intval($this->view_direction).".png";
			else
				$iconfile = "$prefix--1.png";

			if ($this->viewpoint_ri == $this->reference_index) {
				$viewpoint_eastings = $this->viewpoint_eastings;
				$viewpoint_northings = $this->viewpoint_northings;
			} else {
				$viewpoint_eastings = -1;
				$viewpoint_northings = -1;
				$latlong = $conv->national_to_wgs84($this->viewpoint_eastings,$this->viewpoint_northings,$this->viewpoint_ri);
				if (count($latlong)) { # FIXME error handling
					$enr = $conv->wgs84_to_national($latlong[0],$latlong[1], true, $this->reference_index);
					if (count($enr)) { # FIXME error handling
						$viewpoint_eastings = $enr[0];
						$viewpoint_northings  = $enr[1];
					}
				}
			}
	
			$different_square_true = (intval($this->nateastings/1000) != intval($viewpoint_eastings/1000)
						|| intval($this->natnorthings/1000) != intval($viewpoint_northings/1000));

			$show_viewpoint = (intval($this->viewpoint_grlen) > 4) || ($different_square_true && ($this->viewpoint_grlen == '4'));

	//calculate photographer position
			if (!$this->issubmit && $show_viewpoint) {
				$e = $viewpoint_eastings;	$n = $viewpoint_northings;
				if ($this->viewpoint_grlen == '4') {
					$e +=500; $n += 500;
				}
				if ($this->viewpoint_grlen == '6') {
					$e +=50; $n += 50;
				}
				#$vleft = ($width/4) + ( ($e - $east) * $widthby2 / 1000 );
				#$vtop = $width - ( ($width/4) + ( ($n - $nort) * $widthby2 / 1000 ) );
				$dx = ($e - 500 - $east) * $widthby2 / 1000;
				$dy = ($n - 500 - $nort) * $widthby2 / 1000;
				$vleft = $widthby2 + ($dx*$cosrot - $dy*$sinrot);
				$vtop  = $widthby2 - ($dx*$sinrot + $dy*$cosrot);

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

			$top = round($top);
			$left = round($left);
			$vtop = round($vtop);
			$vleft = round($vleft);
			
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

	//grid
			if ($this->grid) {
				$gridfile = "grid_$width";
				// we only have +3, 0, -3, here. latitude is approx. 47°...56° => 3°*sin(lat) approx. 2.34°
				if ($this->delmeri < 0) {
					$gridfile .= "_-2.34";
				} elseif ($this->delmeri > 0) {
					$gridfile .= "_+2.34";
				} else {
				}
				#trigger_error("--- >{$_SERVER['HTTP_USER_AGENT']}< ", E_USER_NOTICE);
				if (preg_match('#^Mozilla/4\.0 \(compatible; MSIE [56]\.#', $_SERVER['HTTP_USER_AGENT'])) {
					$gridfile .= ".gif"; # IE 6 :-(
				} else {
					$gridfile .= ".png";
				}
				$str .= "<div style=\"position:absolute;top:1px;left:1px;\" id=\"grid\"><img src=\"http://{$CONF['STATIC_HOST']}/img/$gridfile\" alt=\"\" width=\"$width\" height=\"$width\" name=\"grid\"/></div>";
			#	$gxy0 = 1;
			#	$gx1 = $width/4 + 1;
			#	$gx2 = $width-$width/4 + 1;
			#	$gy1 = $width/4 - 9;        # FIXME?
			#	$gy2 = $width-$width/4 - 9; # FIXME?
			#	$str .= "<div style=\"position:absolute;top:".$gxy0."px;left:".$gx1."px;\" id=\"gridw\"><img src=\"http://{$CONF['STATIC_HOST']}/img/bluetransp.png\" alt=\"\" width=\"1\" height=\"$width\" name=\"gridw\"/></div>";
			#	$str .= "<div style=\"position:absolute;top:".$gxy0."px;left:".$gx2."px;\" id=\"gride\"><img src=\"http://{$CONF['STATIC_HOST']}/img/bluetransp.png\" alt=\"\" width=\"1\" height=\"$width\" name=\"gride\"/></div>";
			#	$str .= "<div style=\"position:absolute;top:".$gy1."px;left:".$gxy0."px;\" id=\"gridn\"><img src=\"http://{$CONF['STATIC_HOST']}/img/bluetransp.png\" alt=\"\" width=\"$width\" height=\"1\" name=\"gridn\"/></div>";
			#	$str .= "<div style=\"position:absolute;top:".$gy2."px;left:".$gxy0."px;\" id=\"grids\"><img src=\"http://{$CONF['STATIC_HOST']}/img/bluetransp.png\" alt=\"\" width=\"$width\" height=\"1\" name=\"grids\"/></div>";
			}

	//subject icon
			$str .= "<div style=\"position:absolute;top:".($top-14)."px;left:".($left-14)."px;".( $this->displayMarker1 ?'':'display:none')."\" id=\"marker1\"><img src=\"http://{$CONF['STATIC_HOST']}/img/icons/$subfile\" alt=\"+\" width=\"29\" height=\"29\" name=\"subicon\"/></div>";

	//photographer icon
			if ($this->issubmit) {
				$str .= "<div style=\"position:absolute;top:".($vtop-14)."px;left:".($vleft-14)."px;".( $this->displayMarker2 ?'':'display:none')."\" id=\"marker2\"><img src=\"http://{$CONF['STATIC_HOST']}/img/icons/$iconfile\" alt=\"+\" width=\"29\" height=\"29\" name=\"camicon\"/></div>";
			} else {
				$str .= "<div style=\"position:absolute;top:".($vtop-20)."px;left:".($vleft-9)."px;".( $this->displayMarker2 ?'':'display:none')."\" id=\"marker2\"><img src=\"http://{$CONF['STATIC_HOST']}/img/icons/$iconfile\" alt=\"+\" width=\"20\" height=\"31\" name=\"camicon\"/></div>";
			}

	//overlay (for dragging)
			$str .= "<div style=\"position:absolute;top:0px;left:0px;z-index:3\">";
			$imagestr = "<img src=\"http://{$CONF['STATIC_HOST']}/img/blank.gif\" class=\"mapmask\" style=\"width:{$width}px;height:".($width+$extra)."px\" border=\"1\" alt=\"$title\" title=\"$title\" name=\"map\" galleryimg=\"no\"/>";
			if ($this->maplink&&!empty($gridref)) {
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
				<div class=\"interestBox\" style=\"font-size:0.8em\">Switch to <a href=\"javascript:switchTo(2);\"  id=\"mapSwitcherOS50k\">Historic Map</a><a href=\"javascript:switchTo(1);\" id=\"mapSwitcherVoB\" style=\"display:none\">Modern Map</a>.</div>
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
						document.images['tile'].src = 'http://{$CONF['STATIC_HOST']}/img/blank.gif';
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
		if ((!empty($this->inline) || !empty($this->issubmit))) {
			if ($this->service == 'Google') {
				return "<script src=\"http://maps.google.com/maps?file=api&amp;v=2&amp;key={$CONF['google_maps_api_key']}\" type=\"text/javascript\"></script>";
			} elseif ($this->service == 'OLayers') {
				if ($CONF['google_maps_api_key'])
					$ft = "<script src=\"http://maps.google.com/maps/api/js?v=3.5&amp;sensor=false&amp;key={$CONF['google_maps_api_key']}\" type=\"text/javascript\"></script>";
				else
					$ft = '';
				$ft .= <<<EOF
<!--script type="text/javascript" src="/ol/OpenLayers.js"></script-->
<!--script type="text/javascript" src="/mapper/geotools2.js"></script-->
<!--script type="text/javascript" src="/mappingO.js"></script-->
EOF;
				return $ft;
			}
		}
	}

	function getMeriBlock($long,$lat1,$lat2,$op=1) {
		return "			var polyline = new GPolyline([
				new GLatLng($lat1,$long),
				new GLatLng($lat2,$long)
			], \"#FF0000\", 1, $op);
			map.addOverlay(polyline);\n";
	}

	function getMeriBlockOL($long,$lat1,$lat2,$op=1) {
		return <<<EOF
			var lp1 = new OpenLayers.Geometry.Point($long, $lat1);
			var lp2 = new OpenLayers.Geometry.Point($long, $lat2);
			var points = [
				lp1.transform(epsg4326, map.getProjectionObject()),
				lp2.transform(epsg4326, map.getProjectionObject())
			];
			var line = new OpenLayers.Geometry.LineString(points);

			var style = {
				strokeColor: '#ff0000',
				strokeWidth: 1,
				strokeOpacity: $op,
			};

			lines.addFeatures([new OpenLayers.Feature.Vector(line, null, style)]);
EOF;
	}

function getPolyLineBlock(&$conv,$e1,$n1,$e2,$n2,$op=1) {
	list($lat1,$long1) = $conv->national_to_wgs84($e1,$n1,$this->reference_index);
	list($lat2,$long2) = $conv->national_to_wgs84($e2,$n2,$this->reference_index);
	return "			var polyline = new GPolyline([
			new GLatLng($lat1,$long1),
			new GLatLng($lat2,$long2)
		], \"#0000FF\", 1, $op);
		map.addOverlay(polyline);\n";
}

function getPolyLineBlockOL(&$conv,$e1,$n1,$e2,$n2,$op=1) {
	list($lat1,$long1) = $conv->national_to_wgs84($e1,$n1,$this->reference_index);
	list($lat2,$long2) = $conv->national_to_wgs84($e2,$n2,$this->reference_index);
	return <<<EOF
		var lp1 = new OpenLayers.Geometry.Point($long1, $lat1);
		var lp2 = new OpenLayers.Geometry.Point($long2, $lat2);
		var points = [
			lp1.transform(epsg4326, map.getProjectionObject()),
			lp2.transform(epsg4326, map.getProjectionObject())
		];
		var line = new OpenLayers.Geometry.LineString(points);

		var style = {
			strokeColor: '#0000ff',
			strokeWidth: 1,
			strokeOpacity: $op,
		};

		lines.addFeatures([new OpenLayers.Feature.Vector(line, null, style)]);
EOF;
}

#	function getPolyLineBlock(&$conv,$e1,$n1,$e2,$n2) {
#		list($lat1,$long1) = $conv->national_to_wgs84($e1,$n1,$this->reference_index);
#		list($lat2,$long2) = $conv->national_to_wgs84($e2,$n2,$this->reference_index);
#		return "			var polyline = new GPolyline([
#				new GLatLng($lat1,$long1),
#				new GLatLng($lat2,$long2)
#			], \"#0000FF\", 1);
#			map.addOverlay(polyline);\n";
#	}
	
	function getPolySquareBlock(&$conv,$e1,$n1,$e2,$n2) {
		list($lat1,$long1) = $conv->national_to_wgs84($e1,$n1,$this->reference_index);
		list($lat2,$long2) = $conv->national_to_wgs84($e2,$n2,$this->reference_index);
		return "			pickupbox = new GPolygon([
				new GLatLng($lat1,$long1),
				new GLatLng($lat1,$long2),
				new GLatLng($lat2,$long2),
				new GLatLng($lat2,$long1),
				new GLatLng($lat1,$long1)
			], \"#0000FF\", 1, 0.7, \"#00FF00\", 0.5);
			map.addOverlay(pickupbox);\n";
	}

	function getPolySquareBlockOL(&$conv,$e1,$n1,$e2,$n2) {
		list($lat1,$long1) = $conv->national_to_wgs84($e1,$n1,$this->reference_index);
		list($lat2,$long2) = $conv->national_to_wgs84($e2,$n2,$this->reference_index);
		return <<<EOF
			pickuplayer = lines;
			var p1 = new OpenLayers.Geometry.Point($long1, $lat1);
			var p3 = new OpenLayers.Geometry.Point($long2, $lat2);
			p1.transform(epsg4326, map.getProjectionObject());
			p3.transform(epsg4326, map.getProjectionObject());
			var p2 = new OpenLayers.Geometry.Point(p1.x, p3.y);
			var p4 = new OpenLayers.Geometry.Point(p3.x, p1.y);

			var points = [ p1, p2, p3, p4 ];

			var ring = new OpenLayers.Geometry.LinearRing(points);

			var style = {
				strokeColor: '#0000ff',
				strokeWidth: 1,
				strokeOpacity: 0.7,
				fillColor: '#00ff00',
				fillOpacity: 0.5
			};

			pickupbox = new OpenLayers.Feature.Vector(ring, null, style);
			pickuplayer.addFeatures([pickupbox]);
EOF;
	}

	function getScriptTag()
	{
		global $CONF;
		if ($this->service == 'Google') {
			if (empty($this->inline) && empty($this->issubmit)) {
				//its now handled by the 'childmap'
				return;
			}
			require_once('geograph/conversions.class.php');
			$conv = new Conversions;
				
			$e = floor($this->nateastings/1000) * 1000;
			$n = floor($this->natnorthings/1000) * 1000;
				
			if (strpos($CONF['raster_service'],'Grid') !== FALSE) {
				
				if (!$this->iscmap) {
					$block = $this->getPolyLineBlock($conv,$e-1000,$n,$e+2000,$n);
					$block .= $this->getPolyLineBlock($conv,$e-1000,$n+1000,$e+2000,$n+1000);
					$block .= $this->getPolyLineBlock($conv,$e,$n-1000,$e,$n+2000);
					$block .= $this->getPolyLineBlock($conv,$e+1000,$n-1000,$e+1000,$n+2000);
				}
				
				if (!empty($this->viewpoint_northings)) {
					if ($this->viewpoint_ri == $this->reference_index) {
						$viewpoint_eastings = $this->viewpoint_eastings;
						$viewpoint_northings = $this->viewpoint_northings;
					} else {
						$viewpoint_eastings = -1;
						$viewpoint_northings = -1;
						$latlong = $conv->national_to_wgs84($this->viewpoint_eastings,$this->viewpoint_northings,$this->viewpoint_ri);
						if (count($latlong)) { # FIXME error handling
							$enr = $conv->wgs84_to_national($latlong[0],$latlong[1], true, $this->reference_index);
							if (count($enr)) { # FIXME error handling
								$viewpoint_eastings = $enr[0];
								$viewpoint_northings  = $enr[1];
							}
						}
					}
					$different_square_true = (intval($this->nateastings/1000) != intval($viewpoint_eastings/1000)
						|| intval($this->natnorthings/1000) != intval($viewpoint_northings/1000));

					$show_viewpoint = (intval($this->viewpoint_grlen) > 4) || ($different_square_true && ($this->viewpoint_grlen == '4'));

					if ($show_viewpoint) {
						$ve = $viewpoint_eastings;	$vn = $viewpoint_northings;
						if ($this->viewpoint_grlen == '4') {
							$ve +=500; $vn += 500;
						}
						if ($this->viewpoint_grlen == '6') {
							$ve +=50; $vn += 50;
						}
						list($lat,$long) = $conv->national_to_wgs84($ve,$vn,$this->reference_index);
						$block .= "
						var ppoint = new GLatLng({$lat},{$long});
						map.addOverlay(createPMarker(ppoint));\n";
					}
				}

				if (empty($lat) && $this->issubmit) {
					list($lat,$long) = $conv->national_to_wgs84($e-700,$n-500,$this->reference_index);
					$block .= "
						var ppoint = new GLatLng({$lat},{$long});
						map.addOverlay(createPMarker(ppoint));\n";
				}
			} else {
				$block = '';
			}
			if ($this->exactPosition) {
				$block.= "map.addOverlay(createMarker(point));";
			} elseif ($this->issubmit) {
				list($lat,$long) = $conv->national_to_wgs84($e-400,$n-500,$this->reference_index);
				$block .= "
					var point2 = new GLatLng({$lat},{$long});
					map.addOverlay(createMarker(point2));\n";
			}
			if ($this->issubmit) {
				$zoom=13;
			} else {
				$zoom=14;
			}
			if ($this->issubmit && !$this->iscmap) {
				$block .= $this->getPolySquareBlock($conv,$e-800,$n-600,$e-200,$n-100);
			}
			if ($this->issubmit && !$this->iscmap) {
				for ($i=100; $i<=900; $i+=100) {
					$block .= $this->getPolyLineBlock($conv,$e,   $n+$i,$e+1000,$n+$i,   0.25);
					$block .= $this->getPolyLineBlock($conv,$e+$i,$n,   $e+$i,  $n+1000, 0.25);
				}
			}
			if (empty($this->lat)) {
				list($this->lat,$this->long) = $conv->national_to_wgs84($this->nateastings,$this->natnorthings,$this->reference_index);
			}
			if ($CONF['showmeridian'] != 0 && !$this->iscmap) {
				list($centlat,$centlong) = $conv->national_to_wgs84($e+500,$n+500,$this->reference_index);
				$merilong = round($centlong/$CONF['showmeridian']) * $CONF['showmeridian'];
				$meridist = deg2rad(abs($centlong-$merilong)) * cos(deg2rad($centlat)) * 6371;
				if ($meridist < 3) { # only show meridian if closer than 3 km to center of square
					$deltalat = rad2deg(3.0/6371); # show approx 2*3km
					$block .= $this->getMeriBlock($merilong,$centlat-$deltalat,$centlat+$deltalat);
				}
			}
			if ($this->issubmit) {
				$p1 = "<script type=\"text/javascript\" src=\"".smarty_modifier_revision("/mapper/geotools2.js")."\"></script>";
			} else {
				$p1 = '';
			}
			#	<style type=\"text/css\">
			#	v\:* {
			#		behavior:url(#default#VML);
			#	}
			#	</style>
			#osm test stolen from http://www.openstreetmap.info/examples/gmap-example2.html <- http://www.openstreetmap.info/examples/webmap.html
			$osm_func=<<<EOF
function GetTileUrl_Mapnik(a, z) {
    return "http://tile.openstreetmap.org/" +
                z + "/" + a.x + "/" + a.y + ".png";
}


function GetTileUrl_TaH(a, z) {
    return "http://tah.openstreetmap.org/Tiles/tile/" +
                z + "/" + a.x + "/" + a.y + ".png";
}

function GetTileUrl_TopB(a, z) {
    //return "http://topo.openstreetmap.de/base/" +
    sd = inthash("" + a.x + a.y + z, 2) == 0 ? "base" : "base2" ;
    return "http://" + sd + ".wanderreitkarte.de/base/" +
                z + "/" + a.x + "/" + a.y + ".png";
}

function GetTileUrl_TopH(a, z) {
    //return "http://hills-nc.openstreetmap.de/" +
    return "http://wanderreitkarte.de/hills/" +
                z + "/" + a.x + "/" + a.y + ".png";
}

function GetTileUrl_Top(a, z) {
    //return "http://topo.openstreetmap.de/topo/" +
    sd = inthash("" + a.x + a.y + z, 2) == 0 ? "topo" : "topo2" ;
    return "http://" + sd + ".wanderreitkarte.de/topo/" +
                z + "/" + a.x + "/" + a.y + ".png";
}
EOF;

			$osm_block=<<<EOF
    var copyright = new GCopyright(1,
        new GLatLngBounds(new GLatLng(-90,-180), new GLatLng(90,180)), 0,
        ': http://www.openstreetmap.org/copyright');
    var copyrightCollection =
        new GCopyrightCollection('(c) OSM Contributors');
    copyrightCollection.addCopyright(copyright);

    var copyrightOSMs = new GCopyright(1,
        new GLatLngBounds(new GLatLng(-90,-180), new GLatLng(90,180)), 0,
        ': http://www.openstreetmap.org/copyright');
    var copyrightCollectionOSMs =
        new GCopyrightCollection('(c) OSM');
    copyrightCollectionOSMs.addCopyright(copyrightOSMs);

    var tilelayers_mapnik = new Array();
    tilelayers_mapnik[0] = new GTileLayer(copyrightCollection, 0, 18);
    tilelayers_mapnik[0].getTileUrl = GetTileUrl_Mapnik;
    tilelayers_mapnik[0].isPng = function () { return true; };
    tilelayers_mapnik[0].getOpacity = function () { return 1.0; };
    var mapnik_map = new GMapType(tilelayers_mapnik,
        new GMercatorProjection(19), "OSM (Mapnik)",
        { urlArg: 'mapnik', linkColor: '#000000', shortName: 'OSM', alt: 'OSM: Mapnik' });
    map.addMapType(mapnik_map);

    var tilelayers_tah = new Array();
    tilelayers_tah[0] = new GTileLayer(copyrightCollection, 0, 17);
    tilelayers_tah[0].getTileUrl = GetTileUrl_TaH;
    tilelayers_tah[0].isPng = function () { return true; };
    tilelayers_tah[0].getOpacity = function () { return 1.0; };

    var tah_map = new GMapType(tilelayers_tah,
        new GMercatorProjection(19), "OSM (T@H)",
        { urlArg: 'tah', linkColor: '#000000', shortName: 'T@H', alt: 'OSM: Tiles@home' });
    map.addMapType(tah_map);

    var copyright1 = new GCopyright(1,
        new GLatLngBounds(new GLatLng(-90,-180), new GLatLng(90,180)), 0,
        ': http://www.wanderreitkarte.de/licence_de.php');
    var copyright2 = new GCopyright(1,
        new GLatLngBounds(new GLatLng(-90,-180), new GLatLng(90,180)), 0,
        ': http://www.wanderreitkarte.de/licence_de.php');
    var copyrightCollectionTopo = new GCopyrightCollection("Nops RWK");
    var copyrightCollectionTopoH = new GCopyrightCollection("DEM CIAT");
    copyrightCollectionTopo.addCopyright(copyright1);
    copyrightCollectionTopoH.addCopyright(copyright2);

    var tilelayers_top = new Array();
    tilelayers_top[0] = new GTileLayer(copyrightCollectionTopo, 0, 17);
    tilelayers_top[1] = new GTileLayer(copyrightCollectionTopoH, 9, 19);
    tilelayers_top[2] = new GTileLayer(copyrightCollectionTopo, 0, 17);
    tilelayers_top[0].isPng = function () { return true; };
    tilelayers_top[1].isPng = function () { return true; };
    tilelayers_top[2].isPng = function () { return true; };
    tilelayers_top[0].getOpacity = function () { return 1.0; };
    tilelayers_top[1].getOpacity = function () { return 1.0; };
    tilelayers_top[2].getOpacity = function () { return 1.0; };
    tilelayers_top[0].getTileUrl = GetTileUrl_TopB;
    tilelayers_top[1].getTileUrl = GetTileUrl_TopH;
    tilelayers_top[2].getTileUrl = GetTileUrl_Top;

    var topo_map = new GMapType(tilelayers_top,
        new GMercatorProjection(19), "Nops RWK",
        { urlArg: 'topo', linkColor: '#000000', shortName: "Nop-RWK", alt: "Nop: Reit- und Wanderkarte" });
    map.addMapType(topo_map);

    var tilelayers_mapnikh = new Array();
    tilelayers_mapnikh[0] = new GTileLayer(copyrightCollectionOSMs, 0, 18);
    tilelayers_mapnikh[1] = new GTileLayer(copyrightCollectionTopoH, 8, 15);
    tilelayers_mapnikh[0].isPng = function () { return true; };
    tilelayers_mapnikh[1].isPng = function () { return true; };
    tilelayers_mapnikh[0].getOpacity = function () { return 1.0; };
    tilelayers_mapnikh[1].getOpacity = function () { return 1.0; };
    tilelayers_mapnikh[0].getTileUrl = GetTileUrl_Mapnik;
    tilelayers_mapnikh[1].getTileUrl = GetTileUrl_TopH;
    var mapnikh_map = new GMapType(tilelayers_mapnikh,
        new GMercatorProjection(19), "OSM (Mapnik) + Profile",
        { urlArg: 'mapnikh', linkColor: '#000000', shortName: 'OSM+P', alt: 'OSM: Mapnik+Profile' });
    map.addMapType(mapnikh_map);

EOF;



			return "
				$p1
				<script type=\"text/javascript\" src=\"".smarty_modifier_revision("/mappingG.js")."\"></script>
				<script type=\"text/javascript\">
				//<![CDATA[
					var issubmit = {$this->issubmit}+0;
					var iscmap = {$this->iscmap}+0;
					var ri = {$this->reference_index};
					var map = null;
					$osm_func
					function loadmap() {
						if (GBrowserIsCompatible()) {
							map = new GMap2(document.getElementById(\"map\"));
							map.addMapType(G_PHYSICAL_MAP);
							$osm_block
							map.addControl(new GSmallZoomControl());
							///////////////
							//var mapControl = new GHierarchicalMapTypeControl();
							//mapControl.clearRelationships();
							//mapControl.addRelationship(G_SATELLITE_MAP, G_HYBRID_MAP, \"Labels\", false);
							//mapControl.addRelationship(mapnik_map, tah_map, \"T@H\", false);
							//map.addControl(mapControl);
							///////////////
							//map.addControl(new GMapTypeControl(true));
							map.addControl(new GMenuMapTypeControl(true));
							//map.disableDragging();
							map.enableDoubleClickZoom(); 
							map.enableContinuousZoom();
							map.enableScrollWheelZoom();
							var point = new GLatLng({$this->lat},{$this->long});
							//map.setCenter(point, 13, G_PHYSICAL_MAP);
							map.setCenter(point, $zoom, G_HYBRID_MAP);
							//map.setCenter(point, $zoom, G_SATELLITE_MAP);
							$block 
							
							AttachEvent(window,'unload',GUnload,false);
						}
					}
					AttachEvent(window,'load',loadmap,false);
					var static_host = '{$CONF['STATIC_HOST']}';
				//]]>
				</script>";
		} elseif ($this->service == 'OLayers') {
			if (empty($this->inline) && empty($this->issubmit)) {
				//its now handled by the 'childmap'
				return;
			}
			// FIXME/TODO
			// * css in _basic_begin.tpl / _std_begin.tpl : rastermap->getCSSTag() ? ommap: Common place for css?
			require_once('geograph/conversions.class.php');
			$conv = new Conversions;
				
			$e = floor($this->nateastings/1000) * 1000;
			$n = floor($this->natnorthings/1000) * 1000;
				
			$needvectorlayer = false;
			if (strpos($CONF['raster_service'],'Grid') !== FALSE) {
				
				if (!$this->iscmap) {
					$block = $this->getPolyLineBlockOL($conv,$e-1000,$n,$e+2000,$n);
					$block .= $this->getPolyLineBlockOL($conv,$e-1000,$n+1000,$e+2000,$n+1000);
					$block .= $this->getPolyLineBlockOL($conv,$e,$n-1000,$e,$n+2000);
					$block .= $this->getPolyLineBlockOL($conv,$e+1000,$n-1000,$e+1000,$n+2000);
					$needvectorlayer = true;
				}
				
				if (!empty($this->viewpoint_northings)) {
					if ($this->viewpoint_ri == $this->reference_index) {
						$viewpoint_eastings = $this->viewpoint_eastings;
						$viewpoint_northings = $this->viewpoint_northings;
					} else {
						$viewpoint_eastings = -1;
						$viewpoint_northings = -1;
						$latlong = $conv->national_to_wgs84($this->viewpoint_eastings,$this->viewpoint_northings,$this->viewpoint_ri);
						if (count($latlong)) { # FIXME error handling
							$enr = $conv->wgs84_to_national($latlong[0],$latlong[1], true, $this->reference_index);
							if (count($enr)) { # FIXME error handling
								$viewpoint_eastings = $enr[0];
								$viewpoint_northings  = $enr[1];
							}
						}
					}
					$different_square_true = (intval($this->nateastings/1000) != intval($viewpoint_eastings/1000)
						|| intval($this->natnorthings/1000) != intval($viewpoint_northings/1000));

					$show_viewpoint = (intval($this->viewpoint_grlen) > 4) || ($different_square_true && ($this->viewpoint_grlen == '4'));

					if ($show_viewpoint) {
						$ve = $viewpoint_eastings;	$vn = $viewpoint_northings;
						if ($this->viewpoint_grlen == '4') {
							$ve +=500; $vn += 500;
						}
						if ($this->viewpoint_grlen == '6') {
							$ve +=50; $vn += 50;
						}
						list($lat,$long) = $conv->national_to_wgs84($ve,$vn,$this->reference_index);
						$block .= "
						var ppoint = new OpenLayers.LonLat({$long},{$lat});
						createPMarker(ppoint);\n";
					}
				}

				if (empty($lat) && $this->issubmit) {
					list($lat,$long) = $conv->national_to_wgs84($e-700,$n-500,$this->reference_index);
					$block .= "
						var ppoint = new OpenLayers.LonLat({$long},{$lat});
						createPMarker(ppoint);\n";
				}
			} else {
				$block = '';
			}
			if ($this->exactPosition) {
				$block.= "
					var point2 = new OpenLayers.LonLat({$this->long}, {$this->lat});
					createMarker(point2, 0);";
			} elseif ($this->issubmit) {
				list($lat,$long) = $conv->national_to_wgs84($e-400,$n-500,$this->reference_index);
				$block .= "
					var point2 = new OpenLayers.LonLat({$long},{$lat});
					createMarker(point2, 0);\n";
			}
			if ($this->issubmit) {
				$zoom=13;
			} else {
				$zoom=14;
			}
			if ($this->issubmit && !$this->iscmap) {
				$block .= $this->getPolySquareBlockOL($conv,$e-800,$n-600,$e-200,$n-100);
				$needvectorlayer = true;
			}
			if ($this->issubmit && !$this->iscmap) {
				for ($i=100; $i<=900; $i+=100) {
					$block .= $this->getPolyLineBlockOL($conv,$e,   $n+$i,$e+1000,$n+$i,   0.25);
					$block .= $this->getPolyLineBlockOL($conv,$e+$i,$n,   $e+$i,  $n+1000, 0.25);
				}
				$needvectorlayer = true;
			}
			if (empty($this->lat)) {
				list($this->lat,$this->long) = $conv->national_to_wgs84($this->nateastings,$this->natnorthings,$this->reference_index);
			}
			if ($CONF['showmeridian'] != 0 && !$this->iscmap) {
				list($centlat,$centlong) = $conv->national_to_wgs84($e+500,$n+500,$this->reference_index);
				$merilong = round($centlong/$CONF['showmeridian']) * $CONF['showmeridian'];
				$meridist = deg2rad(abs($centlong-$merilong)) * cos(deg2rad($centlat)) * 6371;
				if ($meridist < 3) { # only show meridian if closer than 3 km to center of square
					$deltalat = rad2deg(3.0/6371); # show approx 2*3km
					$block .= $this->getMeriBlockOL($merilong,$centlat-$deltalat,$centlat+$deltalat);
				}
				$needvectorlayer = true;
			}
			if ($this->issubmit) {
				$p1 = "<script type=\"text/javascript\" src=\"".smarty_modifier_revision("/mapper/geotools2.js")."\"></script>";
			} else {
				$p1 = '';
			}
			if (!$CONF['google_maps_api_key']) {
				$google_block='';
				$google_layers = '';
			} else {
				$google_layers = 'gphy, gmap, gsat, ghyb,';
				$google_block=<<<EOF
			var gphy = new OpenLayers.Layer.Google(
				"Google Physical",
				{type: google.maps.MapTypeId.TERRAIN}
			);

			var gmap = new OpenLayers.Layer.Google(
				"Google Streets",
				{numZoomLevels: 20}
			);

			var ghyb = new OpenLayers.Layer.Google(
				"Google Hybrid",
				{type: google.maps.MapTypeId.HYBRID, numZoomLevels: 20}
			);

			var gsat = new OpenLayers.Layer.Google(
				"Google Satellite",
				{type: google.maps.MapTypeId.SATELLITE, numZoomLevels: 22}
			);
EOF;
			}
			if ($needvectorlayer) {
				$vector_layer = "lines,";
				$vector_block = <<<EOF
	lines = new OpenLayers.Layer.Vector(
		"Lines",
		{
			isBaseLayer: false,
			//renderers: OpenLayers.Layer.Vector.prototype.renderers, //FIXME?
			displayInLayerSwitcher: false
		}
	);
EOF;
			} else {
				$vector_layer = "";
				$vector_block = "";
			}

			return "
				$p1
				<!--script type=\"text/javascript\" src=\"/mapper/geotools2.js\"></script-->
				<script type=\"text/javascript\" src=\"/ol/OpenLayers.js\"></script>
				<!--script type=\"text/javascript\" src=\"/mappingO.js\"></script-->
				<script type=\"text/javascript\" src=\"".smarty_modifier_revision("/mappingO.js")."\"></script>
				<script type=\"text/javascript\">
				//<![CDATA[
					var issubmit = {$this->issubmit}+0;
					var iscmap = {$this->iscmap}+0;
					var ri = {$this->reference_index};
					var map = null;
		function loadmapO() {
			map = new OpenLayers.Map({
				div: \"map\",
				projection: epsg900913,
				displayProjection: epsg4326,
				units: \"m\",
				//minZoomLevel : 4,
				//maxZoomLevel : 18,
				//numZoomLevels : null,
				/* Restricted zoom levels seem to be a major pain with OpenLayers, especially when
				   including arbitrary layers that allow different zoom ranges... So, we just allow
				   any zoom level usual services provide und use transparent tiles for levels we
				   don't support...
				*/
				numZoomLevels: 18,
				maxResolution: 156543.0339,
				maxExtent: [-20037508, -20037508, 20037508, 20037508],
				//restrictedExtent: bounds,
				controls : [
					new OpenLayers.Control.Navigation(),
					new OpenLayers.Control.ZoomPanel(),
					new OpenLayers.Control.LayerSwitcher({'ascending':false}),
					new OpenLayers.Control.Attribution(),
				]
			});
			$google_block
			$vector_block
			var mapnik = new OpenLayers.Layer.OSM();

			var osmarender = new OpenLayers.Layer.OSM(
				\"OpenStreetMap (Tiles@Home)\",
				\"http://tah.openstreetmap.org/Tiles/tile/\${z}/\${x}/\${y}.png\"
			);

			var hills = new OpenLayers.Layer.XYrZ( //FIXME our own version?
				\"Profile\",
				[ \"http://wanderreitkarte.de/hills/\${z}/\${x}/\${y}.png\", \"http://www.wanderreitkarte.de/hills/\${z}/\${x}/\${y}.png\"],
				9/*8*/, 15, OpenLayers.Util.Geograph.MISSING_TILE_URL,
				{
					attribution: 'H&ouml;hen: <a href=\"http://www.wanderreitkarte.de/\">Nops Wanderreitkarte</a> mit <a href=\"http://www.wanderreitkarte.de/licence_de.php\">CIAT-Daten</a>',
					sphericalMercator : true,
					isBaseLayer : false,
					visibility : false,
				}
			);

			var topobase = new OpenLayers.Layer.XYrZ(
				\"Nop's Wanderreitkarte\",
				[ \"http://base.wanderreitkarte.de/base/\${z}/\${x}/\${y}.png\", \"http://base2.wanderreitkarte.de/base/\${z}/\${x}/\${y}.png\"],
				4, 16, OpenLayers.Util.Geograph.MISSING_TILE_URL,
				{
					attribution: '&copy; <a href=\"http://www.wanderreitkarte.de/\">Nops Wanderreitkarte</a> (<a href=\"http://www.wanderreitkarte.de/licence_de.php\">CC</a>)',
					sphericalMercator : true,
					isBaseLayer : true,
				}
			);
			var topotrails = new OpenLayers.Layer.XYrZ(
				\"Nop's Wanderreitkarte (Wege)\",
				[ \"http://topo.wanderreitkarte.de/topo/\${z}/\${x}/\${y}.png\", \"http://topo2.wanderreitkarte.de/topo/\${z}/\${x}/\${y}.png\"],
				4, 16, OpenLayers.Util.Geograph.MISSING_TILE_URL,
				{
					attribution: '&copy; <a href=\"http://www.wanderreitkarte.de/\">Nops Wanderreitkarte</a> (<a href=\"http://www.wanderreitkarte.de/licence_de.php\">CC</a>)',
					sphericalMercator : true,
					isBaseLayer : false,
					visibility : false,
					displayInLayerSwitcher: false,
				}
			);
			map.events.register('changebaselayer', map, function(e) {
				/* Topographical map: always show trails layer */
				var showtopotrails = topobase == e.layer;//map.baselayer;
				if (topotrails.getVisibility() != showtopotrails)
					topotrails.setVisibility(showtopotrails);
			});

			initMarkersLayer();

			map.addLayers([
				mapnik, osmarender,
				topobase, topotrails,
				hills,
				$google_layers
				$vector_layer
				dragmarkers
			]);
			var dragFeature = new OpenLayers.Control.DragFeature(dragmarkers, {'onDrag': markerDrag, 'onComplete': markerCompleteDrag, 'documentDrag': true});
			map.addControl(dragFeature);
			dragFeature.activate();
			var point = new OpenLayers.LonLat({$this->long}, {$this->lat});
			map.setCenter(point.transform(epsg4326, map.getProjectionObject()), $zoom);
			$block
			// FIXME initial map type
		}

			AttachEvent(window,'load',loadmapO,false);
			var static_host = '{$CONF['STATIC_HOST']}';
				//]]>
				</script>";
		} else {
				$east = (floor($this->nateastings/1000) * 1000) + 500;
				$nort = (floor($this->natnorthings/1000) * 1000) + 500;
				$mericonv = $this->delmeri * sin(deg2rad($this->lat));// FIXME is lat always set?
			$str = "
			<script type=\"text/javascript\">
				var cene = {$east};
				var cenn = {$nort};
				var maph = {$this->width};
				var mapw = {$this->width};
				var rot  = {$mericonv};
				var ri   = {$this->reference_index};
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
		if ($this->service == 'Google' || $this->service == 'OLayers') {
			return '';
		} elseif ($this->service == 'WMS') { //FIXME id
			return "<span id=\"mapTitleVoB\">".$this->title."</span>";
		}
		return "<span id=\"mapTitleOS50k\"".($this->service == 'OS50k'?'':' style="display:none"').">1:50,000 Modern Day Landranger&trade; Map</span>".
		"<span id=\"mapTitleVoB\"".($this->service == 'VoB'?'':' style="display:none"').">1940s OS New Popular Edition".(($this->issubmit)?"<span style=\"font-size:0.8em;color:red\"><br/><b>Please confirm positions on the modern map, as accuracy may be limited.</b></span>":'')."</span>";
	}

	function getFootNote() 
	{
		if ($this->service == 'Google' || $this->service == 'OLayers') {
			return '';
		} elseif ($this->service == 'WMS') {
			if ($this->issubmit) {
				return '';#FIXME
			} else {
				return $this->footnote;#FIXME
			}
		} elseif ($this->issubmit) {
			return "<span id=\"mapFootNoteOS50k\"".(($this->service == 'OS50k' && $this->issubmit)?'':' style="display:none"')."><br/>Centre the blue circle on the subject and mark the photographer position with the black circle. <b style=\"color:red\">The circle centre marks the spot.</b> The red arrow will then show view direction.</span>".
			"<span id=\"mapFootNoteVoB\"".($this->service == 'VoB'?'':' style="display:none"')."><br/>Historical Map provided by <a href=\"http://www.visionofbritain.org.uk/\" title=\"Vision of Britain\">VisionOfBritain.org.uk</a></span>";
		} elseif ($this->service == 'OS50k') {
			if (!empty($this->clickable)) {
				return "<span id=\"mapFootNoteOS50k\">TIP: Click the map to open OS Get-a-Map</span><span id=\"mapFootNoteVoB\"></span>";
			} else {
				return "<span id=\"mapFootNoteOS50k\"".(($this->displayMarker1 || $this->displayMarker2)?'':' style="display:none"').">TIP: Hover over the icons to hide</span><span id=\"mapFootNoteVoB\"></span>";
			}
		}
	}

	function createTile($service,$path = null) {
		if ($service == 'OS50k') {
			return $this->combineTiles($this->square,$path);
		} elseif (preg_match('/OS50k-mapper\d?/',$service)) {
			return $this->combineTilesMapper($this->square,$path);
		} elseif ($service == 'OS50k-small') {
			if ($sourcepath = $this->getMapPath('OS50k',true)) {
				return $this->createSmallExtract($sourcepath,$path);
			} 
		} elseif ($service == 'OS250k-m40k') {
			return $this->combineTilesMapper($this->square,$path);
		} 
		return false;
	}
	
	function getMapPath($service,$create = true) {
		$path = $this->getOSGBStorePath($service);
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

			$cmd = sprintf('%s"%smontage" -geometry +0+0 %s -tile 3x3 png:- | "%sconvert" - -crop %ldx%ld+%ld+%ld +repage -thumbnail %ldx%ld -colors 128 -font "%s" -fill "#eeeeff" -draw "roundRectangle 6,230 155,243 3,3" -fill "#000066" -pointsize 10 -draw "text 10,240 \'© Crown Copyright %s\'" -colors 128 -depth 8 -type Palette png:%s', 
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
				
		$cmd = sprintf('%s"%sconvert" png:%s -gravity SouthWest -crop %ldx%ld+%ld+%ld +repage -crop %ldx%ld +repage -thumbnail %ldx%ld +repage -colors 128 -font "%s" -fill "#eeeeff" -draw "roundRectangle 13,114 112,130 3,3" -fill "#000066" -pointsize 10 -draw "text 14,123 \'© OSGB %s\'" -colors 128 -depth 8 -type Palette png:%s', 
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
			print "<pre>$cmd</pre>";

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
		$mappath = $this->getMapPath($this->service);

		if (!$mappath || !file_exists($mappath)) {
			$expires=strftime("%a, %d %b %Y %H:%M:%S GMT", time()+604800);
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

		customExpiresHeader(604800,true);
		
		header("Content-Type: image/png");
		
		$size=filesize($mappath);
		header("Content-Size: $size");
		header("Content-Length: $size");

		readfile($mappath);
	}
	
	/**
	* Return an opaque, url-safe token representing this map
	* @access public
	*/
	function getToken()
	{
		# FIXME ri?
		$token=new Token;
		$token->setValue("e", floor($this->nateastings /$this->divisor[$this->service]));
		$token->setValue("n", floor($this->natnorthings /$this->divisor[$this->service]));
		$token->setValue("s", $this->service);
		$token->setValue("i", $this->serviceid);
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
		# FIXME ri?
		$ok=false;
		$token=new Token;
		if ($token->parse($tokenstr))
		{
			$ok=$token->hasValue("e") &&
				$token->hasValue("n") &&
				$token->hasValue("s") &&
				$token->hasValue("i");
			if ($ok)
			{
				$this->serviceid = $token->getValue("i");
				$this->service = $token->getValue("s");
				$this->nateastings = $token->getValue("e") * $this->divisor[$this->service];
				$this->natnorthings = $token->getValue("n") * $this->divisor[$this->service];
				$this->width = $this->tilewidth[$this->service];
				
				if ($token->hasValue("r")) {
					$this->epoch = $token->getValue("r");
				}
				if ($this->serviceid >= 0) {
					foreach($CONF['mapservices'][$this->serviceid] as $name=>$value) // FIXME database?
					{
						if (!is_numeric($name))
							$this->$name=$value;
					}
					#$this->enabled = true;
					#$this->serviceid = $serviceid;
					if ($square->reference_index == 3) {
						$this->zone = 32;
					} elseif ($square->reference_index == 4) {
						$this->zone = 33;
					} elseif ($square->reference_index == 5) {
						$this->zone = 31;
					}
					if ($this->service == 'WMS') {
						if ($this->servicegk === false) {
							$this->delmeri = 0;
						} else {
							$this->delmeri = (2 * $this->zone - $this->servicegk - 61) * 3;
						}
					}
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
