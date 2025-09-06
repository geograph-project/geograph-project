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

if (empty($_GET['query']))
	$_GET['query'] = 'geograph';

//simplify the query a bit, in the vague hope of increasing cachablity of tiles!
$_GET['query'] = strtolower(trim(preg_replace('/\s+/',' ',$_GET['query'])));

print "<p>Query: <b>".htmlentities($_GET['query'])."</b>. <span id=countPrompt></span></p>";

//todo, the KML download, could use total_found to change the message.
?>

<h2>Images along the Thames</h2>

<div id="map" style="width:800px; height:700px; max-height:90vh; max-width:80vw;"></div>
<div id="results"></div>


        <link rel="stylesheet" type="text/css" href="https://unpkg.com/leaflet@1.3.1/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.3.1/dist/leaflet.js" type="text/javascript"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.5.0/proj4.js"></script>
        <script type="text/javascript" src="<? echo smarty_modifier_revision("/js/Leaflet.MetricGrid.js"); ?>"></script>
        <script type="text/javascript" src="<? echo smarty_modifier_revision("/js/mappingLeaflet.js"); ?>"></script>

	<script src="https://www.geograph.org/leaflet/leaflet-hash.js"></script>

<script type="text/javascript">
var map = null ;
var issubmit = false;
var static_host = '<? echo $CONF['STATIC_HOST']; ?>';

//////////////////////////////////////////

var images;
var queryFilter = null;
var whereFilter = null;

function loadmap() {

	//stolen from Leaflet.base-layers.js - alas that file no compatible with mappingLeaflet.js at the moment :(
	var bounds = L.latLngBounds(L.latLng(49.863788, -13.688451), L.latLng(60.860395, 1.795260));

	setupBaseMap(); //creates the map, but does not initialize a view
	map.fitBounds(bounds,{maxZoom:15});

	var hash = new L.Hash(map);

        const urlParams = new URLSearchParams(window.location.search);

        queryFilter = urlParams.get('query') || 'geograph';

//////////////////////////////////////////

	const polygonStr = "-0.07540,51.50359+-0.06557,51.49963+-0.05591,51.50046+-0.05154,51.50170+-0.04568,51.50450+-0.04556,51.50563+-0.04359,51.50668+-0.04074,51.50708+-0.03537,51.50625+-0.03464,51.50575+-0.03392,51.50196+-0.03317,51.50163+-0.03316,51.50059+-0.03418,51.50020+-0.03329,51.49876+-0.03435,51.49854+-0.03461,51.49688+-0.03336,51.49643+-0.03475,51.49565+-0.03350,51.49500+-0.03387,51.49398+-0.03069,51.48927+-0.03397,51.48713+-0.03135,51.48435+-0.03132,51.48323+-0.02556,51.48304+-0.02467,51.48422+-0.02227,51.48392+-0.01965,51.48318+-0.01909,51.48155+-0.01777,51.48099+-0.01469,51.48109+-0.01270,51.48244+-0.00289,51.48446+0.00277,51.48832+0.00351,51.49195+0.00207,51.49450+0.00267,51.49516+0.00166,51.49624+0.00006,51.49603+-0.00145,51.50010+-0.00017,51.50179+-0.00116,51.50249+-0.00064,51.50318+0.00271,51.50449+0.00565,51.50314+0.01417,51.49597+0.01852,51.49385+0.01955,51.49426+0.02656,51.49292+0.04159,51.49517+0.04063,51.49588+0.02658,51.49383+0.01836,51.49494+0.01323,51.49795+0.00497,51.50502+0.00191,51.50529+-0.00187,51.50365+-0.00289,51.50008+-0.00094,51.49510+0.00094,51.49532+0.00038,51.49464+0.00204,51.49196+0.00153,51.48895+-0.00365,51.48522+-0.01730,51.48190+-0.01850,51.48382+-0.02252,51.48514+-0.02537,51.48533+-0.02620,51.48393+-0.02992,51.48386+-0.02995,51.48475+-0.03242,51.48709+-0.02908,51.48911+-0.03173,51.49302+-0.03202,51.49712+-0.03310,51.49735+-0.03189,51.49824+-0.03165,51.50235+-0.03340,51.50422+-0.03285,51.50565+-0.03470,51.50706+-0.04345,51.50770+-0.05286,51.50235+-0.06028,51.50097+-0.06590,51.50065+-0.07202,51.50379+-0.07687,51.50456+-0.07540,51.50359";

        var url = `https://api.geograph.org.uk/api-facetql.php?match=${encodeURIComponent(queryFilter)}&order=sequence+asc&limit=50&select=id,title,realname,wgs84_lat,wgs84_long,hash`;

        if (polygonStr) {
	    url += addPolygonFilter(polygonStr);
        }

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.rows) {
                    var markerBounds = L.latLngBounds();
                    data.rows.forEach(row => {
                        if (row.wgs84_lat && row.wgs84_long) {
                            var latLng = L.latLng(rad2deg(row.wgs84_lat), rad2deg(row.wgs84_long));
                            var marker = L.marker(latLng).addTo(map);
                            var popupContent = `<a href="https://www.geograph.org.uk/photo/${row.id}" target="_blank">` +
                                               `<img src="${getGeographUrl(row.id, row.hash, 'med')}"><br>` +
                                               `${escapeHtml(row.title)}</a> by ${escapeHtml(row.realname)}`;
                            marker.bindPopup(popupContent);
                            marker.bindTooltip(row.title);
                            markerBounds.extend(latLng);
                        }
                    });
                    if (markerBounds.isValid()) {
                        map.fitBounds(markerBounds);
                    }
                    if (data.meta && data.meta.total_found)
			document.getElementById('countPrompt').innerText = "Showing "+data.rows.length+" of "+data.meta.total_found;
		    images = data.rows;
                }
            });
}

//////////////////////////////////////////

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
	if (addToMap)
            L.polygon(latlngs, {color: 'red', stroke:0, opacity: 0.2, fillOpacity: 0.2}).addTo(map);

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
