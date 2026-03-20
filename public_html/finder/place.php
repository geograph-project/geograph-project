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


$smarty->assign('responsive',1);

$smarty->display('_basic_begin.tpl',filemtime(__FILE__));


//        $db = GeographDatabaseConnection(true);
?>
<style>

/* The wrapper created by Leaflet */
.custom-label-wrapper {
    width: auto !important;
    height: auto !important;
}

.map-label {
    position: relative;
    display: inline-block;
    padding: 5px 10px;
    background: white;
    border: 1px solid #aaa;
    border-radius: 8px;
    white-space: normal;      /* Allow wrapping */
    max-width: 120px;         /* Control wrap point */
    min-width: 40px;
    text-align: center;
    font-size: 12px;
    font-weight: bold;
    
    /* THE MAGIC: Move the label so the bottom-center 
       is exactly over the lat/lng point */
    transform: translate(-50%, -100%); 
    margin-top: -10px; /* Offset for the arrow height */
}

/* The Arrow */
.map-label::after {
    content: '';
    position: absolute;
    top: 100%; /* At the bottom of the label */
    left: 50%;
    margin-left: -8px;
    border-width: 8px;
    border-style: solid;
    border-color: #999 transparent transparent transparent;
}

/* The subtle highlight for the top result */
.map-label.primary-match {
    background: #fff9c4; /* Light yellow */
    border: 1px solid #fbc02d; /* Gold/Yellow border */
    box-shadow: 0 0 8px rgba(251, 192, 45, 0.4); /* Soft glow */
    font-weight: bold;
}

/* Level 1: Starts With (Light highlight) */
.map-label.match-starts {
    background: #fffde7; /* Very pale yellow */
    border: 1px solid #fbc02d;
}

/* Level 2: Exact Match (Stronger highlight) */
.map-label.match-exact {
    background: #fff59d; /* Noticeable yellow */
    border: 2px solid #fbc02d;
    font-weight: bold;
}

.map-query-info {
    background: rgba(255, 255, 255, 0.5);
    padding: 5px 10px;
    --border: 2px solid rgba(0,0,0,0.2);
    border-radius: 4px;
    font-family: sans-serif;
    font-size: 13px;
    color: #333;
    pointer-events: none; /* Allows clicks to pass through to the map */
    box-shadow: 0 1px 5px rgba(0,0,0,0.4);
    margin-top: 10px;
    margin-right: 10px;
}

/* Reset and Full Height */
html, body {
    height: 100%;
    margin: 0;
    padding: 0;
    overflow: hidden; /* Prevent double scrollbars */
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

.search-map-body {
    display: flex;
    flex-direction: column;
    height: 100vh;
}

/* Header and Input Styling */
.search-header {
    padding: 5px;

padding-right: 50px; /*avoid the parent close button */

    background: #f8f9fa;
    border-bottom: 1px solid #ddd;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    z-index: 1001; /* Stay above map */
}

#loc {
    width: 100%;
    padding: 12px 16px;
    font-size: 16px; /* Prevents iOS zoom-on-focus */
    border: 1px solid #ccc;
    border-radius: 8px;
    box-sizing: border-box;
    transition: border-color 0.2s, box-shadow 0.2s;
    outline: none;
}

#loc:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

/* Map takes remaining space */
#map-container {
    flex-grow: 1;
    position: relative;
}

#map {
    position: absolute;
    top: 0;
    bottom: 0;
    width: 100%;
    height: 100%;
    max-width:800px;
    max-height:600px;

    cursor: crosshair;

}

.search-header form {
    display:flex;
}
.search-header #loc {
    flex:1;
}
#hide-keyboard {
    display: none; /* Hidden by default */
    margin-left: 8px;
    padding: 0 15px;
    background: #5ea5f1;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    white-space: nowrap;
    font-size:2em;
}

/* Show the button only when the keyboard is likely up */
@media (orientation: landscape) and (max-height: 500px) {
    .keyboard-open #hide-keyboard {
        display: block;
    }
}

</style>

<body class="search-map-body">
    <header class="search-header">
        <form onsubmit="return false;">
            <input type="search" name="loc" id="loc" 
                   placeholder="Enter placename (e.g., Brighton)" 
                   autocomplete="off">
            <button type="button" id="hide-keyboard" title="Show Map"> &#128506; </button>
        </form>
    </header>

    <div id="map-container">
        <div id="map"></div>
    </div>
