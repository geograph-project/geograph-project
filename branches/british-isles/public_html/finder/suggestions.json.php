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

#########################################
# general page startup

require_once('geograph/global.inc.php');

if (empty($_GET['q'])) {
	die("[]");
}
if (!empty($_GET['callback'])) {
	die("['error']"); //hack to prevent others abusing this script?
}

$q = strtolower(trim($_GET['q']));
$qu = urlencode(trim($_GET['q']));
$total = @intval($_GET['total']);

$remote = file_get_contents("http://suggestqueries.google.com/complete/search?output=toolbar&hl=en&q=$qu");

$output = array();

if (!empty($remote) && preg_match_all('/ data="(.+?)"/',$remote,$m)) {

	$output['suggestions'] = array();
	$sph = GeographSphinxConnection('sphinxql',true);

	$bits = $means = array();
	foreach ($m[1] as $item) {
		if ($item == $q) //sometimes the query is a suggestion!
			continue;
		$sph->query("SELECT id FROM sample8 WHERE MATCH(".$sph->quote($item).") LIMIT 0 OPTION ranker=none");
		$data2 = $sph->getAssoc("SHOW META");
		if (!empty($data2['total_found'])) {
			$output['suggestions'][] = array('query'=>$item, 'total_found'=> $data2['total_found']);

			if ($data2['total_found'] > ($total*2) && strlen($item) < 64 && levenshtein($item,$q) <=2)
                                $means[$item] = $data2['total_found'];
		}
	}

	if (!empty($means) && count($means) ==1 && (count($output['suggestions'])>1 || !empty($total)))
		$output['correction'] = $means;
}

header('Access-Control-Allow-Origin: *'); //although now this allows everyone to access it!
customExpiresHeader(360000);

outputJSON($output);
