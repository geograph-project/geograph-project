<?php

require('conf/'.$_SERVER['HTTP_HOST'].'.conf.php');

if (empty($CONF['phpbin'])) {
	header("HTTP/1.0 404 Not Found"); //FIXME
	exit(1);
} else {
	$phpbin=$CONF['phpbin'];
}

if (empty($CONF['phpscriptopt'])) {
	$phpopt='';
} else {
	$phpopt=$CONF['phpscriptopt'];
}

if (!empty($CONF['mem_maptiles'])) {
	$memopt = '-d memory_limit='.$CONF['mem_maptiles'];
} else {
	$memopt = '';
}

$scripts=array(
	'maps'=> "{$phpbin} {$phpopt} {$memopt} {$_SERVER['DOCUMENT_ROOT']}/../scripts/recreate_maps.php --load=1.5 --dir={$_SERVER['DOCUMENT_ROOT']}/../ --config={$_SERVER['HTTP_HOST']}",
	'kml' => "{$phpbin} {$phpopt} {$_SERVER['DOCUMENT_ROOT']}/../scripts/recreate_kml.php --load=1.5 --dir={$_SERVER['DOCUMENT_ROOT']}/../ --config={$_SERVER['HTTP_HOST']}",
	'tmp' => "{$phpbin} {$phpopt} {$_SERVER['DOCUMENT_ROOT']}/../scripts/cleanup_tmp.php --load=1.5 --dir={$_SERVER['DOCUMENT_ROOT']}/../ --config={$_SERVER['HTTP_HOST']}",
	'dumpimg'=> "{$phpbin} {$phpopt} {$memopt} {$_SERVER['DOCUMENT_ROOT']}/../scripts/dump_tables.php --load=1.5 --dir={$_SERVER['DOCUMENT_ROOT']}/../ --config={$_SERVER['HTTP_HOST']} --item='@images'",
	'dumpp1'=> "{$phpbin} {$phpopt} {$memopt} {$_SERVER['DOCUMENT_ROOT']}/../scripts/dump_tables.php --load=1.5 --dir={$_SERVER['DOCUMENT_ROOT']}/../ --config={$_SERVER['HTTP_HOST']} --item='@priority1'",
	'dumpp2'=> "{$phpbin} {$phpopt} {$memopt} {$_SERVER['DOCUMENT_ROOT']}/../scripts/dump_tables.php --load=1.5 --dir={$_SERVER['DOCUMENT_ROOT']}/../ --config={$_SERVER['HTTP_HOST']} --item='@priority2'",
	'dumpp3'=> "{$phpbin} {$phpopt} {$memopt} {$_SERVER['DOCUMENT_ROOT']}/../scripts/dump_tables.php --load=1.5 --dir={$_SERVER['DOCUMENT_ROOT']}/../ --config={$_SERVER['HTTP_HOST']} --item='@priority3'",
	'dumpp4'=> "{$phpbin} {$phpopt} {$memopt} {$_SERVER['DOCUMENT_ROOT']}/../scripts/dump_tables.php --load=1.5 --dir={$_SERVER['DOCUMENT_ROOT']}/../ --config={$_SERVER['HTTP_HOST']} --item='@priority4'",
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
