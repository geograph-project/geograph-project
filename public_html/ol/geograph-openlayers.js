/**
 *
 * Fuses OS OpenSpace and other mapping providers into one unified OpenLayers interface
 * 
 * This file copyright (c)2012 Barry Hunter
 * 
 * based heavily on the work of Bill Charwick, which was also GPL code. 
 * 
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
 *
 */

var olmap = {
	map: null,
	layers: {},
	images: {}
};
var tabletOrPhone = false;

function loadMapInner() { //NOTE: does not center the map, the parent function will have to take care of that!

    if ((navigator.userAgent.indexOf('Android') != -1) ||
        (navigator.userAgent.indexOf('Opera Mobi') != -1) ||
        (navigator.userAgent.indexOf('iPhone') != -1) ||
        (navigator.userAgent.indexOf('iPod') != -1) ||
        (navigator.userAgent.indexOf('iPad') != -1)) {
        tabletOrPhone = true;
    }

    olmap.layers['os'] = new OpenLayers.Layer.OsOpenSpaceLayer({
        OSAPIKey: "A493C3EB96133019E0405F0ACA6056E3", // Geograph's key key
        OSKeysUrl: 'http://'+window.location.host+'/', // Geograph's URL (escaped) registered against the key
        layerName: "Ordnance Survey GB"
    });

    if (tileserver_default) { // this is set by nls-api.js
	// Define the XYZ-based layer for NLS Map
	OpenLayers.Layer.NLS = OpenLayers.Class(OpenLayers.Layer.XYZ, {
		name: "NLS Maps API",
		attribution: 'Historical maps from <a href="http://geo.nls.uk/maps/api/" target="_blank">NLS Maps API<\/a>',
		getURL: NLSTileUrlOS,
		sphericalMercator: true,
		transitionEffect: 'resize',
		CLASS_NAME: "OpenLayers.Layer.NLS"
	});

	olmap.layers['nls'] = new OpenLayers.Layer.NLS( "OS Historical GB");
    }
    
    olmap.layers['google_physical'] = new OpenLayers.Layer.Google(
        "Google Physical",
        { type: google.maps.MapTypeId.TERRAIN, numZoomLevels: 20 }
    );
    olmap.layers['google'] = new OpenLayers.Layer.Google(
        "Google Streets", // the default
        { numZoomLevels: 20 }
    );
    olmap.layers['google_hybrid'] = new OpenLayers.Layer.Google(
        "Google Hybrid",
        { type: google.maps.MapTypeId.HYBRID, numZoomLevels: 20 }
    );
    olmap.layers['google_satellite'] = new OpenLayers.Layer.Google(
        "Google Satellite",
        { type: google.maps.MapTypeId.SATELLITE, numZoomLevels: 22 }
    );

    olmap.layers['osm'] = new OpenLayers.Layer.OSM("OSM (OpenStreetMap)", null, {attribution:'<a href="http://www.openstreetmap.org/copyright">&copy OpenStreetMap contributors</a>'});

    olmap.layers['osm_cycle'] = new OpenLayers.Layer.OSM("OSM OpenCycleMap", ['http://a.tile.opencyclemap.org/cycle/${z}/${x}/${y}.png',
                                                      'http://b.tile.opencyclemap.org/cycle/${z}/${x}/${y}.png',
                                                      'http://c.tile.opencyclemap.org/cycle/${z}/${x}/${y}.png'],
							 {attribution:'Tiles &copy; OpenCycleMap, Map data &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>'});

    olmap.layers['osm_tran'] = new OpenLayers.Layer.OSM("OSM Public Transport", ['http://a.tile2.opencyclemap.org/transport/${z}/${x}/${y}.png',
                                                      'http://b.tile2.opencyclemap.org/transport/${z}/${x}/${y}.png',
                                                      'http://c.tile2.opencyclemap.org/transport/${z}/${x}/${y}.png'],
							 {attribution:'Tiles &copy; Gravitystorm, Map data &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>'});

    olmap.layers['osm_phys'] = new OpenLayers.Layer.OSM("OSM Landscape", ['http://a.tile3.opencyclemap.org/landscape/${z}/${x}/${y}.png',
                                                      'http://b.tile3.opencyclemap.org/landscape/${z}/${x}/${y}.png',
                                                      'http://c.tile3.opencyclemap.org/landscape/${z}/${x}/${y}.png'],
                                                         {attribution:'Tiles &copy; Gravitystorm, Map data &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>'});

    olmap.layers['bing'] = new OpenLayers.Layer.Bing({
        name: "Bing Road",
        type: "Road",
        key: "AhwwUjiHWfAqm-dQiAhV1tJO82v-v5mU6osoxU3t1XKx-AlPyKzfBhKpTY81MKtJ"
    });

    olmap.layers['bing_aerial'] = new OpenLayers.Layer.Bing({
        name: "Bing Aerial",
        type: "Aerial",
        key: "AhwwUjiHWfAqm-dQiAhV1tJO82v-v5mU6osoxU3t1XKx-AlPyKzfBhKpTY81MKtJ"
    });

    olmap.map = new OpenLayers.Map('map', {
        displayProjection: "EPSG:4326", //wgs84 - used for permalinks etc... 
        controls: [
	    new OpenLayers.Control.KeyboardDefaults(),
	    new OpenLayers.Control.Navigation(),
	    new OpenLayers.Control.PanZoomBar(),
	    new OpenLayers.Control.Attribution(),
	    new OpenLayers.Control.OsLogo(),
	    new OpenLayers.Control.LayerSwitcher(),
            new OpenLayers.Control.Graticule({ visible: false, layerName: 'Lat/Long Grid' }), 
            new OpenLayers.Control.UKOSGraticule(),
            new OpenLayers.Control.IrishGraticule(),
	    new OpenLayers.Control.Permalink({anchor: true})
	]
    });

    if (!tabletOrPhone) {
        // UK/Irish Grid Ref + WGS84 Lat/Lon pointer pos if we have a mouse
        olmap.map.addControl(new OpenLayers.Control.MousePosition({ emptyString: "", numDigits: 6 }));               
        // use olControlMousePosition to style the pointer
    }

    // Even if you specify EPSG:4326 for these layers, if you add features / markers with code, you must transfrom them using olmap.map.getProjection() 
    // so probably best not to bother
    olmap.layers['markers'] = new OpenLayers.Layer.Markers('Markers');

    for(i in olmap.layers)
	olmap.map.addLayer(olmap.layers[i]);

}


