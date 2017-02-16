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
        'number'=>1000,   //number to do each time
        'sleep'=>0,    //sleep time in seconds
	'mode'=>'new',
);

$HELP = <<<ENDHELP
    --mode=new|update   : mode (new)
    --sleep=<seconds>   : seconds to sleep between calls (0)
    --number=<number>   : number of items to process in each batch (10)
ENDHELP;


chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


if ($param['mode'] == 'new') {
$sql = "
SELECT
	gi.gridimage_id,comment
FROM
	gridimage_search gi
LEFT JOIN
	gridimage_link l ON (gi.gridimage_id = l.gridimage_id)
WHERE
	(comment LIKE '%http%' OR comment LIKE '%www.%')
AND
	l.gridimage_link_id IS NULL
GROUP BY
	gi.gridimage_id
ORDER BY
	NULL
LIMIT {$param['number']}";
} elseif ($param['mode'] == 'update') {
$db->Execute("SET SESSION group_concat_max_len = 1000000");

$sql = "
SELECT
	gi.gridimage_id,comment,gi.upd_timestamp, max(last_found) as last_link,group_concat(url separator ' ') as urls
FROM
	gridimage_search gi
INNER JOIN
	gridimage_link l ON (gi.gridimage_id = l.gridimage_id)
WHERE
	(comment LIKE '%http%' OR comment LIKE '%www.%')
	AND next_check < '2013'
GROUP BY
	gi.gridimage_id
HAVING
	upd_timestamp > last_link
ORDER BY
	NULL
LIMIT {$param['number']}";
}

if (empty($sql))
	die("unknown mode\n");

$done = 0;
$recordSet = &$db->Execute("$sql");

$bindts = $db->BindTimeStamp(time());

while (!$recordSet->EOF)
{
	//some people do " also >http://www.ge...", which breaks our 'anti-HTML' extraction
	$recordSet->fields['comment'] = preg_replace('/ >http/',' > http',$recordSet->fields['comment']);


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
		if (strpos($url,'http') !== 0) {
			$url = "http://$url";
		}
		$qurl = $db->Quote($url);
		$rows = $db->getAll("SELECT * FROM gridimage_link WHERE url = $qurl ORDER BY gridimage_id = {$recordSet->fields['gridimage_id']} DESC LIMIT 2");

		if (count($rows)) {
			$found_on_image = 0;
			foreach ($rows as $row) {
				if ($row['gridimage_id'] == $recordSet->fields['gridimage_id']) {
					$found_on_image=$recordSet->fields;
				}
			}
			if (!empty($found_on_image)) {
				//existing row on this image, needs updating
				if ($found_on_image['next_check'] > '2023') //the link was previouslly removed
					$db->Execute("UPDATE gridimage_link SET next_check = NOW(),last_found=NOW() WHERE url = $qurl AND gridimage_id = {$recordSet->fields['gridimage_id']}");
				else
					$db->Execute("UPDATE gridimage_link SET last_found=NOW() WHERE url = $qurl AND gridimage_id = {$recordSet->fields['gridimage_id']}");
			} else {
				//existing row on other image, needs duplicating!
				$row = $rows[0];
				unset($row['gridimage_link_id']);
				$row['gridimage_id'] = $recordSet->fields['gridimage_id'];
				$row['created'] = $bindts;
				$row['first_used'] = $bindts; //record this as now, because right now the redirect was in place
				$row['last_found'] = $bindts;

				$db->Execute('INSERT INTO gridimage_link SET `'.implode('` = ?,`',array_keys($row)).'` = ?',array_values($row));
				$done++;
				print ".";
			}
		} else {
			//brand new link, insert it!
			$sql = "INSERT INTO gridimage_link SET
				gridimage_id = {$recordSet->fields['gridimage_id']},
				url = $qurl,
				first_used = NOW(),created = NOW(),last_found=NOW()";
			$db->Execute("$sql");
			$done++;
			print "+";
		}
		if (isset($urls[$url])) {
			unset($urls[$url]);
		}
	}

	if (count($urls)) {
		foreach ($urls as $url => $dummy) {
			print "\nDELETING: $url from {$recordSet->fields['gridimage_id']}\n";
			$qurl = $db->Quote($url);
			$sql = "UPDATE gridimage_link SET next_check = '2023-01-01'
				WHERE gridimage_id = {$recordSet->fields['gridimage_id']}
				AND url = $qurl";
			$db->Execute("$sql");
		}
		$done++;
	}

	print "{$recordSet->fields['gridimage_id']} ";

	$recordSet->MoveNext();
}
$recordSet->Close();

if ($done) {
	print "Links processed so should go again!\n";
}

print "DONE!\n";


