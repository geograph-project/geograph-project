<?php
/**
 * $Project: GeoGraph $
 * $Id: recreate_maps.php 2996 2007-01-20 21:39:07Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Barry Hunter (geo@barryhunter.co.uk)
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

$param = array('debug'=>false,'table'=>'ni_counties','ri'=>2, 'limit'=>10,'exact'=>false);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

############################################

if ($param['debug'])
	print "Starting. ".date('r')."\n";

############################################

$table = "{$param['table']}_hectad";

//for now assumes the pkey on original table is called 'auto_id'!

if (!$db->getOne("SHOW TABLES LIKE '$table'")) {
	$db->Execute("create table $table (
	auto_id int unsigned not null,
	hectad varchar(7) not null,
	WKT GEOMETRY NOT NULL,
	area int unsigned not null,
	bound_images int unsigned default null,
	stat_updated timestamp not null on update current_timestamp(),
	primary key(auto_id,hectad),
	SPATIAL INDEX(WKT)
	)");
		//note, cany use a polygon columnt type, as it MAY be a multipolygon!
}

############################################


$rows = $db->getAll("SELECT auto_id FROM {$param['table']} LEFT JOIN $table USING (auto_id) WHERE $table.auto_id IS NULL LIMIT {$param['limit']}");

foreach ($rows as $row) {

		//by ensuring only one row on the left table, should use the, using MBRIntersects for very quick 'initial' join against all hectads!
	$sql = "select auto_id,hectad,ST_INTERSECTION(WKT,boundary_en) AS `WKT`,ROUND(ST_AREA(ST_INTERSECTION(WKT,boundary_en))) AS area, NULL AS bound_images, 0 as stat_updated
	FROM {$param['table']} INNER JOIN hectad_boundary ON MBRIntersects(boundary_en,WKT)
	WHERE auto_id = {$row['auto_id']} AND reference_index = {$param['ri']}";

	if ($param['debug'] === "2")
		die("$sql;\n");

	if ($param['exact']) {
		// actually, still using the GIS index for the join! but then post filters anyway!
		//$sql .= " AND ST_AREA(ST_INTERSECTION(WKT,boundary_en)) > 0";
		$sql .= " AND ST_INTERSECTS(WKT,boundary_en)";
	}

	$sql = "INSERT INTO $table $sql";
	//print "$sql;\n";
	$db->Execute($sql);
	print "{$row['auto_id']}. Rows = ".$db->Affected_Rows()."\n";
}
print "\n";

if ($param['debug'])
	print "Done. ".date('r')."\n";

############################################

if (!$param['exact'] && empty($rows)) {
	//because we join USING MBR (for better query performance) - can end up with hectads that dont actully overlapp the exact boundary

	//but only want to do it right at end, once no more created. If doing it ongoing, might delete rows that get recreated each time

	$sql = "DELETE FROM $table WHERE area = 0";
	$db->Execute($sql);

	print "Deleted Rows = ".$db->Affected_Rows()."\n";
}