///////////////////////////////////////////////////////////////
//
// dynamic geograph image layer
//
///////////////////////////////////////////////////////////////

function mapEvent(event) {
 if (!olmap.layers['markers'].getVisibility()) {
    return;
 }

 var endpoint = "http://wac.3c13.edgecastcdn.net/803C13/jam/sample8.php";


  var data = {
     a: 1,
     q: '',
     limit: 40,
     select: "title,realname,user_id,hash,grid_reference,takenday,wgs84_long,wgs84_lat"
  };

  data.sort="sequence ASC"; data.rank=2;

  data.olbounds = olmap.map.getExtent().transform(olmap.map.getProjection(),"EPSG:4326").toString();

  _call_cors_api(
    endpoint,
    data,
    'serveCallback',
    function(data) {
     if (data && data.matches) {
        //remove the old markers
        $.each(olmap.images,function(id,value) {
            olmap.images[id].old = true;
        });


        var loaded = 0;
        
        $.each(data.matches,function(index,value) {
          if (olmap.images[value.id]) {
            olmap.images[value.id].old = false;
          } else {
            value.attrs.thumbnail = getGeographUrl(value.id, value.attrs.hash, 'small');

        var iconSize = new OpenLayers.Size(36, 36);
        var iconOffset = new OpenLayers.Pixel(-19, -19);
        var markerIcon = new OpenLayers.Icon(value.attrs.thumbnail, iconSize, iconOffset, null);

        var markerPoint = new OpenLayers.LonLat(rad2deg(parseFloat(value.attrs.wgs84_long)), rad2deg(parseFloat(value.attrs.wgs84_lat))).transform("EPSG:4326", olmap.map.getProjection());

	olmap.images[value.id] = new OpenLayers.Marker(markerPoint, markerIcon);

        olmap.images[value.id].events.register('mousedown', olmap.images[value.id], function(evt) { 
		//alert(this.icon.url); 
		//window.open("http://www.geograph.org.uk/photo/"+value.id);

		olmap.images[value.id].popup = new OpenLayers.Popup('pop'+value.id,
                   markerPoint,
                   new OpenLayers.Size(300,200),
                   '<center><a href="http://www.geograph.org.uk/photo/'+value.id+'"><img src="'+value.attrs.thumbnail+'"/></a><br/><b>'+value.attrs.title+'</b> by <b>'+value.attrs.realname+'</b></center>',
                   true);
		olmap.map.addPopup(olmap.images[value.id].popup);

		OpenLayers.Event.stop(evt); 
	});

	olmap.layers['markers'].addMarker(olmap.images[value.id]);

          }
          loaded=loaded+1;
        });


        $.each(olmap.images,function(id,value) {
            if (olmap.images[id].old && olmap.images[id].old == true) {
                olmap.layers['markers'].removeMarker(olmap.images[id]);
                delete olmap.images[id];
            }
        });

        $("#map_message").html(loaded+" of "+data.total_found+" <span>in "+data.time+" seconds</span>");

     } else {
        if (data.time) {
           $("#map_message").html("No results found, in "+data.time+" seconds");
           $.each(olmap.images,function(id,value) {
              //if (olmap.images[id].old == true) {
                olmap.layers['markers'].removeMarker(olmap.images[id]);
                delete olmap.images[id];
              //}
           });
        }
        else if (data.error)
           $("#map_message").html(data.error.replace(/^index [\w,]+:/,''));     
     }
    }
  );
}

