<?php
/**
 * $Project: GeoGraph $
 * $Id: tile-hectad.json.php 8949 2019-05-17 10:04:25Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2008 Barry Hunter (geo@barryhunter.co.uk)
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

if (empty($_GET['user_id'])) {
	if (date('G') < 6) //early in morning, keep until 6am, when new version should be available
		$target = strtotime(date('Y-m-d').' 06:05 '.date('O'));
	else //otherwise keep until tomorrow morning!
		$target = strtotime(date('Y-m-d',time()+3600*24).' 06:05 '.date('O'));
	$seconds = $target-time();
}
if (empty($seconds) || $seconds < 60)
	$seconds = 3600*6;

customGZipHandlerStart();
customExpiresHeader($seconds,true);
header('Access-Control-Allow-Origin: *');

$sql = array();
$sql['wheres'] = array();

if (!empty($_GET['hectad'])) {
	$db = GeographDatabaseConnection(true);

        $row = $db->getRow("select * from hectad_stat where hectad = ".$db->Quote($_GET['hectad']));

        if (!empty($row['map_token'])) {

                $ri = $row['reference_index'];
                $x = ( intval(($row['x'] - $CONF['origins'][$ri][0])/10)*10 ) +  $CONF['origins'][$ri][0];
                $y = ( intval(($row['y'] - $CONF['origins'][$ri][1])/10)*10 ) +  $CONF['origins'][$ri][1];

		$sql['wheres'][] = "x between $x AND $x+9";
		$sql['wheres'][] = "y between $y AND $y+9";
		$sql['wheres'][] = "reference_index = {$row['reference_index']}"; //just to exclude small number of cross-grids

	} else {
		$error = "unknown hectad";
	}

} else {
	$error = 'no hectad!';
}


if (empty($error)) {

	$sql['tables'] = array();
	$sql['order'] = 'NULL';

	if (empty($_GET['user_id'])) {
		$sql['tables']['gs'] = 'gridsquare gs';
		$sql['columns'] = "grid_reference as gr,x,y,imagecount as c,has_recent as r,percent_land as l,max_ftf as g";
	} elseif (empty($_GET['user_id'])) {
		$sql['tables']['gs'] = 'gridsquare gs';
		$sql['tables']['gi'] = 'LEFT JOIN gridimage gi USING (gridsquare_id)';
		$sql['group'] = 'gridsquare_id';
		$sql['wheres'][] = "moderation_status IN ('geograph','accepted')";
		$sql['columns'] = "grid_reference as gr,x,y,imagecount as c,has_recent as r,percent_land as l,max(ftf) as g";
	} else {
		$sql['tables']['gi'] = 'gridimage_search';
		$sql['group'] = 'grid_reference';
		$sql['wheres'][] = "user_id = ".intval($_GET['user_id']);
		$sql['columns'] = "grid_reference as gr,x,y,count(*) as c,max(ftf) as g";
	}

	$query = sqlBitsToSelect($sql);

	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	$rows = $db->getAssoc($query);

	foreach ($rows as $idx => $row) {
		foreach ($row as $key => $value)
			$rows[$idx][$key] = intval($value);
	}
	$data = array('squares'=>$rows);

} else {
	$data = array('error'=>$error);
}

outputJSON($data);

