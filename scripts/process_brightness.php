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
$sql = "SELECT $gi_columns,showday FROM gridimage_search inner join gridimage_daily using (gridimage_id) inner join gridimage_thumbsize using (gridimage_id) where showday is null and vote_baysian > 3 and maxw = 393";
$sql = "SELECT $gi_columns,showday FROM gridimage_search inner join gridimage_daily using (gridimage_id) where showday is null and vote_baysian > 2.7";
$sql .= " AND brightness IS null";

$imagelist = new ImageList();
$imagelist->_getImagesBySql($sql." LIMIT 24");

if (empty($imagelist->images))
	die();

$db = GeographDatabaseConnection(false);
$filesystem = GeographFileSystem();

############################################

$c = 0;
foreach ($imagelist->images as $image) {
	$resized = $image->getFixedThumbnail(393,300, 2);
	$filename = $resized['url'];

	$fullimg = $filesystem->imagecreatefromjpeg($_SERVER['DOCUMENT_ROOT'].$filename);

	$b = getBrightness($fullimg);

	print "$filename -> $b\n";

	if (is_numeric($b) && !is_nan($b))
		$db->Execute("UPDATE gridimage_daily SET brightness = $b, updated=updated WHERE gridimage_id = {$image->gridimage_id}");

	$c++;
	if ($c == $param['limit'])
		exit;
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


