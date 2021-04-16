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

header("Content-Type: text/plain");

$db = GeographDatabaseConnection(false);

//curated list of threads
$topic_ids = array(
	'14625', //The "Show us one of your pictures" thread
	'1006', //refelctions
	'14121', //paintings
        '17542', //all need is light
	'14867', //square
	'15315', //Building a Themed Gallery : Landforms
	'15654', //Place name elements and named features on OS maps (only because the photo is curated, tend to be nice!)
	'3102', //Random Thumbs: a Judging Challenge
	'2853', //Celebrating personal milestones (variable - but worth highlighting!)
        '17652', //Suggest home page pics here
	'25057', //best of hamsters 2013
	'22765', //Show me one of MY images!
#	'', //
);

//add all the monthly threads...
//$topic_ids = array_merge($topic_ids,$db->getCol("SELECT topic_id FROM geobb_topics WHERE forum_id = 6 AND topic_title LIKE 'Your own monthly photo competition%'"));
$topic_ids = array_merge($topic_ids,$db->getCol("SELECT topic_id FROM geobb_topics WHERE forum_id = 6 AND topic_title LIKE 'YOMP %'"));

//add all the tweeted selection
$topic_ids = array_merge($topic_ids,$db->getCol("SELECT topic_id FROM geobb_topics WHERE topic_title like '%tweet%' and forum_id in (6,11)"));

//add all the poty threads
$topic_ids = array_merge($topic_ids,$db->getCol("SELECT topic_id FROM geobb_topics WHERE forum_id = 17"));

$col = $db->getCol($sql = "select gridimage_id from geobb_posts inner join gridimage_post using (post_id,topic_id) where topic_id in (".implode(',',$topic_ids).") order by seq_id desc limit 300");
print implode("\n",$col);

if (!empty($_GET['sql']))
	print $sql;


print "\npotd:\n";
//recent potd
$col = $db->getCol("select gridimage_id from gridimage_daily where showday < date(now()) and showday is not null order by showday desc limit 30");
print implode("\n",$col);

print "\nthumbed:\n";
//thumbed!
$col = $db->getCol("select id from vote_stat as vs where type='img' and num > 1 order by last_vote desc limit 60");
print implode("\n",$col);

print "\nmod:\n";
//thumbed!
$col = $db->getCol("select id from vote_stat as vs where type='mod' and `avg` > 3 order by last_vote desc limit 200");
print implode("\n",$col);




