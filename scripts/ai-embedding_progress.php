<?php
/**
 * $Project: GeoGraph $
 * $Id: conversion.php 2960 2007-01-15 14:33:27Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2008 Barry Hunter (geo@barryhunter.co.uk)
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
$param=array(
	'source'=>'gridimage_embedding_1024',
	'table'=>'embedding_progress_pe',
	'type'=>'image',
	'model'=>'pe',
	'group'=>'SUBSTRING(updated, 1, 10) AS day', //for _by_id, would be 'gridimage_id div 100000 as shard' for example (applied automatically!)
	'execute'=>false,
);

chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

############################

if(strpos($param['table'],'_by_id') !== FALSE && $param['group'] == 'SUBSTRING(updated, 1, 10) AS day') //-- ie still the original default
        $param['group'] = 'gridimage_id div 100000 as shard';


// 1. Query the table comment from the schema
$checkSql = "SELECT TABLE_COMMENT
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = ?";

$comment = $db->GetOne($checkSql, [$param['table']]);

if (!empty($comment)) {
    // Regex breakdown:
    // FROM (.*?)      -> Captures source table
    // WHERE type=(.*?) -> Captures type (even if empty)
    // AND model=(.*?)  -> Captures model
    // GROUP BY (.*)    -> Captures the rest of the string as the group expression
    $pattern = "/FROM (.*?) WHERE type=(.*?) AND model=(.*?) GROUP BY (.*)/i";

    if (preg_match($pattern, $comment, $matches)) {
        // Need to set values, to override the defaults in config array! (does mean can't accidently (by specifing on command line) use different settings with an existing table :)
        $param['source'] = trim($matches[1]);
        $param['type']   = trim($matches[2]);
        $param['model']  = trim($matches[3]);
        $param['group']  = trim($matches[4]);

        print "INFO: Auto-configured from table comment:\n";
        print "      Source: {$param['source']} | Model: {$param['model']} | Type: '{$param['type']}' | Group: '{$param['group']}'\n";
    }
}
############################

upsertEmbeddingProgress($db, $param['source'], $param['table'], $param['type'], $param['model'], $param['group']);



/**
 * Inserts or updates progress into an embedding tracking table using a SELECT from a source table.
 *
 * @param ADOConnection $db The ADODB database connection object.
 * @param string $sourceTable The name of the table to select data from (e.g., 'gridimage_embedding_1024').
 * @param string $progressTable The name of the table to insert/update into (e.g., 'embedding_progress_pe').
 * @param string $type The 'type' filter for the source table (e.g., 'image').
 * @param string $model The 'model' filter for the source table (e.g., 'pe').
 * @param string $groupByExpr The expression to use in the GROUP BY clause (e.g., 'SUBSTRING(updated, 1, 10) AS day' or 'gridimage_id DIV 100000 AS shard').
 * @return bool True on success, false on failure.
 */
