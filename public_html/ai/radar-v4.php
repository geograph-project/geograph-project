<?php

require_once('geograph/global.inc.php');
init_session();

$USER->mustHavePerm('basic');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Geograph Scout - Dev Prototype</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="/js/Leaflet.GeographScout.js?<? echo filemtime(__DIR__.'/../js/Leaflet.GeographScout.js'); ?>"></script>
    <script type="text/javascript" src="<? echo smarty_modifier_revision("/mapper/geotools2.js"); ?>"></script>

    <style>
        body { margin: 0; display: flex; flex-direction: column; height: 100vh; font-family: sans-serif; }
        #controls { padding: 15px; background: #2c3e50; color: white; display: flex; gap: 20px; flex-wrap: wrap; }
        #map { flex-grow: 1; }
        .status-bar { background: #ecf0f1; padding: 5px 15px; font-size: 0.8em; border-bottom: 1px solid #ccc; }
	label { white-space: nowrap; }
    </style>
</head>
<body>

<div class="status-bar" id="status">Initializing GPS...</div>
<div id="map"></div>

<script>
    // --- CONFIG & STATE ---
    const map = L.map('map'); //.setView([57.4, -2.9], 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

    let userMarker = L.circleMarker([0,0], {color: 'blue', radius: 8, fillOpacity: 0.8}).addTo(map);
	let geographScout = null;

    // --- LOGIC ---

    async function updatePosition(lat, lng) {
        const userLatLng = L.latLng(lat, lng);
        userMarker.setLatLng(userLatLng);

	if (!geographScout) {
		//we now start without location, so itialize it the first time!
		map.setView(userLatLng, 12);

	} else {
	    // 2. Smart Panning Logic
	    // We get the current bounds and "pad" them negatively by 25%
	    // to create a central 50% "safe zone"
	    const currentBounds = map.getBounds();
	    const safeZone = currentBounds.pad(-0.4);
	    const extraZone = currentBounds.pad(3);

	    if (!extraZone.contains(userLatLng)) {
	        //might as well go right there
	        map.setView(userLatLng, map.getZoom(), { animate: false });

	    } else if (!currentBounds.contains(userLatLng)) {
	        //try to scroll nicely.
	        map.panTo(userLatLng);

	    } else if (!safeZone.contains(userLatLng)) {
	        // panInside is great because it only moves the map
	        // the minimum distance required to show the point
	        map.panInside(userLatLng, {
	            padding: [100, 100], // Extra pixel padding from the edge
	            animate: true,
	            duration: 0.5
	        });
	    }
        }

        document.getElementById('status').innerText = `Position Uploaded`;
        if (!geographScout) {
		// 1. Initialize the plugin
		geographScout = L.geographScout({
		    user_id: <? echo intval($USER->user_id); ?>,
		    apiUrl: '/api-scout.php'
		});

		// 2. (Optional) Add it to the map by default
		geographScout.addTo(map);

		// 3. Add it to the standard Leaflet Layer Control
		const overlays = {
			    "Geograph Scout": geographScout
		};
		L.control.layers(null, overlays).addTo(map);
	}
    }

    // --- GEOLOCATION START ---
    if ("geolocation" in navigator) {
        navigator.geolocation.watchPosition(
            (pos) => updatePosition(pos.coords.latitude, pos.coords.longitude),
            (err) => console.error(err),
            { enableHighAccuracy: true }
        );
    }

</script>
</body>
</html>
