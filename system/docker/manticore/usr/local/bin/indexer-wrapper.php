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
    throw new RuntimeException('mysqli connection error: ' . mysqli_connect_error());
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
			if ($name == 'tickets_closed') $name = 'tickets_closed_delta'; //anoyingly, the index filename, doesnt amtch
			$sql = "REPLACE INTO sph_server_index SET index_name = $name, server_id = $server_id, last_indexed = FROM_UNIXTIME($time)";
			print "$sql;\n";
			db_Execute($sql);
		}
	}
	exit;
}

#####################################################

//this is deliberately a GLOBAL lock, so that no two instances (even staging!) are indexing at the same time!
if (!empty($param['lock']))
	if (!db_getOne("SELECT GET_LOCK('indexer_active',60)"))
 	       die("unable to get a lock;\n");

$hour = date('G');
$indexes = db_getAll("
SELECT sph_index.index_name, preindex, postindex, server_id, last_indexed
FROM sph_index LEFT JOIN sph_server_index ON (sph_index.index_name = sph_server_index.index_name AND server_id = $server_id)
WHERE DATE_ADD(coalesce(last_indexed,'2000-01-01 00:00:00'), interval `minutes` minute) < NOW() AND minhour <= $hour ORDER BY type+0");

if (empty($indexes)) {
	if (!empty($param['lock']))
		db_Execute("DO RELEASE_LOCK('indexer_active')");
	exit;
}

#####################################################

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

		$cmd = "indexer --config /etc/sphinxsearch/sphinx.conf ".implode(" ",array_keys($list))." --rotate"; //--sighup-each if large indexes?

		##################

		$start = microtime(true);
		passthru($cmd);
		$end = microtime(true);

		##################

		$name = db_Quote(trim($row['index_name']));

		$sql = "REPLACE INTO sph_server_index SET index_name = $name, server_id = $server_id, last_indexed = NOW()";
		db_Execute($sql);

		$sql = "INSERT INTO sph_log SET index_name = $name, server_id = $server_id, created = NOW(), taken = ".($end-$start);
		db_Execute($sql);

		$done[$row['index_name']] = 1;
	}
	if (!empty($param['lock']))
		db_Execute("DO RELEASE_LOCK('indexer_active')");
	exit;
}

#####################################################

$list = array();

foreach ($indexes as $row) {
	if (!empty($row['preindex']))
		$list = array($row['preindex']=>1)+$list;

	$list[$row['index_name']]=1;

	if (!empty($row['postindex']))
		$list[$row['postindex']]=1;
}

#####################################################

$cmd = "indexer --config /etc/sphinxsearch/sphinx.conf ".implode(" ",array_keys($list))." --rotate"; //--sighup-each if large indexes?
passthru($cmd);

foreach ($list as $index => $dummy) {
	$name = db_Quote(trim($index));

	$sql = "REPLACE INTO sph_server_index SET index_name = $name, server_id = $server_id, last_indexed = NOW()";
	db_Execute($sql);

	$sql = "INSERT INTO sph_log SET index_name = $name, server_id = $server_id, created = NOW()";
	db_Execute($sql);
}

#####################################################

if (!empty($param['lock']))
	db_Execute("DO RELEASE_LOCK('indexer_active')");
