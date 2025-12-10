<?php
/**
 * $Project: GeoGraph $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2022 Barry Hunter (geo@barryhunter.co.uk)
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

//these are the arguments we expect
$param=array('verbose'=>false, 'index'=>'test-index', 'insert'=>false, 'delta'=>false, 'test'=>false, 'limit'=>20000,
 'query'=>'road', 'lat'=>false,'lng'=>false,'d'=>0.1, 'user_id'=>false, 'largest'=>false, 'region'=>false); //--filters for testing queries

$ABORT_GLOBAL_EARLY = true; //this stops connecting to memcache, so FileSystem will get a fresh STS token! (not from memcache!)

chdir(__DIR__);
require "./_scripts.inc.php";

   $s3VectorBucketName = 'geograph-vector-bucket';
   $awsRegion = "us-east-1"; //s3vector, isnt available in all regions - so we have to define the region to use!

   //this is just for the 'image' index (our main one!)
   //up here, because uysed by mulitple modes, insert, delta and test mode!

	//for CLIP
	$table_embedding = "gridimage_embedding";
	$table_progress = "embedding_progress_clip";
	$model = 'clip';

	//Perception Encoder
	if (preg_match('/-pe$/',$param['index'])) {
		$table_embedding = "gridimage_embedding_1024";
		$table_progress = "embedding_progress_pe";
		$model = 'pe';
	}

##################################
// Form commands for inserting rows into S3Vector index
// note, only prints the command, although for image specifically, it can auto-execute sharded inserts using embedding_progress_clip

// NOTE: this function is used for 'initial' commissining of indexes, using the python client to do the bulk insert,
//  ... partly used the python clien, so can use the boto API clinet, but also because it may need to call titen embedding fro bedrock.
// there is now a seperate pure PHP implemwntaion further down, intended for 'delta' use, but much more basis. Mainly just enougn to add new images to main image-clip index.

if (!empty($param['insert'])) {

    if (preg_match('/^(label|user|tags|doc|place|thread|image)-(\w+)/',$param['index'], $m)) {
	$source = $m[1];
	$model = $m[2]; //todo, could validate the model, but the vector-cmd6.py will do that anyway.
    } else {
	//basically the default for the test-index!
        $source = 'image';
	$model = 'clip';
    }

	//for now, rather than encoding the injection process in PHP, use the python script!
    $cmd = array();
    $cmd[] = "python3";
    $cmd[] = "vector-cmd6.py";
    $cmd[] = "--index " . escapeshellarg($param['index']);
    $cmd[] = "--model $model";
    $cmd[] = "insert-mysql";
    $cmd[] = "-D " . escapeshellarg($CONF['db_db']);

    //these largely mimic how original sphinx index would of been built, so replicate the queries in sphinx.conf.d/
    if ($source == 'label') {
	$cmd[] = '-t"label_embedding"';
	$cmd[] = '-s'.escapeshellarg("id, label, src, embeddings");
	$cmd[] = '-w'.escapeshellarg("length(embeddings)=2048 AND id < 100"); //just in case! (length selects CLIP!)

    } elseif ($source == 'user') {
	    $cmd[] = "-t " . escapeshellarg("user inner join user_stat using (user_id)");
	    $cmd[] = "-s " . escapeshellarg("user_id AS id, CONCAT_WS(', ',realname, NULLIF(nickname,'')) AS input_text, realname, nickname, images");
	    $cmd[] = "-w " . escapeshellarg("images > 0");

    } elseif ($source == 'tags') { //the existing index has 's' on it!
				//canonical=0 only picks offical prefixed tags
	    $cmd[] = "-t " . escapeshellarg("tag_stat inner join tag using (tag_id)");
	    $cmd[] = "-s " . escapeshellarg("tag_id as id, if(prefix in ('top','type','subject','bucket') and canonical =0,tag,tagtext) as input_text,".
					    " tagtext, count as images, users, if(prefix in ('top','type','subject','bucket') and canonical=0, prefix, if(classification like 'named%' or classification = 'related-to', 'named', 'tag')) as src");
	    $cmd[] = "-w " . escapeshellarg("tag_id = final_id and status = 1 and count>0");

    } elseif ($source == 'doc') {

	    $cmd[] = "-t " . escapeshellarg("content");
	    $cmd[] = "-s " . escapeshellarg("content_id AS id, CONCAT_WS(', ',title,NULLIF(extract,'')) AS input_text, title, extract, url, source");
	    $cmd[] = "-w " . escapeshellarg("type='document'");

    } elseif ($source == 'place') {

	    $cmd[] = "-t " . escapeshellarg("sphinx_placenames");
	    $cmd[] = "-s " . escapeshellarg("placename_id as id, CONCAT_WS(', ', Place,NULLIF(County,'Unknown'),NULLIF(Country,'Unknown'),postcode) as input_text, Place,County,Country, km_ref, postcode, images");
	    //$cmd[] = "-w " . escapeshellarg("placename_id > 100000");

    } elseif ($source == 'thread') {
	//specically threads, not posts!

	    $cmd[] = "-t " . escapeshellarg("geobb_topics");
	    $cmd[] = "-s " . escapeshellarg("topic_id AS id, topic_title AS input_text, topic_title as title, forum_id, topic_poster_name as name, date(topic_time) as day");
	    $cmd[] = "-w " . escapeshellarg("topic_poster>0");


    } elseif ($source == 'image') {
	die("For now please use delta mode, which adds more metadata fields, this old code is now redundant");

	if ($model != 'clip' && $model != 'pe') die("only clip/pe supported for now");

        $cmd[] = '-t"'.$table_embedding.' USE INDEX (PRIMARY) INNER JOIN gridimage_search USING (gridimage_id)"';
               //forcing the PRIMARY, is because the 'embedding_progress_clip' sharding is designed to work work on the primary
               //on its own the optimizer chooses gridimage index, because it ends up using ORDER BY id for its own batching (500 a a time, so it loops in id order)
               //the double level of sharding, picks the wrong index!

	//the CAST() is just to ensure it numeric - better than ROUND which stiull produces a float, "vector-cmd" can already deal with the DECIMAL from wgs84_lat etc
	//todo, this would need updating to compute myriad, largest, and country as the delta does below (as query is passed over command line to python, need to be more careful of query getting complicated
	$cmd[] = '-s'.escapeshellarg("gridimage_id AS id, user_id, grid_reference as gridref, CAST(REPLACE(imagetaken,'-','') AS UNSIGNED) AS taken, wgs84_lat as slat, wgs84_long as slng, embeddings");

	//actully for images we have a special way of doing it incrementally, because data is still being compliled!
	if ($param['insert'] > 1) {
		$db = GeographDatabaseConnection(false);
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

		if (empty($db->readonly)) //by running this, we can actully get accurate stat!
			$db->Execute("INSERT INTO $table_progress (day, count, min_id, max_id, done) SELECT     substring(updated, 1, 10) AS day,     COUNT(*) AS new_count,     MIN(seq_id) AS new_min_id,     MAX(seq_id) AS new_max_id,     NULL AS done FROM     $table_embedding WHERE     type = 'image' AND model = '$model'    AND seq_id > (SELECT COALESCE(MAX(max_id), 0) FROM $table_progress)  GROUP BY  day ON DUPLICATE KEY UPDATE     count = $table_progress.count + VALUES(count),      max_id = VALUES(max_id), `done`=NULL");


		//create table embedding_progress_clip select substring(updated,1,10) as day,count(*) as count,min(seq_id) as min_id,max(seq_id) as max_id from gridimage_embedding where type='image' group by substring(updated,1,10) order by null;
		//alter table embedding_progress_clip add done datetime default null
		//alter table embedding_progress_clip add primary key(day);
		//replace into embedding_progress_clip select substring(updated,1,10) as day,count(*) as count,min(seq_id) as min_id,max(seq_id) as max_id,null as done from gridimage_embedding where type='image' and seq_id >= (select max(min_id) from embedding_progress_clip) group by substring(updated,1,10);
		//note, this replace will add any new days, but it WILL also replace the last day, usuaully a good thing, as it may have more images
		//... its just htat it ALWAYS replaces last day (resetting done!), even if unnessary. could use max(max_id) as the crit, but then count(*)/min_id will be WRONG for the day, will be replaced with only NEW rows, not all rows. 
		//but see vision-stat.php, which has a even better INSERT ... ON DUPLICATE KEY UPDATE ..., which only counts new rows
		$data = $db->getAll("SELECT * FROM $table_progress WHERE done IS NULL AND `day` < date(now())");
		foreach ($data as $row) {
			//todo
			$min = max($row['min_id'], $row['delta_max']);
			// as that is now updated by delta! delta_max is the max id that was already inserted, techncally should be +1 (but inserting one document again is not a big deal

			$where = "type='image' AND model = '$model' AND seq_id BETWEEN $min AND {$row['max_id']} AND updated LIKE '{$row['day']}%'"; //dont know if filtering by day helps or not!
			$where = '-w'.escapeshellarg($where);
			print implode(' ',$cmd)." $where\n";

			$return_status = 0; // Initialize the variable
			if ($param['insert'] > 2) {
				putenv('PYTHONUNBUFFERED=1');

				passthru(implode(' ',$cmd)." $where", $return_status);

				if ($return_status !== 0) {
					print "apt install pip && pip install requests boto3 mysql_connector numpy\n";
				}
			}

			$sql = "UPDATE $table_progress SET done=NOW() WHERE min_id = {$row['min_id']}";
			print "# $sql;\n\n";
			if ($param['insert'] > 2 && $return_status === 0 && preg_match('/^image-',$param['index'])) { //should only really be marking as done, for the real index!
				//the connection might of closed!
				$db = GeographDatabaseConnection(false);
				$db->Execute($sql);

				//for now only do one shard at a time!
				exit;
			}
		}
		exit;
	}

	$cmd[] = '-w'.escapeshellarg("type='image' AND model = '$model' AND seq_id < 100");
    }

	print implode(' ',$cmd)."\n";
	exit;
}

##################################

$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

//$memcache = false;

 $filesystem = new FileSystem(); //sets up configuation automagically
  //the vector lip needs S3 class setup already!

include "3rdparty/s3vectors.inc.php";

##################################
// this is seperate implentaiton of insert, not as flexible as the full one, but good enough for now

if (!empty($param['delta'])) {

	list($type, $model) = explode('-', $param['index'], 2);

	if (empty($type) || empty($model)) {
	    print "Invalid index name format. Use '{type}-{model}'.\n";
	    die(1);
	}

	// Determine the SQL query based on the index type
	if ($type === 'tags') {
	    echo "Fetching and inserting 'tags' data for model '$model'...\n";
	    $sql = 'SELECT tag_id as id, tagtext, images FROM tags LIMIT 10'; //TODO!

		//in partcilar this is going to need to know have a way of keeping track of progress.

	} elseif ($type === 'test' || $type == 'image') {
	    echo "Fetching and inserting 'image' data into '{$param['index']}'...\n";

		$sql = "SELECT gridimage_id AS id, user_id,
			grid_reference AS gridref, SUBSTRING(grid_reference,1,LENGTH(grid_reference)-4) AS myriad,
			CAST(REPLACE(imagetaken,'-','') AS UNSIGNED) AS taken,
			wgs84_lat AS slat, wgs84_long AS slng, embeddings, seq_id,
			get_largest_tier(GREATEST(width,height,original_width,original_height)) AS largest,
			Region as region,
			Country as country
		FROM $table_embedding USE INDEX (PRIMARY)
		INNER JOIN gridimage_search USING (gridimage_id)
		LEFT JOIN gridimage_size s USING (gridimage_id)
		LEFT JOIN gridsquare USING (grid_reference)
		LEFT JOIN sphinx_placenames USING (placename_id)
		WHERE type = 'image' AND model = '$model'";

            if ($type == 'image') {
		if ($model != 'clip' && $model != 'pe') die("only clip/pe supported for now");

		if (empty($db->readonly)) //by running this, we can actully get accurate stat!
			$db->Execute("INSERT INTO $table_progress (day, count, min_id, max_id, done) SELECT     substring(updated, 1, 10) AS day,     COUNT(*) AS new_count,     MIN(seq_id) AS new_min_id,     MAX(seq_id) AS new_max_id,     NULL AS done FROM     $table_embedding WHERE     type = 'image' AND model = '$model'    AND seq_id > (SELECT COALESCE(MAX(max_id), 0) FROM $table_progress)  GROUP BY  day ON DUPLICATE KEY UPDATE     count = $table_progress.count + VALUES(count),      max_id = VALUES(max_id), `done`=NULL");

		//keep track with embedding_progress_clip. the 'done' column is updated by the main one, we have our own delta_max to keep track!
		// note, if update above, finds new rows, it resets done, which means we can carry on...

		$data = $db->getRow("SELECT * FROM $table_progress WHERE done IS NULL AND (delta_max is null OR delta_max < max_id) LIMIT 1");
		if (empty($data)) {
		    print "nothing to add\n";
		    die(3);
		}

		print_r($data);
		$min = max($data['min_id'], $data['delta_max']);
		$max = $data['max_id'];
		$sql .= " AND seq_id BETWEEN $min AND $max ORDER BY seq_id"; //limit 500 - note order is IMPORTANT

$sql .= " LIMIT ".$param['limit'];

	    } else {
	        $sql .= " LIMIT 10";
	    }

	} else {
	    print "Unsupported index type '$type'.\n" ;
	    die(1);
	}

print "$sql;\n\n";

	// Process data in batches
	$batchSize = 500; //s3vector limit!
	$documentsBatch = [];
	$recordsProcessed = 0;

	$rs = $db->Execute($sql);
	if ($rs) {
            $count = $rs->RecordCount();
		print "Found $count\n";

	    while (!$rs->EOF) {
		$recordsProcessed++;

		$document = [];
		foreach ($rs->fields as $columnName => $value) {
		    if ($columnName === 'tagtext') { //maybe shouldnt be hardcoded?
			$document['vector'] = getTextEmbedding($value, $model);
			$document[$columnName] = $value;
		    } elseif ($columnName === 'embeddings') {
			$document['vector'] = array_values(unpack('g*', $value));
		    } elseif ($columnName === 'seq_id') {
			$last_id = $value; //just used for tracking, not actully updated!

//todo, this should be auto-detected
		    } elseif ($columnName === 'slat' || $columnName === 'slng') {
			$document[$columnName] = floatval($value);
		    } elseif ($columnName === 'taken' || $columnName === 'user_id' || $columnName == 'largest') { //even though numberic in mysql, we still have a string via adodb
			$document[$columnName] = intval($value);


		    } else {
			$document[$columnName] = $value;
		    }
		}
		$documentsBatch[] = $document;

		// If the batch is full, insert the vectors and clear the batch
		if (count($documentsBatch) >= $batchSize) {
		    echo "Inserting batch of " . count($documentsBatch) . " vectors ($recordsProcessed total)...\n";
		    $result = putVectors($s3VectorBucketName, $param['index'], $documentsBatch, $awsRegion);
		    echo "  Batch result: " . $result['message'] . "\n";
		    $documentsBatch = []; // Reset the batch
		}

		$rs->MoveNext();
	    }
	} else {
            print "query error\n";
	    die(2);
        }

	// Insert any remaining vectors in the final batch
	if (!empty($documentsBatch)) {
	    echo "Inserting final batch of " . count($documentsBatch) . " vectors...\n";
	    $result = putVectors($s3VectorBucketName, $param['index'], $documentsBatch, $awsRegion);
	    echo "Final batch result: " . $result['message'] . "\n\n";
	}

	if (!empty($last_id) && !empty($data)) {
		$updates = array();
		$updates[] = "delta_max = $last_id";
//todo, if ($last_id == $data['max_id']) SET done=now();
// in theory the update should still 'reset' done, if more rows are added!
		if ($last_id == $data['max_id'])
			$updates[] = "done=now()";

	    $sql = "UPDATE $table_progress SET ".implode(', ',$updates)." WHERE day = '{$data['day']}'";

	    print "$sql\n";
	    $db->Execute($sql);
	}
	exit;
}

##################################
// Function to test index, although hardcoded for testing an 'image' index!

if ($param['test']) {
	$topK = 30; // might as well!

	$rows = $db->getAll("SELECT * FROM $table_progress INNER JOIN $table_embedding ON (seq_id = min_id) ORDER BY day DESC limit 5");
	foreach ($rows as $idx => $row) {
		$queryEmbedding = array_values(unpack('g*', $row['embeddings']));
		$needle = $row['gridimage_id'];

		print "$idx. looking for $needle for {$row['day']}\n";

		    $queryPayload = [
		        'vectorBucketName' => $s3VectorBucketName,
		        'indexName' => $param['index'],
		        'queryVector' => ['float32' => $queryEmbedding],
		        'topK' => $topK,
		        'returnDistance' => true,
		        'returnMetadata' => false,
		    ];

		    $results = queryS3Vectors(
		        $queryPayload,
		        $awsRegion,
		        $param['verbose']
		    );

		$found = false;
	        foreach ($results['vectors'] as $i => $vector) {
			if ($vector['key'] == $needle) {
				print "  Found $needle as $i and {$vector['distance']}\n";
				$found = true;
			}
	        }
		if (!$found)
			print "  Not Found\n";
	}
	exit;
}

##################################


   if (!empty($param['query'])) {
	//this table sometimes have a enginered prompt, which we want to use
	$row = $db->getRow("SELECT * FROM label_embedding WHERE label = ".$db->Quote($param['query'])." AND model='$model'");
        if (!empty($row))
		$queryEmbedding = array_values(unpack('g*', $row['embeddings']));
	else {
		//otherwise lookup via our API!

		require_once('geograph/vectors.inc.php');
		$queryEmbedding = getTextEmbedding($param['query'], $model);
	}
   } else {
       $queryEmbedding = example_vector();
   }

    $topK = 5;
    $queryFilter = null; // Example: '{"genre": "scifi"}'

    $parts = array(); //will be specifically a list if ANDed criteria
    if ($param['lat']) {
	/*
	$queryFilter['$and'] = [
		array('slat' => array('$gte' => $param['lat']- $param['d'], '$lte' => $param['lat']+ $param['d'])),
		array('slng' => array('$gte' => $param['lng']- $param['d'], '$lte' => $param['lng']+ $param['d']))
		];

	*/
	$parts[] = array('slat' => array('$gte' => $param['lat']- $param['d'], '$lte' => $param['lat']+ $param['d']));
	$parts[] = array('slng' => array('$gte' => $param['lng']- $param['d'], '$lte' => $param['lng']+ $param['d']));
    }
    if ($param['user_id'])
	$parts[] = array('user_id' => array('$eq' => intval($param['user_id'])));

    if ($param['region']) {
        if (preg_match('/-([\w ]+)/',$param['region'],$m)) {
		$parts[] = array('region' => array('$ne' => $m[1], '$exists'=>true));
	} else {
		$parts[] = array('region' => array('$eq' => $param['region']));
	}
    }

    if ($param['largest']) {
        if (preg_match('/(\d+)\+/',$param['largest'],$m)) {
		$parts[] = array('largest' => array('$gte' => intval($m[1])));
	} else {
		$parts[] = array('largest' => array('$eq' => intval($param['largest'])));
	}
    }

    if (!empty($parts)) {
        if (count($parts) > 1) {
	//multiple actully need nesting.
	   $queryFilter = array();
	   $queryFilter['$and'] =$parts;
	} else {
        //todo  if 1 then use directly?
           $queryFilter = $parts[0];
	}
    }
    print json_encode($queryFilter)."\n";

