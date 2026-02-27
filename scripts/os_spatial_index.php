<?php

/**
 * GEOGRAPHIC NAME DISAMBIGUATION & CLUSTERING
 * * This script appends a /[GridRef] suffix to place names in the OS and IE
 * gazetteers to create unique labels for search and RRF partitioning.
 * * It ensures cross-border uniqueness (e.g., Bangor/SH57 vs Bangor/J13)
 * while intentionally clustering "close-proximity" duplicates - such as
 * Suburban Areas near their parent settlements-under a shared label
 * to prevent ranking fracture in the same geographic area.

 * see docs/os_spatial_index.txt for how the raw table was created
 */

$param = array('execute'=>0,'stats'=>false);

chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


###########################################################

if ($param['stats']) {
	$table = 'images_place_joined_test_new';

	if (!$db->getOne("show columns from os_spatial_index LIKE 'images'")) {
		printOrExecute("ALTER TABLE os_spatial_index ADD images MEDIUMINT UNSIGNED DEFAULT NULL");
		printOrExecute("ALTER TABLE ie_spatial_index ADD images MEDIUMINT UNSIGNED DEFAULT NULL");
	}

	printOrExecute("CREATE INDEX IF NOT EXISTS name1 ON $table (name1)");

	//in theory names should be the same!
	foreach(array('os_spatial_index', 'ie_spatial_index') as $index) {
		printOrExecute("UPDATE $index s
		JOIN (
		    SELECT name1, COUNT(*) as image_count
		    FROM $table
		    GROUP BY name1
		) i ON s.name1 = i.name1
		SET s.images = i.image_count");
	}
	print ".\n";
	exit;
}

###########################################################

if ($db->getOne("select name1 from os_spatial_index where name1 like '%/%'"))
	die("appears this script has already been run, only safe to run once, after the table created\n");


###########################################################

//noop, if alrady!
printOrExecute("ALTER TABLE os_spatial_index MODIFY name1 VARCHAR(128) NOT NULL");
printOrExecute("ALTER TABLE ie_spatial_index MODIFY name1 VARCHAR(128) NOT NULL");

printOrExecute("CREATE INDEX IF NOT EXISTS idx_name1 ON os_spatial_index (name1)");
printOrExecute("CREATE INDEX IF NOT EXISTS idx_name1 ON ie_spatial_index (name1)");

###########################################################
// Normalize Irish Place Names and Counties

$iePlaces = $db->Execute("SELECT id, name1, county FROM ie_spatial_index");
while ($row = $iePlaces->FetchRow()) {
	//there are a few with entities!
    $cleanName = html_entity_decode($row['name1'], ENT_QUOTES | ENT_HTML5);

    // Strip literal quotes that were escaped as &Quot;
    $cleanName = trim($cleanName, '"');

    // Normalizing name1: HELEN'S BAY -> Helen's Bay
    // We use a custom ucwords-style approach to handle apostrophes correctly
    $cleanName = mb_convert_case($cleanName, MB_CASE_TITLE, "UTF-8");
    $cleanName = str_replace("'S", "'s", $cleanName); // Fix apostrophe-S

    // Fix O'brien -> O'Brien
    $cleanName = preg_replace_callback("/O'([a-z])/", function($matches) {
        return "O'" . strtoupper($matches[1]);
    }, $cleanName);

    // Normalizing county: KERRY -> Kerry
    $cleanCounty = mb_convert_case($row['county'], MB_CASE_TITLE, "UTF-8");

    printOrExecute("UPDATE ie_spatial_index SET name1 = ?, county = ? WHERE id = ?", 
        [$cleanName, $cleanCounty, $row['id']]);
}

###########################################################
// 1. Find all names that exist more than once across both tables

$sql = "
    SELECT name1 FROM (
        SELECT name1 FROM os_spatial_index
        UNION ALL
        SELECT name1 FROM ie_spatial_index
    ) as combined
    GROUP BY name1
    HAVING COUNT(*) > 1";

$duplicates = $db->getCol($sql);

###########################################################

 require_once('geograph/conversions.class.php');
 $conv = new Conversions;

// note we ALSO lookup close pairs in OS data
$proximityPairs = $db->getAll("SELECT a.name1, a.id as id1, b.id as id2, a.local_type, b.local_type, ST_X(b.point_en) as e,ST_Y(b.point_en) as n,
       ST_Distance(a.point_en, b.point_en) as dist FROM os_spatial_index a JOIN os_spatial_index b ON a.name1 = b.name1 AND a.id != b.id AND a.local_type = 'Suburban Area'
 WHERE ST_Distance(a.point_en, b.point_en) < 2000");

/* * DISAMBIGUATION & CLUSTERING LOGIC:
 * OS Open Names contains multiple entries for the same name (e.g., Bournemouth as both 'Town' 
 * and 'Suburban Area'). If we give them different names, we fracture the photo rankings.
 * * STRATEGY: 
 * 1. IDENTIFY: Find 'Suburban Area' entries within 2km of another entry with the same name.
 * 2. CLUSTER: Force these "Close Pairs" to use a identical Master GridRef suffix.
 * 3. SEPARATE: Use a distinct GridRef suffix for entries with the same name that are far apart.
 * * RESULT: 
 * - 'Elmdon/SP1683' becomes a single "bucket" for the two Solihull suburban centroids.
 * - 'Elmdon/TL4639' remains a separate bucket for the Essex village.
 * * UX NOTE: The '/' suffix now signifies a "geographically unique cluster." Even if a cluster 
 * contains only one point, the consistent naming convention prevents "hidden" distant duplicates 
 * (like Elmdon vs Elmdon/TL4639) from confusing the user or the RRF ranker.
 */

foreach ($proximityPairs as $row) {
    // We don't want to disambiguate these against EACH OTHER
    //$gr = ... calculate GR from one place (use the b table, as it should be a village/town if apprioate)
    list ($gr,) = $conv->national_to_gridref($row['e'],$row['n'],4,1);
    $proximityMap[$row['id1']] = $gr; //store the same GR for BOTH
    $proximityMap[$row['id2']] = $gr;
}

###########################################################

foreach ($duplicates as $name) {
    // 2. Process OS (British) Table
    $osPlaces = $db->getAll("SELECT id, geometry_x, geometry_y FROM os_spatial_index WHERE name1 = ?", [$name]);
    foreach ($osPlaces as $place) {
	// Partition Name: If it's a close pair, use a 'Master' gr for the group
	if (isset($proximityMap[$place['id']])) {
            $gr = $proximityMap[$place['id']]; //use the standardizied GR for the pair!
        } else {
	    list ($gr,) = $conv->national_to_gridref($place['geometry_x'],$place['geometry_y'],4,1);
	}
        $newName = "$name/".str_replace(' ','',$gr);
        printOrExecute("UPDATE os_spatial_index SET name1 = ? WHERE id = ?", [$newName, $place['id']]);
    }

    // 3. Process IE (Irish) Table
    $iePlaces = $db->getAll("SELECT id, geometry_x, geometry_y FROM ie_spatial_index WHERE name1 = ?", [$name]);
    foreach ($iePlaces as $place) {
	list ($gr,) = $conv->national_to_gridref($place['geometry_x'],$place['geometry_y'],4,2);
        $newName = "$name/".str_replace(' ','',$gr);
        printOrExecute("UPDATE ie_spatial_index SET name1 = ? WHERE id = ?", [$newName, $place['id']]);
    }
    print "\n";
}


#############################################################

function printOrExecute($sql, $values = []) {
        global $param, $db;

        if ($param['execute']) {
                $db->Execute($sql, $values) or die("$sql;\n\n".$db->ErrorMsg()."\n\n");
		print $db->Affected_Rows()." ";
        } else {
                print "$sql; -- ".implode(',',$values)."\n\n";
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