function rad2deg (angle) {
    // Converts the radian number to the equivalent number in degrees  
    // 
    // version: 1109.2015
    // discuss at: http://phpjs.org/functions/rad2deg
    // +   original by: Enrique Gonzalez
    // +      improved by: Brett Zamir (http://brett-zamir.me)
    // *     example 1: rad2deg(3.141592653589793);
    // *     returns 1: 180
    return angle * 57.29577951308232; // angle / Math.PI * 180
}

function getGeographUrl(gridimage_id, hash, size) { 

	yz=zeroFill(Math.floor(gridimage_id/1000000),2); 
	ab=zeroFill(Math.floor((gridimage_id%1000000)/10000),2); 
	cd=zeroFill(Math.floor((gridimage_id%10000)/100),2);
	abcdef=zeroFill(gridimage_id,6); 

	if (yz == '00') {
		fullpath="/photos/"+ab+"/"+cd+"/"+abcdef+"_"+hash; 
	} else {
		fullpath="/geophotos/"+yz+"/"+ab+"/"+cd+"/"+abcdef+"_"+hash; 
	}
	
	switch(size) { 
		case 'full': return "http://s0.geograph.org.uk"+fullpath+".jpg"; break; 
		case 'med': return "http://s"+(gridimage_id%4)+".geograph.org.uk"+fullpath+"_213x160.jpg"; break; 
		case 'small': 
		default: return "http://s"+(gridimage_id%4)+".geograph.org.uk"+fullpath+"_120x120.jpg"; 
	}
}
function zeroFill(number, width) {
	width -= number.toString().length;
	if (width > 0) {
		return new Array(width + (/\./.test(number)?2:1)).join('0') + number;
	}
	return number + "";
}

// function to allow using cors if possible
function _call_cors_api(endpoint,data,uniquename,success) {
  crossDomain = true; //todo/tofix!
  if (uniquename && crossDomain && !jQuery.support.cors) {
    //use a normal JSONP request - works accorss domain
    endpoint += (endpoint.indexOf('?')>-1?'&':'?')+"callback=?&";
    $.ajax({
      url: endpoint,
      data: data,
      dataType: 'jsonp',
      jsonpCallback: uniquename,
      cache: true,
      success: success
    });
  } else {
    //works as a json requrest - either same domain, or a browser with cors support
    $.ajax({
      url: endpoint,
      data: data,
      dataType: 'json',
      cache: true,
      success: success
    });
  }
}

///////////////////////////////////////////////////////////////
//
// patch MousePosition control to return a UK or Irish Grid Ref
//
//   by Bill Chadwick
//
///////////////////////////////////////////////////////////////

