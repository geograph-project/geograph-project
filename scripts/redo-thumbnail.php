<?

$param = array('execute'=>false, 'maxd'=>1024, 'limit'=>1, 'id'=>197578);

chdir(__DIR__);
require "./_scripts.inc.php";

chdir($_SERVER['DOCUMENT_ROOT']); //even as a script this is updated!

if ($param['execute'] && trim(`whoami`) != 'www-data') {
	die("ERROR: Needs to be run as www-data!\n");
}

if ($param['execute']) $db = GeographDatabaseConnection(false);
$db_read = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$maxd = $param['maxd'];
$row = array('gridimage_id'=>$param['id']);

$filesystem = new FileSystem();
$filesystem->log=1;

if (!$filesystem->file_exists($_SERVER['DOCUMENT_ROOT']."/photos/error.jpg")) {
	die("ERROR: {$_SERVER['DOCUMENT_ROOT']} does not appear to be connected to FileSystem\n");
}



	$image = new GridImage($param['id']);

	if ($maxd < 640) {

		$CONF['template']='archive' ; //this actully make it avoid checking existance!

		$resized = $image->getThumbnail($maxd,$maxd==216?160:$maxd, 2); //does NOT have a option to avoid check_exists!
		$path = $resized['url'];


		$CONF['template']='basic' ; //needs resetting see above!

	} else {
		//$path = $image->getImageFromOriginal($maxd,$maxd);
		//CANT use the above, as will create the thumbnail on demand! (doesnt have a checkexits option to disable it!)
		$path = $image->_getOriginalpath(FALSE, false, "_{$maxd}x{$maxd}");
	}

	if (basename($path) == "error.jpg")
		die("got $path for {$row['gridimage_id']}\n"); //just a emergency measure, so the code below doesnt blindly delete the error image!





	// then only look at cases where the file exists (got this far becasue it NOT in gridimage_thumbsize, which probably means its currutped!)
	if ($filesystem->file_exists($_SERVER['DOCUMENT_ROOT'].$path)) {

		// Task 1 - Delete the file
		$cmd = "unlink .$path";
		print " $cmd\n";
		if ($param['execute'])
			$filesystem->unlink($_SERVER['DOCUMENT_ROOT'].$path);

	} else {
		print("$path not found\n");
	}


		// Task 2 - Clear Memcache
		$key = "L~is:{$row['gridimage_id']}:{$maxd}x{$maxd}";
		$mkey = "{$row['gridimage_id']}:{$maxd}x{$maxd}";

		print " delete $key\n";
		if ($param['execute']) {
	  		$result = $memcache->name_delete('is',$mkey);
			print "REsult: $result\n";
		}


		// Task 3 - Clear ThumbSize
		$sql = "DELETE FROM gridimage_thumbsize WHERE gridimage_id = {$row['gridimage_id']} AND maxw = {$maxd}";
		print " $sql\n";
		if ($param['execute']) {
			$db->Execute($sql);
			print "Rows: ".mysql_affected_rows($db->_connectionID)."\n";
		}

		// Task 4 - Invalidate
		if ($maxd <=1024) {
			//TODO, inline the code to do this!
			print "php scripts/test-s3-invalidation.php --path=$path --dir={$param['dir']}\n";
		}


		// Task 5 - Refresh the thumbnail - technically optional but may as well?
		if ($param['execute']) {
sleep(3); //not ideal, but try to deal with S3 eventual consistency. No longer using s3fs, that makes sure updates are visible right after being done (using its own cache!)
$image = new GridImage($param['id']);

			if ($maxd < 640) {
				$resized = $image->getThumbnail($maxd,$maxd==216?160:$maxd, 2);
				$path = $resized['url'];
			} else {
				$path = $image->getImageFromOriginal($maxd,$maxd);
			}
			//TODO could try now testing if the file has been created ok this time?

sleep(3); //not ideal, but try to deal with S3 eventual consistency. No longer using s3fs, that makes sure updates are visible right after being done (using its own cache!)

                        print "bytes: ".$filesystem->filesize($_SERVER['DOCUMENT_ROOT'].$path);
                        $size = $filesystem->getimagesize($_SERVER['DOCUMENT_ROOT'].$path);
			if (!empty($size))
				print ", size: {$size[3]}";

			print " ($path)";
			print "\n";

			print "identify {$_SERVER['DOCUMENT_ROOT']}$path\n";
		}

		print "\n\n";

