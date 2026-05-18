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
$param=array();


chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

##################################

chdir("/mnt/efs/models/");

require_once 'GeographModelBase.php';

//other datasets have precomputed the distance!
$distance = "IF(natnorthings>0 AND viewpoint_eastings>0,
pow(2,floor(log2(SQRT(
pow(cast(nateastings as signed)-cast(viewpoint_eastings as signed),2)
+pow(cast(natnorthings as signed)-cast(viewpoint_northings as signed),2)
))))
,'Unknown') AS distance";


$sql = "SELECT gridimage_id as image_id, $distance,
 	GROUP_CONCAT(if(e.type = 'image',e.embeddings,null)) AS `clip-image`,
        GROUP_CONCAT(if(e.type = 'title',e.embeddings,null)) AS `clip-title`
	FROM gridimage_embedding e
	INNER JOIN gridimage USING (gridimage_id)
	WHERE model = 'clip'
	GROUP BY gridimage_id
	ORDER BY NULL
	LIMIT 10";

$data = $db->getAll($sql);
foreach ($data as &$row) {
    foreach ($row as $key => &$value) {
        if (strpos($key, '-')) {
            $value = array_values(unpack('g*', $value));
        }
    }
}
unset($row); // Break reference safety

// 2. Run the Bulk Inference
$bulkResults = run_bulk_inference($data);

// 3. Output or process results
//print_r($bulkResults);
foreach ($bulkResults as $rows) {
	foreach($rows as $row)
		print implode('; ',$row)."\n";
}


////////////////////////////////////
exit;

foreach ($data as $row) {
	foreach ($row as $key => &$value)
		if (strpos($key,'-'))
	            $value = array_values(unpack('g*', $value));

	print_r($row);
	$r = run_inference($row);
	print_r($r);
	exit;
}


//note this function in intended to just run one inference!
function run_inference($inputs) {

    $allResults = [];
    $directories = array_filter(glob('*'), 'is_dir');

$stats=[];
    foreach ($directories as $dir) {
        $modelPhp = $dir . '/model.php';
        $weightsJson = $dir . '/weights.json';

        if (file_exists($modelPhp) && file_exists($weightsJson.".bin")) {

            require_once $modelPhp;

            // Determine class name from directory name
            // e.g. gallery -> GeographGalleryModel
            // Special case for model_types -> GeographTypesModel
            $className = 'Geograph' . ucfirst(str_replace('model_', '', $dir)) . 'Model';

            if (class_exists($className)) {
                try {
$start = microtime(true);
                    $model = new $className($weightsJson);
$loaded = microtime(true);
                    $results = $model->predict_api($inputs);
$end = microtime(true);
$stats[$className."Load"] = $loaded-$start;
$stats[$className."Infer"] = $end-$loaded;

                    $allResults = array_merge($allResults, $results);
                } catch (Exception $e) {
                    // Skip if error as requested
                    error_log("Error running model in $dir: " . $e->getMessage());
                }
            }
        }
    }
print_r($stats);
    return $allResults;
}

/**
 * Runs inference on an array of multiple image data inputs.
 * Loads the models and weights once, then loops through the data.
 *
 * @param array $allInputs Array of rows containing image data/embeddings.
 * @return array Combined results grouped by input/row index.
 */
function run_bulk_inference(array $allInputs) {
    $bulkResults = [];
    $initializedModels = [];
    $directories = array_filter(glob('*'), 'is_dir');
    $stats = ['load_times' => [], 'infer_times' => []];

    // --- PHASE 1: Bootstrap & Load Models (Once) ---
    foreach ($directories as $dir) {
        $modelPhp = $dir . '/model.php';
        $weightsJson = $dir . '/weights.json';

        if (file_exists($modelPhp) && file_exists($weightsJson . ".bin")) {
            require_once $modelPhp;

            $className = 'Geograph' . ucfirst(str_replace('model_', '', $dir)) . 'Model';

            if (class_exists($className)) {
                try {
                    $start = microtime(true);
                    
                    // Instantiate and cache the model instance
                    $initializedModels[$className] = new $className($weightsJson);
                    
                    $stats['load_times'][$className] = microtime(true) - $start;
                } catch (Exception $e) {
                    error_log("Error loading model in $dir: " . $e->getMessage());
                }
            }
        }
    }

    // --- PHASE 2: Loop and Infer ---
    foreach ($allInputs as $index => $inputs) {
        $bulkResults[$index] = [];
        
        foreach ($initializedModels as $className => $model) {
            try {
                $start = microtime(true);
                
                $results = $model->predict_api($inputs);
                
                // Track stats dynamically per row/model if needed
                $stats['infer_times'][$index][$className] = microtime(true) - $start;

                $bulkResults[$index] = array_merge($bulkResults[$index], $results);
            } catch (Exception $e) {
                error_log("Error running model $className on row $index: " . $e->getMessage());
            }
        }
    }

    // Optional: Log or view performance stats
    // print_r($stats);

    return $bulkResults;
}
