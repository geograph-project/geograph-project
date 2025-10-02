       /**
         * Custom Leaflet Control for the Expand Button.
         * Extends L.Control to add a button to the map interface.
         * The styling is now entirely in the onAdd function for a self-contained JS block.
         */
        L.Control.ExpandButton = L.Control.extend({
            options: {
                position: 'topright', // Position the control in the top-left corner
                // This targetUrl is only used for the map-only view
                targetUrl: 'https://www.geograph.org.uk/mapper/combined.php#',
            },

            onAdd: function (map) {
                // Create the control container div using Leaflet's base classes only
                const container = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
                
                // --- Inline CSS applied via JavaScript properties ---
                
                // Apply main button styles
                container.style.backgroundColor = 'white';
                container.style.padding = '8px';
                container.style.cursor = 'pointer';
                container.style.borderRadius = '0.5rem';
                container.style.boxShadow = '0 1px 3px rgba(0,0,0,0.4)';
                container.style.display = 'flex';
                container.style.alignItems = 'center';
                container.style.justifyContent = 'center';
                container.style.border = '2px solid #3b82f6'; // Blue border
                container.style.transition = 'background-color 0.2s';
                
                // Create the button content (an SVG for the expand icon) with inline styling
                container.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: #3b82f6;">
                        <path d="M5 5h5V3H3v7h2zm5 14H5v-5H3v7h7zm11-5h-2v5h-5v2h7zM14 5h5v5h2V3h-7z"/>
                    </svg>
                `;

                // Simulate hover effect using mouseover/mouseout events
                L.DomEvent.on(container, 'mouseover', () => {
                    container.style.backgroundColor = '#f3f4f6'; // Light gray on hover
                });
                L.DomEvent.on(container, 'mouseout', () => {
                    container.style.backgroundColor = 'white'; // Reset to white
                });


                // Prevent map clicks from propagating to the button container
                L.DomEvent.disableClickPropagation(container);
                L.DomEvent.on(container, 'contextmenu', L.DomEvent.stop); // Prevent right-click context menu

                // Attach the click handler
                L.DomEvent.on(container, 'click', (e) => {
                    this._expandMap(map);
                    L.DomEvent.stop(e); // Stop event propagation
                });

                return container;
            },

            /**
             * Handles the logic for getting map view and opening the new URL.
             * It now checks for a query string in the search input to determine the URL format.
             * @param {L.Map} map The Leaflet map instance.
             */
            _expandMap: function (map) {
                // 1. Get current map parameters
                const center = map.getCenter();
                const zoom = map.getZoom();
                
                // 2. Format Latitude and Longitude to 6 decimal places
                const lat = center.lat.toFixed(6);
                const lng = center.lng.toFixed(6);

                // 3. Get the search query input element and value
                const searchInput = document.querySelector('input[name="q"]');
                const queryValue = searchInput ? searchInput.value.trim() : '';
                
                let url;
                
                if (queryValue) {
                    // Alternate URL if a query is present (Location-based search)
                    const encodedQuery = encodeURIComponent(queryValue);
                    // Format: https://www.geograph.org.uk/browser/#!/q={query}/loc={lat}/{lng}/dist=2000
                    url = `https://www.geograph.org.uk/browser/#!/q=${encodedQuery}/loc=${lat}%2C${lng}/dist=2000/display=map_dots`;
                    console.log('Search query detected. Generating search URL.');
                } else {
                    // Original URL if no query is present (Map view)
                    // Format: https://www.geograph.org.uk/mapper/combined.php#{zoom}/{lat}/{lng}
                    url = `${this.options.targetUrl}${zoom}/${lat}/${lng}`;
                    console.log('No search query. Generating map URL.');
                }

                // 4. Open in a new window/tab
                window.open(url, '_blank');

                console.log(`Map expanded. URL generated: ${url}`);
            }
        });

        // Helper function to instantiate the control
        L.control.expandButton = function(options) {
            return new L.Control.ExpandButton(options);
        };

