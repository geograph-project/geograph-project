/**
 * $Project: GeoGraph $
 * $Id: mapping.js 3657 2007-08-09 18:12:09Z barry $
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
 
 var currentelement = null;
 
 var marker1 = null;
 var eastings1 = 0;
 var northings1 = 0;
 var marker2 = null;
 var eastings2 = 0;
 var northings2 = 0;
 var lat1 = 0;
 var lon1 = 0;
 var lat2 = 0;
 var lon2 = 0;
 
 var pickupbox = null;
 
 function createMarker(point,picon) {
 	if (picon) {
 		marker2 = new GMarker(point,{draggable: true, icon:picon});
 		var marker = marker2;
	} else {
		marker1 = new GMarker(point, {draggable: true});
		var marker = marker1;
	}
	if (issubmit) {
		GEvent.addListener(marker, "drag", function() {
			var pp = marker.getPoint();
			
			//create a wgs84 coordinate
			wgs84=new GT_WGS84();
			wgs84.setDegrees(pp.lat(), pp.lng());
			if (ri == -1||issubmit) {
			if (wgs84.isIreland()) {
				//convert to Irish
				var grid=wgs84.getIrish(true);
			
			} else if (wgs84.isGreatBritain()) {
				//convert to OSGB
				var grid=wgs84.getOSGB();
			} else if (wgs84.isGermany32()) {
				//convert to German
				var grid=wgs84.getGerman32();
			} else if (wgs84.isGermany33()) {
				//convert to German
				var grid=wgs84.getGerman33();
			} else if (wgs84.isGermany31()) {
				//convert to German
				var grid=wgs84.getGerman31();
			}
			}
			else if (ri == 1)
				var grid=wgs84.getOSGB();
			else if (ri == 2)
				var grid=wgs84.getIrish();
			else if (ri == 3)
				var grid=wgs84.getGerman32(true, false);
			else if (ri == 4)
				var grid=wgs84.getGerman33(true, false);
			else if (ri == 5)
				var grid=wgs84.getGerman31(true, false);
			
			//get a grid reference with 4 digits of precision
			var gridref = grid.getGridRef(4);

			if (picon) {
				lon2 = wgs84.longitude*Math.PI/180.;
				lat2 = wgs84.latitude*Math.PI/180.;
				eastings2 = grid.eastings;
				northings2 = grid.northings;
				document.theForm.photographer_gridref.value = gridref;
			} else {
				lon1 = pp.lng()*Math.PI/180.;
				lat1 = pp.lat()*Math.PI/180.;
				eastings1 = grid.eastings;
				northings1 = grid.northings;
				document.theForm.grid_reference.value = gridref;
			}  
			
			//if (document.theForm.use6fig)
			//	document.theForm.use6fig.checked = true;
			
			if (eastings1 > 0 && eastings2 > 0 && pickupbox != null) {
				map.removeOverlay(pickupbox);
				pickupbox = null;
			}
			
			updateViewDirection();
			
			if (typeof parentUpdateVariables != 'undefined') {
				parentUpdateVariables();
			}
		});
	} else {
		GEvent.addListener(marker, "dragend", function() {
			marker.setPoint(point);
		});
	}
	return marker;
}

function createPMarker(ppoint) {
	var picon = new GIcon();
	picon.image = "http://"+static_host+"/img/icons/camicon.png";
	picon.shadow = "http://"+static_host+"/img/icons/cam-s.png";
	picon.iconSize = new GSize(20, 34);
	picon.shadowSize = new GSize(37, 34);
	picon.iconAnchor = new GPoint(10, 34);
	return createMarker(ppoint,picon)
}




function checkFormSubmission(that_form,mapenabled) {
	if (checkGridReferences(that_form)) {
		message = '';
		if (that_form.grid_reference.value == '' || that_form.grid_reference.value.length < 7) 
			message = message + "* Subject Grid Reference\n";
		if (that_form.photographer_gridref.value == '') 
			message = message + "* Photographer Grid Reference\n";
		if (that_form.view_direction.selectedIndex == 0) 
			message = message + "* View Direction\n";
		if (message.length > 0) {
			message = "We notice that the following fields have been left blank:\n\n" + message;
			message = message + "\nWhile you can continue without providing this information we would appreciate including as much detail as possible as it will make plotting the photo on a map much easier.\n\n";
			if (mapenabled) {
				message = message + "Adding the missing information should be very quick by dragging the icons on the map.\n\n";
			}
			message = message + "Click OK to add the information, or Cancel to continue anyway.";
			return !confirm(message);
		}
		return true;
	} else {
		return false;
	}
}

function checkGridReferences(that_form) {
	if (!checkGridReference(that_form.grid_reference,true)) 
		return false;
	if (!checkGridReference(that_form.photographer_gridref,true)) 
		return false;
	return true;

} 

function checkGridReference(that,showmessage) {
	GridRef = /\b([a-zA-Z]{1,3}) ?(\d{2,5})[ \.]?(\d{2,5})\b/;
	ok = true;
	if (that.value.length > 0) {
		myArray = GridRef.exec(that.value); 
		if (myArray && myArray.length > 0) {
			numbers = myArray[2]+myArray[3];
			if (numbers.length == 0 || numbers.length % 2 != 0) {
				ok = false;
			}
		} else {
			ok = false;
		}
	}
	if (ok == false && showmessage) {
		if (that.name == 'grid_reference') {
			alert("please enter a valid subject grid reference");
		} else {
			alert("please enter a valid photographer grid reference");
		}
		that.focus();
	}
	return ok;
}

String.prototype.trim = function () {
	return this.replace(/^\s+|\s+$/g,"");
}

/*function getMapCenter() {
	latlon = map.getCenter();
}*/
function mapMarkerToCenter(that) {
	latlon = map.getCenter();
	if (that.name == 'photographer_gridref') {
		currentelement = marker2;
	} else {
		currentelement = marker1;
	}
	currentelement.setPoint(latlon);
	GEvent.trigger(currentelement,'drag');
}


