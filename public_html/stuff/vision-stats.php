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

	print "<h2>Basic stats for model processing</h2>";
	print "<hr>";

	$number = $db->getOne("SELECT count(*) FROM gridimage_label WHERE model = ".$db->Quote($_GET['model']));
	print "<p><b>".number_format($number,0)."</b> total image-label pairs saved.</p>";

	$row = $db->getRow("SELECT * FROM gridimage_label WHERE model = ".$db->Quote($_GET['model'])." ORDER BY seq_id DESC"); //limit 1 added automatically!
	print "<p>Most Recent <b>".htmlentities($row['label'])."</b> for {$row['gridimage_id']} at {$row['updated']}.</p>";

	print "<hr>";

	$number = $db->getOne("SELECT count(*) FROM gridimage_embedding"); // WHERE model = ".$db->Quote($_GET['model']));
	print "<p><b>".number_format($number,0)."</b> total embeddings saved (for image and title), so nominally ".number_format($number/2,0)." images</p>";

	$row = $db->getRow("SELECT * FROM gridimage_embedding ORDER BY seq_id DESC"); //limit 1 added automatically!
	print "<p>Most Recent for {$row['type']} {$row['gridimage_id']} of length ".(strlen($row['embeddings'])/4)." at {$row['updated']}.</p>";

	print "<hr>";

	$number = $db->getOne("SELECT COUNT(*) FROM labeler_agent WHERE updated > date_sub(now(),interval 24 hour)");
	 print "<p>We seen <b>".number_format($number,0)."</b> processing clients in the last 24 hours";

	$smarty->display('_std_end.tpl');
	exit;

