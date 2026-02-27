<?php

$param = array('table'=>'images_place_joined_test', 'shard'=>100000, 'execute'=>0, 'max'=>0, 'preset'=>false);

chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$Table = $param['table'];
$targetTable = "{$param['table']}_new";

###################################################################
// quick way to just rebuild a single preset - use after updating a preset!

if (!empty($param['preset'])) {
	include "geograph/ranking_presets.inc.php";

	//todo: $targetTable = $table ... may want to update the live table, not the _new one!

	$name = $param['preset'];
	$criteria = $presets[$name];

//foreach ($presets as $name => $criteria) {
    echo "-- Processing Preset: $name ---\n";

foreach($criteria as $c)
	print "\t".implode("\t",$c)."\n";

    // in a function so can be called directly
    buildRRTPreset($name, $criteria);

	exit;
}

###################################################################
// Configuration: Declarative structure

// in general the final table, will be intendted to use with gridimage_search, so no need to duplicate columns in that table

$config = [
    'gridimage_id'   => ['type' => 'pkey', 'source' => 'gridimage_search'],

//    'grid_reference' => ['source' => 'gridsquare'], -- in practice, not worth duplicating?

	//but might as well precompute these
	//note not currently including reference_index, so LENGTH(hectad) is used as a stand in, so avoid removing hectad
    'hectad'         => ['type' => 'formula', 'typeStr'=>'char(4)', 'sql' => "CONCAT(SUBSTRING(gs.grid_reference,1,LENGTH(gs.grid_reference)-3),SUBSTRING(gs.grid_reference,LENGTH(gs.grid_reference)-1,1))"],
    'myriad'         => ['type' => 'formula', 'typeStr'=>'char(2)', 'sql' => "SUBSTRING(gs.grid_reference,1,LENGTH(gs.grid_reference)-4)"],

	//ai columns
    'ai_types'       => ['type' => 'aggregate', 'typeStr' => 'VARCHAR(64)', 'source'  => 'gridimage_label', 'sql' => "GROUP_CONCAT(DISTINCT IF(l.model = 'types', l.label, NULL) SEPARATOR ';')"],
    'ai_tops'        => ['type' => 'aggregate', 'typeStr' => 'VARCHAR(255)', 'source' => 'gridimage_label', 'sql' => "GROUP_CONCAT(DISTINCT IF(l.model = 'clip' AND l.score > 0.35, l.label, NULL) SEPARATOR ';')"],

	//copy this from gridimage, mainly to enable easy spatial joins
	//but can also backfill where nateastings is missing.
    'point_en'       => ['type' => 'formula', 'typeStr' => 'POINT NOT NULL', 'sql' => "CASE
    WHEN nateastings != 0 THEN POINT( g.nateastings, g.natnorthings)
    WHEN gi.reference_index = 1 THEN POINT(((gi.x - 206) * 1000) + 500, (gi.y * 1000) + 500)
    WHEN gi.reference_index = 2 THEN POINT(((gi.x - 10) * 1000) + 500, ((gi.y - 149) * 1000) + 500)
END"],

    // Sphinx-compatible Logarithmic Distance Bucketing
    // string is used to allow for unknown, as sphinx doesnt store NULL
    // but be careful if put this in a column which allows NULL, 0m distance might be stored as NULL because maths fails with div/0! - so below includes COALESCE to avoid that
    // in concept would be nice to use numberic column, and NULL. (but as noted NULL/empty still mean 0 distance in sphinx!)

    // Powers of 2 (e.g., 1, 2, 4, 8, 16, 32, 64, 128...)
    'distance' => [
        'type' => 'formula',
        'typeStr' => 'MEDIUMINT UNSIGNED', // Stored as string to allow 'Unknown'
        'sql' => "COALESCE(IF(g.natnorthings > 0 AND g.viewpoint_eastings > 0,
            CAST(POW(2, FLOOR(LOG2(SQRT(
                    POW(CAST(g.nateastings AS SIGNED) - CAST(g.viewpoint_eastings AS SIGNED), 2) +
                    POW(CAST(g.natnorthings AS SIGNED) - CAST(g.viewpoint_northings AS SIGNED), 2)
                )
            ))) AS UNSIGNED),
            'Unknown'
        ),'0')"
    ],

    // Cross-grid detection: Is the viewpoint in a different 1km square than the subject?
    // not sure if worth including this... (its metadata that can't be got from gridimage_search alone)
    'crossgrid' => [
        'type' => 'formula',
        'typeStr' => "TINYINT UNSIGNED", // NULL allowed by default
        'sql' => "IF(gi.moderation_status = 'accepted' AND g.nateastings > 0 AND g.viewpoint_eastings > 0, IF(
                (g.nateastings DIV 1000 != g.viewpoint_eastings DIV 1000) OR (g.natnorthings DIV 1000 != g.viewpoint_northings DIV 1000),
                1, 0), NULL)"
    ],

	//these allow rapid grouping
    // Unique ID for the 100m grid square (e.g. 1543000212)
    // BIGINT is necessary as the math exceeds the 2.1bn limit of INT
    'scenti' => [
        'type' => 'formula',
        'typeStr' => 'BIGINT UNSIGNED',
        'sql' => "(gi.reference_index * 1000000000 + IF(g.natgrlen <= 3, (g.nateastings DIV 100) * 100000 + (g.natnorthings DIV 100), 0))"
    ],

    // Unique ID for the 1km square from which the photo was taken
    'viewsquare' => [
        'type' => 'formula',
        'typeStr' => 'INT UNSIGNED',
        'sql' => "(gi.reference_index * 1000000 + (g.viewpoint_northings DIV 1000) * 1000 + g.viewpoint_eastings DIV 1000)"
    ],

     // Image Format: Perfect use case for ENUM
    'format' => [
        'type' => 'formula',
        'typeStr' => "ENUM('square', 'landscape', 'panorama', 'portrait')",
        'sql' => "CASE
            WHEN ABS(CAST(width AS SIGNED) - CAST(height AS SIGNED)) <= 60 THEN 'square'
            WHEN width > height THEN IF(width > (height * 2), 'panorama', 'landscape')
            ELSE 'portrait'
        END"
    ],

	//note, name1/centroid_dist/bearing_deg/cardinal_dir/packed_mbr are fixed, and set by a seperate Geo Query
	//also note, that name1 is used for partioning the scores. ambigious placenames need dealing with!
    'name1'          => ['type' => 'formula', 'typeStr' => 'VARCHAR(64)', 'sql' => 'NULL'],
    'centroid_dist'  => ['type' => 'formula', 'typeStr' => 'MEDIUMINT UNSIGNED', 'sql' => 'NULL'],
    'bearing_deg'    => ['type' => 'formula', 'typeStr' => 'smallint default null', 'sql' => 'NULL'],
    'cardinal_dir'   => ['type' => 'formula', 'typeStr' => "ENUM('N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW') DEFAULT NULL", 'sql' => 'NULL'],
    'packed_mbr'     => ['type' => 'formula', 'typeStr' => 'VARCHAR(255)', 'sql' => 'NULL'],

	//might as well include these (although we are really computing a 'better' placename from OS Open Names)
        //we basically joining in sphinx_placenames because it quite an expensive join to do at runtime
	//todo, would be to extract county from os_spatial_index (will need updating!)
    'Place'          => ['source' => 'sphinx_placenames'],
    'County'         => ['source' => 'sphinx_placenames'],
    'Country'        => ['source' => 'sphinx_placenames'],
    'Region'         => ['source' => 'sphinx_placenames'],

	//AI scores
    'score'          => ['source' => 'gridimage_score', 'type' => 'pivot', 'filter' => "s.model='score'"], //fake hits based score!
    'aesthetic'      => ['source' => 'gridimage_score', 'type' => 'pivot', 'filter' => "s.model='aesthetic'"],
    'technical'      => ['source' => 'gridimage_score', 'type' => 'pivot', 'filter' => "s.model='technical'"],
    'baysian'        => ['source' => 'gridimage_score', 'type' => 'pivot', 'filter' => "s.model='baysian'"], //gallery!
    'v_bayesian'     => ['source' => 'gridimage_score', 'type' => 'pivot', 'filter' => "s.model='v_bayesian'"], //scenic

	//to enable resolution filter
    'largest'	     => ['type' => 'formula', 'typeStr' => 'MEDIUMINT UNSIGNED', 'sql' => 'GREATEST(width,height,original_width,original_height)'],

];
//NOTE, 'date_sequence' and score/index coluns for RRF presets are added automatically too!

