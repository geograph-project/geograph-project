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

	$db = GeographDatabaseConnection(true);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

 $imagelist = new ImageList();

#######################################################################

$metrics = array('accuracy'=>'accuracy', 'prominence'=>'prominence', 'validity'=>'validity', 'overall' => '(accuracy+prominence+validity) div 3');

$current_key = (!empty($_GET['metric']) && isset($metrics[$_GET['metric']])) ? $_GET['metric'] : 'overall';
$metric_sql = $metrics[$current_key];

$raw = "select gridimage_id, label, $metric_sql as best,reasoning,is_gold_standard, title,grid_reference,gi.user_id,realname
	from curated1 inner join curated_judge using (gridimage_id, label)
	inner join gridimage_search gi using (gridimage_id)
	group by label, best";

 $imagelist->_getImagesBySql($raw);

if ($cnt = count($imagelist->images)) {
	foreach ($imagelist->images as $image) {
		$image->reference_index = (strlen($image->grid_reference) == 5)?2:1;

		//so it goes in the tooltip!!
		$image->title = $image->reasoning.' - '."\n\n".$image->title;

		$labels[$image->label][$image->best] = $image;

		//$images[$image->gridimage_id] = $image;
	}
}

#######################################################################

?>
<h2>Quality Verification</h2>

<p>The first AI is optimized for speed; it effectively looks through all 8M+ images for visual matches and returns a small number of results that look like they "could be".

<p>This page shows the results of taking those initial suggestions and asking a second AI to perform a much more critical evaluation - to "really" look at the image (and read the title/description!) and decide if it truly depicts the chosen subject.

<p>Images are rated on a 1-5 scale:
<ul>
<li><b>Columns 1 &amp; 2</b>: Where the initial trawl missed the mark.
<li><b>Columns 3 &amp; 4</b>: Might be close, but not enough to make a good example.
<li><b>Column 5</b>: Where the second AI says, "Oh yes, that's a good match".
<li><b>Gold Background</b>: The AI specifically gave these a "rosette" :) In concept, only those marked in gold would be considered for the final dataset.<br><br>
<li><b>Hover over the thumbnail</b> to read the AI explanation justifying the score it gave.
</ul>

<p>Metric: &middot;
<?
foreach($metrics as $key => $value) {
	if ($current_key === $key)
		print "<b>$key</b>";
	else
		print "<a href=\"?metric=$key\">$key</a>";
	print " &middot; ";
}
print "</p>";

$cols = range(1,5);

print "<table cellspacing=0 cellpadding=4 border=1 bordercolor=#eee id=\"table\" style=\"position:relative\">";
print "<tr style=\"position:sticky;top:0;left:0;background-color:#ddd\">";
print "<td>";
foreach($cols as $idx => $best) {
	print "<th>".htmlentities($best);
}

foreach ($labels as $label => $rows) {
	print "<tr>";
	$url = "?label=".urlencode($label);
	$labelh = htmlentities($label);
	print "<th><a href=\"$url\">$labelh</a>";

	foreach($cols as $idx => $best) {

		if (!empty($rows[$best])) {
			if ($rows[$best]->is_gold_standard) {
				print "<td align=center style=background-color:gold>";
			} elseif ($best <3) {
				print "<td align=center style=background-color:pink>";
			} else {
				print "<td align=center>";
			}
			$imagelist->getThumbnailLink($labels[$label][$best]);
		} else {
			print "<td>";
		}
	}
}

print "</table>";

#######################################################################

?>
<style>
</style>
<?

$smarty->display('_std_end.tpl');



