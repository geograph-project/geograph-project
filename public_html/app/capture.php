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
            --content-text: #000000;
            --card-faded: #666666; /* Slightly softer than #333 for better hierarchy */

            --accent: #6c757d;
            --input-bg: #ffffff;
            --input-placeholder: #999999;


            /* Light Mode Secondary */
            --secondary-bg: #e9ecef;
            --secondary-text: #333333;
            --secondary-hover: #dee2e6; /* Slightly darker for interaction */
	    --danger-bg: #ffc0cb;       /* Your 'pink' */
	    --danger-text: #333333;

        }
        body.dark-mode {
                --bg: #121212;
            --content-text: #e0e0e0;
            --card-faded: #a0a0a0;     /* Light grey to stand out on dark cards */

            /* New: Dark Mode Inputs */
            --input-bg: #2c2c2c;       /* Slightly lighter than card-bg to "lift" the input */
            --input-placeholder: #757575;

            /* Dark Mode Secondary */
            --secondary-bg: #333333;    /* Dark grey to sit quietly on the card */
            --secondary-text: #e0e0e0;  /* Off-white text */
            --secondary-hover: #444444; /* Slightly lighter for interaction */
	    --danger-bg: #442727;       /* Deep wine/maroon background */
	    --danger-text: #ff8a8a;     /* Soft red/pink text */
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--bg);
            color: var(--content-text);
            margin: 0; padding: 20px;
        }

        @media screen and (max-width: 500px) {
                body {
                        padding:20px 2px;
                }
        }

        /* Custom Buttons */
        .btn { padding: 14px 28px; border-radius: 12px; border: none; cursor: pointer; touch-action: manipulation; user-select: none; font-weight: 600; transition: all 0.2s; display: inline-block; margin: 8px 0; font-size: 16px; }
        .btn-primary { background: var(--primary); color: white; width: 100%; box-sizing: border-box; text-align:center }
        .btn-upload { background: var(--primary); color: white; width: 100%; }
        .btn-upload:disabled { background: #ccc; cursor: not-allowed; }
        .btn-secondary { background: var(--secondary-bg); color: var(--secondary-text); width: 100%; }
	.btn-help { background-color:#8ddf8d; }

	#map {
	 width:350px; height:350px; max-height:90vh; max-width:90vw;
 	margin:auto;
	}

	#noteForm {
		padding: 10px; border-radius: 8px; text-align: left;
	}
	#noteForm textarea {
		width: 100%; height: 60px; font-family:Georgia; font-size:1.1em; border-radius:8px; padding:6px;  box-sizing: border-box;
		background-color: var(--input-bg);
		color: var(--content-text);
	}
	#noteForm p { color: #666; text-align:center; }
	#noteForm input[type=text] {
		max-width:100%;
		width: 200px; margin: 8px 0; padding:8px; border: none; background: white; text-align:center;
		background-color: var(--input-bg);
		color: var(--content-text);
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
    line-height: 1.4;
}

.delete-btn {
    color: var(--danger-text);
    background: var(--danger-bg);
    border: 1px solid #f8d7da;
    border-radius: 4px;
    padding: 4px 8px;
    cursor: pointer;
    user-select: none;
}