function upsertEmbeddingProgress(
    ADOConnection $db,
    string $sourceTable,
    string $progressTable,
    string $type,
    string $model,
    string $groupByExpr = 'SUBSTRING(updated, 1, 10) AS day'
): bool {
	global $param;

    // 1. Determine the column name from the GROUP BY expression for the INSERT list and PK
    // Safely parse the column name/alias from the expression.
    $upperExpr = strtoupper($groupByExpr);
    $aliasPos = strrpos($upperExpr, ' AS ');
    
    if ($aliasPos !== false) {
        // If ' AS ' is found, everything after it is the alias/column name
        $groupByCol = trim(substr($groupByExpr, $aliasPos + 4));
    } else {
        // If no ' AS ' is found, assume the entire expression is the column name (e.g., 'day')
        $groupByCol = trim($groupByExpr);
    }
    
    // Fallback/validation: Ensure it's not empty, otherwise default to 'day'
    if (empty($groupByCol)) {
        $groupByCol = 'day';
    }

    // You are correct: we'll assume VARCHAR(10) is a reasonable default type for this
    // primary key column, covering both dates and small integer 'shard' IDs.
    $pkDataType = 'VARCHAR(10)'; 
    
    // 2. Ensure the output/progress table exists
    // Use the dynamically determined column name ($groupByCol) in the DDL.
    $createTableSql = "
        CREATE TABLE IF NOT EXISTS `$progressTable` (
            `$groupByCol` $pkDataType NOT NULL,
            count INT UNSIGNED NOT NULL DEFAULT 0,
            min_id BIGINT UNSIGNED NOT NULL,
            max_id BIGINT UNSIGNED NOT NULL,
            done TINYINT(1) NULL DEFAULT NULL,
            PRIMARY KEY (`$groupByCol`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT=".$db->Quote("FROM $sourceTable WHERE type=$type AND model=$model GROUP BY $groupByExpr");
	//the ocmment isnt valid SQL, just aiming to recoud the source!
	print "--Create (will run)\n";

	print_r("$createTableSql;\n");

    if (!$db->Execute($createTableSql)) {
        error_log("Failed to create table $progressTable: " . $db->ErrorMsg());
        return false;
    }

    // 3. Prepare the main UPSERT statement

    //Handle Model (Always exists)
    $whereClauses = ["model = ?"];
    $params = [$model];

    //Handle Type (Optional)
    if (!empty($type)) {
        $whereClauses[] = "type = ?";
        $params[] = $type;
    }

    //Handle Static Filter (No '?' here, so no param added)
//the query optimizer no longer uses the key with a query ! (it used to!)
$db->Execute("SELECT @max_done := COALESCE(MAX(max_id), 0) FROM $progressTable");
    $whereClauses[] = "seq_id > @max_done";

    $whereSql = implode(" AND ", $whereClauses);

    $sql = "
        INSERT INTO `$progressTable`
            (`$groupByCol`, count, min_id, max_id, done)
        SELECT
            $groupByExpr,
            COUNT(*) AS new_count,
            MIN(seq_id) AS new_min_id,
            MAX(seq_id) AS new_max_id,
            NULL AS done
        FROM
            $sourceTable
        WHERE
            $whereSql
        GROUP BY
            `$groupByCol`
        ON DUPLICATE KEY UPDATE
            count = `$progressTable`.count + VALUES(count),
            max_id = VALUES(max_id),
            `done` = NULL;
    ";

if (empty($param['execute'])) {
	print "--Query (not run)\n";
	print_r(emulate_adodb_query_for_debug($db, $sql,$params).";\n");
	exit;
}
    $result = $db->Execute($sql, $params);

    if (!$result) {
        error_log("Failed to execute UPSERT query: " . $db->ErrorMsg());
        return false;
    }

    print "  -- Affected: ".$db->Affected_Rows()."\n";

    return true;
}




function emulate_adodb_query_for_debug(ADOConnection $db, string $sql, array $params): string {
    // Escape the parameters using the database connection's Quoting function (e.g., $db->qstr)
    $quoted_params = array_map(function($param) use ($db) {
        // Use ADODB's Quote() method to properly handle strings, numbers, and NULLs
        return $db->Quote($param);
    }, $params);

    // Split the SQL string by the '?' placeholder
    $sql_parts = explode('?', $sql);

    // If the number of placeholders doesn't match the number of parameters, something is wrong
    if (count($sql_parts) !== count($quoted_params) + 1) {
        // Return original SQL with a warning message
        return "/* WARNING: Parameter count mismatch! */\n" . $sql;
    }

    $final_sql = '';
    
    // Stitch the SQL parts and quoted parameters back together
    for ($i = 0; $i < count($quoted_params); $i++) {
        // Append SQL part $i, then parameter $i
        $final_sql .= $sql_parts[$i] . $quoted_params[$i];
    }
    
    // Append the final SQL part (which follows the last '?' or is the whole string if no '?')
    $final_sql .= end($sql_parts);
    
    return $final_sql;
}
