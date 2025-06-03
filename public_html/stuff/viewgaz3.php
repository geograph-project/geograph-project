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
$reference_index = 2; // Default for Ireland

// $smarty->display('_std_begin.tpl'); // Handled by stuff_viewgaz3.tpl

$links = array('viewgaz4.php' => 'Great Britain','viewgaz3.php' => 'Ireland', 'viewgaz5.php' => 'Isle of Man', '/finder/places.php'=>'Search', '/mapper/combined.php'=>'Map');
$smarty->assign('nav_links', $links);

##################################################
// Name switcher logic and SQL column determination
$current_name_preference = $_GET['name'] ?? 'english';
if (!in_array($current_name_preference, ['english', 'irish', 'enie', 'ieen'])) {
    $current_name_preference = 'english';
}
$smarty->assign('current_name_preference', $current_name_preference);

$name_switcher_options = ['english'=>'English', 'irish'=>'Irish', 'enie'=>'English/Irish', 'ieen'=>'Irish/English'];
$smarty->assign('name_switcher_options', $name_switcher_options);

$switcher_params = $_GET;
unset($switcher_params['name']);
$smarty->assign('name_switcher_base_url', "?" . http_build_query($switcher_params));

// Determine $column for SQL based on $current_name_preference
// This $column variable will be used in SQL queries within each mode.
$column_sql_select = 'name'; // Default to 'name' which is typically English
if ($current_name_preference == 'irish') {
    $column_sql_select = "IF(irish='', name, irish)";
} elseif ($current_name_preference == 'enie') {
    $column_sql_select = "IF(irish='', name, CONCAT(name, ' / ', irish))";
} elseif ($current_name_preference == 'ieen') {
    $column_sql_select = "IF(irish='', name, CONCAT(irish, ' / ', name))";
}
// Note: The alias 'display_name' will be used in SQL queries for this selected column.

##################################################

$ni = 0; // Flag for showing Northern Ireland specific note
$page_title_str = ''; // To be built based on mode

