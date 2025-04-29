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

$links = array('viewgaz4.php' => 'Great Britain','viewgaz3.php' => 'Ireland', 'viewgaz6.php' => 'Isle of Man', '/finder/places.php'=>'Search', '/mapper/combined.php'=>'Map');

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

	$where = array();
	$extra = array();
	$name = array();

	$name[] = "Isle of Man";

	if (!empty($_GET['alpha'])) {
		$name[] = htmlentities($_GET['alpha']);
		$where[] = "name LIKE ".$db->Quote($_GET['alpha']."%");
	}

	$name = implode(", ",$name);
	print '<div class="interestBox">';
	print "<h2>Places in $name</h2>";
	print '</div>';

	if (!empty($_GET['alpha'])) {
		$where[] = "class != 'boundary'";
		print "<p>Note: the count of images is just images nearby, havent checked there is specifically a photo of the feature!</p>";
	} else {
		$where[] = "class = 'place'";
		print "<p>Note: This is only listing City, Town and Villages, not other features</p>";
	}

	$where = implode(" AND ",$where);
	$data = $db->getAll("select name,osm_id,type,lon,lat,place_rank,images,recent
		from planet_im where $where group by name,round(lat,1),round(lon,1)");
		//note, tehre is a county column, but not many 'places' have it!
		//the group by is because tehre are some duplicate names, adding lat/lon is just case any far! (for place's that fine)


	print "<div style=\"columns: auto 24em\">";

	$last = null;
	$alpha = null;
	foreach($data as $row) {
		if (!empty($row['postcode']) && $last != $row['postcode']) {
			if ($last) print "</ul></div>";

			$last = $row['postcode'];
			$name = htmlentities($row['postcode']);
			print "<div style=\"break-inside: avoid;\">"; //to keep the name with the list!
			print "<h4>$name</h4>";
			print "<ul>";
		}

		list($e,$n,$reference_index) = $conv->wgs84_to_national($row['lat'],$row['lon'],true);
		list ($gridref,) = $conv->national_to_gridref($e,$n,null,$reference_index);

		$row['name'] = utf8_to_latin1($row['name']);

		$url = urlencode2($row['name']);
		$url = "/near/$url/$gridref?dist=2000";
		$name = htmlentities($row['name']);

		if ($row['place_rank'] < 19) {
			print "<li><b><a href=\"$url\">$name</a></b>";
		} else
			print "<li><a href=\"$url\">$name</a>";

		if (!empty($row['type']))
			print " <i style=color:gray>{$row['type']}</i>";

		if (!empty($row['images'])) {
			print " (".number_format($row['images'],0)." images";
			if (!empty($row['recent']) && $row['recent'] > '1000')
				print ", last in ".substr( $row['recent'],0,4);
			print ")";
		} else {
			print " <a href=\"/mapper/combined.php#14/{$row['lat']}/{$row['lon']}\">&#128205;</a>";
		}
	}

	if ($last) print "</ul></div>";

	print "</div>";

//	if ($more) {
	        print "<br><hr>";
	        print "If dont see the place looking for, can view list of smaller places, but need the<br> first letter of the name: ";
		$url = "?".implode('&amp',$extra);
        	foreach(range('A','Z') as $alpha)
	                print " &nbsp; <a href=\"$url&amp;alpha=$alpha\">$alpha</a>";
//	}

##################################################



$smarty->display('_std_end.tpl');