</body>

        <link rel="stylesheet" type="text/css" href="https://unpkg.com/leaflet@1.3.1/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.3.1/dist/leaflet.js" type="text/javascript"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.5.0/proj4.js"></script>
        <script type="text/javascript" src="<? echo smarty_modifier_revision("/js/Leaflet.MetricGrid.js"); ?>"></script>
        <script type="text/javascript" src="<? echo smarty_modifier_revision("/js/mappingLeaflet.js"); ?>"></script>
        <script type="text/javascript" src="<? echo smarty_modifier_revision("/mapper/geotools2.js"); ?>"></script>

        <script type="text/javascript">
        var map = null ;
        var allMarkers = []; // Array to hold marker references
        var issubmit = false;
    	var static_host = '<? echo $CONF['STATIC_HOST']; ?>';

////////////////////////////////////////////////////////

        function loadmap() {

            //stolen from Leaflet.base-layers.js - alas that file no compatible with mappingLeaflet.js at the moment :(

            var layerAttrib='&copy; Geograph Project';
            var layerUrl='https://t0.geograph.org.uk/tile/tile-coverage.php?z={z}&x={x}&y={y}';
            var bounds = L.latLngBounds(L.latLng(49.863788, -13.688451), L.latLng(60.860395, 1.795260));

            var coverageCoarse = new L.TileLayer(layerUrl, {user_id: 0, minZoom: 5, maxZoom: 12, attribution: layerAttrib, bounds: bounds, opacity:0.6});
            overlayMaps["Geograph Coverage"] = coverageCoarse;

            setupBaseMap(); //creates the map, but does not initialize a view
            //overlayMaps["Geograph Coverage"].addTo(map);

            //map.fitBounds(bounds,{maxZoom:15});

            markerGroup = L.featureGroup().addTo(map);

            document.getElementById('loc').addEventListener('keyup', handleSearchInput);

            /////////////////////////

            const urlParams = new URLSearchParams(window.location.search);
            const locParam = urlParams.get('loc');
            const inputField = document.getElementById('loc');

		    if (locParam && inputField) {
		        // 1. Fill the input box
		        inputField.value = locParam;

		        // 2. Trigger the search immediately
		        // We call the function directly to bypass the debounce delay on load
		        searchGazetteer(locParam);

                mapUtils._notify("Tap a place, and then 'Use This Location'. Or click the map.", "green");
		    } else {
                mapUtils._notify("Enter a placename or click anywhere on the map to pick a spot.", "green");
            }

            setTimeout(function() { inputField.focus(); }, 100);

inputField.addEventListener('focus', function() {
    document.body.classList.add('keyboard-open');
});

inputField.addEventListener('blur', function() {
    document.body.classList.remove('keyboard-open');
});

            /////////////////////////

            // Custom "Back to Results" Control
            L.Control.ResultsExtent = L.Control.extend({
                options: { position: 'topleft' },
                onAdd: function(map) {
                    const container = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
                    const button = L.DomUtil.create('a', 'results-extent-button', container);

                    button.innerHTML = '&#9974;';
                    button.href = '#';
                    button.title = 'Zoom to all results';
                    button.style.fontSize = '18px';

                    L.DomEvent.on(button, 'click', function(e) {
                        L.DomEvent.stop(e);

                        // Only fly if we actually have markers
                        if (markerGroup.getLayers().length > 0) {
                            map.flyToBounds(markerGroup.getBounds(), {
                                padding: [40, 40],
                                maxZoom: 15,
                                duration: 1
                            });
                        } else {
                            mapUtils._notify("No results to zoom to", "#666");
                        }
                    });

                    return container;
                }
            });

            map.addControl(new L.Control.ResultsExtent());

            const queryInfoControl = L.Control.extend({
                options: { position: 'topright' },
                onAdd: function() {
                    infoBox = L.DomUtil.create('div', 'map-query-info');
                    infoBox.style.display = 'none'; // Hidden by default
                    return infoBox;
                }
            });
            map.addControl(new queryInfoControl());


            ////////////////

            let manualMarker = null;

            map.on('click', function(e) {
                const lat = e.latlng.lat;
                const lng = e.latlng.lng;

                // 1. Calculate Grid Ref using your library
                const wgs84 = new GT_WGS84();
                wgs84.setDegrees(lat, lng);
                const gridref = wgs84.getGridRef(4);

                if (!gridref)
                    return;

                // 2. Create the Popup Content
                const container = document.createElement('div');
                container.innerHTML = `
                    <strong style="display:block; margin-bottom:4px;">Selected Location</strong>
                    <span style="font-size:12px; color:#666;">Grid Ref: ${gridref}</span><br>
                    <span style="font-size:11px; color:#999;">${lat.toFixed(6)}, ${lng.toFixed(6)}</span><br>
                `;

                const finalGridref = wgs84.getGridRef(3).replace(/ /g,''); // 6-figure GR

                const useButton = document.createElement('button');
                useButton.textContent = "Use this location";
                useButton.style.cssText = "margin-top:10px; width:100%; cursor:pointer; font-weight:bold; padding:5px;";

                useButton.addEventListener('click', () => {
                    // Return the data to the parent
                    useLocation(lat, lng, finalGridref, "Grid Reference");
                });
                container.appendChild(useButton);

                // 3. Manage the Marker
                if (manualMarker) {
                    manualMarker.setLatLng(e.latlng).setPopupContent(container).openPopup();
                } else {
                    manualMarker = L.marker(e.latlng).addTo(map).bindPopup(container).openPopup();
                }
            });
            ////////////////
        }
