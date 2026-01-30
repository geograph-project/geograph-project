<?php

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;
$USER->mustHavePerm("basic");

$smarty->display('_std_begin.tpl');

$db = GeographDatabaseConnection(true);

// --- FORM SUBMISSION LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_tags'])) {
    $submitted_subjects = isset($_POST['subject']) ? $_POST['subject'] : [];
    $image_ids_on_page = isset($_POST['image_ids']) ? explode(',', $_POST['image_ids']) : [];

    if (!empty($image_ids_on_page)) {
        try {
            foreach ($submitted_subjects as $gridimage_id => $subject_to_add) {
                if (!empty($subject_to_add) && $subject_to_add !== 'none') {
                    $tags = new Tags;
                    $tags->addTag($subject_to_add, 'subject');
                    $tags->commit($gridimage_id, true);
                }
            }

            $update_sql = "UPDATE subjects_ai_result SET processed = NOW() WHERE user_id = ? AND gridimage_id IN (?" . str_repeat(",?", count($image_ids_on_page) - 1) . ")";
            $params = array_merge([$USER->user_id], $image_ids_on_page);
            $db->execute($update_sql, $params);

        } catch (Exception $e) {
            error_log("Failed to process subject submission: " . $e->getMessage());
        }
    }

    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

// --- DATA FETCHING ---
$user_id = $USER->user_id;
$sql = "SELECT c.gridimage_id, gs.user_id, gs.title, gs.grid_reference, gs.realname, gs.imagetaken, c.ai_result " .
       "FROM subjects_ai_result c " .
       "INNER JOIN gridimage_search gs ON c.gridimage_id = gs.gridimage_id " .
       "WHERE c.processed IS NULL AND gs.tags NOT like '%subject%' AND c.user_id = ? " .
       "LIMIT 20";
print $sql;
$images = $db->getAll($sql, array($user_id));

$all_subjects_sql = "SELECT subject FROM subjects ORDER BY subject ASC";
$all_subjects = $db->getAll($all_subjects_sql);

