<?php

require_once('geograph/global.inc.php');
init_session();

?>
<!DOCTYPE html>
<html>
<head>
   <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Page</title>

    <style>
        :root {
            --primary: #007AFF;
            --success: #28a745;
            --bg: #f8f9fa;
            --accent: #6c757d;
        }
        body { font-family: -apple-system, system-ui, sans-serif; background: var(--bg); margin: 0; padding: 20px; }
        .card { background: white; padding: 24px; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); width: 100%; max-width: 450px; text-align: center; }

        @media screen and (max-width: 500px) {
                body {
                        padding:20px 2px;
                }
                .card {
                        padding:20px 2px;
                }
        }

        /* Custom Buttons */
        .btn { padding: 14px 28px; border-radius: 12px; border: none; cursor: pointer; touch-action: manipulation; user-select: none; font-weight: 600; transition: all 0.2s; display: inline-block; margin: 8px 0; font-size: 16px; }
        .btn-select { background: var(--primary); color: white; width: 100%; box-sizing: border-box; }
        .btn-upload { background: var(--primary); color: white; width: 100%; }
        .btn-upload:disabled { background: #ccc; cursor: not-allowed; }
        .btn-secondary { background: #e9ecef; color: #333; width: 100%; }

</style>

    <link rel="stylesheet" type="text/css" href="https://unpkg.com/leaflet@1.3.1/dist/leaflet.css" />
    <link rel="stylesheet" type="text/css" href="<?php echo smarty_modifier_revision("/js/mappingLeaflet.css"); ?>" />
    <script src="<?php echo smarty_modifier_revision("/mapper/geotools2.js"); ?>"></script>
    <script src="https://unpkg.com/leaflet@1.3.1/dist/leaflet.js" type="text/javascript"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.7.0/proj4.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/proj4leaflet/1.0.2/proj4leaflet.min.js"></script>

    <script src="<?php echo smarty_modifier_revision("/js/Leaflet.MetricGrid.js"); ?>"></script>
    <script src="<?php echo smarty_modifier_revision("/js/mappingLeaflet.js"); ?>"></script>
    <script src="<?php echo smarty_modifier_revision("/js/Leaflet.GeographRecentUploads.js"); ?>"></script>
</head>

<body>
    <p>Use this button to capture a new image with your camera. The current location will be written to the filename, such that when upload the file later, the location wont be stripped by privacy concious browsers.
	<label for="cameraInput" class="btn btn-select" id="select-label">Capture Image</label>
    <input type="file" id="cameraInput" accept="image/*" capture="environment" hidden>

    <button id="downloadBtn" class="btn" style="display:none;">2. Save to Downloads</button>

    <p>Use this button to save a note, its tagged with our current position, can be referened later (on this device only!) during submission.
    <button id="saveBtn" class="btn btn-select" onclick="alert('non functional')">Save Local Note (placeholder)</button>

	<br>
	<br>

    <label><input type=checkbox name=show_map onclick="toggleMap(this.checked)"> Show Live Map</label> (helps make sure your device has a good GPS lock)
    <div id="map" style="width:350px; height:350px; max-height:90vh; max-width:80vw;"></div>

    <script>
        var map = null ;
        var issubmit = false;
        var static_host = '<? echo $CONF['STATIC_HOST']; ?>';
        let mapMarker = null;
        var historyPoints = null;

        function toggleMap(enable) {
        	if (enable) {
        		if (map) {
        			//loaded before
        			document.getElementById('map').style.display = '';
        			//in theory  still there.
        		} else {
        			loadmap();
        		        initLocationTracking();
        		}
        	} else {
        		document.getElementById('map').style.display = 'none';
        		//todo, would be disable the watchPosition
        	}
        }

        let accuracyCircle;
        let latestCoords = null;

        // Initialize the map and track position
        function initLocationTracking() {
            if (!navigator.geolocation) return;

            navigator.geolocation.watchPosition((pos) => {
                const { latitude, longitude, accuracy } = pos.coords;
                const latlng = [latitude, longitude];

                // Update or create map marker
                if (!mapMarker) {
                    mapMarker = L.marker(latlng).addTo(map);
                    map.setView(latlng, 16);
                } else {
                    mapMarker.setLatLng(latlng);
                }

                // Visualize accuracy (a circle around the user)
                if (accuracyCircle) map.removeLayer(accuracyCircle);
                accuracyCircle = L.circle(latlng, { radius: accuracy }).addTo(map);

                // Store for the 'Save' button
                latestCoords = { lat: latitude, lng: longitude };
            }, (err) => console.error(err), { enableHighAccuracy: true });
        }

        let latestFile = null;
    	let fileList = document.getElementById('fileList');

    	// Pad function to ensure 05 becomes '05'
	    const pad = (n) => n.toString().padStart(2, '0');
        let datePart;
	    let timePart;

        document.getElementById('cameraInput').addEventListener('change', (e) => {
            latestFile = e.target.files[0];
            // Get location immediately on capture
            navigator.geolocation.getCurrentPosition((pos) => {
                latestCoords = { lat: pos.coords.latitude, lng: pos.coords.longitude };

        		const now = new Date();
        		datePart = `${now.getFullYear()}${pad(now.getMonth() + 1)}${pad(now.getDate())}`;
		        timePart = `${pad(now.getHours())}${pad(now.getMinutes())}${pad(now.getSeconds())}`;

                document.getElementById('downloadBtn').style.display = 'block';
                document.getElementById('downloadBtn').textContent = `Download ${timePart} Again`;
                document.getElementById('downloadBtn').click();
            });
        });

        document.getElementById('downloadBtn').addEventListener('click', () => {
            if (!latestFile) return;

    		if (!latestCoords || !latestCoords.lat) {
    			 newName = `IMG_${datePart}_${timePart}_unknown.jpg`;
    		} else {
    			var wgs84 = new GT_WGS84();
    			wgs84.setDegrees(latestCoords.lat, latestCoords.lng);
    			let gridref = wgs84.getGridRef(10);

    			if (gridref) {
    				//this is our proper format, date/time for easy sorting, plus GR
    			    newName = `IMG_${datePart}_${timePart}_${gridref.replace(/ /g,'')}.jpg`;
    			} else {
    				//fallback (outside GB/Ire :)
    			    newName = `IMG_${latestCoords.lat.toFixed(6)}_${latestCoords.lng.toFixed(6)}.jpg`;
    			}
    		}

            // Trigger download
            const link = document.createElement('a');
            link.href = URL.createObjectURL(latestFile);
            link.download = newName;
            link.click();

            // Add to list for visual confirmation
    		if (fileList) {
    	        const li = document.createElement('li');
                li.textContent = `Saved: ${newName}`;
    	        document.getElementById('fileList').appendChild(li);
    		}

    		//add to map!
    		if (map && historyPoints) {
    			L.circleMarker([latestCoords.lat, latestCoords.lng], {radius:6}).addTo(historyPoints);
    		}
        });


       function loadmap() {

            //stolen from Leaflet.base-layers.js - alas that file no compatible with mappingLeaflet.js at the moment :(

            var layerAttrib='&copy; Geograph Project';
            var layerUrl='https://t0.geograph.org.uk/tile/tile-coverage.php?z={z}&x={x}&y={y}';
            var bounds = L.latLngBounds(L.latLng(49.863788, -13.688451), L.latLng(60.860395, 1.795260));
            var coverageCoarse = new L.TileLayer(layerUrl, {user_id: 0, minZoom: 5, maxZoom: 12, attribution: layerAttrib, bounds: bounds, opacity:0.6});
            overlayMaps["Geograph Coverage"] = coverageCoarse;

            setupBaseMap(); //creates the map, but does not initialize a view

        	historyPoints = new L.FeatureGroup().addTo(map);
     //     map.fitBounds(bounds,{maxZoom:15});
       }

//now only loaded when tick the box!
// window.addEventListener('DOMContentLoaded', loadmap);
    </script>
</body>
</html>
