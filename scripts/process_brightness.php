<?php
/**
 * $Project: GeoGraph $
 * $Id: submissions.php 6368 2010-02-13 19:45:59Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2007 Barry Hunter (geo@barryhunter.co.uk)
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

$param = array('limit'=>1);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

require_once('geograph/imagelist.class.php');

$gi_columns = "gridimage_id,user_id"; //,realname,title,grid_reference,credit_realname";

$q = array();
//select * from gridimage_daily inner join gridimage_thumbsize using (gridimage_id) where showday is null and vote_baysian > 3.5 and maxw = 393 limit 10;
//$sql = "SELECT $gi_columns,showday FROM gridimage_search inner join gridimage_daily using (gridimage_id) inner join gridimage_thumbsize using (gridimage_id) where showday is null and vote_baysian > 3 and maxw = 393";

$sql = "SELECT $gi_columns,showday FROM gridimage_search inner join gridimage_daily using (gridimage_id) where 1"; //showday is null and vote_baysian > 2.7";
// Update query to check if ANY of the new metric columns are NULL
$sql .= " AND (brightness IS NULL OR highlight_sat IS NULL OR shadow_sat IS NULL)";

$imagelist = new ImageList();
$imagelist->_getImagesBySql($sql." LIMIT 24"); // Limiting to 24 images per run

if (empty($imagelist->images))
    die(); //silent, as not error for cron.

$db = GeographDatabaseConnection(false);
$filesystem = GeographFileSystem();

############################################

$c = 0;
foreach ($imagelist->images as $image) {
    // 2. Load Image Thumbnail
    $resized = $image->getFixedThumbnail(393, 300, 2); 
    $filename = $resized['url'];

    // Load the GD resource using the custom filesystem function
    $fullimg = $filesystem->imagecreatefromjpeg($_SERVER['DOCUMENT_ROOT'].$filename);

    // 3. Calculate All Metrics
    // Use the updated function that returns an array
    $metrics = getExposureMetrics($fullimg);

    $b = $metrics['brightness'];
    $hs = $metrics['highlight_sat'];
    $ss = $metrics['shadow_sat'];

    print "{$filename} -> Brightness: {$b}, Highlights: {$hs}%, Shadows: {$ss}%\n";

    // 4. Update Database
    // Ensure all values are numeric and valid before update
    if (is_numeric($b) && is_numeric($hs) && is_numeric($ss)) {
        // Use a prepared statement or proper escaping (e.g., $db->qstr()) for production code
        // Simple interpolation used here for demonstration, assume $b, $hs, $ss are safe (numeric)
        $db->Execute(
            "UPDATE gridimage_daily 
             SET brightness = {$b}, 
                 highlight_sat = {$hs}, 
                 shadow_sat = {$ss}, 
                 updated=updated 
             WHERE gridimage_id = {$image->gridimage_id}"
        );
    } else {
        error_log("Failed to calculate valid metrics for image ID: {$image->gridimage_id}");
    }

    $c++;
    if ($c == $param['limit'])
        exit("Limit reached.");
}

############################################
/*
ALTER TABLE gridimage_daily
ADD COLUMN highlight_sat FLOAT DEFAULT NULL
AFTER brightness;

ALTER TABLE gridimage_daily
ADD COLUMN shadow_sat FLOAT DEFAULT NULL
AFTER highlight_sat;
*/

/**
 * Calculates brightness (Luminance), highlight saturation, and shadow saturation.
 * Returns an array: ['brightness', 'highlight_sat', 'shadow_sat']
 */
function getExposureMetrics($gdHandle) {
    if (!is_resource($gdHandle)) {
        return ['brightness' => null, 'highlight_sat' => null, 'shadow_sat' => null];
    }
    
    $width = imagesx($gdHandle);
    $height = imagesy($gdHandle);
    $totalPixels = $width * $height;
    
    $totalLuminance = 0;
    $highlightCount = 0; 
    $shadowCount = 0;    

    for ($x = 0; $x < $width; $x++) {
        for ($y = 0; $y < $height; $y++) {
            $rgb = imagecolorat($gdHandle, $x, $y);

            $red = ($rgb >> 16) & 0xFF;
            $green = ($rgb >> 8) & 0xFF;
            $blue = $rgb & 0xFF;
            
            // Standard Luminance calculation: Y = 0.299R + 0.587G + 0.114B
            $luminance = 0.299 * $red + 0.587 * $green + 0.114 * $blue;

            $totalLuminance += $luminance;

            // Check for clipped highlights (near 255)
            if ($luminance > 250) {
                $highlightCount++;
            }
            // Check for crushed shadows (near 0)
            if ($luminance < 5) {
                $shadowCount++;
            }
        }
    }
    
    // Clean up GD resource
    imagedestroy($gdHandle);

    // Normalize brightness to 0-100 scale (dividing by 255 and multiplying by 100)
    $avg_brightness = ($totalLuminance / $totalPixels) / 2.55; 
    
    // Calculate highlight/shadow percentage
    $highlight_perc = ($highlightCount / $totalPixels) * 100;
    $shadow_perc = ($shadowCount / $totalPixels) * 100;

    return [
        'brightness' => round($avg_brightness, 2),
        'highlight_sat' => round($highlight_perc, 2),
        'shadow_sat' => round($shadow_perc, 2),
    ];
}

############################################

//https://stackoverflow.com/questions/21580154/can-php-detect-if-an-image-is-too-light
    function getBrightness($gdHandle) {
        $width = imagesx($gdHandle);
        $height = imagesy($gdHandle);

        $totalBrightness = 0;

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $rgb = imagecolorat($gdHandle, $x, $y);

                $red = ($rgb >> 16) & 0xFF;
                $green = ($rgb >> 8) & 0xFF;
                $blue = $rgb & 0xFF;

                $totalBrightness += (max($red, $green, $blue) + min($red, $green, $blue)) / 2;
            }
        }

        imagedestroy($gdHandle);

        return ($totalBrightness / ($width * $height)) / 2.55;
    }


