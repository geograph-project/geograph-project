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

 customExpiresHeader(3600,false,true);

	$smarty->display('_std_begin.tpl');

?>

<div style="max-width:60em">
<h2>AI-Generated Visual Tags (Alpha)</h2>
<p><strong>What is this?</strong>

These tags are created by a "Computer Vision" model that looks at the image and describes what it sees in plain English. This process 
is entirely automatic - the AI does not see the original title, tags, or location data submitted by the photographer.</p>

</div>

<hr>
<br>
<?

$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


$andwhere = $leftjoin = $extra = '';

$model = 'md3';
if (!empty($_GET['model']) && preg_match('/^\w+$/',$_GET['model']))
	$model = $_GET['model'];

$type = 'tags'; //short/normal/long
if (!empty($_GET['type']) && preg_match('/^\w+$/',$_GET['type']))
	$type = $_GET['type'];

//this is only intended as a QUICk test, will setup a proper keywords index!!
if (!empty($_GET['word']) && preg_match('/^\w+[ \w]*$/',$_GET['word']))
	$andwhere = " and caption LIKE ".$db->Quote('%'.$_GET['word'].'%');

if (!empty($_GET['tag']) && preg_match('/^\w+[ \w-]*$/',$_GET['tag']))
	$andwhere = " and caption REGEXP ".$db->Quote('(^|[\\\\[",])'.$_GET['tag'].'([,"\\\\]|$)');

####################################################

	//just some some examples!
	$sql = "SELECT caption
		FROM gridimage_caption c
		WHERE c.type='$type' AND c.model = '$model' $andwhere
		AND length(caption) < 2048
		LIMIT 240";
	//the AI getting stuck in a loop, can produice long lists!

	$rows = $db->getAll($sql);
	$stat = array();
	foreach ($rows as $row) {
		//echo htmlentities2(utf8_to_latin1($image->caption));
			 $list = explode(',', strtolower(preg_replace('/[\r\n]+/','',utf8_to_latin1($row['caption']))));
        		 foreach($list as $tag) {
	                        $tag = trim($tag, '[]", ');
                	        @$stat[$tag]++;
				//print "<span class=tag>".htmlentities2($tag)."</span> ";
		        }
	}

	if (count($stat) > 2) {
		ksort($stat);
                print "<h3>Common tags</h3><p>This is to demonstrate the kind of keywords could serach to find the above images</p>&middot; ";
                foreach ($stat as $word => $count) {
                        if ($count == 1) continue; //cut down on noice a bit
                        $size = min(10,max(log($count),1)); //dont actll care about the count, just showing relative counts
                        $opacity = min(1, 0.3 + ($count / max($word_counts)));
                        print "<span class=nowrap style=\"font-size:{$size}em;opacity: {$opacity};\">".htmlentities($word)."</span> &middot ";
                }
	}

?>
<style>
span.tag {
	display:inline-block;
	white-space:nowrap;
	background-color:lightgreen;
	padding:4px;
	margin:2px;
	border-radius:5px;
}
</style>
<?


####################################################


	$smarty->display('_std_end.tpl');
	exit;

