<?php
/**
 * $Project: GeoGraph $
 * $Id: viewsearches.php,v 1.5 2005/08/06 12:35:01 barryhunter Exp $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Barry Hunter (geo@barryhunter.co.uk)
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


//these are the arguments we expect
$param=array('execute'=>0,'debug'=>0);

chdir(__DIR__);
require "./_scripts.inc.php";

if (empty($param['execute']))
	$param['debug'] = 1;

############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

############################################

$topic_id = 31185;



$last = $db->getRow("SELECT post_id,(post_time > date_sub(now(),interval 48 hour)) as recent FROM geobb_posts WHERE topic_id = $topic_id AND poster_id=23277 AND post_text LIKE 'Please post pictures of:%' ORDER BY post_id DESC LIMIT 1");

if (!$last) {
	if ($param['debug'])
		print "its the first post!\n";

} elseif ($db->getOne("SELECT post_id FROM geobb_posts WHERE topic_id = $topic_id AND post_id > {$last['post_id']} AND post_text LIKE BINARY '%SKIP%'")) {
	//skipping checking for images, and moving right along.
	if ($param['debug'])
		print "skipping checking";

} elseif ($db->getOne("SELECT post_id FROM geobb_posts WHERE topic_id = $topic_id AND post_id > {$last['post_id']} AND post_text LIKE BINARY '%WAIT%' and post_time > date_sub(now(),interval 24 hour)")) {
	if ($param['debug'])
		print "asked to wait!\n";
	exit;

} elseif ($db->getOne("SELECT post_id FROM geobb_posts WHERE topic_id = $topic_id AND post_id > {$last['post_id']} AND post_time > date_sub(now(),interval 1 hour)")) {
	if ($param['debug'])
		print "first hour\n";
	exit;

} else {

	$rows = $db->getAll("SELECT post_id,post_text,(post_time > date_sub(now(),interval 24 hour)) as recent FROM geobb_posts WHERE topic_id = $topic_id AND post_id > {$last['post_id']} AND post_text LIKE '%[[[%' ORDER BY post_id");

	if (count($rows)) {
		$images = 0;
		$recent = 0; //was there images posted in last 24 hou?
		foreach ($rows as $row) {
			if (preg_match_all('/\[\[\[(\d+)\]\]\]/',$row['post_text'],$m)) {
				$images += count($m[1]);
				$recent = $row['recent'];
			}
		}

		if ($last['recent']) { //first 48 hours!
			if ($images < 40 && $recent) { //require 40 images in first 48 hours!
				if ($param['debug'])
					print "not enough early images yet\n";
				exit;
			}
		} else {
			if ($images < 20 && $recent) {
				if ($param['debug'])
					print "not enough images yet\n";
				exit;
			}
		}
		//we have enough images, so move along...
		if ($param['debug'])
			print "enough... \n";

	//give it 48 hours.
	} elseif ($last['recent']) {
		if ($param['debug'])
			print "no replies yet\n";
		exit;
	}
}

$label = $db->getRow("SELECT label_id,label,description,welsh FROM curated_headword WHERE post_id IS NULL AND description != '' AND description NOT like 'x%' AND notes NOT like 'x%' ORDER BY RAND() LIMIT 1");

if (!empty($label)) {

	$title = $label['label'];
	$message = "Please post pictures of: <br><br><b><big>$title</big></b>";
	if (!empty($label['welsh']))
		$message .= " (Welsh: {$label['welsh']})";

	$message .= "<br><br>".nl2br(htmlentities(strip_tags($label['description'])),false);

	$url = htmlentities("https://www.geograph.org.uk/curated/collecter.php?group=Geography+and+Geology&label=".urlencode($label['label']));
	$message .= "<br><br><a href=\"$url\" target=\"_new\" rel=\"nofollow\">Curation Interface for $title</a>";

	$message .= "<br><br>Remember, while discussions are welcome ONLY post images pertaining to the last requested subject. Always check the last post in the thread posted by socket. ";

	//$message .= "<br><br>The work-in-progress Article is available here: <a href=\"http://www.geograph.org.uk/article/Collaborative-Landforms-Gallery\" target=\"_blank\">Collaborative-Landforms-Gallery</a>";

	$message .= "<!-- (label#{$label['label_id']}) -->";


	$sql = "INSERT INTO geobb_posts SET topic_id = $topic_id,forum_id=6,poster_id=23277,poster_name='socket'";
	$sql .= ",post_time = NOW()";
	$sql .= ",post_text = ".$db->quote($message);

	if (!$param['execute']) {
		print_r($label);
		print "$sql\n\n";
		exit;
	}

	$result = $db->Execute($sql) or die ("Couldn't insert : $sql " . $db->ErrorMsg() . "\n");
        $id = $db->Insert_ID();

        $sql = "UPDATE geobb_topics SET topic_last_post_id = $id,posts_count=posts_count+1 WHERE topic_id = $topic_id";
        $result = $db->Execute($sql) or die ("Couldn't update : $sql " . $db->ErrorMsg() . "\n");

	$sql = "UPDATE curated_headword SET post_id = $id WHERE label_id = {$label['label_id']}";
        $result = $db->Execute($sql) or die ("Couldn't update : $sql " . $db->ErrorMsg() . "\n");

	if ($param['debug'])
		print "SAVED $id\n";

} else {
	print "no id!\n";
}
