<?php

require('conf/'.$_SERVER['HTTP_HOST'].'.conf.php');

#$phpdir="/usr/bin"; # FIXME $CONF
if (empty($CONF['phpdir'])) {
	header("HTTP/1.0 404 Not Found"); //FIXME
	exit(1);
}
$phpdir=$CONF['phpdir'];
if (empty($CONF['phpbin'])) {
	$phpbin='php';
} else {
	$phpbin=$CONF['phpbin'];
}

if (!empty($CONF['mem_maptiles'])) {
	$memopt = '-d memory_limit='.$CONF['mem_maptiles'];
} else {
	$memopt = '';
}

$scripts=array(
	'maps'=> "{$phpdir}/{$phpbin} {$memopt} {$_SERVER['DOCUMENT_ROOT']}/../scripts/recreate_maps.php --load=1.5 --dir={$_SERVER['DOCUMENT_ROOT']}/../ --config={$_SERVER['HTTP_HOST']}",
	'kml' => "{$phpdir}/{$phpbin} {$_SERVER['DOCUMENT_ROOT']}/../scripts/recreate_kml.php --load=1.5 --dir={$_SERVER['DOCUMENT_ROOT']}/../ --config={$_SERVER['HTTP_HOST']}",
	'tmp' => "{$phpdir}/{$phpbin} {$_SERVER['DOCUMENT_ROOT']}/../scripts/cleanup_tmp.php --load=1.5 --dir={$_SERVER['DOCUMENT_ROOT']}/../ --config={$_SERVER['HTTP_HOST']}"
);

$logfile=$_SERVER['DOCUMENT_ROOT']."/../log/runscript.log";

if (isset($_GET['job'])) {
	$job=$_GET['job'];
} else {
	$job='';
}

if (($_SERVER['REMOTE_ADDR'] != $_SERVER['SERVER_ADDR']) && $_SERVER['REMOTE_ADDR'] != $CONF['server_ip'] || !array_key_exists($job, $scripts)) {
	trigger_error("rs error: '{$_SERVER['REMOTE_ADDR']}' != '{$_SERVER['SERVER_ADDR']}'", E_USER_WARNING);
	header("HTTP/1.0 404 Not Found"); //FIXME
	exit(1);
}
if (!empty($CONF['time_runscript'])) {
	set_time_limit($CONF['time_runscript']);
}

#file_put_contents($logfile, "start $job ".date("Y-m-d H:i:s")."\n", FILE_APPEND);

$logcmd=" >> {$logfile} 2>&1";
chdir($_SERVER['DOCUMENT_ROOT']);
header('content-type: text/plain');
$return_var = 0;
passthru($scripts[$job].$logcmd, $return_var);

#file_put_contents($logfile, "end $job ".date("Y-m-d H:i:s")."\n", FILE_APPEND);

echo $return_var;
#exit($return_var);

?>