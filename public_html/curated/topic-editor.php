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

?>
<h2>Curated Images - Education Topic List</h2>

<p>This is the current topic list. It's still in development, and needs fleshing out. Contact us, if would like to help curate the list of topics themselves.

<p>We have already been collecting images, using a <a href="all-topics.php">long list of educational themed words from a dictionary</a>.
We've tried to cross reference this short list of topics, with that long list, the 'label' column shows the suggested label from the long list. 
If there is no link or description visible, means the word is not actully in the dictionary.

<p><i>If we have already begun collecting images for the label, use one of the 'curation interface' links to help with the curation process</i>.</p>

<p>Alternatively, if the label doesn't actully exist yet, can click the link to create it as a now formal label, to allow collection of images.

<hr>
<?


$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

/*
$data = $db->getAll("
select stack,name,label,l.description,count(gridimage_id) as images,h.description as h_description,h.label_id
from curated_label l
 left join curated1 c1 using (label)
 left join curated_headword h using (label)
where label is not null and stack != ''
group by name
order by stack,name
");
*/

$data = $db->getAll("
select stack,name,label,l.description,images,h.description as h_description,h.label_id
from curated_label l
 left join curated1_stat using (label)
 left join curated_headword h using (label)
where label is not null and stack != ''
group by name
order by stack,name
");


$last = array();


print "<table cellspacing=0 cellpadding=4 border=1>";
print "<tr>";
	print "<td>Name";
//	print "<td>Description";
	print "<td>Suggested Label";
	print "<td>Label Decription";
	print "<td>Images for Label";
	print "<td>Curation";

foreach ($data as $row) {
	$bits = explode(' > ',$row['stack']);
	foreach($bits as $idx => $bit) {
		if ($bit != @$last[$idx])
			print "<tr style=background-color:#eee><td colspan=6>".str_repeat('&nbsp;&nbsp;&nbsp;',$idx)."<b>".htmlentities($bit)."</b>";
	}
	$last = $bits;

	print "<tr><td>";
	print str_repeat('&nbsp;&nbsp;&nbsp;',count($bits)+1);
	print "<big>".htmlentities($row['name'])."</big>";

//	print "<td style=font-size:0.7em>".htmlentities($row['description']);

	$row['group'] = 'Geography and Geology';

	if (empty($row['label_id'])) {
		print "<td style=color:gray>".htmlentities($row['label']);
		if (!empty($row['label'])) {
			$link1 = "create-topic.php?label=".urlencode($row['label'])."&name=".urlencode($row['name'])."&amp;group=".urlencode($row['group']);
			print "<td colspan=3 style=background-color:#eee><i>Label not found</i> <a href=\"$link1\">create label...</a>";
		} else
			print "<td colspan=3 style=background-color:#eee>&nbsp;";
		continue;
	} else {
		print "<td>".htmlentities($row['label']);
	}

	$row['h_description'] = smarty_modifier_truncate($row['h_description'],140,"...");
	print "<td style=font-size:0.7em>".htmlentities($row['h_description']);
	if (!empty($row['label_id']))
		print " <a href=\"headwords.php?id={$row['label_id']}\">edit</a>";

	$row['group'] = $row['stack'];
	if (empty($row['label']))
		$row['label'] = $row['name'];

	//$link1 = "sample.php?label=".urlencode($row['label']);
	$link1 = "/photoset/view.php?label=".urlencode($row['label']);
	$link2 = "collecter.php?group=".urlencode($row['group'])."&amp;label=".urlencode($row['label']);

	print "<td>";
	if (!empty($row['images'])) {
		print "<a href=\"$link1\">{$row['images']} Images</a>";
	}

	print "<td>";
	if ($row['h_description']) {
		print "<i><a href=\"$link2\">Curation</a></i>";
	}
	print "</tr>";
}
print "</table>";


$smarty->display('_std_end.tpl');



