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

$param=array(
    'host'=>false, //override mysql host
    'cluster'=>'manticore_cluster', //new name on manticore 10!!?!?
    'table'=>'gridimage_embedding', //source mysql table
    'index'=>'', //destination index (defauls to same as table)
	'location'=>false,
    'drop'=>false,
    'create'=>false,
    'debug'=>false,
    'execute'=>false,
	'log'=>false,

    'limit'=>10,
    'offset'=>0,
);

$ABORT_GLOBAL_EARLY=1; //avoids global.inc.php auto connecteding to redis to with "$memcache" variable

chdir(__DIR__);
require "./_scripts.inc.php";

$CONF['manticorert_host'] = "manticorert-worker-svc.dev.svc.cluster.local"; //test instance!

if ($param['offset']) {
	$param['limit'] = $param['offset'].','.$param['limit'];
}

if (array_sum(explode(',', $param['limit'])) > 150000) {
	die("limit too high\n");
}

if (empty($CONF['manticorert_host']))
	die("No manticorert host\n");

$rt = GeographSphinxConnection('manticorert',true);

############################################
//define the table structure

$numericKeys = ['id'];
$cols = [];

if ($param['table'] == 'gridimage_embedding') {
    $fieldDefinitions = [
        'id' => ['select' => "gridimage_id as id", 'create' => null], // 'id' will not appear in $cols
        'title' => ['select' => "title", 'create' => "title TEXT"],
        'grid_reference' => ['select' => "grid_reference", 'create' => "grid_reference STRING indexed attribute"],
        'realname' => ['select' => "realname", 'create' => "realname TEXT"],
        'user_id' => ['select' => "user_id", 'create' => "user_id int"],
        'user' => ['select' => "CONCAT('user',user_id) as user", 'create' => "user TEXT indexed"],
        'image_vector' => ['select' => "embeddings AS image_vector", 'create' => "image_vector FLOAT_VECTOR knn_type='hnsw' knn_dims='512' hnsw_similarity='COSINE'"],
        'hash' => ['select' => null, 'create' => "`hash` STRING attribute"],
    ];

    if ($param['location']) {
        $fieldDefinitions['wgs84_lat'] = ['select' => "RADIANS(wgs84_lat) AS wgs84_lat", 'create' => 'wgs84_lat FLOAT'];
        $fieldDefinitions['wgs84_long'] = ['select' => "RADIANS(wgs84_long) AS wgs84_long", 'create' => 'wgs84_long FLOAT'];
        $fieldDefinitions['plus_vector'] = ['select' => null, 'create' => "plus_vector FLOAT_VECTOR knn_type='hnsw' knn_dims='514' hnsw_similarity='L2'"];
		//for now L2 is intentional, as our fake vector is no longer normalized, it doesnt work so well in COSINE
		//but COSINE does seem to work better for the real CLIP embeddedings!
    }

    $sources = [];

    foreach ($fieldDefinitions as $fieldName => $def) {
        if (isset($def['select']) && $def['select'] !== null)
            $sources[] = $def['select'];

        if (isset($def['create']) && $def['create'] !== null) {
            $cols[$fieldName] = $def['create']; // Assign with $fieldName as key
            if (preg_match('/\b(integer|float)\b/i', $def['create'])) {
                $numericKeys[] = $fieldName;
            }
        }
    }

	$query = "SELECT ".implode(', ',$sources)."
	    FROM gridimage_search INNER JOIN gridimage_embedding USING (gridimage_id)
	    WHERE type = 'image' AND model = 'clip'";

} else {
	$cols['label'] = "label TEXT";
	$cols['label_vector'] = "label_vector FLOAT_VECTOR knn_type='hnsw' knn_dims='512' hnsw_similarity='COSINE'";

	$query = "SELECT id, label, embeddings AS label_vector FROM {$param['table']} WHERE model = 'clip'";
}

if (empty($param['index']))
	$param['index'] = $param['table'];

############################################

$db = GeographDatabaseConnection(false);
$sql = array();

$exists = $rt->getRow("show tables like ".$rt->Quote($param['index']));

if ($param['drop']) {
        if (!empty($param['cluster'])) {
		$row = $rt->getRow("show status like 'cluster_{$param['cluster']}_indexes'");
		if (!empty($row) && preg_match("/\b{$param['index']}\b/",$row['Value']))
	                $sql[] = "ALTER CLUSTER {$param['cluster']} DROP {$param['index']}";
        }

	if (!empty($exists))
		$sql[] = "DROP TABLE {$param['index']}";
}

if (empty($exists) || $param['create'] || $param['drop']) {
	$sql[] = "CREATE TABLE {$param['index']} (".implode(', ',$cols).")";

	if (!empty($param['cluster'])) {
		$sql[] = "ALTER CLUSTER {$param['cluster']} ADD {$param['index']}";
	}
}

############################################

//the creates above, DONT use cluster name on index, but inserts below do!
if (!empty($param['cluster'])) {
	$param['index'] = "{$param['cluster']}:{$param['index']}";
}

