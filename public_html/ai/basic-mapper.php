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

<h2>Approximate Polygon Results</h2>

<div id="map" style="width:800px; height:700px; max-height:90vh; max-width:80vw;"></div>
<div id="results"></div>

<div style="max-width:60em;">
<br><br>
<button type=button id=downloadBtn>Download as KML</button> <span id="message">Downloads <b>upto</b> 1000 images. Remember these results may be based on an <b>approximate</b> boundary, you might be getting images just outside the original boundary.</span>
</div>


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
        const polygonStr = urlParams.get('polygon');

//        document.getElementById('query-display').innerText = query;

        var url = `https://api.geograph.org.uk/api-facetql.php?match=${encodeURIComponent(queryFilter)}&order=sequence+asc&limit=50&select=id,title,realname,wgs84_lat,wgs84_long,hash`;

        if (polygonStr) {
            var latlngs = polygonStr.split(' ').map(function(coord) {
                var pair = coord.split(',');
                return L.latLng(parseFloat(pair[1]), parseFloat(pair[0]));
            });
            L.polygon(latlngs, {color: 'red', stroke:0, opacity: 0.2, fillOpacity: 0.2}).addTo(map);

            var radianCoords = latlngs.map(function(latlng) {
                var lngRad = (latlng.lng * Math.PI / 180).toFixed(6);
                var latRad = (latlng.lat * Math.PI / 180).toFixed(6);
                return [lngRad, latRad];
            });
            var radianList = radianCoords.flat().join(',');
            whereFilter = `CONTAINS(GEOPOLY2D(${radianList}),wgs84_long,wgs84_lat)`;
            //url += `&where=${encodeURIComponent(where)}`;
            //wreirdly sphinx/manticore, cant do the expressons direct in where!, need adding to select!
	    url += `,${encodeURIComponent(whereFilter)}+as+inside&where=${encodeURIComponent('inside=1')}`;
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

	const messageDiv = document.getElementById('message');
	const downloadBtn = document.getElementById('downloadBtn');

// Function to handle the KML generation and download
            const generateAndDownloadKML = async (query) => {
                // Show loading state
                downloadBtn.textContent = 'Generating...';
                messageDiv.textContent = '';
                messageDiv.className = 'mt-4 text-center text-sm font-medium';

                // API request to get images
                let url = `https://api.geograph.org.uk/api-facetql.php?match=${encodeURIComponent(queryFilter)}&order=sequence+asc&limit=1000&select=id,title,realname,wgs84_lat,wgs84_long,hash,takenday`;
		if (whereFilter)
		    url += `,${encodeURIComponent(whereFilter)}+as+inside&where=${encodeURIComponent('inside=1')}`;

		const KML_MIME_TYPE = 'application/vnd.google-earth.kml+xml';

                try {
                    const response = await fetch(url);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    const data = await response.json();
                    
                    if (!data.rows || data.rows.length === 0) {
                        messageDiv.textContent = 'No images found for this query.';
                        messageDiv.classList.add('text-red-500');
                        return;
                    }

                    // Start building the KML content for each placemark
                    let placemarkContent = '';
                    
                    data.rows.forEach(row => {
                        if (row.wgs84_lat && row.wgs84_long) {
                            const lat = rad2deg(row.wgs84_lat);
                            const long = rad2deg(row.wgs84_long);
                            const thumbnailUrl = getGeographUrl(row.id, row.hash, 'small');
                            const fullUrl = getGeographUrl(row.id, row.hash, 'full');

                            // Build the Placemark XML string based on the user's detailed example
                            const descriptionHtml = `<a href="https://www.geograph.org.uk/photo/${row.id}" target="_blank">
                                <img alt="${escapeHtml(row.title)} by ${escapeHtml(row.realname)}" src="${fullUrl}" />
                                </a><br/>${escapeHtml(row.description || '')} (<a href="https://www.geograph.org.uk/photo/${row.id}">View page on Geograph</a>)<br/><br/>
				&copy; Copyright <a title="view user profile" href="https://www.geograph.org.uk/profile/${row.realname}">${escapeHtml(row.realname)}</a>
                                and licensed for reuse under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/">Creative Commons Licence</a>`;

                            placemarkContent += `
<Placemark id="id${row.id}">
  <name>${escapeHtml(row.title)}</name>
  <description><![CDATA[${descriptionHtml}]]></description>
  <Snippet><![CDATA[${escapeHtml(row.description || '').substring(0, 100)}...]]></Snippet>
  <visibility>1</visibility>
  <atom:link href="https://www.geograph.org.uk/photo/${row.id}" />
  <Point>
    <coordinates>${long},${lat},0</coordinates>
  </Point>
  <atom:author>
    <atom:name>${escapeHtml(row.realname)}</atom:name>
  </atom:author>
  <Style>
    <IconStyle>
      <Icon>
        <href>${thumbnailUrl}</href>
      </Icon>
    </IconStyle>
  </Style>
  <styleUrl>https://staging.data.geograph.org.uk/facets/style.kmz#def</styleUrl>
  <TimeStamp>
    <when>${row.date ? new Date(row.date).toISOString().split('T')[0] : ''}</when>
  </TimeStamp>
</Placemark>`;
                        }
                    });

                    if (placemarkContent === '') {
                         messageDiv.textContent = 'No images with valid coordinates were found.';
                         messageDiv.classList.add('text-red-500');
                         return;
                    }

                    // Full KML XML structure
                    const kmlContent = '<'+'?xml version="1.0" encoding="UTF-8"?'+'>'+`
<kml xmlns="http://www.opengis.net/kml/2.2" xmlns:atom="http://www.w3.org/2005/Atom">
  <Document>
    <name>Geograph Images</name>
    ${placemarkContent}
  </Document>
</kml>`;

                    // Create a Blob and trigger the download
                    const blob = new Blob([kmlContent], { type: KML_MIME_TYPE });
                    const kmlurl = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = kmlurl;
                    a.download = `geograph_images.kml`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(kmlurl);

                    messageDiv.textContent = `KML file with ${data.rows.length} placemarks generated and downloaded successfully!`;
                    messageDiv.classList.add('text-green-600');

                } catch (error) {
                    messageDiv.textContent = 'An error occurred. Check the console for details.';
                    messageDiv.classList.add('text-red-500');
                    console.error("Error fetching data or generating KML:", error);
                } finally {
                    // Reset button state
                    downloadBtn.textContent = 'Generate & Download KML';
                }
            };

            downloadBtn.addEventListener('click', () => {
/*                const query = searchQueryInput.value.trim();
                if (query === '') {
                    messageDiv.textContent = 'Please enter a search query.';
                    messageDiv.classList.add('text-red-500');
                    return;
                }
                generateAndDownloadKML(query);
*/
		generateAndDownloadKML();
            });


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

        AttachEvent(window,'load',loadmap,false);
        </script>


	<?


$smarty->display('_std_end.tpl');
