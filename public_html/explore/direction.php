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

customExpiresHeader(3600,false,true);

	$smarty->assign('responsive',true);
	$smarty->display('_std_begin.tpl');

	//$db = GeographDatabaseConnection(true);
	$sph = GeographSphinxConnection('sphinxql',true);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

// This will be set after processing form inputs
// $title = "Explore Images over time";
$index = "sample8"; //note, can add WHERE ... on the end!

if (!empty($_GET['snippet_id'])) {

                        $db = GeographDatabaseConnection(true);
                        $id = intval($_GET['snippet_id']);
                        if ($row = $db->getRow("SELECT content_id,title FROM content WHERE foreign_id = $id AND source = 'snippet'")) {
				//use content, because, could use content_id= in the browser URL!
				$extra = "/snippets+".urlencode('"'.$row['title'].'"');
                        } else {
				die("The browser can't view images from this particular shared description");
                        }

	$index .= " WHERE snippet_ids = ".intval($_GET['snippet_id']); //works even on a MVA!

} else {
	die("todo - other filter methods not supported yet");
}

// Define available fields for rows and columns
// These could be dynamically fetched from a database or configuration if needed
$available_fields = [
    'takenyear' => 'Year Taken',
    'direction' => 'Direction',
    'category' => 'Category', // Example additional field
    'user_id' => 'User ID'     // Example additional field
];

// Get form inputs or use defaults
$row_field = $_GET['row_field'] ?? 'takenyear';
$row_sort_type = $_GET['row_sort_type'] ?? 'alpha';
$row_sort_dir = $_GET['row_sort_dir'] ?? 'asc';

$col_field = $_GET['col_field'] ?? 'direction';
$col_sort_type = $_GET['col_sort_type'] ?? 'alpha';
$col_sort_dir = $_GET['col_sort_dir'] ?? 'asc';

// Sanitize inputs (basic example, consider more robust validation)
$row_field = array_key_exists($row_field, $available_fields) ? $row_field : 'takenyear';
$col_field = array_key_exists($col_field, $available_fields) ? $col_field : 'direction';

$valid_sort_types = ['alpha', 'numeric', 'images', 'unique'];
$row_sort_type = in_array($row_sort_type, $valid_sort_types) ? $row_sort_type : 'alpha';
$col_sort_type = in_array($col_sort_type, $valid_sort_types) ? $col_sort_type : 'alpha';

$valid_sort_dirs = ['asc', 'desc'];
$row_sort_dir = in_array($row_sort_dir, $valid_sort_dirs) ? $row_sort_dir : 'asc';
$col_sort_dir = in_array($col_sort_dir, $valid_sort_dirs) ? $col_sort_dir : 'asc';


// Use the selected field names
$rowname = $row_field;
$colname = $col_field;

// Generate dynamic title
$title_row_label = $available_fields[$rowname] ?? $rowname;
$title_col_label = $available_fields[$colname] ?? $colname;
$title = "Explore Images: " . htmlspecialchars($title_row_label) . " by " . htmlspecialchars($title_col_label);
if (!empty($_GET['snippet_id'])) {
    // If filtering by snippet, add it to title or as a subtitle
    // For simplicity, appending to main title for now
    // $title .= " (for Snippet ID: " . intval($_GET['snippet_id']) . ")";
}


	print '<div class="interestBox">';
        print "<h2>$title</h2>";

        // Form for selecting row, column, and sort options
        print '<form method="get" action="">';
        print '<input type="hidden" name="snippet_id" value="' . htmlspecialchars($_GET['snippet_id'] ?? '') . '">';

        print '<label for="row_field">Row Field:</label>';
        print '<select name="row_field" id="row_field">';
        foreach ($available_fields as $field_key => $field_label) {
            print '<option value="' . $field_key . '" ' . ($row_field == $field_key ? 'selected' : '') . '>' . htmlspecialchars($field_label) . '</option>';
        }
        print '</select>';

        print '<label for="row_sort_type">Row Sort Type:</label>';
        print '<select name="row_sort_type" id="row_sort_type">';
        print '<option value="alpha" ' . ($row_sort_type == 'alpha' ? 'selected' : '') . '>Alphabetical</option>';
        print '<option value="numeric" ' . ($row_sort_type == 'numeric' ? 'selected' : '') . '>Numeric</option>';
        print '<option value="images" ' . ($row_sort_type == 'images' ? 'selected' : '') . '>Number of Images</option>';
        print '<option value="unique" ' . ($row_sort_type == 'unique' ? 'selected' : '') . '>Number of Unique Values</option>';
        print '</select>';

        print '<label for="row_sort_dir">Row Sort Direction:</label>';
        print '<select name="row_sort_dir" id="row_sort_dir">';
        print '<option value="asc" ' . ($row_sort_dir == 'asc' ? 'selected' : '') . '>Ascending</option>';
        print '<option value="desc" ' . ($row_sort_dir == 'desc' ? 'selected' : '') . '>Descending</option>';
        print '</select>';

        print '<br>';

        print '<label for="col_field">Column Field:</label>';
        print '<select name="col_field" id="col_field">';
        foreach ($available_fields as $field_key => $field_label) {
            print '<option value="' . $field_key . '" ' . ($col_field == $field_key ? 'selected' : '') . '>' . htmlspecialchars($field_label) . '</option>';
        }
        print '</select>';

        print '<label for="col_sort_type">Column Sort Type:</label>';
        print '<select name="col_sort_type" id="col_sort_type">';
        print '<option value="alpha" ' . ($col_sort_type == 'alpha' ? 'selected' : '') . '>Alphabetical</option>';
        print '<option value="numeric" ' . ($col_sort_type == 'numeric' ? 'selected' : '') . '>Numeric</option>';
        print '<option value="images" ' . ($col_sort_type == 'images' ? 'selected' : '') . '>Number of Images</option>';
        print '<option value="unique" ' . ($col_sort_type == 'unique' ? 'selected' : '') . '>Number of Unique Values</option>';
        print '</select>';

        print '<label for="col_sort_dir">Column Sort Direction:</label>';
        print '<select name="col_sort_dir" id="col_sort_dir">';
        print '<option value="asc" ' . ($col_sort_dir == 'asc' ? 'selected' : '') . '>Ascending</option>';
        print '<option value="desc" ' . ($col_sort_dir == 'desc' ? 'selected' : '') . '>Descending</option>';
        print '</select>';

        print '<br>';
        print '<input type="submit" value="Update View">';
        print '</form>';
        print "<hr>"; // Separator before the table

	print "</div>";

