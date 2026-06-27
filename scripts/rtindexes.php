<?php

//define the possible params, and default value.
$param = array('dry' => 1, 'delta' => 0, 'status' => 0, 'index' => false, 'dump' => 0, 'all'=>false); //all is default anyway, but exists, just as a quick way to bypass default status!

$start_dir = getcwd();

chdir(__DIR__);
require "./_scripts.inc.php";

if (empty($param['dump']) && empty($param['index']) && empty($param['all']) && empty($param['delta']))
	$param['status']=1;

$db = GeographDatabaseConnection(false);
$rt = GeographSphinxConnection('manticorert',true);

$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

###############################################################################

$known_indexes = array(
    'caption'              => "SELECT COUNT(*) FROM gridimage_caption WHERE type='caption'",
    'gallery_ids'          => "SELECT COUNT(*) FROM gallery_ids INNER JOIN gridimage_search ON (gridimage_id = id) INNER JOIN gridimage_size USING (gridimage_id) WHERE original_width > 1000 AND gallery_ids.baysian > 3.6",
    'gaz'                  => "SELECT COUNT(*) FROM placename_index",
    'gridimage_group_stat' => "", // NOTE, the database table is no longer maintained, data in injected direct inot RT index!
			//SELECT COUNT(DISTICT grid_reference, label) FROM gridmage_group would be INCREDIBLY SLOW
    'gridprefix'           => "SELECT COUNT(*) FROM gridprefix", //does not just list landcount ones!
    'gridsquare'           => "SELECT COUNT(*) FROM gridsquare",
    'ie_open_data'         => "SELECT COUNT(*) FROM ie_open_data",
    'index_coverage'       => "",
    'loc_placenames'       => "SELECT COUNT(*) FROM loc_placenames p LEFT JOIN loc_adm1 ON (p.adm1 = loc_adm1.adm1 AND loc_adm1.country = p.country)", 
    'os_gaz'               => "SELECT COUNT(*) FROM os_gaz",
    'os_gaz_250'           => "SELECT COUNT(*) FROM os_gaz_250",
    'tag_stat'             => "SELECT COUNT(*) FROM tag_stat",
);

###############################################################################

// Determine which indexes we are targeting
if (!empty($param['index'])) {
    $target_indexes = array();
    foreach (explode(',', $param['index']) as $index) {
        $index = trim($index);
        if (!array_key_exists($index, $known_indexes)) {
            die("Error: Index '{$index}' is not recognized in this script.\n");
        }
        $target_indexes[] = $index;
    }
} else {
    $target_indexes = array_keys($known_indexes);
}

$manticore_tables = $rt->getAssoc("SHOW TABLES");

###############################################################################

if (!empty($param['status'])) {

    echo sprintf("\n%-25s | %-12s | %-12s | %-10s\n", "Index Name", "Manticore", "Expected", "Match?");
    echo str_repeat("-", 70) . "\n";

    foreach ($target_indexes as $index) {
        $manticore_docs = "";
        $db_count = "";

        if (isset($manticore_tables[$index])) {
            $status_data = $rt->getAssoc("SHOW INDEX $index STATUS");
            $manticore_docs = $status_data['indexed_documents'] ?? 0;
        }

        if (!empty($known_indexes[$index])) {
            $db_count = $db->getOne($known_indexes[$index]);
        }

        $match = "xxxxxxx";
        if ($manticore_docs === "") $match = "missing";
        elseif ($db_count === "") $match = "??";
        elseif ((int)$manticore_docs === (int)$db_count) $match = "y";

        echo sprintf("%-25s | %-12s | %-12s | %-10s\n", $index, $manticore_docs, $db_count, $match);
    }
    echo "\n";

    // 3. Find stray indexes on Manticore not declared in our script code
    foreach ($manticore_tables as $index => $type) {
        // Skip template or local tables if necessary based on your original demo script logic
        if ($type == 'local' || $type == 'template') continue; 
        
        if (!isset($known_indexes[$index])) {
            print "WARNING: Found '$index' on Manticore server, but it has NO definition in this script.\n";
        }
    }
    
    echo "\n" . str_repeat("-", 70) . "\n";
    echo "=== Cluster Sync & Verification ===\n";

    // 1. Fetch Cluster Configuration Status
    $cluster_status = $rt->getAssoc("SHOW STATUS LIKE 'cluster%index%'");
    
    // Dynamically grab the cluster name from the status key (e.g., 'cluster_manticore_cluster_indexes' -> 'manticore_cluster')
    $cluster_name = 'manticore_cluster'; // default fallback
    $cluster_indexes_raw = '';
    foreach ($cluster_status as $key => $val) {
        if (preg_match('/^cluster_(.+)_indexes$/', $key, $matches)) {
            $cluster_name = $matches[1];
            $cluster_indexes_raw = $val;
            break;
        }
    }

    // Explode into array & trim whitespace, ignoring empty values
    $cluster_indexes = !empty($cluster_indexes_raw) ? array_map('trim', explode(',', $cluster_indexes_raw)) : array();

    if (empty($cluster_indexes)) {
	print "NO CLUSTER INDEXES - need to create cluster?? \n";
	//die();
    } else {
        print "Cluster Indexes: ".implode(', ',$cluster_indexes)."\n";
    }

    // 4. Compare Script/Manticore tables against Cluster Members
    $all_tracked_indexes = array_unique(array_merge(array_keys($known_indexes), array_keys($manticore_tables)));
$all_tracked_indexes = array_keys($manticore_tables);

    foreach ($all_tracked_indexes as $index) {
        // Skip default templates/local engines if they leak into $manticore_tables
        if (isset($manticore_tables[$index]) && ($manticore_tables[$index] == 'local' || $manticore_tables[$index] == 'template')) {
            continue;
        }

        if (!in_array($index, $cluster_indexes)) {
     ##       print "CLUSTER MISMATCH: Index '$index' is missing from cluster '$cluster_name'.\n";
            print "ALTER CLUSTER `$cluster_name` ADD $index;\n";
        }
    }

    echo "\n";
    exit(0);
}

