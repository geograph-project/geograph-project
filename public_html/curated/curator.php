<?

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;
$USER->mustHavePerm("basic");

//default
$query = 'Coastal';
$tag = 'Coastal';

if (!empty($_GET['tag'])) {
	$db = GeographDatabaseConnection(true);
	if ($_GET['tag'] == 'random') {
		$row = $db->getRow("select top,count(*) as images,sum(t.user_id = 3) as yours
			 from category_primary left join curated_tag t on (tag = top and status = 1) group by top order by yours,images,rand() limit 1");
		if (!empty($row)) {
			//actully lets redirect, will be much nider if have the tag in URL (for logs etc)
			customNoCacheHeader();
			header("Location: ?tag=".urlencode($row['top']));
			exit;

			$tag = $query = $row['top'];
		}

	//just a place holder. Needs better logic!
	} else	if ($db->getOne("SELECT tag_id FROM tag WHERE tag = ".$db->Quote($_GET['tag']))) {
		$tag = $query = $_GET['tag'];
	}
}

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
	<a href="/">Back to Geograph</a> | <a href="?tag=random">Load another Random Tag</a>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"></script>
    <script src="curator.js?<? echo filemtime('curator.js'); ?>"></script>
</body>
</html>
