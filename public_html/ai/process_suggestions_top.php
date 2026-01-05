<?php

require_once('../geograph/global.inc.php');
init_session();

$smarty = new GeographPage;
$USER->mustHavePerm("basic");

$smarty->display('_std_begin.tpl');

$db = GeographDatabaseConnection(true);

// --- FORM SUBMISSION LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_tags'])) {
    $submitted_tags = isset($_POST['tags']) ? $_POST['tags'] : [];
    $image_ids_on_page = isset($_POST['image_ids']) ? explode(',', $_POST['image_ids']) : [];

    if (!empty($image_ids_on_page)) {
        // Process tag additions
        foreach ($submitted_tags as $gridimage_id => $tags_to_add) {
            if (!empty($tags_to_add)) {
                $tags = new Tags;
                $tags->addTags($tags_to_add, 'top');
                $tags->commit($gridimage_id, true);
            }
        }

        // Mark all images on the page as processed
        $db->begin_transaction();
        try {
            $update_sql = "UPDATE clipthelandscape SET processed = NOW() WHERE gridimage_id IN (?" . str_repeat(",?", count($image_ids_on_page) - 1) . ")";
            $update_stmt = $db->prepare($update_sql);

            $types = str_repeat('i', count($image_ids_on_page));
            $update_stmt->bind_param($types, ...$image_ids_on_page);

            $update_stmt->execute();
            $update_stmt->close();
            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            error_log("Failed to update processed status: " . $e->getMessage());
        }
    }

    // Refresh the page to show the next batch
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

// --- DATA FETCHING LOGIC ---
$user_id = $USER->user_id;
$sql = "SELECT c.gridimage_id, c.user_id, gs.title, gs.grid_reference, gs.realname, c.labels " .
       "FROM clipthelandscape c " .
       "INNER JOIN gridimage_search gs USING (gridimage_id, user_id) " .
       "WHERE c.processed IS NULL AND c.tops = 0 AND c.user_id = ? " .
       "LIMIT 10";

$stmt = $db->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$images = [];
while ($row = $result->fetch_assoc()) {
    $images[] = $row;
}
$stmt->close();

// --- DISPLAY LOGIC ---
if (!empty($images)) {
    echo '<h2>Suggested Tags for Your Images</h2>';
    echo '<p>Review the suggested tags and uncheck any you do not want to add. Click "Submit" at the bottom to process the checked tags.</p>';
    echo '<form method="POST" action="">';
    echo '<table border="1" cellpadding="5" cellspacing="0">';
    echo '<thead><tr><th>Thumbnail</th><th>Suggested Tags</th></tr></thead>';
    echo '<tbody>';

    $image_ids_on_page = [];
    foreach ($images as $row) {
        $image = new GridImage();
        $image->fastInit($row);
        $image_ids_on_page[] = $image->gridimage_id;

        echo '<tr>';
        echo '<td style="width: 130px; text-align: center;">';
        echo '<a title="' . htmlspecialchars($image->grid_reference . ' : ' . $image->title . ' by ' . $image->realname) . '" href="/photo/' . $image->gridimage_id . '">';
        echo $image->getThumbnail(120, 120, false, true);
        echo '</a>';
        echo '</td>';

        echo '<td>';
        $tags = explode(';', $row['labels']);
        foreach ($tags as $tag) {
            $tag = trim($tag);
            if (!empty($tag)) {
                echo '<label style="display: block; margin: 2px;">';
                echo '<input type="checkbox" name="tags[' . $image->gridimage_id . '][]" value="' . htmlspecialchars($tag) . '" checked> ';
                echo htmlspecialchars($tag);
                echo '</label>';
            }
        }
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


$smarty->display('_std_end.tpl');

?>
