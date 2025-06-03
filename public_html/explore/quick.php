<?php
/**
 * $Project: GeoGraph $
 * $Id: conversion.php 5502 2009-05-13 14:18:23Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 BArry Hunter (geo@barryhunter.co.uk)
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

//customExpiresHeader(3600,false,true);

	$smarty->assign('responsive',true);
	// $smarty->display('_std_begin.tpl'); // Handled by explore_quick.tpl

	//$db = GeographDatabaseConnection(true);
	$sph = GeographSphinxConnection('sphinxql',true);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$links = array('/explore/quick.php'=>'Topics','/finder/recent.php'=>'Recent', '/explore/decades.php'=>'Decades',
	'/stuff/viewgaz4.php' => 'Great Britain','/stuff/viewgaz3.php' => 'Ireland', '/stuff/viewgaz5.php' => 'Isle of Man', '/search.php'=>'Search', '/mapper/combined.php'=>'Map',
	'/browser/' => 'Advanced Browser','/content/explore.php'=>'Collections');
$smarty->assign('links_main_tabs', $links);

// Title is now in explore_quick.tpl
// print '<div class="interestBox">';
// print "<h2>Explore Images</h2>";
// print "</div>";

$lists = array(
	'subjects' => array('title'=>'Subjects',            'column'=>'subjects','group'=>'subject_ids'),
	'contexts' => array('title'=>'Geographical Context','column'=>'contexts','group'=>'context_ids'),
	'tags' =>     array('title'=>'Tags',                'column'=>'tags','group'=>'tag_ids'),
	'types' =>     array('title'=>'Types',              'column'=>'types','group'=>'type_ids'),
	'countries' => array('title'=>'Countries',          'column'=>'country','group'=>'country'),
	'user' =>      array('title'=>'Photographer',       'column'=>'realname','group'=>'user_id'),
	'landcover' => array('title'=>'Landcover',          'column'=>'landcover','group'=>'landcover'),
);
$smarty->assign('filter_lists', $lists);

$where = $match = $browser = array();

#######################################
//the main form
// Form and List select are now in explore_quick.tpl
// print "<form style=\"background-color:#eee;padding:10px\">\n\n";
// print "List: <select name=\"list\" onchange=\"this.form.submit()\">";
// print "<option></option>";
// foreach ($lists as $name => $data) {
// printf('<option value="%s"%s>%s</option>', $name, ($name == $_GET['list']??'')?' selected':'', $data['title']);
// }
// print "</select>\n\n";

#######################################
//can show a list specific dropdown (well 1000 items, its not a full search/autocomplete dropdown)

// This block is now for data preparation. The actual list ($list) fetching will be handled later.
if (!empty($_GET['list']) && isset($lists[$_GET['list']])) {
    $selected_list_name = $_GET['list'];
    $selected_list_details = $lists[$selected_list_name];
    $smarty->assign('selected_list_name', $selected_list_name);
    $smarty->assign('selected_list_details', $selected_list_details);

    // Fetch items for the selected list type (for its own dropdown or for the topic list)
    // This fetch uses a blank $where and $match at this stage,
    // as it's for populating the dropdown itself, or the general topic list.
    // Specific filtering for results happens later.
    $temp_where_for_list = array();
    $temp_match_for_list = array();
    $order = 'count DESC';
    $list_items_for_dropdown_or_topic = get_list_mva(
        $temp_where_for_list,
        $temp_match_for_list,
        $selected_list_details['group'],
        $selected_list_details['column'],
        $order
    );

    if (!empty($_GET['alpha'])) {
        if (!function_exists('cmp')) {
            function cmp($a, $b) {
                return strcasecmp($a['groupby'],$b['groupby']);
            }
        }
        uasort($list_items_for_dropdown_or_topic, 'cmp');
    }
    $smarty->assign('selected_list_items', $list_items_for_dropdown_or_topic);

    // If a specific item from this list IS selected (e.g., $_GET['subjects']='castles'),
    // add it to $match and $browser arrays for the main search query.
    if (!empty($_GET[$selected_list_name])) {
        $item_value = $_GET[$selected_list_name];
        $sphinx_column_name = $selected_list_details['column']; // Or sometimes $selected_list_details['group'] depending on sphinx schema
        // For MVA groups like subject_ids, the actual filter is on the group field, not the text column.
        // However, the text value is used for display and sometimes for direct sphinx @field searching if indexed.
        // The original code used $s['column'] for the @field part of $match.
        $match[] = "@{$sphinx_column_name} " . $sph->Quote($item_value);
        $browser[] = urlencode2("{$selected_list_details['column']} \"" . $item_value . "\"");
    }
}

#######################################
// Populate $match and $browser based on other GET parameters (country, county)
if (!empty($_GET['country'])) {
    $match[] = "@country " . $sph->Quote($_GET['country']);
    $browser[] = urlencode2("country \"" . $_GET['country'] . "\"");
}

if (!empty($_GET['country']) && !empty($_GET['county'])) { // County implies country
    $match[] = "@county " . $sph->Quote($_GET['county']);
    $browser[] = urlencode2("county \"" . $_GET['county'] . "\"");
}

//more filters
//The get_list calls for country_list and county_list are kept from Part 1,
//they use the $where and $match arrays which might now be populated by the above.
//This is a bit circular if $match was empty before, but get_list adds a default match if empty.
//For consistency, it might be better if these get_list calls also used temporary empty where/match
//if they are meant to populate dropdowns independent of other active filters.
//However, original code implies they ARE filtered by existing $match.

if (true) { // Always show Country dropdown
    $countries = get_list(array(), array(), 'country', 'country ASC'); // Fetch all countries for dropdown
    $smarty->assign('country_list', $countries);
}

if (!empty($_GET['country'])) {
    // Fetch counties for the selected country, independent of other $match filters for the dropdown itself
    $county_match_for_dropdown = array("@country " . $sph->Quote($_GET['country']));
    $counties = get_list(array(), $county_match_for_dropdown, 'county', 'county ASC');
    $smarty->assign('county_list', $counties);
}


#######################################
// Determine if filters are active for main content display
$filters_active = (!empty($where) || !empty($match));
$smarty->assign('filters_active', $filters_active);


if ($filters_active) {
    // general results + setup
    if (!empty($match)) {
        $where[] = "MATCH(" . $sph->Quote(implode(' ', $match)) . ")";
    }

    $thumbw = 213;
    $thumbh = 160;
    $smarty->assign('thumb_width', $thumbw);
    $smarty->assign('thumb_height', $thumbh);

    $pgsize = 30;
    $pg = $_GET['pg'] ?? 1; // Assume pagination might be added

    $sql = "SELECT id,title,realname,user_id,takendays,tags,grid_reference,hash FROM sample8 WHERE " . implode(' AND ', $where);
    if (($_GET['sort'] ?? '') == 'recent') {
        $sql .= " ORDER BY id DESC";
    }
    // Add other sort options if any
    $sql .= sprintf(" LIMIT %d,%d", ($pg - 1) * $pgsize, $pgsize);

    $imagelist = new ImageList();
    $imagelist->_setSph($sph);
    $count = $imagelist->getImagesBySphinxQL($sql, true);

    $smarty->assign('image_list', $imagelist->images);
    $smarty->assign('image_count', $count); // $count is number of images on current page
    $smarty->assign('total_image_count', $imagelist->resultCount);

    // Secondary Tab Navigation (links_results_tabs)
    $browser_base_url_path = '/browser/#!/' . implode('/', $browser); // $browser should be populated correctly now
    $smarty->assign('browser_base_url', $browser_base_url_path);

    // Build query string for current page, removing sort for "Preview" link
    $current_query_params = $_GET;
    unset($current_query_params['sort']);
    $preview_link_query = http_build_query($current_query_params);
    if ($preview_link_query) $preview_link_query = '?'.$preview_link_query;


    $links_results_tabs = array(
        '/explore/quick.php' . $preview_link_query => 'Preview',
        smarty_function_linktoself(array('name' => 'sort', 'value' => 'recent')) => 'Recent', // function generates full URL with other params
        '/search.php' . $preview_link_query => 'Search', // Basic search link, might need more specific params
        $browser_base_url_path . "/display=map" => 'Map',
        $browser_base_url_path => 'Advanced Browser'
    );
    $smarty->assign('links_results_tabs', $links_results_tabs);

    // Footer Links (links_footer)
    $links_footer_temp = $links_results_tabs; // Start with the same links
    unset($links_footer_temp['/explore/quick.php' . $preview_link_query]); // Remove preview from footer

    $links_footer_array_for_smarty = array();
    foreach($links_footer_temp as $lf_link => $lf_name) {
        $links_footer_array_for_smarty[] = array('link' => $lf_link, 'name' => $lf_name);
    }
    $links_footer_array_for_smarty[] = array('link' => $browser_base_url_path."/display=group/group=country/n=6/gorder=alpha%20asc", 'name' => 'By Country');

    if (!empty($_GET['subjects'])) {
         $links_footer_array_for_smarty[] = array('link' => "/stuff/tagmap.php?tag=".urlencode($_GET['subjects']), 'name' => "Tag Map");
    }
    $smarty->assign('links_footer', $links_footer_array_for_smarty);


    // "Not enough results?" Section Data
    if (!empty($_GET['subjects'])) {
        $smarty->assign('not_enough_results_link', "/browser/#!/q=" . urlencode2('"' . $_GET['subjects'] . '"'));
        // Ensure $sph is available
        $keywords_data = $sph->getAssoc("CALL KEYWORDS(" . $sph->Quote($_GET['subjects']) . ", 'sample8', 1)");
        $keyword_doc_counts = array();
        foreach ($keywords_data as $row_keyword) {
            $keyword_doc_counts[] = $row_keyword['docs'];
        }
        $estimate = empty($keyword_doc_counts) ? 0 : array_sum($keyword_doc_counts) / count($keyword_doc_counts);
        $smarty->assign('not_enough_results_estimate', number_format($estimate, 0));
    }
    // All print statements removed from this block

} elseif (!empty($selected_list_items)) { // Display list of topics if filters not active but a list type was chosen
    // $list variable from original code is now $selected_list_items, already assigned

    // Sorting Tab Navigation (links_sort_topic_list)
    $current_params_for_sort_tabs = $_GET; // Current GET params
    $base_query_for_sort_tabs = array('list' => $current_params_for_sort_tabs['list']); // Only keep 'list' param for base

    $links_sort_topic_list = array();
    // For 'Images' (alpha=0 or no alpha)
    $params_images = $base_query_for_sort_tabs; // No alpha needed, or alpha=0
    // $links_sort_topic_list[smarty_function_linktoself(array('name'=>'alpha','value'=>0,'extras'=>http_build_query($params_images)))] = 'Images';
    $links_sort_topic_list['?' . http_build_query($params_images)] = 'Images';


    // For 'Alpha' (alpha=1)
    $params_alpha = $base_query_for_sort_tabs;
    $params_alpha['alpha'] = 1;
    // $links_sort_topic_list[smarty_function_linktoself(array('name'=>'alpha','value'=>1,'extras'=>http_build_query($params_alpha)))] = 'Alpha';
    $links_sort_topic_list['?' . http_build_query($params_alpha)] = 'Alpha';

    $smarty->assign('links_sort_topic_list', $links_sort_topic_list);


    // Official Items (official_items)
    $official_items = array();
    if (isset($selected_list_name)) { // Check if $selected_list_name is set
        if ($selected_list_name == 'subjects') {
            if (empty($db)) $db = GeographDatabaseConnection(true);
            $official_items = $db->getAssoc("SELECT LOWER(subject),1 from subjects");
        } elseif ($selected_list_name == 'contexts') {
            if (empty($db)) $db = GeographDatabaseConnection(true);
            $official_items = $db->getAssoc("SELECT LOWER(top),1 FROM category_primary");
        }
    }
    $smarty->assign('official_items', $official_items);

    // List Display Style (list_display_style)
    $list_display_style = '';
    if (isset($selected_list_items)) { // Check if list exists
        if (count($selected_list_items) > 52) {
            $list_display_style = "columns: auto 18em;";
        } elseif (count($selected_list_items) > 36) {
            $list_display_style = "columns: auto 40em;";
        }
        // else, no specific style, template default or no columns
    }
    $smarty->assign('list_display_style', $list_display_style);
    // All print statements removed from this block
}

#######################################

$smarty->display('explore_quick.tpl'); // Changed from _std_end.tpl


#######################################
// functions

function get_list_mva($where,$match,$group,$column,$order = 'count DESC') {
	global $sph;

	if (!empty($match))
		$where[] = "MATCH(".$sph->Quote(implode(' ',$match)).")";
	elseif (empty($where))
		$where[] = "MATCH('@country Ireland')"; //somethign!

	$where = implode(' AND ',$where);

//print $where;

	$list = $sph->getAll("SELECT GROUPBY() as groupby,{$group},{$column},COUNT(*) AS count FROM sample8 WHERE $where GROUP BY {$group} ORDER BY $order LIMIT 1000");
	if (!empty($list) && preg_match('/_ids$/',$group)) {
		foreach ($list as &$row) {
			$ids = explode(',',$row[$group]);
	                $names = explode('_SEP_',$row[$column]);array_shift($names); //the first is always blank!
			$row[$column] = trim($names[array_search($row['groupby'],$ids)]);
			$row['groupby'] = $row[$column]; //we ALSO set this, just to allow easy resorting of array
		}
	}
	unset($row);
	return $list;
}

function get_list($where,$match,$column,$order = 'count DESC') {
	global $sph;

	if (empty($where) && empty($match))
		$match[] = 'Ireland Geograph';// just to have somehting?

	if (!empty($match))
		$where[] = "MATCH(".$sph->Quote(implode(' ',$match)).")";


	$where = implode(' AND ',$where);

//print "SELECT $column,COUNT(*) as count FROM sample8 WHERE $where GROUP BY $column ORDER BY $order";
		//this is simple query as not using MVAs
	$list = $sph->getAll("SELECT $column,COUNT(*) as count FROM sample8 WHERE $where GROUP BY $column ORDER BY $order");

	return $list;
}
