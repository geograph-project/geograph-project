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

$links = array('viewgaz4.php' => 'Great Britain','viewgaz3.php' => 'Ireland', 'viewgaz5.php' => 'Isle of Man');

print "&middot; ";
foreach ($links as $link => $name) {
	if ($link == basename($_SERVER['PHP_SELF'])) {
		print "<b>$name</b>";
	} else {
		print "<a href=$link>$name</a>";
	}
	print " &middot; ";
}
print "<hr>";

##################################################

	$where = array();
	$extra = array();
	$name = array();

	$name[] = "Isle of Man";

	if (!empty($_GET['alpha'])) {
		$name[] = htmlentities($_GET['alpha']);
		$where[] = "def_nam LIKE ".$db->Quote($_GET['alpha']."%");
	}

	$name = implode(", ",$name);
	print "<h2>Places in $name</h2>";

	if (!empty($_GET['alpha'])) {
		$where = implode(" AND ",$where);
		$data = $db->getAll("select def_nam,east as e,north as n,has_dup,km_ref,f_code from os_gaz WHERE full_county = 'Isle of Man' AND $where ORDER BY def_nam");

		$codes = $db->getAssoc("select * from os_gaz_code");
	} else {
		$data = $db->getAll("select def_nam,postcode,e,n,f_code,on250,images,has_dup,km_ref,recent from iom_open_places order by postcode, def_nam");
		print "<p>Note: This is only listing City, Town and Villages, not smaller settlements</p>";
	}


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

		list ($gridref,) = $conv->national_to_gridref($row['e'],$row['n'],null,$reference_index);

		$url = urlencode2($row['def_nam']);
		$url = "/near/$url/$gridref?dist=2000";
		$name = htmlentities($row['def_nam']);
		if ($row['has_dup'])
			$name .= "/".$row['km_ref'];

		if ($row['f_code'] == 'T' || !empty($row['on250'])) { //has no City!
			print "<li><b><a href=\"$url\" title=\"{$row['f_code']}\">$name</a></b>";
		} else
			print "<li><a href=\"$url\">$name</a>";

		if (!empty($row['f_code']) && !empty($codes[$row['f_code']]))
			print " <i style=color:gray>{$codes[$row['f_code']]}</i>";


		if (!empty($row['images'])) {
			print " (".number_format($row['images'],0)." images";
			if (!empty($row['recent']) && $row['recent'] > '1000')
				print ", last in ".substr( $row['recent'],0,4);
			print ")";
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
