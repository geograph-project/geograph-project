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


if (!empty($_POST)) {
	$db = GeographDatabaseConnection(false);

	foreach ($_POST['description'] as $label_id => $description) {
		if (!empty($description)) {

		        $updates = array();
		        $updates['user_id'] = intval($USER->user_id);

		        $updates['description'] = $description;
		        $updates['source'] = $_POST['source'][$label_id];

			$label_id = intval($label_id);
		        $db->Execute('UPDATE curated_headword SET `'.implode('` = ?,`',array_keys($updates)).'` = ? WHERE label_id = '.$label_id,array_values($updates));

		        if ($db->Affected_Rows() == 1) {
		                print "<h3>Updated #$label_id </h3>";
		        } else {
		                print "<h3>Updating label #$label_id failed ".mysql_error()."</h3>";
		        }
		}
	}
}





?>
<h2>Curated Images - current Topic List</h2>

<p>We are looking semi-formal definitions of each term. Where a term may have multiple meanings looking for the <b>Geography and Geology</b> themed definitions.

<p>The definition would help the curator select images for the term, as well as provide explanation for viewers.

<? if (empty($_GET)) { ?>
<hr>
<p style=padding:10px;background-color:pink;>Note: If a term would <b>not be well illustrated</b> by Geograph images, just enter 'x' in the definition box.
For example, 'Galaxy' - while could easily <i>contrive</i> images to illustrate the term (eg, any photo on earth is taken in a galaxy!) its a bit of a stretch.

Think of it, if was explaining <b>the 'Geography and Geology' meaning of the term</b>, to a Child, that the images would would help with the explanation.</p>
<? } ?>

<hr>
Please provide source for any definition, you add. Either a URL, or at least a short reference. If wrote the definition yourself, put your name in box!
<hr>
<form method=post style="background-color:#eee">

<table>
<?

if (empty($db))
	$db = GeographDatabaseConnection(false); //we update the curated_offset table!
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

if (!empty($_GET['id'])) {
	$data = $db->getAll("SELECT label_id,label,description,source,welsh FROM curated_headword WHERE label_id = ".intval($_GET['id']));
} elseif (!empty($_GET['linked'])) {
	$data = $db->getAll("SELECT h.label_id,h.label,h.description,source,welsh
		FROM curated_headword h INNER JOIN curated_label USING (label)
		ORDER BY label");
} elseif (!empty($_GET['edit']) && !empty($USER->user_id)) {
	$data = $db->getAll("SELECT label_id,label,description,source,welsh FROM curated_headword WHERE user_id = {$USER->user_id} ORDER BY label");
} else {
	$size = 150;
	$offset = $db->getOne("SELECT headword FROM curated_offset WHERE user_id = {$USER->user_id}");
	if (empty($offset)) {
		$offset = $db->getOne("SELECT MAX(headword) FROM curated_offset WHERE updated > DATE_SUB(NOW(),INTERVAL 24 HOUR)");
		@$offset+= $size;

		 $db->Execute("insert into curated_offset set user_id = {$USER->user_id}, headword=$offset on duplicate key update headword=$offset");
	}

	$data = $db->getAll("SELECT label_id,label,description,source,welsh FROM curated_headword WHERE description = '' LIMIT $offset,$size");
}

foreach ($data as $row) {
	print "<tr>";
	print "<th rowspan=2><big>".htmlentities($row['label'])."</big>";
	if (!empty($row['welsh']))
		print "<br><br><span style=color:gray>welsh term</span>: ".htmlentities($row['welsh']);
	print "</th>";
	print "<td>Formal Definition:    &nbsp; (can try a <a href=\"https://www.google.com/search?q=define+".urlencode($row['label'])."\" target=_blank>Google Search</a> to get started)<br>";
	print "<textarea name=\"description[{$row['label_id']}]\" maxlength=2048 rows=4 cols=80 placeholder=\"definiton for ".htmlentities($row['label'])."\">".htmlentities($row['description'])."</textarea>";
	print "</tr>";
	print "<tr>";
	print "<td align=right>Source: <input name=\"source[{$row['label_id']}]\" maxlength=512 size=50 value=\"".htmlentities($row['source'])."\"></td>";
}

?>
</table>

Submit results: <input type=submit><? if (count($data) > 1) { ?> (dont have to fill out each row, can save results of just a few rows!)<? } ?>
</form>

<?
$smarty->display('_std_end.tpl');



