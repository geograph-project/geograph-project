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

print $CONF['template'].' : '.$_SESSION['responsive']."<hr>";


if (empty($_GET['q'])) {
	die("no gr");
}
if (preg_match('/(_|%)/',$_GET['q']) && strlen($_GET['q']) < 6)
	die("too short");

$crit = "LIKE ".$db->Quote(trim($_GET['q']));

print "<script src=\"".smarty_modifier_revision("/js/geograph.js")."\"></script>";
print "<script src=\"".smarty_modifier_revision("/sorttable.js")."\"></script>";


if (!empty($_GET['all'])) {
	$tables = explode(',','placename_index,sphinx_placenames,loc_abgaz,loc_counties,loc_counties_pre74,loc_placenames,loc_towns,os_open_names,os_gaz,os_gaz_250,os_gaz_old');
} else {
	$tables = explode(',','sphinx_placenames,loc_abgaz,loc_counties,loc_counties_pre74,loc_placenames,loc_towns,os_open_names,os_gaz,os_gaz_250');
}

foreach ($tables as $table) {
	$cols = array();
	$grname = $name = false;
	foreach ($db->getAssoc("DESCRIBE $table") as $field => $row) {
		if ($row['Type'] != 'point') {
			$cols[] = "`$field`";
		}
		if (!$name && preg_match('/varchar\((\d+)\)/',$row['Type'],$m) && intval($m[1]) > 6) {
			$name = $field;
		}
                if (!$grname && ($field == 'km_ref' || $field == 'gridref'  || $field == 'gr') ) {
                        $grname = $field;
                }
        }
        if (!empty($_GET['gr'])) {
                if ($grname)
                        dump_sql_table("SELECT ".implode(',',$cols)." FROM $table WHERE $grname $crit LIMIT 1000","$table.$grname");
                continue;
        }
	if ($table == 'os_gaz' && strpos($_GET['q'],'%') === FALSE) {

		dump_sql_table("SELECT ".implode(',',$cols)." FROM $table WHERE $name $crit LIMIT 1000","$table.$name");

		$crit2 =  "LIKE ".$db->Quote(trim($_GET['q']).'/%');
                dump_sql_table("SELECT ".implode(',',$cols)." FROM $table WHERE $name $crit2 LIMIT 1000","$table.$name (before slash)");

		$crit2 =  "LIKE ".$db->Quote('%/'.trim($_GET['q']));
                dump_sql_table("SELECT ".implode(',',$cols)." FROM $table WHERE $name $crit2 LIMIT 1000","$table.$name (after slash)");

	} elseif ($table == 'os_open_names') {
		$name = 'name1';
                dump_sql_table("SELECT ".implode(',',$cols)." FROM $table WHERE $name $crit LIMIT 1000","$table.$name");
		$name = 'name2';
                dump_sql_table("SELECT ".implode(',',$cols)." FROM $table WHERE $name $crit LIMIT 1000","$table.$name");

	} elseif ($name) {

		dump_sql_table("SELECT ".implode(',',$cols)." FROM $table WHERE $name $crit LIMIT 1000","$table.$name");
	}
}



function dump_sql_table($sql,$title = '') {
	global $db;

	$recordSet = $db->Execute($sql) or die ("Couldn't select photos : $sql " . $db->ErrorMsg() . "\n");

	$row = $recordSet->fields;

	print "<H3>$title</H3>";

	$c= 0;
	print "<TABLE class=\"report sortable\" id=\"photolist\" border='1' cellspacing='0' cellpadding='2'><THEAD><TR>";
	foreach ($row as $key => $value) {
		print "<TH>$key</TH>";
	}
	print "</TR></THEAD><TBODY>";

	while (!$recordSet->EOF) {
		$row = $recordSet->fields;
		print "<TR>";
		$align = "left";
		foreach ($row as $key => $value) {
			print "<TD ALIGN=$align>".htmlentities($value)."</TD>";
			$align = "right";
		}
		print "</TR>";

                if (!empty($_GET['map']) && $c == 0) {
			require_once('geograph/conversions.class.php');
			$conv = new Conversions;

			if (!empty($row['e']) && !empty($row['n']) && !empty($row['reference_index'])) {
				$e = $row['e'];
				$n = $row['n'];
				$ri = $row['reference_index'];
			} elseif (!empty($row['geometry_x']) && !empty($row['geometry_y'])) {
				$e = $row['geometry_x'];
				$n = $row['geometry_y'];
				$ri = 1; //os_open_names
			} elseif (!empty($row['east']) && !empty($row['north'])) {
				$e = $row['east'];
				$n = $row['north'];
				$ri = 1; //os dbs
			} else {
				foreach (array('gr','km_ref','gridref') as $key) {
					if (!empty($row[$key])) {
						$square = new GridSquare();
						if ($grid_ok=$square->setByFullGridRef($row[$key])) {
							list($lat,$long) = $conv->gridsquare_to_wgs84($square);
						}
					}
				}
			}


			if (!empty($e)) {
				list($lat,$long) = $conv->national_to_wgs84($e,$n,$ri);
			}


			if (!empty($lat)) {
				$coord = "$lat,$long";
				print "<img src=\"https://maps.googleapis.com/maps/api/staticmap?markers=size:mid|$coord&zoom=13&key={$CONF['google_maps_api3_key']}&size=300x300&maptype=terrain\">";
			}

                }

		$recordSet->MoveNext();
		$c++;
	}
	print "</TR></TBODY></TABLE>";
	return $c;
}


