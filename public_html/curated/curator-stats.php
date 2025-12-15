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

$groups = array('top','edu');
if (empty($_GET['group']) || !in_array($_GET['group'], $groups))
	$_GET['group'] = $groups[0];

$gurl = urlencode($_GET['group']);

if ($_GET['group'] == 'top') {
	//checks if there isa  'checked'/'verified' tag on te image, 
$sql = "SELECT p.grouping, p.top AS tag, COUNT(t.curated_id) AS images, SUM(t.user_id = ?) AS yours, p.sort_order,
       COUNT(DISTINCT t.gridimage_id) AS total_images,
       COUNT(DISTINCT IF(t.user_id = ?, t.gridimage_id, NULL)) AS your_images,
       SUM(c.checked_count) AS checked,
       SUM(c.verified_count) AS verified
FROM category_primary p
LEFT JOIN curated_tag t ON (t.tag = p.top AND t.status = 1)
LEFT JOIN (
    SELECT gridimage_id,
           SUM(IF(tag = 'Checked', 1, 0)) AS checked_count,
           SUM(IF(tag = 'Verified', 1, 0)) AS verified_count
    FROM curated_tag
    WHERE status = 1
    GROUP BY gridimage_id
) c ON c.gridimage_id = t.gridimage_id
GROUP BY p.sort_order WITH ROLLUP";

} elseif ($_GET['group'] == 'edu') {
	//checked/verified, checks if such a tag exists for teh image, but really only works on hte 'top' group, not edu!
	$sql = "SELECT 'edu' as grouping, l.label as tag, COUNT(t.curated_id) AS images, SUM(t.user_id = ?) AS yours, l.id as sort_order,
	       COUNT(DISTINCT t.gridimage_id) as total_images,
	       COUNT(DISTINCT IF(t.user_id = ?, t.gridimage_id, null)) as your_images,
	       SUM(c.checked_count) AS checked,
	       SUM(c.verified_count) AS verified
	FROM label_embedding l
        LEFT JOIN curated_tag t ON (t.tag = l.label AND t.status = 1)
		LEFT JOIN (
			SELECT gridimage_id,
				   SUM(IF(tag = 'Checked', 1, 0)) AS checked_count,
				   SUM(IF(tag = 'Verified', 1, 0)) AS verified_count
			FROM curated_tag
			WHERE status = 1
			GROUP BY gridimage_id
		) c ON c.gridimage_id = t.gridimage_id
	WHERE l.label LIKE '%>%>%'
    GROUP BY l.id WITH ROLLUP";

} else {
	die('todo');
}


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
		print "<th>Checked";
		print "<th>Verified";
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
		print "<td align=right>".number_format($row['checked'],0);
		print "<td align=right>".number_format($row['verified'],0);
		if ($tag = urlencode($row['tag']))
			print "<td><a href=\"curator.php?tag=$tag&amp;group=$gurl\">Select some images</a>";

		//the totals only really make sense in the ROLLUP Row
		if (empty($row['sort_order']) && !empty($row['total_images'])) {
			print "<tr>";
			print "<td>Unique Images";
			print "<td>";
			print "<td align=right>".number_format($row['total_images'],0);
			print "<td align=right>".number_format($row['your_images'],0);
			print "<td>";
			print "<td>";
		}
	}
	print "</table>";
	if ($row['your_images']) { //its the ROLLUP row!
		print "<a href=curator-part2.php?group=$gurl>Can also goto step 2, as have preselected some images</a> (not checked if already verified yet)";
	}
} else {
	die("no images found, perhaps need to select some in step 1?");
}

$smarty->display('_std_end.tpl');

