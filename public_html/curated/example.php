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

	$db = GeographDatabaseConnection(true);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	print "<h2>Curated Education Images</h2>";

$where = '';
if (!empty($_GET['g']))
        $where .= " AND `group` LIKE ".$db->Quote('%'.$_GET['g'].'%');
if (!empty($_GET['l']))
        $where .= " AND `label` = ".$db->Quote($_GET['l']);

//todo, this will have moe filtering!
$master = $db->getRow("SELECT * FROM curated1 WHERE gridimage_id > 0 AND active = 1 $where ORDER BY RAND() LIMIT 1");
if (empty($master))
	die("nothing to display right now, please try later");

##################################
# temporally hardcoded list of similar examples. So if pick an image of a village, none of the others will be a town or city which could be hard to diffentiate!

$lists = array(
	explode(',','city,village,town'),
	explode(',','port,harbour'),
	explode(',','season,weather'),
	explode(',','beach,coast,ocean,sea,cliff'),
	explode(',','hill,mountain,valley,cliff'),
);

$exclude= "label != '{$master['label']}'";
foreach ($lists as $list)
	if (in_array($master['label'],$list))
		$exclude= "label NOT IN ('".implode("','",$list)."')";

##################################

//todo, join with gridimage_search to ensure active image! (and could maybe use quickinit then!
$images = $db->getAll("SELECT * FROM curated1 WHERE gridimage_id > 0 AND active = 1 AND `group` = '{$master['group']}' AND $exclude GROUP BY label LIMIT 3");
$images[] = $master;

shuffle($images);


if (!empty($_GET['t'])) {
	$type = intval($_GET['t']);
} else
	$type = rand(1,4);



print "<form method=post class=reply>";


##############################################

if ($type == 2) { //map

	$image = new GridImage($master['gridimage_id']);

	print "<iframe src=\"/map_frame.php?id={$image->gridimage_id}&amp;hash=".$image->_getAntiLeechHash()."\" width=252 height=252 frameborder=0 scrolling=no></iframe>";

	print "<p>Which of the following is most likely to be in the above 1km square?<br>";
	foreach ($images as $idx => $row) {
		print "<div class=box2><input type=radio name=reply value=\"{$row['label']}\" id=c$idx><label for=c$idx>";
		print htmlentities(ucfirst($row['label']))."</label></div>";
	}

##############################################

} elseif ($type == 4) { //map+thumb

	$image = new GridImage($master['gridimage_id']);

	print "<iframe src=\"/map_frame.php?id={$image->gridimage_id}&amp;hash=".$image->_getAntiLeechHash()."\" width=252 height=252 frameborder=0 scrolling=no></iframe>";

	print "<p>Which of the following is most likely to be in the above 1km square?<br>";
	foreach ($images as $idx => $row) {

		print '<div class=box1>';
		$image = new GridImage($row['gridimage_id']);

		//todo use a token to hide the image id!
		print "<input type=radio name=reply value=\"{$row['gridimage_id']}\" id=c$idx><label for=c$idx>";
		//load by url, because dont want to display the title or link to the actual page!
		$url = $image->getThumbnail(213,160,true);
		print "<img src=$url>";
		print "</label>";

		print "<div>Image by ".htmlentities($image->realname)."</div>";
		print "</div>";

	}

##############################################

} elseif ($type == 1) { //pick image
	print "<p>Please select the image that is of a <span class=ask>".htmlentities(ucfirst($master['label']))."</span></p>";

	foreach ($images as $idx => $row) {
		print '<div class=box1>';
		$image = new GridImage($row['gridimage_id']);

		//todo use a token to hide the image id!
		print "<input type=radio name=reply value=\"{$row['gridimage_id']}\" id=c$idx><label for=c$idx>";
		//load by url, because dont want to display the title or link to the actual page!
		$url = $image->getThumbnail(213,160,true);
		print "<img src=$url>";
		print "</label>";

		print "<div>Image by ".htmlentities($image->realname)."</div>";
		print "</div>";
	}

##############################################

} else { //type=3 
	$image = new GridImage($master['gridimage_id']);
	//$url = $image->getThumbnail(213,160,true);
	$url = $image->_getFullpath(true,true);
	print "<img src=$url>";
	print "<div class=credit>Image by ".htmlentities($image->realname)."</div>";

	print "<p>What is the above image of?<br>";
	foreach ($images as $idx => $row) {
		print "<div class=box2><input type=radio name=reply value=\"{$row['label']}\" id=c$idx><label for=c$idx>";
		print htmlentities(ucfirst($row['label']))."</label></div>";
	}
}


print "<br style=clear:both>";

print "<input type=submit>";
print "</form>";

?>

<style>
	span.ask {
		font-size: 3em
	}
	.box1 {
		float:left; width:260px;height:220px;text-align:center;
		padding:10px;
		border:1px solid #e4e4fc;
		margin:10px;
	}
	.box1 div, .credit {
		font-size:0.9em;
		color:silver;
	}

	.box2 {
		float:left; width:260px; height:100px; text-align:center;
		padding:10px;
		border:1px solid #e4e4fc;
		margin:10px;
		font-size: 3em;
	}

	.reply input:checked + label {
		background-color:yellow;
	}
</style>

<?



$smarty->display('_std_end.tpl');