if (!empty($_GET['alpha'])) {
    /*******************************************************
     * MODE 1: Alpha Filter (Places starting with letter)
     *******************************************************/
    $smarty->assign('display_mode', 'alpha_filter');
    $smarty->assign('show_name_switcher', true);

    $alpha_char = htmlentities($_GET['alpha']);
    $page_title_str = "Places beginning with " . $alpha_char;

    $where = array();
    $where[] = "(name LIKE ".$db->Quote($alpha_char."%")." OR irish LIKE ".$db->Quote($alpha_char."%").")";
    $where_clause = implode(" AND ", $where);

    $sql = "SELECT name, $column_sql_select AS display_name, town_class, images, recent, e, n, country, county
            FROM ie_open_places
            WHERE $where_clause
            ORDER BY country DESC, county, display_name";
    $data = $db->getAll($sql);

    $places_list_for_smarty = array();
    foreach($data as $row) {
        list($gridref,) = $conv->national_to_gridref($row['e'], $row['n'], null, $reference_index);

        $display_name_processed = '';
        // Original logic: if name is all caps or non-alpha, title case it, else use utf8_to_latin1
        if (!preg_match('/[a-z]/', $row['name'])) {
            $display_name_processed = to_title_case(strtolower($row['display_name']));
        } else {
            $display_name_processed = utf8_to_latin1($row['display_name']);
        }

        $is_major = false;
        if (isset($row['town_class']) && preg_match('/(\d+)/', $row['town_class'], $m) && $m[1] <= 3) {
            $is_major = true;
        }

        $places_list_for_smarty[] = array(
            'county' => $row['county'],
            'country' => $row['country'],
            'is_major_town' => $is_major,
            'url' => "/near/" . urlencode2($row['name']) . "/$gridref?dist=2000",
            'display_name' => $display_name_processed,
            'image_count' => $row['images'] ?? 0,
            'recent_year' => (!empty($row['recent']) && $row['recent'] > '1000') ? substr($row['recent'], 0, 4) : null,
        );
        if (!empty($row['country']) && $row['country'] == 'Northern Ireland') $ni = 1;
    }
    $smarty->assign('places_list', $places_list_for_smarty);

} elseif (!empty($_GET['county'])) {
    /*******************************************************
     * MODE 2: County Filter (Places within a county)
     *******************************************************/
    $smarty->assign('display_mode', 'county_filter');
    $smarty->assign('show_name_switcher', true);

    $county_name_param = $_GET['county'];
    $page_title_str_base = to_title_case(strtolower($county_name_param));

    $where = array();
    $where[] = "county = ".$db->Quote($county_name_param);

    if (!empty($_GET['island'])) {
        if ($_GET['island'] == 'mainland') {
            $where[] = "island_name = ''";
        } else {
            $where[] = "island_name = ".$db->Quote($_GET['island']);
        }
        $page_title_str_base .= " (" . to_title_case(str_replace('_',' ',$_GET['island'])) . ")";
    }
    $page_title_str = "Places in " . $page_title_str_base;

    $where_clause = implode(" AND ", $where);
    $sql = "SELECT name, $column_sql_select AS display_name, town_class, images, recent, e, n, country, county
            FROM ie_open_places
            WHERE $where_clause
            ORDER BY display_name";
    $data = $db->getAll($sql);

    $county_note_str = '';
    foreach($data as $row_check) {
        if (empty($row_check['images'])) {
            $county_note_str = "Note: even if no images are listed, can still click to view nearby images";
            break;
        }
    }
    $smarty->assign('county_note', $county_note_str);

    $places_list_for_smarty = array();
    foreach($data as $row) {
        list($gridref,) = $conv->national_to_gridref($row['e'], $row['n'], null, $reference_index);
        $url = "/near/" . urlencode2($row['name']) . "/$gridref?dist=2000";
        if (empty($row['images'])) {
            $url .= "0"; // Extend distance if no images for the place itself
        }

        $display_name_processed = '';
        if (!preg_match('/[a-z]/', $row['name'])) {
            $display_name_processed = to_title_case(strtolower($row['display_name']));
        } else {
            $display_name_processed = utf8_to_latin1($row['display_name']);
        }

        $is_major = false;
        if (isset($row['town_class']) && preg_match('/(\d+)/', $row['town_class'], $m) && $m[1] <= 3) {
            $is_major = true;
        }

        $places_list_for_smarty[] = array(
            // county and country are implicitly the one filtered by, but good to have if data structure is mixed
            'county' => $row['county'] ?? $county_name_param,
            'country' => $row['country'] ?? '',
            'is_major_town' => $is_major,
            'url' => $url,
            'display_name' => $display_name_processed,
            'image_count' => $row['images'] ?? 0,
            'recent_year' => (!empty($row['recent']) && $row['recent'] > '1000') ? substr($row['recent'], 0, 4) : null,
        );
        if (!empty($row['country']) && $row['country'] == 'Northern Ireland') $ni = 1;
    }
    $smarty->assign('places_list', $places_list_for_smarty);

} else {
    /*******************************************************
     * MODE 3: Default (List all counties)
     *******************************************************/
    $smarty->assign('display_mode', 'list_counties');
    $smarty->assign('show_name_switcher', false); // No name switcher on the main county list page
    $page_title_str = "Populated Place Directory for Ireland";

    $sql = "SELECT country, county, island_name, name, e, n, COUNT(*) AS places, SUM(images) AS images, SUM(images>0)/COUNT(*)*100 AS percent
            FROM ie_open_places
            GROUP BY country, county, island_name
            ORDER BY country DESC, county, island_name"; // Added island_name to GROUP BY and ORDER BY
    $data = $db->getAll($sql);

    $islands = array(); // To track counties that have associated islands
    foreach($data as $row) {
        if (!empty($row['island_name'])) {
            $islands[$row['county']] = 1;
        }
    }

    $counties_list_for_smarty = array();
    foreach($data as $row) {
        $county_display_name = to_title_case(strtolower($row['county']));
        $url = '';

        // If it's a mainland part of a county that also has islands, mark it as "mainland"
        $current_island_name = $row['island_name'];
        if (empty($current_island_name) && !empty($islands[$row['county']])) {
             $current_island_name = "mainland";
        }

        if (($row['places'] ?? 0) === 1 || ($row['places'] ?? 0) === '1') { // If only one place, link directly to it
            list($gridref,) = $conv->national_to_gridref($row['e'], $row['n'], null, $reference_index);
            $url = "/near/" . urlencode2($row['name']) . "/$gridref?dist=2000";
        } else { // Multiple places, link to county filter
            $url = "?county=" . urlencode($row['county']);
        }

        if (!empty($current_island_name)) { // If it's an island or explicitly mainland part of islanded county
            $url .= "&island=" . urlencode($current_island_name); // Use $current_island_name for URL
            $county_display_name .= " (" . to_title_case(str_replace('_', ' ', $current_island_name)) . ")";
        }

        $counties_list_for_smarty[] = array(
            'country' => $row['country'],
            'url' => $url,
            'display_name' => $county_display_name,
            'places' => $row['places'] ?? 0,
            'percent_photographed' => (isset($row['percent']) && $row['percent'] < 100 && $row['percent'] >=0) ? floatval(round($row['percent'],1)) : null, // ensure percent is not negative
            'image_count' => $row['images'] ?? 0,
        );
        if (!empty($row['country']) && $row['country'] == 'Northern Ireland') $ni = 1;
    }
    $smarty->assign('counties_list', $counties_list_for_smarty);
    $smarty->assign('alphabet_range', range('A', 'Z'));
}

$smarty->assign('page_title', $page_title_str);
$smarty->assign('show_ni_note', (bool)$ni);

$smarty->display('stuff_viewgaz3.tpl');
?>
