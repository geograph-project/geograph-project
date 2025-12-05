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
        <input type="number" id="num-clusters" value="8" min="2" max="50" style="display: none;">

        <div id="group-tickbox" class="radio-options">
            <label>
                <input type="checkbox" id="group-by-place" name="group-by-place" value="1">Also Group by Place
            </label>
        </div>

        <div class="radio-options">
            <label>
                <input type="checkbox" id="longer-results" name="longer-results" value="1">Longer Results
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
                $('#num-clusters').hide();
            } else {
                $('#search-labels, #group-tickbox').hide();
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
    });
    </script>

</body>
</html>
