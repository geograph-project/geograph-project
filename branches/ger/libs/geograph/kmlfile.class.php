<?php

/**
 * $Project: GeoGraph $
 * $Id: functions.inc.php 2911 2007-01-11 17:37:55Z barry $
 *
 * GeoGraph geographic photo archive project
 * http://geograph.sourceforge.net/
 *
 * This file copyright (C) 2007 Barry Hunter (geo@barryhunter.co.uk)
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

/**************************************************
* WARNING: Designed for PHP5 - not php4 compatible
******/

class kmlPrimative {

	public $tag = '';
	public $id = '';

	public $values = array();

	public $items = array();

	public $children = array();

	public function __construct($tag,$id = '') {
		$this->tag = $tag;
		if (!empty($id)) {
			$this->values['id'] = $id;
			$this->id = $id;
		}
	}

	public function setItem($name,$value,$raw = false) {
		if ($raw) {
			$this->items[$name] = $value;
		} else {
			$this->items[$name] = utf8_encode(htmlnumericentities($value));
		}
		return $this;
	}

	public function setItemCDATA($name,$value) {
		$this->items[$name] = utf8_encode("<![CDATA[$value]]>");
		return $this;
	}

	public function getItem($name) {
		return $this->items[$name];
	}

	public function issetItem($name) {
		return isset($this->items[$name]);
	}

	public function addChild($obj = null,$id = '',$ref = '') {
		if (!is_object($obj)) {
			if (class_exists('kml'.$obj,false)) {
				$classname = 'kml'.$obj;
				$obj = new $classname($id);
			} else {
				$obj = new kmlPrimative($obj,$id);
			}
		}
		if (!empty($ref)) {
			$this->children[$ref] = $obj;
		} else {
			$this->children[] = $obj;
		}
		return $obj;
	}

	public function getChild($ref) {
		return $this->children[$ref];
	}

	public function unsetChild($ref) {
		if (isset($this->children[$ref])) {
			$d = $this->children[$ref];
			unset($this->children[$ref]);
			return $d;
		}
	}

	public function setTimeStamp($when) {
		$this->addChild('TimeStamp','','TimeStamp')->setItem('when',$when);
		return $this;
	}
	
	public function useCredit($author,$link = '') {
		if (!empty($author)) {
			$this->addChild('atom:author','','atom:author')->setItem('atom:name',$author);
		}
		if (!empty($link)) {
			$this->link = $link;
		}
	}

	public function toString($indent = 0,$prettyprint = true) {
		if ($prettyprint) {
			$s = str_repeat("\t",$indent)."<{$this->tag}";
			if (count($this->values)) {
				foreach ($this->values as $name => $value) {
					$s .= " $name=\"$value\"";
				}
			}
			$s .= ">\n";
			if (count($this->items)) {
				foreach ($this->items as $name => $value) {
					$s .= str_repeat("\t",$indent+1)."<$name>$value</$name>\n";
				}
			}
			if (!empty($this->link)) {
				$s .= str_repeat("\t",$indent+1)."<atom:link href=\"{$this->link}\" />\n";
			}
			if (count($this->children)) {
				foreach ($this->children as $id => $obj) {
					$s .= $obj->toString($indent+1);
				}
			}
			return $s.str_repeat("\t",$indent)."</{$this->tag}>\n";
		} else {
			$s = "<{$this->tag}";
			if (count($this->values)) {
				foreach ($this->values as $name => $value) {
					$s .= " $name=\"$value\"";
				}
			}
			$s .= ">";
			if (count($this->items)) {
				foreach ($this->items as $name => $value) {
					$s .= "<$name>$value</$name>";
				}
			}
			if (!empty($this->link)) {
				$s .= "<atom:link href=\"{$this->link}\" />\n";
			}
			if (count($this->children)) {
				foreach ($this->children as $id => $obj) {
					$s .= $obj->toString($indent+1,$prettyprint);
				}
			}
			return $s."</{$this->tag}>";
		}
	}

}

/**************************************************
*
******/

class kmlFile extends kmlPrimative {
	var $extension = 'kmz';
	var $version = '2.0';
	
	public function __construct($id = '') {
		$this->contentType = "application/vnd.google-earth.kml+xml";
		$this->encoding = "utf-8";
		parent::__construct('kml',$id);
	}
	
	public function setHint($target='sky') {
		if ($mode=='sky') {
			$this->values['hint'] = "target=sky";
			$this->version = "2.2";
		} elseif (isset($this->values['hint'])) {
			unset($this->values['hint']);
		}
	}
	
