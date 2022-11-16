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

if (!empty($_POST['results']) && !empty($_POST['label'])) {
	$ids = array();
	parse_str($_POST['results'],$ids);

	$where= "`label` = ".$db->Quote($_POST['label']);

	foreach ($ids as $key => $value) {
		$sql = "UPDATE curated1 SET `score` = `score` + ".intval($value)." WHERE $where AND gridimage_id = ".intval($key);
		$db->Execute($sql);
	}
}


?>
<h2>Curated Images</h2>

<form method=post onsubmit="countResults()" name=theForm>

<div class="interestBox">
<?

if (empty($_GET)) {
	$_GET['label'] = $db->getOne("SELECT label FROM curated1 WHERE active>0 AND score>7 and gridimage_id>0 ORDER BY updated ASC"); //todo, this could be mproved!
}


if (!empty($_GET['label'])) {
	$row = $db->getRow("SELECT * FROM curated_label WHERE label = ".$db->Quote($_GET['label']));
	if (!empty($row)) {
		print "<h3 style=\"margin-top:0;background-color:#ccc;padding:3px;\">".htmlentities($row['label'])."<small><br>".htmlentities($row['description'])."</small></h3>";
		if (!empty($row['notes'])) {
			print "<div style=padding:10px>".htmlentities($row['notes'])."</div>";
			print "<hr>";
		}
	} else {
		$row = $db->getRow("SELECT * FROM curated_headword WHERE label = ".$db->Quote($_GET['label']));
		if (!empty($row)) {
			print "<h3 style=\"margin-top:0;background-color:#ccc;padding:3px;\">".htmlentities($row['label'])."<small><br>".htmlentities($row['description'])."</small></h3>";
		} else {
			print "<h3 style=\"margin-top:0;background-color:#ccc;padding:3px;\">".htmlentities($_GET['label'])."</h3>";
		}
	}
}

$where= array('active > 0');

if (!empty($_GET['label'])) {
	$where[] = "`label` = ".$db->Quote($_GET['label']);
	print "Select any image(s) that DO NOT really illustrate '<big style=background-color:yellow>".htmlentities($_GET['label'])."</big>'";
	print '<input type=hidden name="label" value="'.htmlentities($_GET['label']).'">';

	print "<br><br>(while an image might technically be of a ".htmlentities($_GET['label']).", we <i>most interested</i> in images that are particully representative of the subject)";
} else {
	die("no labels available");
}

if (isset($_GET['region']))
	$where[] = "`region` = ".$db->Quote(($_GET['region']=='unspecified')?'':$_GET['region']);
if (!empty($_GET['caption']))
	$where[] = "`caption` = ".$db->Quote($_GET['caption']);
if (!empty($_GET['feature']))
	$where[] = "`feature` = ".$db->Quote($_GET['feature']);

$where = implode(" AND ",$where);
$rows = $db->getAll("SELECT gridimage_id, g.user_id, realname, credit_realname, title, grid_reference
		FROM curated1 c INNER JOIN gridimage_search g USING (gridimage_id) WHERE $where ORDER BY substring(updated,1,10),RAND() LIMIT 20");

?>
</div>

<p>Click a thumbnail to toggle selected on/off. Right click an image and use 'open in new tab' etc if want to view larger. Note, please still submit the form even if don't spot any non matching, you adding confirmation that they ok too.</p>



<div class=thumbs>
<?

if (empty($rows)) {
	print "No images! <a href=\"add.php?".http_build_query($_GET)."\">Add now</a>";
} else {

	foreach ($rows as $row) {

                        $image = new GridImage();
                        $image->fastInit($row);
                        $thumbh = 160;
                        $thumbw = 213;
?>
		<div class="thumb shadow" id="t<? echo $image->gridimage_id; ?>">
                                <a title="<? echo $image->grid_reference; ?> : <? echo htmlentities($image->title) ?> by <? echo htmlentities($image->realname); ?> - RIGHT click to view full size image" target=_blank href="/photo/<? echo $image->gridimage_id; ?>"><? echo $image->getThumbnail($thumbw,$thumbh,false,true); ?></a>
		</div>
<?
	}
	print "<br>";
}

?>
</div>

<p>You have selected <input type=text size=2 value=0 id="counter" readonly> images as <B>not</B> matching. Is that correct? <input type=checkbox> (tick to confirm)</p>

<input type=hidden name="results">
<input type=submit value="Submit Results" disabled>

</form>

<style>
div.thumbs div.thumb {
	float:left;
	width: 216px;
	height: 163px;
	text-align:center;
}
div.thumbs br {
	clear:both;
}
div.thumbs div.selected {
	background-color:pink;
}
div.thumbs div.selected img {
	opacity:0.5;
}
#counter {
	text-align:right;
}

</style>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<script>

$(function() {
	$("div.thumbs div.thumb a").click(function( event ) {
		event.preventDefault();
	});

	$("div.thumbs div.thumb").click(function( event ) {
		if ($(this).hasClass('selected')) {
				$(this).removeClass('selected')
		} else {
				$(this).addClass('selected')
		}

		$('#counter').val($("div.thumbs div.selected").length);
		countResults();
	});

	$('input[type=checkbox]').click(function() {
		if ($(this).prop('checked')) {
			$('input[type=submit]').prop('disabled',false);
		} else {
			$('input[type=submit]').prop('disabled',true);
		}
		countResults();
	});

});

function countResults() {
	var c = {};
	$("div.thumbs div.thumb").each(function(index) {
		c[$(this).attr('id')]=3;

console.log($(this).attr('id'));

	});
console.log(c);
	$("div.thumbs div.selected").each(function(index) {
		c[$(this).attr('id')]=-1;
	});

console.log(c);
	var r = [];
	$.each(c, function( index, value ) {
console.log(index,value);
		r.push(index.replace(/t/,'') +'='+ value);
	});

console.log(r);
	document.forms['theForm'].elements['results'].value = r.join('&');
}

</script>
<?

$smarty->display('_std_end.tpl');

