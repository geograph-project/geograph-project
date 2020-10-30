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


init_session();

$USER->mustHavePerm("basic");


$smarty = new GeographPage;

 customExpiresHeader(300,false,true);

	$smarty->display('_std_begin.tpl');

	$db = GeographDatabaseConnection(true);

	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$list = $db->getAll("
		SELECT imagetaken,count(*) AS images, gi.title, grid_reference, COUNT(DISTINCT grid_reference) AS squares, id
		FROM gridimage_search gi
			 LEFT JOIN geotrips t ON (t.uid = user_id AND t.date = imagetaken)
		WHERE user_id = {$USER->user_id}
		GROUP BY imagetaken DESC
		LIMIT 150
		");

	print "<h2>Recent Trips</h2>";

	if (count($list)) {
		print "<p>Click the date, to run a search for all your images that day. Showing most recent 150 days. (Note: the linked geotrip (if any), is just a trip you have on that day - ie only date is used to match)</p>";
		print "<ul>";
		foreach ($list as $idx => $row) {
			$link = htmlentities2("/search.php?user_id={$USER->user_id}&orderby=submitted&taken_start={$row['imagetaken']}&taken_end={$row['imagetaken']}&do=1");
			print "<li><a href=\"$link\">{$row['imagetaken']}</a> ({$row['images']} images, {$row['squares']} squares) - ";
			if (!empty($row['id']))
				print "<a href=\"/geotrips/{$row['id']}\">Geo-Trip</a> - ";
			print " example: {$row['grid_reference']} ".htmlentities2($row['title'])."</li>";
		}
		print "</ul>";
	} else {
		print "nothing to display";
	}

	$smarty->display('_std_end.tpl');
	exit;