#######################################

// Helper function for sorting
// $a and $b are keys from the array being sorted (e.g., row values or column values)
// $sort_type: 'alpha', 'numeric', 'images', 'unique'
// $sort_dir: 'asc', 'desc'
// $data_for_sorting: An associative array where keys are $a or $b, and values are the pre-calculated numbers
//                     for sorting (e.g., total images for that key, or count of unique items for that key).
//                     This is used for 'images' and 'unique' sort types. For 'alpha' and 'numeric',
//                     $a and $b (the keys themselves) are directly compared.
function get_sort_comparison_function($sort_type, $sort_dir, $data_for_sorting = null) {
    return function($a, $b) use ($sort_type, $sort_dir, $data_for_sorting) {
        $val_a = $a; // $a is the key (e.g. '2005' or 'North')
        $val_b = $b; // $b is the key

        if ($sort_type == 'numeric') {
            // Direct numeric comparison of the keys
            $cmp = floatval($val_a) <=> floatval($val_b);
        } elseif ($sort_type == 'images' && $data_for_sorting !== null) {
            // Compare based on precomputed image counts stored in $data_for_sorting
            $count_a = $data_for_sorting[$a] ?? 0;
            $count_b = $data_for_sorting[$b] ?? 0;
            $cmp = $count_a <=> $count_b;
        } elseif ($sort_type == 'unique' && $data_for_sorting !== null) {
            // Compare based on precomputed unique counts stored in $data_for_sorting
            $unique_a = $data_for_sorting[$a] ?? 0;
            $unique_b = $data_for_sorting[$b] ?? 0;
            $cmp = $unique_a <=> $unique_b;
        } else { // 'alpha' / default (also fallback if $data_for_sorting is null for 'images'/'unique')
            // Default to natural string comparison of the keys
            $cmp = strnatcmp($val_a, $val_b);
        }

        return ($sort_dir == 'desc') ? -$cmp : $cmp;
    };
}

