<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Geospatial Operations</title>
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
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
            <p class="text-gray-600">Upload a KML, GML, or GeoJSON file and click on features to generate new, simplified polygons.</p>
        </header>

        <main>
            <div class="file-input-container">
                <label for="fileInput" class="input-label">
                    <span id="file-label">Choose a file...</span>
                    <input type="file" id="fileInput" class="hidden" accept=".kml,.gml,.geojson,.json,.kmz" />
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
    <!-- Turf.js for geospatial operations -->
    <script src="https://cdn.jsdelivr.net/npm/@turf/turf@6/turf.min.js"></script>
    <!-- JSZip for KMZ files -->
    <script src="https://unpkg.com/jszip@3.10.1/dist/jszip.min.js"></script>
    <!-- ToGeoJSON for KML and GML parsing -->
    <script src="https://unpkg.com/@mapbox/togeojson@0.16.0/togeojson.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const map = L.map('map').setView([0, 0], 2);
            let originalLayer = null;

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

            function showMessage(text) {
                messageText.textContent = text;
                messageBox.classList.remove('hidden');
            }

            function hideMessage() {
                messageBox.classList.add('hidden');
            }
            
            closeMessageButton.addEventListener('click', hideMessage);

            fileInput.addEventListener('change', async (event) => {
                const file = event.target.files[0];
                if (!file) return;

                hideMessage(); // Hide any previous messages
                loadingSpinner.classList.remove('hidden');
                fileLabel.textContent = file.name;

                // Clear previous layers
                if (originalLayer) {
                    map.removeLayer(originalLayer);
                }

                const reader = new FileReader();
                reader.onload = async (e) => {
                    const fileContent = e.target.result;
                    let geojsonData;

                    try {
                        const fileExtension = file.name.split('.').pop().toLowerCase();
                        if (fileExtension === 'geojson' || fileExtension === 'json') {
                            geojsonData = JSON.parse(fileContent);
                        } else if (fileExtension === 'kml') {
                            const kml = new DOMParser().parseFromString(fileContent, 'text/xml');
                            geojsonData = toGeoJSON.kml(kml);
                            // Fallback to manual parsing if toGeoJSON fails to find features
                            if (!geojsonData || !geojsonData.features || geojsonData.features.length === 0) {
                                geojsonData = parseKmlCoordinates(kml);
                            }
                        } else if (fileExtension === 'gml') {
                            const gml = new DOMParser().parseFromString(fileContent, 'text/xml');
                            geojsonData = toGeoJSON.gml(gml);
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

                    originalLayer = L.geoJSON(geojsonData, {
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
                        },
                        onEachFeature: (feature, layer) => {
                            layer.on('click', async (e) => {
                                loadingSpinner.classList.remove('hidden');
                                await new Promise(resolve => setTimeout(resolve, 10)); // Allow UI to update
                                const geomType = feature.geometry.type;
                                let newFeature = null;
                                
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
                                    
                                    // Make sure the new layer is visible
                                    map.fitBounds(newLayer.getBounds());
                                } else {
                                    showMessage('Could not process feature.');
                                }
                                loadingSpinner.classList.add('hidden');
                            });
                        }
                    }).addTo(map);

                    map.fitBounds(originalLayer.getBounds());
                    loadingSpinner.classList.add('hidden');
                };

                reader.onerror = () => {
                    showMessage('Failed to read file!');
                    loadingSpinner.classList.add('hidden');
                };

                reader.readAsArrayBuffer(file);
            });
            
            // Custom KML parser for coordinates
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
                // Simplify the polygon to get to under 100 points
                let simplifiedPolygon = originalPolygon;
                let numPoints = turf.coordAll(originalPolygon).length;
                let tolerance = 0.001; // Initial tolerance

                const maxIterations = 10;
                let iterations = 0;
                while (numPoints > 100 && iterations < maxIterations) {
                    simplifiedPolygon = turf.simplify(simplifiedPolygon, { tolerance: tolerance, highQuality: false });
                    numPoints = turf.coordAll(simplifiedPolygon).length;
                    tolerance *= 1.5; // Increase tolerance for next try
                    iterations++;
                }

                // Buffer the simplified polygon to cover the original
                let bufferedPolygon = null;
                let bufferDistance = 0.001;
                const maxBufferIterations = 10;
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

                return bufferedPolygon;
            }

            function processPolyline(originalPolyline) {
                // Buffer the polyline to create a polygon
                const bufferedPolyline = turf.buffer(originalPolyline, 0.01);
                
                // Check if simplification is needed
                let numPoints = turf.coordAll(bufferedPolyline).length;
                if (numPoints > 100) {
                    let simplifiedPolyline = bufferedPolyline;
                    let tolerance = 0.001;
                    const maxIterations = 10;
                    let iterations = 0;
                    while (numPoints > 100 && iterations < maxIterations) {
                        simplifiedPolyline = turf.simplify(simplifiedPolyline, { tolerance: tolerance, highQuality: false });
                        numPoints = turf.coordAll(simplifiedPolyline).length;
                        tolerance *= 1.5;
                        iterations++;
                    }
                    return simplifiedPolyline;
                }

                return bufferedPolyline;
            }
        });
    </script>
</body>
</html>

