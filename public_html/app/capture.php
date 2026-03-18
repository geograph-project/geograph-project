<?php

require_once('geograph/global.inc.php');
init_session();

?>
<!DOCTYPE html>
<html>
<head>
   <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Save Photo/Location</title>

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
        .btn-select { background: var(--primary); color: white; width: 100%; box-sizing: border-box; text-align:center }
        .btn-upload { background: var(--primary); color: white; width: 100%; }
        .btn-upload:disabled { background: #ccc; cursor: not-allowed; }
        .btn-secondary { background: #e9ecef; color: #333; width: 100%; }

	#map {
	 width:350px; height:350px; max-height:90vh; max-width:90vw;
 	margin:auto;
	}

	#noteForm {
		padding: 10px; border-radius: 8px; text-align: left;
	}
	#noteForm textarea {
		width: 100%; height: 60px; font-family:Georgia; font-size:1.1em; border-radius:8px; padding:6px;  box-sizing: border-box; 
	}
	#noteForm p { color: #666; text-align:center; }
	#noteForm input[type=text] {
		max-width:100%;
		width: 200px; margin: 8px 0; padding:8px; border: none; background: white; text-align:center
	}

h3 {
	text-align:center;
}

#notesList {
    list-style: none; 
    padding: 0;
}

.note-item {
    padding: 12px;
    border-bottom: 1px solid #eee; /* Changed dashed to solid for a cleaner look */
    margin-bottom: 10px;
}

.note-header {
    display: flex;
    justify-content: space-between; /* Pushes content to the edges */
    align-items: center;            /* Vertically centers them */
    gap: 10px;
    margin-bottom: 8px;
}

.note-body {
    font-family:Georgia;
    color: #333;
    line-height: 1.4;
}

.delete-btn {
    background: #ffeded;
    color: #d93025;
    border: 1px solid #f8d7da;
    border-radius: 4px;
    padding: 4px 8px;
    cursor: pointer;
    user-select: none;
}

.delete-btn:hover {
    background: #f8d7da;
}

</style>

    <link rel="stylesheet" type="text/css" href="https://unpkg.com/leaflet@1.3.1/dist/leaflet.css" />
    <link rel="stylesheet" type="text/css" href="<?php echo smarty_modifier_revision("/js/mappingLeaflet.css"); ?>" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-geotag-photo@0.5.1/dist/Leaflet.GeotagPhoto.css" />

    <script src="<?php echo smarty_modifier_revision("/mapper/geotools2.js"); ?>"></script>
    <script src="https://unpkg.com/leaflet@1.3.1/dist/leaflet.js" type="text/javascript"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.7.0/proj4.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/proj4leaflet/1.0.2/proj4leaflet.min.js"></script>
    <script src="https://unpkg.com/leaflet-geotag-photo@0.5.1/dist/Leaflet.GeotagPhoto.min.js"></script>

    <script src="<?php echo smarty_modifier_revision("/js/Leaflet.MetricGrid.js"); ?>"></script>
    <script src="<?php echo smarty_modifier_revision("/js/mappingLeaflet.js"); ?>"></script>
    <script src="<?php echo smarty_modifier_revision("/js/Leaflet.GeographRecentUploads.js"); ?>"></script>
</head>

<body>

    <p>Use this button to take a photo with your camera. Location data will be included in the filename, which will be saved to your
    Downloads folder. If you don't receive a download notification, you can try the download again.

	<label for="cameraInput" class="btn btn-select" id="select-label">Take Photo</label>
    <input type="file" id="cameraInput" accept="image/*" capture="environment" hidden>

	<div id="takeMessage"></div>
    <button id="downloadBtn" class="btn" style="display:none;">Save to Downloads</button>

    <p>Or seperately, use this button to save a note tagged with your location which can be used during submission (accessible on this device only). Notes are listed at the bottom of the page.
    <button id="saveBtn" class="btn btn-select">Create Location Note</button>

    <div id="noteForm" style="display:none;">
        <textarea id="noteText" wrap="soft" maxlength="255" placeholder="Enter note..."></textarea>
        <p>Drag the map to refine position of the cross-hairs...<br><input type="text" id="noteCoords" readonly></p>
	<div id="positionNote"></div>
        <button class="btn btn-select" onclick="saveNoteToLocalStorage()">Save Note</button>
    </div>

	<br>

    <label><input type=checkbox name=show_map onclick="toggleMap(this.checked)"> <b>Show Live Map</b> (helps see if a good GPS lock)</label>
    <div id="map"></div>

    <h3>Saved Notes</h3>
    <ul id="notesList">none</ul>

    <script>
        var map = null ;
        var issubmit = false;
        var static_host = '<? echo $CONF['STATIC_HOST']; ?>';
        let mapMarker = null;
        var historyPoints = null;

