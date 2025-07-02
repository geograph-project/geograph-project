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

$_GET['model'] = 'clip';

$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	print "<h2>Basic stats for stored data (model: ClipTheLandscape)</h2>";
	print "<hr>";

	$number = $db->cacheGetOne(3600,"SELECT count(*) FROM gridimage_label WHERE model = ".$db->Quote($_GET['model']));
	print "<p><b>".number_format($number,0)."</b> total image-label pairs saved (can be multiple labels per image) - not updated in real time.</p>";

	$row = $db->getRow("SELECT * FROM gridimage_label WHERE model = ".$db->Quote($_GET['model'])." ORDER BY seq_id DESC LIMIT 1");
	print "<p>Most Recent <b>".htmlentities($row['label'])."</b> (".sprintf('%.1f',$row['score']*100)."%) for image #{$row['gridimage_id']} at <tt>{$row['updated']}</tt>.</p>";

	print "<hr>";

//	$number = $db->getOne("SELECT count(*) FROM gridimage_embedding"); // WHERE model = ".$db->Quote($_GET['model'])); -- currently only one model saved!
	$number = $db->getOne("SELECT TABLE_ROWS FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gridimage_embedding'");
	print "<p><b>".number_format($number,0)."</b> total CLIP embeddings saved (for image and title), so nominally ".number_format($number/2,0)." images.</p>";

	$row = $db->getRow("SELECT * FROM gridimage_embedding ORDER BY seq_id DESC LIMIT 1");
	print "<p>Most Recent embedding of <tt>".htmlentities($row['type'])."</tt> for #{$row['gridimage_id']} of length ".(strlen($row['embeddings'])/4)." at <tt>{$row['updated']}</tt>.</p>";

	$number = 1000;
	$crit = $db->getOne("SELECT DATE_SUB(NOW(),INTERVAL 1 HOUR)");
	if ($row['updated'] > $crit) {
		$oldest = $db->getRow("SELECT updated FROM gridimage_embedding ORDER BY seq_id DESC LIMIT ".($number-1).",1");
		$t1 = strtotime($row['updated']);
		$t2 = strtotime($oldest['updated']);
		$diff_in_seconds = $t1 - $t2;
		print "Stored $number rows in last ".round($diff_in_seconds/60,1)." minutes.";

		if ($diff_in_seconds > 0) { // Avoid division by zero
		    $rows_per_second = $number / $diff_in_seconds;
		    print " (<i style=color:gray>Estimated " . round($rows_per_second*3600, -2) . " rows in last hour</i>)";
		}
	}

	print "<hr>";

	$number = $db->getOne("SELECT COUNT(*) FROM labeler_agent WHERE updated > date_sub(now(),interval 24 hour)");
	print "<p>We seen <b>".number_format($number,0)."</b> processing clients in the last 24 hours.";

	$number = $db->getOne("SELECT COUNT(*) FROM labeler_agent WHERE ipaddr = INET6_ATON('".getRemoteIP()."') AND  updated > date_sub(now(),interval 24 hour)");
	if ($number > 0)
		print " (<b>$number</b> from <u>your</u> IP address)";

	$smarty->display('_std_end.tpl');
	exit;

