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

// Handles CSV export from gridimage_search table
require_once('geograph/global.inc.php');

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Should not happen if form is used, but good to check
    $_SESSION['export_errors'] = ['Invalid request method. Please use the form.'];
    header("Location: export_gridimage_search_form.php");
    exit;
}

// Retrieve form data (trimming whitespace from string inputs)
$columns = isset($_POST['columns']) ? $_POST['columns'] : [];
$delimiter = isset($_POST['delimiter']) ? trim($_POST['delimiter']) : ',';
$quote_char = isset($_POST['quote_char']) ? trim($_POST['quote_char']) : '"';
// $escape_char is not used in standard CSV generation, but we retrieve it if needed for other logic
$escape_char = isset($_POST['escape_char']) ? trim($_POST['escape_char']) : '\\'; 

$min_lat_str = isset($_POST['min_lat']) ? trim($_POST['min_lat']) : '';
$min_lon_str = isset($_POST['min_lon']) ? trim($_POST['min_lon']) : '';
$max_lat_str = isset($_POST['max_lat']) ? trim($_POST['max_lat']) : '';
$max_lon_str = isset($_POST['max_lon']) ? trim($_POST['max_lon']) : '';

$moderation_status = isset($_POST['moderation_status']) ? trim($_POST['moderation_status']) : '';
$imageclass = isset($_POST['imageclass']) ? trim($_POST['imageclass']) : '';
$user_id_str = isset($_POST['user_id']) ? trim($_POST['user_id']) : '';

$submitted_after_str = isset($_POST['submitted_after']) ? trim($_POST['submitted_after']) : '';
$submitted_before_str = isset($_POST['submitted_before']) ? trim($_POST['submitted_before']) : '';

$imagetaken_after_str = isset($_POST['imagetaken_after']) ? trim($_POST['imagetaken_after']) : '';
$imagetaken_before_str = isset($_POST['imagetaken_before']) ? trim($_POST['imagetaken_before']) : '';

$errors = [];

// --- Input Validation ---

// 1. Selected Columns
if (empty($columns) || !is_array($columns)) {
    $errors[] = "You must select at least one column to export.";
}

// 2. CSV Delimiter/Quote
if (strlen($delimiter) !== 1) {
    $errors[] = "Delimiter must be a single character.";
    $delimiter = ','; // Default back
}
if (strlen($quote_char) !== 1) {
    $errors[] = "Quote Character must be a single character.";
    $quote_char = '"'; // Default back
}

// 3. Bounding Box
$bbox_fields = [$min_lat_str, $min_lon_str, $max_lat_str, $max_lon_str];
$bbox_provided_count = 0;
foreach($bbox_fields as $field) {
    if ($field !== '') {
        $bbox_provided_count++;
    }
}

if ($bbox_provided_count > 0 && $bbox_provided_count < 4) {
    $errors[] = "For Bounding Box filter, all four coordinates (Min/Max Latitude and Longitude) must be provided.";
} elseif ($bbox_provided_count === 4) {
    if (!is_numeric($min_lat_str)) $errors[] = "Min Latitude must be a numeric value.";
    if (!is_numeric($min_lon_str)) $errors[] = "Min Longitude must be a numeric value.";
    if (!is_numeric($max_lat_str)) $errors[] = "Max Latitude must be a numeric value.";
    if (!is_numeric($max_lon_str)) $errors[] = "Max Longitude must be a numeric value.";

    if (empty($errors)) { // Proceed to range checks only if numeric
        $min_lat = floatval($min_lat_str);
        $min_lon = floatval($min_lon_str);
        $max_lat = floatval($max_lat_str);
        $max_lon = floatval($max_lon_str);

        if ($min_lat >= $max_lat) $errors[] = "Min Latitude must be less than Max Latitude.";
        if ($min_lon >= $max_lon) $errors[] = "Min Longitude must be less than Max Longitude.";
        if ($min_lat < -90 || $min_lat > 90) $errors[] = "Min Latitude must be between -90 and 90.";
        if ($max_lat < -90 || $max_lat > 90) $errors[] = "Max Latitude must be between -90 and 90.";
        if ($min_lon < -180 || $min_lon > 180) $errors[] = "Min Longitude must be between -180 and 180.";
        if ($max_lon < -180 || $max_lon > 180) $errors[] = "Max Longitude must be between -180 and 180.";
    }
}

