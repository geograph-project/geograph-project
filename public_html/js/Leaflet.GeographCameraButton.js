//assumes, L.easyButton, Font-Awesome, and GeoTools2 are already installed!
// if MediaDatabase is available, will save taken photos to long term storage

L.GeographCameraButton = L.Control.extend({
    // Default options
    options: {
        controlicon: 'fa-camera',
        controltitle: 'Take Photo',
        position: 'topleft', // Standard Leaflet control position
    	historyPoints: null, // The caller passes a L.FeatureGroup() here
        targetPane: 'historyDots' //what pane to historyPoints to (intended to can put them on top of polygons etc)
    },

    initialize: function(options) {
        L.setOptions(this, options);
    },

    onAdd: function(map) {
	    // we'll assume the user had already created and added the layer to map, but we will take care of loading from history for them
        if (typeof MediaDatabase !== 'undefined' && this.options.historyPoints) {
            this._loadHistoryIntoMap();
        }

        // Create a hidden file input bound to this instance
        this._fileInput = L.DomUtil.create('input', 'hidden-camera-input');
        this._fileInput.type = 'file';
        this._fileInput.accept = 'image/jpeg';
        this._fileInput.capture = 'environment';
        this._fileInput.style.display = 'none';

        // Append to the map container so it's part of the DOM
        map.getContainer().appendChild(this._fileInput);

        // Setup the EasyButton using options
        this._button = L.easyButton(
            this.options.controlicon,
            () => { this._fileInput.click(); },
            this.options.controltitle
        ).addTo(map);

        this._setupListeners();

        // Ensure the pane exists once when the control is added
        if (!map.getPane(this.options.targetPane)) {
            map.createPane(this.options.targetPane);
            map.getPane(this.options.targetPane).style.zIndex = 450;
        }

        return L.DomUtil.create('div', 'leaflet-camera-control-wrapper');
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
        this._map.on('locationfound', (e) => {
            this._lastGpsResult = e; // Stores latlng, accuracy, timestamp, etc.
        });

        this._fileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (!file) return;

            // We look for the span that has the FontAwesome 'fa' class
            const btnIcon = this._button.button.querySelector('.fa');
            const originalClass = btnIcon.className;
            btnIcon.className = 'fa fa-spinner fa-spin';

            // 1. Check if we have a "Fresh" GPS fix from the Locate control (within last 15s)
            const now = Date.now();
            if (this._lastGpsResult && (now - this._lastGpsResult.timestamp < 15000)) {
                console.log("Using 'Fresh' GPS from Map Events");
                newName = this._processDownload(file, this._lastGpsResult.latlng, false);
                this._addToHistory(newName, this._lastGpsResult.latlng, false);
                btnIcon.className = originalClass;
                return;
            }

            // 2. Fallback to API if we don't have a fresh fix
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    newName = this._processDownload(file, pos.coords, false);
                    this._addToHistory(newName, pos.coords, false);
                    btnIcon.className = originalClass;
                },
                (err) => {
                    // 3. Last Resort: Map Center
                    const mapCenter = this._map.getCenter();
                    this._notify(this._getFriendlyError(err) + " Using map center", 'orange');
                    newName = this._processDownload(file, mapCenter, true);
                    this._addToHistory(newName, mapCenter, true);
                    btnIcon.className = originalClass;
                },
                { enableHighAccuracy: true, timeout: 8000, maximumAge: 10000 }
            );
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

    _processDownload: function(file, coords, isFromMap) {
        const now = new Date();
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
