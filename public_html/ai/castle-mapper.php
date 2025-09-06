<?php
/**
 * $Project: GeoGraph $
 * $Id: xmas.php 6235 2009-12-24 12:33:07Z barry $
 *
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Barry Hunter (geo@barryhunter.co.uk)
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

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;

$smarty->display('_std_begin.tpl');

?>

<h2>Images around Harlech</h2>

<p>Images on the inner ring, generally depict the castle itself. Whereas the ones further out are scenes seen around the town. The exact selection is done by AI and not may be totally accurate.

<div id="map" style="width:1200px; height:800px; max-height:90vh; max-width:80vw;"></div>
<div id="countPrompt"></div>


        <link rel="stylesheet" type="text/css" href="https://unpkg.com/leaflet@1.3.1/dist/leaflet.css" />
	<link rel="stylesheet" href="/js/LeafletLineMarker.css?<? echo filemtime('../js/LeafletLineMarker.css'); ?>" />


        <script src="https://unpkg.com/leaflet@1.3.1/dist/leaflet.js" type="text/javascript"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.5.0/proj4.js"></script>
        <script type="text/javascript" src="<? echo smarty_modifier_revision("/js/Leaflet.MetricGrid.js"); ?>"></script>
        <script type="text/javascript" src="<? echo smarty_modifier_revision("/js/mappingLeaflet.js"); ?>"></script>

	<script src="https://www.geograph.org/leaflet/leaflet-hash.js"></script>
        <script src="/js/vector.class.js"></script>
        <script src="/js/LeafletLineMarker.js?<? echo filemtime('../js/LeafletLineMarker.js'); ?>"></script>


<script type="text/javascript">
var map = null ;
var issubmit = false;
var static_host = '<? echo $CONF['STATIC_HOST']; ?>';

//////////////////////////////////////////

let images = [];
let labels = [];

function loadmap() {

	//stolen from Leaflet.base-layers.js - alas that file no compatible with mappingLeaflet.js at the moment :(
	var bounds = L.latLngBounds(L.latLng(49.863788, -13.688451), L.latLng(60.860395, 1.795260));

	setupBaseMap(); //creates the map, but does not initialize a view
	map.fitBounds(bounds,{maxZoom:15});

	var hash = new L.Hash(map);

        const urlParams = new URLSearchParams(window.location.search);

        queryFilter = urlParams.get('query') || 'geograph';

//////////////////////////////////////////

	let polygonList = [
		"-4.11044,52.86227+-4.11044,52.86227+-4.11044,52.86227+-4.11045,52.86227+-4.11045,52.86227+-4.11045,52.86227+-4.11045,52.86227+-4.11232,52.86122+-4.11232,52.86122+-4.11232,52.86122+-4.11232,52.86121+-4.11232,52.86121+-4.11254,52.85945+-4.11254,52.85945+-4.11254,52.85945+-4.11254,52.85945+-4.11129,52.85786+-4.11129,52.85786+-4.11129,52.85786+-4.11129,52.85786+-4.11128,52.85786+-4.11128,52.85786+-4.11128,52.85786+-4.10714,52.85803+-4.10713,52.85803+-4.10713,52.85803+-4.10713,52.85803+-4.10713,52.85803+-4.10712,52.85803+-4.10558,52.85968+-4.10558,52.85968+-4.10558,52.85968+-4.10558,52.85968+-4.10558,52.85968+-4.10558,52.85969+-4.10738,52.86162+-4.10738,52.86162+-4.10738,52.86162+-4.10739,52.86162+-4.10739,52.86162+-4.11044,52.86227"
	];

	let fetchPromises = polygonList.map(polygonStr => {
	    // Start with the base URL
	    let url = `https://api.geograph.org.uk/api-facetql-vector.php?long=1&match=${encodeURIComponent(queryFilter)}&order=sequence+asc&limit=80&select=id,title,realname,image_vector,wgs84_lat,wgs84_long,hash,takenday`;

	    // Conditionally add the polygon filter
	    if (polygonStr) {
	        url += addPolygonFilter(polygonStr);
	    }

	    // Return the fetch promise for this URL
	    return fetch(url).then(response => response.json());
	});
	//make sure the labels fetch is FIRST
	fetchPromises.unshift(
	    fetch('/finder/label-vectors.json.php?labels=castle,tower,building,church,pub,other,road,forest,people,aerial').then(response => response.json())
	);
	/*
	var query = '"Derwent Water" aerial NY21|NY22'; //dont use polygone, as need bigger area, fun thing would be use turf.buffer!
	fetchPromises.push(
	    fetch(
		`https://api.geograph.org.uk/api-facetql-vector.php?long=1&match=${encodeURIComponent(query)}&order=sequence+asc&limit=75&select=id,title,realname,image_vector,wgs84_lat,wgs84_long,hash,takenday`
	    ).then(response => response.json())
	); */

        let markerBounds = L.latLngBounds();
        let total = 0;
	let total_found = 0;
	Promise.all(fetchPromises)
	    .then(results => {
	        // Loop through each result and add its rows to the list
	        results.forEach(data => {
        	    if (data.rows) { //image results
        	            data.rows.forEach(row => {
                	        if (row.wgs84_lat && row.wgs84_long) {
                        	    var latLng = L.latLng(rad2deg(row.wgs84_lat), rad2deg(row.wgs84_long));
				    var dir = getCardinalDirection(centriod.lat,centriod.lng, latLng.lat,latLng.lng); //abitary point in the middle of the lake!
				    var len = 280;
				    var closestLabel = 'unknown';
				    if (row.image_vector) {
					closestLabel = getClosestLabel(row.image_vector);
					if (closestLabel == 'castle' || closestLabel == 'tower')
						len=80;
					if (closestLabel == 'aerial')
						len = 380;
					else if (!row.inside) //means it not a polygon match! (must be the aerial query!
					   return; //skip! non-aerial in the aerial keyword search! or we could do a 'point in polygon' check or at least use markerBounds!
				    } else {
                                           return; //skip! unprocessed!
				    }

				    var marker = L.lineMarker(latLng, {img: getGeographUrl(row.id, row.hash, 'small'), dir:dir, title:row.title, imgSize: 80, lineLength:len}).addTo(map);
        	                    var popupContent = `<a href="https://www.geograph.org.uk/photo/${row.id}" target="_blank">` +
                                               `<img src="${getGeographUrl(row.id, row.hash, 'full')}"><br>` +
                                               `${escapeHtml(row.title)}</a> by ${escapeHtml(row.realname)}`;
                	            marker.bindPopup(popupContent);
                        	    //marker.bindTooltip(row.title);
					//marker.bindTooltip(closestLabel);
				    if (closestLabel && closestLabel != 'aerial')
		                            markerBounds.extend(latLng);
        	                }
                	    });
			    total += data.rows.length;
			    if (data.meta && data.meta.total_found)
                                total_found += parseInt(data.meta.total_found,10);
 	            } else {
			for (const label in data) {
		                if (data[label]) {
                		    try {
		                        labels[label] = new EmbeddingVector(data[label]).normalize();
		                    } catch (e) {
                		        console.error(`Could not create vector for label "${label}":`, e);
		                    }
		                }
		        }
		    }
                });

                if (markerBounds.isValid()) {
		    if (!window.location.hash)
	                    map.fitBounds(markerBounds);
         	}
	        if (total_found)
		    document.getElementById('countPrompt').innerText = "Showing "+total+" of "+total_found+", effectively at random to give a selection of images";
            });
}

