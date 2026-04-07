<?php

require_once('geograph/global.inc.php');
require_once('geograph/uploadmanager.class.php');

session_cache_limiter('none'); //want to allow the caching to happen (privately)

init_session();

$USER->mustHavePerm("basic");

$uploadmanager=new UploadManager;

if ($uploadmanager->validUploadId($_GET['preview'])) {

	customExpiresHeader(3600*24);

	//first try the high resolution one
	$imagePath = $uploadmanager->_originalJPEG($_GET['preview']);

	//otherwise fallback to the base image
	if (!file_exists($imagePath))
		$imagePath = $uploadmanager->_pendingJPEG($_GET['preview']);

	if (file_exists($imagePath)) {
		$maxw = $maxh = 400;

		// 1. Set the header
		header("Content-Type: image/jpeg");

		list($width, $height) = getimagesize($imagePath);

		// If the image is smaller than our target in ANY dimension,
		// we must shrink the target to match the image. Allowing to get previews of wide panos for example!
		if ($width < $maxw) {
		        $maxw = $width;
		}
		if ($height < $maxh) {
		        $maxh = $height;
		}
		// If both match the original, just stream it
		if ($width == $maxw && $height == $maxh) {
		        header("Content-Type: image/jpeg");
		        readfile($imagePath);
		        exit;
		}

		// 2. Build the command string
		// Using escapeshellarg for security on the input file
		$input = escapeshellarg($imagePath);

		//for panos. attention not working so well
		if ($width/$height > 2.9) {
			// Entropy ignores flat black edges but still looks for detail
			$cmd = "vips smartcrop $input .jpg[strip,Q=87] {$maxw} {$maxh} --interesting entropy";
		} else {
			$cmd = "vips smartcrop $input .jpg[strip,Q=87] {$maxw} {$maxh} --interesting attention";
		}

		// 3. Execute and stream directly to the buffer
		passthru($cmd);
		exit;
	} else {
		$err="Upload image not found";
	}
} else {
	$err = "Bad preview id";
}


//generate an error message image if we reach here
$im  = imagecreate (320, 240);
$bgc = imagecolorallocate ($im, 255, 255, 255);
$tc  = imagecolorallocate ($im, 0, 0, 0);
imagefilledrectangle ($im, 0, 0, 320, 240, $bgc);
imagestring ($im, 1, 5, 5, $err, $tc);

imagejpeg($im);
imagedestroy($im);
