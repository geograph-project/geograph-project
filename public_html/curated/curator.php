<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Curation App</title>
    <link rel="stylesheet" href="curator.css?<? echo filemtime('curator.css'); ?>">
</head>
<body>
    <div class="container">
        <div class="column left-column">
            <h2 id="resultHeader">Search Results</h2>
            <div class="search-box">
                <input type="search" id="queryInput" value="Coastal" placeholder="Enter search query...">
                <button id="searchButton">Search</button>
            </div>
            <div class="search-results" id="searchResults">
                </div>
            <div class="pagination">
                <button id="prevPage" disabled>Previous</button>
                <span id="currentPage">Pages</span>
                <button id="nextPage" disabled>Next</button>
            </div>
        </div>
        <div class="column right-column">
            <h2>Selected Images (<span id="currentTag">Coastal</span>)</h2>
            <div class="selected-images" id="selectedImages">
                </div>
        </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"></script>
    <script src="curator.js?<? echo filemtime('curator.js'); ?>"></script>
</body>
</html>
