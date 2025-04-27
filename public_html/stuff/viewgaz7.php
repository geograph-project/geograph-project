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


$all = array();

$codes = $db->getAssoc("select * from os_gaz_code");

	$_GET['adm1'] = 'Isle_of_Man';

##################################################
//original - from places.php
 //actully this one is not used any more in places.php (so hide by default to avoid confusion)

if (!empty($_GET['old'])) {

				if (preg_match('/^\w+/',$_GET['adm1'])) {
                                        $sql = "SELECT co_code,name as adm1_name FROM os_gaz_county WHERE name LIKE ".$db->Quote($_GET['adm1'])." LIMIT 1";
                                        $placename = $db->GetRow($sql);
                                } else {
                                        die("adm1 error");
                                }

                                $sql = "SELECT placename_id,full_name,c,gridimage_id, f_code, km_ref, has_dup
                                FROM gridimage_os_gaz left join os_gaz on (seq = placename_id - 1000000)
                                WHERE gridimage_os_gaz.co_code = '{$placename['co_code']}'";

		if (!empty($_GET['alpha']))
	                $sql .= " AND full_name LIKE ".$db->Quote($_GET['alpha']."%");

				//gridimage_os_gaz is needed to get c/images, but os_gaz is also needed to find the original f_code!
	$counts = $db->GetAssoc($sql);

	foreach ($counts as $id => $row) {
                $url = urlencode2($row['full_name']);
		$url = "/near/$url/".$row['km_ref'];

		$name = $row['full_name'];
		$all[$name]['orig'] = "<a href=\"$url\">".$codes[$row['f_code']]." (".$row['c'].")</a>";
	}
	$all['__Total']['orig'] = count($counts);

}
##################################################
// 'preview' in places! (its now enabled by default!)

	$ri = $reference_index;

                                        $filter = "county LIKE ".$db->Quote($_GET['adm1']); //underscores already work as wildcards

                                $sql = "SELECT placename_id,Place as full_name,images as c,  f_code
                                FROM sphinx_placenames left join os_gaz on (seq = placename_id - 1000000)
				 where reference_index = $ri AND images > 0 AND sphinx_placenames.$filter ORDER BY Place";

	$counts = $db->GetAssoc($sql);

	foreach ($counts as $id => $row) {
                $url = urlencode2($row['full_name']);
		$url = "/place/$url"; //dont add gr, we know it 'unique', or has already had the gridref added!

		$name = preg_replace('#/[A-Z]{2}\d{4}#','',$row['full_name']);

$name = preg_replace("/'s$/",'s',$name); //st johns/etc

		$all[$name]['pre'] = "<a href=\"$url\">".$codes[$row['f_code']]." (".$row['c'].")</a>";
	}
	$all['__Total']['pre'] = count($counts);

##################################################
//new iom_open_places

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
                $data = $db->getAll("select def_nam,east as e,north as n,has_dup,km_ref,f_code,'?' as images from os_gaz WHERE full_county = 'Isle of Man' AND $where ORDER BY def_nam");
        } else {
                $data = $db->getAll("select def_nam,postcode,e,n,f_code,on250,images,has_dup,km_ref,recent from iom_open_places order by postcode, def_nam");
        }


	foreach ($data as $row) {
                list ($gridref,) = $conv->national_to_gridref($row['e'],$row['n'],null,$reference_index);

                $url = urlencode2($row['def_nam']);
                $url = "/near/$url/$gridref?dist=2000";

		$name = $row['def_nam'];

$name = preg_replace("/'s$/",'s',$name); //st johns/etc

		$all[$name]['places'] = "<a href=\"$url\">".$codes[$row['f_code']]." (".$row['images'].")</a>";
	}
	$all['__Total']['places'] = count($data);

##################################################
//OSM Names

	$where = array();
	$extra = array();
	$name = array();

	$name[] = "Isle of Man";

	if (!empty($_GET['alpha'])) {
		$name[] = htmlentities($_GET['alpha']);
		$where[] = "name LIKE ".$db->Quote($_GET['alpha']."%");
	}

	$name = implode(", ",$name);

	if (!empty($_GET['alpha'])) {
		$where[] = "class != 'boundary'";
	} else {
		$where[] = "class = 'place'";
	}

	$where = implode(" AND ",$where);
	$data = $db->getAll("select name,osm_id,type,lon,lat,place_rank,images,recent
		from planet_im where $where group by name,round(lat,1),round(lon,1)");

	foreach ($data as $row) {
                $row['name'] = utf8_to_latin1($row['name']);

                list($e,$n,$reference_index) = $conv->wgs84_to_national($row['lat'],$row['lon'],true);
                list ($gridref,) = $conv->national_to_gridref($e,$n,null,$reference_index);

                $url = urlencode2($row['name']);
                $url = "/near/$url/$gridref?dist=2000";

		$name = $row['name'];

$name = preg_replace('/^The (.*)/','$1, The', $name); //to make consistent is OS!
$name = preg_replace("/'s$/",'s',$name); //st johns/etc
$name = preg_replace("/^Saint /",'St ',$name); //for consistent with OS!

		$all[$name]['osm'] = "<a href=\"$url\">".$row['type']." (".$row['images'].")</a>";
	}
	$all['__Total']['osm'] = count($data);

##################################################

ksort($all);

print "<table cellspacing=0 cellpadding=3 border=1 bordercolor=#eee>";
print "<tr>";
	print "<td>";
	if (!empty($_GET['old']))
		print "<th>Old";
	print "<th>Original";
	print "<th>Revamped";
	print "<th>OSM";
foreach ($all as $name => $data) {
	print "<tr>";
	print "<th>".htmlentities($name);
if (!empty($_GET['old'])) {
	if (isset($data['orig'])) {
		print "<td align=right>".$data['orig'];
	} else {
		print "<td style=background-color:#eee>-";
	}
}
	if (isset($data['pre'])) {
		print "<td align=right>".$data['pre'];
	} else {
		print "<td style=background-color:#eee>-";
	}
	if (isset($data['places'])) {
		print "<td align=right>".$data['places'];
	} else {
		print "<td style=background-color:#eee>-";
	}
	if (isset($data['osm'])) {
		print "<td align=right>".$data['osm'];
	} else {
		print "<td style=background-color:#eee>-";
	}
}
print "</table>";

##################################################



$smarty->display('_std_end.tpl');
