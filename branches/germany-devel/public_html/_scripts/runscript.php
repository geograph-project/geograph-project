<?php

require('conf/'.$_SERVER['HTTP_HOST'].'.conf.php');

#$phpdir="/usr/bin"; # FIXME $CONF
if (empty($CONF['phpdir'])) {
	header("HTTP/1.0 404 Not Found"); //FIXME
	exit(1);
}
$phpdir=$CONF['phpdir'];

$scripts=array(
	'maps'=> "{$phpdir}/php {$_SERVER['DOCUMENT_ROOT']}/../scripts/recreate_maps.php --load=1.5 --dir={$_SERVER['DOCUMENT_ROOT']}/../ --config={$_SERVER['HTTP_HOST']}",
	'kml' => "{$phpdir}/php {$_SERVER['DOCUMENT_ROOT']}/../scripts/recreate_kml.php --load=1.5 --dir={$_SERVER['DOCUMENT_ROOT']}/../ --config={$_SERVER['HTTP_HOST']}",
	'tmp' => "{$phpdir}/php {$_SERVER['DOCUMENT_ROOT']}/../scripts/cleanup_tmp.php --load=1.5 --dir={$_SERVER['DOCUMENT_ROOT']}/../ --config={$_SERVER['HTTP_HOST']}"
);

$logfile=$_SERVER['DOCUMENT_ROOT']."/../log/runscript.log";

if (isset($_GET['job'])) {
	$job=$_GET['job'];
} else {
	$job='';
}

if (($_SERVER['REMOTE_ADDR'] != $_SERVER['SERVER_ADDR']) || !array_key_exists($job, $scripts)) {
	header("HTTP/1.0 404 Not Found"); //FIXME
	exit(1);
}

$logcmd=" >> {$logfile} 2>&1";
chdir($_SERVER['DOCUMENT_ROOT']);
header('content-type: text/plain');
$return_var = 0;
passthru($scripts[$job].$logcmd, $return_var);
echo $return_var;
#exit($return_var);

?>
