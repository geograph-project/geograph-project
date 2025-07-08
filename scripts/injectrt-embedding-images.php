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
    'index'=>'gridimage_embedding', //destination index
    'drop'=>false,
    'create'=>true,
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

if ($param['limit'] > 30000) {
	//5k images, takes about 22Mb, so currentlyly with 250Mb memory, limit is about 60k documents, BUt to be safe shouldnt go above 50k images (even less if more other indexes)
	die("limit too high, been observed to crash when using 100000 (and 20000 too)\n");
}
$param['limit'] *= 2; //2 rows per image (so input is number of images)

if ($param['offset']) {
	$param['offset'] *= 2; //2 rows per image (so input is number of images)
	$param['limit'] = $param['offset'].','.$param['limit'];
}

if (empty($CONF['manticorert_host']))
	die("No manticorert host\n");

$rt = GeographSphinxConnection('manticorert',true);

############################################

$db = GeographDatabaseConnection(false);

/*
SHOW TABLES;
CREATE TABLE test ( title TEXT, image_vector FLOAT_VECTOR knn_type='hnsw' knn_dims='4' hnsw_similarity='l2' );
SHOW STATUS LIKE 'cluster%';
ALTER CLUSTER manticore_cluster ADD test;
SELECT * FROM test;
SELECT * FROM manticore_cluster:test;
INSERT INTO manticore_cluster:test VALUES ( 1, 'yellow bag', (0.653448,0.192478,0.017971,0.339821) ), ( 2, 'white bag', (-0.148894,0.748278,0.091892,-0.095406) );
INSERT INTO manticore_cluster:test VALUES 
	( 1, 'yellow bag', (0.653448,0.192478,0.017971,0.339821) ),
	( 2, 'white bag', (-0.148894,0.748278,0.091892,-0.095406) );
*/

$sql = array();

if ($param['drop']) {
        if (!empty($param['cluster'])) {
                $sql[] = "ALTER CLUSTER {$param['cluster']} DROP {$param['index']}";
        }

	$sql[] = "DROP TABLE {$param['index']}";
}
//todo check if the table needs creating!
if ($param['create']) {
	//todo make this more generic (Eg tag embedings, will just have one vector column!
	$sql[] = "CREATE TABLE {$param['index']} (
	title TEXT,
	grid_reference TEXT,
	realname TEXT,
        user_id integer,
	title_vector FLOAT_VECTOR knn_type='hnsw' knn_dims='512' hnsw_similarity='COSINE',
	image_vector FLOAT_VECTOR knn_type='hnsw' knn_dims='512' hnsw_similarity='COSINE'
	 )";

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

//todo make this more generic
/* this would be ideal, but very ineffient...
$data = $db->getAll("SELECT gridimage_id, realname, grid_reference, title,
	GROUP_CONCAT(IF(type = 'title',embeddings,NULL)) AS title_vector,
	GROUP_CONCAT(IF(type = 'image',embeddings,NULL)) AS image_vector
	FROM gridimage_search INNER JOIN gridimage_embedding USING (gridimage_id)
	GROUP BY gridimage_id
	ORDER BY NULL
	LIMIT 10");
*/

$data = $db->getAll($qyert = "SELECT gridimage_id, user_id, realname, grid_reference, title, type, embeddings
    FROM gridimage_search INNER JOIN gridimage_embedding USING (gridimage_id)
        LIMIT {$param['limit']}");

print "$qyert;\n";

///////////////////////////////////

$rows = array();
$values = array();
$insert = false;
foreach($data as $idx => $row) {
//test!
//$row['embeddings'] = strlen($row['embeddings']);

	$id = $row['gridimage_id'];

	///////////////////////////////////
	//$row -> $rows
	if (isset($rows[$id])) {
		$rows[$id][$row['type']."_vector"] = $row['embeddings'];
	} else {
		$row[$row['type']."_vector"] = $row['embeddings'];
		$row['id'] = $id; //fixed column name in manticore!
		unset($row['type']);
		unset($row['embeddings']);
		unset($row['gridimage_id']);
		$rows[$id] = $row;
	}

	///////////////////////////////////
	//$rows -> $values
	//submit as go, stops the $rows growing big, and should be quick to loop though as it shouldnt grow
	foreach($rows as $id => $row) {
		if (!empty($row["image_vector"]) && !empty($row["title_vector"])) {
			ksort($row); //as could be in different order;
			if (empty($insert))
				$insert = "REPLACE INTO {$param['index']} (".implode(",",array_keys($row)).") VALUES\n";
			$str = "("; $sep = '';
			foreach($row as $key => $value) {
				if (is_null($value))
                                        $value = "''"; //doesnt support null!

				elseif (strpos($key,'_vector')) {
					$list = unpack('g*', $value);
					if ($param['debug'])
						$list = array_slice($list,0,4);
					$value = "(".implode(', ',$list).")";

                                } elseif ($key != 'id' && $key != 'user_id') { //Don't just use 'is_numeric', as inserting a number into string attribute, silently fails!
                                        $enc = mb_detect_encoding($value, 'UTF-8, ISO-8859-15, ASCII');
                                        if ($enc == 'ISO-8859-15' || strpos($value,'&#')!==FALSE) //dont just blindly convert, as while MOST columns in database are latin1, not quite all!
                                                $value = latin1_to_utf8($value);
                                        $value = $rt->Quote($value);
                                }
				$str .= $sep.$value;
				$sep = ',';
			}
			$values[] = $str.")";
			unset($rows[$id]);
		}
	}

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
}

if (count($values)>0)
	$sql[] = $insert.implode(',', $values);

############################################

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