//////////////////////////////////////////

function getCardinalDirection(lat1, lon1, lat2, lon2) {
    const latDiff = (lat2 - lat1)/2;
    const lonDiff = lon2 - lon1;

    // Check if the change in latitude is greater than the change in longitude
    if (Math.abs(latDiff) > Math.abs(lonDiff)) {
        // The direction is mostly North or South
        if (latDiff > 0) {
            return 'down';
        } else {
            return 'up';
        }
    } else {
        // The direction is mostly East or West
        if (lonDiff > 0) {
            return 'left';
        } else {
            return 'right';
        }
    }
}

function getClosestLabel(image_vector) {
                        try {
                            const imageVector = new EmbeddingVector(image_vector).normalize();
                            const nearest = imageVector.knn(labels, 1);
                            if (nearest.length > 0) {
                                return nearest[0].key;
                            }
                        } catch(e) {
                            console.error(`Could not process vector:`, e);
                        }
	return 'unknown';
}

//////////////////////////////////////////

var centriod;

function addPolygonFilter(polygonStr, addToMap) {
        if (polygonStr) {
            whereFilter = getPolygonFilter(polygonStr, addToMap);
            return `,${encodeURIComponent(whereFilter)}+as+inside&where=${encodeURIComponent('inside=1')}`;
        }
	return '';
}
function getPolygonFilter(polygonStr, addToMap) {
	var latlngs = polygonStr.split('+').map(function(coord) {
                var pair = coord.split(',');
                return L.latLng(parseFloat(pair[1]), parseFloat(pair[0]));
        });
	let polygon = L.polygon(latlngs, {color: 'red', stroke:0, opacity: 0.1, fillOpacity: 0.1});
	//if (addToMap)
            polygon.addTo(map);
	centriod = polygon.getCenter();

        var radianCoords = latlngs.map(function(latlng) {
                var lngRad = (latlng.lng * Math.PI / 180).toFixed(6);
                var latRad = (latlng.lat * Math.PI / 180).toFixed(6);
                return [lngRad, latRad];
        });
        var radianList = radianCoords.flat().join(',');
        return `CONTAINS(GEOPOLY2D(${radianList}),wgs84_long,wgs84_lat)`;
}

//////////////////////////////////////////

function rad2deg (angle) {
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
                case 'full': return "https://s0.geograph.org.uk"+fullpath+".jpg"; break;
                case 'med': return "https://s"+(gridimage_id%4)+".geograph.org.uk"+fullpath+"_213x160.jpg"; break;
                case 'small':
                default: return "https://s"+(gridimage_id%4)+".geograph.org.uk"+fullpath+"_120x120.jpg";
        }
}

function zeroFill(number, width) {
        width -= number.toString().length;
        if (width > 0) {
                return new Array(width + (/\./.test(number)?2:1)).join('0') + number;
        }
        return number + "";
}

function escapeHtml(unsafe) {
    if (unsafe === null || unsafe === undefined) {
        return '';
    }
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

//////////////////////////////////////////

        AttachEvent(window,'load',loadmap,false);
        </script>


	<?


$smarty->display('_std_end.tpl');