	public function outputFile($extension='',$sendheaders = true,$diskfile = '') {
		if (!empty($extension)) {
			$this->extension = $extension;
		}
		if ($this->extension == 'kml') {
			return $this->outputKML($sendheaders,$diskfile);
		} else {
			return $this->outputKMZ($sendheaders,$diskfile);
		}
	}

	
	public function outputKML($sendheaders = true,$diskfile = '') {
		if (empty($this->filename)) {
			$this->filename = uniqid().".kml";
		}
		$content =& $this->returnKML();
		if ($sendheaders && !headers_sent()) {
			Header("Content-Type: ".$this->contentType."; charset=".$this->encoding."; filename=".basename($this->filename));
			Header("Content-Disposition: attachment; filename=".basename($this->filename));
			header("Content-Length: ".strlen($content));
		}
		if (empty($diskfile)) {
			echo $content;
		} else {
			file_put_contents($diskfile,$content);
		}
		return $this->filename;
	}

	function outputKMZ($sendheaders = true,$diskfile = '') {
		$this->contentType = "application/vnd.google-earth.kmz+xml";

		if (empty($this->filename)) {
			$this->filename = uniqid().".kmz";
		}
		
		$content = $this->returnKML(false);

		include("zip.class.php");
		
		$zipfile = new zipfile();   
		
		// add the binary data stored in the string 'content' 
		$zipfile -> addFile($content, "doc.kml");   
		
		$content =& $zipfile->file();
		
		if ($sendheaders && !headers_sent()) {
			Header("Content-Type: ".$this->contentType."; charset=".$this->encoding."; filename=".basename($this->filename));
			Header("Content-Disposition: attachment; filename=".basename($this->filename));
			header("Content-Length: ".strlen($content));
		}
		
		if (empty($diskfile)) {
			echo $content;
		} else {
			file_put_contents($diskfile,$content);
		}
		
		return $this->filename;
	}

	public function returnKML($prettyprint = true) {
		$s = "<?xml version=\"1.0\" encoding=\"".$this->encoding."\"?>\n";

		if (!empty($this->atom)) {
			$this->values['xmlns:atom'] .= "http://www.w3.org/2005/Atom";
			$this->version = 2.2;
		}
	
		$this->values['xmlns'] = "http://earth.google.com/kml/".$this->version;
	
		return $s.parent::toString(0,$prettyprint);
	}

}

/**************************************************
*
******/

class kmlDocument extends kmlPrimative {

	public function __construct($id = '') {
		parent::__construct('Document',$id);
	}

	public function addHoverStyle($id='def',$iconScale = 1,$hoverScale=2.1,$icons='',$iconprefix = 'http://maps.google.com/mapfiles/kml/') {
		if ($icons) {
			switch ($icons) {
				case 'photo': $normal = 'pal4/icon46.png';$hover = 'pal4/icon38.png'; break;
				case 'multi': $normal = 'pal3/icon40.png';$hover = 'pal3/icon32.png'; break;
				default: list($normal,$hover) = explode(';',$icons); break;
			}
		}


		$Style = $this->addChild('Style','n'.$id);
		$Style->addChild('LabelStyle')->setItem('scale',0);
		if ($iconScale != 1 || $normal) {
			$IconStyle = $Style->addChild('IconStyle');
			if ($iconScale != 1)
				$IconStyle->setItem('scale',$iconScale);
			if ($normal)
				$IconStyle->addChild('Icon')->setItem('href',$iconprefix.$normal);
		}

		$Style2 = $this->addChild('Style','h'.$id);
		$IconStyle2 = $Style2->addChild('IconStyle');
		if ($iconScale != 1 || $hoverScale || $hover) {
			if ($iconScale != 1 || $hoverScale) 
				$IconStyle2->setItem('scale',$hoverScale*$iconScale);
			if ($hover)
				$IconStyle2->addChild('Icon')->setItem('href',$iconprefix.$hover);
		}

		$StyleMap = $this->addChild('StyleMap',$id);
		$Pair = $StyleMap->addChild('Pair');
		$Pair->setItem('key','normal');
		$Pair->setItem('styleUrl','#n'.$id);

		$Pair2 = $StyleMap->addChild('Pair');
		$Pair2->setItem('key','highlight');
		$Pair2->setItem('styleUrl','#h'.$id);

		return $this;
	}
}

class kmlFolder extends kmlDocument {
	public function __construct($id = '') {
		parent::__construct($id);
		$this->tag = 'Folder';
	}
}

/**************************************************
*
******/

class kmlPlacemark extends kmlPrimative {

	public function __construct($id,$itemname = '',$kmlPoint = null) {
		parent::__construct('Placemark',$id);
		if (!empty($itemname)) {
			$this->setItem('name',$itemname);
		}
		if (is_object($kmlPoint)) {
			$this->addChild($kmlPoint,$id,'Point');
		}
	}

	public function useHoverStyle($id='def') {
		//todo a bodge - but can't think of a better way
		$prefix = empty($GLOBALS['stylefile'])?'':$GLOBALS['stylefile'];
		$this->setItem('styleUrl',$prefix.'#'.$id);
		return $this;
	}

	public function useImageAsIcon($url) {
		$Style = $this->addChild('Style');
		$IconStyle = $Style->addChild('IconStyle');
		$Icon = $IconStyle->addChild('Icon');
		$Icon->setItem('href',htmlspecialchars($url));
		return $this;
	}