// 4. User ID
$user_id = null;
if ($user_id_str !== '') {
    if (!is_numeric($user_id_str)) {
        $errors[] = "User ID must be a numeric value.";
    } else {
        $user_id = intval($user_id_str);
    }
}

// 5. Date Fields
function validate_date_format($date_str, $field_name, &$errors_array) {
    if ($date_str === '') return null; // Not provided, not an error
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_str)) {
        $errors_array[] = "{$field_name} must be in YYYY-MM-DD format.";
        return false;
    }
    $d = DateTime::createFromFormat('Y-m-d', $date_str);
    if ($d && $d->format('Y-m-d') === $date_str) {
        return $date_str;
    }
    $errors_array[] = "{$field_name} is not a valid date.";
    return false;
}

$submitted_after = validate_date_format($submitted_after_str, "Submitted After date", $errors);
$submitted_before = validate_date_format($submitted_before_str, "Submitted Before date", $errors);
$imagetaken_after = validate_date_format($imagetaken_after_str, "Image Taken After date", $errors);
$imagetaken_before = validate_date_format($imagetaken_before_str, "Image Taken Before date", $errors);


// --- Error Handling Flow ---
if (!empty($errors)) {
    $_SESSION['export_errors'] = $errors;
    $_SESSION['export_form_data'] = $_POST; // Store submitted data
    header("Location: export_gridimage_search_form.php");
    exit;
}

// Define valid columns for gridimage_search table (moved after validation)
$all_valid_columns = [
    'gridimage_id', 'user_id', 'moderation_status', 'title', 'title2',
    'submitted', 'imageclass', 'imagetaken', 'upd_timestamp', 'x', 'y',
    'grid_reference', 'credit_realname', 'realname', 'reference_index',
    'comment', 'comment2', 'wgs84_lat', 'wgs84_long', 'ftf', 'seq_no',
    'point_xy', 'point_ll'
];

// Validate and sanitize selected columns (now that $errors is empty)
$selected_columns_sql = []; // Renamed to avoid conflict with $columns from POST
foreach ($columns as $col) { // $columns is from $_POST['columns']
    if (in_array($col, $all_valid_columns)) {
        if ($col === 'point_ll') {
            $selected_columns_sql[] = "ST_AsText(point_ll) AS point_ll_text";
        } elseif ($col === 'point_xy') {
            $selected_columns_sql[] = "ST_AsText(point_xy) AS point_xy_text";
        } else {
            $selected_columns_sql[] = $col;
        }
    } else {
        // This case should ideally not be reached if form provides valid options
        // Or if it is, it means invalid column name was submitted, could add to $errors
        // For now, we just ignore invalid ones passed.
    }
}

if (empty($selected_columns_sql)) {
    // This should have been caught by the $errors check for empty $columns,
    // but as a fallback if $columns had only invalid names:
    $_SESSION['export_errors'] = ["No valid columns were selected for export."];
    $_SESSION['export_form_data'] = $_POST;
    header("Location: export_gridimage_search_form.php");
    exit;
}


// --- Database Query Construction ---
global $db; // Assuming $db is initialized in global.inc.php

$sql_select = implode(', ', $selected_columns_sql);
$sql_from = "gridimage_search";
$where_conditions = [];

// Add filter conditions (using validated variables)
if ($bbox_provided_count === 4 && isset($min_lon, $min_lat, $max_lon, $max_lat)) { // Check if BBOX coords are set
    $bbox_wkt = sprintf("POLYGON((%f %f, %f %f, %f %f, %f %f, %f %f))", $min_lon, $min_lat, $max_lon, $min_lat, $max_lon, $max_lat, $min_lon, $max_lat, $min_lon, $min_lat);
    $where_conditions[] = "ST_Contains(ST_GeomFromText(" . $db->qstr($bbox_wkt) . "), point_ll)";
}

