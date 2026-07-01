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

$_GET['model'] = 'pe';

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	if (empty($db->readonly)) { //by running this, we can actully get accurate stat!
		$db->Execute("SELECT @max_done := COALESCE(MAX(max_id), 0) FROM embedding_progress_pe");
		$db->Execute("INSERT INTO embedding_progress_pe (day, count, min_id, max_id, done) SELECT     substring(updated, 1, 10) AS day,     COUNT(*) AS new_count,     MIN(seq_id) AS new_min_id,     MAX(seq_id) AS new_max_id,     NULL AS done FROM     gridimage_embedding_1024 WHERE     type = 'image' AND model ='pe'    AND seq_id > @max_done  GROUP BY  day ON DUPLICATE KEY UPDATE     count = embedding_progress_pe.count + VALUES(count),      max_id = VALUES(max_id), `done`=NULL");
	}

	$number = $db->getOne("select SUM(count)*2 from embedding_progress_pe"); //only counts type=image

	print "<p><b>".number_format($number,0)."</b> total PE embeddings saved (for image and title), so nominally ".number_format($number/2,0)." images.</p>";

	$row = $db->getRow("SELECT * FROM gridimage_embedding_1024 ORDER BY seq_id DESC LIMIT 1");
	print "<p>Most Recent embedding of <tt>".htmlentities($row['type'])."</tt> for #{$row['gridimage_id']} of length ".(strlen($row['embeddings'])/4)." at <tt>{$row['updated']}</tt>.</p>";

	$number = 2000;
	$crit = $db->getOne("SELECT DATE_SUB(NOW(),INTERVAL 1 HOUR)");
	if ($row['updated'] > $crit) {
		$oldest = $db->getRow("SELECT updated FROM gridimage_embedding_1024 ORDER BY seq_id DESC LIMIT ".($number-1).",1");
		$t1 = strtotime($row['updated']);
		$t2 = strtotime($oldest['updated']);
		$diff_in_seconds = $t1 - $t2;
		print "Stored $number rows in last ".round($diff_in_seconds/60,1)." minutes.";

		if ($diff_in_seconds > 0) { // Avoid division by zero
		    $rows_per_second = $number / $diff_in_seconds;
		    print " (<i style=color:gray>Estimated " . round($rows_per_second*3600, -2) . " rows in last hour</i>)";
		}
	}

	$a = $db->getRow("SELECT floor(avg(gridimage_id)) as id,count(t.gridimage_id) as total, count(l.gridimage_id) as done
		 FROM `tmp_label_pe` t left join gridimage_embedding_1024 l using (gridimage_id)");
	printf('<p>Processing of current batch of %d images, centered around %d, is %.1f%% done. (should see this fluctuating)', $a['total'], $a['id'], $a['done']/$a['total']*100);

	$rows = $db->getAll("SELECT * FROM embedding_progress_pe ORDER BY `day` DESC LIMIT 5");
	print "<p>Last 5 days: ";
	foreach ($rows as $row)
		print "{$row['day']}: {$row['count']} images, ";

	print "<hr>";

	$number = $db->getOne("SELECT COUNT(*) FROM labeler_agent WHERE updated > date_sub(now(),interval 24 hour)");
	print "<p>We seen <b>".number_format($number,0)."</b> processing clients in the last 24 hours.";

	$number = $db->getOne("SELECT COUNT(*) FROM labeler_agent WHERE ipaddr = INET6_ATON('".getRemoteIP()."') AND  updated > date_sub(now(),interval 24 hour)");
	if ($number > 0)
		print " (<b>$number</b> from <u>your</u> IP address)";

	$smarty->display('_std_end.tpl');
	exit;

