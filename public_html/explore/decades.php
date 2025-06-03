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
	// $smarty->display('_std_begin.tpl'); // Handled by explore_decades.tpl

	//$db = GeographDatabaseConnection(true);
	$sph = GeographSphinxConnection('sphinxql',true);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$links = array('/explore/quick.php'=>'Topics','/finder/recent.php'=>'Recent', '/explore/decades.php'=>'Decades', 
	'/stuff/viewgaz4.php' => 'Great Britain','/stuff/viewgaz3.php' => 'Ireland', '/stuff/viewgaz5.php' => 'Isle of Man', '/search.php'=>'Search', '/mapper/combined.php'=>'Map',
	'/browser/' => 'Advanced Browser','/content/explore.php'=>'Collections');
$smarty->assign('links_main_tabs', $links);

// Title is now in explore_decades.tpl
// print '<div class="interestBox">';
// print "<h2>Explore Images over time</h2>";
// print "</div>";

#######################################
$where = $match = array(); // Keep for potential future filtering, not used in current query

if (true) { // Main data fetching block

    if (!empty($match)) { // Keep for potential future filtering
        $where[] = "MATCH(".$sph->Quote(implode(' ',$match)).")";
    }

    $thumbw = 120;
    $thumbh = 120;
    $smarty->assign('thumb_width', $thumbw);
    $smarty->assign('thumb_height', $thumbh);

    // The SQL query fetches images grouped by decade and country.
    // It includes 'count(*) as images' which is crucial.
    $sql = "SELECT id,user_id,title,realname,grid_reference,takenyear,decade,country,count(*) as images FROM sample8";
    // if (!empty($where)) $sql .= " WHERE ".implode(' AND ',$where); // If actual filters were to be applied
    $sql .= " GROUP BY decade,country ORDER BY decade DESC, country ASC LIMIT 1000"; // Added default ordering

    $imagelist = new ImageList();
    $imagelist->_setSph($sph);

    // getImagesBySphinxQL creates proper image objects and also stores the 'images' count from count(*)
    $count_of_groups = $imagelist->getImagesBySphinxQL($sql, true);

    $metrix = array();
    $countries_map = array(); // Use a different name to avoid confusion with $image->country
    foreach ($imagelist->images as $i => $image_obj) {
        $decade_val = str_replace('tt','0s',$image_obj->decade);
        if ($decade_val == '0000s' || empty($image_obj->country)) continue; // Skip invalid decades or empty countries

        // $image_obj contains the 'images' property from 'count(*) as images'
        // It also contains other first image details for the group (id, title etc.)
        $metrix[$decade_val][$image_obj->country] = array(
            'image_obj' => $image_obj,         // The full image object from ImageList
            'count'     => $image_obj->images  // The count of images for this decade/country group
        );
        @$countries_map[$image_obj->country]++; // Store unique country names for header
    }

    krsort($metrix); // Sort decades in reverse chronological order (keys of $metrix)
    ksort($countries_map); // Sort country names alphabetically for header

    $smarty->assign('decade_matrix', $metrix);
    $smarty->assign('country_headers', array_keys($countries_map));

    // HTML table generation is now removed and handled by explore_decades.tpl
}

#######################################

$smarty->display('explore_decades.tpl'); // Changed from _std_end.tpl


