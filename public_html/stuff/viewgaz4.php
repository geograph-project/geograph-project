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

$links = array('viewgaz4.php' => 'Great Britain','viewgaz3.php' => 'Ireland', 'viewgaz5.php' => 'Isle of Man',  'viewgaz8.php' => 'Channel Islands', 'https://geo-en.hlipp.de/search.php' => 'Germany');

print '<div class="tabHolder" style="max-width:940px">Places in: ';
foreach ($links as $link => $name) {
	if ($link == basename($_SERVER['PHP_SELF'])) {
		print "<a class=tabSelected href=$link>$name</a> ";
	} else {
		print "<a class=tab href=$link>$name</a> ";
	}
}
print '</div>';

##################################################

$column = 'name1'; //as display_name

function display_swithcer_cym() {
	global $column;
	if (empty($_GET['name']))
		$_GET['name'] = 'english';

	//these are fragments for use within concat_ws!
	$english = "if(name1_lang='' OR name1_lang='eng',name1,NULL), if((name2 != '' AND name2_lang='') OR name2_lang='eng',name2,NULL)";
	$welsh = "if(name1_lang='cym',name1,NULL), if(name2_lang='cym',name2,NULL)";

	//better version, now os_open_places now always has english in name1!
	$english = "name1,IF(name2_lang='eng',name2,NULL)";
	$welsh = "IF(name2_lang='cym',name2,NULL)";

	if ($_GET['name'] == 'english') {
		$column = "CONCAT_WS(' / ',$english)"; //can show TWO english names
	} elseif ($_GET['name'] == 'welsh') {
		$column = "CONCAT_WS(' / ',$welsh,if(name2_lang='' OR name2_lang='eng',name1,NULL))"; //need to make sure still pick the 'unknown' name (when no welsh!)
	} elseif ($_GET['name'] == 'ency') {
		$column = "CONCAT_WS(' / ',$english,$welsh)";
	} elseif ($_GET['name'] == 'cyen') {
		$column = "CONCAT_WS(' / ',$welsh,$english)"; //if no welsh, then english/unknown will still be shown.
	}

	$options = array('english'=>'English','welsh'=>'Welsh','ency'=>'English/Welsh','cyen'=>'Welsh/English');
	$url = htmlentities("?".http_build_query($_GET));
	print "Names: &middot; ";
	foreach ($options as $link => $name) {
		if ($_GET['name'] == $link) {
			print "<b>$name</b> ";
		} else {
			print "<a href=$url&amp;name=$link>$name</a> ";
		}
		print " &middot; ";
	}
}

