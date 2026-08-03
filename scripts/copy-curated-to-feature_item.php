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
$param=array('feature_type_id'=>7, 'label'=>'Sea Arches', 'execute'=>false);


chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

##################################

$param['feature_type_id'] = intval($param['feature_type_id']);
$label = $db->Quote($param['label']);

$sql = trim("
INSERT INTO feature_item (
    feature_type_id,
    name,
    wgs84_lat,
    wgs84_long,
    point_ll,
    gridimage_id,
    nearby_images,
    user_id,
    gridimage_id_user_id,
    created
)
SELECT
    {$param['feature_type_id']} AS feature_type_id,
    c.feature AS name,
    ROUND(AVG(gi.wgs84_lat), 6) AS wgs84_lat,
    ROUND(AVG(gi.wgs84_long), 6) AS wgs84_long,
    POINT(ROUND(AVG(gi.wgs84_long), 6), ROUND(AVG(gi.wgs84_lat), 6)) AS point_ll,
    c.gridimage_id AS gridimage_id,
    COUNT(gi.gridimage_id) AS nearby_images,
    c.user_id AS user_id,
    c.user_id AS gridimage_id_user_id,
    NOW() AS created
FROM curated c
INNER JOIN gridimage_search gi USING (gridimage_id)
LEFT JOIN feature_item f
       ON f.feature_type_id = {$param['feature_type_id']}
      AND f.name = c.feature
WHERE c.label = $label
  AND c.feature IS NOT NULL
  AND c.feature != ''
  AND f.name IS NULL
GROUP BY c.feature
HAVING (STDDEV(gi.wgs84_lat) < 0.005 OR STDDEV(gi.wgs84_lat) IS NULL)
   AND (STDDEV(gi.wgs84_long) < 0.005 OR STDDEV(gi.wgs84_long) IS NULL)
");

print "$sql;\n";


#############

// 1. Fetch unlinked image points for missing feature names
$sqlSelect = "
    SELECT
        c.gridimage_id,
        c.feature,
        c.label,
        c.user_id,
        gi.wgs84_lat,
        gi.wgs84_long
    FROM curated c
    INNER JOIN gridimage_search gi USING (gridimage_id)
    LEFT JOIN feature_item f
           ON f.feature_type_id = {$param['feature_type_id']}
          AND f.name = c.feature
    WHERE c.label = $label
      AND c.feature IS NOT NULL
      AND c.feature != ''
      AND f.name IS NULL
    ORDER BY c.feature, c.gridimage_id
";

$rows = $db->getAll($sqlSelect);

// Group rows by feature string
$featuresByName = [];
foreach ($rows as $row) {
    $featuresByName[$row['feature']][] = $row;
}

// Haversine / Equirectangular distance approximation (in km)
function getDistanceKm($lat1, $lon1, $lat2, $lon2) {
    $deg2rad = M_PI / 180;
    $x = ($lon2 - $lon1) * $deg2rad * cos(($lat1 + $lat2) / 2 * $deg2rad);
    $y = ($lat2 - $lat1) * $deg2rad;
    return sqrt($x * $x + $y * $y) * 6371; // Earth radius in km
}

$clustersToInsert = [];

// 2. Spatial clustering per feature name
foreach ($featuresByName as $featureName => $items) {
    $clusters = []; // Array of clusters for this name

    foreach ($items as $item) {
        $assigned = false;
        foreach ($clusters as &$cluster) {
            // Check distance against the centroid/first point of existing cluster
            $dist = getDistanceKm($item['wgs84_lat'], $item['wgs84_long'], $cluster['lat_sum'] / $cluster['count'], $cluster['long_sum'] / $cluster['count']);
            
            // If within 0.5 km (500m), add to this spatial cluster
            if ($dist <= 0.5) {
                $cluster['items'][] = $item;
                $cluster['lat_sum'] += $item['wgs84_lat'];
                $cluster['long_sum'] += $item['wgs84_long'];
                $cluster['count']++;
                $assigned = true;
                break;
            }
        }

        // If further than 500m from all existing clusters, start a new cluster
        if (!$assigned) {
            $clusters[] = [
                'feature' => $featureName,
                'label' => $item['label'],
                'items' => [$item],
                'lat_sum' => $item['wgs84_lat'],
                'long_sum' => $item['wgs84_long'],
                'count' => 1
            ];
        }
    }

    // Accumulate distinct spatial clusters
    foreach ($clusters as $c) {
        $avgLat = $c['lat_sum'] / $c['count'];
        $avgLong = $c['long_sum'] / $c['count'];
        
        $clustersToInsert[] = [
            'feature_type_id' => $param['feature_type_id'],
            'name'          => $c['feature'],
            'wgs84_lat'     => round($avgLat, 6),
            'wgs84_long'    => round($avgLong, 6),
            'gridimage_id'  => $c['items'][0]['gridimage_id'], // Primary representative ID
            'user_id'       => $c['items'][0]['user_id'],
            'gridimage_id_user_id' => $c['items'][0]['user_id'],
            'nearby_images' => $c['count']
        ];
    }
}

// 3. Batch insert distinct feature clusters
print_r($clustersToInsert);

foreach($clustersToInsert as $row) {
        $sql = 'INSERT INTO feature_item SET `'.implode('` = ?,`', array_keys($row))."` = ?, point_ll = POINT({$row['wgs84_long']},{$row['wgs84_lat']})";

	print "$sql;\n";
	if($param['execute']) {
        	$db->Execute($sql, array_values($row)) or die("$sql\n\n".$db->ErrorMsg()."\n");
	}
}

echo "Created " . count($clustersToInsert) . " distinct feature records.\n";
