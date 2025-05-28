<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2023 The GeoGraph Project Team
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

require_once('geograph/global.inc.php');

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Retrieve stored form data if available
$form_data = isset($_SESSION['export_form_data']) ? $_SESSION['export_form_data'] : [];
// Clear it after retrieving so it's not reused unintentionally on a fresh form load
if (isset($_SESSION['export_form_data'])) {
    unset($_SESSION['export_form_data']);
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Export Gridimage Search</title>
    <style>
        body { font-family: sans-serif; }
        .form-section { margin-bottom: 20px; padding: 15px; border: 1px solid #ccc; border-radius: 5px; }
        .form-section h3 { margin-top: 0; }
        label { display: block; margin-bottom: 5px; }
        input[type="text"], input[type="date"], select { width: 250px; padding: 8px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 3px; box-sizing: border-box; }
        .checkbox-group label { display: inline-block; margin-right: 15px; }
        .bbox-inputs input[type="text"] { width: 120px; margin-right: 5px; }
        .date-range-inputs input[type="date"] { width: 120px; margin-right: 5px; }
        input[type="submit"] { padding: 10px 20px; background-color: #4CAF50; color: white; border: none; border-radius: 3px; cursor: pointer; }
        input[type="submit"]:hover { background-color: #45a049; }
    </style>
</head>
<body>

<h2>Gridimage Search Export Configuration</h2>

<?php
// Display errors if they exist
if (isset($_SESSION['export_errors']) && !empty($_SESSION['export_errors'])) {
    echo '<div style="color: red; border: 1px solid red; padding: 10px; margin-bottom: 15px; background-color: #ffeeee;">';
    echo '<strong>Please correct the following errors:</strong><br>';
    foreach ($_SESSION['export_errors'] as $error) {
        echo htmlspecialchars($error) . '<br>';
    }
    echo '</div>';
    unset($_SESSION['export_errors']); // Clear errors after displaying
}
?>

<form action="export_gridimage_search.php" method="POST">

    <div class="form-section">
        <h3>1. Select Columns to Export</h3>
        <p><em>Spatial columns <code>point_xy</code> and <code>point_ll</code> are also available.</em></p>
        <div class="checkbox-group">
            <?php
            $available_columns = [
                'gridimage_id', 'user_id', 'moderation_status', 'title', 'title2', 
                'submitted', 'imageclass', 'imagetaken', 'upd_timestamp', 'x', 'y', 
                'grid_reference', 'credit_realname', 'realname', 'reference_index', 
                'comment', 'comment2', 'wgs84_lat', 'wgs84_long', 'ftf', 'seq_no',
                'point_xy', 'point_ll' // Added spatial columns here for selection
            ];
            $selected_columns = isset($form_data['columns']) && is_array($form_data['columns']) ? $form_data['columns'] : $available_columns; // Default to all checked or use stored data

            foreach ($available_columns as $column) {
                $checked = in_array($column, $selected_columns) ? 'checked' : '';
                echo "<label><input type='checkbox' name='columns[]' value='{$column}' {$checked}> {$column}</label>";
            }
            ?>
        </div>
    </div>

    <div class="form-section">
        <h3>2. CSV Format Options</h3>
        <label for="delimiter">Delimiter:</label>
        <input type="text" id="delimiter" name="delimiter" value="<?php echo htmlspecialchars(isset($form_data['delimiter']) ? $form_data['delimiter'] : ','); ?>">

        <label for="quote_char">Quote Character:</label>
        <input type="text" id="quote_char" name="quote_char" value="<?php echo htmlspecialchars(isset($form_data['quote_char']) ? $form_data['quote_char'] : '"'); ?>">

        <label for="escape_char">Escape Character (Note: CSV standard is to double the quote character):</label>
        <input type="text" id="escape_char" name="escape_char" value="<?php echo htmlspecialchars(isset($form_data['escape_char']) ? $form_data['escape_char'] : '\\'); ?>">
    </div>

    <div class="form-section">
        <h3>3. Row Filters</h3>

        <h4>Bounding Box (BBOX) - WGS84 Coordinates</h4>
        <div class="bbox-inputs">
            <label for="min_lat">Min Latitude (-90 to 90):</label>
            <input type="text" id="min_lat" name="min_lat" placeholder="e.g., 50.0" value="<?php echo htmlspecialchars(isset($form_data['min_lat']) ? $form_data['min_lat'] : ''); ?>">
            <label for="min_lon">Min Longitude (-180 to 180):</label>
            <input type="text" id="min_lon" name="min_lon" placeholder="e.g., -5.0" value="<?php echo htmlspecialchars(isset($form_data['min_lon']) ? $form_data['min_lon'] : ''); ?>">
            <br>
            <label for="max_lat">Max Latitude (-90 to 90):</label>
            <input type="text" id="max_lat" name="max_lat" placeholder="e.g., 60.0" value="<?php echo htmlspecialchars(isset($form_data['max_lat']) ? $form_data['max_lat'] : ''); ?>">
            <label for="max_lon">Max Longitude (-180 to 180):</label>
            <input type="text" id="max_lon" name="max_lon" placeholder="e.g., 2.0" value="<?php echo htmlspecialchars(isset($form_data['max_lon']) ? $form_data['max_lon'] : ''); ?>">
        </div>

        <label for="moderation_status">Moderation Status:</label>
        <select id="moderation_status" name="moderation_status">
            <?php
            $mod_statuses = ["Any", "Accepted", "Pending", "Rejected", "Geograph"];
            $current_mod_status = isset($form_data['moderation_status']) ? $form_data['moderation_status'] : 'Any';
            foreach ($mod_statuses as $status) {
                $selected = ($status === $current_mod_status || ($status === "Any" && $current_mod_status === '')) ? 'selected' : '';
                echo "<option value='" . ($status === "Any" ? "" : $status) . "' {$selected}>{$status}</option>";
            }
            ?>
        </select>

        <label for="imageclass">Image Class:</label>
        <input type="text" id="imageclass" name="imageclass" placeholder="e.g., Landscape" value="<?php echo htmlspecialchars(isset($form_data['imageclass']) ? $form_data['imageclass'] : ''); ?>">

        <label for="user_id">User ID:</label>
        <input type="text" id="user_id" name="user_id" placeholder="e.g., 123 (numeric)" value="<?php echo htmlspecialchars(isset($form_data['user_id']) ? $form_data['user_id'] : ''); ?>">

        <h4>Submitted Date Range (YYYY-MM-DD)</h4>
        <div class="date-range-inputs">
            <label for="submitted_after">Submitted After:</label>
            <input type="date" id="submitted_after" name="submitted_after" value="<?php echo htmlspecialchars(isset($form_data['submitted_after']) ? $form_data['submitted_after'] : ''); ?>">
            <label for="submitted_before">Submitted Before:</label>
            <input type="date" id="submitted_before" name="submitted_before" value="<?php echo htmlspecialchars(isset($form_data['submitted_before']) ? $form_data['submitted_before'] : ''); ?>">
        </div>

        <h4>Image Taken Date Range (YYYY-MM-DD)</h4>
        <div class="date-range-inputs">
            <label for="imagetaken_after">Image Taken After:</label>
            <input type="date" id="imagetaken_after" name="imagetaken_after" value="<?php echo htmlspecialchars(isset($form_data['imagetaken_after']) ? $form_data['imagetaken_after'] : ''); ?>">
            <label for="imagetaken_before">Image Taken Before:</label>
            <input type="date" id="imagetaken_before" name="imagetaken_before" value="<?php echo htmlspecialchars(isset($form_data['imagetaken_before']) ? $form_data['imagetaken_before'] : ''); ?>">
        </div>
    </div>

    <input type="submit" value="Export CSV">

</form>

</body>
</html>