#############################################################

// 1. Build the CREATE TABLE SQL dynamically
function getCreateSql($db, $config, $targetTable) {
    $columns = [];
    foreach ($config as $colName => $settings) {
        $typeStr = "FLOAT"; // Default for scores/formulas
	if (isset($settings['typeStr'])) {
	    $typeStr = $settings['typeStr'];

	//find from source table
        } elseif (!empty($settings['source'])) {
	    $sourceTable = $settings['source'];
            $meta = $db->MetaColumns($sourceTable);
	    foreach ($meta as $m) {
                // Check if column name matches or if it's the primary key
                if ($m->name == $colName || (($settings['type'] ?? '') == 'pkey')) {
                    $typeStr = $m->type;
                    if ($m->max_length > 0 && !in_array(strtolower($m->type), ['int', 'float', 'double'])) {
                        $typeStr .= "({$m->max_length})";
                    }
                    break;
                }
            }
        }
        $isPKey = ($settings['type'] ?? '') == 'pkey';
        $columns[] = "`$colName` $typeStr" . ($isPKey ? " PRIMARY KEY" : "");
    }
    return "CREATE TABLE IF NOT EXISTS $targetTable (" . implode(", ", $columns) . ") ENGINE=InnoDB";
}

// 2. Main Execution
printOrExecute("DROP TABLE IF EXISTS $targetTable");
printOrExecute(getCreateSql($db, $config, $targetTable));

