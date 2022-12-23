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
	die('{"error":"unknown id"}');
}

#########################################

if (!empty($CONF['s3_cache_bucket_path'])) {
	header("Content-Type:application/json"); //need to set the header first!

	$filesystem = new FileSystem();
	$cachefile = "/mnt/s3/cache/related/$id.json";

	// could use this...
	//  if ($filesystem->file_exists($path, true)) { //true to download the file and put in a temp file
	//      $content = $filesystem->file_get_contents($path); //will read from the temp file

	// or can use readfile, which can avoid writing to a tempory file

	$bytes = $filesystem->readfile($cachefile, true); //outputs it directly if exists! avoiding a temp file
	if (!empty($bytes))
		exit; //we done!
}

#########################################

$sph = GeographSphinxConnection('sphinxql', true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$rows = queryQL('myriad,hectad,grid_reference,takenyear,takenmonth,takenday,groups,tags,types,contexts,snippets,subjects,place,county,country,scenti,user_id,realname,imageclass',
		'', array("id=$id"));

if (empty($rows))
	$rows = rowsFromDB($id);

if (!empty($rows)) {
	$row = $rows[0]; //its actully always one row!

	$data = array();
	$data['row'] = $row;
	foreach($data['row'] as $key => $value)
		if (preg_match('/s$/',$key))
			unset($data['row'][$key]);

        $required = array();
        $optional = array();

	//Note this is only only emulating the default 'related' mode, JS can do other modes too!

		$required[] = $row['myriad'];
		$optional[] = $row['hectad'];

	$optional[] = $row['grid_reference'];

	if ($row['takenyear'] > '1' && $row['takenyear'] < 2010) //recent years have LOTS of matches
		@$optional[] = $row['takenyear'];
	@$optional[] = $row['takenmonth'];
	@$optional[] = $row['takenday'];

	$splits = array('contexts','groups','tags','snippets','subjects');
	foreach ($splits as $split)
		if (!empty($row[$split]) && strlen($row[$split]) > 5) {
			$list = explode(' _SEP_ ',preg_replace('/(top|subject):/','',preg_replace('/(^\s*_SEP_\s*|\s*_SEP_\s*$)/','', $row[$split])));
			foreach ($list as $bit) {
				if ($row['tags'] && strlen($row['tags']) > 5 && ($bit == 'Farm, Fishery, Market Gardening' || $bit == 'Roads, Road transport' || $bit == 'Wild Animals, Plants and Mushrooms'))
                                        continue; //these contexts have so many matches, now lets skip if have some tags
				$bit = str_replace(' the ',' * ',$bit); //context in particular have 'and' which is a very common keyword!
				$optional[] = '"'.$bit.'"';
			}
		}

	if (!empty($row['place']) && strlen($row['place']) > 2)
		$optional[] = '"'.$row['place'].'"';
	/* disabled for now, they greatly inflate the number of matches (partiucully 'England'!)
	if (!empty($row['county']) && strlen($row['county']) > 2)
		$optional[] = '"'.$row['county'].'"';
	if (!empty($row['country']) && strlen($row['country']) > 2)
		$optional[] = '"'.$row['country'].'"';
	*/
	$optional[] = 'user'.$row['user_id'];

	if (!empty($row['imageclass']) && strlen($row['imageclass']) > 2)
		$optional[] = '"'.$row['imageclass'].'"';

	$match = implode(" ",$required);
	$match .= " (".implode('|',$optional).")";

	$data['match'] = $match;
	$data['rows'] = queryQL('id,title,myriad,hectad,grid_reference,takenyear,takenmonth,takenday,hash,realname,user_id,place,county,country,hash,scenti,width,height',
                $match, array("id!=$id"), 10);

	//dont bother with $data['meta'] - the client doesnt use it.

	if (!empty($CONF['s3_cache_bucket_path']) && $cachefile) {
		//will have already outputed a Content-Type above!

		if (!empty($data['row']))
			http_response_code(200); //readfile above, might of proxied the 404 from S3!

		//dont need to support callback here!
		$content = json_encode($data, JSON_PARTIAL_OUTPUT_ON_ERROR );

		header("Etag: \"".md5($content)."\""); //for compatibity with filesystem->readfile above
		print $content;

		$filesystem->file_put_contents($cachefile, $content, 'bucket-owner-full-control'); //we need to specify a ACL, because the cache bucket doesnt allow public-read (which is used for photo bucket)
	} else {
		outputJSON($data);
	}
} else {
	die('{"error":"no results"}');
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

	return $db->getAll("SELECT
        SUBSTRING(gi.grid_reference,1,LENGTH(gi.grid_reference)-4) AS myriad,
        CONCAT(SUBSTRING(gi.grid_reference,1,LENGTH(gi.grid_reference)-3),SUBSTRING(gi.grid_reference,LENGTH(gi.grid_reference)-1,1)) AS hectad,
        grid_reference,
        year(imagetaken) AS takenyear,REPLACE(SUBSTRING(imagetaken,1,7),'-','') AS takenmonth,REPLACE(imagetaken,'-','') AS takenday,
        REPLACE(tags,'?',' _SEP_ ') AS tags,user_id,imageclass
	 FROM gridimage_search gi WHERE gridimage_id = ".$id);
}
