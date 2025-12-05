<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Geograph Zero-Shot Search Demo</title>
    <style>
        body { font-family: sans-serif; }
        #search-form { margin-bottom: 20px; }
        #search-form input, #search-form textarea {
            display: block;
            width: 100%;
            max-width: 600px;
            padding: 8px;
            margin-bottom: 10px;
            box-sizing: border-box;
        }
        #search-form button { padding: 3px 5px; }
        .label-group { margin-bottom: 3px; }
        .label-group h2 { margin: 0; }
        .image-container { display: flex; --flex-wrap: wrap; gap: 2px; min-width:150px; min-height:200px }
        .image-item { text-align: center; }
        .image-item p { font-size: 0.9em; margin: 5px 0 0 0; }
        #loading-indicator { display: none; font-size: 1.2em; }

	#search-form #predefined-buttons button {
		font-size: 0.7em;
	}
	#search-form button#search-button {
		font-weight:bold;
	}

        .radio-options {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .radio-options label {
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
	    white-space: nowrap;
        }
	#search-form label:has(input:checked) {
	    font-weight:bold;
        }
    </style>
</head>
<body>

    <h2>Zero-Shot Image Search</h2>
    <p>Search for images and then classify them against a list of labels, or group the images into a arbitary number of clusters.</p>

    <div id="search-form">
        <label>AI Model:</label>
        <div class="radio-options">
            <label>
                <input type="radio" name="model" value="clip" checked> CLIP
            </label>
            <label>
                <input type="radio" name="model" value="pe"> Perception Encoder
            </label>
        </div>
        <label for="search-query">Search Query:</label>
        <div class="radio-options">
            <label>
                <input type="radio" name="type" value="label" checked>"Looks Like" Mode
            </label>
            <label>
                <input type="radio" name="type" value="match">"Keywords" Mode
            </label>
        </div>
        <input type="text" id="search-query" value="castle" placeholder="e.g., castle, river, church">

        <label for="search-labels">Labels or Clusters:</label>
        <div class="radio-options">
            <label>
                <input type="radio" name="mode" value="classify" checked> Classify by Labels
            </label>
            <label>
                <input type="radio" name="mode" value="cluster"> Cluster into K Similarity Based groups
            </label>
        </div>

        <textarea id="search-labels" rows="3" placeholder="e.g., stone, ruin, modern, interior">stone, ruin, modern, interior</textarea>
	<div id="predefined-buttons">Predefined:
		<button data-list="Mountain Landscape, Forest/Woodland, Riverside, Coastal/Harbor, Natural Scenery, General Building, Housing, Commercial Buildings, Office Buildings, Skyscrapers, Retail/Shopfronts, Parking Structure, Historic Ruins, Churches/Steeples, Roads/Highways, Railroad Tracks, Bridges, Power Lines, Bus Stops/Stations, Traffic Lights, Pedestrian Crossings, Subway/Metro, Various Objects, Vehicles, Signage/Maps, Local Produce, Statues/Monuments, Trash Cans/Bins, Public Benches, Fire Hydrants, Human Activity, People Working, Local Festival, Recreation/Leisure, Crowded Street, Outdoor Dining, Nightlife Scene, Parks/Plazas, Street Art/Graffiti, Panoramic View, Close-up, Aerial Shot, Street Level, Sunny Day, Overcast Day, Night Time, Sunset, Indoor, Outdoor, Residential Area, Industrial Zone, Miscellaneous/Unrelated">General List</button>
		<button data-list="Seashore, Beach, Ocean View, Harbor/Port, Estuary Mudflats, Island View, Small Islands/Ait, Flat Plains, Rolling Fields, Valley Floor, Highland Moor, Rocky Plateau, Mountain Summit, Hillside, Cliff Face, Rock Formation, Boulder Field, Scree Slope, Geological Outcrop, Calm Lake, Wetland Marsh, Peat Bog, Flowing River, Winding Stream, Drainage Ditch, Open Grassland, Dry Heathland, Dense Scrub, Deciduous Forest, Conifer Forest, Dense Woods, Wild Animal, Wild Flower, Mushroom/Fungi, Wild Bird, Water Reservoir, Water Tower, Dam Structure, Wind Turbine, Solar Panel Array, Power Station/Plant, Radio Tower, Cellular Antenna, Satellite Dish, Fencing/Barrier, Farmhouse, Arable Field, Fishing Boat, Greenhouse/Nursery, Open-Pit Quarry, Mine Entrance/Shaft, Industrial Excavator, Factory Building, Construction Site, Abandoned Structure, Derelict Machinery, City Skyline, Urban Street, Village Square, Residential Street, Historic Estate, Mansion House, Town Hall/Public Building, Library, Museum, Shopfront/Retail, Office Building, Sports Field, Playground Equipment, Golf Course, Public Park, Formal Garden, Residential House, Apartment Block, School/University, Hospital/Clinic, Church/Chapel, Historic Ruin, Clear Blue Sky, Storm Clouds, Heavy Rain, Snow/Ice, Aircraft/Airplane, Train/Railway, Motor Vehicle, Canal Barge/Boat, People in a Crowd, Outdoor Event/Gathering, Miscellaneous/Unrelated Image">Context List</button>
		<button data-list="Skyscrapers, Office Buildings, Retail/Shopfronts, Apartment Block, Historic Facade, Modern Glass Tower, Industrial Loft, Parking Structure, Traffic Lights, Pedestrian Crossings, Subway/Metro Entrance, Bus Stop Shelter, Street Signage, Paved Roadway, Overhead Power Lines, Public Plaza, City Park, Street Art/Graffiti, Outdoor Dining Area, Nightlife Scene, Public Fountain, Statue/Monument, Crowded Street, Commuters, Street Performer, Bicyclist, Delivery Vehicle, Vehicular Traffic, People Working, City Skyline, High-Angle View, Close-up Detail, Bright Sunlight, Night Time View, Farmhouse, Mountain Summit, Calm Lake, Wild Animal, Miscellaneous/Unrelated">City Center</button>
		<button data-list="Arable Field, Grain Crop, Hay Bales, Irrigation System, Vineyard/Orchard, Ploughed Soil, Harvested Field, Cows/Cattle, Sheep/Flock, Poultry/Chickens, Horses, Tractor/Combine, Farm Vehicle, Farmhouse, Barn/Shed, Silo, Greenhouse/Nursery, Windmill/Water Pump, Wooden Fencing, Stone Wall Boundary, Rolling Hills, Open Pasture, Rural Stream, Forest Edge, Dirt Road, Cloudy Sky, Sunrise/Sunset, Farmer Working, Animal Grazing, Haying/Planting, Farm Produce, Rural Event, Skyscrapers, Traffic Lights, Retail/Shopfronts, Subway/Metro Entrance, Miscellaneous/Unrelated">Farmland</button>
		<button data-list="Ocean Waves, Calm Sea, Tidal Pool, Breaking Surf, Open Water, High Tide, Low Tide, Sandy Beach, Rocky Shore, Cliff Face, Coastal Dunes, Seabirds, Driftwood/Debris, Seagrass, Lighthouse, Pier/Jetty, Docks/Quay, Harbor Wall, Coastal Fortification, Beach Hut/Chalet, Coastal Path, Fishing Boat, Sailboat/Yacht, Cargo Ship, Buoy/Marker, Ferry, Speedboat, Sunset over Ocean, Fog/Mist, Stormy Sea, Clear Horizon, Tractor/Combine, Office Buildings, Silo, Greenhouse/Nursery, Miscellaneous/Unrelated">Coastal Images</button>
		<button data-list="Folded Rock Strata, Fault Line, Erosion Gully, Canyon/Gorge, Alluvial Fan, Arete/Ridge, U-Shaped Valley, Meandering River, River Delta, Oxbow Lake, Sand Bar, Sea Stack, Arch Rock, Abrasion Platform, Glacial Moraine, Drumlin, Kettle Lake, Glacier Ice, Snowfield, Cirque/Corrie, Volcanic Cone, Lava Flow, Crater Lake, Geothermal Vent, Basalt Column, Limestone Pavement, Cave Entrance, Sand Dune, Salt Pan, Desert Oasis, Office Building, Tractor/Combine, Crowded Street, Subway Entrance, Miscellaneous/Unrelated">Geomorphology</button>
		<button data-list="Granite Outcrop, Basalt Columns, Pumice Rock, Volcanic Ash, Obsidian Specimen, Layered Sandstone, Shale/Siltstone, Conglomerate Rock, Fossilized Shells, Coal Seam, Marble Slab, Slate Quarry, Gneiss Banding, Quartzite, Crystal Structure, Geode Interior, Mineral Vein, Rock Hammer/Tool, Rock Cleavage, Weathered Surface, Clay Soil, Gravel Bed, River Silt, Peat Sample, Bedrock Exposed, Geologist Sampling, Close-up Rock Texture, Rock Sample Label, Hand Holding Sample, Field Notes/Map, Grocery Store, Playground Equipment, Dining Table, Apartment Block, Soccer Field, Miscellaneous/Unrelated">Geology</button>
		<button data-list="Water Cascade, Waterfall, Rapids/Whitewater, Dam Overflow, Irrigation Canal, Floodwater, Mirror Reflection on Water, Water Reservoir, Marsh Vegetation, Swampy Ground, Pond/Pool, Dew/Moisture, Tide Coming In, Ripples on Water, Breaking Waves, Ocean Mist, Salty Sea Spray, Eroding Bank, Frozen Lake, Icy Surface, Glacial Meltwater, Snow-covered River, Iceberg, Water Turbidity, Water Measurement Gauge, Hydroelectric Plant, Bridge over Water, Waterfowl, Boats/Canoes, Fisherman Casting, Riverbank Vegetation, Wetland Boardwork, Historic Church, Mountain Summit, Factory Building, Shopping Mall, School Bus, Miscellaneous/Unrelated">Hydrology</button>
		<button data-list="Limestone Pavement, Arch Rock, Sand Bar, Bay View, Sandy Beach, Shingle Beach, Sea Cave, Sea Cliff, Tidal Creek, Headland, Lagoon, Mudflats, Offshore Bar, Raised Beach, Salt Marsh, Sand Dunes, Coastal Spit, Recurved Spit, Sea Stack, Storm Beach, Sea Stump, Tombolo, Wavecut Platform, Dune Regeneration, Gabions, Wooden Groyne, Stone Groyne, Offshore Breakwater, Wooden Revetments, Rock Armour, Sea Wall, Aqueduct, Channel Straightening, River Confluence, Dam Structure, River Delta, Flood Defenses, Flood Plain, Flooded Area, River Gorge, Earthen Levee, River Meander, Ox-bow Lake, Water Reservoir, River Channel, River Cliff, River Slip-off Slope, River Source, Water Spring/Source, V-shape Valley, Waterfall, Arête/Ridge, Glacial Cirque, Glacial Corrie, Glacial Drumlin, Dry Valley, Glacial Erratics, Glacial Trough, Hanging Valley, Kame Terrace, Glacial Moraine, Pingo, Pyramid Peak, Ribbon Lake, Scree Slope, Tarn Lake, Truncated Spur, U-shaped Valley, Fog/Mist, Weather Station, Hail Shower, Falling Snow, Cirrus Clouds, Cumulus Clouds, Cumulonimbus Clouds, Igneous Rock, Chalk Cliff, Limestone Rock, Sedimentary Rock, Tor Rock Formation, Education Centre, Footpath Erosion, Holiday Village, Seaside Town, Theme Park, Commercial Forestry, Fishing Vessel, Mining Operation, Farming Land, Retail Park, Industrial Estate, Science Park, Hamlet, Village, Town Centre, Central Business District, Suburbs, Terraced Housing, Semi-detached House, Detached House, Apartment Flats, Tower Block, Corner Shop, Shopping Parade, Shopping Mall">Geography</button>
		<button data-list="Limestone Pavement, Arch Rock, Sand Bar, Bay View, Sandy Beach, Shingle Beach, Sea Cave, Sea Cliff, Tidal Creek, Headland, Lagoon, Mudflats, Offshore Bar, Raised Beach, Salt Marsh, Sand Dunes, Coastal Spit, Recurved Spit, Sea Stack, Storm Beach, Sea Stump, Tombolo, Wavecut Platform, Dune Regeneration, Gabions, Wooden Groyne, Stone Groyne, Offshore Breakwater, Wooden Revetments, Rock Armour, Sea Wall, Aqueduct, Channel Straightening, River Confluence, Dam Structure, River Delta, Flood Defenses, Flood Plain, Flooded Area, River Gorge, Earthen Levee, River Meander, River Meander with Cut-off Loop, Ox-Bow Lake in a Floodplain, Crescent-shaped body of still water, Water Reservoir, River Channel, River Cliff, River Slip-off Slope, River Source, Water Spring/Source, V-shape Valley, Waterfall, Arête/Ridge, Glacial Cirque, Glacial Corrie, Glacial Drumlin, Dry Valley, Glacial Erratics, Glacial Trough, Hanging Valley, Kame Terrace, Glacial Moraine, Pingo, Pyramid Peak, Ribbon Lake, Scree Slope, Tarn Lake, Truncated Spur, U-shaped Valley, Fog/Mist, Weather Station, Hail Shower, Falling Snow, Cirrus Clouds, Cumulus Clouds, Cumulonimbus Clouds, Igneous Rock, Chalk Cliff, Limestone Rock, Sedimentary Rock, Tor Rock Formation, Education Centre, Footpath Erosion, Holiday Village, Seaside Town, Theme Park, Commercial Forestry, Fishing Vessel, Mining Operation, Farming Land, Retail Park, Industrial Estate, Science Park, Hamlet, Village, Town Centre, Central Business District, Suburbs, Terraced Housing, Semi-detached House, Detached House, Apartment Flats, Tower Block, Corner Shop, Shopping Parade, Shopping Mall">Geography 2</button>

		<!--
		<button data-list=""></button>
		<button data-list=""></button>
		-->
	</div>

        <input type="number" id="num-clusters" value="8" min="2" max="50" style="display: none;">

        <div id="group-tickbox" class="radio-options">
            <label>
                <input type="checkbox" id="group-by-place" name="group-by-place" value="1">Also Group by Place
            </label>
        </div>

        <button id="search-button">Search and Process</button>
    </div>

    <div id="loading-indicator">Loading...</div>

    <div id="results-container">
        <!-- Results will be displayed here -->
    </div>

    <!-- JavaScript libraries -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="/js/vector.class.js"></script>
    <script src="finder-demo.js?<? echo filemtime('finder-demo.js'); ?>"></script>

    <script>
    $(document).ready(function() {
        const storageKeys = {
            query: 'zeroShotSearchQuery',
            labels: 'zeroShotSearchLabels',
            type: 'zeroShotSearchType',
            mode: 'zeroShotSearchMode',
            clusters: 'zeroShotSearchClusters',
            model: 'zeroShotSearchModel'
        };

        // --- Core Functions ---

        // Toggles input visibility based on the selected mode
        function toggleInputs() {
            const mode = $('input[name="mode"]:checked').val();
            if (mode === 'classify') {
                $('#search-labels, #group-tickbox').show();
		$('#predefined-buttons').show();
                $('#num-clusters').hide();
            } else {
                $('#search-labels, #group-tickbox').hide();
		$('#predefined-buttons').hide();
                $('#num-clusters').show();
            }
        }


        // Reads values from the URL and Local Storage, then populates the form.
        function loadFormState() {
            const params = new URLSearchParams(window.location.search);

            // Query
            const queryFromUrl = params.get('query');
            const queryFromStorage = localStorage.getItem(storageKeys.query);
            $('#search-query').val(queryFromUrl || queryFromStorage || 'castle');

            // Labels
            const labelsFromUrl = params.get('labels');
            const labelsFromStorage = localStorage.getItem(storageKeys.labels);
            $('#search-labels').val(labelsFromUrl || labelsFromStorage || 'stone, ruin, modern, interior');

            // Clusters
            const clustersFromUrl = params.get('clusters');
            const clustersFromStorage = localStorage.getItem(storageKeys.clusters);
            $('#num-clusters').val(clustersFromUrl || clustersFromStorage || '8');

            // Search Type
            const typeFromUrl = params.get('type');
            const typeFromStorage = localStorage.getItem(storageKeys.type);
            const selectedType = typeFromUrl || typeFromStorage || 'label';
            $(`input[name="type"][value="${selectedType}"]`).prop('checked', true);

            // Mode
            const modeFromUrl = params.get('mode');
            const modeFromStorage = localStorage.getItem(storageKeys.mode);
            const selectedMode = modeFromUrl || modeFromStorage || 'classify';
            $(`input[name="mode"][value="${selectedMode}"]`).prop('checked', true);

            // Model
            const modelFromUrl = params.get('model');
            const modelFromStorage = localStorage.getItem(storageKeys.model);
            const selectedModel = modelFromUrl || modelFromStorage || 'clip';
            $(`input[name="model"][value="${selectedModel}"]`).prop('checked', true);

            toggleInputs();
        }

        // Saves current form values to local storage
        function saveToLocalStorage() {
            localStorage.setItem(storageKeys.query, $('#search-query').val());
            localStorage.setItem(storageKeys.labels, $('#search-labels').val());
            localStorage.setItem(storageKeys.type, $('input[name="type"]:checked').val());
            localStorage.setItem(storageKeys.mode, $('input[name="mode"]:checked').val());
            localStorage.setItem(storageKeys.clusters, $('#num-clusters').val());
            localStorage.setItem(storageKeys.model, $('input[name="model"]:checked').val());
        }

        // Updates the URL with current form values
        function updateUrl() {
            const params = new URLSearchParams();

            params.set('query', $('#search-query').val());
            params.set('type', $('input[name="type"]:checked').val());
            params.set('mode', $('input[name="mode"]:checked').val());
            params.set('model', $('input[name="model"]:checked').val());

            if ($('input[name="mode"]:checked').val() === 'classify') {
                params.set('labels', $('#search-labels').val());
            } else {
                params.set('clusters', $('#num-clusters').val());
            }

            const newUrl = `${window.location.pathname}?${params.toString()}`;
            history.pushState(null, '', newUrl);
        }

        // --- Event Handlers ---

        // 1. Load form state on page load (from URL or Local Storage)
        loadFormState();
        if (window.location.search) {
            runSearch();
        }

        // 2. Save form state to local storage on any form field change
        $('#search-form').on('change', 'input, textarea', function() {
            saveToLocalStorage();
        });

        // 3. Toggle inputs when mode changes
        $('input[name="mode"]').on('change', function() {
            toggleInputs();
        });

        // 4. Update URL and run search when the button is clicked
        $('#search-button').on('click', function() {
            saveToLocalStorage();
            updateUrl();
            runSearch();
        });

        // 4. Listen for popstate event (back/forward button) to restore form state
        window.addEventListener('popstate', function() {
            loadFormState();
	    if (window.location.search)
	        runSearch();
        });


	// Function to handle the predefined label button clicks

	    const targetTextarea = $('#search-labels');
	    // Attach event listener to all buttons within the 'Predefined' section
	    $('button[data-list]').on('click', function() {
	        // Get the label list from the data-list attribute
	        const newList = $(this).data('list');
	        
	        // Get the current labels for comparison
	        const currentLabels = targetTextarea.val().trim();
	        
	        // Check if the current labels are different AND not empty
	        if (currentLabels.length > 0 && currentLabels !== newList) {
	            // Prompt user for confirmation
	            if (!confirm('Are you sure you wish to overwrite your current custom labels?')) {
	                return; // Stop if the user cancels
	            }
	        }
	        
	        // Overwrite the textarea value
	        targetTextarea.val(newList);
	        
	        // Optional: Save the new labels to local storage immediately
	        //saveToLocalStorage(); 
	    });


    });
    </script>

</body>
</html>