// --- DISPLAY LOGIC ---
if (!empty($images)) {
    echo '<script>const allSubjects = ' . json_encode($all_subjects) . ';</script>';
    echo '<h2>Suggested Subjects</h2>';

    echo '<div style="background: #fdf6e3; border: 1px solid #eee8d5; padding: 15px; margin-bottom: 20px; border-radius: 4px; line-height: 1.5;">';
    echo 'Please carefully review the suggested tags to look for the most appropriate subject tag for the image. ';
    echo 'Should use the <b>Expand</b> button to see the full subject list to select from. ';
    echo 'Note it is best to <b>leave on "None" if need be</b>; don\'t just pick the best of the AI suggestions if none are appropriate. ';
    echo 'Don\'t feel the need to just pick the best of a bad bunch.';
    echo '</div>';

    print '<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>';
    print "<link href=".smarty_modifier_revision("/js/select2-3.3.2/select2.css").' rel="stylesheet"/>';
    print "<script src=".smarty_modifier_revision("/js/select2-3.3.2/select2.js").'></script>';

    echo '<form method="POST" action="">';
    echo '<table border="1" cellpadding="5" cellspacing="0" bordercolor="#eee" style="width:100%; max-width:60em; border-collapse:collapse;">';
    echo '<thead><tr><th>Thumbnail</th><th>Suggested Subjects</th></tr></thead><tbody>';

    $image_ids_on_page = [];
    foreach ($images as $row) {
        $image = new GridImage();
        $image->fastInit($row);
        $image_ids_on_page[] = $image->gridimage_id;

        echo '<tr>';

	$url = $image->getThumbnail(213, 160, true, false);
	$style = "background-image: linear-gradient(rgba(255, 255, 255, 0.8), rgba(255, 255, 255, 0.9)), url($url); background-size:cover; text-shadow: 0px 0px 3px #ffffff;padding:10px";

        echo '<td style="width: 222px; text-align: center; vertical-align: top;">';
        echo '<a href="/photo/' . $image->gridimage_id . '" target="_blank">' . $image->getThumbnail(213, 160, false, true) . '</a>';
        echo '</td>';

        echo "<td style=\"vertical-align: top; $style\">";
        echo "<b>".htmlentities2($image->title)."</b>";
        if (!empty($row['imagetaken']) && $row['imagetaken'] > 1000) echo ", ".substr($row['imagetaken'],0,4);
        echo "<hr>";

        // Store the AI results in a data attribute so JS can rebuild the list later
        echo '<div id="tags-container-' . $image->gridimage_id . '"
                   data-suggestions="' . htmlspecialchars($row['ai_result']) . '"
                   style="margin-bottom:8px">';

        // Initial Radio List
        echo '<label style="display: block; color: #666;"><input type="radio" name="subject[' . $image->gridimage_id . ']" value="none" checked> <i>None / Skip</i></label>';

        $suggestions = explode(';', $row['ai_result']);
        foreach ($suggestions as $sug) {
            if (preg_match('/^(.*)\s(\d+%)$/', trim($sug), $matches)) {
                $tagName = trim($matches[1]);
                echo '<label style="display: block; margin: 2px;">';
                echo '<input type="radio" name="subject[' . $image->gridimage_id . ']" value="' . htmlspecialchars($tagName) . '"> ';
                echo htmlspecialchars($tagName) . ' <small style="color:#888">(' . $matches[2] . ')</small>';
                echo '</label>';
            }
        }
        echo '</div>';
        echo '<button type="button" class="toggle-subjects-btn" data-imageid="' . $image->gridimage_id . '">Select from full List &gt;</button>';
        echo '</td></tr>';
    }

    echo '</tbody></table>';
    echo '<input type="hidden" name="image_ids" value="' . implode(',', $image_ids_on_page) . '">';
    echo '<br><input type="submit" name="submit_tags" value="Submit Batch">';
    echo '</form>';
}
?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const buttons = document.querySelectorAll('.toggle-subjects-btn');

    buttons.forEach(button => {
        button.addEventListener('click', function () {
            const imageId = this.dataset.imageid;
            const container = document.getElementById('tags-container-' + imageId);
            const rawSuggestionsString = container.dataset.suggestions;
            const isExpanding = this.textContent.includes('Select');

            // 1. Capture current selection before clearing
            let currentVal = 'none';
            const selected = container.querySelector('input[type="radio"]:checked, select');
            if (selected) currentVal = selected.value;

            // 2. Parse suggestions into an array of names for easy checking
            const suggestionsArray = rawSuggestionsString.split(';').map(s => {
                const match = s.trim().match(/^(.*)\s(\d+%)$/);
                return match ? match[1].trim() : null;
            }).filter(n => n !== null);

            container.innerHTML = ''; // Clear container

            if (isExpanding) {
                // --- SWITCH TO DROPDOWN ---
                this.textContent = 'Back to suggestion list <';
                const select = document.createElement('select');
                select.name = `subject[${imageId}]`;
                select.id = `subject${imageId}`;
                //select.style.width = '100%';

                const optNone = document.createElement('option');
                optNone.value = 'none';
                optNone.textContent = '-- Select Subject --';
                select.appendChild(optNone);

                allSubjects.forEach(item => {
                    const opt = document.createElement('option');
                    opt.value = item.subject;
                    opt.textContent = item.subject;
                    if (item.subject === currentVal) opt.selected = true;
                    select.appendChild(opt);
                });
                container.appendChild(select);

                // Trigger your Select2 initialization here if needed
                $(`#subject${imageId}`).select2();

            } else {
                // --- SWITCH BACK TO RADIO LIST ---
                this.textContent = 'Select from full List >';

                // Add "None" radio
                const noneLabel = document.createElement('label');
                noneLabel.style.display = 'block';
                noneLabel.style.color = '#666';
                noneLabel.innerHTML = `<input type="radio" name="subject[${imageId}]" value="none" ${currentVal === 'none' ? 'checked' : ''}> <i>None / Skip</i>`;
                container.appendChild(noneLabel);

                // Add original AI suggestions
                const sugs = rawSuggestionsString.split(';');
                sugs.forEach(s => {
                    const match = s.trim().match(/^(.*)\s(\d+%)$/);
                    if (match) {
                        const name = match[1].trim();
                        const pct = match[2];
                        const label = document.createElement('label');
                        label.style.display = 'block';
                        label.style.margin = '2px';
                        label.innerHTML = `<input type="radio" name="subject[${imageId}]" value="${name}" ${currentVal === name ? 'checked' : ''}> ${name} <small style="color:#888">(${pct})</small>`;
                        container.appendChild(label);
                    }
                });

                // 3. CHECK FOR CUSTOM SELECTION
                // If the value isn't 'none' and isn't in the AI list, add a special radio for it
                if (currentVal !== 'none' && !suggestionsArray.includes(currentVal)) {
                    const customLabel = document.createElement('label');
                    customLabel.style.display = 'block';
                    customLabel.style.margin = '2px';
                    customLabel.style.padding = '2px';
                    customLabel.style.backgroundColor = '#fff9c4'; // Light yellow highlight to show it's custom
                    customLabel.style.border = '1px dashed #fbc02d';
                    customLabel.innerHTML = `<input type="radio" name="subject[${imageId}]" value="${currentVal}" checked> <b>${currentVal}</b> <small>(User Selected)</small>`;
                    container.appendChild(customLabel);
                }
            }
        });
    });
});
</script>

<?php
$smarty->display('_std_end.tpl');
