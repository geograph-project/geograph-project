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

$smarty->assign('responsive', true);
$smarty->display('_std_begin.tpl');

$links = array('viewgaz4.php' => 'Great Britain','viewgaz3.php' => 'Ireland', 'viewgaz6.php' => 'Isle of Man',
	'viewgaz8.php' => 'Channel Islands', 'https://www.geograph.org.gg/search.php'=>'Search', 'https://www.geograph.org/leaflet/islands.php'=>'Map');

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

	$name[] = "Channel Islands";

	if (!empty($_GET['country'])) {
		$name[] = htmlentities($_GET['country']);
		$where[] = "country = ".$db->Quote($_GET['country']);
	}

	if (!empty($_GET['alpha'])) {
		$name[] = htmlentities($_GET['alpha']);
		$where[] = "name LIKE ".$db->Quote($_GET['alpha']."%");
	}

	$name = implode(", ",$name);
	print '<div class="interestBox">';
	print "<h2>Places in $name</h2>";
	print '</div>';

	if (!empty($_GET['alpha'])) {
		//$where[] = "class != 'boundary'";
		print "<p>Note: the count of images is just images nearby, havent checked there is specifically a photo of the feature!</p>";
	} else {
		$where[] = "class = 'place' or type = 'locality'";
		print "<p>Note: This is only listing City, Town and Villages, not other features</p>";
	}

	$where = implode(" AND ",$where);
	$data = $db->getAll("select name,city,osm_id,type,lon,lat, place_rank, concat_ws(', ', if(state='',if(country = 'Guernsey','General',NULL),state),country) as state, country
		from planet_ci where $where group by name,round(lat,1),round(lon,1) order by country,state,name");
		//note, tehre is a county column, but not many 'places' have it!
		//the group by is because tehre are some duplicate names, adding lat/lon is just case any far! (for place's that fine)


	print "<div style=\"columns: auto 24em\">";

	$last = null;
	$alpha = null;
	foreach($data as $row) {
		if (!empty($row['state']) && $last != $row['state']) {
			if ($last) print "</ul></div>";

			$last = $row['state'];
			$name = htmlentities($row['state']);
			print "<div style=\"break-inside: avoid;\">"; //to keep the name with the list!
			print "<h4>$name</h4>";
			print "<ul>";
		}

		$row['name'] = utf8_to_latin1($row['name']);

		$url = "https://www.geograph.org.gg/search.php?location={$row['lat']},{$row['lon']}&go=1";
		$name = htmlentities($row['name']);
		if (!empty($row['city']) && $row['city'] != $row['name'] && $row['city'] != $row['country'])
			 $name .= ", ".htmlentities($row['city']);

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
			print " <a href=\"https://www.geograph.org/leaflet/islands.php#14/{$row['lat']}/{$row['lon']}\">&#128205;</a>";
		}
	}

	if ($last) print "</ul></div>";

	print "</div>";

//	if ($more) {
		$raw = $db->getAll("SELECT country, UPPER(SUBSTRING(name, 1, 1)) as alpha, COUNT(*) as places 
		                    FROM planet_ci 
		                    WHERE `class` != 'boundary' 
		                    GROUP BY country, alpha 
		                    ORDER BY country DESC, alpha ASC");

		$data = [];
		foreach ($raw as $row) {
		    // We only want to store actual letters A-Z for the navigation row
		    if (preg_match('/^[A-Z]$/i', $row['alpha'])) {
		        $data[$row['country']][strtoupper($row['alpha'])] = $row['places'];
		    }
		}

		print "<br><hr>";
		print "Browse Channel Island places by first letter:";

		foreach ($data as $country => $alphas) {
		    print "<div style='margin-top: 10px;'>";
		    print "<strong>" . htmlspecialchars($country) . ":</strong><br>";
		    
		    foreach (range('A', 'Z') as $char) {
		        if (isset($alphas[$char])) {
		            $count = $alphas[$char];
		            // Added class='alpha-nav' for the CSS we discussed earlier
		            $url = "?country=" . urlencode($country) . "&amp;alpha=$char";
		            $title = number_format($count) . " places in $country starting with $char";
		            
		            print " &nbsp; <a href=\"$url\" title=\"$title\">$char</a>";
		        } else {
		            print " &nbsp; <span style=\"color: #ccc;\" title=\"No places\">$char</span>";
		        }
		    }
		    print "</div>";
		}

//	}

##################################################



$smarty->display('_std_end.tpl');
