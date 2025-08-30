<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Geospatial Operations</title>
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <!-- Leaflet.draw CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css" />
    <!-- Tailwind CSS CDN for styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: #f7fafc;
        }
        #map {
            flex-grow: 1;
            height: 60vh;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-top: 1rem;
        }
        .container {
            max-width: 1024px;
            margin: auto;
            padding: 2rem;
        }
        .file-input-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .input-label {
            cursor: pointer;
            padding: 0.75rem 1.5rem;
            border: 2px dashed #cbd5e0;
            border-radius: 0.5rem;
            color: #4a5568;
            transition: all 0.2s ease-in-out;
        }
        .input-label:hover {
            border-color: #a0aec0;
            color: #2d3748;
        }
        .message-box {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            text-align: center;
            border: 2px solid #ef4444; /* red-500 */
        }
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">

    <div class="container bg-white rounded-lg shadow-xl p-8 my-8">
        <header class="text-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Geospatial Operations</h1>
            <p class="text-gray-600">Upload a KML, GML, GeoJSON, or GPX file, or draw directly on the map to create new, simplified polygons.</p>
        </header>

        <main>
            <div class="file-input-container">
                <label for="fileInput" class="input-label">
                    <span id="file-label">Choose a file...</span>
                    <input type="file" id="fileInput" class="hidden" accept=".kml,.gml,.geojson,.json,.kmz,.gpx" />
                </label>
            </div>
            
            <div id="loading-spinner" class="mt-4 hidden text-center">
                <svg class="animate-spin h-8 w-8 text-blue-500 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="mt-2 text-gray-500">Processing file...</p>
            </div>
            
            <div id="map" class="mt-8"></div>
        </main>

        <footer class="text-center text-gray-500 mt-8 text-sm">
            <p>&copy; 2024 Geospatial App. All rights reserved.</p>
        </footer>
    </div>

    <!-- Custom message box -->
    <div id="messageBox" class="hidden">
        <div class="overlay"></div>
        <div class="message-box">
            <p id="messageText" class="text-gray-700"></p>
            <button id="closeMessage" class="mt-4 px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-50">Close</button>
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- Leaflet.draw JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
    <!-- Turf.js for geospatial operations -->
    <script src="https://cdn.jsdelivr.net/npm/@turf/turf@6/turf.min.js"></script>
    <!-- JSZip for KMZ files -->
    <script src="https://unpkg.com/jszip@3.10.1/dist/jszip.min.js"></script>
    <!-- ToGeoJSON for KML and GPX parsing -->
    <script src="https://unpkg.com/@mapbox/togeojson@0.16.0/togeojson.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Set the map's initial view to the UK
            const map = L.map('map').setView([54.0, -2.0], 6);

            // Add a base map tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            const fileInput = document.getElementById('fileInput');
            const fileLabel = document.getElementById('file-label');
            const loadingSpinner = document.getElementById('loading-spinner');
            const messageBox = document.getElementById('messageBox');
            const messageText = document.getElementById('messageText');
            const closeMessageButton = document.getElementById('closeMessage');
            
            // Feature group to store drawn shapes
            const drawnItems = new L.FeatureGroup();
            map.addLayer(drawnItems);

            // Keep track of the currently selected layer
            let selectedLayer = null;
            
            // Initialize Leaflet.draw control
            const drawControl = new L.Control.Draw({
                edit: {
                    featureGroup: drawnItems
                },
                draw: {
                    polygon: true,
                    polyline: true,
                    rectangle: false,
                    circle: false,
                    circlemarker: false,
                    marker: false,
                }
            });
            map.addControl(drawControl);

            // Listen for a click on any layer in the drawnItems group
            drawnItems.on('click', async (e) => {
                const feature = e.layer.toGeoJSON();
                await processAndDisplayFeature(feature);
            });
            
            // Listen for the 'draw:created' event
            map.on(L.Draw.Event.CREATED, function (event) {
                const layer = event.layer;
                drawnItems.addLayer(layer);
            });

            function showMessage(text) {
                messageText.textContent = text;
                messageBox.classList.remove('hidden');
            }

            function hideMessage() {
                messageBox.classList.add('hidden');
            }
            
            closeMessageButton.addEventListener('click', hideMessage);
            
            async function processAndDisplayFeature(feature) {
                loadingSpinner.classList.remove('hidden');
                await new Promise(resolve => setTimeout(resolve, 10));
                
                let newFeature = null;
                const geomType = feature.geometry.type;

                if (geomType === 'Polygon' || geomType === 'MultiPolygon') {
                    newFeature = processPolygon(feature);
                } else if (geomType === 'LineString' || geomType === 'MultiLineString') {
                    newFeature = processPolyline(feature);
                }

                if (newFeature) {
                    const newLayer = L.geoJSON(newFeature, {
                        style: {
                            fillColor: '#f87171', // red-400
                            color: '#dc2626',    // red-600
                            weight: 3,
                            opacity: 0.8,
                            fillOpacity: 0.7,
                            fill: true
                        }
                    }).addTo(map);

                    // Add click event to the new red layer to remove it
                    newLayer.on('click', (e) => {
                        map.removeLayer(e.target);
                    });
                    
                    map.fitBounds(newLayer.getBounds());
                } else {
                    showMessage('Could not process feature.');
                }
                loadingSpinner.classList.add('hidden');
            }

            fileInput.addEventListener('change', async (event) => {
                const file = event.target.files[0];
                if (!file) return;

                hideMessage(); // Hide any previous messages
                loadingSpinner.classList.remove('hidden');
                fileLabel.textContent = file.name;

                // Clear existing features in the drawnItems layer group
                //drawnItems.clearLayers();

                const reader = new FileReader();
                reader.onload = async (e) => {
                    const fileContent = e.target.result;
                    let geojsonData;

                    try {
                        const fileExtension = file.name.split('.').pop().toLowerCase();
                        
                        // Decode ArrayBuffer to string for XML-based files
                        const decoder = new TextDecoder('utf-8');
                        let fileString = '';
                        if (fileExtension !== 'geojson' && fileExtension !== 'json') {
                           fileString = decoder.decode(fileContent);
                        } else {
                            // GeoJSON/JSON can be parsed directly from the ArrayBuffer
                            fileString = fileContent;
                        }

                        if (fileExtension === 'geojson' || fileExtension === 'json') {
                            geojsonData = JSON.parse(fileString);
                        } else if (fileExtension === 'kml') {
                            const kml = new DOMParser().parseFromString(fileString, 'text/xml');
                            geojsonData = toGeoJSON.kml(kml);
                            // Fallback to manual parsing if toGeoJSON fails to find features
                            if (!geojsonData || !geojsonData.features || geojsonData.features.length === 0) {
                                geojsonData = parseKmlCoordinates(kml);
                            }
                        } else if (fileExtension === 'gml') {
                            const gml = new DOMParser().parseFromString(fileString, 'text/xml');
                            // Directly use the custom GML parser
                            geojsonData = parseGmlCoordinates(gml);
                        } else if (fileExtension === 'gpx') {
                            const gpx = new DOMParser().parseFromString(fileString, 'text/xml');
                            geojsonData = toGeoJSON.gpx(gpx);
                        } else if (fileExtension === 'kmz') {
                            const zip = await JSZip.loadAsync(fileContent);
                            const kmlFile = zip.file(/\.kml$/i)[0];
                            if (!kmlFile) throw new Error('No KML file found inside KMZ');
                            const kmlContent = await kmlFile.async('string');
                            const kml = new DOMParser().parseFromString(kmlContent, 'text/xml');
                            geojsonData = toGeoJSON.kml(kml);
                            // Fallback to manual parsing for KMZ if toGeoJSON fails
                            if (!geojsonData || !geojsonData.features || geojsonData.features.length === 0) {
                                geojsonData = parseKmlCoordinates(kml);
                            }
                        } else {
                            throw new Error('Unsupported file type.');
                        }

                        // Final check to ensure data is valid and has features
                        if (!geojsonData || !geojsonData.features || geojsonData.features.length === 0) {
                            throw new Error('File contains no valid geospatial features.');
                        }
                    } catch (error) {
                        showMessage(`Error parsing file: ${error.message}`);
                        loadingSpinner.classList.add('hidden');
                        return;
                    }
                    
                    // Add imported features to the drawnItems layer group and fit bounds
                    if (geojsonData.type === 'FeatureCollection' && geojsonData.features) {
                        L.geoJSON(geojsonData, {
                            style: (feature) => {
                                const geomType = feature.geometry.type;
                                if (geomType === 'Polygon' || geomType === 'MultiPolygon') {
                                    return {
                                        fillColor: '#60a5fa', // blue-400
                                        color: '#2563eb',    // blue-600
                                        weight: 2,
                                        opacity: 0.8,
                                        fillOpacity: 0.5,
                                        fill: true
                                    };
                                } else if (geomType === 'LineString' || geomType === 'MultiLineString') {
                                    return {
                                        color: '#1d4ed8', // blue-700
                                        weight: 4,
                                        opacity: 0.7
                                    };
                                }
                                return {};
                            }
                        }).eachLayer(layer => {
                            drawnItems.addLayer(layer);
                        });
                    } else if (geojsonData.type === 'Feature') {
                        L.geoJSON(geojsonData, {
                            style: (feature) => {
                                const geomType = feature.geometry.type;
                                if (geomType === 'Polygon' || geomType === 'MultiPolygon') {
                                    return {
                                        fillColor: '#60a5fa', // blue-400
                                        color: '#2563eb',    // blue-600
                                        weight: 2,
                                        opacity: 0.8,
                                        fillOpacity: 0.5,
                                        fill: true
                                    };
                                } else if (geomType === 'LineString' || geomType === 'MultiLineString') {
                                    return {
                                        color: '#1d4ed8', // blue-700
                                        weight: 4,
                                        opacity: 0.7
                                    };
                                }
                                return {};
                            }
                        }).eachLayer(layer => {
                            drawnItems.addLayer(layer);
                        });
                    }
                    
                    map.fitBounds(drawnItems.getBounds());
                    loadingSpinner.classList.add('hidden');
                };

                reader.onerror = () => {
                    showMessage('Failed to read file!');
                    loadingSpinner.classList.add('hidden');
                };

                reader.readAsArrayBuffer(file);
            });
            
            // Custom parser for GML files
            function parseGmlCoordinates(xmlDoc) {
                const features = [];
                // Search for a <posList> within a <LinearRing>
                const posListText = xmlDoc.querySelector('gml\\:LinearRing gml\\:posList, LinearRing posList')?.textContent;

                if (posListText) {
                    // Coordinates in GML posList are typically lat, lon, height
                    // We need to parse them and reverse the order to lon, lat for GeoJSON
                    const coords = posListText.trim().split(/\s+/).map((c, i, arr) => {
                        // GML posList can have different coordinate orders. This assumes lat lon.
                        // We will need to check the srsName to be sure, but a common format is lat lon
                        if (i % 2 === 0) {
                            return [parseFloat(arr[i + 1]), parseFloat(c)];
                        }
                        return null;
                    }).filter(c => c !== null);
                    
                    if (coords.length > 0) {
                        // The GeoJSON specification requires the first and last points of a polygon to be the same
                        // GML LinearRing doesn't always have this. We ensure it here.
                        if (coords[0][0] !== coords[coords.length - 1][0] || coords[0][1] !== coords[coords.length - 1][1]) {
                            coords.push(coords[0]);
                        }
                        
                        features.push({
                            type: 'Feature',
                            geometry: {
                                type: 'Polygon',
                                coordinates: [coords]
                            },
                            properties: {}
                        });
                    }
                }
                
                return {
                    type: 'FeatureCollection',
                    features: features
                };
            }
            
            // Custom KML parser for coordinates (as a fallback)
            function parseKmlCoordinates(xmlDoc) {
                const features = [];
                const coordinatesText = xmlDoc.querySelector('Polygon LinearRing coordinates, LineString coordinates')?.textContent;

                if (coordinatesText) {
                    const coords = coordinatesText.trim().split(/\s+/).map(c => {
                        const parts = c.split(',');
                        return [parseFloat(parts[0]), parseFloat(parts[1])];
                    });
                    
                    if (coords.length > 0) {
                        features.push({
                            type: 'Feature',
                            geometry: {
                                type: 'Polygon',
                                coordinates: [coords]
                            },
                            properties: {}
                        });
                    }
                }
                
                return {
                    type: 'FeatureCollection',
                    features: features
                };
            }

            function processPolygon(originalPolygon) {
                // Simplify the original polygon to get to under 100 points
                let simplifiedPolygon = originalPolygon;
                let numPoints = turf.coordAll(originalPolygon).length;
                let tolerance = 0.001; // Initial tolerance

                const maxSimplifyIterations = 20;
                let iterations = 0;
                while (numPoints > 100 && iterations < maxSimplifyIterations) {
                    simplifiedPolygon = turf.simplify(simplifiedPolygon, { tolerance: tolerance, highQuality: false });
                    numPoints = turf.coordAll(simplifiedPolygon).length;
                    tolerance *= 1.5; // Increase tolerance for next try
                    iterations++;
                }

                // Buffer the simplified polygon to cover the original
                let bufferedPolygon = null;
                let bufferDistance = 0.001;
                const maxBufferIterations = 20;
                iterations = 0;

                while (!bufferedPolygon && iterations < maxBufferIterations) {
                    bufferedPolygon = turf.buffer(simplifiedPolygon, bufferDistance);
                    // Check if the buffered polygon covers the original
                    if (turf.booleanContains(bufferedPolygon, originalPolygon)) {
                        break;
                    }
                    bufferedPolygon = null; // Reset for next iteration
                    bufferDistance *= 1.5;
                    iterations++;
                }

                if (!bufferedPolygon) {
                    // Fallback to a single larger buffer if iterative fails
                    bufferedPolygon = turf.buffer(simplifiedPolygon, 0.02);
                }

                // Final simplification of the buffered polygon to ensure < 100 points
                let finalPolygon = bufferedPolygon;
                numPoints = turf.coordAll(bufferedPolygon).length;
                tolerance = 0.0001; // Start with a very small tolerance

                const maxFinalSimplifyIterations = 25;
                iterations = 0;
                while (numPoints > 100 && iterations < maxFinalSimplifyIterations) {
                    const tempSimplified = turf.simplify(finalPolygon, { tolerance: tolerance, highQuality: false });
                    // Only use the simplified version if it still contains the original
                    if (turf.booleanContains(tempSimplified, originalPolygon)) {
                        finalPolygon = tempSimplified;
                        numPoints = turf.coordAll(finalPolygon).length;
                    }
                    tolerance *= 1.5;
                    iterations++;
                }
                
                return finalPolygon;
            }

            function processPolyline(originalPolyline) {
                let finalPolygon = null;
                let bufferDistance = 0.5;
                const maxAttempts = 20;
                let attempts = 0;

                while (!finalPolygon && attempts < maxAttempts) {
                    const bufferedPolygon = turf.buffer(originalPolyline, bufferDistance);
                    let simplifiedPolygon = bufferedPolygon;
                    let numPoints = turf.coordAll(simplifiedPolygon).length;
                    let simplifyTolerance = 0.0001;

                    const maxSimplifyIterations = 25;
                    let simplifyAttempts = 0;

                    // Simplify until we are under 100 points or hit max iterations
                    while (numPoints > 100 && simplifyAttempts < maxSimplifyIterations) {
                        const tempSimplified = turf.simplify(simplifiedPolygon, { tolerance: simplifyTolerance, highQuality: false });

                        // Check if the simplified version still contains the original line
                        if (turf.booleanContains(tempSimplified, originalPolyline)) {
                            simplifiedPolygon = tempSimplified;
                            numPoints = turf.coordAll(simplifiedPolygon).length;
                        } else {
                            // If it doesn't, we can't simplify further, so we break
                            break;
                        }
                        simplifyTolerance *= 1.5;
                        simplifyAttempts++;
                    }

                    // Check if the simplified polygon is under the point limit and contains the original line
                    if (numPoints <= 100 && turf.booleanContains(simplifiedPolygon, originalPolyline)) {
                        finalPolygon = simplifiedPolygon;
                    } else {
                        // If not, increase the buffer distance and try again
                        bufferDistance *= 1.5;
                    }
                    attempts++;
                }

                return finalPolygon;
            }
        });
    </script>
</body>
</html>

