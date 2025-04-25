<?php
/**
 * $Project: GeoGraph $
 * $Id: viewps.php 2295 2006-07-05 12:15:49Z barryhunter $
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

$_GET['live']= 1;

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;

$db = GeographDatabaseConnection(false);

$conv = new Conversions;
$reference_index = 2;

$smarty->display('_std_begin.tpl');

$ni = 0;

if (!empty($_GET['alpha'])) {
	$where = array();

	$where[] = "name LIKE ".$db->Quote($_GET['alpha']."%");
	$name = htmlentities($_GET['alpha']);

	$where = implode(" AND ",$where);
	$data = $db->getAll("select name,town_class,images,e,n,country,county
		 from ie_open_places where $where order by country desc,county,name");

	print "<h2>Places beginning with $name</h2>";

print "<div style=\"columns: auto 24em\">";

	$last = null;
	foreach($data as $row) {
		if ($last != $row['county']) {
			if ($last) print "</ul></div>";

			$last = $row['county'];
			$name = htmlentities($row['county'].', '.$row['country']);
			print "<div style=\"break-inside: avoid;\">"; //to keep the name with the list!
			print "<h4>$name</h4>";
			print "<ul>";
		}

		list ($gridref,) = $conv->national_to_gridref($row['e'],$row['n'],null,$reference_index);

		$url = urlencode2($row['name']);
		$url = "/near/$url/$gridref?dist=2000";
		$name = htmlentities(to_title_case(strtolower($row['name'])));

		if (preg_match('/(\d+)/',$row['town_class'],$m) && $m[1] <= 3) {
			print "<li><b><a href=\"$url\">$name</a></b>";
		} else
			print "<li><a href=\"$url\">$name</a>";
		if (!empty($row['images']))
			print " (".number_format($row['images'],0)." images)";
		if (!empty($row['country']) && $row['country'] == 'Northern Ireland') $ni = 1;
	}

	if ($last) print "</ul></div>";

print "</div>";

} elseif (!empty($_GET['county'])) {
	$where = array();

	$name = htmlentities(to_title_case(strtolower($_GET['county'])));
	$where[] = "county = ".$db->Quote($_GET['county']);

	if (!empty($_GET['island'])) {
		if ($_GET['island'] == 'mainland')
			$where[] = "island_name = ''";
		else
			$where[] = "island_name = ".$db->Quote($_GET['island']);

		$name .= " (".to_title_case(str_replace('_',' ',$_GET['island'])).")";
	}

	$where = implode(" AND ",$where);
	$data = $db->getAll("select name,town_class,images,e,n, country
		 from ie_open_places where $where order by name");

	print "<h2>Places in $name</h2>";

	foreach($data as $row) {
		if (empty($row['images'])) {
			print "Note: even is no images are listed, can still click to view nearby images";
			break;
		}
	}

print "<div style=\"columns: auto 24em\">";

	$last = null;
	foreach($data as $row) {
		if ($last != substr($row['name'],0,1)) {
			if ($last) print "</ul>";

			$last = substr($row['name'],0,1);
//			print "<h4>$last</h4>";
			print "<ul>";
		}

		list ($gridref,) = $conv->national_to_gridref($row['e'],$row['n'],null,$reference_index);

		$url = urlencode2($row['name']);
		$url = "/near/$url/$gridref?dist=2000";
		$name = htmlentities(to_title_case(strtolower($row['name'])));

		if (preg_match('/(\d+)/',$row['town_class'],$m) && $m[1] <= 3) {
			print "<li><b><a href=\"$url\">$name</a></b>";
		} else
			print "<li><a href=\"$url\">$name</a>";
		if (!empty($row['images']))
			print " (".number_format($row['images'],0)." images)";
		if (!empty($row['country']) && $row['country'] == 'Northern Ireland') $ni = 1;
	}

	if ($last) print "</ul>";

print "</div>";

} else {
	$data = $db->getAll("select country,county,island_name,name,e,n,count(*) as places, sum(images) as images, sum(images>0)/count(*)*100 as percent
		 from ie_open_places group by country desc,county,island_name");

	$islands = array();
	foreach($data as $row)
		if (!empty($row['island_name']))
			$islands[$row['county']]=1;


	print "<h2>Places Directory for Ireland</h2>";

print "<div style=\"columns: auto 36em\">";

	$country = null;

	foreach($data as $row) {
		if ($country != $row['country']) {
			if ($country) print "</ul>";
			print "<h4>".htmlentities($row['country'])."</h4>";
			print "<ul>";
			$country = $row['country'];
		}
		if (empty($row['island_name']) && !empty($islands[$row['county']]))
			$row['island_name'] = "mainland";

		if ($row['places'] === '1') {
			list ($gridref,) = $conv->national_to_gridref($row['e'],$row['n'],null,$reference_index);

			$url = urlencode2($row['name']);
			$url = "/near/$url/$gridref?dist=2000";
		} else {
			$url = "?county=".urlencode($row['county']);
		}

		$name = htmlentities(to_title_case(strtolower($row['county'])));
		if (!empty($row['island_name'])) {
			$url .= "&amp;island=".urlencode($row['island_name']);
			$name .= " (".to_title_case(str_replace('_',' ',$row['island_name'])).")";
		}

		print "<li><b><a href=\"$url\">$name</a></b>";

		print " (".number_format($row['places'],0)." places, ".floatval(round($row['percent'],1))."% photographed";
		if (!empty($row['images']))
			print ", ".number_format($row['images'],0)." images)";
		else
			print ")";
	}

	if ($country) print "</ul>";
	$ni = 1;
print "</div>";

	print "<br><hr>";
	print "If don't know the county, try the first letter of the name: ";
	foreach(range('A','Z') as $alpha)
		print " &nbsp; <a href=\"?alpha=$alpha\">$alpha</a>";

}

if (!empty($ni))
	print "<hr>Note: for Northern Ireland, using a list of places on 250k mapping, so may miss some smaller places";

$smarty->display('_std_end.tpl');
