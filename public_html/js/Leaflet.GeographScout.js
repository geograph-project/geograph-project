L.GeographScout = L.LayerGroup.extend({
    options: {
        apiUrl: 'https://api.geograph.org.uk/api-scout.php',
        user_id: 0,
        refreshDistance: 10, // km
        poiRadius: 10 // km
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
        this._localSquareCache = {};
        this._lastFetchLocation = null;
        this._typeLookup = {};

        // Default Filters
        this._filters = {
            categories: ["2", "3", "5", "9", "8", "10", "11", "15", "17"],
            unphotographed: true,
            noRecent: true,
            personal: true,
            personalUpdate: true
        };
    },

    onAdd: function (map) {
        L.LayerGroup.prototype.onAdd.call(this, map);
        this._map = map;
        this._initUi();
        if (Object.keys(this._typeLookup).length == 0)
	        this._fetchTypes();

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
		            <label style="color:#9b59b6"><input type="checkbox" id="gs-no-recent" ${this._filters.noRecent ? 'checked':''}> No Recent (Global)</label><br>
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
            this._filters.noRecent = document.getElementById('gs-no-recent').checked;
            this._filters.personal = document.getElementById('gs-personal').checked;
            this._filters.personalUpdate = document.getElementById('gs-update').checked;

            // Map the category checkboxes
            const checked = Array.from(this._dialog.querySelectorAll('.filter:checked'));
            this._filters.categories = checked.flatMap(el => el.value.split(','));

            this.refreshDisplay();
        };
    },

    _renderCheck: function(val, label) {
        const isChecked = val.split(',').some(v => this._filters.categories.includes(v));
        return `<label style="display:block"><input type="checkbox" class="filter" value="${val}" ${isChecked?'checked':''}> ${label}</label>`;
    },

///////////////////////////////////////////////////////

    _onMapMove: async function () {
        const center = this._map.getCenter();

        // Logic: Should we fetch new data?
        let needsFetch = false;
        if (!this._lastFetchLocation) {
            needsFetch = true;
console.log('First Fetch', center);
        } else {
            const distFromLastFetch = center.distanceTo(this._lastFetchLocation) / 1000;
            // Fetch if moved > 10km or if we have no data
            if (distFromLastFetch > this.options.refreshDistance) needsFetch = true;
console.log('Move', center, this._lastFetchLocation, 'km:', distFromLastFetch, '>', this.options.refreshDistance, needsFetch);
        }

        if (needsFetch) {
            await this._fetchFromGeographAPI(center.lat, center.lng);
        }

        // Always refresh the markers/squares on move to ensure they
        // are visible in the current viewport even if we didn't fetch
        this.refreshDisplay();
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
        // 1. Get the grid object for the SW corner
        const wgs84 = new GT_WGS84();
        wgs84.setDegrees(sq.lat, sq.lng);
        const grid = wgs84.getGrid();

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

    _renderNearbyPoints: function(userPt) {
        this._poiLayer.clearLayers();
        const center = this._map.getCenter();

        this._poiCache.forEach(poi => {
            const poiLatLng = L.latLng(poi.lt, poi.lg);
            const distKm = center.distanceTo(poiLatLng) / 1000;

//todo could just use map bounds instead!

            // Only show markers within 10km of current location
            if (distKm <= 10 && this._filters.categories.includes(poi.t)) {
                var title = this._typeLookup[poi.t];
                L.marker(poiLatLng)
                    .bindPopup(`<b>${poi.n}</b><br>${title}<br>${distKm.toFixed(2)}km away`)
        		    .addTo(this._poiLayer);
            }
        });
    },

    _renderSquares: function(squares) {

        this._squareLayer.clearLayers();

        squares.forEach(sq => {
            let color = null;
            let label = "";
            let dash = null;

    //TODO, could also check is within bounds of map!

            // Priority 1: Globally Unphotographed
            if (this._filters.unphotographed && sq.status.nonGeograph) {
                color = "#e74c3c";
                label = "Unphotographed Square";
            }
            // Priority 2: Globally Non-Recent
            else if (this._filters.noRecent && sq.status.nonRecent) {
                color = "#9b59b6";
                label = "Needs Recent Imagery (Global)";
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
                    fillOpacity: 0.2,
                    dashArray: dash,
                    interactive: true
                }).addTo(this._squareLayer).bindPopup(`Square: ${sq.gr}<br>${label}<br>Images: ${sq.c}`);
            }
        });
    },

    _processSquares: function(allSquares, userSquares) {
        // Map of user squares for easy attribute lookup (like 'last photographed')
        const userSquareMap = new Map(userSquares.map(s => [s.gr, s]));

        allSquares.forEach(sq => {
            const userData = userSquareMap.get(sq.gr);
            const hasVisited = !!userData; // Truthy if the ID exists in the map

            // LOGIC A: Globally unphotographed (no geographs)
            const isNonGeograph = !sq.g;

            //geographed, but not recently
            const isNonRecent = sq.g && !sq.r;

            // LOGIC B: Not photographed by ME
            const isPersonallyNeeded = !hasVisited;

            // LOGIC C: Photographed by me, but not RECENTLY (e.g., > 5 years)
            // Check if the user's specific record for this square says has_recent (r) is 0
            const isNeedsUpdate = hasVisited && userData.r === 0;

            // Decide if we should track/show this square
            if (isNonGeograph || isNonRecent || isPersonallyNeeded || isNeedsUpdate) {
                // Add metadata so the renderer knows WHY it's showing
                sq.status = {
                    nonGeograph: isNonGeograph,
                    nonRecent: isNonRecent,
                    personallyNeeded: isPersonallyNeeded,
                    needsUpdate: isNeedsUpdate
                };
                this._localSquareCache[sq.gr] = sq;
            }
        });

        this._renderSquares(Object.values(this._localSquareCache));
    },

    _fetchFromGeographAPI: async function(lat, lng) {
        const currentPos = L.latLng(lat, lng);

        // Check against the bounds stored in the options
        if (this.options.bounds && !this.options.bounds.contains(currentPos)) {
            console.warn("Request skipped: Coordinates are outside Britain and Ireland.");
            this._lastFetchLocation = currentPos; //still set the fetch position to avoid lots of fake fetches
            return; // Exit early to prevent API calls
        }

        // Round for privacy and better server-side caching
        const fuzzyLat = lat.toFixed(2);
        const fuzzyLng = lng.toFixed(2);

        const bbox = this._getBBoxForScout(lat, lng, 5); //will only do spall areas!

        // Run both fetches in parallel
        const [poiRes, squareRes, userRes] = await Promise.allSettled([
            fetch(`${this.options.apiUrl}?lat=${fuzzyLat}&lng=${fuzzyLng}&radius=30`).then(r => r.json()),
            fetch(`https://api.geograph.org.uk/stuff/squares.json.php?olbounds=${bbox}`).then(r => r.json()),
            fetch(`https://api.geograph.org.uk/stuff/squares.json.php?olbounds=${bbox}&user_id=${this.options.user_id}`).then(r => r.json())
        ]);

        if (poiRes.status === 'fulfilled') {
            poiRes.value.forEach(item => {
                if (!this._poiCache[item.id]) {
                    this._poiCache[item.id] = item;
                }
            });
        }

        if (squareRes.status === 'fulfilled' && userRes.status === 'fulfilled') {
            const allMarkers = squareRes.value.markers || [];
            const userMarkers = userRes.value.markers || [];
            this._processSquares(allMarkers, userMarkers);
        }

        this._lastFetchLocation = L.latLng([lat, lng]);
        const keys = Object.keys(this._poiCache);
        if (document.getElementById('status'))
	        document.getElementById('status').innerText = `Cache updated. Total points in DB: ${keys.length}`;
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

///////////////////////////////////////////////////////

    refreshDisplay: function() {
        if (!this._lastFetchLocation) return;
        this._renderSquares(Object.values(this._localSquareCache));
        this._renderNearbyPoints(this._lastFetchLocation);
    }
});

// Factory method
L.geographScout = function (options) {
    return new L.GeographScout(options);
};
