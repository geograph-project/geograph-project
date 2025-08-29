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
        .image-item img {
            max-width: 150px;
            height: auto;
        }
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
    </style>
</head>
<body>

    <h2>Zero-Shot Image Search</h2>
    <p>Search for images and then classify them against a list of labels.</p>

    <div id="search-form">
        <label for="search-query">Search Query:</label>
        <div class="radio-options">
            <label>
                <input type="radio" name="type" value="label" checked>Concept Search
            </label>
            <label>
                <input type="radio" name="type" value="match">Keywords Search
            </label>
        </div>
        <input type="text" id="search-query" value="castle" placeholder="e.g., castle, river, church">

        <label for="search-labels">Classification Labels (comma-separated):</label>
        <textarea id="search-labels" rows="3" placeholder="e.g., stone, ruin, modern, interior">stone, ruin, modern, interior</textarea>

        <div class="radio-options">
            <label>
                <input type="checkbox" id="group-by-place" name="group-by-place" value="1">Also Group by Place
            </label>
        </div>

        <button id="search-button">Search and Classify</button>
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
            type: 'zeroShotSearchType'
        };

        // --- Core Functions ---

        // Reads values from the URL and Local Storage, then populates the form.
        function loadFormState() {
            const params = new URLSearchParams(window.location.search);

            const queryFromUrl = params.get('query');
            const queryFromStorage = localStorage.getItem(storageKeys.query);
            $('#search-query').val(queryFromUrl || queryFromStorage || 'castle');

            const labelsFromUrl = params.get('labels');
            const labelsFromStorage = localStorage.getItem(storageKeys.labels);
            $('#search-labels').val(labelsFromUrl || labelsFromStorage || 'stone, ruin, modern, interior');

            const typeFromUrl = params.get('type');
            const typeFromStorage = localStorage.getItem(storageKeys.type);
            const selectedType = typeFromUrl || typeFromStorage;

            if (selectedType) {
                $(`input[name="type"][value="${selectedType}"]`).prop('checked', true);
            } else {
                $(`input[name="type"][value="label"]`).prop('checked', true);
            }
        }

        // Saves current form values to local storage
        function saveToLocalStorage() {
            localStorage.setItem(storageKeys.query, $('#search-query').val());
            localStorage.setItem(storageKeys.labels, $('#search-labels').val());
            localStorage.setItem(storageKeys.type, $('input[name="type"]:checked').val());
        }

        // Updates the URL with current form values
        function updateUrl() {
            const searchQuery = $('#search-query').val();
            const searchLabels = $('#search-labels').val();
            const searchType = $('input[name="type"]:checked').val();

            const params = new URLSearchParams();
            if (searchQuery) params.set('query', searchQuery);
            if (searchLabels) params.set('labels', searchLabels);
            if (searchType) params.set('type', searchType);

            const newUrl = `${window.location.pathname}?${params.toString()}`;
            history.pushState(null, '', newUrl);
        }

        // --- Event Handlers ---

        // 1. Load form state on page load (from URL or Local Storage)
        loadFormState();
        if (window.location.search)
            runSearch();

        // 2. Save form state to local storage on any form field change
        $('#search-form').on('change', '#search-query, #search-labels, input[name="type"]', function() {
            saveToLocalStorage();
        });

        // 3. Update URL and save to local storage only when the search button is clicked
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
