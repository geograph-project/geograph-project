<?php
/**
 * $Project: GeoGraph $
 * $Id: clusters.php 5786 2009-09-12 10:18:04Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2008 Barry Hunter (geo@barryhunter.co.uk)
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

if (empty($_GET['callback'])) {
    header('Access-Control-Allow-Origin: *');
}

customExpiresHeader(3600 * 24);

// Input validation
if (empty($_POST['ids'])) {
    outputJSON(['error' => 'no image id']);
    exit;
}

// Regex to validate the list of IDs (e.g., "123,456,789")
if (!preg_match('/^\d+(,\d+)*$/', $_POST['ids'])) {
    outputJSON(['error' => 'no image id list']);
    exit;
}
$image_ids = $_POST['ids'];

$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$response = [
    'images' => [], // Consistent place for the image_id => comment map
    'snippets' => [], // Consistent place for snippet array
    'error' => null,
];

// 1. Fetch Image Comments (original $rows data)
$rows = $db->getAssoc("SELECT gridimage_id, comment FROM gridimage_search WHERE gridimage_id IN ($image_ids) AND comment != '' LIMIT 1000");

if (!empty($rows)) {
    // Process comments to UTF-8
    $response['images'] = $rows;
    foreach ($response['images'] as &$comment_value) {
        $comment_value = latin1_to_utf8($comment_value);
    }
    unset($comment_value); // Good practice
} else {
    $response['error'] = "unable to load image"; // Keeping the original error message for compatibility
}

// 2. Fetch Snippet Data (if requested)
if (!empty($_REQUEST['snippets'])) {
    $snippets = $db->getAll("select snippet_id, gridimage_id, realname, s.user_id, title, comment, grid_reference, has_dup
                             from snippet s
                             inner join user using (user_id)
                             inner join gridimage_snippet using (snippet_id)
                             where enabled = 1 and gridimage_id in ($image_ids)");

    if (!empty($snippets)) {
        // Process snippet strings to UTF-8
        foreach ($snippets as &$row) {
            $row['title'] = latin1_to_utf8($row['title']);
            $row['comment'] = latin1_to_utf8($row['comment']);
        }
        unset($row);
        $response['snippets'] = $snippets;
    }
}

outputJSON($response);
