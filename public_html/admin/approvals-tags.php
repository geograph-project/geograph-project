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

$USER->hasPerm("director") || $USER->mustHavePerm("moderator");

$smarty->display('_std_begin.tpl');

$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

##############################

	$class = 'unsafe';
	if (!empty($_GET['c']) && preg_match('/^\w[\w-]+$/',$_GET['c']))
		$class = $_GET['c'];
	$class = $db->Quote($class);
	$data = $db->getAll("
		SELECT tag_id,tagtext,count AS images FROM tag INNER JOIN tag_stat USING (tag_id) WHERE classification = $class ORDER BY tagtext LIMIT 100");

	print "<h2>Flagged Tags</h2>";
	print "<p>These are tags flagged by AI as <b>potential</b> issue. They might be fine, this page is to confirm the ones that need further attention.";
	print "<p>Also, note that the actual images might be in themselves ok, its just that being able to search then using this tag that is the concern.";

		print "<p>Note: the buttons don't do anything in this demo!";

	print "<table cellspacing=0 cellpadding=4 border=1 bordercolor=#eee>";
		print "<tr><th>".implode("</th><th>",array_map('htmlentities',array_keys($data[0])))."</th></tr>";
	foreach($data as $row) {
		//print "<tr><td>".implode("</td><td align=right>",array_map('htmlentities',$row))."</td>";
		print "<tr>";
		foreach($row as $key => $value) {
			if ($key == 'tagtext') {
				$url = urlencode2($value);
				$value = htmlentities($value);
				print "<td><tt><a href=\"/tagged/$url\" target=\"preview\" style=text-decoration:none><b>$value</b></a></tt>";
			} elseif ($key == 'tag_id') {
				print "<td align=right><span style=color:silver>".intval($value);
			} elseif (is_numeric($value)) {
				print "<td align=right>".floatval($value);
			} else {
				print "<td>".htmlentities($value);
			}
		}
		print "<td><button disabled>Flag!</button> <button disabled>Looks Safe</button>";
	}
	print "</table>";

	$smarty->display('_std_end.tpl');