//////////////////////////////////////////////
// Saving Notes Function

const btn = document.getElementById('saveBtn');

btn.onclick = () => {
    const form = document.getElementById('noteForm');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
    if(form.style.display === 'block') {
        btn.style.opacity = 0.4;
        toggleMap(true); // Ensure map is enabled
        document.querySelector('input[name=show_map]').checked = true;
        updateCoordsField(map.getCenter());
    } else {
        btn.style.opacity = 1;
   }
};

function updateCoordsField(latlng) {
    document.getElementById('noteCoords').value = `${latlng.lat.toFixed(6)}, ${latlng.lng.toFixed(6)}`;
}

const noteText = document.getElementById('noteText');

noteText.addEventListener('input', (e) => {
	if (noteText.value != '') noteText.setCustomValidity('');
});

function saveNoteToLocalStorage() {
    const note = noteText.value;
    const coords = document.getElementById('noteCoords').value;
    if(!note) {
	noteText.setCustomValidity('Please enter a note.');
        noteText.reportValidity();
        return;
    }

    const entry = {
        note,
        coords,
        timestamp: new Date().toLocaleString()
    };

    const storage = JSON.parse(localStorage.getItem('savedNotes') || '[]');
    storage.push(entry);
    localStorage.setItem('savedNotes', JSON.stringify(storage));

    document.getElementById('noteText').value = '';
    renderNotesList();
	btn.style.opacity = 1;
	document.getElementById('noteForm').style.display = 'none';
}

function renderNotesList() {
    const list = document.getElementById('notesList');
    list.innerHTML = '';
    const storage = JSON.parse(localStorage.getItem('savedNotes') || '[]');
    if (!storage.length) {
	list.innerHTML = 'None';
        return;
    }
    storage.forEach((item, index) => {
        const li = document.createElement('li');
        li.innerHTML = `<li class="note-item">
	    <div class="note-header">
        	<strong>${item.timestamp}</strong>
	        <tt>${item.coords}</tt>
	        <button class="delete-btn" onclick="deleteNote(${index})">Delete</button>
	    </div>
	    <div class="note-body">${escapeHTML(item.note)}</div>
	</li>`;
        list.appendChild(li);

                if (map && historyPoints) {
			const latlng = item.coords.trim().split(/\s*,\s*/).map(Number);
                        L.circleMarker(latlng, {radius:6, color:'red'}).addTo(historyPoints);
                }
    });
}

function deleteNote(index) {
    let storage = JSON.parse(localStorage.getItem('savedNotes') || '[]');
    storage.splice(index, 1);
    localStorage.setItem('savedNotes', JSON.stringify(storage));
    renderNotesList();
}

// Initial render
renderNotesList();

function escapeHTML(str) {
    const p = document.createElement('p');
    p.textContent = str;
    return p.innerHTML;
}