function display_swithcer_gla() {
	global $column;
	if (empty($_GET['name']))
		$_GET['name'] = 'gaelic';

	//these are fragments for use within concat_ws!
	$english = "if(name1_lang='' OR name1_lang='eng',name1,NULL), if((name2 != '' AND name2_lang='') OR name2_lang='eng',name2,NULL)";
	$gaelic = "if(name1_lang='gla',name1,NULL), if(name2_lang='gla',name2,NULL)";

	//better version, now os_open_places now always has english in name1!
	$english = "name1,IF(name2_lang='eng',name2,NULL)";
	$gaelic = "IF(name2_lang='gla',name2,NULL)";

	if ($_GET['name'] == 'english') {
		$column = "CONCAT_WS(' / ',$english)"; //can show TWO english names
	} elseif ($_GET['name'] == 'gaelic') {
		$column = "CONCAT_WS(' / ',$gaelic,if(name2_lang='' OR name2_lang='eng',name1,NULL))"; //need to make sure still pick the 'unknown' name (when no gaelic!)
	} elseif ($_GET['name'] == 'engl') {
		$column = "CONCAT_WS(' / ',$english,$gaelic)";
	} elseif ($_GET['name'] == 'glen') {
		$column = "CONCAT_WS(' / ',$gaelic,$english)"; //if no gaelic, then english/unknown will still be shown.
	}

	$options = array('english'=>'English','gaelic'=>'Gaelic','engl'=>'English/Gaelic','glen'=>'Gaelic/English');
	$url = htmlentities("?".http_build_query($_GET));
	print "Names: &middot; ";
	foreach ($options as $link => $name) {
		if ($_GET['name'] == $link) {
			print "<b>$name</b> ";
		} else {
			print "<a href=$url&amp;name=$link>$name</a> ";
		}
		print " &middot; ";
	}
}

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
			$where[] = "full_county = ''";
		else
			$where[] = "full_county = ".$db->Quote($_GET['county']);
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

	print '<div class="interestBox">';
	print "<h2>Places in $name</h2>";
	if (!empty($_GET['county']) && preg_match('/ - /',$_GET['county'])) {
		display_swithcer_cym(); //sets $column for display_name
	}
	if (!empty($_GET['county']) && in_array($_GET['county'],array('Na h-Eileanan an Iar','East Ayrshire','Highland','Argyll and Bute'))) {
		display_swithcer_gla(); //sets $column for display_name
	}
	print '</div>';
	if ($more)
		print "<p>Note: This is only listing City, Town and Villages, not smaller settlements. See links at bottom for more</p>";
	/* original - before adding join on sphinx_placenames
	$data = $db->getAll("select country,full_county as county, name1, $column as display_name, images, local_type,
		 geometry_x as e, geometry_y as n, local_type in ('City','Town','Village') as b
		 from os_open_places where $where order by country,full_county,display_name limit 1000");
	*/

	//this joins in sphinx_placenames, but must do it via os_spatial_index (which has precomputed placename_id column!!!)
	// NOTE. we DONT ue os_spatial_index.images column, while technically more recent, it might not be as accurate! (has no formal update system yet!!)
		$where = preg_replace('/\b(name1|local_type|full_county)\b/','o.$1',$where);
		$column = preg_replace('/\b(name1|local_type|full_county)\b/','o.$1',$column);
	$data = $db->getAll("select o.country,o.full_county as county, o.name1, $column as display_name, o.images, o.local_type,
		 o.geometry_x as e, o.geometry_y as n, o.local_type in ('City','Town','Village') as b, Place
		 from os_open_places o left join os_spatial_index using (id) left join sphinx_placenames using (placename_id)
		where $where order by country,full_county,display_name limit 1000");


	if (!empty($_GET['sort']))
		$data = sort_county($data,'display_name');

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
			if ($alpha != substr($row['display_name'],0,1)) {
				if ($alpha || $last) print "</ul></div>";

	                        $alpha = substr($row['display_name'],0,1);
				print "<div style=\"break-inside: avoid;\">"; //to keep the name with the list!
	                      print "<h4>$alpha</h4>";
        	                print "<ul>";
			}
		}

		list ($gridref,) = $conv->national_to_gridref($row['e'],$row['n'],null,$reference_index);

		if (!empty($row['Place'])) {
			//the /place/ splace is based on sphinx_placenames.
			// os_spatial_index.placename_id is materialsed to only select if the sphinx placename is good coordinate!
			$url = urlencode2($row['Place']);
			//$url = "/place/$url"; //this matches where Place = ...
			$url = "/near/$url"; // this is a real 'nearby' search
		} else {
			$url = urlencode2($row['name1']);
			$url = "/near/$url/$gridref?dist=2000";
		}
		$name = htmlentities($row['display_name']); //utf8_to_latin1 ??

			$name = str_replace(' / ','<span style=color:silver> / </span><span style=color:brown>',$name)."</span>";


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
	$data = $db->getAll("select country,full_county as county,name1,count(*) as places,sum(images) as images, sum(images>0)/count(*)*100 as percent,
		 geometry_x as e, geometry_y as n
		from os_open_places where local_type in ('City','Town','Village') group by country,full_county");

	if (!empty($_GET['sort']))
		$data = sort_country($data, 'country', 'county');

	print '<div class="interestBox">';
	print "<h2>Places Directory for Great Britain</h2>";
	$smarty->display('_location-search.tpl');
	print '</div>';

	print "Note: This is only counting City, Town and Villages, not smaller settlements";

?>
<style>
.county-prefix {
	font-weight: normal;
}
</style>
<?

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

		if (!empty($_GET['sort'])) {
			$name = highlight_county_prefix($row['county']); //automatically escapes too!
		} else {
			$name = htmlentities($row['county']);
		}

		if ($row['country'] == 'Wales') {
			$name = preg_replace('/^(\w+) - \1$/','$1',$name);
			$name = str_replace(' - ','<span style=color:silver> - </span><span style=color:brown>',$name)."</span>";
		}

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
	print "If don't know the county, try the first letter of the name (by region): <br><br>";

	$raw = $db->getAll("SELECT region, UPPER(SUBSTRING(name1, 1, 1)) as alpha, MIN(geometry_y) as lat, COUNT(*) as places
	                    FROM os_open_places GROUP BY region, alpha ORDER BY lat ASC, alpha ASC");

	$data = [];
	foreach ($raw as $row) {
	    // Group the letters under their respective regions
	    $data[$row['region']]['letters'][$row['alpha']] = $row['places'];
	    // Keep a running total of places per region
	    $data[$row['region']]['total'] = ($data[$row['region']]['total'] ?? 0) + $row['places'];
	}

	print "<table>";
	foreach ($data as $regionName => $info) {
	    $urlBase = "?region=" . urlencode($regionName);

	    print "<tr>";
	    print "<th>" . htmlspecialchars($regionName) . "</th>";
	    print "<td align='right'>" . number_format($info['total']) . "</td>";

	    // Generate A-Z, but only link the ones that exist in our data
	    foreach (range('A', 'Z') as $char) {
		if (isset($info['letters'][$char])) {
	            $count = $info['letters'][$char];
	            print "<td><a href=\"{$urlBase}&amp;alpha={$char}\" title=\"{$count} places\">{$char}</a></td>";
	        } else {
	            // Style the unused letters (greyed out or just plain text)
	            print "<td style='color: #ccc;'>{$char}</td>";
	        }
	    }

	    print "<th>" . htmlspecialchars($regionName) . "</th>";
	    print "</tr>";
	}
	print "</table>";
}


$smarty->display('_std_end.tpl');



/**
 * Sorts data by country first, then by county with prefixes ignored.
 */
function sort_country(array $data, string $countryCol, string $countyCol): array {
    usort($data, function($a, $b) use ($countryCol, $countyCol) {

        // 1. First, compare the Country (Standard alphabetical)
        $countryCmp = strnatcasecmp($a[$countryCol], $b[$countryCol]);

        // 2. If countries are different, return that comparison result immediately
        if ($countryCmp !== 0) {
            return $countryCmp;
        }

        // 3. If countries are the same, compare the "Cleaned" County names
        $countyA = transform_for_sorting($a[$countyCol]);
        $countyB = transform_for_sorting($b[$countyCol]);

        return strnatcasecmp($countyA, $countyB);
    });

    return $data;
}

/**
 * Sorts an array of associative arrays by a county column, 
 * ignoring specific prefixes like 'City of', 'North', etc.
 */
function sort_county(array $data, string $column): array {
    usort($data, function($a, $b) use ($column) {
        $nameA = transform_for_sorting($a[$column]);
        $nameB = transform_for_sorting($b[$column]);

        return strnatcasecmp($nameA, $nameB);
    });

    return $data;
}

/**
 * Helper to strip prefixes for sorting logic
 */
function transform_for_sorting(string $str): string {
    // 1. Remove "The" from the very start
    $str = preg_replace('/^The\s+/i', '', $str);

    // 2. Define prefixes to ignore. 
    // Using a negative lookahead (?!Riding) to ensure "East Riding" stays as "East"
    $prefixes = [
        'City of ',
        'Sir y ',
        'Sir ',
        'County of ',
        'County ',
        'Central ',
        'Greater ',
        'North ',
        'South ',
        'East\s+(?!Riding)',
        'West '
    ];

    foreach ($prefixes as $prefix) {
        // Replace the prefix if it exists at the start of the string
        $str = preg_replace('/^' . $prefix . '/i', '', $str);
    }

    return trim($str);
}

/**
 * Wraps recognized prefixes in a span for CSS styling.
 */
function highlight_county_prefix(string $name): string {
    // The order here matters: longer/more specific prefixes first
    $prefixes = [
        'The City of ',
        'City of ',
        'Sir y ',
        'Sir ',
        'County of ',
        'County ',
        'Central ',
        'Greater ',
        'North ',
        'South ',
        'East\s+(?!Riding)',
        'West ',
        'The '
    ];

    foreach ($prefixes as $p) {
        // Pattern: Start of string (^), case-insensitive (/i)
        $pattern = '/^(' . $p . ')/i';
        
        // If we find a match, wrap it and stop (so we don't wrap twice)
        if (preg_match($pattern, $name, $matches)) {
            $prefixText = $matches[1];
            $remainder = substr($name, strlen($prefixText));
            return '<span class="county-prefix">' . htmlspecialchars($prefixText) . '</span>' . htmlspecialchars($remainder);
        }
    }

    return htmlspecialchars($name);
}
