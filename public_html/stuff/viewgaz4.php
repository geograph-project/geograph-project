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

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;

$db = GeographDatabaseConnection(false);

$conv = new Conversions;
$reference_index = 1;

$smarty->display('_std_begin.tpl');

$links = array('viewgaz4.php' => 'Great Britain','viewgaz3.php' => 'Ireland', 'viewgaz5.php' => 'Isle of Man', '/finder/places.php'=>'Search', '/mapper/combined.php'=>'Map');

print '<div class="tabHolder" style="max-width:940px">Places in: ';
foreach ($links as $link => $name) {
	if ($link == basename($_SERVER['PHP_SELF'])) {
		print "<a class=tabSelected>$name</a> ";
	} else {
		print "<a class=tab href=$link>$name</a> ";
	}
}
print '</div>';

##################################################

if (!empty($_GET['alpha']) || !empty($_GET['region']) || !empty($_GET['county'])) {
	$where = array();
	$extra = array();
	$name = array();

	if (!empty($_GET['region'])) {
		$name[] = htmlentities($_GET['region']);
		$extra[] = "region=".urlencode($_GET['region']);
		$where[] = "region = ".$db->Quote($_GET['region']);
	}

	if (!empty($_GET['county'])) {
		$name[] = htmlentities($_GET['county']);
		$extra[] = "county=".urlencode($_GET['county']);
		if ($_GET['county'] == 'unknown')
			$where[] = "county_unitary = ''";
		else
			$where[] = "county_unitary = ".$db->Quote($_GET['county']);
	}

	if (!empty($_GET['alpha'])) {
		$name[] = "Beginning with ".htmlentities($_GET['alpha']);
		$where[] = "name1 LIKE ".$db->Quote($_GET['alpha']."%");
	}

	$more = 0;
	if (count($where) < 2) {
		$where[] = "local_type in ('City','Town','Village')";
		$more = 1;
	}

	$where = implode(" AND ",$where);
	$name = implode(", ",$name);

	$data = $db->getAll("select country,county_unitary as county, name1, images, local_type,
		 geometry_x as e, geometry_y as n, local_type in ('City','Town','Village') as b
		 from os_open_places where $where order by country,county_unitary,name1 limit 1000");

	print '<div class="interestBox">';
	print "<h2>Places in $name</h2>";
	print '</div>';
	if ($more)
		print "<p>Note: This is only listing City, Town and Villages, not smaller settlements. See links at bottom for more</p>";


	print "<div style=\"columns: auto 24em\">";

	$last = null;
	$alpha = null;
	foreach($data as $row) {
		if ($last != $row['county']) {
			if ($last) print "</ul></div>";

			$last = $row['county'];
			$name = htmlentities($row['county'].', '.$row['country']);
			if (empty($_GET['county'])) {
				print "<div style=\"break-inside: avoid;\">"; //to keep the name with the list!
			} else {
				print "<div>";
			}
			print "<h4>$name</h4>";
			print "<ul>";
		}
		if (!empty($_GET['county']) && empty($_GET['alpha'])) {
			if ($alpha != substr($row['name1'],0,1)) {
				if ($alpha || $last) print "</ul></div>";

	                        $alpha = substr($row['name1'],0,1);
				print "<div style=\"break-inside: avoid;\">"; //to keep the name with the list!
	                      print "<h4>$alpha</h4>";
        	                print "<ul>";
			}
		}

		list ($gridref,) = $conv->national_to_gridref($row['e'],$row['n'],null,$reference_index);

		$url = urlencode2($row['name1']);
		$url = "/near/$url/$gridref?dist=2000";
		$name = htmlentities($row['name1']);

		if ($row['b']) {
			print "<li><b><a href=\"$url\" title=\"{$row['local_type']}\">$name</a></b>";
		} else
			print "<li><a href=\"$url\">$name</a>";
		if (!empty($row['images']))
			print " (".number_format($row['images'],0)." images)";
	}

	if ($last) print "</ul></div>";

	print "</div>";

	if ($more) {
	        print "<br><hr>";
	        print "If dont see the place looking for, can view list of smaller places, but need the<br> first letter of the name: ";
		$url = "?".implode('&amp',$extra);
        	foreach(range('A','Z') as $alpha)
	                print " &nbsp; <a href=\"$url&amp;alpha=$alpha\">$alpha</a>";
	}


##################################################

} else {
	$data = $db->getAll("select country,county_unitary as county,name1,count(*) as places,sum(images) as images, sum(images>0)/count(*)*100 as percent,
		 geometry_x as e, geometry_y as n
		from os_open_places where local_type in ('City','Town','Village') group by country,county_unitary");

	print '<div class="interestBox">';
	print "<h2>Places Directory for Great Britain</h2>";
	print '</div>';

	print "Note: This is only counting City, Town and Villages, not smaller settlements";

	print "<div style=\"columns: auto 28em\">";

	$country = null;

	foreach($data as $row) {
		if ($country != $row['country']) {
			if ($country) print "</ul>";
			print "<h4>".htmlentities($row['country'])."</h4>";
			print "<ul>";
			$country = $row['country'];
		}

		if (empty($row['county']))
			$row['county'] = 'unknown';

		if ($row['places'] === '1') {
			list ($gridref,) = $conv->national_to_gridref($row['e'],$row['n'],null,$reference_index);

			$url = urlencode2($row['name1']);
			$url = "/near/$url/$gridref?dist=2000";
		} else {
			$url = "?county=".urlencode($row['county']);
		}

		$name = htmlentities($row['county']);

		print "<li><b><a href=\"$url\">$name</a></b>";

		print " (".number_format($row['places'],0)." places";
		if ($row['percent'] < 100)
			print ", ".floatval(round($row['percent'],1))."% photographed";
		if (!empty($row['images']))
			print ", ".number_format($row['images'],0)." images)";
		else
			print ")";
	}

	if ($country) print "</ul>";
	print "</div>";

	print "<br><hr>";
	print "If don't know the county, try the first letter of the name (by region): ";
	print "<table>";
	$regions = $db->getAll("select region,count(*) as places from os_open_places group by region order by geometry_y");
	foreach($regions as $row) {
		print "<tr>";
		print "<th>{$row['region']}";
		print "<td align=right>{$row['places']}";
		$url = "?region=".urlencode($row['region']);
		foreach(range('A','Z') as $alpha)
			print "<td><a href=\"$url&amp;alpha=$alpha\">$alpha</a>";
		print "<th>{$row['region']}";
	}
	print "</table>";
}


$smarty->display('_std_end.tpl');
