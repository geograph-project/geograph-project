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


$smarty->display('_std_begin.tpl');

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

?>
<h2>Curated Images</h2>

<p>This page shows the 'work in progress' curation, intended for curators themselves to collaboratively build up the collection.
The matrix below <i>tracks</i> where images have already been found, by region (to guage coverage). Click a 'images' link to explore the selected images.

<?

$where= array('active > 0');
$where[] = 'original_width >=800';
$where[] = "region != ''";

$where = implode(" AND ",$where);
$rows = $db->getAll("SELECT `group`,label,region,count(*) as images
		FROM curated1 c INNER JOIN gridimage_size USING (gridimage_id)
		WHERE $where GROUP BY `group`,label,region") or die(mysql_error());

$matrix = array();
$cols = array();

$group = "Geography and Geology";
$terms = $db->getAll("SELECT label FROM curated_headword WHERE description != '' and description NOT like 'x%' AND notes NOT like 'x%' ORDER BY label");
foreach ($terms as $row) {
	$matrix[$group.'|'.$row['label']] = array();
}

foreach ($rows as $row) {
	@$matrix[$row['group'].'|'.$row['label']][$row['region']] = $row;
	@$cols[$row['region']]++;
}

ksort($cols);

print "<table cellspacing=0 cellpadding=3 border=1>";
print "<tr><td colspan=2></td>";
foreach ($cols as $region => $count) {
	print "<th>".htmlentities($region)."</th>";
}
print "</tr>";

$last = null;
foreach ($matrix as $grouplabel => $data) {
	if (empty($grouplabel))
		continue;

	list($group,$label) = explode("|",$grouplabel);

	if ($group != $last) {
		print "<tr>";
		print "<td colspan=".(count($cols)+2)." style=color:white;background-color:black>".htmlentities($group);
		$last = $group;
	}


	$link = "group=".urlencode($group)."&label=".urlencode($label);
	print "<tr>";
	if (!empty($data)) {
		print "<td><b><a href=\"sample.php#$link\">".htmlentities($label)."</a></b></td>";
	} else
		print "<td><b>".htmlentities($label)."</b></td>";

	print "<td><a href=\"collecter.php?$link\">add</a></td>";

	foreach ($cols as $region => $count) {
		$link2 = "$link&region=".urlencode($region);
		print "<td align=center width=100>";
		if (!empty($data[$region])) {
			$row = $data[$region];
			print "<a href=\"sample.php#$link2\"><big>{$row['images']}</big></a> ";
			if ($row['images'] < 10)
				print "<b>";
		} else
			print "<b>";
		//print "<a href=\"collecter.php?$link\">+</a></b>";
		print "</td>";
	}
}

print "<tr><td colspan=2></td>";
foreach ($cols as $region => $count) {
	print "<th>".htmlentities($region)."</th>";
}
print "</tr>";
print "</table>";

print "<hr>";


//todo, this needs to honouyr the 'anon'  option in user_preference (see credits.php)
//print "This initial data curated by: ";
//$users = $db->getCol("select realname from curated1 inner join user using (user_id) where $where group by user_id");
//print implode(', ',$users);



$smarty->display('_std_end.tpl');



