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

$param = array('execute'=>0,'table'=>'content','table_id'=>'','where'=>'');

chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

###############################################################


$table = $param['table'];

if (empty($param['table_id'])) {
	//todo, this should perhaps be detected as the 'PRI' column from DESCRIBE
	$table_id = preg_replace('/\w+\./','',$param['table'])."_id";
} else {
	$table_id =$param['table_id'];
}

$extra = '';
$found = $foundkey = false;
foreach ($db->getAssoc("DESCRIBE $table") as $column => $row) {
	if (stripos($row['Extra'],'CURRENT_TIMESTAMP') !== FALSE) {
		$extra .= ", $column=$column";
	}
	if ($column == 'sequence')
		$found=1;
	if ($column == $table_id)
		$foundkey=1;
}

if (!$found) {
	$sql = "alter table $table add `sequence` int(10) unsigned DEFAULT NULL";
	die("No sequence column found on $table\?!\nmaybe run: $sql\n");
}
if (!$foundkey) {
	die("Column $table.$table_id not found\n");
}

###############################################################
# Cut down version of full sequence system. (this works ok, but not effienct enough for millions of rows!)
#   ... also this version doesnt try to maniuplate which is chosen first (because no mysql WITHIN GROUP ORDER BY)



// 1. Load the dataset into memory
$sql = "SELECT $table_id as id, wgs84_lat as lat, wgs84_long as lon FROM $table WHERE wgs84_lat > 0";
if (!empty($param['where'])) $sql .= " AND " . $param['where'];

$dataset = $db->GetAll($sql);
$total_rows = count($dataset);

$sequenced_ids = []; // Stores our results in order: [id1, id2, id3...]
$already_picked = []; // Fast lookup for IDs we've already used
$d = 0;
$max_d = 30;

// 2. The Diversity Loop
while (count($sequenced_ids) < $total_rows) {
    $buckets = [];
    $found_in_round = 0;

    foreach ($dataset as $row) {
        $id = $row['id'];
        
        // Skip if already picked in a previous round
        if (isset($already_picked[$id])) continue;

        // Calculate the grid bucket key (replicating your SQL math)
        // Formula: round( (long + 90) * pow(d+1, 1.4) )
        $scale = pow($d + 1, 1.4);
        $key_lon = round(($row['lon'] + 90.0) * $scale);
        $key_lat = round($row['lat'] * $scale);
        $bucket_key = $key_lon . '|' . $key_lat;

        // Collect rows into buckets
        $buckets[$bucket_key][] = $id;
    }

    // No more rows left to pick?
    if (empty($buckets)) break;

    // 3. Pick one random ID from each bucket (Simulating GROUP BY + ORDER BY RAND())
    foreach ($buckets as $bucket_ids) {
        $winner = $bucket_ids[array_rand($bucket_ids)];
        $sequenced_ids[] = $winner;
        $already_picked[$winner] = true;
        $found_in_round++;
    }

    echo "Round D=$d: Picked $found_in_round rows. Total: " . count($sequenced_ids) . "\n";

    if ($d < $max_d) {
        $d++;
    }
}

// 4. Bulk Update back to MySQL
if (!empty($sequenced_ids)) {
    echo "Generating update queries...\n";
    
    // We update in chunks to avoid hitting max_allowed_packet limits
    $chunks = array_chunk($sequenced_ids, 5000, true);
    $counter = 1;

    foreach ($chunks as $chunk) {
        $cases = "";
        $ids = [];
        foreach ($chunk as $id) {
            $cases .= "WHEN $id THEN $counter ";
            $ids[] = $id;
            $counter++;
        }
        
        $ids_list = implode(',', $ids);
        $update_sql = "UPDATE $table 
                       SET sequence = CASE $table_id 
                       $cases 
                       END 
                       WHERE $table_id IN ($ids_list)";
        
        if (!empty($param['execute'])) {
            $db->Execute($update_sql);
        } else {
            echo $update_sql . ";\n\n";
        }
    }
}

















//need the unique index on table_id, as we abuse a auto-incrment key to get a running sequence
$db->Execute($sql = "create temporary table square1 ($table_id int unsigned unique, sequence int unsigned not null auto_increment primary key)") or die("$sql;\n".$db->ErrorMsg()."\n\n");

$group = 'round( (wgs84_long+90.0)*pow($d+1,1.4) ), round( (wgs84_lat)*pow($d+1,1.4) )';
$max =30;

##print "$sql;\n\n";

$where = "wgs84_lat > 0 AND square1.$table_id IS NULL";
if (!empty($param['where']))
	$where .= " AND ".$param['where'];

$d = 0;
$loop=0;
while(1) {
	// need to insert into a new table, as it cant insert into same table as selecting

	$db->Execute($sql = "create temporary table square2
			select $table_id
			from $table left join square1 using ($table_id)
			where $where
			group by ".str_replace('$d',$d,$group)."
			order by rand()") or die("$sql;\n".$db->ErrorMsg()."\n\n");

	if (empty($param['execute']))
		print "$sql;\n\n";

        $rows = $db->Affected_Rows();
        print "$loop, ".date('r')." F=".$rows."\n";

	$db->Execute($sql = "INSERT INTO square1 SELECT $table_id,NULL AS sequence FROM square2");

	if (empty($param['execute']))
		print "$sql;\n\n";

	$db->Execute("DROP temporary TABLE square2");

	if (empty($param['execute']))
		break;

        if (empty($rows))
                break;

        if ($d < $max)
                $d++;

        $loop++;
}


###############################################################

$sql = "update $table inner join square1 using ($table_id) set $table.sequence = square1.sequence  $extra";

print "$sql;\n";

if (empty($param['execute']))
	exit;

###############################################################

$db->Execute($sql);
$rows = $db->Affected_Rows();
print "Finished, ".date('r')." Affected=".$rows."\n";

###############################################################
