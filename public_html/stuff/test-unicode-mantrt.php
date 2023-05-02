<?php
/**
 * $Project: GeoGraph $
 * $Id: conversion.php 2960 2007-01-15 14:33:27Z barry $
 *
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2007 Barry Hunter (geo@barryhunter.co.uk)
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

ini_set('display_errors',1);

//this page is explicityl testing getting proper UTF8 from manticore, so wants to make sure browsers treat this as UTF8
// as well as making sure the string functions are all mb_ safe!
ini_set('default_charset','UTF-8');

$sph = GeographSphinxConnection('manticorert', true);

runRow("SELECT * FROM os_gaz WHERE id in (36,10,53653)");

runRow("SELECT * FROM os_gaz_250 WHERE id in (26093,26178)");

runRow("SELECT * FROM loc_placenames WHERE id in (2,257)");

runRow("SELECT * FROM gridimage_group_stat WHERE grid_reference = 'NF6906'");

runRow("SELECT * FROM snippet WHERE id=22756");


function runRow($sql) {
	global $sph;
	$rows = $sph->getAll($sql);

	print "<h2>".htmlentities($sql,ENT_COMPAT,'UTF-8')."</h2>";
	print "<table>";
	foreach ($rows as $row) {
		foreach ($row as $key => $value) {
			print "<tr><th>$key</th>";
			print "<td>".htmlentities($value,ENT_COMPAT,'UTF-8');
			if (!is_numeric($value) && !empty($_GET['detect'])) {
				$enc = mb_detect_encoding($value, 'UTF-8, ISO-8859-15, ASCII');
				print "<td>".$enc;

				$v2 = utf8_to_latin1($value);

				$enc2 = mb_detect_encoding($v2, 'UTF-8, ISO-8859-15, ASCII');
				print "<td>".$enc2;

			}
		}
		print "<tr><td><td><hr>";
	}
	print "</table>";
}
