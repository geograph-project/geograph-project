#!/usr/bin/php
<?php

$param = array('single'=>1,'prime'=>0, 'lock'=>1);

#####################################################
//very simple argument parser

for($i=1; $i<count($_SERVER['argv']); $i++) {
        $arg=$_SERVER['argv'][$i];
        if (substr($arg,0,2)=='--') {
                $arg=substr($arg,2);
                $bits=explode('=', $arg,2);
                if (isset($param[$bits[0]])) {
                        //if we have a value, use it, else just flag as true
                        $param[$bits[0]]=isset($bits[1])?$bits[1]:true;
                }
                else die("unknown argument --$arg\n");
        }
        else die("unexpected argument $arg\n");
}

#####################################################
# real basic wrapper (somewhat like adodb)

$db = mysqli_connect($_SERVER['MYSQL_HOST'],$_SERVER['MYSQL_USER'],$_SERVER['CONF_DB_PWD'],$_SERVER['MYSQL_DATABASE']);
if (mysqli_connect_errno()) {
        error_log('FATAL ERROR, mysqli connection error: ' . mysqli_connect_error());
        exit(1);
}

function db_Quote($in) {
	return "'".mysqli_real_escape_string($GLOBALS['db'], $in)."'";
}
function db_getOne($sql) {
	$result = mysqli_query($GLOBALS['db'], $sql);
	return mysqli_fetch_array($result)[0];
}
function db_getAll($sql) {
	$data = array();
	$result = mysqli_query($GLOBALS['db'], $sql);
	while ($row = mysqli_fetch_assoc($result))
		$data[] = $row;
	return $data;
}
function db_Execute($sql) {
	return mysqli_query($GLOBALS['db'], $sql);
}

#####################################################

$server_id = db_Quote(trim(`hostname`));
$pid = getmypid();

#####################################################

if (!empty($param['prime'])) {
	$h = popen("find /var/lib/manticore/data/ -name '*.sph' -printf '%T@ %P\\n'",'r');
	while ($h && !feof($h)) {
		$line = trim(fgets($h));
		$bits = explode(' ',$line);
		if (!empty($bits[1])) {
			$time = intval($bits[0]);
			$parts = explode('.',$bits[1]); //to remove the .sph extension

			$name = db_Quote(trim($parts[0]));
			if ($name == "'tickets_closed'") $name = "'tickets_closed_delta'"; //anoyingly, the index filename, doesnt amtch
			$sql = "REPLACE INTO sph_server_index SET index_name = $name, server_id = $server_id, last_indexed = FROM_UNIXTIME($time)";
			print "$sql;\n";
			db_Execute($sql);

			if (mysqli_affected_rows($db) == 1) { //if =1 then first time created, if 2 then it just rewriting existing!
				$sql = "INSERT INTO sph_indexer_log SET index_name = $name, server_id = $server_id, created = NOW(), pid = $pid";
				db_Execute($sql);
			}
		}
	}
	exit;
}

#####################################################

$lockwait = 60; //when called routinely from cron, just die if can't get lock, will go again in 5 minutes anyway

if (is_dir("/var/lib/manticore/data/") && is_writable("/var/lib/manticore/data/")) {
	//if the folder is empty (no indexes) - then it probably means its a brand new instance.
	// just to be safe delete any metadata records, just incase there was some activity in past, but the peristant volumn was not maintained
	// if we inherit a old volume, then the normal process, should rebuild any indexes anyway!
	$indexes = glob("/var/lib/manticore/data/"."*.sph");
	if (empty($indexes)) {
		$sql = "DELETE FROM sph_server_index WHERE server_id = $server_id";
		print "$sql;\n";
		db_Execute($sql);
		$lockwait = 3600; //when running in the init-container, we should wait for the lock!
	}
} else {
        error_log("FATAL ERROR, unable to access /var/lib/manticore/data/");
        exit(1);
}

#####################################################

//this is deliberately a GLOBAL lock, so that no two instances (even staging!) are indexing at the same time!
if (!empty($param['lock']))
	if (!db_getOne("SELECT GET_LOCK('indexer_active',$lockwait)")) {
 		print("FATAL ERROR: unable to get a lock;\n");
		exit(2);
	}

