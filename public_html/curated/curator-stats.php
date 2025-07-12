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

$smarty = new GeographPage;
$USER->mustHavePerm("basic");
customNoCacheHeader();

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


$sql = "SELECT grouping, top AS tag, COUNT(curated_id) AS images, SUM(user_id = ?) AS yours, sort_order
	, COUNT(distinct gridimage_id) as total_images, COUNT(distinct IF(user_id = ?,gridimage_id,null)) as your_images
        FROM category_primary
        LEFT JOIN curated_tag t ON (tag = top AND status = 1)
        GROUP BY sort_order WITH ROLLUP";

$rows = $db->getAll($sql, [$USER->user_id, $USER->user_id]);

$smarty->display('_std_begin.tpl');

if (!empty($rows)) {
	print "<h2>Curated Tags</h2>";

	print "<table cellspacing=0 cellpadding=3 border=1 bordercolor=#eee>";
	print "<tr>";
		print "<th>Group";
		print "<th>Tag";
		print "<th>Images";
		print "<th>Yours";
	foreach($rows as $row) {
		print "<tr>";
		if (empty($row['sort_order'])) { //will be null
			$row['grouping'] = 'Total Tag-Image Pairs';
			$row['tag'] = '';
		}
		print "<td>".htmlentities($row['grouping']);
		print "<td>".htmlentities($row['tag']);
		print "<td align=right>".number_format($row['images'],0);
		print "<td align=right>".number_format($row['yours'],0);
		if ($tag = urlencode($row['tag']))
			print "<td><a href=\"curator.php?tag=$tag\">Select some images</a>";

		//the totals only really make sense in the ROLLUP Row
		if (empty($row['sort_order'])) {
			print "<tr>";
			print "<td>Unique Images";
			print "<td>";
			print "<td align=right>".number_format($row['total_images'],0);
			print "<td align=right>".number_format($row['your_images'],0);
		}
	}
	print "</table>";
	if ($row['your_images']) { //its the ROLLUP row!
		print "<a href=curator-part2.php>Can also goto step 2, as have preselected some images</a> (not checked if already verified yet)";
	}
} else {
	die("no images found, perhaps need to select some in step 1?");
}

$smarty->display('_std_end.tpl');