############################################
//execute commands so far

if (!empty($param['log'])) {
	$h = fopen("injectrt-".date('Y-m-d').'.log', 'a');

	$host = $rt->getOne("SELECT hostname FROM test");
	fwrite($h,"-- writing to host $host\n");
}

if ($param['execute'] > 1 && !empty($sql)) { //need to execute these FIRST!
	foreach($sql as $idx => $query) {
		if (!empty($param['log'])) {
			fwrite($h,'-- '.date('r')."\n");
			fwrite($h,"$query;\n");
		}
		$rt->Execute($query);
                if (!empty($param['log'])) {
			fwrite($h,'-- '.$rt->Affected_Rows()."\n");
		}
		print "$idx: ".$rt->Affected_Rows()."\n";
	}
	$sql = array();
}

############################################
// main loop

print "$query LIMIT {$param['limit']};\n";
$data = $db->getAll("$query LIMIT {$param['limit']}");

///////////////////////////////////

function mapRange(float $value, float $in_min, float $in_max, float $out_min, float $out_max): float {
    return ($value - $in_min) * ($out_max - $out_min) / ($in_max - $in_min) + $out_min;
}
if (!empty($cols['plus_vector'])) {
	print "-- Fetching Boundary...\n";
	//sample8 is quickest way to get this!
	$sph = GeographSphinxConnection('sphinxql',true);
	$range = $sph->getRow("select min(wgs84_lat) as mnlt,min(wgs84_long) as mnln,max(wgs84_lat) as mxlt,max(wgs84_long) as mxln from sample8");
	var_export($range);
}

$values = array();
$insert = false;
print "-- Inserting data...\n";
foreach($data as $idx => $row) {
	if (!empty($cols['hash'])) {
		$row['hash'] = substr(md5($row['id'].$row['user_id'].$CONF['photo_hashing_secret']), 0, 8);
	}

	if (!empty($cols['plus_vector'])) {
		//the plus vector, adds location the the image vector
		//$row['plus_vector'] = $row['image_vector'] . pack('g*', (float)$row['wgs84_lat'], (float)$row['wgs84_long']);
if ($param['debug'])
	$row['image_vector'] = substr($row['image_vector'],0,4);

		$row['plus_vector'] = $row['image_vector'] . pack('g*',
			mapRange($row['wgs84_lat'],  $range['mnlt'], $range['mxlt'], -1, 1),
			mapRange($row['wgs84_long'], $range['mnln'], $range['mxln'], -1, 1));
	}

	///////////////////////////////////
	//$row -> $values

	if (empty($insert))
		$insert = "REPLACE INTO {$param['index']} (".implode(",",array_keys($row)).") VALUES\n";
	$str = $sep = '';
	foreach($row as $key => $value) {
		if (is_null($value))
                        $value = "''"; //doesnt support null!

		elseif (strpos($key,'_vector')) {
			$list = unpack('g*', $value);
			if ($param['debug'])
				$list = array_slice($list,0,4);
			$value = "(".implode(', ',$list).")";

                } elseif (!in_array($key, $numericKeys)) { //Don't just use 'is_numeric', as inserting a number into string attribute, silently fails!
                        $enc = mb_detect_encoding($value, 'UTF-8, ISO-8859-15, ASCII');
                        if ($enc == 'ISO-8859-15' || strpos($value,'&#')!==FALSE) //dont just blindly convert, as while MOST columns in database are latin1, not quite all!
                                $value = latin1_to_utf8($value);
                        $value = $rt->Quote($value);
                }
		$str .= $sep.$value;
		$sep = ',';
	}
	$values[] = "($str)";

	///////////////////////////////////
	//$values -> $sql
	//and submit final rows in small batches (worth doing on a RT index)

	if (count($values)>=100) {
		if ($param['execute'] > 1) {
			$query = $insert.implode(',', $values);
			if (!empty($param['log'])) {
				fwrite($h,'-- '.date('r')."\n");
				fwrite($h,"$query;\n");
			}
			$rt->Execute($query);
	                if (!empty($param['log'])) {
				fwrite($h,'-- '.$rt->Affected_Rows()."\n");
			}
			print "$idx: ".$rt->Affected_Rows()."\n";
			sleep(1);
		} else {
			$sql[] = $insert.implode(',', $values);
		}
		$values = array();
	}

	///////////////////////////////////
}

if (count($values)>0)
	$sql[] = $insert.implode(',', $values);

############################################
// submit any commands left

if ($param['debug'])
	print_r($sql);

if ($param['execute'] && !empty($sql))
	foreach($sql as $idx => $query) {
		if (!empty($param['log'])) {
			fwrite($h,'-- '.date('r')."\n");
			fwrite($h,"$query;\n");
		}
		$rt->Execute($query);
                if (!empty($param['log'])) {
			fwrite($h,'-- '.$rt->Affected_Rows()."\n");
		}
		print "$idx: ".$rt->Affected_Rows()."\n";
	}

############################################