###############################################################
// Dump Schema Configurations Alphabetically for Diffing

if (!empty($param['dump'])) {
    $manticore_tables = $rt->getAssoc("SHOW TABLES");
    
    // Sort tables alphabetically by their index names
    ksort($manticore_tables);
    
    // Fetch cluster indexes list once to cross-check membership
    $cluster_status = $rt->getAssoc("SHOW STATUS LIKE 'cluster%indexes'");
    $cluster_indexes_raw = '';
    foreach ($cluster_status as $key => $val) {
        $cluster_indexes_raw = $val;
    }
    $cluster_indexes = !empty($cluster_indexes_raw) ? array_map('trim', explode(',', $cluster_indexes_raw)) : array();

    print "-- Config: {$param['config']}\n"; //so saved in file
    print "-- Indexes: ".implode(", ",array_keys($manticore_tables))."\n";
    if (empty($cluster_indexes)) {
	print "-- No Cluster Indexes\n";
    } else {
        print "-- Cluster Indexes: ".implode(', ',$cluster_indexes)."\n";
    }
    print "-- Dumped: ".date('r')."\n";
    print "\n";

    foreach ($manticore_tables as $index => $type) {
        // Skip default local/template engines if they aren't explicit RT configurations
        if ($type == 'local' || $type == 'template') continue;

	if (!empty($param['index']) && !in_array($index,$target_indexes)) continue;
        
        // Fetch document rows and size statistics
        $status_data = $rt->getAssoc("SHOW INDEX $index STATUS");
        $docs = $status_data['indexed_documents'] ?? 0;
        $bytes = $status_data['disk_bytes'] ?? 0;
        
        // Check cluster status
        $in_cluster = in_array($index, $cluster_indexes) ? "YES" : "NO";
        
        // Fetch the raw schema generation string
        // Manticore returns this as a two-column row: ['Table' => '...', 'Create Table' => '...']
        $create_row = $rt->getRow("SHOW CREATE TABLE `$index`");
        $create_statement = $create_row['Create Table'] ?? $create_row['Create Index'] ?? "-- Error fetching schema";

$create_statement = str_replace("\n","\n\t", $create_statement);
$create_statement = str_replace("\n\t)","\n)", $create_statement);

        // Output structured block with metadata comments cleanly formatted
        echo "-- ".str_repeat('-',60)."\n";
        echo "-- INDEX: $index\n";
        echo "-- Documents: $docs\n";
        echo "-- Disk Size: $bytes bytes\n";
        echo "-- Replicated In Cluster: $in_cluster\n\n";
        echo trim($create_statement) . ";\n\n";
    }
    exit(0);
}

###############################################################
// Run Main Indexing Block Loop

