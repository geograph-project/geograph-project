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
 * Helper script for database administrators.
 *
 * This script scans the 'schema/procedures/' directory for .sql files containing
 * stored procedures and checks if they have been installed in the database.
 *
 * For each .sql file found, it derives a procedure name from the filename and
 * checks for its existence. If the procedure is not found, it notifies the
 * admin and provides the 'source' command to install it using the mysql client.
 */

chdir(__DIR__);
require "./_scripts.inc.php";

echo "Checking for uninstalled stored procedures...\n";

// Establish a database connection.
$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

// Get a list of all existing stored procedures in the current database.
$existing_procedures_raw = $db->GetAll("SHOW PROCEDURE STATUS WHERE Db = DATABASE()");
$existing_procedures = [];
foreach ($existing_procedures_raw as $proc) {
    $existing_procedures[$proc['Name']] = true;
}

// Define the path to the procedures directory.
$procedures_dir = __DIR__ . '/../schema/procedures/';
$sql_files = glob($procedures_dir . '*.sql');

if (empty($sql_files)) {
    echo "No .sql files found in 'schema/procedures/'.\n";
    exit(0);
}

$missing_count = 0;

// Loop through all .sql files in the procedures directory.
foreach ($sql_files as $file_path) {
    // Derive the expected procedure name from the filename.
    // e.g., 'rebuild_snippet_duplicate.sql' -> 'rebuild_snippet_duplicate'
    // This convention needs to be followed for new procedures.
    $procedure_name = basename($file_path, '.sql');

    // The first procedure created had a different naming convention.
    // vision_rotate_ids.sql -> rotate_vision_ids()
    if ($procedure_name === 'vision_rotate_ids') {
        $procedure_name = 'rotate_vision_ids';
    }

    // Check if the procedure exists in the database.
    if (!isset($existing_procedures[$procedure_name])) {
        $missing_count++;
        echo "--------------------------------------------------\n";
        echo "MISSING Procedure: '$procedure_name'\n";
        echo "Source file:       '$file_path'\n\n";

        // Prompt the user to import the script.
        echo "This procedure appears to be uninstalled. Do you want to install it? [y/N] ";
        $handle = fopen("php://stdin", "r");
        $line = strtolower(trim(fgets($handle)));
        fclose($handle);

        if ($line === 'y' || $line === 'yes') {
            echo "To install, please run the following command from your shell:\n";
            echo "\n    mysql --user=USER --password=PASS --database=" . $db->database . " < " . realpath($file_path) . "\n\n";
            echo "Note: Replace USER and PASS with the appropriate database credentials.\n";
            echo "The script cannot perform the import directly as it does not have access to credentials\n";
            echo "and cannot robustly handle SQL files with custom delimiters.\n";
        } else {
            echo "Skipping installation.\n";
        }
    }
}

if ($missing_count === 0) {
    echo "All found procedures are already installed.\n";
} else {
    echo "--------------------------------------------------\n";
    echo "Found $missing_count missing procedure(s).\n";
}

echo "Check complete.\n";
