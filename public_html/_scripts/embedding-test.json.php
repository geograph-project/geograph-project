<?php
// label-vectors.json.php

require_once('geograph/global.inc.php');
require_once('geograph/vectors.inc.php');

// Set headers for JSON response and CORS
header('Content-Type: application/json');
//header('Access-Control-Allow-Origin: *');
//customExpiresHeader(3600*24*7);
customNoCacheHeader();

// Input parameters
$model = (isset($_GET['model']) && ctype_alnum($_GET['model']))?$_GET['model']: 'clip';
$label = (isset($_GET['label']) && ctype_alnum($_GET['label']))?$_GET['label']: 'test';
$image = (isset($_GET['image']) && ctype_alnum($_GET['image']))?$_GET['image']: '';

#########################################################################
$response = array();
$response['ts'] = time();
$response['model'] = $model;

//actully this script just exist fetching embeding from API - so specifically WITHOUT caching.
// we dont return the vector, to prevent this being abused.

if (!empty($image)) {
	//actully we dont allow custom images. Just a test image! (these guide images are photographs)
	$image = "../templates/basic/img/guide1.jpg";

	$start = microtime(true);
        $vector = getImageEmbedding($image, null,null, $model);
	$end = microtime(true);

} elseif (!empty($label)) {

	$start = microtime(true);
        $vector = getTextEmbedding($label, $model);
	$end = microtime(true);
}


if (isset($vector)) {
	$response['time'] = round($end-$start,6);
	$response['type'] = gettype($vector);
	if (is_array($vector)) {
		$response['size'] = count($vector);
		$normalized = array_map(function($val) {
		    return (int)round($val * 100000);
		}, $vector);
		$response['hash'] = md5(implode('|', $normalized));
		$response['vsum'] = round(array_sum($vector),6);
	}
}

echo json_encode($response);