$hour = date('G');
$indexes = db_getAll("
SELECT sph_index.index_name, preindex, postindex, posttrigger, server_id, last_indexed
FROM sph_index LEFT JOIN sph_server_index ON (sph_index.index_name = sph_server_index.index_name AND server_id = $server_id)
WHERE (active=0 AND last_indexed IS NULL) OR triggered > 0 OR (active = 1 AND DATE_ADD(coalesce(last_indexed,'2000-01-01 00:00:00'), interval `minutes` minute) < NOW() AND minhour <= $hour)
ORDER BY type+0, index_name");

if (empty($indexes)) {
	if (!empty($param['lock']))
		db_Execute("DO RELEASE_LOCK('indexer_active')");
	exit;
}

#####################################################

$done = array();
$trigger = array();

function trigger_post() {
	global $db, $done, $trigger, $server_id, $param;
	if (!empty($trigger)) {
		foreach ($trigger as $index => $dummy) {
			if (empty($done[$index])) {
				$name = db_Quote(trim($index));

				$sql = "UPDATE sph_server_index SET triggered=1 WHERE index_name = $name AND server_id = $server_id";
				db_Execute($sql);
			}
		}
	}
}

register_shutdown_function('trigger_post');

#####################################################

function process_list($list, $log = null) {
	global $server_id, $done, $pid, $param;

	//todo, shoud NOT do --rotate if running in the init-container, but probably doesnt matter!
	$cmd = "indexer --config /etc/sphinxsearch/sphinx.conf ".implode(" ",array_keys($list))." --rotate"; //--sighup-each if large indexes?

	##################
	$return_code = null; //as passed by reference

	ob_start();
	$start = microtime(true);
	passthru($cmd, $return_code);
	$end = microtime(true);
	$output = ob_get_contents();
	ob_end_flush();

	// 2. Check both the exit code and the text for "ERROR"
	// This catches the 'write error' issues shown in your logs
	$has_error = ($return_code !== 0 || stripos($output, 'ERROR:') !== false);

	foreach ($list as $index => $dummy) {
		$name = db_Quote(trim($index));
		$errorMessage = null;

		// Matches: ERROR: index 'your_index': some error message until newline
		if (preg_match("/ERROR: index '$index': (.*)/i", $output, $matches)) {
		        $errorMessage = trim($matches[1]);
		} elseif ($return_code !== 0) {
			$errorMessage = "Process exited with code $return_code (check logs)";
		}

		//only update if successful
		if ($errorMessage === null && $return_code === 0) {
			$sql = "REPLACE INTO sph_server_index SET index_name = $name, server_id = $server_id, last_indexed = NOW()";
			db_Execute($sql);
		}

		//always log, even failures
		$sql = "INSERT INTO sph_indexer_log SET index_name = $name, server_id = $server_id, created = NOW(), pid = $pid";
		if (!empty($errorMessage))
			$sql .=", error_message = ".db_Quote($errorMessage);
		if ($index == $log)
			$sql .=", taken = ".($end-$start);
		db_Execute($sql);
		$done[$index]=1;
	}
}

#####################################################
# run each index as a seperate process

if (!empty($param['single'])) {
	$done = array();
	foreach ($indexes as $row) {
		if (!empty($done[$row['index_name']])) //may of been done as pre/post on previous run!
			continue;

		$list = array();
		if (!empty($row['preindex']))
			$list = array($row['preindex']=>1)+$list;
		$list[$row['index_name']]=1;
		if (!empty($row['postindex']))
			$list[$row['postindex']]=1;
		if (!empty($row['posttrigger']))
			$trigger[$row['posttrigger']]=1;

		process_list($list, $row['index_name']);
	}
	if (!empty($param['lock']))
		db_Execute("DO RELEASE_LOCK('indexer_active')");
	exit;
}

#####################################################
# else build just a single list (so it deduplicates)

$list = array();

foreach ($indexes as $row) {
	if (!empty($row['preindex']))
		$list = array($row['preindex']=>1)+$list;

	$list[$row['index_name']]=1;

	if (!empty($row['postindex']))
		$list[$row['postindex']]=1;
	if (!empty($row['posttrigger']))
		$trigger[$row['posttrigger']]=1;
}

process_list($list);

#####################################################

if (!empty($param['lock']))
	db_Execute("DO RELEASE_LOCK('indexer_active')");
