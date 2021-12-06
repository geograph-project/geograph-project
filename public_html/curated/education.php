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


$smarty->display('_std_begin.tpl');

?>
<h2>Curated Images - current Topic List</h2>

<p>This is the current topic list. It's still in development, and needs fleshing out. Contact us, if would like to help curate the list of topics themselves.
<? if ($USER->registered) { ?>
<p><i>Some topics we have begun already collecting imags, use one of the 'curation interface' links to help with the curation process</i>.</p>
<? } ?></p>


<hr>
<?


$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

/*
$data = $db->getAll("
select stack,name,label,count(gridimage_id) as images,length(curated_headword.description) hw
from curated_label
 left join curated1 using (label)
 left join curated_headword using (label)
where label is not null and stack != ''
group by name
order by stack,name
");
*/

$data = $db->getAll("
select `group`,stack,name,label,images,length(curated_headword.description) hw
from curated_label
 left join curated1_stat using (label)
 left join curated_headword using (label)
where label is not null and stack != ''
group by name
order by stack,name
");


$last = array();

foreach ($data as $row) {
	$bits = explode(' > ',$row['stack']);
	foreach($bits as $idx => $bit) {
		if ($bit != @$last[$idx])
			print str_repeat('&nbsp;&nbsp;&nbsp;',$idx)."<b>".htmlentities($bit)."</b><br>";
	}
	$last = $bits;

	print str_repeat('&nbsp;&nbsp;&nbsp;',count($bits)+1);
	print "<big>".htmlentities($row['name'])."</big>";

	if (empty($row['label']))
		$row['label'] = $row['name'];

	//$link1 = "sample.php?label=".urlencode($row['label']);
	//$link1 = "/photoset/view.php?label=".urlencode($row['label']);
	$link1 = "viewer.php?group=".urlencode($row['group'])."&amp;label=".urlencode($row['label']);
	$link2 = "collecter.php?group=".urlencode($row['group'])."&amp;label=".urlencode($row['label']);

	if (!empty($row['images'])) {
		print " <a href=\"$link1\"><b>{$row['images']}</b> Images so far</a>";
	}

	if ($USER->registered && $row['hw']) {
		print " <i><a href=\"$link2\">Curation Interface</a></i>";
	}
	print "<br>";
}



$smarty->display('_std_end.tpl');



