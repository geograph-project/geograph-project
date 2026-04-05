/* * Leaflet.GeographCameraButton
 * * Assumes: Font-Awesome and GeoTools2 are already installed!
 * - If MediaDatabase is available: saves taken photos to long-term local history.
 * - If submission_utils (sendToPHP & processItem) is available: provides an
 * upload button that handles EXIF extraction and client-side resizing.
 */

L.GeographCameraButton = L.Control.extend({
    options: {
        controlicon: 'fa-camera',
        controltitle: 'Take Photo',
        position: 'topleft',
        historyPoints: null,
        targetPane: 'historyDots'
    },

    initialize: function(options) {
        L.setOptions(this, options);
        this._state = {
            latestFile: null,
            latestCoords: null,
            latestName: null,
            isFromMap: false
        };
    },

    onAdd: function(map) {
        this._map = map;
        this._injectStatusCSS();

        // 1. Create main container
        const container = L.DomUtil.create('div', 'leaflet-bar leaflet-control geograph-camera-container');
 
        // Prevent map clicks from leaking through
        L.DomEvent.disableClickPropagation(container);
        L.DomEvent.on(container, 'contextmenu', L.DomEvent.stop); // Prevent right-click context menu

        // 2. The Main "Take Photo" Button
        this._mainBtn = L.DomUtil.create('a', 'leaflet-camera-main-btn', container);
        this._mainBtn.innerHTML = `<i class="fa ${this.options.controlicon}"></i>`;
        this._mainBtn.href = '#';
        this._mainBtn.title = this.options.controltitle;

        // 3. The "Action Bar" (Hidden by default)
        this._actionBar = L.DomUtil.create('div', 'camera-action-bar', container);
        this._actionBar.style.display = 'none';
        this._actionBar.style.flexDirection = 'row';

        this._uploadBtn = L.DomUtil.create('a', 'leaflet-camera-upload-btn', this._actionBar);
        this._uploadBtn.innerHTML = '<i class="fa fa-upload"></i> Upload';
        this._uploadBtn.href = '#';
        this._uploadBtn.style.background = '#4CAF50';
        this._uploadBtn.style.color = 'white';
        this._uploadBtn.style.width = 'auto';
        this._uploadBtn.style.padding = '0 10px';

        this._cancelBtn = L.DomUtil.create('a', 'leaflet-camera-cancel-btn', this._actionBar);
        this._cancelBtn.innerHTML = '<i class="fa fa-times"></i>';
        this._cancelBtn.href = '#';
        this._cancelBtn.style.background = '#f44336';
        this._cancelBtn.style.color = 'white';

        // 4. Hidden File Input
        this._fileInput = L.DomUtil.create('input', 'hidden-camera-input', container);
        this._fileInput.type = 'file';
        this._fileInput.accept = 'image/jpeg';
        this._fileInput.capture = 'environment';
        this._fileInput.style.display = 'none';

        // Listeners
        L.DomEvent.on(this._mainBtn, 'click', L.DomEvent.stop).on(this._mainBtn, 'click', () => {
            this._fileInput.click();
        });

        L.DomEvent.on(this._uploadBtn, 'click', L.DomEvent.stop).on(this._uploadBtn, 'click', this._handleUpload, this);
        L.DomEvent.on(this._cancelBtn, 'click', L.DomEvent.stop).on(this._cancelBtn, 'click', this._hideActions, this);

        this._setupListeners();

        if (!map.getPane(this.options.targetPane)) {
            map.createPane(this.options.targetPane);
            map.getPane(this.options.targetPane).style.zIndex = 450;
        }

        if (typeof MediaDatabase !== 'undefined' && this.options.historyPoints) {
            this._loadHistoryIntoMap();
        }

        return container;
    },

    _showActions: function() {
        this._actionBar.style.display = 'flex';
        this._uploadBtn.innerHTML = '<i class="fa fa-upload"></i> Upload';
    },

    _hideActions: function() {
        this._actionBar.style.display = 'none';
        this._state.latestFile = null;
    },

    _loadHistoryIntoMap: async function() {
        if (this._historyLoading) return;

        //Wait for the map to actually be initialized if it isn't yet
        if (!this._map || !this._map._loaded || !this._map.getCenter()) {
            this._map.once('viewreset moveend', () => this._loadHistoryIntoMap());
            return;
        }

        this._historyLoading = true; // Lock it (may receive BOTH moveend, and viewreset?

        const dbHistory = window.dbHistory || new MediaDatabase();
        const historyPoints = this.options.historyPoints;

        historyPoints.clearLayers();
        const geoRecords = await dbHistory.getGeoHistory();

    	const colours = {
    		'taken': 'blue',
    		'uploaded': 'red',
    		'submitted': 'green'
    	};

        geoRecords.forEach(record => {
            L.circleMarker(L.latLng(record.exifData.lat, record.exifData.long), {
                radius: 6,
                color: colours[record.status] ?? 'blue',
                pane: this.options.targetPane
            })
            .bindPopup(`<b>${record.status.toUpperCase()}</b><br>${record.filename}`)
            .addTo(historyPoints);
        });

        this._historyLoading = false;
    },

    _setupListeners: function() {
        // We'll keep track of the last known "Good GPS" fix globally within this control
        this._lastGpsResult = null;
        // Listen to the map's location event (which L.Control.Locate triggers)
        this._map.on('locationfound', (e) => { this._lastGpsResult = e; });

        this._fileInput.addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) return;

            const now = new Date(); //as close to possible when image taken (but will be when press 'OK' not when pressed shutter

            // Start spinning while we resolve location and save
            const btnIcon = this._mainBtn.querySelector('.fa');
            const originalClass = btnIcon.className;
            btnIcon.className = 'fa fa-spinner fa-spin';

            let coords, isFromMap = false;

            // 1. Check for "Fresh" Map Fix (within 15s)
            if (this._lastGpsResult && (now.getTime() - this._lastGpsResult.timestamp < 15000)) {
                coords = this._lastGpsResult.latlng;
            } else {
                // 2. Fallback: Request fresh GPS from browser
                try {
                    const pos = await new Promise((resolve, reject) => {
                        navigator.geolocation.getCurrentPosition(resolve, reject, {
                            enableHighAccuracy: true,
                            timeout: 8000,
                            maximumAge: 10000
                        });
                    });
                    coords = pos.coords;
                } catch (err) {
                    // 3. Final Resort: Map Center
                    this._notify(this._getFriendlyError(err) + " Using map center", 'orange');
                    coords = this._map.getCenter();
                    isFromMap = true;
                }
            }

            const newName = this._processDownload(now, file, coords, isFromMap);
            this._addToHistory(newName, coords, isFromMap);

            // Store state for the Upload button
            this._state = { latestFile: file, latestCoords: coords, latestName: newName, isFromMap };

            btnIcon.className = originalClass;

            if (typeof window.sendToPHP === 'function') //if have submission libary loaded, can enable upload
                this._showActions();
        });
    },

    _getFriendlyError: function(err) {
        switch(err.code) {
            case 1: return "Location access denied.";
            case 2: return "GPS signal unavailable.";
            case 3: return "GPS timed out.";
            default: return "Location error.";
        }
    },

    _notify: function(text, color) {
        const msg = L.DomUtil.create('div', '', this._map.getContainer());
        msg.style.cssText = `position:absolute; top:70px; left:50%; transform:translateX(-50%); background:${color}; color:white; padding:8px 15px; border-radius:4px; z-index:1000; font-family:sans-serif; font-size:13px; pointer-events:none; box-shadow:0 2px 5px rgba(0,0,0,0.3); transition:opacity 1s;`;
        msg.innerHTML = text;
        setTimeout(() => {
            msg.style.opacity = '0';
            setTimeout(() => msg.remove(), 1000);
        }, 3000);
    },

    //_uploadImage: function(newName, exifData) ...
	    //reminder! if we implement a upload button here, will need to save (via sendToPHP), but ALSO update/resync .historyPoints

    _addToHistory: function(newName, coords, isFromMap = false) {
        // Try .lat (Leaflet) first, fall back to .latitude (Sensor API)
        const lat = coords.lat !== undefined ? coords.lat : coords.latitude;
        const lng = coords.lng !== undefined ? coords.lng : coords.longitude;

       	// Save in long term storage (even if dont have historyPoints layer!)
	    if (typeof MediaDatabase !== 'undefined' && newName) {
	        const dbHistory = window.dbHistory || new MediaDatabase();
    	    dbHistory.savePhotoTaken(newName, lat, lng);
        }

        // Only plot if the historyPoints layer was provided in options
        if (this.options.historyPoints) {

            const timestamp = new Date().toLocaleTimeString();

            const marker = L.circleMarker([lat, lng], {
                radius: 6,
                color: 'blue',
	        	pane: this.options.targetPane
            });
            marker.bindPopup(`<b>Photo Taken</b><br>${isFromMap ? '(Map Center)' : '(GPS)'}<br>${timestamp}`);

            marker.addTo(this.options.historyPoints);
        }
    },

    _processDownload: function(now, file, coords, isFromMap) {
        const pad = (n) => n.toString().padStart(2, '0');
        const datePart = `${now.getFullYear()}${pad(now.getMonth() + 1)}${pad(now.getDate())}`;
        const timePart = `${pad(now.getHours())}${pad(now.getMinutes())}${pad(now.getSeconds())}`;

        let newName = `IMG_${datePart}_${timePart}`;

        if (isFromMap)
		    newName += "_MAP";

        if (coords) {
            // Try .lat (Leaflet) first, fall back to .latitude (Sensor API) (would also cope with GT_WGS84 object
            const lat = coords.lat !== undefined ? coords.lat : coords.latitude;
            const lng = coords.lng !== undefined ? coords.lng : coords.longitude;

      	    let locString = `_${lat.toFixed(6)}_${lng.toFixed(6)}`;
            try {
            	const wgs84 = new GT_WGS84();
                wgs84.setDegrees(lat,lng);
                let gridref = wgs84.getGridRef(isFromMap?3:5);
                if (gridref)
            		locString = gridref.replace(/ /g, '');
            } catch (e) {
    	        //pass
            }
            newName += `_${locString}`;
        } else {
            newName += `_unknown`;
        }

        newName += ".jpg";

        // Trigger download
        const link = document.createElement('a');
        link.href = URL.createObjectURL(file);
        link.download = newName;
        link.click();

        // Clean up
        URL.revokeObjectURL(link.href);
        this._fileInput.value = ''; // Reset input for next photo

        return newName; //needed to for saving to history!
    },

    _handleUpload: async function() {
        if (!this._state.latestFile) return;

        const { latestFile, latestCoords, latestName } = this._state;
        const btnIcon = this._mainBtn.querySelector('.fa');
        const originalClass = btnIcon.className;

        try {
            this._uploadBtn.textContent = "Processing...";
            btnIcon.className = 'fa fa-spinner fa-spin';

            // Provided by submission_utils
            const item = await processItem({ file: latestFile });

            const lat = latestCoords.lat ?? latestCoords.latitude;
            const lng = latestCoords.lng ?? latestCoords.longitude;

            const exifData = {
                hasGeo: Number.isFinite(lat) && Number.isFinite(lng),
                lat: lat,
                long: lng,
                date: item.exifData?.date || this._getExifDate(latestName),
                orientation: item.exifData?.orientation
            };

            const result = await sendToPHP(item.dataUri, latestName, (percent) => {
                this._uploadBtn.textContent = `${percent}%`;
            }, exifData);

            if (result && result.success) {
                this._notify("Upload Successful", "green");
                this._hideActions();
                // Re-sync history to show the 'green' (submitted) marker
                if (typeof MediaDatabase !== 'undefined' && this.options.historyPoints) this._loadHistoryIntoMap();
            } else {
                this._uploadBtn.textContent = "Retry?";
            }
        } catch (err) {
            console.error(err);
            this._uploadBtn.textContent = "Error";
        } finally {
            btnIcon.className = originalClass;
        }
    },

    _getExifDate: function(fileName) {
        // Expects "IMG_20260405_215337_..."
        // Matches the parts: 20260405 and 215337
        const parts = fileName.split('_');
        if (parts.length < 3) return ""; // Fallback or error handling

        const d = parts[1]; // "20260405"
        const t = parts[2]; // "215337"

        // Format to "YYYY:MM:DD HH:MM:SS"
        return `${d.slice(0, 4)}:${d.slice(4, 6)}:${d.slice(6, 8)} ${t.slice(0, 2)}:${t.slice(2, 4)}:${t.slice(4, 6)}`;
    },

    _injectStatusCSS: function() {
        if (document.getElementById('geograph-camera-styles')) return;
        
        const style = L.DomUtil.create('style', '', document.head);
        style.id = 'geograph-camera-styles';
        style.innerHTML = `
            .geograph-camera-container { display: flex; flex-direction: row; background: white; align-items: stretch; }
            .geograph-camera-container a {
                display: flex !important; align-items: center; justify-content: center;
                transition: all 0.2s; color: #444; text-decoration: none; gap:5px;
            }
            .leaflet-camera-main-btn { width: 30px; height: 30px; }
            .camera-action-bar { display: flex; border-left: 1px solid #ccc; overflow: hidden; }
            .leaflet-camera-upload-btn { 
                background: #4CAF50 !important; color: white !important; 
                padding: 0 12px; font-size: 11px; font-weight: bold; font-family: sans-serif;
                white-space: nowrap; border-left: 1px solid rgba(0,0,0,0.1);
            }
            .leaflet-camera-cancel-btn { 
                background: #f44336 !important; color: white !important; 
                width: 26px; border-left: 1px solid rgba(0,0,0,0.1);
            }
            .leaflet-camera-upload-btn:hover { background: #45a049 !important; }
            .leaflet-camera-cancel-btn:hover { background: #da190b !important; }
        `;
    }
});

// Factory function
L.geographCameraButton = function(options) {
    return new L.GeographCameraButton(options);
};


/*
// 1. Create the layer for photo history
const photoHistory = new L.FeatureGroup().addTo(map);

// 2. Pass it into the camera button
const cameraBtn = L.geographCameraButton({
    historyPoints: photoHistory,
    controlicon: 'fa-camera',
    controltitle: 'Record Location Photo'
}).addTo(map);

*/