//        AttachEvent(window,'load',loadmap,false);

window.addEventListener('DOMContentLoaded', () => {
    loadmap();
});

////////////////////////////////////////////////////////

const mapUtils = {
    _notify: function(text, color) {
        const mapContainer = map.getContainer(); // Use your map variable
        const msg = L.DomUtil.create('div', 'leaflet-notification', mapContainer);
        
        msg.style.cssText = `position:absolute; top:70px; left:50%; transform:translateX(-50%); 
                             background:${color}; color:white; padding:8px 15px; border-radius:4px; 
                             z-index:1000; font-family:sans-serif; font-size:13px; pointer-events:none;`;
        
        msg.innerHTML = text;

        setTimeout(() => {
            msg.style.opacity = '0';
            setTimeout(() => msg.remove(), 1000);
        }, 3000);
    }
};

////////////////////////////////////////////////////////

// Initialize your FeatureGroup to hold pins and the debounce timer
let markerGroup;
let searchTimer;

function searchGazetteer(query) {
    if (query.length < 3) {
        markerGroup.clearLayers();
        // Clear attribution when search is cleared
        if (markerGroup.getAttribution) {
            markerGroup.options.attribution = "";
            map.attributionControl.removeAttribution(""); // Force refresh
        }
        return;
    }

    const url = `/finder/places.json.php?q=${encodeURIComponent(query)}&new=1`;

    fetch(url)
        .then(response => response.json())
        .then(data => {
            // 1. Clear existing markers
            markerGroup.clearLayers();

            // 2. Handle Copyright
            // First, remove the old attribution string if it exists
            if (markerGroup.options.attribution) {
                map.attributionControl.removeAttribution(markerGroup.options.attribution);
            }

            // Set the new attribution from the JSON response
            markerGroup.options.attribution = data.copyright || "";

            // Re-add it to the control
            if (markerGroup.options.attribution) {
                map.attributionControl.addAttribution(markerGroup.options.attribution);
            }

            // 1. Update/Show Query Info
            if (infoBox) {
                // Clean up the string (removes newlines and extra spaces)
                if (!data.query_info && data.total_found) {
                    infoBox.innerHTML = `${data.total_found} results`;
                } else if (!data.query_info) {
                    infoBox.innerHTML = "?";
                } else if (m = data.query_info.match(/\d+ of \d+/)) {
                    infoBox.innerHTML = m[0];
                } else {
                    infoBox.innerHTML = data.query_info.replace(/\n/g, '').trim();
                }
                infoBox.style.display = 'block';
            }

            if (!data.items || data.items.length === 0) {
        		mapUtils._notify("No locations found for '" + query + "'", "#cc0000");

                    if (!map._loaded) {
                        //but worth setting up the map for them
                        map.setView([54.0, -2.0], 6);
                    }

                return;
    	    }

            // 3. Process Items
            data.items.forEach((item,index) => {
                // Convert Grid Ref to LatLng using your library
                const centeredGR = item.gr.replace(/^(\w{1,2})(\d{2})(\d{2})$/, '$1$25$35');
                const wgs84 = GT_WGS84.parseGridRef(centeredGR);

                if (wgs84) {
                    const latLng = L.latLng(wgs84.latitude, wgs84.longitude);

                    const cleanName = item.name.split('/')[0];
                    const userQuery = query.toLowerCase().trim();
                    const compareName = cleanName.toLowerCase().trim();

                    // Determine the CSS class based on string proximity
                    let matchClass = "";
                    let matchWeight = 0; // Higher = more prominent

                    if (compareName === userQuery) {
                        matchClass = "match-exact";
                        matchWeight = 3000;
                    } else if (compareName.startsWith(userQuery)) {
                        matchClass = "match-starts";
                        matchWeight = 2000;
                    } else {
                        matchClass = ""; // Default white
                        matchWeight = 1000;
                    }

                    // Add the "primary" glow only to the very first API result
                    const isTopResult = (index === 0);
                    const labelClass = `map-label ${matchClass} ${isTopResult ? 'primary-match' : ''}`;

                    // Priority Z-Index: 
                    // Exact matches go on top of Start matches, which go on top of Contains.
                    // Within those groups, the API's original rank (index) breaks the tie.
                    const priorityZ = matchWeight - index;

            		// Create the Label Icon
                    const labelIcon = L.divIcon({
                        className: 'custom-label-container', // Wrapper class
                        html: `<div class="${labelClass}">${cleanName}</div>`,
                        iconSize: null,      // Let the content define the size
                        iconAnchor: [0, 0]   // We will handle the offset in CSS
                    });

                    // Create Popup Content
                    const container = document.createElement('div');
                    container.innerHTML = `
                        <strong style="display:block; margin-bottom:4px;">${item.name}</strong>
                        <span style="font-size:12px; color:#666;">${item.gr} ${item.localities}</span><br>
                    `;

                    // 2. Create the 'Use' link as a real DOM node
                    const useLink = document.createElement('a');
                    useLink.href = "#";
                    useLink.textContent = "Use this location";
                    useLink.style.cssText = "display:inline-block; margin-top:8px; font-weight:bold; color:#007bff; cursor:pointer; text-decoration:none;";

                    // 3. Attach a clean event listener
                    useLink.addEventListener('click', (e) => {
                        e.preventDefault();
                        // No escaping needed! We are passing the variables directly.
                        useLocation(wgs84.latitude, wgs84.longitude, item.gr, item.name);
                    });

                    container.appendChild(useLink);

                    // Create Marker and add to Group
                    L.marker(latLng, { 
                        icon: labelIcon,
                        zIndexOffset: priorityZ, // Forces the ranking order
                        riseOnHover: true        // Optional: brings any label to front when moused over
                    })
                        .bindPopup(container)
                        .addTo(markerGroup);
                }
            });

            // 4. Auto-bound the map to the markers
            if (markerGroup.getLayers().length > 0) {
                if (map._loaded) {
                    //later use aimation as search
                   map.flyToBounds(markerGroup.getBounds(), { padding: [40, 40], maxZoom: 15, duration:1 });
                } else {
                    //on first use, go right away
                   map.fitBounds(markerGroup.getBounds(), { padding: [40, 40], maxZoom: 15 });
                }

            }
        })
        .catch(err => {
		mapUtils._notify("Error searching gazetteer", "orange");
		console.error("Search error:", err)
	});
}

////////////////////////////////////////////////////////

// The Debounce Wrapper
function handleSearchInput(e) {
    clearTimeout(searchTimer);
    const query = e.target.value;
    if (query.length < 3)
    	return;
    // 500ms delay to prevent API spamming
    searchTimer = setTimeout(() => searchGazetteer(query), 500);
}

// Example 'Use this location' function
function useLocation(lat, lng, gr, name) {
    console.log("Location selected:", lat, lng);
    // Add your logic here (e.g., closing a modal or updating a form)

    // Send data to the window that opened this iframe
    window.parent.postMessage({
        type: 'PLACE_SELECTED',
        lat: lat,
        lng: lng,
        gr: gr,
        name: name
    }, '*'); // In production, replace '*' with your actual domain
}


        </script>


	<?


//$smarty->display('_std_end.tpl');


