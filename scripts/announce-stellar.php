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
$param=array('execute'=>false);

chdir(__DIR__);
require "./_scripts.inc.php";

$tty = posix_isatty(STDOUT);

############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

############################################

if (!$db->getOne("SHOW TABLES LIKE 'gridimage_score_daily'"))
	die($tty?"gridimage_score_daily table doesnt exist\n":''); //dont say anything in cron

############################################

$topic_id = 1087;
$forum_id = 6;
$poster_id = 23277;
$poster_name = $db->Quote("socket");

$last = $db->getRow("select * from gridimage_score_daily where showday is null order by submitted desc limit 1");

while (!empty($last)) {
	$gridimage_id = $last['gridimage_id'];

	$check = $db->getRow("SELECT * FROM geobb_posts WHERE topic_id = $topic_id AND (post_text LIKE '%[[$gridimage_id]]%' OR post_text LIKE '%[image id=$gridimage_id%')");

	if ($check) {
		$when = $db->Quote($row['post_time']);
	        $sql = "UPDATE gridimage_score_daily SET showday = $when WHERE gridimage_id = $gridimage_id";
	        if ($param['execute']) {
	                $result = $db->Execute($sql) or die ("Couldn't insert : $sql " . $db->ErrorMsg() . "\n");
	        } else {
        	        print "$sql;\n\n";
			exit;
	        }

		//try again!
		$last = $db->getRow("select * from gridimage_score_daily where showday is null order by submitted desc limit 1");
		continue;
	}

	$message = "While browsing around I happened across this: <br>";
	$message .= "[image id=$gridimage_id]";
	$message .= "<br><br>This is a new experiment to highlight a small sample of images, found by AI, I'll try posting one image a day.";

	if ($tty)
		print "$message\n\n";

        $sql = "INSERT INTO geobb_posts SET topic_id = $topic_id,forum_id=$forum_id,poster_id=$poster_id,poster_name=$poster_name";
        $sql .= ",post_time = NOW()";
        $sql .= ",post_text = ".$db->quote($message);


	if ($param['execute']) {
	        $result = $db->Execute($sql) or die ("Couldn't insert : $sql " . $db->ErrorMsg() . "\n");
	        $id = $db->Insert_ID();
		print "Post ID: $id\n"; //might stil be useful in cron log!
	} else {
		print "$sql;\n\n";
		$id = "@last";
	}

        $sql = "UPDATE geobb_topics SET topic_last_post_id = $id,posts_count=posts_count+1 WHERE topic_id = $topic_id";
	if ($param['execute']) {
	        $result = $db->Execute($sql) or die ("Couldn't insert : $sql " . $db->ErrorMsg() . "\n");
	} else {
		print "$sql;\n\n";
	}

	$sql = "UPDATE gridimage_score_daily SET showday = NOW() WHERE gridimage_id = $gridimage_id";
	if ($param['execute']) {
	        $result = $db->Execute($sql) or die ("Couldn't insert : $sql " . $db->ErrorMsg() . "\n");
	} else {
		print "$sql;\n\n";
	}

	break; //out of the while loop, once done!
}

