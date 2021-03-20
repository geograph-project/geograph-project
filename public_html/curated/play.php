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

	$db = GeographDatabaseConnection(true);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


$where = '';
if (!empty($_GET['g']))
	$where .= " AND `group` LIKE ".$db->Quote('%'.$_GET['g'].'%');
if (!empty($_GET['l']))
	$where .= " AND `label` = ".$db->Quote($_GET['l']);

//todo, this will have moe filtering!
$images = $db->getAll("SELECT * FROM (SELECT * FROM curated1 WHERE gridimage_id > 0 AND active = 1 $where ORDER BY RAND() LIMIT 30) t2 ORDER BY label");
if (empty($images))
	die("nothing to display right now, please try later");

print "<script>";
print "var images = new Array();\n";
foreach ($images as $image) {
	$img = new GridImage($image['gridimage_id']);
	//$url = $img->getThumbnail(213,160,true);
	//$url = $img->_getFullpath(true,true);

	$img->_getFullSize(); //(just to set the sizes!)
	$url = $img->getLargestPhotoPath(true); //technically this maxes out at 1024!

	$row = array($url, $image['label'], $img->realname);
	print "images.push(".json_encode($row).");\n";
}
?>
</script>

<style>
#scr {
	position:fixed; top:0; left:0; bottom: 0; right:0; width:100%; height: 100%;
	background: url()  no-repeat center center fixed;
	background-size: cover;
}
#caption {
	position:fixed; top:45vh; left:0; bottom:60vh; right:0; width:100%;
	text-align:center; font-size:5em; color:white; text-shadow:0px 0px 13px rgba(0, 0, 0, 1);
	opacity:0.6;
	z-index:100;
}
#credit {
	position:fixed; bottom:0; right:0; width:100%;
	text-align:right; font-size:1em; color:white; text-shadow:0px 0px 13px rgba(0, 0, 0, 1);
	opacity:0.5; padding:10px;
	z-index:100;
}
</style>

<div id=scr></div>
<div id=caption></div>
<div id=credit>image by <span></span></div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<script>
var idx = 0;
function setImage() {
	$('#scr').css({'background-image': 'url('+images[idx][0]+')'});
	$('#caption').text(images[idx][1]);
	$('#credit span').text(images[idx][2]);

	idx++;
	if (idx == images.length) idx=0;

	//preload the image for next time!
	if (images[idx] && images[idx][0]) {
	    var img=new Image();
	    img.src=images[idx][0];
	}
}

setImage();
setInterval(setImage,4500);

</script>

