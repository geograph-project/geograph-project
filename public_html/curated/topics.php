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
<i>(can suggest images for known topics, via the <a href=coverage.php>Coverage</a> page)</i>.
<? } ?></p>


<hr><blockquote style="background-color:#eee;padding:20px;margin:0">
<?


$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$where = array();
//$where[] = "active > 0";
//$where[] = "gridimage_id > 0";
if (isset($_GET['group']))
        $where[] = "`group` = ".$db->Quote($_GET['group']);
else
	$where[] = 1;

$where = implode(" AND ",$where);

//$data = $db->getAll("SELECT `group`,`label`,COUNT(*) as count FROM curated1 WHERE $where GROUP BY `group`,`label`");
$data = $db->getAll("SELECT `group`,`label`,images as count FROM curated1_stat WHERE $where ORDER BY `group`,`label`");

$last = '';
$prev = '';
$sep = '';

foreach ($data as $row) {
	if ($last != $row['group']) {
		if ($last)
			print "<br><br><hr>";
		print "&gt; <b>".htmlentities($row['group'])."</b><br><br>";
		$last = $row['group'];
		$sep = '';
	}

	print $sep;

	if (!empty($_GET['c'])) {
		$url = "https://t0.geograph.org.uk/tile-coveragethumb.png.php?label=".urlencode($row['label'])."&fudge=2&scale=0.3";
		print "<img src=\"$url\">";
	}

	$link = "sample.php?group=".urlencode($row['group'])."&label=".urlencode($row['label']);
	$text = htmlentities($row['label']);
	$first = substr($text,0,1);
	if (strcasecmp($first,$prev) != 0) {
		$text = preg_replace('/^(\w)/','<b>$1</b>',$text);
		$prev = $first;
	}
	print "<a href=\"$link\" title=\"{$row['count']} image(s)\">$text</a>";

	$sep = ', ';
}

print "</blockquote>";


if (!empty($_GET['group']) && $_GET['group'] == 'Geography and Geology') {




?>
<style>
	blockquote {
		line-height:1.5em;
	}
	blockquote a {
		white-space: nowrap;
		text-decoration:none;
	}
</style>
<h3>Full Topic List Search</h3>

The above list is the list of terms we've already made a start on curating. We know of many other terms in the dictionary,
that can find below, and make an early start on collecting suitable images...

<form method="get" action="/curated/collecter.php">
	<input type=hidden name=group value="Geography and Geology">
	<input type=search name="label" id="qqq" size=50>
	<input type=submit value="Collect...">
</form>

<div id="message"></div>


<p>Search for a label above, once found, click collect to start providing images.

<link type="text/css" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.22/themes/ui-lightness/jquery-ui.css" rel="stylesheet"/>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.min.js" type="text/javascript"></script>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.22/jquery-ui.min.js"></script>

<script>
$.ajaxSetup({
  cache: false
});

$(function () {

	$( "#qqq" ).autocomplete({
		minLength: 2,
		source: function( request, response ) {

			var url = "/finder/headwords.json.php?q="+encodeURIComponent(request.term);

			$.ajax({
				url: url,
				dataType: 'json',
				cache: true,
				success: function(data) {

					if (!data || data.length < 1) {
						$("#message").html("Nothing matching '"+request.term+"'");
						return;
					}

					var re=new RegExp(request.term.replace(/=/g,''),'i');

					var results = [];
					$.each(data.rows, function(i,item){
						if (item.label) {
							var images = 0;
                                                        if (item.images)
                                                                images = parseInt(item.images,10);
							results.push({label:item.label, images:images});
						}
					});

					response(results);
				}
			});

		}
	})
	.data( "autocomplete" )._renderItem = function( ul, item ) {
		var re=new RegExp('('+$("#qqq").val().replace(/=/g,'').replace(/ /g,'|')+')','gi');
		return $( "<li></li>" )
			.data( "item.autocomplete", item )
			.append( "<a>" + item.label.replace(/=/g,'').replace(re,'<b>$1</b>') + (item.images?" <small>[~" + item.images + " images so far]</small>":"") + "</a>" )
			.appendTo( ul );
	};
	if ($("#qqq").val().length > 2) {
		 $("#qqq").autocomplete( "search", $("#qqq").val() );
	}
});

</script>

<?
}

$smarty->display('_std_end.tpl');



