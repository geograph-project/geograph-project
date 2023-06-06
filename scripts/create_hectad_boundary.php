<?php
/**
 * $Project: GeoGraph $
 * $Id: recreate_maps.php 2996 2007-01-20 21:39:07Z barry $
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

$param = array('debug'=>false,'limit'=>10);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

############################################

if ($param['debug'])
	print "Starting. ".date('r')."\n";


$rows = $db->getAll("SELECT hectad_stat.* FROM hectad_stat LEFT JOIN hectad_boundary USING (hectad) WHERE hectad_boundary.hectad IS NULL LIMIT {$param['limit']}");

require_once('geograph/conversions.class.php');
$conv = new Conversions;

foreach ($rows as $row) {

	list($e,$n) = $conv->internal_to_national($row['x'],$row['y'],$row['reference_index']);

	$e1 = intval($e/10000)*10000;
	$n1 = intval($n/10000)*10000;
	$e2 = $e1+9999.9999;
	$n2 = $n1+9999.9999;

	//winding order seems to be counter-clockwise from bottom left

	$wkt = "POLYGON(($e1 $n1,$e2 $n1,$e2 $n2,$e1 $n2,$e1 $n1))";

	$bits = array();
	$bits[] = "hectad = '{$row['hectad']}'";
	$bits[] = "reference_index = {$row['reference_index']}";

	$bits[] = "boundary_en = ST_GEOMFROMTEXT('{$wkt}')";

		$sql = "INSERT INTO hectad_boundary SET ".implode(',',$bits);
		if ($param['debug'])
			print "Inserting {$row['hectad']}...";
		$db->Execute($sql);

}
