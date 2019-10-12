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


        $param = array('type'=>'typo','size'=>10000,'print'=>0);

        chdir(__DIR__);
        require "./_scripts.inc.php";

#####################

set_time_limit(3600*24);

$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$type = $param['type'];

$where = array();

$last_id = $db->getOne("SELECT MAX(gridimage_id) FROM gridimage_search");

$where[] = 'gridimage_id > '.($last_id-$param['size']);

$where[] = "include!=''";
$where[] = "exclude=''";
$where[] = "enabled = 1";
$where[] = "profile in ('phrase','either')";
$where[] = "t.updated < DATE_SUB(NOW(),INTERVAL 3 HOUR)";

$where= implode(' AND ',$where);

$sql = "
SELECT typo_id,include,gridimage_id
FROM `typo` t
INNER JOIN `gridimage_search` gi ON ( `comment` LIKE CONCAT('%',include,'%') OR IF(t.title=1,gi.title LIKE CONCAT('%',include,'%'),0) )
WHERE $where";
print "$sql;\n";

if ($param['print'])
	exit;

$rows = $db->getAll($sql);

if (!empty($rows)) {
	if ($db->readonly) {
		$db = GeographDatabaseConnection(false);
	}

	$typos = array();
	foreach ($rows as $row) {
		$id = $row['gridimage_id'];
		$word = $db->Quote($row['include']);
		$sql = "INSERT INTO gridimage_typo SET gridimage_id = $id,created=NOW(),`word` = $word,type='$type' ON DUPLICATE KEY UPDATE updated = NOW(),`word` = $word";
		$db->Execute($sql);

		@$typos[$row['typo_id']]++;
	}

	foreach ($typos as $typo_id => $count) {
		$sql = "UPDATE typo SET
			last_results = $count,
			last_time=NOW(),
			last_size={$param['size']},
			last_gridimage_id=$last_id,
			last_user_id=0,
			total_results=total_results+$count,
			total_runs = total_runs + 1
			WHERE typo_id = $typo_id";
		$db->Execute($sql);
		print "$sql;\n";
	}

}

print "done\n";
