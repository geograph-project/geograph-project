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

$param = array('execute'=>0,'table'=>'content','table_id'=>'');

chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

###############################################################


$table = $param['table'];

if (empty($param['table_id'])) {
	$table_id = preg_replace('/\w+\./','',$param['table'])."_id";
} else {
	$table_id =$param['table_id'];;
}


$extra = '';
$found = false;
foreach ($db->getAssoc("DESCRIBE $table") as $column => $row) {
	if (stripos($row['Extra'],'CURRENT_TIMESTAMP') !== FALSE) {
		$extra .= ", $column=$column";
	}
	if ($column == 'sequence')
		$found=1;
}

if (!$found) {
	$sql = "alter table $table add `sequence` int(10) unsigned DEFAULT NULL";
	die("No sequence column found on $table\?!\nmaybe run: $sql\n");
}

###############################################################
# Cut down version of full sequence system. (this works ok, but not effienct enough for millions of rows!)
#   ... also this version doesnt try to maniuplate which is chosen first (because no mysql WITHIN GROUP ORDER BY)

//need the unique index on table_id, as we abuse a auto-incrment key to get a running sequence
$db->Execute($sql = "create temporary table square1 ($table_id int unsigned unique, sequence int unsigned not null auto_increment primary key)") or die("$sql;\n".mysql_error()."\n\n");

$group = 'round( (wgs84_long+90.0)*pow($d+1,1.4) ), round( (wgs84_lat)*pow($d+1,1.4) )';
$max =30;

##print "$sql;\n\n";

$d = 0;
$loop=0;
while(1) {
	// need to insert into a new table, as it cant insert into same table as selecting

	$db->Execute($sql = "create temporary table square2
			select $table_id
			from $table left join square1 using ($table_id)
			where wgs84_lat > 0 AND square1.$table_id IS NULL
			group by ".str_replace('$d',$d,$group)."
			order by rand()") or die("$sql;\n".mysql_error()."\n\n");

print "$sql;\n\n";

        $rows = mysql_affected_rows();
        print "$loop, ".date('r')." F=".$rows."\n";

	$db->Execute($sql = "INSERT INTO square1 SELECT $table_id,NULL AS sequence FROM square2");

##print "$sql;\n\n";

	$db->Execute("DROP temporary TABLE square2");

	if (empty($param['execute']))
		break;
##exit;
        if (empty($rows))
                break;

        if ($d < $max)
                $d++;

        $loop++;
}


###############################################################

$sql = "update $table inner join square1 using ($table_id) set $table_id.sequence = square1.sequence  $extra";

print "$sql;\n";

if (empty($param['execute']))
	exit;

###############################################################

$db->Execute("update $table inner join square1 using ($table_id) set $table.sequence = square1.sequence  $extra");
$rows = mysql_affected_rows();
print "Finished, ".date('r')." Affected=".$rows."\n";

###############################################################
