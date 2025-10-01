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

//This file 'duplicates' the function of $gazetter->findListByNational(..) but is a simpler streamlined implemantion, using a direct access
	//... the 'gaz' sphinx indexes dont include coordinates, so can be ysed

// ... later we could convert to a sphinx index, specifically if add 'features' to the list

require_once('geograph/global.inc.php');

//header('Access-Control-Allow-Origin: *');
customExpiresHeader(3600*24);

 $conv = new Conversions;

$sql = array();
$sql['wheres'] = array();
$sphinxq = empty($_GET['q'])?'':$_GET['q'];

$radius = 4000;
$e = intval($_GET['e']);
//$sql['wheres'][] = sprintf("e between %d and %d", $e-$radius, $e+$radius);

$n = intval($_GET['n']);
//$sql['wheres'][] = sprintf("n between %d and %d", $n-$radius, $n+$radius);


                $left=$e-$radius;
                $right=$e+$radius;
                $top=$n-$radius;
                $bottom=$n+$radius;

                $rectangle = "'POLYGON(($left $bottom,$right $bottom,$right $top,$left $top,$left $bottom))'";

$sql['wheres'][] = "CONTAINS( GeomFromText($rectangle), point_en)";

if (empty($error)) {

	$db = GeographDatabaseConnection(true);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	$sql['tables'] = array();
	$sql['tables'][] = 'ie_open_data';
	$sql['columns'] = 'name,irish,county,country,e,n,town_class';
	$sql['order'] = 'name';
	$sql['limit'] = 100;

	$query = sqlBitsToSelect($sql);

	$data['rows'] = $db->getAll($query);

	if (!empty($data['rows'])) {
		foreach($data['rows'] as &$row) {
			$row['name'] = latin1_to_utf8($row['name']);
			if (!empty($row['irish'])) {
				$enc = mb_detect_encoding($row['irish'], 'UTF-8, ISO-8859-15, ASCII');
				if ($enc == 'ISO-8859-15')
					$row['irish'] = latin1_to_utf8($row['irish']);
			}
			$row['e'] = intval($row['e']);
			$row['n'] = intval($row['n']);
		}
		unset($row);
	}
} else {
	$data = array('error'=>$error);
}

outputJSON($data);
