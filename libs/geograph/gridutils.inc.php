<?php

function output_style_block() {
?>
<style>
.grid-group {
    --margin-bottom: 25px;
    padding: 15px;
    background: #fdfdfd;
    --border: 1px solid #eaeaea;
}
.bucket-section {
    margin-top: 10px;
    padding: 10px;
    border-radius: 4px;
}
.good-section {
    background-color: #e1ffe1; /* Light green tint */
}
.bad-section {
    background-color: #fff5f5; /* Light red tint */
}
.grid-container {
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.grid-thumbnails {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}
.grid-thumbnails a img {
    border: 1px solid #ccc;
    border-radius: 4px;
    transition: transform 0.2s;
}
.grid-thumbnails a img:hover {
    transform: scale(1.05);
}
</style>
<?
}

##################

/**
 * Renders a grid of thumbnails grouped by a specific metric/column.
 *
 * @param string $table       The main metric table to join (e.g., 'curated_judge')
 * @param string $score       The metric column to select as 'metric'
 * @param string $column      The column to group the rows by
 * @param string $where       The WHERE clause conditions
 * @param int    $images      Max images to show per group
 * @param int    $groups      Max unique groups to display
 * @param string $extraTables Optional extra INNER/LEFT JOIN syntax
 * @param string $link Optional expression to form a link to add to the group
 */
function output_grid_table($table, $score, $column, $where, $images = 5, $groups = 10, $extraTables = '', $link = '') {
    global $db, $CONF; // Assuming $db is your ADODB instance

	if (empty($link)) $link = "''";

	if ($table == 'snippet_judge') //bodge!
		$link = "reasoning, $link";


    // 1. Build and execute the query safely
    // Note: Ensure $table, $score, and $column are whitelisted or safe from SQL injection
    $sql = "SELECT gi.gridimage_id, gi.user_id, gi.title, gi.realname, gi.grid_reference, gi.reference_index,
                   $score AS metric, $column AS grouper, $link as link
            FROM gridimage_search gi
            INNER JOIN $table t USING (gridimage_id)
            $extraTables
            WHERE $where
            ORDER BY grouper DESC, metric DESC"; // Sorting by grouper keeps groups together

//print_r($sql);

    // Over-fetch slightly to ensure we have enough candidates per group
    $fetchLimit = $images * $groups * 3;
    $rs = $db->SelectLimit($sql, $fetchLimit);

    if (!$rs) {
        echo "<p>Error executing query.</p>";
        return;
    }

    // 2. Process rows into capped groups
    $groupedData = [];
    $links = [];
    while ($row = $rs->FetchRow()) {
        $grouper = $row['grouper'];

        // If we already have max groups, and this is a new group, skip it
        if (!isset($groupedData[$grouper]) && count($groupedData) >= $groups) {
            continue;
        }

        // If the group exists but already has enough images, skip this row
        if (isset($groupedData[$grouper]) && count($groupedData[$grouper]) >= $images) {
            continue;
        }

        // Initialize group array if new
        if (!isset($groupedData[$grouper])) {
            $groupedData[$grouper] = [];
        }
	if (!empty($row['link']))
		$links[$grouper] = $row['link'];

        $groupedData[$grouper][] = $row;
    }

    // 3. Render the HTML Grid
    $thumbh = 120;
    $thumbw = 120;

    echo '<div class="grid-container">';
    foreach ($groupedData as $groupName => $rows) {
        // Render a section/row for each group
	$a = empty($links[$groupName])?'':"<a href=\"".htmlentities($links[$groupName])."\">";
        echo '<div class="grid-group">';
        echo '<h3>Group: ' . $a . htmlentities($groupName, ENT_QUOTES, 'UTF-8') . '</a></h3>';
        echo '<div class="grid-thumbnails">';

        foreach ($rows as $row) {
            $image = new GridImage();
            $image->fastInit($row);

            // Building clean, entity-encoded attributes
            $titleAttr = htmlentities(
                $image->grid_reference . ' : ' . $image->title . ' by ' . $image->realname . ' - click to view full size image', 
                ENT_QUOTES, 
                'UTF-8'
            );
		if (!empty($image->reasoning))
			$titleAttr .= " - ".htmlentities($image->reasoning);
            
            $url = $CONF['canonical_domain'][$image->reference_index] . '/photo/' . $image->gridimage_id;

            echo '<a title="' . $titleAttr . '" href="' . $url . '">';
            echo $image->getThumbnail($thumbw, $thumbh, false, true);
            echo '</a>';
        }

        echo '</div>'; // .grid-thumbnails
        echo '</div>'; // .grid-group
    }
    echo '</div>'; // .grid-container
}



################################


/**
 * Renders a grid of thumbnails split into 'Good' and 'Bad' metrics per group.
 *
 * @param string $table         The main metric table to join (e.g., 'curated_judge')
 * @param string $score         The metric column to select as 'metric'
 * @param string $column        The column to group the rows by
 * @param string $where         The WHERE clause conditions (exclude hard metric bounds here)
 * @param string $good_crit     What marks the row as good eg 'score>=4'
 * @param int    $good_limit    Max 'good' images to show per group
 * @param int    $bad_limit     Max 'bad' images to show per group
 * @param int    $groups_limit  Max unique groups to display
 * @param string $extraTables   Optional extra INNER/LEFT JOIN syntax
 * @param string $order         Optional order (default 'CRC32(grouper) ASC')
 */
function output_advanced_grid_table($table, $score, $column, $where, $good_crit, $good_limit = 5, $bad_limit = 5, $groups_limit = 10, $extraTables = '', $order = null, $link = null) {
    global $db, $CONF;

	//specifying values here, allows them to be skipped!
        if (empty($extraTables)) $extraTables = ''; //coalesce other falsy to string (as it used in string content)
        if (empty($order)) $order = "CRC32(grouper) ASC";
        if (empty($link)) $link = "''";

	if ($table == 'snippet_judge') //bodge!
		$link = "reasoning, $link";

    // 1. Build and execute the query
    // We sort by grouper, and then by metric descending so best results appear first
    $sql = "SELECT gi.gridimage_id, gi.user_id, gi.title, gi.realname, gi.grid_reference, gi.reference_index,
                   $score AS metric, $column AS grouper, $link as link
            FROM gridimage_search gi
            INNER JOIN $table t USING (gridimage_id)
            $extraTables
            WHERE $where
            ORDER BY grouper ASC, metric DESC";

    // We filter rowidx <= $images inside the subquery wrapper
    $sql = "SELECT * FROM (
                SELECT gi.gridimage_id, gi.user_id, gi.title, gi.realname, gi.grid_reference, gi.reference_index,
                       $score AS metric,
                       $column AS grouper, $link as link,
                       $good_crit AS is_good,
                       ROW_NUMBER() OVER (PARTITION BY $column, $good_crit ORDER BY $score DESC) AS rowidx
                FROM gridimage_search gi
                INNER JOIN $table t USING (gridimage_id)
                $extraTables
                WHERE $where
            ) AS subq
            WHERE rowidx <= " . (int)$good_limit . "
            ORDER BY $order, is_good DESC, metric DESC";

if (!empty($_GET['print']))
	print htmlentities($sql).";";

    // Increase the over-fetch multiplier slightly to ensure we grab enough 'bad' candidates too
    $fetchLimit = ($good_limit + $bad_limit) * $groups_limit * 4;
    $rs = $db->SelectLimit($sql, $fetchLimit);

    if (!$rs) {
        echo "<p>Error executing query.</p>";
        return;
    }

    // 2. Process rows into capped 'good' and 'bad' sub-buckets
    $groupedData = [];
    $links = [];
    while ($row = $rs->FetchRow()) {
        $grouper = $row['grouper'];
        $metric = (float)$row['metric'];

        // Determine if this row is 'good' or 'bad'
        $isGood = !!$row['is_good'];

        // Track unique main groups
        if (!isset($groupedData[$grouper])) {
            if (count($groupedData) >= $groups_limit) {
                continue; // Max unique groups reached
            }
            // Initialize sub-buckets for the new group
            $groupedData[$grouper] = [
                'good' => [],
                'bad'  => []
            ];
        }
	if (!empty($row['link']))
		$links[$grouper] = $row['link'];

        // Add to the appropriate bucket if it hasn't hit its specific cap
        if ($isGood && count($groupedData[$grouper]['good']) < $good_limit) {
            $groupedData[$grouper]['good'][] = $row;
        } elseif (!$isGood && count($groupedData[$grouper]['bad']) < $bad_limit) {
            $groupedData[$grouper]['bad'][] = $row;
        }
    }

    // Helper function to handle thumbnail rendering logic cleanly
    $renderThumbnails = function($rows) use ($CONF) {
        $thumbh = 120;
        $thumbw = 120;
        
        foreach ($rows as $row) {
            $image = new GridImage();
            $image->fastInit($row);

            $titleAttr = htmlentities(
                $image->grid_reference . ' : ' . $image->title . ' by ' . $image->realname . ' [Score: ' . $row['metric'] . '] - click to view', 
                ENT_QUOTES, 
                'UTF-8'
            );

		if (!empty($image->reasoning))
			$titleAttr .= "\n\nREASONING: ".htmlentities($image->reasoning);
            
            $url = $CONF['canonical_domain'][$image->reference_index] . '/photo/' . $image->gridimage_id;

            echo '<a title="' . $titleAttr . '" href="' . $url . '">';
            echo $image->getThumbnail($thumbw, $thumbh, false, true);
            echo '</a>';
        }
    };

    // 3. Render the HTML Grid with sub-sections
    echo '<div class="grid-container">';
    foreach ($groupedData as $groupName => $buckets) {
        // Skip rendering this group completely if it contains absolutely no images
        if (empty($buckets['good']) && empty($buckets['bad'])) {
            continue;
        }

        echo '<div class="grid-group">';
	$a = empty($links[$groupName])?'':"<a href=\"".htmlentities($links[$groupName])."\">";
        echo '<h2>' . $a. htmlentities($groupName, ENT_QUOTES, 'UTF-8') . '</a></h2>';

        // Render Good Segment
        if (!empty($buckets['good'])) {
            echo '<div class="bucket-section good-section">';
//            echo '<h3>Good Results (&ge; 4)</h3>';
            echo '<div class="grid-thumbnails">';
            $renderThumbnails($buckets['good']);
            echo '</div>';
            echo '</div>';
        }

        // Render Bad Segment
        if (!empty($buckets['bad'])) {
            echo '<div class="bucket-section bad-section">';
//            echo '<h3>Bad Results (< 4)</h3>';
            echo '<div class="grid-thumbnails">';
            $renderThumbnails($buckets['bad']);
            echo '</div>';
            echo '</div>';
        }

        echo '</div>'; // .grid-group
        echo '<hr class="group-divider" />';
    }
    echo '</div>'; // .grid-container
}
