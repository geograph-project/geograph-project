<?php

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;
$USER->mustHavePerm("basic");

$smarty->display('_std_begin.tpl');

$db = GeographDatabaseConnection(false);

// --- FORM SUBMISSION LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_tags'])) {
    $submitted_tags = isset($_POST['tags']) ? $_POST['tags'] : [];
    $image_ids_on_page = isset($_POST['image_ids']) ? explode(',', $_POST['image_ids']) : [];

    if (!empty($image_ids_on_page)) {
        try {
            // Process tag additions
            foreach ($submitted_tags as $gridimage_id => $tags_to_add) {
                if (!empty($tags_to_add)) {
                    $tags = new Tags;
                    $tags->addTags($tags_to_add, 'top');
                    $tags->commit($gridimage_id, true);
                }
            }

            // Mark all images on the page as processed
            $update_sql = "UPDATE clipthelandscape SET processed = NOW() WHERE user_id = ? AND gridimage_id IN (?" . str_repeat(",?", count($image_ids_on_page) - 1) . ")";
            $params = array_merge([$USER->user_id], $image_ids_on_page);
            $db->execute($update_sql, $params);

        } catch (Exception $e) {
            error_log("Failed to process tags submission: " . $e->getMessage());
        }
    }

    // Refresh the page to show the next batch
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

// --- DATA FETCHING LOGIC ---
$user_id = $USER->user_id;
$sql = "SELECT c.gridimage_id, c.user_id, gs.title, gs.grid_reference, gs.realname, imagetaken, c.labels " .
       "FROM clipthelandscape c " .
       "INNER JOIN gridimage_search gs USING (gridimage_id, user_id) " .
       "WHERE c.processed IS NULL AND c.tops = 0 AND c.user_id = ? " .
       "LIMIT 20";

$images = $db->getAll($sql, array($user_id));

// --- FETCH ALL TAGS FOR JAVASCRIPT ---
$all_tags_sql = "SELECT grouping, top FROM category_primary ORDER BY sort_order";
$all_tags = $db->getAll($all_tags_sql);


// --- DISPLAY LOGIC ---
if (!empty($images)) {
    echo '<script>const allTags = ' . json_encode($all_tags) . ';</script>';
    echo '<h2>Suggested Tags for Your Images</h2>';
    echo '<p>Review the suggested tags and uncheck any you do not want to add. Click "Submit" at the bottom to process the checked tags. Click an Expand button to get the full Context list to select from</p>';
    echo '<form method="POST" action="">';
    echo '<table border="1" cellpadding="5" cellspacing="0" border=1 bordercolor="#eee">';
    echo '<thead><tr><th>Thumbnail</th><th>Suggested Tags</th></tr></thead>';
    echo '<tbody>';

    $image_ids_on_page = [];
    foreach ($images as $row) {
        $image = new GridImage();
        $image->fastInit($row);
        $image_ids_on_page[] = $image->gridimage_id;

        echo '<tr>';
        echo '<td style="width: 222px; text-align: center;">';
        echo '<a title="' . htmlentities2($image->grid_reference . ' : ' . $image->title . ' by ' . $image->realname) . '" href="/photo/' . $image->gridimage_id . '" target="_blank">';
        echo $image->getThumbnail(213, 160, false, true);
        echo '</a>';
        echo '</td>';

        echo '<td>';
       echo "<b>".htmlentities2($image->title)."</b>";
       if (!empty($row['imagetaken']) && $row['imagetaken'] > 1000)
               print ", ".substr($row['imagetaken'],0,4);
       print "<hr>";
        echo '<div id="tags-container-' . $image->gridimage_id . '" style="margin-bottom:8px">';
        $tags = explode(';', $row['labels']);
        foreach ($tags as $tag) {
            $tag = trim($tag);
            if (!empty($tag)) {
                echo '<label style="display: block; margin: 2px;">';
                echo '<input type="checkbox" name="tags[' . $image->gridimage_id . '][]" value="' . htmlspecialchars($tag) . '*" checked> '; //need to mark as a 'suggested' tag!
                echo htmlspecialchars($tag);
                echo '</label>';
            }
        }
        echo '</div>';
        echo '<button type="button" class="toggle-tags-btn" data-imageid="' . $image->gridimage_id . '">Expand &gt;</button>';
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';

    // Hidden input to carry over the image IDs that were displayed on the page
    echo '<input type="hidden" name="image_ids" value="' . implode(',', $image_ids_on_page) . '">';

    echo '<br><input type="submit" name="submit_tags" value="Submit">';
    echo '</form>';
} else {
    echo '<p>No more images with tag suggestions found.</p>';
}

?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const buttons = document.querySelectorAll('.toggle-tags-btn');

    buttons.forEach(button => {
        button.addEventListener('click', function () {
            const imageId = this.dataset.imageid;
            const container = document.getElementById('tags-container-' + imageId);
            const isExpanded = this.textContent === 'Collapse';

            // Get currently checked tags
            const checkedTags = new Set();
            container.querySelectorAll('input[type="checkbox"]:checked').forEach(cb => {
                checkedTags.add(cb.value);
            });

            // Clear the container
            container.innerHTML = '';

            if (isExpanded) {
                // Collapse logic
                this.textContent = 'Expand >';
                checkedTags.forEach(tagValue => {
                    const label = document.createElement('label');
                    label.style.display = 'block';
                    label.style.margin = '2px';

                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.name = `tags[${imageId}][]`;
                    checkbox.value = tagValue;
                    checkbox.checked = true;

                    label.appendChild(checkbox);
                    label.append(' ' + tagValue);
                    container.appendChild(label);
                });
            } else {
                // Expand logic
                this.textContent = 'Collapse';
                let currentGrouping = '';
                let groupContainer = null;

                allTags.forEach(tagData => {
                    if (tagData.grouping !== currentGrouping) {
                        currentGrouping = tagData.grouping;

                        groupContainer = document.createElement('div');
                        groupContainer.style.float = 'left';
                        groupContainer.style.maxWidth = '200px';
                        groupContainer.style.marginRight = '20px';
                        container.appendChild(groupContainer);

                        const heading = document.createElement('h4');
                        heading.textContent = currentGrouping;
                        heading.style.marginTop = '10px';
                        heading.style.marginBottom = '5px';
                        groupContainer.appendChild(heading);
                    }

                    const tagValue = tagData.top;
                    const label = document.createElement('label');
                    label.style.display = 'block';
                    label.style.margin = '2px';

                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.name = `tags[${imageId}][]`;
                    checkbox.value = tagValue;
                    if (checkedTags.has(tagValue)) {
                        checkbox.checked = true;
                    }

                    label.appendChild(checkbox);
                    label.append(' ' + tagValue);
                    groupContainer.appendChild(label);
                });

                const clearer = document.createElement('div');
                clearer.style.clear = 'both';
                container.appendChild(clearer);
            }
        });
    });
});
</script>
<?php
$smarty->display('_std_end.tpl');
