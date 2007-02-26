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
*
******/

class kmlPrimative {

	public $name = '';
	public $id = '';

	public $items = array();

	public $children = array();

	public function __construct($name,$id = '') {
		$this->name = $name;
		if (!empty($id)) {
			$this->id = $id;
		}
	}

	public function setItem($name,$value) {
		$this->items[$name] = $value;
	}

	public function getItem($name) {
		return $this->items[$name];
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

	public function toString($indent = 0) {
		$s = str_repeat("\t",$indent)."<{$this->name}";
		if (!empty($this->id)) {
			$s .= " id=\"{$this->id}\"";
		}
		$s .= ">\n";
		if (count($this->items)) {
			foreach ($this->items as $name => $value) {
				$s .= str_repeat("\t",$indent+1)."<$name>$value</$name>\n";
			}
		}
		if (count($this->children)) {
			foreach ($this->children as $id => $obj) {
				$s .= $obj->toString($indent+1);
			}
		}
		return $s.str_repeat("\t",$indent)."</{$this->name}>\n";
	}

}

/**************************************************
*
******/

class kmlFile extends kmlPrimative {

	public function __construct($id = '') {
		$this->contentType = "application/vnd.google-earth.kml+xml";
		$this->encoding = "utf-8";
		parent::__construct('kml',$id);
	}

	public function outputKML($sendheaders = true) {
		if (empty($this->id)) {
			$this->id = uniqid();
		}
		if (empty($this->filename)) {
			$this->filename = $this->id.".kml";
		}
		if ($sendheaders && !headers_sent()) {
			Header("Content-Type: ".$this->contentType."; charset=".$this->encoding."; filename=".basename($this->filename));
			Header("Content-Disposition: attachment; filename=".basename($this->filename));
		}

		echo $this->returnKML();
		return $this->filename;
	}

	function outputKMZ ($sendheaders = true) {
		$this->contentType = "application/vnd.google-earth.kmz+xml";

		if (empty($this->id)) {
			$this->id = uniqid();
		}
		if (empty($this->filename)) {
			$this->filename = $this->id.".kmz";
		}
		if ($sendheaders && !headers_sent()) {
			Header("Content-Type: ".$this->contentType."; charset=".$this->encoding."; filename=".basename($this->filename));
			Header("Content-Disposition: attachment; filename=".basename($this->filename));
		}

		$content = $this->returnKML();
		//todo zip it up
		echo $content;

		return $$this->filename;
	}

	public function returnKML() {
		if (empty($this->id)) {
			$this->id = uniqid();
		}

		$s = "<?xml version=\"1.0\" encoding=\"".$this->encoding."\"?>\n";

		$this->id .= "\" xmlns=\"http://earth.google.com/kml/2.0";

		return $s.parent::toString();
	}

}

/**************************************************
*
******/

class kmlDocument extends kmlPrimative {

	public function addHoverStyle($expandicon = true) {
		$Style = $this->addChild('Style','defaultIcon');
		$LabelStyle = $Style->addChild('LabelStyle');
		$LabelStyle->setItem('scale',0);

		$Style2 = $this->addChild('Style','hoverIcon');
		if ($expandicon) {
			$IconStyle = $Style2->addChild('IconStyle');
			$IconStyle->setItem('scale',2.1);
		}

		$StyleMap = $this->addChild('StyleMap','defaultStyle');
		$Pair = $StyleMap->addChild('Pair');
		$Pair->setItem('key','normal');
		$Pair->setItem('styleUrl','#defaultIcon');

		$Pair2 = $StyleMap->addChild('Pair');
		$Pair2->setItem('key','highlight');
		$Pair2->setItem('styleUrl','#hoverIcon');


	}

	public function __construct($id = '') {
		parent::__construct('Document',$id);
	}
}


/**************************************************
*
******/

class kmlPlacemark extends kmlPrimative {

	public function __construct($id,$itemname,$kmlPoint = null) {
		parent::__construct('Placemark',$id);
		$this->setItem('name',$itemname);
		if (is_object($kmlPoint)) {
			$this->addChild($kmlPoint,$id,'Point');
		}
	}

	public function useHoverStyle() {
		$this->setItem('styleUrl','#defaultStyle');
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

class kmlNetworkLink extends kmlPrimative {

	public function __construct($id,$itemname,$url = null) {
		parent::__construct('NetworkLink',$id);
		$this->setItem('name',$itemname);
		if (!empty($url)) {
			$this->useUrl($url);
		}
	}


	public function useUrl($url) {
		$Url = $this->addChild('Url');
		$Url->setItem('href',htmlspecialchars($url));
		return $this;
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
	}

	public function toString($indent = 0) {
		//make sure coordinates are uptodate
		$this->setItem('coordinates',"{$this->lon},{$this->lat},{$this->alt}");
		return parent::toString($indent);
	}
}


?>