$where = $match = array();

	if (true) {

		$thumbw = 120;
                $thumbh = 120;

			$sql = "SELECT id,user_id,title,realname,grid_reference,takenday,$colname,$rowname,count(*) as images FROM $index"; // WHERE ".implode(' and ',$where);
			// Make sure selected fields are in GROUP BY
			$sql .= " GROUP BY $colname,$rowname";
			// Add other available fields if they are not the row/col name, for potential sorting later if not aggregated
			foreach (array_keys($available_fields) as $field) {
				if ($field != $colname && $field != $rowname && property_exists('Image', $field) ) { // Check if field is a property of Image class
					$sql .= ", $field";
				}
			}
			$sql .= " limit 1000"; // Increased limit to allow for more comprehensive sorting

		$imagelist = new ImageList();
		$imagelist->_setSph($sph);

		//use this as will create us proper image objects!
		$count = $imagelist->getImagesBySphinxQL($sql, true);

		$metrix = $columns = array();
		foreach ($imagelist->images as $i => $image) {
			$current_row_value = $image->{$rowname};
			if ($rowname == 'takenyear') {
				$current_row_value = str_replace('tt','0s',$current_row_value); //allows for decade!
				if ($current_row_value == '0000s') continue;
			}
            if (empty($current_row_value) && $current_row_value !== '0') continue; // Skip if row value is empty

			$rowvalue = $current_row_value;
			$colvalue = $image->{$colname};
            if (empty($colvalue) && $colvalue !== '0') continue; // Skip if column value is empty

			if (!isset($metrix[$rowvalue])) {
				$metrix[$rowvalue] = [];
			}
			$metrix[$rowvalue][$colvalue] = $image;

			if (!isset($columns[$colvalue])) {
				$columns[$colvalue] = 0;
			}
			$columns[$colvalue]++;
		}

#######################################

		print "<table cellspacing=0 cellpadding=1 style=position:relative>";
		print "<tr style=position:sticky;top:0;background-color:white>";
		print "<td>";

		// Prepare data for column sorting
        $col_sort_data = null;
        if ($col_sort_type == 'images') {
            $col_sort_data = [];
            foreach (array_keys($columns) as $col_val) {
                $sum = 0;
                foreach ($metrix as $row_items) {
                    if (isset($row_items[$col_val])) {
                        $sum += $row_items[$col_val]->images;
                    }
                }
                $col_sort_data[$col_val] = $sum;
            }
        } elseif ($col_sort_type == 'unique') {
            $col_sort_data = [];
            foreach (array_keys($columns) as $col_val) {
                $unique_row_keys_for_col = [];
                foreach ($metrix as $row_key => $row_items) {
                    if (isset($row_items[$col_val])) {
                        $unique_row_keys_for_col[$row_key] = true;
                    }
                }
                $col_sort_data[$col_val] = count($unique_row_keys_for_col);
            }
        } else {
            // For 'alpha' or 'numeric' sort on keys, $col_sort_data is not strictly needed
            // as the keys themselves are used by the comparison function.
            $col_sort_data = null;
        }
        uksort($columns, get_sort_comparison_function($col_sort_type, $col_sort_dir, $col_sort_data));

		foreach($columns as $colvalue => $count)
			print "<th style='border-left:1px solid silver'>".htmlentities($colvalue);

		// Prepare data for row sorting
        $row_sort_data = null;
        if ($row_sort_type == 'images') {
            $row_sort_data = [];
            foreach ($metrix as $row_key => $row_items) {
                $row_sort_data[$row_key] = array_sum(array_column($row_items, 'images'));
            }
        } elseif ($row_sort_type == 'unique') {
            $row_sort_data = [];
            foreach ($metrix as $row_key => $row_items) {
                $row_sort_data[$row_key] = count($row_items);
            }
        } else {
            // For 'alpha' or 'numeric' sort on keys, $row_sort_data is not strictly needed.
            $row_sort_data = null;
        }
		uksort($metrix, get_sort_comparison_function($row_sort_type, $row_sort_dir, $row_sort_data));

		foreach($metrix as $rowvalue => $rows) {
			print "<tr>";
			print "<th style='position:sticky;left:0;background-color:white;border-top:1px solid silver'>".htmlentities($rowvalue);
			foreach($columns as $colvalue => $count) {
				print "<td align=center>";
				if (!empty($rows[$colvalue])) {
					$image = $rows[$colvalue];
                    $current_row_value_encoded = urlencode('"'.$rowvalue.'"');
                    if ($rowname == 'takenyear') {
                        // Apply specific encoding for 'takenyear' if it involves 's' for decade
                        $current_row_value_encoded = urlencode('"'.str_replace('0s','tt',$rowvalue).'"');
                    }

					if ($image->images > 1)
						$url = "/browser/#!/$rowname+".$current_row_value_encoded."/$colname+".urlencode('"'.$colvalue.'"').$extra;
					else
						$url = "/photo/{$image->gridimage_id}";

					$image->realname .= " [taken {$image->takenday}]";
			?>
 <a title="<? echo $image->grid_reference; ?> : <? echo htmlentities($image->title) ?> by <? echo htmlentities($image->realname); ?>" href="<? echo $url; ?>"><? echo $image->getThumbnail($thumbw,$thumbh,false,true,'loading=lazy src'); ?></a>
			<?
					if ($image->images>1)
						print "<br>".number_format($image->images,0);
				}
			}
			print "<th style='border-top:1px solid silver'>".htmlentities($rowvalue);
		}

		print "<tr>";
		print "<td>";
		foreach($columns as $colvalue => $count)
			print "<th style='border-left:1px solid silver'>".htmlentities($colvalue);

		print "</table>";
	}

#######################################

$smarty->display('_std_end.tpl');


