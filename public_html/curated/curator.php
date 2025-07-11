<?

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;
$USER->mustHavePerm("basic");

//default
$query = 'Coastal';
$tag = 'Coastal';

//$tag = get_leastused($USER);
//$query = conv_tag2query($tag);

if ($USER->user_id) {
	//special keyword in the sphinx index
	$query .=" user{$USER->user_id}";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Curation App</title>
    <link rel="stylesheet" href="curator.css?<? echo filemtime('curator.css'); ?>">
    <script>
       const API_DOMAIN = <? echo json_encode($CONF['API_HOST']); ?>;
       const STARTER_QUERY = <? echo json_encode($query); ?>;
       const CURRENT_TAG = <? echo json_encode($tag); ?>;
       const USER_ID = <? echo intval($USER->user_id); ?>;
    </script>
</head>
<body>
    <div class="container">
        <div class="column left-column">
            <h2 id="resultHeader">Search Results</h2>
            <div class="search-box">
                <input type="search" id="queryInput" value="<? echo htmlentities($query); ?>" placeholder="Enter search query...">
                <button id="searchButton">Search</button>
            </div>
            <div class="search-results" id="searchResults"></div>
            <div class="pagination">
                <button id="prevPage" disabled>Previous</button>
                <span id="currentPage">Pages</span>
                <button id="nextPage" disabled>Next</button>
            </div>
        </div>
        <div class="column right-column">
            <h2 class="right-column-header">
                <span>Selected Images (<span id="currentTag"><? echo htmlentities($tag); ?></span>)</span>
		<button id="themeToggle">Toggle Dark Theme</button>
	    </h2>
            <div class="selected-images" id="selectedImages"></div>
        </div>
    </div>
    <div class="footer">
	<a href="/">Back to Geograph</a> | <a href=?>Load another random tag</a>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"></script>
    <script src="curator.js?<? echo filemtime('curator.js'); ?>"></script>
</body>
</html>