##################################

    $queryPayload = [
        'vectorBucketName' => $s3VectorBucketName,
        'indexName' => $param['index'],
        'queryVector' => ['float32' => $queryEmbedding],
        'topK' => $topK,
        'returnDistance' => true,
        'returnMetadata' => true,
    ];

    if ($queryFilter && is_string($queryFilter)) {
        $decodedFilter = json_decode($queryFilter, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $queryPayload['filter'] = $decodedFilter;
        } else {
            echo "Warning: Invalid JSON for queryFilter. Filter will be ignored.\n";
        }
    } elseif ($queryFilter && is_array($queryFilter)) {
	$queryPayload['filter'] = $queryFilter;
    }

    $results = queryS3Vectors(
        $queryPayload,
        $awsRegion,
        $param['verbose']
    );

    echo "\n--- Final Results Summary ---\n";
    echo "HTTP Status Code: " . $results['http_code'] . "\n";

    if (!empty($results['vectors'])) {
        echo "Parsed Results:\n";
        foreach ($results['vectors'] as $i => $vector) {
            echo "Result " . ($i + 1) . ":\n";
            echo "  Key: " . ($vector['key'] ?? 'N/A') . "\n";
            echo "  Distance: " . ($vector['distance'] ?? 'N/A') . "\n";
            echo "  Metadata: " . json_encode($vector['metadata'] ?? []) . "\n";
            echo "---\n";
        }
    } else {
        echo "No vectors found for the query or an error occurred.\n";
    }