	public function makeFloating() {
		$this->getChild('Point')->makeFloating();
		return $this;
	}
}

/**************************************************
*
******/

class kmlPhotoOverlay extends kmlPlacemark {

	public function __construct($id,$itemname = '',$kmlPoint = null) {
		parent::__construct($id,$itemname,$kmlPoint);
		$this->tag = 'PhotoOverlay';
	}

	public function setPhoto($url,$shape = 'rectangle') {
		$Icon = $this->addChild('Icon');
		$Icon->setItem('href',$url);
		
		$this->setItem('shape',$shape);
	}
	
	public function setViewVolume1($near=1000,$horzFov = 60,$vertFov = 40) {
		$ViewVolume = $this->addChild('ViewVolume');
		$ViewVolume->setItem('near',$near);
		$ViewVolume->setItem('leftFov',-$horzFov);
		$ViewVolume->setItem('rightFov',$horzFov);
		$ViewVolume->setItem('bottomFov',-$vertFov);
		$ViewVolume->setItem('topFov',$vertFov);
	}
	
	
	
}

/**************************************************
*
******/

class kmlNetworkLink extends kmlPrimative {

	public function __construct($id,$itemname = '',$url = null) {
		parent::__construct('NetworkLink',$id);
		if (!empty($itemname)) {
			$this->setItem('name',$itemname);
		}
		if (!empty($url)) {
			$this->useUrl($url);
		}
	}


	public function useUrl($url) {
		$Url = $this->addChild('Url','','Url');
		$Url->setItem('href',$url);
		return $Url;
	}
}

/**************************************************
*
******/

class kmlPoint extends kmlPrimative {

	public function __construct($lat=0,$lon=0,$alt=0) {
		parent::__construct('Point');
		$this->setItem('coordinates',"$lon,$lat,$alt");
		$this->lat = $lat;
		$this->lon = $lon;
		$this->alt = $alt;
	}

 	public function makeFloating() {
 		$point = $this->getChild('Point');
 		$this->setItem('extrude',1);
 		$this->setItem('altitudeMode','relativeToGround');
 		$this->alt = 125;
 		return $this;
	}

	public function toString($indent = 0,$prettyprint = true) {
		//make sure coordinates are uptodate
		$this->setItem('coordinates',"{$this->lon},{$this->lat},{$this->alt}");
		return parent::toString($indent,$prettyprint);
	}

	function calcHeadingToPoint($p2) {
		$p2lon = deg2rad($p2->lon);
		$p2lat = deg2rad($p2->lat);
		$p1lon = deg2rad($this->lon);
		$p1lat = deg2rad($this->lat);

		$y = sin($p2lon-$p1lon) * cos($p2lat);
		$x = cos($p1lat)*sin($p2lat) - sin($p1lat)*cos($p2lat)*cos($p2lon-$p1lon);
		return rad2deg(atan2($y, $x));
	}
}

/**************************************************
*
******/

class kmlCamera extends kmlPrimative {

	public function __construct($lat=0,$lon=0,$alt=0,$heading=0,$tilt=0,$roll=0) {
		parent::__construct('Camera');
		$this->setItem('longitude',$lon);
		$this->setItem('latitude',$lat);
		$this->setItem('altitude',$alt);
		$this->lat = $lat;
		$this->lon = $lon;
		$this->alt = $alt;
		$this->setItem('heading',$heading);
		$this->setItem('tilt',$tilt);
		$this->setItem('roll',$roll);
	}
}


/**************************************************
*
******/

class kmlRegion extends kmlPrimative {

	public function __construct() {
		parent::__construct('Region');
	}

	public function setBoundary($north,$south,$east,$west) {
		$LatLonAltBox = $this->addChild('LatLonAltBox','','LatLonAltBox');
		$LatLonAltBox->setItem('north',$north);
		$LatLonAltBox->setItem('south',$south);
		$LatLonAltBox->setItem('east',$east);
		$LatLonAltBox->setItem('west',$west);
	}

	public function setPoint($kmlPoint,$d = 0.0001) {
		$LatLonAltBox = $this->addChild('LatLonAltBox','','LatLonAltBox');
		$LatLonAltBox->setItem('north',$kmlPoint->lat+$d);
		$LatLonAltBox->setItem('south',$kmlPoint->lat-$d);
		$LatLonAltBox->setItem('east',$kmlPoint->lon+$d);
		$LatLonAltBox->setItem('west',$kmlPoint->lon-$d);
	}

	public function setLod($min,$max = -1) {
		$Lod = $this->addChild('Lod','','Lod');
		if ($min != 0)
			$Lod->setItem('minLodPixels',$min);
		if ($max != -1)
			$Lod->setItem('maxLodPixels',$max);
	}

	public function setFadeExtent($min,$max = 0) {
		$Lod = $this->getChild('Lod');
		if ($min != 0)
			$Lod->setItem('minFadeExtent',$min);
		if ($max != 0)
			$Lod->setItem('maxFadeExtent',$max);
	}
}

?>