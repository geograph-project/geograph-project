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
 var fracGold = 0.5*Math.sqrt(5.0) - 0.5;

 var pickupbox = null;
 var squarebox = null;
 var sboxeast = null;
 var sboxnorth = null;
 var sboxwidth = null;
 
 function createMarker(point,picon) {
 	if (picon) {
 		marker2 = new GMarker(point,{draggable: !iscmap, icon:picon});
 		//marker2 = new GMarker(point,{draggable: true, icon:picon});
 		var marker = marker2;
	} else {
		marker1 = new GMarker(point, {draggable: true});
		var marker = marker1;
	}
	if (issubmit) {
		if (typeof nolineslayer === 'undefined') {
			nolineslayer = false;
		}
		GEvent.addListener(marker, "drag", function() {
			var pp = marker.getPoint();
			
			//create a wgs84 coordinate
			wgs84=new GT_WGS84();
			wgs84.setDegrees(pp.lat(), pp.lng());
			if (ri == -1||issubmit) {
			if (wgs84.isAustria32()) {
				//convert to Austrian
				var grid=wgs84.getAustrian32();
			} else if (wgs84.isAustria33()) {
				//convert to Austrian
				var grid=wgs84.getAustrian33();
			}
			}
			else if (ri == 6)
				var grid=wgs84.getAustrian32(true, false);
			else if (ri == 7)
				var grid=wgs84.getAustrian33(true, false);

			var curzoom = map.getZoom();
			if (curzoom >= 19) {
				var newdigits = 5;
				var newprec = 1;
			} else if (curzoom >= 15) {
				var newdigits = 4;
				var newprec = 10;
			} else if (curzoom >= 12) {
				var newdigits = 3;
				var newprec = 100;
			} else if (curzoom >= 9) {
				var newdigits = 2;
				var newprec = 1000;
			} else {
				var newdigits = 2;
				var newprec = 0;
			}

			//get a grid reference with the given precision
			var gridref = grid.getGridRef(newdigits);

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


			if (newprec) {
				var neweast = Math.floor(grid.eastings/newprec);
				var newnorth = Math.floor(grid.northings/newprec);
				if (squarebox !== null && (neweast != sboxeast || newnorth != sboxnorth || newprec != sboxwidth)) {
					map.removeOverlay(squarebox);
					squarebox = null;
				}
				if (squarebox === null && !nolineslayer) {
					sboxeast = neweast;
					sboxnorth = newnorth;
					sboxwidth = newprec;
					grid.setGridCoordinates( sboxeast   *newprec,  sboxnorth   *newprec);
					var ll1 = grid.getWGS84(true);
					grid.setGridCoordinates((sboxeast+1)*newprec,  sboxnorth   *newprec);
					var ll2 = grid.getWGS84(true);
					grid.setGridCoordinates((sboxeast+1)*newprec, (sboxnorth+1)*newprec);
					var ll3 = grid.getWGS84(true);
					grid.setGridCoordinates( sboxeast   *newprec, (sboxnorth+1)*newprec);
					var ll4 = grid.getWGS84(true);
					squarebox = new GPolygon(
						[
							new GLatLng(ll1.latitude, ll1.longitude),
							new GLatLng(ll2.latitude, ll2.longitude),
							new GLatLng(ll3.latitude, ll3.longitude),
							new GLatLng(ll4.latitude, ll4.longitude),
							new GLatLng(ll1.latitude, ll1.longitude)
						], "#FFFFFF", 1, 0.5, "#808080", 0.5);
					map.addOverlay(squarebox);
				}
			} else if (squarebox !== null) {
				map.removeOverlay(squarebox);
				squarebox = null;
			}
			
			//if (document.theForm.use6fig)
			//	document.theForm.use6fig.checked = true;
			
			if (eastings1 > 0 && eastings2 > 0 && pickupbox != null) {
				map.removeOverlay(pickupbox);
				pickupbox = null;
			}
			
			if (!iscmap) {
				updateViewDirection();
			}
			
			if (typeof parentUpdateVariables != 'undefined') {
				parentUpdateVariables();
			}
		});
		GEvent.addListener(marker, "dragend", function() {
			if (squarebox !== null) {
				map.removeOverlay(squarebox);
				squarebox = null;
			}
			GEvent.trigger(map,'markerdragend');
		});
		/*GEvent.addListener(map, "zoomend", function() {
			if (squarebox !== null && map.getZoom() < 17) {
				map.removeOverlay(squarebox);
				squarebox = null;
			}
		});*/
	} else {
		GEvent.addListener(marker, "dragend", function() {
			marker.setPoint(point);
		});
	}
	return marker;
}

function floathash(s) { /* string must not be empty! */
	var res = 1.0;
	var len = s.length;
	for (var i = 0; i < len; ++i) {
		res *= s.charCodeAt(i)*fracGold;
		res -= Math.floor(res);
	}
	return res;
}

function inthash(s, n) {
	return Math.floor(floathash(s)*n);
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
		grid=new GT_Austrian32();
		if (grid.parseGridRef(gridref)) {
			ok = true;
		} else {
			grid=new GT_Austrian33();
			ok = grid.parseGridRef(gridref)
		}
	} else {
		if (ri == 6)
			grid=new GT_Austrian32();
		else if (ri == 7)
			grid=new GT_Austrian33();
		else
			return;
		ok = grid.parseGridRef(gridref);
	}
	
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
		GEvent.trigger(map,'markerdragend');
	}
}

function updateViewDirection() {
	if (eastings1 > 0 && eastings2 > 0) {
		//distance = Math.sqrt( Math.pow(eastings1 - eastings2,2) + Math.pow(northings1 - northings2,2) );
		R = 6378137.0;
		dlat = lat1-lat2;
		dlon = lon1-lon2;
		slat = Math.sin(0.5*dlat);
		slon = Math.sin(0.5*dlon);
		sinsq = slat*slat + Math.cos(lat1)*Math.cos(lat2)*slon*slon;
		arc = 2 * Math.atan2(Math.sqrt(sinsq), Math.sqrt(1-sinsq));
		distance = R * arc;
		mindist = map.getZoom() >= 19 ? 3 : 14;
	
		if (distance > mindist) {
			//realangle = Math.atan2( eastings1 - eastings2, northings1 - northings2 ) / (Math.PI/180);
			y = Math.sin(dlon)*Math.cos(lat1);
			x = Math.cos(lat2)*Math.sin(lat1) - Math.sin(lat2)*Math.cos(lat1)*Math.cos(dlon);
			realangle = Math.atan2(y, x);
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

function moveToLatLon(lat, lon) {
	var point = new GLatLng(lat,lon);
	map.setCenter(point);
}
