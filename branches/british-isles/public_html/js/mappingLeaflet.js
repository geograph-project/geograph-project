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
 
 var pickupbox = null;
 
 var distance;
 
 function createMarker(point,picon) {
 	if (picon) {
		marker2 = L.marker(point, {icon: picon, draggable: true}).addTo(map);
 		var marker = marker2;
	} else {
		marker1 = L.marker(point, {draggable: true}).addTo(map);
 		var marker = marker1;
	}

	if (issubmit) { //todo
		google.maps.event.addListener(marker, "drag", function() {
			var grid=gmap2grid(marker.getPosition());
			
			//get a grid reference with 4 digits of precision
			var gridref = grid.getGridRef(4);

			if (picon) {
				eastings2 = grid.eastings;
				northings2 = grid.northings;
				document.theForm.photographer_gridref.value = gridref;
			} else {
				eastings1 = grid.eastings;
				northings1 = grid.northings;
				document.theForm.grid_reference.value = gridref;
			}  
			
			if (document.theForm.use6fig)
				document.theForm.use6fig.checked = true;
			
			if (eastings1 > 0 && eastings2 > 0 && pickupbox != null) {
				pickupbox.setMap(null);
				pickupbox = null;
			}
			
			updateViewDirection();
			
			if (typeof parentUpdateVariables != 'undefined') {
				parentUpdateVariables();
			}
		});
	} else {
		marker.on('dragend', function(e) {
			marker.setLatLng(point);
		});
	}
	return marker;
}

function createPMarker(ppoint) {
	var picon = L.icon({
	    iconUrl: static_host+"/img/icons/camicon-new.png",

	    iconSize:     [20, 34], // size of the icon
	    iconAnchor:   [10, 34], // point of the icon which will correspond to marker's location
	    popupAnchor:  [0, 0] // point from which the popup should open relative to the iconAnchor
	});
	return createMarker(ppoint,picon)
}

function gmap2grid(point) {
	//create a wgs84 coordinate
	wgs84=new GT_WGS84();
	wgs84.setDegrees(point.lat, point.lng);

	if (wgs84.isIreland2()) {
		//convert to Irish
		var grid=wgs84.getIrish(true);

	} else if (wgs84.isGreatBritain()) {
		//convert to OSGB
		var grid=wgs84.getOSGB();
	}
	return grid;
}


function checkFormSubmission(that_form,mapenabled) {

	if (that_form.elements['jpeg'] && that_form.elements['jpeg'].value && that_form.elements['jpeg'].value.length > 0 && !that_form.elements['jpeg'].value.match(/.jpe?g$/i)) {
		if (!confirm("The name of the file does not appear to have a .jpg extension. Note, we only accept JPEG images. If you beleive this file really is a JPEG image, and want to upload anyway, press OK. To select a different file click Cancel")) {
			return false;
		}
	}
	
	if (checkGridReferences(that_form)) {
		if (typeof distance != 'undefined' && distance > 10000) {
			message = "The apparent distance between subject and camera is "+distance+" metres, are you sure this is correct?";
			if (!confirm(message)) {
				return false;
			}
		}
		message = '';
		if (that_form.grid_reference.value == '' || that_form.grid_reference.value.length < 7) 
			message = message + "* Subject Grid Reference\n";
		if (that_form.photographer_gridref.value == '') 
			message = message + "* Camera Grid Reference\n";
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
	GridRef = /\b([a-zA-Z]{1,2}) ?(\d{2,5})[ \.]?(\d{2,5})\b/;
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
			alert("please enter a valid camera grid reference");
		}
		that.focus();
	}
	return ok;
}

String.prototype.trim = function () {
	return this.replace(/^\s+|\s+$/g,"");
}

function updateMapMarker(that,showmessage,dontcalcdirection) {
	if (!checkGridReference(that,showmessage)) {
		return false;
	}
	if (!document.getElementById('map') || !map) {
		//we have no map! so we only wanted to check the GR
		return;
	}
	
	if (that.name == 'photographer_gridref') {
		currentelement = marker2;
	} else {
		currentelement = marker1;
	}
	
	gridref = that.value.trim().toUpperCase().replace(/ /g,'');
	
	var grid=new GT_OSGB();
	var ok = false;
	if (grid.parseGridRef(gridref)) {
		ok = true;
	} else {
		grid=new GT_Irish();
		ok = grid.parseGridRef(gridref)
	}
	
	if (ok && gridref.length > 6) {
		if (gridref.length <= 8 && grid.eastings%100 == 0 && grid.northings%100 == 0) {
			grid.eastings = grid.eastings + 50;
			grid.northings = grid.northings + 50;
		} else if (gridref.length <= 10 && grid.eastings%10 == 0 && grid.northings%10 == 0) {
			grid.eastings = grid.eastings + 5;
			grid.northings = grid.northings + 5;
		}
		
		//convert to a wgs84 coordinate
		wgs84 = grid.getWGS84(true);

		//now work with wgs84.latitude and wgs84.longitude
		var point = new L.LatLng(wgs84.latitude,wgs84.longitude);

		if ((currentelement == null) && map) {
			currentelement = createMarker(point,null);

			//google.maps.event.trigger(currentelement,'drag');
		} else {
			currentelement.setLatLng(point);
		}

		if (that.name == 'photographer_gridref') {
			eastings2 = grid.eastings;
			northings2 = grid.northings;
		} else {
			eastings1 = grid.eastings;
			northings1 = grid.northings;
		}  

		if (!dontcalcdirection)
			updateViewDirection();
		
		if (eastings1 > 0 && eastings2 > 0 && pickupbox != null) {
			setTimeout(" if (pickupbox != null) {pickupbox.removeFrom(map);pickupbox = null;}",1000);
		}
		
		if (typeof parentUpdateVariables != 'undefined') {
			parentUpdateVariables();
		}
	}
}

