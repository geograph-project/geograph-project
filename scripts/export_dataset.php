<?php
/**
 * $Project: GeoGraph $
 * $Id: recreate_maps.php 2996 2007-01-20 21:39:07Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2011 Barry Hunter (geo@barryhunter.co.uk)
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

/**
 * Dataset Export Script
 * Refactored for clarity and maintainability.
 */

$param = [
    'source'  => 'types_dataset_1',
    'col'     => 'types',
    'cols'    => "split, types, coalesce(distance,'0') as distance, weight",

    'model'   => 'clip',   // Options: 'clip', 'pe'
    'second'  => false,    // leave clip as first, can add pe as second!
    'limit'   => 100,      // per loop!
    'type'    => 'image',
    'paths'   => false,    // true triggers 'unclassified' logic
    'status'  => 'accepted',
    'file'    => 'dataset.jsonl',
    'execute' => false,    // Set to true to actually run the export
    'meta'    => false,    // Include extra metadata
    'll'      => false     // Include lat/long
];

chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

// Model-specific settings
if ($param['model'] === 'pe') {
    $table_embedding = "gridimage_embedding_1024";
    $model_id        = 'pe';
} else {
    $table_embedding = "gridimage_embedding";
    $model_id        = 'clip';
}

// --- 2. Query Building ---

$cols  = ["e.gridimage_id", $param['cols']];
$where = ["model = " . $db->Quote($model_id)];
$joins = ["$table_embedding e",
	  "INNER JOIN gridimage_search gi USING (gridimage_id)",
          "INNER JOIN {$param['source']} force index(v) USING (gridimage_id)"];

// Conditional Metadata Columns
if ($param['meta']) {
    array_push($cols, "grid_reference", "title", "realname", "imagetaken");
}
if ($param['ll']) {
    array_push($cols, "wgs84_lat", "wgs84_long");
}

// Logic for unclassified paths or standard tags
if ($param['paths']) {
    // Looking for unclassified / test set
    array_push($cols, "gi.user_id", "gi.moderation_status");
    if (!$param['meta']) {
        array_push($cols, "title", "realname");
    }

    if ($param['col'] === 'types') {
        $cols[] = "IF(gi.moderation_status = 'accepted' AND g2.nateastings > 0 AND viewpoint_eastings > 0 AND (g2.nateastings DIV 1000 != viewpoint_eastings DIV 1000 OR g2.natnorthings DIV 1000 != viewpoint_northings DIV 1000), 'crossgrid', '') as grid";
        $joins[] = "INNER JOIN gridimage g2 USING (gridimage_id)";
    }
    $where[] = "v = 1 AND split = 'tes'";
} else {
    // Standard processing
    if (strpos($param['source'], 'places') !== false || strpos($param['cols'], 'classification') !== false) {
        $joins[] = "INNER JOIN tag_named_stat USING (tag_id)";
        $where[] = "COALESCE(classification2, '') NOT IN ('other', 'geographical-generic')";
    }
    $where[] = "v IN (1, 2)";
    $where[] = "{$param['col']} IS NOT NULL";
}

// Common Filters
if (!empty($param['status'])) {
    $where[] = "gi.moderation_status = " . $db->Quote($param['status']);
}

if (!empty($param['type']) && preg_match('/^\w+$/', $param['type'])) {
    $where[] = "type = " . $db->Quote($param['type']);
} else {
    $cols[] = "type";
}

$cols[] = $param['execute'] ? "e.embeddings" : "LENGTH(e.embeddings) as embeddings_len";

$limitCount = min(200000, intval($param['limit']));

// --- 3. Execution & Sharding ---

$h = fopen($param['file'], 'w');
$maxId = $db->getOne("SELECT MAX(gridimage_id) FROM {$param['source']}");
$shard = ($limitCount <= 1000 || $param['paths']) ? 1000000 : 100000;
$totalProcessed = 0;

for ($start = 0; $start < $maxId; $start += $shard) {
    echo "\nRange: $start - " . ($start + $shard) . "... ";

    $where['filter'] = sprintf("gridimage_id BETWEEN %d AND %d", $start, $start + $shard - 1);

    $sql = "SELECT ".implode(", ", $cols)." FROM ".implode(" ", $joins)." WHERE ".implode(" AND ", $where)." LIMIT $limitCount";

    if (!$param['execute']) {
        echo "\n[DEBUG SQL]:\n$sql;\n";
        exit;
    }

    $rs = $db->Execute($sql);
    while ($rs && !$rs->EOF) {
        $row = $rs->fields;

        // Data Cleaning & Type Casting
        foreach ($row as $key => &$val) {
            if (is_numeric($val) && !in_array($key, ['title', 'distance'])) {
                $val += 0; // Convert to int/float
                if (in_array($key, ['wgs84_lat', 'wgs84_long', 'lat', 'lng'])) {
                    $val = round((float)$val, 6);
                }
            }
        }
        if (!empty($row['title'])) {
            $row['title'] = latin1_to_utf8($row['title']);
            $row['realname'] = utf8_encode($row['realname']);
        }
        $row['embeddings'] = base64_encode($row['embeddings']);

        if ($param['paths']) {
            $img = new GridImage();
            $img->fastInit($row);
            $row['path'] = $img->_getFullpath(false, false);
        }

        fwrite($h, json_encode($row) . "\n");

        $totalProcessed++;
        if ($totalProcessed % 1000 === 0) echo "$totalProcessed... ";

        $rs->MoveNext();
    }
}

fclose($h);
echo "\nFinished. $totalProcessed records written to {$param['file']}\n";
