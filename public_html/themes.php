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

//$db = GeographDatabaseConnection(false);

//probably easier to define in PHP (so that later can convert to database)
$themes = array(
	//'Castles' => array('search'=>'','browser'=>'','gridref'=>''),
	'Castles' => array('search'=>'Castle','browser'=>'Castle','gridref'=>'','tag'=>'Castle'),
	'Geological Interest' => array('search'=>'[top:Geological Interest]','browser'=>'[top:Geological Interest]','gridref'=>'','tag'=>'top:Geological Interest'),
);

#############################################

?>
<html>
<head>
<base target=mainframe>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>

<script>
let themes= <? echo json_encode($themes); ?>;
function update(that) {
	let theme = that.value;
	let row = themes[theme];
	let search = encodeURIComponent(row['search']);
	let browser = encodeURIComponent(row['browser']);
	let $div = $('#output').empty();
	if (row['browser']) {
		add($div, 'Browser','Thumbnails', 'https://www.geograph.org.uk/browser/#!/q='+browser);
		add($div, 'Browser','Coverage Map', 'https://www.geograph.org.uk/browser/#!/q='+browser+'/display=map_dots');
	}
	if (row['search']) {
		add($div, 'Search', 'Search', 'https://www.geograph.org.uk/of/'+search);
		add($div, 'Collections','About '+theme, 'https://www.geograph.org.uk/content/?q='+search);
		add($div, 'Statistics','Image Leaderboard', 'https://www.geograph.org.uk/statistics/groupby.php?distinct=takendays&groupby=auser_id&q='+search+'&ri=0&less=on&more=on#reportlist');
	}
	if (row['tag']) {
		let tag = encodeURIComponent(row['tag']);
		add($div, 'Submit', 'Upload Image', 'https://www.geograph.org.uk/submit.php#'+search);
		add($div, 'Submit', 'Copy from Classic', 'https://www.geograph.org.uk/tags/multitagger.php?simple=1&tag='+tag+'&q='+search+'&onlymine=1');
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
<select name=theme onchange="update(this);">
	<option>Select...</option>
	<? foreach ($themes as $theme => $row) {
		printf('<option>%s</option>',$theme);
	} ?>
</select>

<div id="output">

</div>
</form>

