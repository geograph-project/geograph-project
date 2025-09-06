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
	<link rel="stylesheet" href="/js/LeafletLineMarker.css?<? echo filemtime('../js/LeafletLineMarker.css'); ?>" />


        <script src="https://unpkg.com/leaflet@1.3.1/dist/leaflet.js" type="text/javascript"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.5.0/proj4.js"></script>
        <script type="text/javascript" src="<? echo smarty_modifier_revision("/js/Leaflet.MetricGrid.js"); ?>"></script>
        <script type="text/javascript" src="<? echo smarty_modifier_revision("/js/mappingLeaflet.js"); ?>"></script>

	<script src="https://www.geograph.org/leaflet/leaflet-hash.js"></script>
        <script src="/js/LeafletLineMarker.js?<? echo filemtime('../js/LeafletLineMarker.js'); ?>"></script>


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

	let polygonList = [
		"-0.07540,51.50359+-0.06557,51.49963+-0.05591,51.50046+-0.05154,51.50170+-0.04568,51.50450+-0.04556,51.50563+-0.04359,51.50668+-0.04074,51.50708+-0.03537,51.50625+-0.03464,51.50575+-0.03392,51.50196+-0.03317,51.50163+-0.03316,51.50059+-0.03418,51.50020+-0.03329,51.49876+-0.03435,51.49854+-0.03461,51.49688+-0.03336,51.49643+-0.03475,51.49565+-0.03350,51.49500+-0.03387,51.49398+-0.03069,51.48927+-0.03397,51.48713+-0.03135,51.48435+-0.03132,51.48323+-0.02556,51.48304+-0.02467,51.48422+-0.02227,51.48392+-0.01965,51.48318+-0.01909,51.48155+-0.01777,51.48099+-0.01469,51.48109+-0.01270,51.48244+-0.00289,51.48446+0.00277,51.48832+0.00351,51.49195+0.00207,51.49450+0.00267,51.49516+0.00166,51.49624+0.00006,51.49603+-0.00145,51.50010+-0.00017,51.50179+-0.00116,51.50249+-0.00064,51.50318+0.00271,51.50449+0.00565,51.50314+0.01417,51.49597+0.01852,51.49385+0.01955,51.49426+0.02656,51.49292+0.04159,51.49517+0.04063,51.49588+0.02658,51.49383+0.01836,51.49494+0.01323,51.49795+0.00497,51.50502+0.00191,51.50529+-0.00187,51.50365+-0.00289,51.50008+-0.00094,51.49510+0.00094,51.49532+0.00038,51.49464+0.00204,51.49196+0.00153,51.48895+-0.00365,51.48522+-0.01730,51.48190+-0.01850,51.48382+-0.02252,51.48514+-0.02537,51.48533+-0.02620,51.48393+-0.02992,51.48386+-0.02995,51.48475+-0.03242,51.48709+-0.02908,51.48911+-0.03173,51.49302+-0.03202,51.49712+-0.03310,51.49735+-0.03189,51.49824+-0.03165,51.50235+-0.03340,51.50422+-0.03285,51.50565+-0.03470,51.50706+-0.04345,51.50770+-0.05286,51.50235+-0.06028,51.50097+-0.06590,51.50065+-0.07202,51.50379+-0.07687,51.50456+-0.07540,51.50359",
		"-0.21479,51.46597+-0.21472,51.46517+-0.21261,51.46504+-0.21234,51.46434+-0.20816,51.46304+-0.19980,51.46226+-0.19867,51.46133+-0.19682,51.46099+-0.19256,51.46113+-0.19020,51.46188+-0.19031,51.46299+-0.18889,51.46329+-0.18721,51.46289+-0.18596,51.46409+-0.18204,51.46595+-0.17978,51.46809+-0.17784,51.46783+-0.17634,51.46898+-0.17691,51.47077+-0.17759,51.47105+-0.17650,51.47173+-0.17607,51.47489+-0.17431,51.47799+-0.17151,51.47977+-0.16789,51.48064+-0.15080,51.48282+-0.15120,51.48031+-0.15010,51.47936+-0.15026,51.47822+-0.14557,51.47759+-0.13964,51.47986+-0.13917,51.48031+-0.13977,51.48102+-0.13952,51.48161+-0.13840,51.48170+-0.13810,51.48227+-0.13651,51.48206+-0.12782,51.48456+-0.12444,51.48736+-0.12332,51.48766+-0.12108,51.49286+-0.11855,51.50405+-0.11810,51.50506+-0.11561,51.50693+-0.10734,51.50826+-0.09663,51.50798+-0.09122,51.50657+-0.09049,51.50591+-0.08343,51.50596+-0.07641,51.50392+-0.07551,51.50448+-0.08076,51.50637+-0.08980,51.50713+-0.09633,51.50887+-0.10751,51.50916+-0.11627,51.50773+-0.11935,51.50552+-0.11994,51.50428+-0.12249,51.49305+-0.12458,51.48833+-0.12868,51.48531+-0.13204,51.48425+-0.13925,51.48308+-0.14077,51.48217+-0.14128,51.48087+-0.14091,51.48046+-0.14585,51.47848+-0.14868,51.47878+-0.14872,51.47963+-0.14963,51.48046+-0.14935,51.48306+-0.15060,51.48375+-0.16824,51.48152+-0.17221,51.48056+-0.17555,51.47846+-0.17738,51.47545+-0.17780,51.47242+-0.17852,51.47219+-0.17919,51.47075+-0.17817,51.47023+-0.17781,51.46929+-0.17843,51.46879+-0.18059,51.46897+-0.18443,51.46567+-0.18742,51.46462+-0.18752,51.46399+-0.18859,51.46429+-0.19163,51.46363+-0.19158,51.46242+-0.19298,51.46199+-0.19616,51.46188+-0.19892,51.46307+-0.20780,51.46394+-0.21111,51.46495+-0.21123,51.46560+-0.21377,51.46649+-0.21479,51.46597"
	];

	const fetchPromises = polygonList.map(polygonStr => {
	    // Start with the base URL
	    let url = `https://api.geograph.org.uk/api-facetql.php?match=${encodeURIComponent(queryFilter)}&order=sequence+asc&limit=10&select=id,title,realname,wgs84_lat,wgs84_long,hash,takenday`;

	    // Conditionally add the polygon filter
	    if (polygonStr) {
	        url += addPolygonFilter(polygonStr);
	    }

	    // Return the fetch promise for this URL
	    return fetch(url).then(response => response.json());
	});

        let markerBounds = L.latLngBounds();
        let total = 0;
	let total_found = 0;
	Promise.all(fetchPromises)
	    .then(results => {
	        // Loop through each result and add its rows to the list
	        results.forEach(data => {
        	    if (data.rows) {
        	            data.rows.forEach(row => {
                	        if (row.wgs84_lat && row.wgs84_long) {
                        	    var latLng = L.latLng(rad2deg(row.wgs84_lat), rad2deg(row.wgs84_long));
	                            //var marker = L.marker(latLng).addTo(map);
				    var marker = L.lineMarker(latLng, {img: getGeographUrl(row.id, row.hash, 'small'), dir:'up', title:row.title}).addTo(map);

        	                    var popupContent = `<a href="https://www.geograph.org.uk/photo/${row.id}" target="_blank">` +
                                               `<img src="${getGeographUrl(row.id, row.hash, 'med')}"><br>` +
                                               `${escapeHtml(row.title)}</a> by ${escapeHtml(row.realname)}`;
                	            marker.bindPopup(popupContent);
                        	    //marker.bindTooltip(row.title);
	                            markerBounds.extend(latLng);
        	                }
                	    });
			    total += data.rows.length;
			    if (data.meta && data.meta.total_found)
                                total_found += parseInt(data.meta.total_found,10);
 	            }
                });

                if (markerBounds.isValid()) {
                    map.fitBounds(markerBounds);
         	}
	        if (total_found)
		    document.getElementById('countPrompt').innerText = "Showing "+total+" of "+total_found;
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
