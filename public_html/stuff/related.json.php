<?php
/**
 * $Project: GeoGraph $
 * $Id: conversion.php 5502 2009-05-13 14:18:23Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 BArry Hunter (geo@barryhunter.co.uk)
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

require_once('geograph/global.inc.php');



if (!empty($_GET['id'])) {
	$id = intval($_GET['id']);
} else {
	die("'unknown id'");
}

$sph = GeographSphinxConnection('sphinxql', true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$rows = queryQL('myriad,hectad,grid_reference,takenyear,takenmonth,takenday,groups,tags,types,contexts,snippets,subjects,place,county,country,scenti,user_id,realname,imageclass',
		'', array("id=$id"));

if (empty($rows))
	$rows = rowsFromDB($id);

if (!empty($rows)) {
	$row = $rows[0];

        $required = array();
        $optional = array();

	//NOte this is only only emulating the defautl 'related' mode, JS can do other modes too!

		$required[] = $row['myriad'];
		$optional[] = $row['hectad'];

	$optional[] = $row['grid_reference'];

	@$optional[] = $row['takenyear'];
	@$optional[] = $row['takenmonth'];
	@$optional[] = $row['takenday'];

	$splits = array('contexts','groups','tags','snippets','subjects');
	foreach ($splits as $split)
		if (!empty($row[$split]) && strlen($row[$split]) > 5) {
			$list = explode(' _SEP_ ',preg_replace('/(top|subject):/','',preg_replace('/(^\s*_SEP_\s*|\s*_SEP_\s*$)/','', $row[$split])));
			foreach ($list as $bit)
				$optional[] = '"'.$bit.'"';
		}

	if (!empty($row['place']) && strlen($row['place']) > 2)
		$optional[] = '"'.$row['place'].'"';
	if (!empty($row['county']) && strlen($row['county']) > 2)
		$optional[] = '"'.$row['county'].'"';
	if (!empty($row['country']) && strlen($row['country']) > 2)
		$optional[] = '"'.$row['country'].'"';

	$optional[] = 'user'.$row['user_id'];

	if (!empty($row['imageclass']) && strlen($row['imageclass']) > 2)
		$optional[] = '"'.$row['imageclass'].'"';

	$match = implode(" ",$required);
	$match .= " (".implode('|',$optional).")";


	$rows = queryQL('id,title,myriad,hectad,grid_reference,takenyear,takenmonth,takenday,hash,realname,user_id,place,county,country,hash,scenti',
                $match, array("id!=$id"), 10);

	outputJSON($rows);

} else {
	die("'no results'");

}

###############################

function queryQL($select='id', $query='', $where=array(), $limit = 10) {
	global $sph;

	if (!empty($query))
		$where[] = "MATCH(".$sph->Quote($query).")";

	return $sph->getAll("SELECT $select
		FROM sample8
		WHERE ".implode(" AND ",$where)."
		LIMIT $limit
		OPTION max_query_time = 10000");
}

function rowsFromDB($id) {
	global $ADODB_FETCH_MODE;

	$db = GeographDatabaseConnection(true);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	return $db->getRow("SELECT
        SUBSTRING(gi.grid_reference,1,LENGTH(gi.grid_reference)-4) AS myriad,
        CONCAT(SUBSTRING(gi.grid_reference,1,LENGTH(gi.grid_reference)-3),SUBSTRING(gi.grid_reference,LENGTH(gi.grid_reference)-1,1)) AS hectad,
        grid_reference,
        year(imagetaken) AS takenyear,REPLACE(SUBSTRING(imagetaken,1,7),'-','') AS takenmonth,REPLACE(imagetaken,'-','') AS takenday,
        REPLACE(tags,'?',' _SEP_ ') AS tags,user_id,imageclass
	 FROM gridimage_search gi WHERE gridimage_id = ".$id);
}
