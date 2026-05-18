<?php

require_once('geograph/global.inc.php');
require_once('geograph/uploadmanager.class.php');

session_cache_limiter('none'); //want to allow the caching to happen (privately)

init_session();

$USER->mustHavePerm("basic");

$uploadmanager=new UploadManager;

if ($uploadmanager->validUploadId($_GET['transfer_id'])) {

	ini_set('memory_limit', '148M');

	require_once('geograph/vectors.inc.php');

	customExpiresHeader(3600*24);

	$imagePath = $uploadmanager->_pendingJPEG($_GET['transfer_id']);

	if (file_exists($imagePath)) {

	        $result['suggestions'] = run_inference(array(
        	        'image_id' => 1, //not actully needed on single inference
	                'clip-image' => getImageEmbedding($imagePath),
        	        'clip-title' => getTextEmbeddingWrapper($_GET['title'] ?? "Example"), //wrapper, so uses cache
	                'distance' => 'Unknown',
        	));

	} else {
		$result['error'] ="Upload image not found";
	}
} else {
	$result['error'] = "Bad preview id";
}

outputJSON($result);


#######################

//note this function in intended to just run one inference!
function run_inference($inputs) {

    chdir("/mnt/efs/models");

    require_once 'GeographModelBase.php';

    $allResults = [];
    //$directories = array_filter(glob('*'), 'is_dir'); --glob can be quite slow
    $directories = array('clip','subject','types');

    foreach ($directories as $dir) {
        $modelPhp = $dir . '/model.php';
        $weightsJson = $dir . '/weights.json';

        if (file_exists($modelPhp) && file_exists($weightsJson.".bin")) { //prefer to explicitly use binary version in production

            require_once $modelPhp;

            // Determine class name from directory name
            // e.g. gallery -> GeographGalleryModel
            // Special case for model_types -> GeographTypesModel
            $className = 'Geograph' . ucfirst($dir) . 'Model';

            if (class_exists($className)) {
                try {
                    $model = new $className($weightsJson);
                    $results = $model->predict_api($inputs);

                    unset($model); // free memory

                    $allResults = array_merge($allResults, $results);
                } catch (Exception $e) {
                    // Skip if error as requested
                    error_log("Error running model in $dir: " . $e->getMessage());
                }
            }
        }
    }

    return $allResults;
}

