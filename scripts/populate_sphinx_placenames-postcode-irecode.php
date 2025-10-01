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


############################################

//these are the arguments we expect
$param=array('d'=>4000, 'limit'=>10, 'ri'=>2, 'file' => '../RoutingKeys_WGS84_region.kml');

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$areas = array();
read_kml($param['file'],$areas);

print "Loaded ".count($areas)." areas\n";

############################################

require_once('geograph/gridimage.class.php');
require_once('geograph/conversions.class.php');
require_once('geograph/conversionslatlong.class.php');

$conv = new ConversionsLatLong;

$db = GeographDatabaseConnection(false);

$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

		//we need to join on os_gaz to get east/north
	$sql = "select placename_id,Place,e,n,p.km_ref
	from sphinx_placenames p inner join ie_open_data l on (placename_id = id+3000000)
	where p.reference_index = {$param['ri']} and l.country = 'Ireland' and postcode is null
	limit {$param['limit']}";
	$recordSet = $db->Execute($sql);

	while (!$recordSet->EOF)
	{
		$r =& $recordSet->fields;

		$latlong = $conv->irish_to_wgs84($r['e'],$r['n'], true);
		$p = array($latlong[1],$latlong[0]); //$p uses x,y - which happens to be the same as KML!

		##########################################
print "Checking ".implode(', ',$r)."\n";
print_r($p);

		//two-pass operation, check bbox first (quick), then only if multiple bother with full point in  test!
		$candiates = array();
		foreach ($areas as $idx => $area) {
			if (intersects($p,$area['bbox'])) {
				$candiates[] = $idx;
				//print "{$areas[$idx]['name']}. => ";
				//print_r($area['bbox']);
			}
		}
print "Cand =>";
print_r($candiates);
		if (empty($candiates)) {
			$row = array('code' => ''); //store a blank string :) - not NULL!
		} elseif (count($candiates) == 1) {
			$idx = array_pop($candiates);
			$row = array('code' => $areas[$idx]['name']);
		} else {
			//need to do full test
			foreach ($candiates as $key => $idx) {
				print "Checking $key. $idx. ".count($areas[$idx]['p'])." {$areas[$idx]['name']}\n";
				//pointInside($p,&$points)
				if (!pointInside($p,$areas[$idx]['p']))
					unset($candiates[$key]);
			}
			if (count($candiates) == 1) {
				$idx = array_pop($candiates);
				$row = array('code' => $areas[$idx]['name']);
			} else {
				$row = array('code' => ''); //store a blank string :) - not NULL!

				//print_r($r);
				//print_r($candiates);
				//die("huh?");
			}
		}

		##########################################

		$updates = array();
		if (!empty($row)) {
			//print_r($row);

			$updates['postcode'] = preg_replace('/ .*/','',$row['code']); //drop the after space

			print "Found {$row['code']} for {$r['Place']}\n";
			//print_r($updates);
			//print "$sql\n\n";
		} else {
			$updates['postcode'] = '';
			print "Nothing found for ".implode(', ',$r)."\n";
		}

		$where = "placename_id = ".$r['placename_id'];
		$db->Execute($sql = 'UPDATE sphinx_placenames SET `'.implode('` = ?,`',array_keys($updates))."` = ? WHERE $where", array_values($updates));

		$recordSet->MoveNext();
	}

	$recordSet->Close();

####################################

function read_kml($file,&$areas) {
	//NOTE, this is a VERY rudimentry KML parser, only works on 'clean' KML 
	$h = gzopen($file,'r');
	$idx = 0;
	while($h && !feof($h)) {
		$line = fgets($h);
		//name="RoutingKey">K78</SimpleData>
		if (preg_match('/name="RoutingKey">(.*)<\/SimpleData>/',$line,$m)) {
			$name = trim($m[1]);

		} elseif  (strpos($line,'<innerBoundaryIs>') !== FALSE) {
			$type = 'inner';
		} elseif  (strpos($line,'<outerBoundaryIs>') !== FALSE) {
			$type = 'outer';

		//-6.251368000000017,53.55172799999999,0 -6.248107000000005,53.55341800000001,0 -6.24664100000
		} elseif (strpos($line,'<coordinates>') !== FALSE) {
			if ($type == 'inner') //we only interested in outer boundaries
				continue;

			$line = trim(fgets($h,1000000000)); //immidately just grab the next line

			$bbox = array(180,90,-180,-90); //left,bottom right,top
			$areas[$idx] = array('name'=>$name, 'bbox'=>null, 'p'=>array());

			$bits = explode(' ',$line);
			foreach ($bits as $bit) {
				list($x,$y,$d) = explode(',',$bit);
				if ($y) {
					$areas[$idx]['p'][] = array($x,$y);
					if ($x < $bbox[0]) $bbox[0] = $x;
					if ($y < $bbox[1]) $bbox[1] = $y;
					if ($x > $bbox[2]) $bbox[2] = $x;
					if ($y > $bbox[3]) $bbox[3] = $y;
				}
			}
			$areas[$idx]['bbox'] = $bbox;
			$idx++;
		}
	}
	if (empty($areas)) {
		die("No areas found!\n");
	}
}


// see solution 1 at http://astronomy.swin.edu.au/~pbourke/geometry/insidepoly/
function pointInside($p,&$points) {
        $c = 0;
        $p1 = $points[0];
        $n = count($points);
        for ($i=1; $i<=$n; $i++) {
                $p2 = $points[$i % $n];
                if ($p[1] > min($p1[1], $p2[1])
                                && $p[1] <= max($p1[1], $p2[1])
                                && $p[0] <= max($p1[0], $p2[0])
                                && $p1[1] != $p2[1]) {
                        $xinters = ($p[1] - $p1[1]) * ($p2[0] - $p1[0]) / ($p2[1] - $p1[1]) + $p1[0];
                        if ($p1[0] == $p2[0] || $p[0] <= $xinters)
                                $c++;
                }
                $p1 = $p2;
        }
        // if the number of edges we passed through is even, then it^rs not in the poly.
        return $c%2!=0;
}

function intersects(&$p,&$bbox) {
	return $p[0] >= $bbox[0] && $p[1] >= $bbox[0] && $p[0] <= $bbox[2] && $p[1] <= $bbox[3];
}
