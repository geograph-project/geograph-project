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

class kmlPlacemark_Photo extends kmlPlacemark {



	public function addPhotographerPoint($kmlPoint,$view_direction,$photographer = '') {
		
		//take a copy of the placemark
		$subjectPlacemark = clone $this;
		
		//make ourself a folder, and clear any options
		$this->tag = 'Folder';
		$this->items = array();
		$this->children = array();
		
		//set the minimum back
		$this->setItem('name',$subjectPlacemark->getItem('name'));
		if ($timestamp = $subjectPlacemark->getChild('TimeStamp')) {
			$this->addChild(clone $timestamp,'','TimeStamp');
		}
		if ($subjectPlacemark->issetItem('visibility')) {
			$this->setItem('visibility',$subjectPlacemark->getItem('visibility'));
		}
		
		//add the subject placemark
		$this->addChild($subjectPlacemark);
	
		//add the photographer placemark
		$photographerPlacemark = $this->addChild(new kmlPlacemark(null,'Photographer'));
		if (!empty($photographer)) {
			$photographerPlacemark->setItemCDATA('description',"($photographer)");
		}
		
		$sbjPoint = $subjectPlacemark->getChild('Point');
		
		//setup the sightline
		$MultiGeometry = $photographerPlacemark->addChild('MultiGeometry');
		$MultiGeometry->addChild($kmlPoint);
		$LineString = $MultiGeometry->addChild('LineString');
		$LineString->setItem('tessellate',1);
 		$LineString->setItem('altitudeMode','clampedToGround');
 		$LineString->setItem('coordinates',$sbjPoint->getItem('coordinates')." ".$kmlPoint->getItem('coordinates'));
 		
 		//seems a LookAt is required (but is nice to set the heading anyway!)
 		$LookAt = $photographerPlacemark->addChild('LookAt');
 		$this->addChild($LookAt);
 		$LookAt->setItem('longitude',$kmlPoint->lon);
		$LookAt->setItem('latitude',$kmlPoint->lat);
		$LookAt->setItem('tilt',70);
		$LookAt->setItem('range',1000);
		if (strlen($view_direction) && $view_direction != -1) {
			$LookAt->setItem('heading',$view_direction);
		} else {
			$LookAt->setItem('heading',$kmlPoint->calcHeadingToPoint($sbjPoint));
		}
		
		
		$Style = $photographerPlacemark->addChild('Style');
		$IconStyle = $Style->addChild('IconStyle');
		$IconStyle->setItem('scale',0.7);
		$Icon = $IconStyle->addChild('Icon');
		$Icon->setItem('href',"http://maps.google.com/mapfiles/kml/pal4/icon46.png");
		$LabelStyle = $Style->addChild('LabelStyle');
		$LabelStyle->setItem('scale',0);

		return $this;
	}
	
	public function addViewDirection($view_direction) {
		$LookAt = $this->addChild('LookAt');
		
		$sbjPoint = $this->getChild('Point');
		
		$LookAt->setItem('longitude',$sbjPoint->lon);
		$LookAt->setItem('latitude',$sbjPoint->lat);
		$LookAt->setItem('tilt',70);
		$LookAt->setItem('range',1000);
		if (strlen($view_direction) && $view_direction != -1) {
			$LookAt->setItem('heading',$view_direction);
		} else {
			$LookAt->setItem('heading',0);
		}
	}
}



?>