if (!empty($moderation_status) && $moderation_status !== 'Any') {
    $where_conditions[] = "moderation_status = " . $db->qstr($moderation_status);
}

if (!empty($imageclass)) {
    $where_conditions[] = "imageclass = " . $db->qstr($imageclass);
}

if ($user_id !== null) { // Use the validated numeric user_id
    $where_conditions[] = "user_id = " . $user_id;
}

if ($submitted_after) { // Use validated date
    $where_conditions[] = "submitted >= " . $db->qstr($submitted_after . " 00:00:00");
}
if ($submitted_before) { // Use validated date
    $where_conditions[] = "submitted <= " . $db->qstr($submitted_before . " 23:59:59");
}

if ($imagetaken_after) { // Use validated date
    $where_conditions[] = "imagetaken >= " . $db->qstr($imagetaken_after . " 00:00:00");
}
if ($imagetaken_before) { // Use validated date
    $where_conditions[] = "imagetaken <= " . $db->qstr($imagetaken_before . " 23:59:59");
}

$sql_where = "";
if (!empty($where_conditions)) {
    $sql_where = " WHERE " . implode(" AND ", $where_conditions);
}

$sql = "SELECT " . $sql_select . " FROM " . $sql_from . $sql_where;

// Execute the query
$recordSet = $db->Execute($sql);

if (!$recordSet) {
    // Database operation error before headers are sent
    $_SESSION['export_errors'] = ["Database query failed: " . htmlspecialchars($db->ErrorMsg())];
    $_SESSION['export_form_data'] = $_POST;
    header("Location: export_gridimage_search_form.php");
    exit;
}

if ($recordSet->EOF) {
    // No records found
    $_SESSION['export_errors'] = ["No records found matching your criteria."];
    $_SESSION['export_form_data'] = $_POST;
    header("Location: export_gridimage_search_form.php");
    exit;
}


// Helper function for CSV field formatting
function format_csv_field($value, $delimiter_char, $quote_char_val) { // Renamed params to avoid conflict
    $value = (string) $value;
    $needs_quoting = false;

    if (strpos($value, $delimiter_char) !== false ||
        strpos($value, $quote_char_val) !== false ||
        strpos($value, "\n") !== false ||
        strpos($value, "\r") !== false ||
        strpos($value, "\t") !== false ||
        strpos($value, " ") === 0 ||
        substr($value, -1) === " ") {
        $needs_quoting = true;
    }

    if ($needs_quoting) {
        $escaped_value = str_replace($quote_char_val, $quote_char_val . $quote_char_val, $value);
        return $quote_char_val . $escaped_value . $quote_char_val;
    }
    return $value;
}

// Use the validated delimiter and quote_char from earlier
// $csv_delimiter = $delimiter; (already set and validated)
// $csv_quote_char = $quote_char; (already set and validated)


// Set HTTP Headers for CSV Download
header("Content-type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=\"geograph_export.csv\"");
header("Pragma: no-cache");
header("Expires: 0");

// Output CSV Header Row
$header_output = [];
// Use the actual selected column names/aliases for the header
$csv_header_names = array_map(function($col_expr) {
    if (preg_match('/AS\s+(\w+)$/i', $col_expr, $matches)) {
        return $matches[1]; // Return the alias if present
    }
    return $col_expr; // Otherwise, the column name itself
}, $selected_columns_sql);

foreach ($csv_header_names as $col_name) {
    $header_output[] = format_csv_field($col_name, $delimiter, $quote_char);
}
echo implode($delimiter, $header_output) . "\n";

// Output CSV Data Rows
while (!$recordSet->EOF) {
    $row_output = [];
    $fields = $recordSet->fields;

    foreach ($csv_header_names as $header_name) {
        $value_to_format = isset($fields[$header_name]) ? $fields[$header_name] : '';
        $row_output[] = format_csv_field($value_to_format, $delimiter, $quote_char);
    }
    echo implode($delimiter, $row_output) . "\n";
    $recordSet->MoveNext();
}

$recordSet->Close();

// No further output should exist after this point
exit; // Ensure no accidental output beyond this point
?>
