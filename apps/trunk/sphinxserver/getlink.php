<?

if (!empty($_GET['url'])) {
	##http://tinyurl.com/api-create.php?url=http://json-tinyurl.appspot.com/

	$short = file_get_contents("http://tinyurl.com/api-create.php?url=".urlencode($_GET['url']));

	if ($short) {
		$res = array('ok'=>TRUE,'tinyurl'=>$short);
	} else {
		$res = array('ok'=>FALSE,'error'=>'unknown error');
	}

} else {
	$res = array('ok'=>FALSE,'error'=>'unknown url');
}



	if (isset($_GET['callback'])) {
		$callback=preg_replace('/[^\w$]+/','',$_GET['callback']);
		if (empty($callback)) {
			$callback = "geograph_callback";
		}

		header('Content-type: application/x-javascript');

		print "{$callback}(";
	} else {
		header('Content-type: application/json');
	}

	print str_replace('-INF','0',json_encode($res));

	if (!empty($callback))
		print ");";


