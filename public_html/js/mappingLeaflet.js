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
 var logodiv = null;

 var distance;
 var checkedonce = false;

 function createMarker(point,picon) {
 	if (picon) {
		marker2 = L.marker(point, {icon: picon, draggable: true, riseOnHover:true}).addTo(map);
 		var marker = marker2;
	} else {
		var sicon = L.icon({
		    iconUrl: static_host+"/img/icons/circle.png",

		    iconSize:     [29, 29], // size of the icon
		    iconAnchor:   [14, 14], // point of the icon which will correspond to marker's location
		    popupAnchor:  [14, 0] // point from which the popup should open relative to the iconAnchor
		});
		marker1 = L.marker(point, {icon: sicon, draggable: true, riseOnHover:true}).addTo(map);
 		var marker = marker1;
	}

	if (issubmit) {
		marker.on('drag', function(e) {
			var grid=gmap2grid(marker.getLatLng());

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

			if (document.theForm.use6fig && !document.theForm.use6fig.checked && !checkedonce) {
				var z=14; //zoom level on normal web tile maps.
				if (map.options && map.options.crs && map.options.crs.code && map.options.crs.code == "EPSG:27700") //the OS maps use a differet CRS, with differnt zooms
					z = 7;
				if (map.getZoom() <= z) {
					document.theForm.use6fig.checked = true;
					checkedonce = true;
				}
			}

			if (eastings1 > 0 && eastings2 > 0 && pickupbox != null) {
				pickupbox.remove();
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
	    iconUrl: static_host+"/img/icons/viewc--1.png",

	    iconSize:     [29, 29], // size of the icon
	    iconAnchor:   [14, 14], // point of the icon which will correspond to marker's location
	    popupAnchor:  [14, 0] // point from which the popup should open relative to the iconAnchor
	});
	return createMarker(ppoint,picon)
}

function mapdragend(e) {
	if (pickupbox) {
		var height = document.getElementById('map').clientHeight;
		var toplef = map.containerPointToLatLng([10,height-125]);
		var botrig = map.containerPointToLatLng([48,height-55]);
		pickupbox.setLatLngs([
			[toplef.lat,toplef.lng],
			[toplef.lat,botrig.lng],
			[botrig.lat,botrig.lng],
			[botrig.lat,toplef.lng],
			[toplef.lat,toplef.lng]
		]);
		pickupbox.redraw();

		if (document.theForm.grid_reference.value == '' || document.theForm.grid_reference.value.replace(/ /g,'').length <=6) //to exclude 4fig subject
			marker1.setLatLng( map.containerPointToLatLng([30,height-105]) );

		if (document.theForm.photographer_gridref.value == '')
			marker2.setLatLng( map.containerPointToLatLng([30,height-75]) );
	}
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

                        replaceIcon('camicon',static_host+"/img/icons/viewc-"+parseInt(newangle,10)+".png");
                        if (document.theForm.photographer_gridref.value == '')
                                replaceIcon('subicon',static_host+"/img/icons/subc-"+parseInt(newangle,10)+".png");
                        else
                                replaceIcon('subicon',static_host+"/img/icons/circle.png");

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

//while there is a 'setIcon' for markers, there isnt a getIcon, so modifing is tricky, so we search the DOM!
function replaceIcon(name,newSrc) {
        if (name == 'camicon') {
                var re = new RegExp("icons\/viewc");
        } else {
                var re = new RegExp("icons\/(subc|circle)");
        }
        for(var q=0;q<document.images.length;q++)
                if (document.images[q].src.match(re))
                        document.images[q].src = newSrc;
}


function updateCamIcon() {
        if (!document.getElementById('map')) {
                //we have no map!
                return;
        }
        ele = document.theForm.view_direction;
        realangle = ele.options[ele.selectedIndex].value;
        if (realangle == -1) {
                replaceIcon('camicon',static_host+"/img/icons/viewc--1.png");
                replaceIcon('subicon',static_host+"/img/icons/subc--1.png");
        } else {
                jump = 360.0/16.0;
                newangle = Math.floor(Math.round(realangle/jump)*jump);
                if (newangle == 360)
                        newangle = 0;
                replaceIcon('camicon',static_host+"/img/icons/viewc-"+parseInt(newangle,10)+".png");
                if (document.theForm.photographer_gridref.value == '')
                        replaceIcon('subicon',static_host+"/img/icons/subc-"+parseInt(newangle,10)+".png");
                else
                        replaceIcon('subicon',static_host+"/img/icons/circle.png");
        }
}


function enlargeMap() {
	if (map._loaded)
		var bounds = map.getBounds();
        ele = document.getElementById('map');
        ele.style.width = "100%";
        ele.style.height = "450px";
        map.invalidateSize();
	if (map._loaded)
		map.fitBounds(bounds);

	if (typeof resizeContainer == 'function') {
		resizeContainer();
	}
}


	var baseMaps = {};
	var overlayMaps = {};

///////////////////////////////////////////

	//despite it name, this now sets up many base layers, as well as overlays and controls!
	function setupOSMTiles(map,mapTypeId) {

		var osmAttrib='Map data &copy; <a href="https://openstreetmap.org">OpenStreetMap</a> contributors';
		baseMaps['OpenStreetMap'] = new L.TileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
			 {mapLetter: 'o', minZoom: 3, maxZoom: 18, attribution: osmAttrib});

		var topoAttribution = 'Data: &copy; <a href="https://openstreetmap.org/copyright">OpenStreetMap</a>-Contributors, <a href="http://viewfinderpanoramas.org">SRTM</a> | Map Style: &copy; (<a href="https://creativecommons.org/licenses/by-sa/3.0/">CC-BY-SA</a>) <a href="https://opentopomap.org">OpenTopoMap</a> - [<a href="https://www.geograph.org/leaflet/otm-legend.php">Key</a>]';
		baseMaps["OpenTopoMap"] = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png',
			{mapLetter: 'l', minZoom: 1, maxZoom: 17, detectRetina: false, attribution: topoAttribution});

/*
		//baseMaps['OSM Cycle'] = new L.TileLayer('https://tile.thunderforest.com/cycle/{z}/{x}/{y}{r}.png?apikey=42a8aaad46fa4fd784104f2870221993',
		//	{mapLetter: 'c', maxZoom: 18, attribution: '<a href=https://www.thunderforest.com/>thunderforest.com</a>, '+osmAttrib});

		baseMaps['OSM Terrain'] = new L.TileLayer('https://tile.thunderforest.com/landscape/{z}/{x}/{y}{r}.png?apikey=42a8aaad46fa4fd784104f2870221993',
			{mapLetter: 't', maxZoom: 18, attribution: '<a href=https://www.thunderforest.com/>thunderforest.com</a>, '+osmAttrib});
*/

		baseMaps['MapBox Imagery'] = new L.TileLayer('https://api.mapbox.com/styles/v1/geograph/cjh8zse9f2lq32spb7s5vmvbk/tiles/256/{z}/{x}/{y}?access_token={accessToken}',
			{mapLetter: 'h', maxZoom: 18, attribution: 'Imagery &copy; <a href="https://www.mapbox.com/">Mapbox</a>',
				accessToken: 'pk.eyJ1IjoiZ2VvZ3JhcGgiLCJhIjoiY2lteXI3cmlpMDBmenY5bTF5dHFqMnh0NiJ9.sPXF2s1niWNNEfqGjs2HGw'});

		var nlsAttrib = "\u003ca href=\"http://maps.nls.uk/projects/subscription-api/\"\u003eNational Library of Scotland\u003c/a\u003e";
		baseMaps['Historic OS - GB 1920s'] = new L.TileLayer('https://api.maptiler.com/tiles/uk-osgb1919/{z}/{x}/{y}.jpg?key=RJOABq94aMWBy2AuidnK',
		        {mapLetter: 'n', minZoom: 1, maxZoom:14 , attribution: nlsAttrib, crossOrigin: true,
				bounds: [[49.6, -12], [61.7, 3]] });

		//note this layer is used with specific permission of NLS, need to ask before using it in other sites
		baseMaps['Historic OS - Ireland'] = new L.TileLayer('https://geo.nls.uk/maps/ireland/gsgs4136/{z}/{x}/{y}.png',
		        {mapLetter: 'i', tms: true, minZoom: 5, maxZoom: 15, attribution: 'Provided by <a href="https://geo.nls.uk/">NLS Geo</a>',
				bounds: [[51.371780, -10.810546], [55.422779, -5.262451]] });

		baseMaps['Bartholomew Ireland 1940s'] = new L.TileLayer('https://api.maptiler.com/tiles/uk-baire250k1940/{z}/{x}/{y}.png?key=RJOABq94aMWBy2AuidnK',
		        {minZoom: 5, maxZoom:12 , attribution: nlsAttrib, crossOrigin: true,
				bounds: [[51.371780, -10.810546], [55.422779, -5.262451]] });


		if (L.tileLayer.bing) {
		        var BING_KEY = 'AhwwUjiHWfAqm-dQiAhV1tJO82v-v5mU6osoxU3t1XKx-AlPyKzfBhKpTY81MKtJ';
		        var bingAttribution = 'Image courtesy of Ordnance Survey, via Bing <a style="white-space: nowrap" target="_blank" href="https://www.microsoft.com/maps/product/terms.html">Terms of Use</a>';
			baseMaps["Ordnance Survey GB"] = L.tileLayer.bing({mapLetter: 'b', 'bingMapsKey':BING_KEY,'minZoom':12,'maxZoom':17,'imagerySet':'OrdnanceSurvey', attribution:bingAttribution,
		                bounds: [[49.6, -12], [61.7, 3]] });
		}

		if (mapTypeId && baseMaps[mapTypeId])
			map.addLayer(baseMaps[mapTypeId])
		else
			map.addLayer(baseMaps['OpenStreetMap']);

///////////////////////////////////////////

		if (L.GeographRecentUploads)
			overlayMaps["Recent Uploads"] = L.geographRecentUploads();

		// dots layer
	        var layerUrl='https://t0.geograph.org.uk/tile/tile-density.php?z={z}&x={x}&y={y}&match=&l=1&6=1';
		var layerAttrib='&copy; Geograph Project';
		var bounds = L.latLngBounds(L.latLng(49.863788, -13.688451), L.latLng(60.860395, 1.795260));
        	overlayMaps['Photo Subjects'] = new L.TileLayer(layerUrl, {minZoom: 6, maxZoom: 18, attribution: layerAttrib, bounds: bounds, opacity: 0.8});

		if (L.britishGrid) {
			var gridOptions = {
		                opacity: 0.3,
		                weight: 0.7,
		                showSquareLabels: [100000,10000,100]
		        };

			overlayMaps['OSGB Grid'] = L.britishGrid(gridOptions);
			overlayMaps['Irish Grid'] = L.irishGrid(gridOptions);
			if (!issubmit) {
				overlayMaps['OSGB Grid'].addTo(map);
				overlayMaps['Irish Grid'].addTo(map);
			}
		}

		if (L.geographGeocoder && !geocoder)
			map.addControl(geocoder = L.geographGeocoder());

		if (L.control.locate)
			L.control.locate({
				keepCurrentZoomLevel: [13,18],
				locateOptions: {
					maxZoom: 16,
					enableHighAccuracy: true
			}}).addTo(map);

		L.control.layers(baseMaps,overlayMaps).addTo(map);
	}

///////////////////////////////////////////

	//creates the highlevel 'map' object. Note this version does NOT initalaize a center/zoom for the map. Will have to do that afterwards
	function setupBaseMap(options) {
		var mapOptions = {
			attributionControl:false //we add our own manually!
		};
		if (options)
			 L.extend(mapOptions, options);

		//just a normal Leafelt Map
		map = window.map = L.map('map', mapOptions).addControl(
			L.control.attribution({ position: 'bottomright', prefix: ''}) );

		var serviceUrl = 'https://api.os.uk/maps/raster/v1/zxy';
		if (window.OSAPIKey && L.Proj && L.Proj.CRS) {
			var defaultCRS = map.options.crs;

			// Setup the EPSG:27700 (British National Grid) projection.
			var osgbCRS = new L.Proj.CRS('EPSG:27700', '+proj=tmerc +lat_0=49 +lon_0=-2 +k=0.9996012717 +x_0=400000 +y_0=-100000 +ellps=airy +towgs84=446.448,-125.157,542.06,0.15,0.247,0.842,-20.489 +units=m +no_defs',
			{
				resolutions: [ 896.0, 448.0, 224.0, 112.0, 56.0, 28.0, 14.0, 7.0, 3.5, 1.75, 0.875, 0.4375, 0.21875, 0.109375 ],
				origin: [ -238375.0, 1376256.0 ]
			});

			// Transform coordinates.
			var transformCoords = function(arr) {
				return proj4('EPSG:27700', 'EPSG:4326', arr).reverse();
			};

			var bounds = [
				transformCoords([ -238375.0, 0.0 ]),
				transformCoords([ 900000.0, 1376256.0 ])
			];

			var basemap1 = L.tileLayer(serviceUrl + '/Leisure_27700/{z}/{x}/{y}.png?key=' + OSAPIKey, {
				minZoom: 3,
				maxZoom: 9,
				bounds
			})

			var basemap2 = L.tileLayer(serviceUrl + '/Outdoor_27700/{z}/{x}/{y}.png?key=' + OSAPIKey, {
				minZoom: 10,
				maxZoom: 13,
 				bounds
			});

			baseMaps['Modern OS - GB'] = L.layerGroup([basemap1], {
		//		crs: crs,
				attribution: 'Contains OS data &copy; Crown copyright and database rights 2022',
				mapLetter: 'a'
			}); //.addTo(map);
			if (window.enhancedOSZoom)
				baseMaps['Modern OS - GB'].addLayer(basemap2);

//this is a hack, because the built in function, notices that the newly added layer, has differnet zoom levels, and that the zoom is outside the rage
// .. the goal here is to prevent the OS layers, being added to the internal '_zoomBoundLayers' list!
// so when adding OS layers to map, it doesnt change the zoom of the map because the zoom range of new layers is less
// dont need to worry about diabling the 'onRemove' call of _removeZoomLimit, because OS map range is smaller than all others
basemap1.beforeAdd = function() {};
basemap2.beforeAdd = function() {};

			baseMaps['Modern OS - GB'].on('add', function(e) {
				var bounds = map.getBounds();
				var center = map.getCenter();
				var zoom = map.getZoom();

			        // Append the API logo.
				//https://labs.os.uk/public/os-api-branding/v0.3.0/os-api-branding.js
			        logodiv = document.createElement('div');
			        logodiv.className = 'os-api-branding logo';
			        map._container.appendChild(logodiv);

				if (map._container.clientWidth < 420 && document.querySelectorAll) {
					var elements = document.querySelectorAll('.leaflet-control-attribution');
					for(var i=0;i<elements.length;i++)
						//elements[i].style.display='none';
						elements[i].style.maxWidth=(map._container.clientWidth-120)+'px';
				}

				for(i in overlayMaps) {
					if (i.indexOf('Grid') == -1) { //the grid layers do cope with differnet crs!
						//overlayMaps[i].removeFrom(map); //seems to be a NOOP, if not on the map, so just call regardless!
						//for some reason, it not removing the layer, so just 'disable' it instead; although this also prevents the user enabling it!
						overlayMaps[i].options.minZoom += 100;
					}
				}

				map.options.crs = osgbCRS;
				map.setView(center, zoom-6, {animate:false}); //we need this, because after changing crs the center is shifted
                                map._resetView(map.getCenter(), map.getZoom(), true); //we need this to redraw all layers (polygons, markers...) in the new projection.

				//to emulate what happens in the original _updateZoomLevels (which is never called now, because beforeAdd is hobbled)
				map._layersMaxZoom = (window.enhancedOSZoom)?13:9;
				map._layersMinZoom = 3;

				//[-50,50] worked ok with a 390 square map in submission! (-25 did not!)
				var padw = map._container.clientWidth/-8;
				var padh = map._container.clientHeight/-8;

				map.fitBounds(bounds, {animate:false, padding: [padw,padh]}); //-padding is is prevent creep as switch back/forward

			}).on('remove', function(e) {
				var bounds = map.getBounds();
				var center = map.getCenter();
				var zoom = map.getZoom();

				if (logodiv) {
					map._container.removeChild(logodiv);
					var elements = document.querySelectorAll('.leaflet-control-attribution');
					for(var i=0;i<elements.length;i++)
						//elements[i].style.display='';
						elements[i].style.maxWidth='';
				}

				for(i in overlayMaps) {
					if (i.indexOf('Grid') == -1) { //the grid layers do cope with differnet crs!
						overlayMaps[i].options.minZoom -= 100;
					}
				}

				map.options.crs = defaultCRS;
				map.setView(center, zoom+6); //we need this, because after changing crs the center is shifted
                             	map._resetView(map.getCenter(), map.getZoom(), true); //we need this to redraw all layers (polygons, markers...) in the new projection.

				//even though _updateZoomLevels should be setting this for the other layer, it seems more reliavle to remove outselfs
				map._layersMaxZoom = undefined;
				map._layersMinZoom = undefined;

				var padw = map._container.clientWidth/-7;
				var padh = map._container.clientHeight/-7;

				map.fitBounds(bounds, {animate:false, padding: [padw,padh]});
			});

		} else if(window.OSAPIKey) {
			baseMaps["OS Outdoor"] = L.tileLayer(serviceUrl + '/Outdoor_3857/{z}/{x}/{y}.png?key=' + OSAPIKey, {
				minZoom: 7,
			        maxZoom: 16,
				bounds: [
			            [ 49.528423, -10.76418 ],
			            [ 61.331151, 1.9134116 ]
			        ],
				attribution: 'Contains OS data &copy; Crown copyright and database rights 2022',
			});

        	        baseMaps['OS Outdoor'].on('add', function(e) {
	                        // Append the API logo.
	                        //https://labs.os.uk/public/os-api-branding/v0.3.0/os-api-branding.js
	                        logodiv = document.createElement('div');
	                        logodiv.className = 'os-api-branding logo';
                	        map._container.appendChild(logodiv);

        	                if (map._container.clientWidth < 420 && document.querySelectorAll) {
	                                var elements = document.querySelectorAll('.leaflet-control-attribution');
                	                for(var i=0;i<elements.length;i++)
        	                                //elements[i].style.display='none';
	                                        elements[i].style.maxWidth=(map._container.clientWidth-120)+'px';
                	        }
        	        }).on('remove', function(e) {
	                        if (logodiv) {
                	                map._container.removeChild(logodiv);
        	                        var elements = document.querySelectorAll('.leaflet-control-attribution');
	                                for(var i=0;i<elements.length;i++)
                        	                //elements[i].style.display='';
                	                        elements[i].style.maxWidth='';
        	                }
	                });

		}

		var mapTypeId = "OpenTopoMap";
		if (baseMaps['Modern OS - GB'])
			mapTypeId = 'Modern OS - GB';

		if (window.localStorage && window.localStorage.getItem) {
			if (!window.leafletBaseKey)
				leafletBaseKey = 'LeafletBase';
			if (window.localStorage.getItem(leafletBaseKey)) //we can't check it a valid basemap here!
				mapTypeId = window.localStorage.getItem(leafletBaseKey);
		}

		setupOSMTiles(map,mapTypeId);

		map.on('baselayerchange', function (e) {
			var name = null;
			for(i in baseMaps) {
				if (baseMaps[i] == e.layer)
					name = i;
			}

			if (window.localStorage && window.localStorage.getItem)
				window.localStorage.setItem(window.leafletBaseKey, name);

			var color = (name && name.indexOf('Imagery') > -1)?'#fff':'#00f';
			var opacity = (name && name.indexOf('Imagery') > -1)?0.8:0.3;
			for(i in overlayMaps) {
				if (i.indexOf('Grid') > 0 && overlayMaps[i].options.color != color && overlayMaps[i].setOpacity) {
					overlayMaps[i].options.color = color;
					overlayMaps[i].setOpacity(opacity);
					overlayMaps[i]._reset();
				}
			}
		});

		if (mapTypeId && mapTypeId.indexOf('Imagery') > -1 && baseMaps[mapTypeId])
			map.fire('baselayerchange',{layer: baseMaps[mapTypeId]}); // need this to select the white grid!

	}

///////////////////////////////////////////


