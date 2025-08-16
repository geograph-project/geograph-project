<?php
/**
 * $Project: GeoGraph $
 *
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2023 Jules (jules@geograph.org.uk)
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

/**
 * Testing script for stored procedures.
 *
 * This script scans the 'schema/procedures/' directory for .sql files.
 * If a file contains a special '@@TEST_METADATA@@' block, this script
 * will perform a before-and-after test on the procedure.
 *
 * The test consists of:
 * 1. Saving a snapshot of the first 100 rows of the target table.
 * 2. Calling the stored procedure to run it once.
 * 3. Saving another snapshot of the first 100 rows.
 * 4. Running 'diff' on the two snapshots to show changes.
 *
 * This script assumes the procedures are already installed. Use the
 * 'check_and_install_procedures.php' script to install them first.
 */

chdir(__DIR__);
require "./_scripts.inc.php";

echo "--- Stored Procedure Test Runner ---\n";

$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$procedures_dir = __DIR__ . '/../schema/procedures/';
$sql_files = glob($procedures_dir . '*.sql');

if (empty($sql_files)) {
    echo "No .sql files found in 'schema/procedures/'.\n";
    exit(0);
}

foreach ($sql_files as $file_path) {
    $procedure_name = basename($file_path, '.sql');
    // Handle the special case for the first procedure.
    if ($procedure_name === 'vision_rotate_ids') {
        $procedure_name = 'rotate_vision_ids';
    }

    echo "\n----------------------------------------\n";
    echo "Processing: $procedure_name\n";

    $metadata = parse_metadata($file_path);

    if (!$metadata) {
        echo "Skipping: No test metadata found.\n";
        continue;
    }

    echo "Test metadata found for target table '{$metadata['TARGET_TABLE']}'.\n";

    // Check if the target table exists before proceeding.
    $table_exists = $db->GetOne("SHOW TABLES LIKE '{$metadata['TARGET_TABLE']}'");
    if (!$table_exists) {
        echo "Skipping: Target table '{$metadata['TARGET_TABLE']}' does not exist.\n";
        continue;
    }

    $before_file = $procedures_dir . $metadata['TARGET_TABLE'] . '.before.csv';
    $after_file = $procedures_dir . $metadata['TARGET_TABLE'] . '.after.csv';

    // 1. BEFORE snapshot
    echo "Creating BEFORE snapshot...\n";
    if (!export_table_snapshot($db, $metadata, $before_file)) {
        continue;
    }

    // 2. RUN procedure
    echo "Running procedure: CALL $procedure_name()\n";
    $db->Execute("CALL $procedure_name()");
    echo "Procedure finished.\n";

    // 3. AFTER snapshot
    echo "Creating AFTER snapshot...\n";
    if (!export_table_snapshot($db, $metadata, $after_file)) {
        unlink($before_file); // Clean up
        continue;
    }

    // 4. DIFF
    echo "Comparing snapshots:\n";
    $diff_output = shell_exec("diff -u \"$before_file\" \"$after_file\"");
    if (empty($diff_output)) {
        echo "OK: No differences found.\n";
    } else {
        echo $diff_output;
    }

    // 5. Clean up
    unlink($before_file);
    unlink($after_file);
}

echo "\n----------------------------------------\n";
echo "Test run complete.\n";


/**
 * Parses the metadata block from a SQL file.
 *
 * @param string $file_path Path to the SQL file.
 * @return array|false An array with metadata or false if not found.
 */
function parse_metadata($file_path) {
    $content = file_get_contents($file_path);
    if (!preg_match('/-- @@TEST_METADATA@@(.*?)-- @@END_TEST_METADATA@@/s', $content, $matches)) {
        return false;
    }
    $metadata_block = $matches[1];
    $metadata = [];
    if (preg_match('/-- TARGET_TABLE: ([\w_]+)/', $metadata_block, $table_match)) {
        $metadata['TARGET_TABLE'] = trim($table_match[1]);
    }
    if (preg_match('/-- PRIMARY_KEY: ([\w_,]+)/', $metadata_block, $key_match)) {
        $metadata['PRIMARY_KEY'] = trim($key_match[1]);
    }
    if (isset($metadata['TARGET_TABLE']) && isset($metadata['PRIMARY_KEY'])) {
        return $metadata;
    }
    return false;
}

/**
 * Exports a snapshot of a table to a CSV file.
 *
 * @param ADOConnection $db The database connection.
 * @param array $metadata The metadata array.
 * @param string $filename The output filename.
 * @return bool True on success, false on failure.
 */
function export_table_snapshot($db, $metadata, $filename) {
    $sql = "SELECT * FROM `{$metadata['TARGET_TABLE']}` ORDER BY {$metadata['PRIMARY_KEY']} LIMIT 100";
    $result = $db->Execute($sql);
    if (!$result) {
        echo "Error running query: " . $db->ErrorMsg() . "\n";
        return false;
    }

    $handle = fopen($filename, 'w');
    if (!$handle) {
        echo "Error: Could not open file for writing: $filename\n";
        return false;
    }

    $header_written = false;
    while ($row = $result->FetchRow()) {
        if (!$header_written) {
            fputcsv($handle, array_keys($row));
            $header_written = true;
        }
        fputcsv($handle, $row);
    }

    fclose($handle);
    return true;
}
