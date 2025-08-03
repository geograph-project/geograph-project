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
        .image-container { display: flex; --flex-wrap: wrap; gap: 2px; }
        .image-item { text-align: center; }
        .image-item img {
            max-width: 150px;
            height: auto;
        }
        .image-item p { font-size: 0.9em; margin: 5px 0 0 0; display:none }
        #loading-indicator { display: none; font-size: 1.2em; }
    </style>
</head>
<body>

    <h2>Zero-Shot Image Search</h2>
    <p>Search for images and then classify them against a list of labels.</p>

    <div id="search-form">
        <label for="search-query">Search Query:</label>
        <input type="text" id="search-query" value="castle" placeholder="e.g., castle, river, church">

        <label for="search-labels">Classification Labels (comma-separated):</label>
        <textarea id="search-labels" rows="3" placeholder="e.g., stone, ruin, modern, interior">stone, ruin, modern, interior</textarea>

        <button id="search-button">Search and Classify</button>
    </div>

    <div id="loading-indicator">Loading...</div>

    <div id="results-container">
        <!-- Results will be displayed here -->
    </div>

    <!-- JavaScript libraries -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="/js/vector.class.js"></script>
    <script src="/finder/finder-demo.js?<? echo filemtime('finder-demo.js'); ?>"></script>

</body>
</html>
