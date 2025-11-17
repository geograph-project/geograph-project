<?

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;
$USER->mustHavePerm("basic");

$groups = array('top','edu');
if (empty($_GET['group']) || !in_array($_GET['group'], $groups))
	$_GET['group'] = $groups[0];

$gurl = urlencode($_GET['group']);
//default
$query = 'Coastal';
$tag = 'Coastal';
$ai = false;

if ($_GET['group'] == 'top') {

	if (!empty($_GET['tag'])) {
		$db = GeographDatabaseConnection(true);
		if ($_GET['tag'] == 'random') {
			$row = $db->getRow("select top,count(*) as images,sum(t.user_id = {$USER->user_id}) as yours
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
	$ai = false;

} elseif ($_GET['group'] == 'edu') {

	if (!empty($_GET['tag'])) {
		$db = GeographDatabaseConnection(true);
		if ($_GET['tag'] == 'random') {
			$row = $db->getRow("select label from label_embedding where label like '%>%' AND model = 'clip' ORDER BY RAND() LIMIT 1"); //no attempt to find unphotographed one yet!
			if (!empty($row)) {
				//actully lets redirect, will be much nider if have the tag in URL (for logs etc)
				customNoCacheHeader();
				header("Location: ?tag=".urlencode($row['label'])."&group=edu");
				exit;

				$tag = $query = $row['label'];
			}

		//just a place holder. Needs better logic! - for no new we need the embedding!
		} else	if ($db->getOne("SELECT label FROM label_embedding WHERE label = ".$db->Quote($_GET['tag'])." AND model='clip'")) {
			$tag = $query = $_GET['tag'];
		}
	}

	$ai = true;
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
            <div class="column-header">
                <h2 id="resultHeader">Search Results</h2>
                <div class="ai-enhanced-toggle"<? if (!$ai) { echo ' style="display:none"'; } ?> title="NOTE: the AI search current ignores the user filter (so shows everybodies images)">
                    <input type="checkbox" id="aiEnhancedCheckbox">
                    <label for="aiEnhancedCheckbox">AI Enhanced Search</label>
                </div>
            </div>
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
            <div class="column-header">
                <h2>Selected Images (<span id="currentTag"><? echo htmlentities($tag); ?></span>)</h2>
		<button id="themeToggle">Toggle Dark Theme</button>
	    </div>
            <div class="selected-images" id="selectedImages"></div>
        </div>
    </div>
    <div class="footer">
	<a href="/">Back to Geograph</a> | <a href="?tag=random&group=<? echo $gurl; ?>">Load another Random Tag</a> | <a href="curator-stats.php?group=<? echo $gurl; ?>">View Stats</a>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"></script>
    <script src="curator.js?<? echo filemtime('curator.js'); ?>"></script>
</body>
</html>