#############################################################

// 3. Chunking Logic
if (!empty($param['max']))
	$maxId = intval($param['max']);
else
	$maxId = (int)$db->GetOne("SELECT MAX(gridimage_id) FROM gridimage_search");

    // Prepare column selections
    $selectFields = [];

    foreach ($config as $colName => $settings) {
        if (($settings['type'] ?? '') == 'pivot') {
            $selectFields[] = "MAX(IF({$settings['filter']}, s.score, NULL)) AS `$colName`";
        } elseif (($settings['type'] ?? '') == 'aggregate') {
            $selectFields[] = "{$settings['sql']} AS `$colName`";
        } elseif (($settings['type'] ?? '') == 'formula') {
            $selectFields[] = "{$settings['sql']} AS `$colName`";
        } else {
            // Map table aliases: gi (search), gr (square), sp (place)
            $alias = ($settings['source'] == 'gridimage_search') ? 'gi' :
                     (($settings['source'] == 'gridsquare') ? 'gs' : 'sp');
            $selectFields[] = "$alias.`$colName`";
        }
    }

echo "-- Starting ingest of $maxId rows in chunks of {$param['shard']}...\n";

for ($start = 0; $start < $maxId; $start += $param['shard']) {
    $end = $start + $param['shard'];

    printOrExecute("
        INSERT INTO $targetTable
        SELECT " . implode(",\n ", $selectFields) . "
        FROM gridimage_search gi
        INNER JOIN gridimage g USING (gridimage_id)
        INNER JOIN gridsquare gs USING (grid_reference)
        INNER JOIN sphinx_placenames sp ON(sp.placename_id = gs.placename_id)
        INNER JOIN gridimage_size size USING (gridimage_id)
        LEFT JOIN gridimage_score s USING (gridimage_id)
        LEFT JOIN gridimage_label l USING (gridimage_id)
        WHERE gi.gridimage_id > $start AND gi.gridimage_id <= $end

and gi.gridimage_id MOD 23 = 11

        GROUP BY gi.gridimage_id");

    $percent = round(($end / $maxId) * 100, 2);
    echo "-- Processed up to ID $end ($percent%)\n";
    if (!$param['execute'])
	break;
}

#############################################################

if ($db->getOne("SHOW TABLES LIKE '{$Table}'")) {

	echo "-- Inheriting existing spatial data from production...\n";

	// Copy what we already know to avoid expensive ST_Distance calls
	printOrExecute("
	    UPDATE $targetTable new
	    INNER JOIN $Table old USING (gridimage_id)
	    SET new.name1 = old.name1,
	        new.centroid_dist = old.centroid_dist,
	        new.bearing_deg = old.bearing_deg,
	        new.cardinal_dir = old.cardinal_dir
	");
}

echo "-- Calculating spatial data for new rows...\n";

// Add a Spatial Index now to make the next UPDATE fast
//... the centroid_dist is also useful
printOrExecute("ALTER TABLE $targetTable ADD SPATIAL INDEX(point_en), ADD INDEX(centroid_dist)");

#############################################################

// Run the spatial update only on rows where we don't have data yet
// We can chunk this too if there are many new rows
/* $spatialUpdate = "UPDATE $targetTable i
SET i.packed_mbr = (
    SELECT CONCAT_WS('|',s.name1,ST_Distance(i.point_en, s.point_en), geometry_x, geometry_y)
    FROM os_spatial_index s
    WHERE MBRContains(s.os_mbr_geometry, i.point_en)
    ORDER BY ST_Distance(i.point_en, s.point_en) /
        CASE
            WHEN s.local_type = 'City' AND ST_Distance(i.point_en, s.point_en) > 5000
            THEN 1 -- City importance evaporates outside 5km
            ELSE SQRT(s.area_m)
    END ASC
    LIMIT 1
)
WHERE i.packed_mbr IS NULL AND centroid_dist IS NULL AND LENGTH(hectad)=4
LIMIT 1000"; */

//actully have a function that encapulates the logic above, but MOST importantly, the query inside a function will use the SPATIAL index
// for better or worse, the subquery version above doesn't!
loopUntilDone("UPDATE $targetTable i
 SET i.packed_mbr = GetBestPlaceOS(i.point_en)
 WHERE i.packed_mbr IS NULL    AND i.centroid_dist IS NULL    AND LENGTH(i.hectad) = 4 LIMIT 1000", 'gb');

//repeat for images over 10km from a place
loopUntilDone("UPDATE $targetTable i
 SET i.packed_mbr = GetRemotePlaceOS(i.point_en)
 WHERE i.packed_mbr IS NULL    AND i.centroid_dist IS NULL    AND LENGTH(i.hectad) = 4 LIMIT 1000", 'gb2');


// and repeat for Ireland!!
/* $spatialUpdate = "UPDATE $targetTable i
SET i.packed_mbr = (
    SELECT CONCAT_WS('|', s.name1, ST_Distance(i.point_en, s.point_en), geometry_x, geometry_y)
    FROM ie_spatial_index s
    WHERE MBRContains(s.search_area, i.point_en)
    ORDER BY ST_Distance(i.point_en, s.point_en) ASC
    LIMIT 1
)
WHERE i.packed_mbr IS NULL AND centroid_dist IS NULL AND LENGTH(hectad)=3
LIMIT 1000"; */

loopUntilDone("UPDATE $targetTable i
 SET i.packed_mbr = GetBestPlaceIE(i.point_en)
 WHERE i.packed_mbr IS NULL    AND i.centroid_dist IS NULL    AND LENGTH(i.hectad) = 3 LIMIT 10000", 'ie');

loopUntilDone("UPDATE $targetTable i
 SET i.packed_mbr = GetRemotePlaceIE(i.point_en)
 WHERE i.packed_mbr IS NULL    AND i.centroid_dist IS NULL    AND LENGTH(i.hectad) = 3 LIMIT 10000", 'ie2');

#############################################################

echo "-- Unpacking data...\n";

printOrExecute("
UPDATE $targetTable i
SET
    name1 = SUBSTRING_INDEX(packed_mbr, '|', 1),
    centroid_dist = ROUND(SUBSTRING_INDEX(SUBSTRING_INDEX(packed_mbr, '|', 2), '|', -1)),

    bearing_deg = ROUND(
       MOD(
            DEGREES(ATAN2(
                CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(packed_mbr, '|', 3), '|', -1) AS SIGNED) - ST_X(i.point_en),
                CAST(SUBSTRING_INDEX(packed_mbr, '|', -1) AS SIGNED) - ST_Y(i.point_en)
            )) + 360,
            360
        )
    ),

    cardinal_dir = MOD(FLOOR((MOD(
            DEGREES(ATAN2(
                CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(packed_mbr, '|', 3), '|', -1) AS SIGNED) - ST_X(i.point_en),
                CAST(SUBSTRING_INDEX(packed_mbr, '|', -1) AS SIGNED) - ST_Y(i.point_en)
            )) + 360,
            360
        ) + 22.5) / 45), 8) + 1

WHERE packed_mbr IS NOT NULL");

#############################################################

print "-- need to stop and add the date_sequence\n";

$cmd = "php ".__DIR__."/date_sequence9.php --config={$param['config']} --table=$targetTable --table_id=gridimage_id --execute=".($param['execute']?2:0); //2 to ensure it creates the column!

print "$cmd\n";
passthru($cmd);

#############################################################

include "geograph/ranking_presets.inc.php";

foreach ($presets as $name => $criteria) {
    echo "-- Processing Preset: $name ---\n";

    // 1. Add columns if they don't exist
    printOrExecute("ALTER TABLE $targetTable
                 ADD COLUMN IF NOT EXISTS {$name}_score FLOAT,
                 ADD COLUMN IF NOT EXISTS {$name}_index MEDIUMINT UNSIGNED");

    // in a function so can be called directly
    buildRRTPreset($name, $criteria);

    // 4. Index the new column for lightning fast frontend queries
    printOrExecute("ALTER TABLE $targetTable ADD INDEX IF NOT EXISTS ({$name}_index)");
}


/*

-- in fact this COULD be used to prune away all the extra rows! (not doing yet, just shows it possible)
-- DELETE FROM images_place_joined_test_new WHERE rank_index > 100;

*/

#############################################################

print "-- optional, the packed column is redundant\n";
$sql = "ALTER TABLE {$targetTable} DROP packed_mbr";
print "$sql; -- not run\n";


echo "-- Complete. Ready for RENAME TABLE swap.\n";
$sql = "DROP TABLE IF EXISTS {$Table}_old";
print "$sql; -- not run\n";

$sql = "RENAME TABLE {$Table} TO {$Table}_old, {$Table}_new TO {$Table}";
print "$sql; -- not run\n";


//really just for debug - so can see results!
// this mainly to wrap spatial columns in ASTEXT!
print "-- Useful query for debugging...\n";

$cols = array();
foreach($config as $key => $value) {
	if ($key == "packed_mbr")
		continue;
	if (strpos($value['typeStr'] ?? '','POINT') === 0) { //todo, other spatial types
		$cols[] = "ST_ASTEXT($key)";
	} else
		$cols[] = "`$key`";
}
//presets add more columns!
foreach ($presets as $name => $criteria) {
	 $cols[] = "{$name}_score";
	 $cols[] = "{$name}_index";
}

$sql = "SELECT ".implode(",",$cols)." FROM $targetTable LIMIT 10";
print "$sql;\n";

#############################################################

function printOrExecute($sql) {
	global $param, $db;

	if ($param['execute']) {
		$db->Execute($sql) or die("$sql;\n\n".$db->ErrorMsg()."\n\n");
	} else {
		print "$sql;\n\n";
	}
}

function loopUntilDone($sql, $prefix='') {
	global $param, $db;

	if ($param['execute']) {
		do {
			$db->Execute($sql);
			$affected = $db->Affected_Rows();
			print "$prefix=$affected. ";
		} while($affected);
	} else {
		print "$sql;\n\n"; //no loop!
	}
}


function buildRRTPreset($name, $criteria) {
	global $db, $param, $targetTable;

    // 2. Build the RRF Calculation Strings
    $needsSearch = false;
    $rrfParts = [];
    foreach ($criteria as $c) {
        $f = $c['field'];
        $k = $c['k'] ?? 60; // Smoothing constant
        $dir = strtoupper($c['dir']);
        if ($f == 'sequence' || $f=='imagetaken') $needsSearch = true;

        // Build the reciprocal rank fragment
	$func = (strpos($f, 'CASE') !== false) ? 'RANK()' : 'ROW_NUMBER()';
	$alias = ($f == 'sequence' || $f=='imagetaken') ? 'gi' : 'i';
        $orderBy = (strpos($f, 'CASE') !== false) ? $f : "$alias.$f";
        if ($f == 'imagetaken' && $dir == 'ASC') {
	     //move unknown dates to end - there are a few odditie like 0000-01-23, so not checking 0000-00-00 explicitly
             //desc sort will natually goto end, so dont need this
            $orderBy = "IF($orderBy < '1000-01-01', '3000-01-01', $orderBy)";
	    $func = "RANK()"; //actully rank may be better for dates, as may be many with same date
        }
        $rrfParts[] = "1.0 / ($k + $func OVER (PARTITION BY name1 ORDER BY $orderBy $dir))";
    }
    $rrfFormula = implode(" + ", $rrfParts);

    // 3. The Combined Update
    // We compute the Score AND the Index in one nested subquery pass
    $joinClause = $needsSearch ? "INNER JOIN gridimage_search gi USING (gridimage_id)" : "";

    echo "-- Updating scores for $name...\n";
    printOrExecute("UPDATE $targetTable AS original
        INNER JOIN (
            SELECT
                gridimage_id,
                computed_score,
                ROW_NUMBER() OVER (PARTITION BY name1 ORDER BY computed_score DESC) as computed_index
            FROM (
                SELECT
                    gridimage_id,
                    name1,
                    ($rrfFormula) as computed_score
                FROM $targetTable i $joinClause
                WHERE name1 IS NOT NULL
            ) AS sub_score
        ) AS ranked ON original.gridimage_id = ranked.gridimage_id
        SET
            original.{$name}_score = ranked.computed_score,
            original.{$name}_index = ranked.computed_index
    ");
}
