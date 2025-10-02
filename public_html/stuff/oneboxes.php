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

//customExpiresHeader(3600,false,true);

//	$smarty->assign('page_title','Computer Vision Datasets');
	$smarty->display('_std_begin.tpl',$_SERVER['PHP_SELF']);

	print "<h2>Demonstration of how page behaves with various queries</h2>";
	print "<p> demonstrating entering query in 'enter search query' above!";

$list = array(
	'Plain Query' => 'bridge',
	'General Query' => 'scotland hills',
	'Unabigious Location' => 'Annaghdown',
	'Abigious Location' => 'crawley',
	'Combined Query' => 'church near crawley',
	'Unabigious Combined' => 'forest near SH7050',
	'No Results!' => 'Wisteria Road SE13',
	'Entering a Tag' => '[subject:canal]',
	'Postcode' => 'B15 2TT',
	'Lat/Long' => '56.123,-4.2324',
	'Exact Grid Ref' => 'TQ702403',
	'Hectad' => 'TQ74',
);

foreach ($list as $title => $query) {
	print "<form method=\"get\" action=\"/finder/finder.php\" style=\"float:left; border:1px solid silver;margin:2px;padding:20px; width:300px\">";
	print "<h3 style=margin-top:0>$title</h3>";
	print "<input type=search name=q value=\"$query\">";
	print "<input type=submit value=\"go\">";
	print "</form>";
}

//https://www.geograph.org.uk/search.php?form=simple&q=SH5055&go=Find&type=on
//redirecting to gridsquare page is done by search.php NOT by of.php!
$query = "SH5055";
	print "<form method=\"get\" action=\"/search.php\" style=\"float:left; border:1px solid silver;margin:2px;padding:20px; width:300px\">";
	print "<h3 style=margin-top:0>4fig Grid Reference</h3>";
	print "<input type=hidden name=form value=simple>";
	print "<input type=search name=q value=\"$query\">";
	print "<input type=submit value=\"go\">";
	print "<p>Note this is actully redirected to gridsquare page</p>";
	print "</form>";

print "<hr style=clear:both>";



	$smarty->display('_std_end.tpl');