function updateMapMarker(that,showmessage,dontcalcdirection) {
	if (!checkGridReference(that,showmessage)) {
		return false;
	}
	if (!document.getElementById('map')) {
		//we have no map! so we only wanted to check the GR
		return;
	}
	
	if (that.name == 'photographer_gridref') {
		currentelement = marker2;
	} else {
		currentelement = marker1;
	}
	
	gridref = that.value.trim().toUpperCase();
	var grid;
	var ok = false;
	
	if (ri == -1 || issubmit) {
	
	grid=new GT_OSGB();
	if (grid.parseGridRef(gridref)) {
		ok = true;
	} else {
		grid=new GT_Irish();
		if (grid.parseGridRef(gridref)) {
			ok = true;
		} else {
			grid=new GT_German32();
			if (grid.parseGridRef(gridref)) {
				ok = true;
			} else {
				grid=new GT_German33();
				if (grid.parseGridRef(gridref)) {
					ok = true;
				} else {
					grid=new GT_German31();
					ok = grid.parseGridRef(gridref)
				}
			}
		}
	}
	}
	else if (ri == 1)
		grid=new GT_OSGB();
	else if (ri == 2)
		grid=new GT_Irish();
	else if (ri == 3)
		grid=new GT_German32();
	else if (ri == 4)
		grid=new GT_German33();
	else if (ri == 5)
		grid=new GT_German31();
	else
		return;
	ok = grid.parseGridRef(gridref); // FIXME needed?
	
	if (ok) {
		//convert to a wgs84 coordinate
		wgs84 = grid.getWGS84(true);

		//now work with wgs84.latitude and wgs84.longitude
		var point = new GLatLng(wgs84.latitude,wgs84.longitude);

		if (currentelement == null && map) {
			currentelement = createMarker(point,null);
			map.addOverlay(currentelement);

			GEvent.trigger(currentelement,'drag');
		}
		currentelement.setPoint(point);

		if (that.name == 'photographer_gridref') {
			lon2 = wgs84.longitude*Math.PI/180.;
			lat2 = wgs84.latitude*Math.PI/180.;
			eastings2 = grid.eastings;
			northings2 = grid.northings;
		} else {
			lon1 = wgs84.longitude*Math.PI/180.;
			lat1 = wgs84.latitude*Math.PI/180.;
			eastings1 = grid.eastings;
			northings1 = grid.northings;
		}  

		if (!dontcalcdirection)
			updateViewDirection();
		
		if (eastings1 > 0 && eastings2 > 0 && pickupbox != null) {
			setTimeout(" if (pickupbox != null) {map.removeOverlay(pickupbox);pickupbox = null;}",1000);
		}
		
		if (typeof parentUpdateVariables != 'undefined') {
			parentUpdateVariables();
		}
	}
}

function updateViewDirection() {
	if (eastings1 > 0 && eastings2 > 0) {
		arc = Math.acos(Math.sin(lat1)*Math.sin(lat2) + Math.cos(lat1)*Math.cos(lat2)*Math.cos(lon2-lon1));
		distance = arc*6378137;
		//distance = Math.sqrt( Math.pow(eastings1 - eastings2,2) + Math.pow(northings1 - northings2,2) );
	
		if (distance > 14) {
			//realangle = Math.atan2( eastings1 - eastings2, northings1 - northings2 ) / (Math.PI/180);
			realangle = Math.acos((Math.sin(lat1)-Math.sin(lat2)*Math.cos(arc)) / (Math.cos(lat2)*Math.sin(arc)));
			realangle *= 180./Math.PI;

			if (realangle < 0)
				realangle = realangle + 360.0;

			jump = 360.0/16.0;

			newangle = Math.floor(Math.round(realangle/jump)*jump);
			if (newangle == 360)
				newangle = 0;

			var ele = document.theForm.view_direction;
			for(q=0;q<ele.options.length;q++)
				if (ele.options[q].value == newangle)
					ele.selectedIndex = q;

		}
	}
}

function updateCamIcon() {

}
