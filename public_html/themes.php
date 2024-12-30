<?php
/**
 * $Project: GeoGraph $
 * $Id: index.php 4866 2008-10-19 21:06:25Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2006 Barry Hunter (geo@barryhunter.co.uk)
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

//you must be logged in to request changes
$USER->mustHavePerm("basic");


if (empty($_GET) && empty($_POST)) {
?>
<title>content grouper</title>
<frameset cols="250,*">
<frame src="/themes.php?sidebar=1">
<frame src="about:blank" name="mainframe">
</frameset>
<?
exit;
}

#############################################

$db = GeographDatabaseConnection(false);

//probably easier to define in PHP (so that later can convert to database)
$themes = array(
	//'Castles' => array('search'=>'','browser'=>'','gridref'=>''),
	'Castles' => array('search'=>'Castle','browser'=>'Castle','gridref'=>'','tag'=>'Castle'),
	'Geological Interest' => array('search'=>'[top:Geological Interest]','browser'=>'[top:Geological Interest]','gridref'=>'','tag'=>'top:Geological Interest'),
	'Sea Walls' => array('search'=>'"Sea Walls"','browser'=>'"Sea Walls"')
);

#############################################

?>
<html>
<head>
<base target=mainframe>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>

<script>
let themes= <? echo json_encode($themes); ?>;
function update_theme(that) {
	let theme = that.value;
	let row = themes[theme];
	update(theme,row);
}
function update_subject(that) {
	let subject = that.value;
	let row = {
		search: '[subject:'+subject+']',
		browser: '[subject:'+subject+']',
		tag: 'subject:'+subject+'',
	};
	update(subject,row);
}
function update_label(that) {
	let label = that.value;
	let row = {
		browser: label,
		label: label,
		tag: 'label:'+label+'',
	};
	update(label,row);
}



function update(theme,row) {
	let search = encodeURIComponent(row['search']);
	let browser = encodeURIComponent(row['browser']);
	let $div = $('#output').empty();
	if (row['browser']) {
		add($div, 'Browser','Thumbnails', 'https://www.geograph.org.uk/browser/#!/q='+browser);
		add($div, 'Browser','Coverage Map', 'https://www.geograph.org.uk/browser/#!/q='+browser+'/display=map_dots');
		add($div, 'Browser','By County', 'https://www.geograph.org.uk/browser/#!/q='+browser+'/display=group/group=county/n=4/gorder=alpha%20asc');
	}
	if (row['search']) {
		add($div, 'Search', 'Search', 'https://www.geograph.org.uk/of/'+search);
		add($div, 'Search', 'GeoRiver', 'https://www.geograph.org.uk/search.php?searchtext='+search+'&orderby=sequence&displayclass=black&do=1');
		add($div, 'Collections','About '+theme, 'https://www.geograph.org.uk/content/?q='+search);
		add($div, 'Statistics','Image Leaderboard', 'https://www.geograph.org.uk/statistics/groupby.php?distinct=takendays&groupby=auser_id&q='+search+'&ri=0&less=on&more=on#reportlist');
	}
	if (row['tag']) {
		let tag = encodeURIComponent(row['tag']);
		add($div, 'Submit', 'Upload Image', 'https://www.geograph.org.uk/submit.php#'+search);
		add($div, 'Submit', 'Copy from Classic', 'https://www.geograph.org.uk/tags/multitagger.php?simple=1&tag='+tag+'&q='+search+'&onlymine=1');
	}
	if (row['label']) {
		let label = encodeURIComponent(row['label']);
		add($div, 'Education', 'Viewer', 'https://www.geograph.org.uk/curated/sample.php?group=&label='+label+'&region=&decade=');
		add($div, 'Education', 'Map', 'https://t0.geograph.org.uk/tile-coveragethumb.png.php?label='+label+'&fudge=3&scale=5');
		add($div, 'Education', 'Add Images', 'https://www.geograph.org.uk/curated/collecter.php?&label='+label+'&by=myriad');
	}
}
let last = '';
function add($div, section, title, href) {
	if (section && section != last) {
		$div.append($('<h4>').text(section));
		last = section;
	}
	$div.append($('<a>').text(title).attr('href',href));
}
</script>
<style>
	a {
		display:block;
		padding-bottom:2px;
		margin-left:10px;
	}
</style>
</head>

<body>
<form onsubmit="return false">
Theme: <select name=theme onchange="update_theme(this);">
	<option>Select...</option>
	<? foreach ($themes as $theme => $row) {
		printf('<option>%s</option>',$theme);
	} ?>
</select><br>
or Subject: <select name=subject onchange="update_subject(this);">
	<option>Select...</option>
	<? $subjects = $db->getCol("SELECT tagtext FROM  tag_stat where tagtext like 'subject:%' and count > 100");
	foreach ($subjects as $subject) {
		$bits = explode(':',$subject,2);
		printf('<option>%s</option>',$bits[1]);
	} ?>
</select><br>
or Topic: <select name=label onchange="update_label(this);">
	<option>Select...</option>

	<? $labels = $db->getAssoc("SELECT name,label from curated_label  inner join curated1_stat using (label)  where label != '' and examples != '' and images > 10");
	foreach ($labels as $name => $label) {
		printf('<option value="%s">%s</option>',$label, $name);
	} ?>

	<option></option>

	<? $labels = $db->getCol("SELECT label FROM curated1_stat where images > 10");
	foreach ($labels as $label) {
		printf('<option>%s</option>',$label);
	} ?>
</select>


<div id="output">

</div>
</form>