///////////////////////////////////
// Map Functions

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

            }, (err) => {
		//enableHighAccuracy Failed
		console.error(err);
		document.getElementById('noteCoords').value = getFriendlyError(err);

		if (mapMarker) //if already initialized dont need anything more
			return;

		document.getElementById('positionNote').textContent = "GPS position failed. Will need to manually center the map";

		// Helper to avoid repeating code
		const setFallbackView = (latlng, zoom = 16) => {
		        if (mapMarker) return; // Guard against race conditions
		        mapMarker = L.marker(latlng).addTo(map);
		        map.setView(latlng, zoom);
		        latestCoords = { lat: latlng[0], lng: latlng[1] };
		};

		// 1. Try low accuracy (Fast fix)
		navigator.geolocation.getCurrentPosition(
		        (pos) => setFallbackView([pos.coords.latitude, pos.coords.longitude]),
		        (err) => {
		            // 2. Low accuracy failed, try Last Note
		            const storage = JSON.parse(localStorage.getItem('savedNotes') || '[]');
		            if (storage.length > 0) {
		                const lastNote = storage[storage.length - 1];
				const latlng = lastNote.coords.trim().split(/\s*,\s*/).map(Number);
		                setFallbackView(latlng);
		            } else {
		                // 3. Absolute fallback (The "Random" point)
		                setFallbackView([57.4, -2.9], 6);
		            }
		        },
		        { enableHighAccuracy: false, timeout: 3000 }
		);

		//all least try to prevent going to far attray when manually location.
		var bounds = L.latLngBounds(L.latLng(49.863788, -13.688451), L.latLng(60.860395, 1.795260));
		map.setMaxBounds(bounds);

	    }, { enableHighAccuracy: true, timeout: 5000 });
        }

    function getFriendlyError(err) {
        switch(err.code) {
            case 1: return "Location access denied by browser.";
            case 2: return "GPS signal unavailable.";
            case 3: return "GPS timed out.";
            default: return err.message || "Location error.";
        }
    }

       function loadmap() {

            //stolen from Leaflet.base-layers.js - alas that file no compatible with mappingLeaflet.js at the moment :(

            var layerAttrib='&copy; Geograph Project';
            var layerUrl='https://t0.geograph.org.uk/tile/tile-coverage.php?z={z}&x={x}&y={y}';
            var bounds = L.latLngBounds(L.latLng(49.863788, -13.688451), L.latLng(60.860395, 1.795260));
            var coverageCoarse = new L.TileLayer(layerUrl, {user_id: 0, minZoom: 5, maxZoom: 12, attribution: layerAttrib, bounds: bounds, opacity:0.6});
            overlayMaps["Geograph Coverage"] = coverageCoarse;

            setupBaseMap(); //creates the map, but does not initialize a view

            historyPoints = new L.FeatureGroup().addTo(map);
            //map.fitBounds(bounds,{maxZoom:15});


            // Update coordinates field when map is dragged
            map.on('moveend', () => {
            //   if(document.getElementById('noteForm').style.display === 'block') {
                   updateCoordsField(map.getCenter());
            //   }
            });

        L.geotagPhoto.crosshair({
          //      crosshairHTML: '<img alt="Center of the map; crosshair location" title="Crosshair" src="https://unpkg.com/leaflet-geotag-photo@0.5.1/images/crosshair.svg" width="100px" />'

            crosshairHTML: `
                <svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="50" cy="50" r="45" fill="none" stroke="black" stroke-width="3" class="main-crosshair" stroke-opacity="0.5"/>
                    <g stroke="black" stroke-width="1" stroke-linecap="round" stroke-opacity="0.5" class="reticle-lines">
                        <line x1="50" y1="43" x2="50" y2="47" /> <line x1="50" y1="53" x2="50" y2="57" /> <line x1="43" y1="50" x2="47" y2="50" /> <line x1="53" y1="50" x2="57" y2="50" /> </g>
                </svg>`

        }).addTo(map); //.on('input', function (event) { //really jsut called when the map is recentered!

       }

	//now only loaded when tick the box!
	// window.addEventListener('DOMContentLoaded', loadmap);

//////////////////////////////////
// Save Image Functions

        let latestFile = null;
    	let fileList = document.getElementById('fileList');

    	// Pad function to ensure 05 becomes '05'
	    const pad = (n) => n.toString().padStart(2, '0');
        let datePart;
	    let timePart;

        document.getElementById('cameraInput').addEventListener('change', (e) => {
            latestFile = e.target.files[0];

       		const now = new Date();
     		datePart = `${now.getFullYear()}${pad(now.getMonth() + 1)}${pad(now.getDate())}`;
	        timePart = `${pad(now.getHours())}${pad(now.getMinutes())}${pad(now.getSeconds())}`;


            // Get location immediately on capture
            navigator.geolocation.getCurrentPosition((pos) => {
                latestCoords = { lat: pos.coords.latitude, lng: pos.coords.longitude };

		const divElement = document.getElementById('takeMessage');
		if (divElement)
			divElement.textContent = '';

                document.getElementById('downloadBtn').style.display = 'block';
                document.getElementById('downloadBtn').textContent = `Download ${timePart} Again`;
                document.getElementById('downloadBtn').click();

            }, (err) => {
		//still trigger the download on error!
		latestCoords = null; //will write an unknown file

		let errorMessage = "Unknown error";
		switch(err.code) {
		        case 1:  errorMessage = "Location access denied"; break;
		        case 2:  errorMessage = "GPS signal lost or unavailable"; break;
		        case 3:  errorMessage = "GPS request timed out"; break;
		        default: errorMessage = err.message || "Positioning error"; break;
		}

		const divElement = document.getElementById('takeMessage');
		if (divElement)
			divElement.textContent = `${errorMessage} (image should be saved with _unknown location)`;

                document.getElementById('downloadBtn').style.display = 'block';
                document.getElementById('downloadBtn').textContent = `Download ${timePart} Again`;
                document.getElementById('downloadBtn').click();

            }, { enableHighAccuracy: true, timeout: 5000 });
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
    			L.circleMarker([latestCoords.lat, latestCoords.lng], {radius:6, color:'blue'}).addTo(historyPoints);
    		}
        });

    </script>
</body>
</html>
