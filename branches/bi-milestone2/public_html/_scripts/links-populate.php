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

require_once('geograph/global.inc.php');

if (!isLocalIPAddress())
{
	init_session();
        $USER->mustHavePerm("admin");
}

$db=NewADOConnection($GLOBALS['DSN']);
if (!$db) die('Database connection failed');  

set_time_limit(3600*24);


#####################

$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

if (empty($_GET['add'])) {
$sql = "
SELECT
	gi.gridimage_id,comment
FROM
	gridimage_search gi
LEFT JOIN
	gridimage_link l ON (gi.gridimage_id = l.gridimage_id)
WHERE
	(comment LIKE '%http://' OR comment LIKE '%www.%')
AND 
	l.gridimage_link_id IS NULL 
GROUP BY 
	gi.gridimage_id
LIMIT 1000";
} else {
$sql = "
SELECT
	gi.gridimage_id,comment,gi.upd_timestamp, max(l.created) as last_link,group_concat(url separator ' ') as urls
FROM
	gridimage_search gi
INNER JOIN
	gridimage_link l ON (gi.gridimage_id = l.gridimage_id)
WHERE
	(comment LIKE '%http://' OR comment LIKE '%www.%')
GROUP BY 
	gi.gridimage_id
HAVING 
	upd_timestamp > last_link
LIMIT 1000";
}

$done = 0;
$recordSet = &$db->Execute("$sql");

$bindts = $db->BindTimeStamp(time());
	
while (!$recordSet->EOF) 
{
	preg_match_all('/(?<!["\'>F=])(https?:\/\/[\w\.-]+\.\w{2,}\/?[\w\~\-\.\?\,=\'\/\\\+&%\$#\(\)\;\:]*)(?<!\.)(?!["\'])/',$recordSet->fields['comment'],$m1);
	
	preg_match_all('/(?<![\/F\.])(www\.[\w\.-]+\.\w{2,}\/?[\w\~\-\.\?\,=\'\/\\\+&%\$#\(\)\;\:]*)(?<!\.)(?!["\'])/',$recordSet->fields['comment'],$m2);

	#print $recordSet->fields['comment'];
	#print "<hr><pre>";
	#print_r($m1);
	#print_r($m2);
	
	$all = array_unique(array_merge($m1[1],$m2[1]));
	
	$urls = array();
	if (!empty($recordSet->fields['urls'])) {
		foreach (explode(' ',$recordSet->fields['urls']) as $url) {
			$urls[$url] = 1;
		} 
	} 
	
	foreach ($all as $url) {
		if (strpos($url,'http://') !== 0) {
			$url = "http://$url";
		}
		$qurl = $db->Quote($url);
		$rows = $db->getAll("SELECT * FROM gridimage_link WHERE url = $qurl ORDER BY gridimage_id = {$recordSet->fields['gridimage_id']} DESC LIMIT 2");
		
		if (count($rows)) {
			$found_on_image = 0;
			foreach ($rows as $row) {
				if ($row['gridimage_id'] == $recordSet->fields['gridimage_id']) {
					$found_on_image++;
				} 
			}
			if ($found_on_image) {
				//nothing to do
			} else {
				$row = $rows[0];
				unset($row['gridimage_link_id']);
				$row['gridimage_id'] = $recordSet->fields['gridimage_id'];
				$row['created'] = $bindts;
				
				$db->Execute('INSERT INTO gridimage_link SET `'.implode('` = ?,`',array_keys($row)).'` = ?',array_values($row));
				$done++;
			}
		} else {
			$sql = "INSERT INTO gridimage_link SET 
				gridimage_id = {$recordSet->fields['gridimage_id']},
				url = $qurl,
				created = NOW()";
			$db->Execute("$sql");
			$done++;
		}
		if (isset($urls[$url])) {
			unset($urls[$url]);
		}
		
	}
	
	if (count($urls)) {
		foreach ($urls as $url => $dummy) {
			print "<BR>DELETING: $url from {$recordSet->fields['gridimage_id']}<BR>";
			$qurl = $db->Quote($url);
			$sql = "DELETE FROM gridimage_link WHERE 
				gridimage_id = {$recordSet->fields['gridimage_id']} AND 
				url = $qurl";
			$db->Execute("$sql");
		}
		$done++;
	}
	
	print "{$recordSet->fields['gridimage_id']} ";
	
	
	$recordSet->MoveNext();
}
$recordSet->Close(); 

print "<h2>DONE</h2>";

if ($done) {
	print " <A href=\"?\">Continue...</a>";
	print "<script>setTimeout(\"window.location.href = window.location.href\",1000);</script>";
}

?>