foreach ($target_indexes as $index) {
    echo "=== Processing Index: $index ===\n";

    ######################################

    if ($index == 'caption') {
	if (!empty($param['delta'])) { echo "-> Skipping: No delta mode configured for $index.\n\n"; continue; }

        $cmd = "php scripts/injectrt.php --index=caption --select=\"select gridimage_id as id,title,user_id,realname,group_concat(if(type='normal',caption,null)) as caption,group_concat(if(type='tags',caption,null)) as labels,grid_reference,tags from gridimage_search inner join gridimage_caption using (gridimage_id) where type in ('normal','tags') group by gridimage_id\" --schema --limit=0";
        if (isset($manticore_tables[$index])) $cmd .= " --drop";
        execute($cmd, $param['dry']);
    }
    ######################################

    elseif ($index == 'gallery_ids') {
        if (!empty($param['delta'])) {
		//NOTE, the view has a hardcoded LIMIT 10! - its NOT using the --delta tracker!
            $cmd = "php scripts/injectrt-delta-function.php --table=gallery_view_delta --index=gallery_ids";
        } else {
		//index definition has been compressed into a view!
            $cmd = "php scripts/injectrt.php --table=gallery_view --index=gallery_ids --schema --limit=0";
   	    if (isset($manticore_tables[$index])) $cmd .= " --drop";
        }
        execute($cmd, $param['dry']);
    }
    ######################################

    elseif ($index == 'gaz') {
	if (!empty($param['delta'])) { echo "-> Skipping: No delta mode configured for $index.\n\n"; continue; }

        //can read the definition from the legacy 'plain' index definition!
        $cmd = "php scripts/injectrt.php --file=sphinx.conf.d/gaz.conf --schema --limit=0";
        if (isset($manticore_tables[$index])) $cmd .= " --drop";
        execute($cmd, $param['dry']);
    }

    ######################################

    elseif ($index == 'gridimage_group_stat') {

	//this has a dedicated PHP script, because the data is inserted directly into the RT index
        if (!empty($param['delta'])) {
		//this should already setup in cron!
	    echo "-> Skipping: Should still be setup in CRON $index.\n\n"; continue;

		//the raw data clustering happens, then the data is aggregated by injectrt-gridimage_group_stat-delta.php
            $cmd = "php scripts/square-cluster.php --count=1000 --execute --sleep=3".
	       " && php scripts/injectrt-gridimage_group_stat-delta.php";
        } else {
//NOT CHECKED YET (the full mode, needs careful testing!)

            $cmd = "php scripts/injectrt-gridimage_group_stat.php";
        }
        execute($cmd, $param['dry']);
    }

    ######################################

    elseif ($index == 'gridprefix') {
	//the table is small enough, we can just recreate the whole table, even in delta mode!
	//if (!empty($param['delta'])) { echo "-> Skipping: No delta mode configured for $index.\n\n"; continue; }

	$query = "SELECT NULL AS id, reference_index, prefix, origin_x, origin_y, landcount, boundary, labelcentre, UNIX_TIMESTAMP(last_timestamp) AS last_timestamp, imagecount, geosquares
	 FROM gridprefix";
	$query = escapeshellarg(trim(preg_replace('/\s+/',' ',$query)));
	$cmd = "php scripts/injectrt.php --schema --select=$query --index=gridprefix --limit=0";

	if (isset($manticore_tables[$index])) $cmd .= " --drop";
        execute($cmd, $param['dry']);
    }

    ######################################

    elseif ($index == 'gridsquare') {
        if (!empty($param['delta'])) {
            $cmd = "php scripts/injectrt-delta-function.php --table=gridsquare --delta=last_timestamp --limit=1000";
        } else {
		//we still pass --delta param, because want to WRITE the value, to can be USED by the delta function above!
            $cmd = "php scripts/injectrt.php --table=gridsquare --schema --limit=0 --delta=last_timestamp";
   	    if (isset($manticore_tables[$index])) $cmd .= " --drop";
        }
        execute($cmd, $param['dry']);
    }

    ######################################

    elseif ($index == 'index_coverage') {
	//this was a test, not formalized yet!
        echo "-> No build logic defined yet.\n\n";
    }

    ######################################

    elseif ($index == 'loc_placenames') {
        if (!empty($param['delta'])) { echo "-> Skipping: No delta mode configured for $index.\n\n"; continue; }

        $cmd = "php scripts/injectrt.php --select=\"select id,dsg,p.adm1,loc_adm1.name as adm1_name,full_name,nametype,e,n,p.reference_index,p.country,has_dup,gns_ufi from loc_placenames p left join loc_adm1 on (p.adm1 = loc_adm1.adm1 and loc_adm1.country = p.country)\" --index=loc_placenames --schema --limit=0";
        if (isset($manticore_tables[$index])) $cmd .= " --drop";
        execute($cmd, $param['dry']);
    }
    ######################################

    //these tables, actully can rely on injectrt.php ability to generate the schema automatically from the table
	// and have no special setup for delta yet
    elseif ($index == 'ie_open_data' || $index == 'os_gaz' || $index == 'os_gaz_250' || $index == 'tag_stat') {

        if (!empty($param['delta'])) { echo "-> Skipping: No delta mode configured for $index .\n\n"; continue; }

        $cmd = "php scripts/injectrt.php --schema --table=$index --limit=0";
        if (isset($manticore_tables[$index])) $cmd .= " --drop";
        execute($cmd, $param['dry']);
    }
}

###############################################################################

function execute($cmd, $dump_mode) {
    global $param;
    $cmd .= " --config=".$param['config'];

    if ($dump_mode) {
        echo "#DRY RUN:\n  $cmd\n\n";
        return;
    }

	//not dry run anymore!
    $cmd .= " --execute=2";

	global $start_dir;
	chdir($start_dir);

    echo "Executing command:\n  $cmd\n";
    passthru($cmd, $return_var);

    if ($return_var === 0) {
        echo "-> Status: Success\n\n";
    } else {
        echo "-> Status: Failed (Exit code $return_var)\n\n";
    }
}