/**
 * Method: formatOutput
 * Override to provide custom display output of UK or Irish grid reference
 *
 * Parameters:
 * lonLat - {<OpenLayers.LonLat>} Location to display
 */
OpenLayers.Control.MousePosition.prototype.formatOutput = function(lonLat) {
    
    var res = this.map.getResolution() || 10; //metres assumed

    // just in case displayProjection is not wgs84
    if (this.displayProjection != "EPSG:4326") {
        lonLat.transform(this.displayProjection, "EPSG:4326");
    }

    // default text outside of UK and Ireland is lat, lon
    var newHtml =  this.prefix + lonLat.lon.toFixed(this.numDigits) + this.separator + lonLat.lat.toFixed(this.numDigits) + this.suffix;
    newHtml += "<BR>" + OpenLayers.Util.getFormattedLonLat(lonLat.lon,'lon','dms') + this.separator + OpenLayers.Util.getFormattedLonLat(lonLat.lat,'lat','dms');


    if (OpenLayers.Projection.Irish.isValidLonLat(lonLat.lon, lonLat.lat)) {
        //Irish area, preceed lat,lon with Irish Grid Ref
        newHtml = OpenLayers.Projection.Irish.lonLatToString(lonLat.transform("EPSG:4326", "EPSG:29902"), res) + "<BR>" + newHtml;
    }
    else if (OpenLayers.Projection.OS.isValidLonLat(lonLat.lon, lonLat.lat)) {
        //UK area, preceed lat,lon with UK Grid Ref
        newHtml = OpenLayers.Projection.OS.lonLatToString(lonLat.transform("EPSG:4326", "EPSG:27700"), res) + "<BR>" + newHtml;
    }

    return newHtml;
};

/**
* APIFunction: getFormattedLonLat
* This function will return latitude or longitude value formatted as 
*
* Parameters:
* coordinate - {Float} the coordinate value to be formatted
* axis - {String} value of either 'lat' or 'lon' to indicate which axis is to
*          to be formatted (default = lat)
* dmsOption - {String} specify the precision of the output can be one of:
*           'dms' show degrees minutes and seconds
*           'dm' show only degrees and minutes
*           'd' show only degrees
* 
* Returns:
* {String} the coordinate value formatted as a string
*/
OpenLayers.Util.getFormattedLonLat = function(coordinate, axis, dmsOption) {
    if (!dmsOption) {
        dmsOption = 'dms';    //default to show degree, minutes, seconds
    }

    coordinate = (coordinate + 540) % 360 - 180; // normalize for sphere being round

    var abscoordinate = Math.abs(coordinate);
    var coordinatedegrees = Math.floor(abscoordinate);

    var coordinateminutes = (abscoordinate - coordinatedegrees) / (1 / 60);
    var tempcoordinateminutes = coordinateminutes;
    coordinateminutes = Math.floor(coordinateminutes);
    var coordinateseconds = (tempcoordinateminutes - coordinateminutes) / (1 / 60);
    coordinateseconds = Math.round(coordinateseconds * 10);
    coordinateseconds /= 10;

    if (coordinateseconds >= 60) {
        coordinateseconds -= 60;
        coordinateminutes += 1;
        if (coordinateminutes >= 60) {
            coordinateminutes -= 60;
            coordinatedegrees += 1;
        }
    }

    if (coordinatedegrees < 10) {
        coordinatedegrees = "0" + coordinatedegrees;
    }
    var str = coordinatedegrees + "\u00B0";

    if (dmsOption.indexOf('dm') >= 0) {
        if (coordinateminutes < 10) {
            coordinateminutes = "0" + coordinateminutes;
        }
        str += coordinateminutes + "'";

        if (dmsOption.indexOf('dms') >= 0) {
            var strSecs = "";
            if (coordinateseconds < 10) {
                strSecs = "0" + coordinateseconds.toFixed(1); // patch to always have 10ths of seconds */
            }
            else {
                strSecs = coordinateseconds.toFixed(1);
            }
            str += strSecs + '"';
        }
    }

    if (axis == "lon") {
        str += coordinate < 0 ? OpenLayers.i18n("W") : OpenLayers.i18n("E");
    } else {
        str += coordinate < 0 ? OpenLayers.i18n("S") : OpenLayers.i18n("N");
    }
    return str;
};

