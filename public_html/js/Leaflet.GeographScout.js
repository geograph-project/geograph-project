L.GeographScout = L.LayerGroup.extend({
    options: {
        apiUrl: 'https://api.geograph.org.uk/api-scout.php',
        user_id: 0,
	    storageKey: 'geograph_scout_filters',
        opacity: 0.8
    },

    initialize: function (options) {
        L.setOptions(this, options);

        // If no bounds were provided in the options, set the default Britain/Ireland box
        if (!this.options.bounds) {
            this.options.bounds = L.latLngBounds( L.latLng(49.863788, -13.688451), L.latLng(60.860395, 1.795260) );
        }

        L.LayerGroup.prototype.initialize.call(this);

        // Internal Layers
        this._poiLayer = L.layerGroup();
        this._squareLayer = L.layerGroup();
        this.addLayer(this._poiLayer);
        this.addLayer(this._squareLayer);

        // State
        this._poiCache = [];
        this._activeMarkers = new Map();
        this._localSquareCache = {};
        this._typeLookup = {};

        this._tilecache = {};

        // Default Filters
        const defaultFilters = {
            categories: ["2", "3", "5", "9", "8", "10", "11", "15", "17"],
            unphotographed: true,
            fewPhotos: true,
            noRecent: true,
            personal: true,
            personalUpdate: true
        };

        // Try to load from localStorage
        let saved = {};
        if (this.options.storageKey) {
            try {
                const item = localStorage.getItem(this.options.storageKey);
                if (item) saved = JSON.parse(item);
            } catch (e) {
                console.error("GeographScout: Corrupt localStorage data", e);
            }
        }

        // Merge: saved values will overwrite defaultFilters keys
        this._filters = L.extend(defaultFilters, saved);
    },

    setOpacity: function(opacity) {
        // Store the value in options so Leaflet "remembers" the current state
        if (this.options) this.options.opacity = opacity;

console.log('S',opacity);

if (opacity>0.8)
	opacity = 1;

        this._squareLayer.eachLayer((layer) => {
            if (layer.setStyle) {
                layer.setStyle({
                    opacity: opacity,           // Stroke opacity
                    fillOpacity: opacity * 0.3   // Fill opacity (kept slightly more transparent)
                });
            }
        });
        return this; // Return 'this' to allow Leaflet-style chaining
    },

    onAdd: function (map) {
        L.LayerGroup.prototype.onAdd.call(this, map);
        this._map = map;
        this._initUi();
        if (Object.keys(this._typeLookup).length == 0)
	        this._fetchTypes();

        // Listen to the map's location event (which L.Control.Locate triggers)
        this._map.on('locationfound', (e) => {
            this._lastGpsResult = e; // Stores latlng, accuracy, timestamp, etc.
        });

	// Bind the moveend event so the plugin reacts to map movement
        this._map.on('moveend', this._onMapMove, this);

        // Initial trigger to load data for the current view
        this._onMapMove();
    },

    onRemove: function (map) {
        // Cleanup events when the layer is toggled off
        this._map.off('moveend', this._onMapMove, this);
        if (this._settingsControl) {
            this._map.removeControl(this._settingsControl);
        }
        L.LayerGroup.prototype.onRemove.call(this, map);
    },

    _initUi: function() {
        // Create the Settings Button
        const SettingsControl = L.Control.extend({
            options: { position: 'topright' },
            onAdd: (map) => {
                const btn = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-control-custom');
                btn.innerHTML = '<button style="font-size:18px; cursor:pointer; width:30px; height:30px; border:none; background:white;">&#x2699;</button>';
                btn.onclick = () => this._showSettings();
                return btn;
            }
        });
        this._settingsControl = new SettingsControl().addTo(this._map);

        // Inject the Dialog into the body if it doesn't exist
        if (!document.getElementById('geograph-settings-dialog')) {
            const dialog = L.DomUtil.create('dialog', '', document.body);
            dialog.id = 'geograph-settings-dialog';
            dialog.style.padding = '20px';
            dialog.style.borderRadius = '8px';
            dialog.style.border = '1px solid #ccc';
            this._dialog = dialog;
        }
    },

    _showSettings: function() {
        this._dialog.innerHTML = `
            <form method="dialog">
                <h3>Geograph Scout Settings</h3>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <strong>Features:</strong><br>
                        ${this._renderCheck('2', 'Trigpoints')}
                        ${this._renderCheck('3', 'Summits')}
                        ${this._renderCheck('5,9', 'Places')}
                        ${this._renderCheck('8', 'Lakes')}
                        ${this._renderCheck('10', 'Castles')}
                        ${this._renderCheck('11', 'Stations')}
                        ${this._renderCheck('15', 'Nature Reserves')}
                        ${this._renderCheck('17', 'Greenspaces')}
                    </div>
                    <div>
                        <strong>Grid Squares:</strong><br>
		            <label style="color:#e74c3c"><input type="checkbox" id="gs-unphoto" ${this._filters.unphotographed ? 'checked':''}> Unphotographed</label>
		            <label style="color:#7b7a7a"><input type="checkbox" id="gs-few-photo" ${this._filters.fewPhotos ? 'checked':''}> Few Photos</label>
		            <label style="color:#9b59b6"><input type="checkbox" id="gs-no-recent" ${this._filters.noRecent ? 'checked':''}> No Recent</label><br>
		            <label style="color:#3498db"><input type="checkbox" id="gs-personal" ${this._filters.personal ? 'checked':''}> Personal</label>
		            <label style="color:#f39c12"><input type="checkbox" id="gs-update" ${this._filters.personalUpdate ? 'checked':''}> Personal Redo (>5yrs)</label>
                    </div>
                </div>
                <br><button type="submit" style="width:100%">Close & Update</button>
            </form>
        `;

        this._dialog.showModal();
        this._dialog.onclose = () => {
            // Update state from UI
            this._filters.unphotographed = document.getElementById('gs-unphoto').checked;
            this._filters.fewPhotos = document.getElementById('gs-few-photo').checked;
            this._filters.noRecent = document.getElementById('gs-no-recent').checked;
            this._filters.personal = document.getElementById('gs-personal').checked;
            this._filters.personalUpdate = document.getElementById('gs-update').checked;

            // Map the category checkboxes
            const checked = Array.from(this._dialog.querySelectorAll('.filter:checked'));
            this._filters.categories = checked.flatMap(el => el.value.split(','));

            // Persist to local storage
            if (this.options.storageKey)
                localStorage.setItem(this.options.storageKey, JSON.stringify(this._filters));

            this._onFilterChange();
        };
    },

    _renderCheck: function(val, label) {
        const isChecked = val.split(',').some(v => this._filters.categories.includes(v));
        return `<label style="display:block"><input type="checkbox" class="filter" value="${val}" ${isChecked?'checked':''}> ${label}</label>`;
    },

///////////////////////////////////////////////////////

    _onMapMove: async function (e, map) {
	const activeMap = map || this._map;
	if (!activeMap) return; // Guard against calls before layer is initialized

        if (activeMap.getZoom() < 11) return;

        const centerLL = activeMap.getCenter();
        const bounds = activeMap.getBounds();

        // 1. Get Center in Grid terms
        const centerGrid = this._convertLLtoEN(centerLL.lat, centerLL.lng);
        if (!centerGrid) return; // Off-grid entirely

        // 2. Calculate spans in meters
        // Distance from center to West edge and North edge
        const westPoint = L.latLng(centerLL.lat, bounds.getWest());
        const northPoint = L.latLng(bounds.getNorth(), centerLL.lng);

        const widthMeters = centerLL.distanceTo(westPoint);
        const heightMeters = centerLL.distanceTo(northPoint);

        // 3. Define the bounding box in Eastings/Northings
        // We add a small buffer (e.g. 5km) to ensure coverage
        const buffer = 5000;
        const minE = centerGrid.eastings - widthMeters - buffer;
        const maxE = centerGrid.eastings + widthMeters + buffer;
        const minN = centerGrid.northings - heightMeters - buffer;
        const maxN = centerGrid.northings + heightMeters + buffer;

        // 4. Snap the start points to the 10km grid
        const startE = Math.floor(minE / 10000) * 10000;
        const startN = Math.floor(minN / 10000) * 10000;

        let requestCount = 0;

        for (let e = startE; e <= maxE; e += 10000) {
            if (requestCount > 16) break; // Stop columns if limit hit
            for (let n = startN; n <= maxN; n += 10000) {
                const hectad = this._convertENtoHectad(e, n, centerGrid.reference_index);

                if (hectad && !this._tilecache[hectad]) {

                    // Safety: stop if the viewport is unexpectedly huge (each fetch is actully 3!
                    if (++requestCount > 16) break;

                    this._tilecache[hectad] = 'loading';
                    this._fetchFromGeographAPIHectad(hectad)
                        .then(() => { this._tilecache[hectad] = 'loaded'; })
                        .catch(() => { delete this._tilecache[hectad]; });
                }
            }
        }
        //no refreshDisplay, as we now let leaflet handle visibiliy, features are always plotted!
    },


    //using our own libary alas, the working with multi-grid is awkward
    _convertLLtoHectad: function(lat,lng) {
    	var wgs84 = new GT_WGS84();
  	    wgs84.setDegrees(lat, lng);
     	let gridref = wgs84.getGridRef(1);
        if (gridref)
            return gridref.replace(/ /g,''); //naturaulyl reurns GRs with spaces!
        return false;
    },

    _convertLLtoEN: function(lat, lng) {
        const wgs84 = new GT_WGS84();
        wgs84.setDegrees(lat, lng);

        // Get the appropriate grid (OSGB for UK, Irish for Ireland)
        const grid = wgs84.getGrid();
        if (!grid)
            return false;

        return {
            eastings: grid.eastings,
            northings: grid.northings,
            reference_index: grid.reference_index //now available in geotools
        };
    },

    _convertENtoHectad: function(e, n, ri) {
        let grid = (ri === 1) ? new GT_OSGB() : new GT_Irish();
        grid.setGridCoordinates(e, n);
        // getGridRef(1) returns the 10km (hectad) reference
        let gridref = grid.getGridRef(1).replace(/\s+/g, '');
        //will blindly return a gridref, even if off the edge of know squares, so need to detect where get numbers wihtout the grid letters.
        return (gridref.length>2)?gridref:false;
    },

    _getBBoxForScout: function(lat, lng, radiusKm = 15) {
        // Crude but effective for 15-30km zooms:
        // 1 deg lat = 111km, 1 deg lng (at 55N) = 64km
        const latPad = radiusKm / 111;
        const lngPad = radiusKm / 64;

        const b = [
            (lng - lngPad).toFixed(4),
            (lat - latPad).toFixed(4),
            (lng + lngPad).toFixed(4),
            (lat + latPad).toFixed(4)
        ];
        return b.join(',');
    },

    _getSquarePolygon: function(sq) {
        let grid;
        //one API gives us lat/long (although may be better to jsut get from the GR anyway)
        //if (sq.lat) {
        //    // 1. Get the grid object for the SW corner
        //    const wgs84 = new GT_WGS84();
        //    wgs84.setDegrees(sq.lat, sq.lng);
        //    grid = wgs84.getGrid();
        //} else {
            if (sq.gr.length == 6)
                grid = new GT_OSGB();
            else
                grid = new GT_Irish();
            grid.parseGridRef(sq.gr);
        //}

        if (!grid || grid.status != 'OK')
           return [];

        // 2. Normalize to the bottom-left of the 1km square (e.g., 345678 -> 345000)
        const e = Math.floor(grid.eastings / 1000) * 1000;
        const n = Math.floor(grid.northings / 1000) * 1000;

        const corners = [
            [e, n],             // SW
            [e + 1000, n],      // SE
            [e + 1000, n + 1000],// NE
            [e, n + 1000]       // NW
        ];

        const latLngs = corners.map(c => {
            grid.setGridCoordinates(c[0], c[1]);
            const conv = grid.getWGS84(true);
            return (conv && conv.status === 'OK')
                ? L.latLng(conv.latitude, conv.longitude)
                : null;
        }).filter(p => p !== null);

        return latLngs;
    },

    _renderSinglePOI: function(poi) {
        if (!this._filters.categories.includes(poi.t)) return;
        if (this._activeMarkers.has(poi.id)) return;

        const poiLatLng = L.latLng(poi.lt, poi.lg);
        const title = this._typeLookup[poi.t] || "Point of Interest";

        // Bind a function instead of a static string
        const marker = L.marker(poiLatLng).bindPopup(() => {
            // This code runs ONLY when the marker is clicked
            let popupContent = `<b>${poi.n}</b><br>${title}`;

            if (this._lastGpsResult && this._lastGpsResult.latlng) {
                const distKm = this._lastGpsResult.latlng.distanceTo(poiLatLng) / 1000;
                popupContent += `<br>${distKm.toFixed(2)}km from your current position`;
            }

            return popupContent;
        });

        marker.addTo(this._poiLayer);
        this._activeMarkers.set(poi.id, marker);
    },

    _onFilterChange: function() {
        this._activeMarkers.forEach((marker, id) => {
            const poi = this._poiCache[id];
            if (!this._filters.categories.includes(poi.t)) {
                this._poiLayer.removeLayer(marker);
                this._activeMarkers.delete(id);
            }
        });

        // Check if any points in the cache should now be shown
        // (e.g. if a category was turned back ON)
        this._poiCache.forEach(poi => {
            this._renderSinglePOI(poi);
        });

        //and trigger the suqars to rerender
        this._renderSquares(Object.values(this._localSquareCache));
    },

    _renderSquares: function(squares) {

        this._squareLayer.clearLayers();

        squares.forEach(sq => {
            let color = null;
            let label = "";
            let dash = null;

            // Priority 1: Globally Unphotographed
            if (this._filters.unphotographed && sq.status.nonGeograph) {
                color = "#e74c3c";
                label = "Unphotographed Square";
            }
            // Priority 2: Globally Non-Recent
            else if (this._filters.noRecent && sq.status.nonRecent) {
                color = "#9b59b6";
                label = "Needs Recent Imagery";
            }
            else if (this._filters.fewPhotos && sq.status.fewPhotos) {
                color = "#7b7a7a";
                label = `Few Photos<br>(based on average ${sq.status.average.toFixed(1)})`;
            }
            // Priority 3: Personal Point (Never visited)
            else if (this._filters.personal && sq.status.personallyNeeded) {
                color = "#3498db";
                label = "You haven't photographed this square";
            }
            // Priority 4: Personal Update (Visited > 5yrs ago)
            else if (this._filters.personalUpdate && sq.status.needsUpdate) {
                color = "#f39c12";
                label = "You haven't visited in 5+ years";
                dash = "5, 5";
            }

            if (color) {
                // Since these are 1km OSGB squares, the bounding box
                // in WGS84 is roughly 0.009 deg High and 0.016 deg Wide
                //const bounds = [
                //    [sq.lat, sq.lng],
                //    [sq.lat + 0.009, sq.lng + 0.016]
                //];
                //L.rectangle(bounds, {

                const latLngs = this._getSquarePolygon(sq);
    		    L.polygon(latLngs, {
                    color: color,
                    weight: 1,
                    opacity: this.options.opacity,
                    fillOpacity: 0.3*this.options.opacity,
                    dashArray: dash,
                    interactive: true
                }).addTo(this._squareLayer).bindPopup(`Square: <a href="/gridref/${sq.gr}" target="_blank">${sq.gr}</a><br>${label}<br>Images: ${sq.c}`);
            }
        });
    },

    _processSquares: function(allSquaresRaw, userSquaresRaw) {
	    // 1. Normalization Helper
	    // Converts {"TQ3039": {c:1}} into [{gr: "TQ3039", c:1}]
	    const normalize = (data) => {
	        if (!data) return [];
	        if (Array.isArray(data)) return data;
	        return Object.entries(data).map(([gr, obj]) => {
	            return { ...obj, gr: gr }; // Inject the key as the 'gr' property
	        });
	    };

	    const allSquares = normalize(allSquaresRaw);
	    const userSquares = normalize(userSquaresRaw);

        // Map of user squares for easy attribute lookup (like 'last photographed')
        const userSquareMap = new Map(userSquares.map(s => [s.gr, s]));

	    // 1. Extract only the numbers that are greater than 0
   	    const validValues = allSquares.map(sq => sq.c).filter(val => val !== null && val > 0);

    	// 2. Perform calculations only if we have data
	    const total = validValues.reduce((sum, val) => sum + val, 0);
    	const avg = validValues.length > 0 ? total / validValues.length : 0;
	    const criteria = Math.min(Math.max(4, avg *0.2), 25);

        allSquares.forEach(sq => {
            //the hectad API, also gives us all at sea squares, which dont want to bother with
            if (!(sq.l || sq.c)) //not if no land and no imags
                return;

            const userData = userSquareMap.get(sq.gr);
            const hasVisited = !!userData; // Truthy if the ID exists in the map

            // LOGIC A: Globally unphotographed (no geographs)
            const isNonGeograph = !sq.g;

	    //geograph, but few photos
            const isFewPhotos = sq.g && sq.c < criteria;

            //geographed, but not recently
            const isNonRecent = sq.g && !sq.r;

            // LOGIC B: Not photographed by ME
            const isPersonallyNeeded = !hasVisited;

            // LOGIC C: Photographed by me, but not RECENTLY (e.g., > 5 years)
            // Check if the user's specific record for this square says has_recent (r) is 0
            const isNeedsUpdate = hasVisited && userData.r === 0;

            // Decide if we should track/show this square
            if (isNonGeograph || isFewPhotos || isNonRecent || isPersonallyNeeded || isNeedsUpdate) {
                // Add metadata so the renderer knows WHY it's showing
                sq.status = {
                    nonGeograph: isNonGeograph,
                    fewPhotos: isFewPhotos,
                    nonRecent: isNonRecent,
                    personallyNeeded: isPersonallyNeeded,
                    needsUpdate: isNeedsUpdate,
		    average: avg
                };
                this._localSquareCache[sq.gr] = sq;
            }
        });

        this._renderSquares(Object.values(this._localSquareCache));
        //this is mainly if GeographCoverage is also in use
        this.fire('dataupdated');
    },

    _fetchFromGeographAPIHectad: async function(hectad) {
        // 1. Construct URLs
        const scoutUrl = `${this.options.apiUrl}?hectad=${hectad}`;
        const allSquaresUrl = `https://t0.geograph.org.uk/tile-hectad.json.php?hectad=${hectad}`;
        const userSquaresUrl = `https://t0.geograph.org.uk/tile-hectad.json.php?hectad=${hectad}&user_id=${this.options.user_id}`;

        try {
            // 2. Run all three fetches in parallel
            const [poiRes, squareRes, userRes] = await Promise.allSettled([
                fetch(scoutUrl).then(r => r.json()),
                fetch(allSquaresUrl).then(r => r.json()),
                fetch(userSquaresUrl).then(r => r.json())
            ]);

            // 3. Process POI (Scout) Data
            if (poiRes.status === 'fulfilled' && Array.isArray(poiRes.value)) {
                poiRes.value.forEach(item => {
                    // Ensure we don't overwrite if it already exists in cache
                    if (!this._poiCache[item.id]) {
                        this._poiCache[item.id] = item;
                        this._renderSinglePOI(item);
                    }
                });
            }

            // 4. Process Grid Squares
            // Note: tile-hectad returns { markers: [...] } directly
            if (squareRes.status === 'fulfilled' && userRes.status === 'fulfilled') {
                const allMarkers = squareRes.value.squares || [];
                const userMarkers = userRes.value.squares || [];

                // Your existing processing logic
                this._processSquares(allMarkers, userMarkers);
            }

            // 5. Update Status UI
            this._tilecache[hectad] = 'loaded'; // Mark as successfully cached

            const cacheCount = Object.keys(this._poiCache).length;
            const statusEl = document.getElementById('status');
            if (statusEl) {
                statusEl.innerText = `Loaded Hectad ${hectad}. Total POIs: ${cacheCount}`;
            }

        } catch (err) {
            console.error(`Failed to fetch data for hectad ${hectad}:`, err);
            // Reset so it can be attempted again on next move
            delete this._tilecache[hectad];
        }
    },

    _fetchTypes: async function() {
        try {
            const response = await fetch('/api-scout.php?types=1');
            const data = await response.json();

            // 2. Convert the array into a keyed object for O(1) lookup
            // Assuming API returns: [{ "id": 1, "title": "UK Islands" }, ...]
            data.forEach(item => {
                this._typeLookup[item.id] = item.title;
            });

            console.log("Type Lookup Loaded:", this._typeLookup);
        } catch (err) {
           console.error("Failed to fetch Geograph types:", err);
        }
    },

});

// Factory method
L.geographScout = function (options) {
    return new L.GeographScout(options);
};
