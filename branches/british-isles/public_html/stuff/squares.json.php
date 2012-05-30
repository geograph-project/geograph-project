<?php
/**
 * $Project: GeoGraph $
 * $Id: clusters.php 5786 2009-09-12 10:18:04Z barry $
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

if (!empty($_GET['callback'])) {
	header('Content-type: text/javascript');
} else {
	header('Content-type: application/json');
}

customExpiresHeader(3600);

$sql = array();
$sql['wheres'] = array();

if (!empty($_GET['bounds'])) {
	$b = str_replace('Bounds','',$_GET['bounds']);
	$b = str_replace('(','',$b);
	$b = str_replace(')','',$b);

	$b = explode(',',$b);

	$span = max($b[3] - $b[1],$b[2] - $b[0]);

	//TODO!
	#$ire = ($lat > 51.2 && $lat < 55.73 && $long > -12.2 && $long < -4.8);
	#$uk = ($lat > 49 && $lat < 62 && $long > -9.5 && $long < 2.3);

	if ($span > 0.22) {
		$error = "Zoom in closer to the British Isles";
	} else {
		###                                         left         right                                     bottom     top
		### $where = "(`$point_long_column` BETWEEN {$b[1]} AND {$b[3]}) and (`$point_lat_column` BETWEEN {$b[0]} AND {$b[2]})";
		$conv = new Conversions;
		
#print_r($span);
		
		list($x1,$y1) = $conv->wgs84_to_internal(floatval($b[0]),floatval($b[1])); //bottom-left
		list($x2,$y2) = $conv->wgs84_to_internal(floatval($b[2]),floatval($b[3])); //top-rigth

		#$rectangle = "'POLYGON(($scanleft $scanbottom,$scanright $scanbottom,$scanright $scantop,$scanleft $scantop,$scanleft $scanbottom))'";
		$rectangle = "'POLYGON(($x1 $y1,$x2 $y1,$x2 $y2,$x1 $y2,$x1 $y1))'";
		
		
		$sql['wheres'][] = "CONTAINS( GeomFromText($rectangle),	point_xy)";


	}

} else {
	$error = "unsupported method";
}

#print "<pre>";
#print_r($error);
#print_r($sql);
#exit;


if (empty($error)) {

	$db = GeographDatabaseConnection(true);

	$sql['tables'] = array();
	$sql['tables']['gs'] = 'gridsquare';

	$sql['columns'] = "imagecount as c,x,y,grid_reference as gr";

	$query = sqlBitsToSelect($sql);

	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	
	$data = $db->getAll($query);

	foreach ($data as $idx => $row) {
		$data[$idx]['c'] = intval($row['c']);
		list($lat,$lng) = $conv->internal_to_wgs84($row['x'],$row['y']);
		$data[$idx]['lat'] = round($lat,6);
		$data[$idx]['lng'] = round($lng,6);
		unset($data[$idx]['x']);
		unset($data[$idx]['y']);
	}
	
} else {
	$data = array('error'=>$error);
}

if (!empty($_GET['callback'])) {
	$callback = preg_replace('/[^\w\.-]+/','',$_GET['callback']);
	echo "{$callback}(";
}

require_once '3rdparty/JSON.php';
$json = new Services_JSON();
print $json->encode($data);

if (!empty($_GET['callback'])) {
	echo ");";
}