##################################


function example_vector() {
// --- Query Parameters ---
// Replace with your actual CLIP ViT-B/32 embedding (example is a placeholder 512-dim array)
// In a real application, this would come from your front-end or another service.
return  [
    0.01, 0.02, 0.03, 0.04, 0.05, 0.06, 0.07, 0.08, 0.09, 0.10, /* ... 502 more values ... */
    // Placeholder: You need 512 float values here for ViT-B/32
    // For a real test, you can generate a random 512-dim vector for testing purposes:
    // array_map(function() { return (float)rand() / (float)getrandmax() * 2 - 1; }, range(0, 511))
    // Or, better, use a known embedding from your CLIP model.
    // Example for a short vector for demonstration (replace with 512 actual values):
    -0.001, 0.002, -0.003, 0.004, -0.005, 0.006, -0.007, 0.008, -0.009, 0.010,
    -0.011, 0.012, -0.013, 0.014, -0.015, 0.016, -0.017, 0.018, -0.019, 0.020,
    -0.021, 0.022, -0.023, 0.024, -0.025, 0.026, -0.027, 0.028, -0.029, 0.030,
    -0.031, 0.032, -0.033, 0.034, -0.035, 0.036, -0.037, 0.038, -0.039, 0.040,
    -0.041, 0.042, -0.043, 0.044, -0.045, 0.046, -0.047, 0.048, -0.049, 0.050,
    -0.051, 0.052, -0.053, 0.054, -0.055, 0.056, -0.057, 0.058, -0.059, 0.060,
    -0.061, 0.062, -0.063, 0.064, -0.065, 0.066, -0.067, 0.068, -0.069, 0.070,
    -0.071, 0.072, -0.073, 0.074, -0.075, 0.076, -0.077, 0.078, -0.079, 0.080,
    -0.081, 0.082, -0.083, 0.084, -0.085, 0.086, -0.087, 0.088, -0.089, 0.090,
    -0.091, 0.092, -0.093, 0.094, -0.095, 0.096, -0.097, 0.098, -0.099, 0.100,
    -0.101, 0.102, -0.103, 0.104, -0.105, 0.106, -0.107, 0.108, -0.109, 0.110,
    -0.111, 0.112, -0.113, 0.114, -0.115, 0.116, -0.117, 0.118, -0.119, 0.120,
    -0.121, 0.122, -0.123, 0.124, -0.125, 0.126, -0.127, 0.128, -0.129, 0.130,
    -0.131, 0.132, -0.133, 0.134, -0.135, 0.136, -0.137, 0.138, -0.139, 0.140,
    -0.141, 0.142, -0.143, 0.144, -0.145, 0.146, -0.147, 0.148, -0.149, 0.150,
    -0.151, 0.152, -0.153, 0.154, -0.155, 0.156, -0.157, 0.158, -0.159, 0.160,
    -0.161, 0.162, -0.163, 0.164, -0.165, 0.166, -0.167, 0.168, -0.169, 0.170,
    -0.171, 0.172, -0.173, 0.174, -0.175, 0.176, -0.177, 0.178, -0.179, 0.180,
    -0.181, 0.182, -0.183, 0.184, -0.185, 0.186, -0.187, 0.188, -0.189, 0.190,
    -0.191, 0.192, -0.193, 0.194, -0.195, 0.196, -0.197, 0.198, -0.199, 0.200,
    -0.201, 0.202, -0.203, 0.204, -0.205, 0.206, -0.207, 0.208, -0.209, 0.210,
    -0.211, 0.212, -0.213, 0.214, -0.215, 0.216, -0.217, 0.218, -0.219, 0.220,
    -0.221, 0.222, -0.223, 0.224, -0.225, 0.226, -0.227, 0.228, -0.229, 0.230,
    -0.231, 0.232, -0.233, 0.234, -0.235, 0.236, -0.237, 0.238, -0.239, 0.240,
    -0.241, 0.242, -0.243, 0.244, -0.245, 0.246, -0.247, 0.248, -0.249, 0.250,
    -0.251, 0.252, -0.253, 0.254, -0.255, 0.256, -0.257, 0.258, -0.259, 0.260,
    -0.261, 0.262, -0.263, 0.264, -0.265, 0.266, -0.267, 0.268, -0.269, 0.270,
    -0.271, 0.272, -0.273, 0.274, -0.275, 0.276, -0.277, 0.278, -0.279, 0.280,
    -0.281, 0.282, -0.283, 0.284, -0.285, 0.286, -0.287, 0.288, -0.289, 0.290,
    -0.291, 0.292, -0.293, 0.294, -0.295, 0.296, -0.297, 0.298, -0.299, 0.300,
    -0.301, 0.302, -0.303, 0.304, -0.305, 0.306, -0.307, 0.308, -0.309, 0.310,
    -0.311, 0.312, -0.313, 0.314, -0.315, 0.316, -0.317, 0.318, -0.319, 0.320,
    -0.321, 0.322, -0.323, 0.324, -0.325, 0.326, -0.327, 0.328, -0.329, 0.330,
    -0.331, 0.332, -0.333, 0.334, -0.335, 0.336, -0.337, 0.338, -0.339, 0.340,
    -0.341, 0.342, -0.343, 0.344, -0.345, 0.346, -0.347, 0.348, -0.349, 0.350,
    -0.351, 0.352, -0.353, 0.354, -0.355, 0.356, -0.357, 0.358, -0.359, 0.360,
    -0.361, 0.362, -0.363, 0.364, -0.365, 0.366, -0.367, 0.368, -0.369, 0.370,
    -0.371, 0.372, -0.373, 0.374, -0.375, 0.376, -0.377, 0.378, -0.379, 0.380,
    -0.381, 0.382, -0.383, 0.384, -0.385, 0.386, -0.387, 0.388, -0.389, 0.390,
    -0.391, 0.392, -0.393, 0.394, -0.395, 0.396, -0.397, 0.398, -0.399, 0.400,
    -0.401, 0.402, -0.403, 0.404, -0.405, 0.406, -0.407, 0.408, -0.409, 0.410,
    -0.411, 0.412, -0.413, 0.414, -0.415, 0.416, -0.417, 0.418, -0.419, 0.420,
    -0.421, 0.422, -0.423, 0.424, -0.425, 0.426, -0.427, 0.428, -0.429, 0.430,
    -0.431, 0.432, -0.433, 0.434, -0.435, 0.436, -0.437, 0.438, -0.439, 0.440,
    -0.441, 0.442, -0.443, 0.444, -0.445, 0.446, -0.447, 0.448, -0.449, 0.450,
    -0.451, 0.452, -0.453, 0.454, -0.455, 0.456, -0.457, 0.458, -0.459, 0.460,
    -0.461, 0.462, -0.463, 0.464, -0.465, 0.466, -0.467, 0.468, -0.469, 0.470,
    -0.471, 0.472, -0.473, 0.474, -0.475, 0.476, -0.477, 0.478, -0.479, 0.480,
    -0.481, 0.482, -0.483, 0.484, -0.485, 0.486, -0.487, 0.488, -0.489, 0.490,
    -0.491, 0.492, -0.493, 0.494, -0.495, 0.496, -0.497, 0.498, -0.499, 0.500,
    -0.233, 0.334
];

}
