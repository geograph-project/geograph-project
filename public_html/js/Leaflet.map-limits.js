
//Setup a Tile-Based quota system

function setupQuota(map, baselayer) {
    if (!map || !baselayer) return;

    let mapInstance = map;
    let premiumGroup = baselayer;

    const CREDIT_LIMIT = 100; let isLocked = false;
    const SOFT_LIMIT = 75; let shownWarning = false;
    let totalCredits = 0;

    function tileEvent(e) {
        if (isLocked) return; // Stop counting if already hit

        const z = e.coords.z;
        //'EPSG:27700' zoom levels!
        totalCredits += (z >= 10 ? 3 : (z >= 6 ? 1 : 0.5));

        if (totalCredits >= CREDIT_LIMIT) {
            isLocked = true;
            shownWarning = false;
        	if (mapInstance.hasLayer(premiumGroup)) {
    	        applyMapRestrictions();
        	}
        } else if (totalCredits >= SOFT_LIMIT) {
    	    showSoftWarning("#b57704", "High OS Map usage detected, map will shortly be restricted");
        }
    }

    function showSoftWarning(color, message) {
    	if (shownWarning) return;
    	shownWarning = true;

        const mapContainer = mapInstance.getContainer();
        const msg = L.DomUtil.create('div', 'leaflet-notification', mapContainer);

        msg.style.cssText = `position:absolute; top:4px; left:50%; transform:translateX(-50%);
                             background:${color}; color:white; padding:8px 15px; border-radius:4px;
                             z-index:1000; font-family:sans-serif; font-size:1.1em; text-align:center;
                             pointer-events:none; transition: opacity 0.5s ease;`;

        msg.innerHTML = message;

    	// Only hide on actual physical interaction
        const hideMsg = () => {
            msg.style.opacity = '0';
            setTimeout(() => { if (msg.parentNode) msg.remove(); }, 500);

            // Remove the listeners so they don't keep firing
            mapInstance.off('mousedown touchstart', hideMsg);

            // Optional: Allow the warning to trigger again after 10 seconds of browsing
            setTimeout(() => { shownWarning = false; }, 10000);
        };

    	mapInstance.once('mousedown touchstart', hideMsg);
    }

    function applyMapRestrictions() {
        const currentZoom = mapInstance.getZoom();

        // Prevent zooming in further than they currently are
        mapInstance.setMaxZoom(currentZoom);

        // Optional: limit panning to exactly what they've loaded
        mapInstance.setMaxBounds(mapInstance.getBounds().pad(0.2));

        // Visual feedback so they know WHY it's restricted
        mapInstance.getContainer().classList.add('map-restricted');

        showSoftWarning('red', "Map Panning Restricted");
    }

    function removeMapRestrictions() {
        // Reset to your original defaults
        if (mapInstance._layersMaxZoom) //we may have set a custom zoom (due to multile CRS!)
        	mapInstance.setMaxZoom(mapInstance._layersMaxZoom);
        else
           	mapInstance.setMaxZoom(null);

        mapInstance.setMaxBounds(null); // Removes the panning boundary

        mapInstance.getContainer().classList.remove('map-restricted');

            // Clean up "stray" warnings
            const activeWarning = mapInstance.getContainer().querySelector('.leaflet-notification');
            if (activeWarning) activeWarning.remove();
    }

    ////////////////////////////////////////////////

	// 1. Monitor the credits
	if (premiumGroup.eachLayer) {
		premiumGroup.eachLayer(l => {
		    if (l instanceof L.TileLayer) {
        		l.on('tileload', tileEvent);
		    }
		});
	} else if (premiumGroup instanceof L.TileLayer) {
	    premiumGroup.on('tileload', tileEvent);
	}

	// 2. Handle Layer Switching
	mapInstance.on('baselayerchange', function(e) {
	    if (e.layer == premiumGroup && isLocked) {
	        applyMapRestrictions();
	    } else {
	        removeMapRestrictions();
	    }
	});

    // 3. Setup styling
    const style = document.createElement('style');
    style.type = 'text/css';
    style.innerHTML = `
        .map-restricted.leaflet-container {
            --filter: sepia(20%) contrast(90%);
        }

        .map-restricted {
        	border:2px solid red !important;
        }
        /* Change cursor to show they can't zoom in */
        .map-restricted .leaflet-interactive {
            cursor: not-allowed;
        }
    `;
    document.head.appendChild(style);
}


/* Example, but note, it should be AFTER 'map' is setup!
window.addEventListener('DOMContentLoaded', function() {
      if (typeof setupQuota === 'function') {
            setupQuota(map, baseMaps['Modern OS - GB']);
        }
}*/
