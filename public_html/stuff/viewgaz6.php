<?php
/**
 * $Project: GeoGraph $
 * $Id: viewps.php 2295 2006-07-05 12:15:49Z barryhunter $
 *
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Barry Hunter (geo@barryhunter.co.uk)
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
init_session();

$smarty = new GeographPage;
$db = GeographDatabaseConnection(false);
$conv = new Conversions;
$reference_index = 1; // Important for IoM conversions

// $smarty->display('_std_begin.tpl'); // Handled by stuff_viewgaz6.tpl

$links = array('viewgaz4.php' => 'Great Britain','viewgaz3.php' => 'Ireland', 'viewgaz6.php' => 'Isle of Man', '/finder/places.php'=>'Search', '/mapper/combined.php'=>'Map');
$smarty->assign('nav_links', $links);

##################################################

$where = array();
$name_for_title_arr = array();
$name_for_title_arr[] = "Isle of Man"; // Base part of the title

if (!empty($_GET['alpha'])) {
    $name_for_title_arr[] = htmlentities($_GET['alpha']); // Add letter to title if present
    $where[] = "name LIKE ".$db->Quote($_GET['alpha']."%");
}
$smarty->assign('page_title', "Places in " . implode(", ", $name_for_title_arr));

$page_note_str = '';
if (!empty($_GET['alpha'])) {
    $where[] = "class != 'boundary'"; // Filter condition
    $page_note_str = "Note: the count of images is just images nearby, havent checked there is specifically a photo of the feature!";
} else {
    $where[] = "class = 'place'"; // Default filter condition
    $page_note_str = "Note: This is only listing City, Town and Villages, not other features";
}
$smarty->assign('page_note', $page_note_str);

$where_clause = implode(" AND ", $where);
// Ensure 'postcode' is selected for grouping. Added explicit ORDER BY.
$sql = "SELECT name, osm_id, type, lon, lat, place_rank, images, recent, postcode
        FROM planet_im
        WHERE $where_clause
        GROUP BY name, postcode, round(lat,1), round(lon,1)
        ORDER BY postcode ASC, name ASC";
$data = $db->getAll($sql);

$places_data_for_smarty = array();
foreach($data as $row) {
    // Perform conversions
    list($e, $n, $current_ref_idx) = $conv->wgs84_to_national($row['lat'], $row['lon'], true); // true for IoM
    $reference_index = $current_ref_idx; // Update reference_index as it might change for IoM
    list($gridref,) = $conv->national_to_gridref($e, $n, null, $reference_index);

    $place_item = $row; // Start with all columns from DB row
    $place_item['name_latin1'] = utf8_to_latin1($row['name']); // Convert name
    $place_item['grid_reference'] = $gridref; // Add grid_reference
    $place_item['url_near'] = "/near/" . urlencode2($row['name']) . "/$gridref?dist=2000"; // Create URL
    $place_item['postcode'] = $row['postcode'] ?? ''; // Ensure postcode key exists, default to empty

    $places_data_for_smarty[] = $place_item;
}
$smarty->assign('places_data', $places_data_for_smarty);

// Alphabetical Links Footer Data
$extra_params_for_alpha_links = array();
// If any other GET parameters (besides 'alpha') should be preserved in the A-Z links,
// they would be added to $extra_params_for_alpha_links here.
// For example:
// if (!empty($_GET['region'])) {
//     $extra_params_for_alpha_links['region'] = $_GET['region'];
// }
$alpha_footer_url_base_str = "?" . http_build_query($extra_params_for_alpha_links);
// If $extra_params_for_alpha_links is empty, http_build_query will return an empty string,
// so $alpha_footer_url_base_str will be just "?". This is fine.

$smarty->assign('alphabet_range', range('A', 'Z'));
$smarty->assign('alpha_footer_url_base', $alpha_footer_url_base_str);

##################################################

$smarty->display('stuff_viewgaz6.tpl'); // Display the new Smarty template
?>