function relocateMapToMarkers() {
	if (!marker1 && !marker2) {
		return;
	}
	var bounds = L.latLngBounds();
	
	if (marker1 && eastings1 > 0) {
		bounds.extend(marker1.getLatLng());
	}
	if (marker2 && eastings2 > 0) {
		bounds.extend(marker2.getLatLng());
	}
	
	map.fitBounds(bounds);
}

function updateViewDirection() {
	document.getElementById("dist_message").innerHTML = '';
	if (eastings1 > 0 && eastings2 > 0) {
		
		distance = Math.sqrt( Math.pow(eastings1 - eastings2,2) + Math.pow(northings1 - northings2,2) );
	
		if (distance > 14) {
			realangle = Math.atan2( eastings1 - eastings2, northings1 - northings2 ) / (Math.PI/180);

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

			if (distance < 100) {
				distance = Math.round(distance);
			} else if (distance < 1000) {
				distance = Math.round(distance/5)*5;
			} else{
				distance = Math.round(distance/50)*50;
			}
			document.getElementById("dist_message").innerHTML = "range about "+distance+" metres";
		}
	}
}

function updateCamIcon() {

}

	var baseMaps = {};
	var overlayMaps = {};

	function firstLetterToType(newtype) {
		var mapTypeId = 'OSM';

		//these are odd, but to maintain some compatiblity with GMaps version
                if (newtype == 's') {mapTypeId = 'Aerial Imagery'}
                if (newtype == 'h') {mapTypeId = 'Aerial Imagery'}
                if (newtype == 't') {mapTypeId = 'OSM Terrain'}
                if (newtype == 'n') {mapTypeId = 'Historic OS - GB 1920s';}
                if (newtype == 'i') {mapTypeId = 'Historic OS - Ireland';}
                if (newtype == 'o') {mapTypeId = 'OSM';}
                if (newtype == 'c') {mapTypeId = 'OSM Cycle';}
                if (newtype == 'p') {mapTypeId = 'OSM Terrain';}

		return mapTypeId;
	}

	function setupOSMTiles(map,mapTypeId) {

		var osmAttrib='Map data &copy; <a href="https://openstreetmap.org">OpenStreetMap</a> contributors';

		baseMaps['OSM'] = new L.TileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
			 {mapLetter: 'o', minZoom: 8, maxZoom: 18, attribution: osmAttrib});		

		baseMaps['OSM Cycle'] = new L.TileLayer('https://tile.thunderforest.com/cycle/{z}/{x}/{y}{r}.png?apikey=42a8aaad46fa4fd784104f2870221993', 
			{mapLetter: 'c', maxZoom: 18, attribution: '<a href=https://www.thunderforest.com/>thunderforest.com</a>, '+osmAttrib});

		baseMaps['OSM Terrain'] = new L.TileLayer('https://tile.thunderforest.com/landscape/{z}/{x}/{y}{r}.png?apikey=42a8aaad46fa4fd784104f2870221993', 
			{mapLetter: 't', maxZoom: 18, attribution: '<a href=https://www.thunderforest.com/>thunderforest.com</a>, '+osmAttrib});

		baseMaps['Aerial Imagery'] = new L.TileLayer('https://api.mapbox.com/styles/v1/geograph/cjh8zse9f2lq32spb7s5vmvbk/tiles/256/{z}/{x}/{y}?access_token={accessToken}', 
			{mapLetter: 'h', maxZoom: 18, attribution: 'Imagery &copy; <a href="https://www.mapbox.com/">Mapbox</a>',
				accessToken: 'pk.eyJ1IjoiZ2VvZ3JhcGgiLCJhIjoiY2lteXI3cmlpMDBmenY5bTF5dHFqMnh0NiJ9.sPXF2s1niWNNEfqGjs2HGw'});

		baseMaps['Historic OS - Ireland'] = new L.TileLayer('https://geo.nls.uk/maps/ireland/gsgs4136/{z}/{x}/{y}.png',
		        {mapLetter: 'i', tms: true, minZoom: 5, maxZoom: 15, attribution: 'Provided by <a href="https://geo.nls.uk/">NLS Geo</a>',
				bounds: [[51.371780, -10.810546], [55.422779, -5.262451]] });

		baseMaps['Historic OS - GB 1920s'] = new L.TileLayer('https://nls-0.tileserver.com/nls/{z}/{x}/{y}.jpg',
		        {mapLetter: 'n', minZoom: 1, maxZoom:18 , attribution: 'Provided by <a href="https://geo.nls.uk/">NLS Geo</a>',
				bounds: [[49.6, -12], [61.7, 3]] });

		if (mapTypeId && baseMaps[mapTypeId])
			map.addLayer(baseMaps[mapTypeId])
		else
			map.addLayer(baseMaps['OSM']);

		if (L.britishGrid) {
			overlayMaps['OSGB Grid'] = L.britishGrid({
				opacity: 0.6,
	        		color: '#00f',
				showSquareLabels: [100000,10000,100]
			}).addTo(map);
	
			overlayMaps['Irish Grid'] = L.irishGrid({
				opacity: 0.6,
				color: '#080',
				showSquareLabels: [100000,10000,100]
			}).addTo(map);
		}

		L.control.layers(baseMaps,overlayMaps).addTo(map);
	}