.delete-btn:hover {
    background: #f8d7da;
}



        dialog::backdrop {
            background: rgba(0, 0, 0, 0.5);
                backdrop-filter: blur(3px);
        }

        dialog {
            /* Ensures it doesn't look like a standard browser alert */

            max-height: 85vh; /* Give a bit more vertical breathing room */
            max-width: 90vw;  /* Prevents it from hitting the screen edges on mobile */
            width: 500px;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #ccc;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            background-color: var(--input-bg);
                    color: var(--content-text);
        }
	dialog ul {
		padding-inline-start: 10px;
	}
        dialog button {
            display:block;
            width:100%;
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

    <script src="https://cdn.jsdelivr.net/npm/exifr@7.1.3/dist/lite.umd.js"></script>
    <script src="<?php echo smarty_modifier_revision("/js/submission_utils.js"); ?>"></script>
    <script src="<?php echo smarty_modifier_revision("/viewer/ExifRestorer.js"); ?>"></script>

    <script>
        window.max_size = 8 * 1024 * 1024; //larger files will be downsized!
        window.uploadMaxDimension = 65536; // Default to effectively unlimited (will be updated by the settings listener!)
    </script>

    <script type="module">
        import { escapeHTML, navigateTo, setupSettingsListener, openModal, closeModal } from '/app/js/utils.js?<? echo filemtime('js/utils.js'); ?>';
	window.escapeHTML = escapeHTML;
        window.navigateTo = navigateTo;
        window.openModal = openModal;
        window.closeModal = closeModal;
        setupSettingsListener();
    </script>
</head>

<body>
	<dialog id="why-modal" onclick="closeModal('why-modal')">
	    <h3>Take Photo</h3>
	    <ul>
	        <li>You don't have to use this page to take photos. You can use your standard camera app if preferred.
	        <li><b>Why use this page?</b> Mobile browsers often strip location data from uploads. Photos taken here bypass this by saving the location directly in the filename.
	        <li>Photos are saved to your "Downloads" folder. You must still upload them manually later.
	        <li>Select 'OK' after snapping a photo to save it, or choose to retake.
	    </ul>

	    <h3>Location Note</h3>
	    <ul>
	        <li>Use "Create Location Note" to save your current coordinates as a waypoint for later.
	        <li>Notes are stored locally on this device only.
		<li>Can be referenced during your final image submission.
	    </ul>

	    <h3>Live Map</h3>
	    <ul>
	        <li>Enable the Live Map to see your real-time position, or to refine a Location Note before saving.
	        <li>Use this to verify your GPS has a "lock" after waking your device, ensuring accurate data before you take a photo.
	    </ul>

	    <button type="button" class="btn" onclick="closeModal('why-modal')">Close</button>
	</dialog>

    <button id="missing-btn" onclick="openModal('why-modal')" style="float:right" class="btn btn-help hidden" type="button">Why this page?</button>

    <p>Use this button to take a photo with your camera. Location data will be included in the filename, which will be saved to your
    Downloads folder. If you don't receive a download notification, you can try the download again.

	<label for="cameraInput" class="btn btn-primary" id="select-label">Take Photo</label>
    <input type="file" id="cameraInput" accept="image/jpeg" capture="environment" hidden>

	<div id="takeMessage"></div>
    <button id="downloadBtn" class="btn btn-secondary" style="display:none;">Retry Download</button>
    <button id="uploadBtn" class="btn btn-secondary" style="display:none;">Upload Last Photo now</button>
    <button id="submitBtn" class="btn btn-primary" style="display:none;">Submit Last Photo now</button>

    <p>Or seperately, use this button to save a note tagged with your location which can be used during submission (accessible on this device only). Notes are listed at the bottom of the page.
    <button id="saveBtn" class="btn btn-primary">Create Location Note</button>

    <div id="noteForm" style="display:none;">
        <textarea id="noteText" wrap="soft" maxlength="255" placeholder="Enter note..."></textarea>
        <p>Drag the map to refine position of the cross-hairs...<br><input type="text" id="noteCoords" readonly></p>
	<div id="positionNote"></div>
        <button class="btn btn-primary" onclick="saveNoteToLocalStorage()">Save Note</button>
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

//need to defer, so happens after the module is loaded
window.addEventListener('DOMContentLoaded', function() {
	// Initial render
	renderNotesList();
});

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
    	const fileList = document.getElementById('fileList');
        const selectLabel = document.getElementById('select-label');
    	const takeMessage = document.getElementById('takeMessage');
        const downloadBtn = document.getElementById('downloadBtn');
        const uploadBtn = document.getElementById('uploadBtn');
        const submitBtn = document.getElementById('submitBtn');

    	// Pad function to ensure 05 becomes '05'
	    const pad = (n) => n.toString().padStart(2, '0');
        let datePart;
	    let timePart;

        document.getElementById('cameraInput').addEventListener('change', (e) => {
            if (!e.target.files || e.target.files.length === 0) {
                // User cancelled the camera or no file selected
                return;
            }
            latestFile = e.target.files[0];

            //making sure to save at time of capture (or as as close as possible!) (its technically when they click OK)
       		const now = new Date();
     		datePart = `${now.getFullYear()}${pad(now.getMonth() + 1)}${pad(now.getDate())}`;
	        timePart = `${pad(now.getHours())}${pad(now.getMinutes())}${pad(now.getSeconds())}`;

            // Get location immediately on capture
            navigator.geolocation.getCurrentPosition((pos) => {
                latestCoords = { lat: pos.coords.latitude, lng: pos.coords.longitude };

                selectLabel.textContent = "Take Another Photo";
       			takeMessage.textContent = '';

                downloadBtn.style.display = 'block';
                downloadBtn.textContent = `Download ${timePart} Again`;
                downloadBtn.click();

                uploadBtn.style.display = 'block';
		uploadBtn.textContent = `Upload ${timePart} Now (${window.uploadMaxDimension < 65535 ? `@${window.uploadMaxDimension}px` : 'Full size'})`;

                uploadBtn.disabled = false; //incase it was previuslly disabled

                submitBtn.style.display = 'none';

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

		        takeMessage.textContent = `${errorMessage} (image should be saved with _unknown location)`;

                //still download!
                downloadBtn.style.display = 'block';
                downloadBtn.textContent = `Download ${timePart} Again`;
                downloadBtn.click();

                //but lets not upload/submit
                uploadBtn.style.display = 'none';
                submitBtn.style.display = 'none';

            }, { enableHighAccuracy: true, timeout: 5000 });
        });

        function getLatestFilename() {
            let newName;
    		if (!latestCoords || !latestCoords.lat) {
    			 newName = `IMG_${datePart}_${timePart}_unknown.jpg`;
    		} else {
    			var wgs84 = new GT_WGS84();
    			wgs84.setDegrees(latestCoords.lat, latestCoords.lng);
    			let gridref = wgs84.getGridRef(5);

    			if (gridref) {
    				//this is our proper format, date/time for easy sorting, plus GR
    			    newName = `IMG_${datePart}_${timePart}_${gridref.replace(/ /g,'')}.jpg`;
    			} else {
    				//fallback (outside GB/Ire :)
    			    newName = `IMG_${latestCoords.lat.toFixed(6)}_${latestCoords.lng.toFixed(6)}.jpg`;
    			}
    		}
            return newName;
        }

        // Converts YYYYMMDD and HHMMSS to "YYYY:MM:DD HH:MM:SS"
        const getExifFormattedDate = () => {
            const d = datePart; // e.g., "20241006"
            const t = timePart; // e.g., "134525"

            // Slice and dice the strings into the EXIF format
            return `${d.slice(0, 4)}:${d.slice(4, 6)}:${d.slice(6, 8)} ${t.slice(0, 2)}:${t.slice(2, 4)}:${t.slice(4, 6)}`;
        };

        document.getElementById('downloadBtn').addEventListener('click', () => {
            if (!latestFile) return;

            let newName = getLatestFilename();

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

        document.getElementById('uploadBtn').addEventListener('click', async () => {
            if (!latestFile) return; //was latestFile = e.target.files[0];

            uploadBtn.textContent = "Processing & Uploading...";
            uploadBtn.disabled = true;

            const newName = getLatestFilename();
            const fallbackExifDate = getExifFormattedDate();

            //convert to 'file' to dateUri, BUT, use our resize handler!

            //does resize - if needed, as well as fetching Exif data!
            //we COULD put the newly generated filename into file.name, and processItem would read it, but better to just use the saved lat/long directly
            const item = await processItem({ file: latestFile });

            const result = await sendToPHP(item.dataUri, newName, (percent) => {
                // Update the button text to show progress
                uploadBtn.textContent = `Uploading... ${percent}%`;
            });
            if (result && result.success) {
                // SUCCESS: Allow direct submission

                document.getElementById('submitBtn').onclick = function() {
                    navigateTo('/app/submit',{message: JSON.stringify({
                        transfer_id: result.upload_id,
                        width: result.width,
                        height: result.height,
                        lat: latestCoords.lat, //use saved coordinates, as dont trust exif!
                        long: latestCoords.lng,
                        // Priority: 1. EXIF date from file, 2. Formatted capture time
                        imagetaken: item.exifData?.date || fallbackExifDate,
                        orientation: item.exifData?.orientation
                    })});
                }
                uploadBtn.style.display = 'none';
                submitBtn.style.display = 'block';
                submitBtn.textContent = `Submit ${timePart} Now`;
            } else {
                uploadBtn.textContent = "Upload Failed. Try again";
            }
        });

    </script>

</body>
</html>
