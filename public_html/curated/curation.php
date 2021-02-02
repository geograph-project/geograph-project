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

?>
<h2>Tasks in Building a Curated Education Themed Collection</h2>

<?

############################

/*
$stat = $db->getRow("
 select
	count(distinct `group`) as groups,
	count(distinct gridimage_id) as images,
	count(distinct label) as labels,
	count(distinct user_id) as users
from  curated1 where gridimage_id > 0 and active>0");
*/
$stat = $db->getRow("
 select
        count(distinct `group`) as groups,
        sum(images) as images,
        count(distinct label) as labels,
        max(curators) as users
from  curated1_stat");

print "<p><b>{$stat['images']}</b> images collected, on <b>{$stat['labels']}</b> topics (in <b>{$stat['groups']}</b> themes), ";
print "collected by about <b>{$stat['users']}</b> contributors. ";

print "<a href=\"sample.php\">View current collection</a> (work in progress!)</p>";

print "<i>NOTE: At this stage we are still doing the large scale curation, selecting large numbers of images for each topic (like a 'long list'). ";
print "Later on will have a process of refining the collection, to get smaller even higher quality list (the final 'short list')</i>";

print "<hr>";

############################

$count = $db->getOne("SELECT count(*) from curated_headword where description = '' and notes NOT like 'x%'");

if ($count) {
	print "<h4>Sourcing Defintions</h4>";
	print "<p>We recently found the 'Y Termiadur Addysg' dictionary; funded by the Welsh Government as the dictionary of standardised terminology for schools and further education http://www.termiaduraddysg.org/";
	print "<p>As such makes a good starting point of terms that may illistrate well with Geograph images</p>";
	print "<p>To try to help the actual curation (as it contains so many terms) we are going though and trimming the unsuitable terms. At the same time collecting brief defintions, to help make sure curators select the suitable images. ";
	print "If interested, click: <b><a href=\"headwords.php\">$count terms still unchecked</a></b>";
	print "<hr>";
}

############################

/*
$rows = $db->getAll("SELECT `group`,label,count(*) as images,max(updated) as last
                FROM curated1 c WHERE gridimage_id > 0 and active>0 GROUP BY `group`,label order by last desc limit 10");
*/
$rows = $db->getAll("SELECT `group`,label,images,last
                FROM curated1_stat order by last desc limit 10");

if (!empty($rows)) {
	print "<h4>More recent updated (currently being worked on by others)</h4>";
	print "<p>Click a topic to go to the submission form to add images (maybe can find more)</p><ul>";
	foreach ($rows as $row) {
		print "<li><a href=\"collecter.php?group=".urlencode($row['group'])."&label=".urlencode($row['label'])."\">".htmlentities($row['group'])." >> <b>".htmlentities($row['label'])."</b></a></li>";
	}
	print "</ul>";
	print "<p>... or if unsure which to choose, <a href=\"collecter.php\">Click here</a> and we will select one for you!</p>";
	print "<hr>";
}


############################

$rows = $db->getAll("select label,count(*) as images from curated_preselect p left join curated1 c using (gridimage_id,label)
	 where p.active = 1 and c.gridimage_id is null group by label");

if (!empty($rows)) {
	print "<h4>Quick Curation</h4>";
	print "<p>We have a few subjects, where we have preselected images using a keyword query (to be the most likely images).
		Needs going though individually and picking out the better images. (saves you creating searches yourself and
		having manually transfer the images to the submission form)</p><ul>";
	foreach ($rows as $row) {
		$row['group'] = "Geography and Geology";
		print "<li><a href=\"select.php?group=".urlencode($row['group'])."&label=".urlencode($row['label'])."\">".htmlentities($row['group'])." >> <b>".htmlentities($row['label'])."</b></a> ({$row['images']} images to go)</li>";
	}
	print "</ul>";
	print "<p>... or if unsure which to choose, <a href=\"select.php\">Click here</a> and we will select one for you!</p>";
	print "<hr>";
}



############################

/*
$rows = $db->getAll("SELECT `group`,label,sum(gridimage_id > 0 and active>0) as images
                FROM curated1 c GROUP BY `group`,label having images =0");
*/
$rows = $db->getAll("select `group`,label
		 from curated1 c left join curated1_stat s using (label,`group`) where c.gridimage_id = 0 and s.label is null");


if (!empty($rows)) {
	print "<h4>Topics with no images currently</h4>";
	print "<p>Click a topic to be go to submission form to add images (will need to find the images yourself via whatever prefered method)</p><ul>";
	foreach ($rows as $row) {
		print "<li><a href=\"collecter.php?group=".urlencode($row['group'])."&label=".urlencode($row['label'])."\">".htmlentities($row['group'])." >> ".htmlentities($row['label'])."</a></li>";
	}
	print "</ul>";
	print "<hr>";
}

############################


print "<p>Also <a href=\"topics.php\">view all known topics</a>. For <a href=\"topics.php?group=Geography+and+Geology\">Geography and Geology</a> it is also possible to <a href=\"topics.php?group=Geography+and+Geology\">search</a> full topic list, if want to make an early start.</p>";

print "or can view the entire <a href=\"coverage.php\">topic list, by region</a> (so can see how many regions are covered)";

print "<hr>";

############################

$rows= $db->getAssoc("select c.user_id,realname,count(*) count,value from curated1 c inner join user u using (user_id) left join user_preference p on (p.user_id = c.user_id and pkey = 'curated.credit') group by c.user_id order by rand()");


$anon=0;
print "Credits: "; $sep = '';
foreach ($rows as $user_id => $row) {
	if (empty($row['value'])) {
		$anon++;
		continue;
	} elseif($row['value'] == 'nocount') {
		$rows[$user_id]['count'] = null;
	}

	print "$sep <a href=\"/profile/{$user_id}\">".htmlentities($row['realname'])."</a>";
	if ($rows[$user_id]['count'] > 1)
		print "({$rows[$user_id]['count']})";
	$sep = ', ';
}

if (!empty($anon)) {
	if ($sep)
		print " and ";
	print "$anon Anonymous User(s)";
}

if ($USER->registered && isset($rows[$USER->user_id])) {
	print " &middot; <a href=\"credits.php\">Configure how your name appears here</a>";
}


$smarty->display('_std_end.tpl');




