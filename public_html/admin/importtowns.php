<?php
/**
 * $Project: GeoGraph $
 * $Id: categories.php 5124 2008-12-25 21:54:38Z barry $
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

$USER->mustHavePerm("admin");

$smarty = new GeographPage;

$db = NewADOConnection($GLOBALS['DSN']);
if (!$db) die('Database connection failed');

$dryrun = !empty($_POST['dryrun']);#true;#FIXME !empty($_POST['dryrun']);
$checkname = !empty($_POST['checkname']);

if (   isset($_POST['submit'])
    && isset($_POST['mincid'])
    && isset($_POST['maxcid'])
    && preg_match('/^\s*\d+\s*$/', $_POST['mincid'])
    && preg_match('/^\s*\d+\s*$/', $_POST['maxcid'])) {

	$mincid = intval($_POST['mincid']);
	$maxcid = intval($_POST['maxcid']);

	set_time_limit(3600*24);

	if (empty($CONF['ogdb_db'])) {
		die('Database not configured');
	}

	#$ogdbDSN = $CONF['db_driver'].'://'.$CONF['db_user'].':'.$CONF['db_pwd'].'@'.$CONF['db_connect'].'/'.$CONF['ogdb_db'];# allow other user/passwd?
	$ogdbDSN = $CONF['db_driver'].'://'.$CONF['db_user'].':'.$CONF['db_pwd'].'@'.$CONF['db_connect'].'/'.$CONF['ogdb_db'].'?new';# allow other user/passwd?
	$ogdb = NewADOConnection($ogdbDSN);
	if (!$ogdb) die('Database connection failed');

	$duplicaterows = array();
	$invalidrows = array();
	$oldrows = array();
	$oldrowsname = array();
	$arr = array();

	$sql = "SELECT gt.text_val AS name, co.lat, co.lon, CAST(gt2.text_val AS SIGNED INTEGER) AS cid
		FROM geodb_textdata as gt
		INNER JOIN geodb_locations gl ON gl.loc_id = gt.loc_id
		INNER JOIN geodb_coordinates co ON gl.loc_id = co.loc_id
		INNER JOIN geodb_textdata gt2 ON gl.loc_id = gt2.loc_id
		WHERE gl.loc_type = 100600000
		AND gt.text_type = 500100000
		AND gt2.text_type = 500600000
		AND CAST(gt2.text_val AS SIGNED INTEGER) BETWEEN '$mincid' AND '$maxcid'
		ORDER BY CAST(gt2.text_val AS SIGNED INTEGER)";
	$dbarr = $ogdb->GetArray($sql);
	$prev = null;
	$skip = false;
	foreach ($dbarr as &$row) {
		if (is_null($prev)) {
			$prev = $row;
			continue;
		}
		#trigger_error("-> {$row['cid']} {$row['name']}", E_USER_NOTICE);
		if ($prev['cid'] === $row['cid']) {
			$duplicaterows[] = $prev;
			$skip = true;
			$prev = $row;
			#trigger_error("-> dup {$prev['cid']} {$prev['name']}", E_USER_NOTICE);
			continue;
		}
		if ($skip) {
			$duplicaterows[] = $prev;
			$skip = false;
			#trigger_error("-> dup2 {$prev['cid']} {$prev['name']}", E_USER_NOTICE);
		} else {
			$arr[] = $prev;
			#trigger_error("-> arr2 {$prev['cid']} {$prev['name']}", E_USER_NOTICE);
		}
		$prev = $row;
	}
	if (!is_null($prev)) {
		if ($skip) {
			$duplicaterows[] = $prev;
			#trigger_error("-> dup3 {$prev['cid']} {$prev['name']}", E_USER_NOTICE);
		} else {
			$arr[] = $prev;
			#trigger_error("-> arr3 {$prev['cid']} {$prev['name']}", E_USER_NOTICE);
		}
	}
	$dbarr = $arr;
	$arr = array();
	$namepatterns = array(
		array('#^((Bad |Heilbad |Seeheilbad |Seebad |Ostseebad |Alt |Groß |Klein |Sankt |Neu |Hohen |Markt |Schwäbisch |Nieder |Ober )?[-\w]+( Land)?)(( bei| beim| vor| vorm| am| an| ob| unter| auf| in| im| der|,| /) [-\w ]+)*( \([^)]+\))?$#', '', '\1'),
		#array('#^((Bad |Heilbad |Seeheilbad |Seebad |Ostseebad |Alt |Groß |Klein |Sankt |Neu |Hohen |Markt )?[-\w]+( Land)?)(( bei| vor| am| an| ob| unter| auf| in| im| der|,| /) [-\w ]+)*$#', '', '\1'),
		#array('#^((Bad |Heilbad |Seeheilbad |Seebad |Ostseebad |Alt |Groß |Klein |Sankt |Neu |Hohen |Markt )?[-\w]+( Land)?) \([^)]+\)$#', '', '\1'),
	);
	foreach ($dbarr as &$row) {
		#trigger_error("--> {$row['cid']} {$row['name']}", E_USER_NOTICE);
		foreach ($namepatterns as $patternrow) {
			$searchpat = $patternrow[0];
			if (preg_match($searchpat, $row['name'])) {
				$replacepat = $patternrow[1];
				$replacetext = $patternrow[2];
				if ($replacepat === '')
					$replacepat = $searchpat;
				$row['shortname'] = preg_replace($replacepat, $replacetext, $row['name']);
				break;
			}
		}
		if (isset($row['shortname'])) {
			$arr[] = $row;
		} else {
			$invalidrows[] = $row;
		}
	}
	$dbarr = $arr;
	$arr = array();
	foreach ($dbarr as &$row) {
		$dbrow = $db->GetRow("select * from loc_towns where community_id='{$row['cid']}' order by s+0 limit 1");
		if ($dbrow === false)
			die('database error');
		if (!count($dbrow)) {
			$arr[] = $row;
		} else {
			$row['dbname'] = $dbrow['name'];
			$row['dbsname'] = $dbrow['short_name'];
			$oldrows[] = $row;
		}
	}
	if ($checkname) {
		$dbarr = $arr;
		$arr = array();
		foreach ($dbarr as &$row) {
			$dbrow = $db->GetRow("select * from loc_towns where community_id between '$mincid' and '$maxcid' and short_name='{$row['shortname']}' order by s+0 limit 1");
			if ($dbrow === false)
				die('database error');
			if (!count($dbrow)) {
				$arr[] = $row;
			} else {
				$row['dbname'] = $dbrow['name'];
				$row['dbsname'] = $dbrow['short_name'];
				$row['dbcid'] = $dbrow['community_id'];
				$oldrowsname[] = $row;
			}
		}
	}

	require_once('geograph/conversions.class.php');
	$conv = new Conversions;
	require_once('geograph/mapmosaic.class.php');
	$mosaic = new GeographMapMosaic;
	foreach ($arr as &$row) {
		list($e, $n, $ri) = $conv->wgs84_to_national($row["lat"], $row["lon"]);
		if (!array_key_exists($ri, $CONF['references'])) {
			die('invalid reference index!'); # FIXME
		}
		$e = round($e);
		$n = round($n);
		$row['reference_index'] = $ri;
		$row['e'] = $e;
		$row['n'] = $n;
		$x = $e+$CONF['origins'][$ri][0]*1000;
		$y = $n+$CONF['origins'][$ri][1]*1000;
		$row['x'] = $x;
		$row['y'] = $y;
		if ($CONF['commongrid']) {
			list($lat,$long) = $conv->national_to_wgs84($e,$n,$ri);
			list($cx,$cy,$cri) = $conv->wgs84_to_national($lat, $long, true, $CONF['commongrid']);
			$cx = round($cx);
			$cy = round($cy);
		} else {
			$cx = $x;
			$cy = $y;
		}
		$row['cx'] = $cx;
		$row['cy'] = $cy;

		$sqlvalues = array();
		$sqlcolumns = array();
		$sqlvalues[]   = $db->Quote($row['name']);
		$sqlcolumns[]  = "name";
		$sqlvalues[]   = $db->Quote($row['shortname']);
		$sqlcolumns[]  = "short_name";
		$sqlvalues[]   = $db->Quote(4);
		$sqlcolumns[]  = "s";
		$sqlvalues[]   = $db->Quote($row['cid']);
		$sqlcolumns[]  = "community_id";
		$sqlvalues[]   = $db->Quote(0);
		$sqlcolumns[]  = "quad";
		$sqlvalues[]   = $db->Quote($ri);
		$sqlcolumns[]  = "reference_index";
		$sqlvalues[]   = $db->Quote($e);
		$sqlcolumns[]  = "e";
		$sqlvalues[]   = $db->Quote($n);
		$sqlcolumns[]  = "n";
		$sqlvalues[]   = $db->Quote($x);
		$sqlcolumns[]  = "x";
		$sqlvalues[]   = $db->Quote($y);
		$sqlcolumns[]  = "y";
		$sqlvalues[]   = $db->Quote($cx);
		$sqlcolumns[]  = "cx";
		$sqlvalues[]   = $db->Quote($cy);
		$sqlcolumns[]  = "cy";
		$sqlvalues[]   = "GeomFromText('POINT({$e} {$n})')";
		$sqlcolumns[]  = "point_en";
		$sqlvalues[]   = "GeomFromText('POINT({$x} {$y})')";
		$sqlcolumns[]  = "point_xy";
		$sqlvalues[]   = "GeomFromText('POINT({$cx} {$cy})')";
		$sqlcolumns[]  = "point_cxy";
		$sql = 'INSERT INTO loc_towns ('.implode(',',$sqlcolumns).') VALUES ('.implode(',',$sqlvalues).')';
		$row['sql'] = $sql;
	}

	if (!$dryrun) {
		foreach ($arr as &$row) {
			$db->Execute($row['sql']);
			$mosaic->expirePosition(floor($row['x']/1000),floor($row['y']/1000),0,true);
		}
	}

	$smarty->assign_by_ref('towns',          $arr);
	$smarty->assign_by_ref('invalidtowns',   $invalidrows);
	$smarty->assign_by_ref('oldtowns',       $oldrows);
	$smarty->assign_by_ref('oldtownsname',   $oldrowsname);
	$smarty->assign_by_ref('duplicatetowns', $duplicaterows);
	$smarty->assign('checkname', $checkname);
	$smarty->assign('dryrun', $dryrun);
	$smarty->assign('mincid', $mincid);
	$smarty->assign('maxcid', $maxcid);
	$smarty->assign('submit', 1);
}

$smarty->display('admin_importtowns.tpl');

