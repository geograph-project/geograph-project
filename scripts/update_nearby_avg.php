<?php
/**
 * $Project: GeoGraph $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2022 Barry Hunter (geo@barryhunter.co.uk)
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

//these are the arguments we expect
$param=array('debug'=>false, 'limit'=> 10);

$debug = (posix_isatty(STDOUT) || $param['debug']);


chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

##################################

//alter table gridsquare add nearby_avg float default null;


// 1. Grab all active prefixes
$prefixes = $db->GetAll("SELECT * FROM gridprefix WHERE imagecount > 0 LIMIT {$param['limit']}");
$done = 0;

foreach ($prefixes as $prefix) {
    $ri     = (int)$prefix['reference_index'];
    $left   = (int)$prefix['origin_x'];
    $right  = (int)$prefix['origin_x'] +$prefix['width']-1;
    $top    = (int)$prefix['origin_y'] +$prefix['height']-1;
    $bottom = (int)$prefix['origin_y'];

    // 2. Build the Well-Known Text (WKT) string for the region envelope
    $wkt = "POLYGON(($left $bottom, $right $bottom, $right $top, $left $top, $left $bottom))";

    // 3. Construct the piecemeal UPDATE query
    // Using MBRContains over standard standard text filtering for MariaDB R-tree index matching
    $sql = "UPDATE gridsquare
            SET nearby_avg = getAverageImages(
                ST_GeomFromText(
                    CONCAT('POLYGON((',
                        x - 10, ' ', y - 10, ',',
                        x + 10, ' ', y - 10, ',',
                        x + 10, ' ', y + 10, ',',
                        x - 10, ' ', y + 10, ',',
                        x - 10, ' ', y - 10,
                    '))')
                )
            ),
            last_timestamp = last_timestamp
            WHERE percent_land > 0
              AND reference_index = ?
              AND MBRContains(ST_GeomFromText(?), point_xy)";

    // 4. Execute safely using ADODB parameter binding
    $db->Execute($sql, array($ri, $wkt));
    $affected = $db->Affected_Rows();

    $done++;
	if ($debug)
        echo "Processed prefix: {$prefix['prefix']} updated:$affected/{$prefix['landcount']} ({$done}/" . count($prefixes) . ")               \r";
}

	if ($debug)
		print "\ndone.\n";

