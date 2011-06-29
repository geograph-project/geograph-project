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

$USER->hasPerm("admin") || $USER->hasPerm("ticketmod") || $USER->hasPerm("mapmod") || $USER->mustHavePerm("moderator");

$smarty = new GeographPage;

$db = NewADOConnection($GLOBALS['DSN']);
if (!$db) die('Database connection failed');  
#$db->debug = true;
	#TODO:
	#  * recreate affected map?
	#      require_once('geograph/mapmosaic.class.php');
	#      $mosaic = new GeographMapMosaic;
	#      $mosaic->expirePosition($x,$y,0,true);
	#  * geocoder line?
	#  * level 5 towns and button to create level 5 towns from towns close to zone boundaries?


	if (isset($_POST['submit'])) {
		require_once('geograph/conversions.class.php');
		$conv = new Conversions;
		require_once('geograph/mapmosaic.class.php');
		$mosaic = new GeographMapMosaic;
		$dryrun = !empty($_POST['dryrun']);
		if ($dryrun)
			$message = "<p>Testing the following changes:</p>";
		else
			$message = "<p>Making the following changes:</p>";
		for ($c = 1; $c <= $_POST['highc']; $c++) {
			if ($_POST['newd'.$c] == '1' && $_POST['oldi'.$c] !== '') {
				$_POST['oldr'.$c] = intval($_POST['oldr'.$c]);
				$_POST['olde'.$c] = intval($_POST['olde'.$c]);
				$_POST['oldn'.$c] = intval($_POST['oldn'.$c]);
				$oldx = $_POST['olde'.$c]+$CONF['origins'][$_POST['oldr'.$c]][0]*1000;
				$oldy = $_POST['oldn'.$c]+$CONF['origins'][$_POST['oldr'.$c]][1]*1000;
				$message .= "<p>Deleting {$_POST['oldi'.$c]}/{$_POST['oldna'.$c]}</p>";
				$sql = "DELETE FROM loc_towns WHERE id=".$db->Quote($_POST['oldi'.$c])." LIMIT 1";
				$message .= "<p><small>$sql</small></p>";
				if (!$dryrun) $db->Execute($sql);
				$message .= "<p>Expiring ".floor($oldx/1000)."/".floor($oldy/1000)."</p>";
				if (!$dryrun) $mosaic->expirePosition(floor($oldx/1000),floor($oldy/1000),0,true);
			}
		}
		for ($c = 1; $c <= $_POST['highc']; $c++) {
			if ($_POST['newd'.$c] == '1')
				continue;
			if (   $_POST['newna'.$c] === $_POST['oldna'.$c]
			    && $_POST['newsn'.$c] === $_POST['oldsn'.$c]
			    && $_POST['newq'.$c]  === $_POST['oldq'.$c]
			    && $_POST['newc'.$c]  === $_POST['oldc'.$c]
			    && $_POST['news'.$c]  === $_POST['olds'.$c]
			    && $_POST['newr'.$c]  === $_POST['oldr'.$c]
			    && $_POST['newe'.$c]  === $_POST['olde'.$c]
			    && $_POST['newn'.$c]  === $_POST['oldn'.$c])
				continue;
			$changesfrom = array();
			$changesto = array();
			$sqlvalues = array();
			$sqlcolumns = array();
			$_POST['newna'.$c] = trim($_POST['newna'.$c]);
			if ($_POST['newna'.$c] === '') {
				$message .= "<p><b>Invalid town name</b>: {$_POST['oldi'.$c]}/<i>{$_POST['oldna'.$c]}</i>: <b>{$_POST['newna'.$c]}</b></p>";
				continue;
			}
			$_POST['newsn'.$c] = trim($_POST['newsn'.$c]);
			if ($_POST['newsn'.$c] === '') {
				$message .= "<p><b>Invalid town name</b>: {$_POST['oldi'.$c]}/<i>{$_POST['oldsn'.$c]}</i>: <b>{$_POST['newsn'.$c]}</b></p>";
				continue;
			}
			if (!preg_match('/^\s*\d+\s*$/',$_POST['newe'.$c]) || !preg_match('/^\s*\d+\s*$/',$_POST['newn'.$c])) {
				$message .= "<p><b>Invalid town position</b>: {$_POST['oldi'.$c]}/<i>{$_POST['oldna'.$c]}</i>: <b>{$_POST['newe'.$c]}, {$_POST['newn'.$c]}</b></p>";
				continue;
			}
			if ($_POST['oldna'.$c] !== $_POST['newna'.$c] || $_POST['oldi'.$c] === '') {
				$changesfrom[] = "name: '{$_POST['oldna'.$c]}'";
				$changesto[]   = "name: '{$_POST['newna'.$c]}'";
				$sqlvalues[]   = $db->Quote($_POST['newna'.$c]);
				$sqlcolumns[]  = "name";
			}
			if ($_POST['oldsn'.$c] !== $_POST['newsn'.$c] || $_POST['oldi'.$c] === '') {
				$changesfrom[] = "short name: '{$_POST['oldsn'.$c]}'";
				$changesto[]   = "short name: '{$_POST['newsn'.$c]}'";
				$sqlvalues[]   = $db->Quote($_POST['newsn'.$c]);
				$sqlcolumns[]  = "short_name";
			}
			if ($_POST['olds'.$c] !== $_POST['news'.$c] || $_POST['oldi'.$c] === '') {
				$_POST['news'.$c] = intval($_POST['news'.$c]);
				if ($_POST['news'.$c] < 1 || $_POST['news'.$c] > 4) {
					$message .= "<p><b>Invalid town size</b>: {$_POST['oldi'.$c]}/<i>{$_POST['oldna'.$c]}</i>: <b>{$_POST['news'.$c]}</b></p>";
					continue;
				}
				$changesfrom[] = "size: {$_POST['olds'.$c]}";
				$changesto[]   = "size: {$_POST['news'.$c]}";
				$sqlvalues[]   = $db->Quote($_POST['news'.$c]);
				$sqlcolumns[]  = "s";
			}
			if ($_POST['oldc'.$c] !== $_POST['newc'.$c] || $_POST['oldi'.$c] === '') {
				$_POST['newc'.$c] = intval($_POST['newc'.$c]);
				if ($_POST['newc'.$c] < 0 || $_POST['newc'.$c] > 999999999) {
					$message .= "<p><b>Invalid community id</b>: {$_POST['oldi'.$c]}/<i>{$_POST['oldna'.$c]}</i>: <b>{$_POST['newc'.$c]}</b></p>";
					continue;
				}
				$changesfrom[] = "cid: {$_POST['oldc'.$c]}";
				$changesto[]   = "cid: {$_POST['newc'.$c]}";
				$sqlvalues[]   = $db->Quote($_POST['newc'.$c]);
				$sqlcolumns[]  = "community_id";
			}
			if ($_POST['oldq'.$c] !== $_POST['newq'.$c] || $_POST['oldi'.$c] === '') {
				$_POST['newq'.$c] = intval($_POST['newq'.$c]);
				if ($_POST['newq'.$c] < -1 || $_POST['newq'.$c] > 4) {
					$message .= "<p><b>Invalid label alignment</b>: {$_POST['oldi'.$c]}/<i>{$_POST['oldna'.$c]}</i>: <b>{$_POST['newq'.$c]}</b></p>";
					continue;
				}
				$changesfrom[] = "align: {$_POST['oldq'.$c]}";
				$changesto[]   = "align: {$_POST['newq'.$c]}";
				$sqlvalues[]   = $db->Quote($_POST['newq'.$c]);
				$sqlcolumns[]  = "quad";
			}
			if ($_POST['oldr'.$c] !== $_POST['newr'.$c] || $_POST['newe'.$c]  !== $_POST['olde'.$c] || $_POST['newn'.$c]  !== $_POST['oldn'.$c] || $_POST['oldi'.$c] === '') {
				$_POST['newr'.$c] = intval($_POST['newr'.$c]);
				if (!array_key_exists($_POST['newr'.$c], $CONF['references'])) {
					$message .= "<p><b>Invalid grid</b>: {$_POST['oldi'.$c]}/<i>{$_POST['oldna'.$c]}</i>: <b>{$_POST['newr'.$c]}</b></p>";
					continue;
				}
				$_POST['newe'.$c] = intval($_POST['newe'.$c]);
				$_POST['newn'.$c] = intval($_POST['newn'.$c]);
				$x = $_POST['newe'.$c]+$CONF['origins'][$_POST['newr'.$c]][0]*1000;
				$y = $_POST['newn'.$c]+$CONF['origins'][$_POST['newr'.$c]][1]*1000;
				if ($_POST['oldi'.$c] !== '') {
					$_POST['oldr'.$c] = intval($_POST['oldr'.$c]);
					$_POST['olde'.$c] = intval($_POST['olde'.$c]);
					$_POST['oldn'.$c] = intval($_POST['oldn'.$c]);
					$oldx = $_POST['olde'.$c]+$CONF['origins'][$_POST['oldr'.$c]][0]*1000;
					$oldy = $_POST['oldn'.$c]+$CONF['origins'][$_POST['oldr'.$c]][1]*1000;
				}
				if ($CONF['commongrid']) {
					list($lat,$long) = $conv->national_to_wgs84($_POST['newe'.$c],$_POST['newn'.$c],$_POST['newr'.$c]);
					list($cx,$cy,$ri) = $conv->wgs84_to_national($lat, $long, true, $CONF['commongrid']);
					$cx = round($cx);
					$cy = round($cy);
				} else {
					$cx = $x;
					$cy = $y;
				}
				$changesfrom[] = "grid,e,n: {$_POST['oldr'.$c]},{$_POST['olde'.$c]},{$_POST['oldn'.$c]}";
				$changesto[]   = "grid,e,n: {$_POST['newr'.$c]},{$_POST['newe'.$c]},{$_POST['newn'.$c]}'";
				$sqlvalues[]   = $db->Quote($_POST['newr'.$c]);
				$sqlcolumns[]  = "reference_index";
				$sqlvalues[]   = $db->Quote($_POST['newe'.$c]);
				$sqlcolumns[]  = "e";
				$sqlvalues[]   = $db->Quote($_POST['newn'.$c]);
				$sqlcolumns[]  = "n";
				$sqlvalues[]   = $db->Quote($x);
				$sqlcolumns[]  = "x";
				$sqlvalues[]   = $db->Quote($y);
				$sqlcolumns[]  = "y";
				$sqlvalues[]   = $db->Quote($cx);
				$sqlcolumns[]  = "cx";
				$sqlvalues[]   = $db->Quote($cy);
				$sqlcolumns[]  = "cy";
				$sqlvalues[]   = "GeomFromText('POINT({$_POST['newe'.$c]} {$_POST['newn'.$c]})')";
				$sqlcolumns[]  = "point_en";
				$sqlvalues[]   = "GeomFromText('POINT({$x} {$y})')";
				$sqlcolumns[]  = "point_xy";
				$sqlvalues[]   = "GeomFromText('POINT({$cx} {$cy})')";
				$sqlcolumns[]  = "point_cxy";
			}
			if ($_POST['oldi'.$c] !== '') {
				$sqlas = array();
				for ($i = 0; $i < count($sqlvalues); ++$i) {
					$sqlas[] = $sqlcolumns[$i] .'='.$sqlvalues[$i];
				}
				$message .= "<p>Updating '#{$_POST['oldi'.$c]} from <i>".implode('; ',$changesfrom)."</i> to <b>".implode('; ',$changesto)."</b>.</p>";
				$sql = 'UPDATE loc_towns SET '.implode(',',$sqlas)." WHERE id=".$db->Quote($_POST['oldi'.$c])." LIMIT 1";
				$message .= "<p><small>$sql</small></p>";
				$message .= "<p>Expiring ".floor($oldx/1000)."/".floor($oldy/1000)."</p>";
				if (!$dryrun) $db->Execute($sql);
				if (!$dryrun) $mosaic->expirePosition(floor($oldx/1000),floor($oldy/1000),0,true);
			} else {
				$message .= "<p>Creating <b>".implode('; ',$changesto)."</b>.</p>";
				$sql = 'INSERT INTO loc_towns ('.implode(',',$sqlcolumns).') VALUES ('.implode(',',$sqlvalues).')';
				$message .= "<p><small>$sql</small></p>";
				if (!$dryrun) $db->Execute($sql);
			}
			if (!$dryrun) $mosaic->expirePosition(floor($x/1000),floor($y/1000),0,true);
			$message .= "<p>Expiring ".floor($x/1000)."/".floor($y/1000)."</p>";
		}
		$message .= "<p>All values updated</p>";
		$smarty->assign('message',  $message);
	}
	
	$where = '';
	#if (!empty($_REQUEST['q'])) {
	#	$a = explode(' ',preg_replace("/[^ \w'\(\)]+/",'',$_REQUEST['q']));
	#	$where = " AND (imageclass LIKE '%".implode("%' OR imageclass LIKE '%",$a)."%' )";
	#	$smarty->assign('q', implode(" ",$a));
	#}
	
	$rilist = array_keys($CONF['references']);
	#$arr = $db->GetArray("select id,name,e,n,s,reference_index,quad from loc_towns where 1 $where order by s,name LIMIT 20");
	#$arr = $db->GetArray("select id,name,e,n,s,reference_index,quad,community_id from loc_towns where 1 $where order by s,name");
	$arr = $db->GetArray("select id,name,short_name,e,n,s,reference_index,quad,community_id from loc_towns where 1 $where order by name");
	if (count($arr)) {
		$e = $arr[0]['e'];
		$n = $arr[0]['n'];
		$ri = $arr[0]['reference_index'];
		$x = floor($e/1000 + $CONF['origins'][$ri][0]);
		$y = floor($n/1000 + $CONF['origins'][$ri][1]);
	} else {
		$ri= $rilist[0];
		$xr = $CONF['xrange'][$ri];
		$yr = $CONF['yrange'][$ri];
		$x = floor(($xr[0] + $xr[1])*.5);
		$y = floor(($yr[0] + $yr[1])*.5);
		#$e = ($x-$CONF['origins'][$ri][0])*1000;
		#$n = ($y-$CONF['origins'][$ri][1])*1000;
	}
	$dbarr = array();
	if (!empty($CONF['ogdb_db'])&&isset($_POST['findlarge'])||isset($_POST['findgiven'])||isset($_POST['findsim'])) {
		$odgbdist = 10; # search distance: up to $odgbdist km
	#500600000
# ludwigsburg: 08118048
# SELECT *  FROM geodb_locations gl LEFT JOIN `geodb_textdata` gt ON (gl.loc_id=gt.loc_id) WHERE `text_val` LIKE '08118048'
# SELECT * 
# FROM geodb_locations gl
# LEFT JOIN `geodb_textdata` gt ON ( gl.loc_id = gt.loc_id ) 
# LEFT JOIN geodb_textdata gt2 ON ( gl.loc_id = gt2.loc_id ) 
# WHERE gt.`text_val` LIKE '08118048'
# ORDER BY gl.loc_id;
		#
#SELECT gt.text_val AS name, co.lat, co.lon, gi.int_val AS size, RPAD(gi2.text_val,8,'9') AS cid
#FROM geodb_textdata as gt
#LEFT JOIN geodb_locations gl ON gl.loc_id = gt.loc_id
#LEFT JOIN geodb_textdata gt2 ON gl.loc_id = gt2.loc_id
#LEFT JOIN geodb_coordinates co ON gl.loc_id = co.loc_id
#LEFT JOIN geodb_intdata gi ON gl.loc_id = gi.loc_id
#LEFT JOIN geodb_textdata gi2 ON gl.loc_id = gi2.loc_id
#WHERE gl.loc_type = 100600000
#  AND gt.text_type = 500100000
#  AND gt2.text_type = 400200000
#  AND gt2.text_val IN('5','6')
#  AND (gi.int_type IS NULL OR gi.int_type=600700000)
#  AND (gi2.text_type IS NULL OR gi2.text_type=500600000)
#  ORDER BY gt2.text_val,gt.text_val
		#
		#$ogdbDSN = $CONF['db_driver'].'://'.$CONF['db_user'].':'.$CONF['db_pwd'].'@'.$CONF['db_connect'].'/'.$CONF['ogdb_db'].$CONF['db_persist'];# allow other user/passwd?
		$ogdbDSN = $CONF['db_driver'].'://'.$CONF['db_user'].':'.$CONF['db_pwd'].'@'.$CONF['db_connect'].'/'.$CONF['ogdb_db'];# allow other user/passwd?
		$ogdb = NewADOConnection($ogdbDSN);
		if (!$ogdb) die('Database connection failed');
		if (isset($_POST['findlarge'])) {
			$limit = max(intval($_POST['findlimit']),5000);
			$sql="
SELECT gt.text_val AS name, co.lat, co.lon, gi.int_val AS size, gt2.text_val AS cid
FROM geodb_textdata as gt
INNER JOIN geodb_locations gl ON gl.loc_id = gt.loc_id
INNER JOIN geodb_coordinates co ON gl.loc_id = co.loc_id
INNER JOIN geodb_intdata gi ON gl.loc_id = gi.loc_id
INNER JOIN geodb_textdata gt2 ON gl.loc_id = gt2.loc_id
WHERE gl.loc_type = 100600000
  AND gt.text_type = 500100000
  AND gi.int_type = 600700000
  AND gt2.text_type = 500600000
  AND gi.int_val >= $limit
  ORDER BY gt.text_val
  ";
			$dbarr=$ogdb->GetArray($sql);
		} else if (isset($_POST['findsim'])) {
			require_once('geograph/conversions.class.php');
			$conv = new Conversions;
			$R = 6378;
			$dlat = rad2deg($odgbdist/$R);
			foreach ($arr as $row) {
				list($lat,$lon) = $conv->national_to_wgs84($row['e'],$row['n'],$row['reference_index']);
				$dlon = rad2deg($odgbdist/($R*cos(deg2rad($lat))));
				$name = $row['name'];
				$name = preg_replace('#[(,/].*$#', '', $name);
				$name = preg_replace('#\s(am|in|bei|vor|im|auf|an|ob|unter|[a-z]\.).*$#', '', $name);
				$name = preg_replace('#\s^.*(Sankt|Bad|Heilbad)\s#', '', $name);
				$sqlname = '%'.$name.'%';
				$sqlreg1 = '[(,/].*'.$name;//FIXME quote name
				#$sqlreg2 = '\s(am|in|bei|vor|im|auf|an|ob|unter|[a-z]\.).*\s'.$name;//FIXME quote name
				$sqlreg2 = '[[:space:]](am|in|bei|vor|im|auf|an|ob|unter|[a-z]\.).*[[:space:]]'.$name;//FIXME quote name
				#trigger_error(" $name $lat $lon $dlat $lon {$row['e']} {$row['n']} {$row['reference_index']} {$row['name']}", E_USER_NOTICE);
				$sql="
SELECT gt.text_val AS name, co.lat, co.lon, gi.int_val AS size, gt2.text_val AS cid
FROM geodb_textdata as gt
INNER JOIN geodb_locations gl ON gl.loc_id = gt.loc_id
INNER JOIN geodb_coordinates co ON gl.loc_id = co.loc_id
INNER JOIN geodb_intdata gi ON gl.loc_id = gi.loc_id
INNER JOIN geodb_textdata gt2 ON gl.loc_id = gt2.loc_id
WHERE gl.loc_type = 100600000
  AND gt.text_type = 500100000
  AND gi.int_type = 600700000
  AND gt2.text_type = 500600000
  AND (co.lat BETWEEN ".$ogdb->Quote($lat-$dlat)." AND ".$ogdb->Quote($lat+$dlat).")
  AND (co.lon BETWEEN ".$ogdb->Quote($lon-$dlon)." AND ".$ogdb->Quote($lon+$dlon).")
  AND gt.text_val LIKE ".$ogdb->Quote($sqlname)."
  AND gt.text_val NOT REGEXP ".$ogdb->Quote($sqlreg1)."
  AND gt.text_val NOT REGEXP ".$ogdb->Quote($sqlreg2)."
  ORDER BY gt.text_val
		  ";
				$dbarr=array_merge($dbarr,$ogdb->GetArray($sql));
				#$dbarr2=$ogdb->GetArray($sql);
				#foreach ($dbarr2 as $row2) {
				#	if (strpos($row2['name'], 'bei '.$name) !== false)
				#		continue;
				#	$dbarr[] = $row2;
				#}
			}
		} elseif (isset($_POST['findgiven'])) {
			if (isset($_POST['findlist'])) {
				$lines = preg_split("/\r\n/", $_POST['findlist']);
				foreach ($lines as $line) {
					$name = trim($line);
					if ($name === '')
						continue;
					$name .= '%';
					$sql="
SELECT gt.text_val AS name, co.lat, co.lon, gi.int_val AS size, gt2.text_val AS cid
FROM geodb_textdata as gt
INNER JOIN geodb_locations gl ON gl.loc_id = gt.loc_id
INNER JOIN geodb_coordinates co ON gl.loc_id = co.loc_id
INNER JOIN geodb_intdata gi ON gl.loc_id = gi.loc_id
INNER JOIN geodb_textdata gt2 ON gl.loc_id = gt2.loc_id
WHERE gl.loc_type = 100600000
  AND gt.text_type = 500100000
  AND gt.text_val LIKE ".$ogdb->Quote($name)."
  AND gi.int_type = 600700000
  AND gt2.text_type = 500600000
  ORDER BY gt.text_val
		  ";
					$dbarr=array_merge($dbarr,$ogdb->GetArray($sql));
				}
			}
		}
	}
	require_once('geograph/conversions.class.php');
	$conv = new Conversions;
	foreach ($dbarr as &$row) {
		list($e, $n, $ri) = $conv->wgs84_to_national($row["lat"], $row["lon"]);
		$row['reference_index'] = $ri;
		$row['e'] = round($e);
		$row['n'] = round($n);
	}
	
	for ($i = 0; $i < 10; ++$i)
		$arr[] = array('id' => '', 'name' => '', 'short_name' => '', 'e' => '', 'n' => '', 's' => '4', 'reference_index' => $rilist[0], 'quad' => 0, 'community_id' => 0);
	
	$smarty->assign('arr',  $arr);
	$smarty->assign('dbarr',$dbarr);
	$smarty->assign('ris',  $CONF['references']);
	$smarty->assign('haveogdb', !empty($CONF['ogdb_db']));

	require_once('geograph/rastermap.class.php');
	require_once('geograph/gridsquare.class.php');
	$square = new Gridsquare();
	$square->loadFromPosition($x, $y);
	#trigger_error(" $x $y {$square->reference_index} {$square->eastings} {$square->northings}", E_USER_NOTICE);

	$rastermap = new RasterMap($square,true,true,false,'latest',-1,true);
	$rastermap->addViewpoint($square->reference_index,$square->eastings,$square->northings,8);
	list($lat,$long) = $conv->gridsquare_to_wgs84($square);
	$smarty->assign('lat', $lat);
	$smarty->assign('long', $long);
	$rastermap->addLatLong($lat,$long);

	$smarty->assign_by_ref('rastermap', $rastermap);
	$smarty->assign('gridref', $square->grid_reference);

	

$smarty->display('admin_towns.tpl');


	
?>
