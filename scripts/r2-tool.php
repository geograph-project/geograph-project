<?php
/**
 * $Project: GeoGraph $
 * $Id: conversion.php 2960 2007-01-15 14:33:27Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2008 Barry Hunter (geo@barryhunter.co.uk)
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

$current = getcwd();

//these are the arguments we expect
$param=array(
	'function'=>'test',

	'bucket'=>false, //note, that while empty here, defaults are defined dynamically using config
	'path'=>false,

	'fatal'=>true,
	'table'=>false,
	'quick'=>false,
);

chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

set_time_limit(3600*24);

#####################

if (empty($CONF['r2_endpoint']))
	die("no r2_endpoint defined\n");

require_once "3rdparty/S3.php";

$r2 = new S3($CONF['r2_access_key'], $CONF['r2_secret_key'], true, str_replace('https://','',$CONF['r2_endpoint']), 'auto');

if (method_exists($r2,'setSignatureVersion'))
	$r2->setSignatureVersion('v4'); //r2 only supports v4

#####################

//most of these functions created to deal with backups, so set that as default
if (empty($param['bucket']))
	$param['bucket'] = $CONF['r2_backup_bucket'];

if (!empty($param['table'])) {
	$param['path'] = 'backups/by-table/'.$param['table']."/";
}

#####################

if ($param['function'] == 'test' || $param['function'] == 'head') {
	if ($param['function'] == 'test') {
		$GLOBALS['curl_verbose'] = true;
		$GLOBALS['curl_verbose_dump'] = true;
	}

	if (empty($param['path'])) {
		$param['bucket'] = $CONF['r2_photo_bucket'];
		$param['path'] = 'robots.txt';
	}

	print "HEAD {$param['bucket']}/{$param['path']}\n";
	$responce = $r2->getObjectInfo($param['bucket'], $param['path']);

	print_r($responce);
	print "\n";
	exit;
}

#####################

if ($param['function'] == 'get') {
	$filename = $param['path'];
	$tmpfname = "/tmp/".basename($filename);

	$r = $r2->getObject($param['bucket'], $filename, $tmpfname);
	print "$tmpfname : ".filesize($tmpfname)." bytes\n";
	print_r($r);
}

#####################

if ($param['function'] == 'getbucket') {
	$list = $r2->getBucket($param['bucket'], $param['path']);
	print json_encode($list);
	exit;
}

#####################

if ($param['function'] == 'list') {
	$list = $r2->getBucket($param['bucket'], $param['path']);
	ksort($list, SORT_NATURAL);

	$total = 0;
	foreach ($list as $filename => $row) {
                if (!empty($row['prefix'])) { //a virtual directory!
                        printf("%s %10s %16s %16s %s\n",
                                 'd', '', '', '', $filename);
                } else { // else a file
                        printf("%s %10d %16s %16s %s   %s\n",
                                 '-', $row['size'], date('Y-m-d H:i:s',$row['time']), $row['hash'], $filename, $row['class']);
			$total+=$row['size'];
		}
	}
	printf("%s %10d %16s %16s %s   %s\n", '.', $total, 'TOTAL', count($list)." Files", '','');
	exit;
}

#####################

if ($param['function'] == 'upload') {
	chdir($current);

	if (empty($param['path'])) {
		$param['path'] = 'backups/';
	}

	//this is only for testing, as gets whole bucket, very innefifient!
	$list = $r2->getBucket($param['bucket'], $param['path'], null, null);

	print count($list)." files found\n";
	//print_r($list);
	sleep(5);

	$source = $param['path'];
	$dest = $source; //its used as bucket key anyway!

	$h = popen("find $source -follow -type f -name '*.sql.*'", 'r'); //want to avoid the latest.md5 files!
	while($h && !feof($h)) {
		$line = trim(fgets($h));
		if (empty($line))
			continue;

		$local = $line; //could add path to make absolute?
		$destination = str_replace($source, $dest, $line);

		if (isset($list[$destination])) {
			$stat = $list[$destination];

			if ($stat['hash'] == md5_file($local)) { //&&$param['move'] ??
				print "#delete $destination (hash match!)\n";
				unlink($local);
			} else {
				print "#skipping $destination (mismatch)\n";
			}
			continue;
		}

		$bytes = number_format(filesize($local),0);
		print "Uploading $local ($bytes)\n";
		$r = $r2->putObject($r2->inputFile($local), $param['bucket'], $destination);

		//for now, we dont delete the $local right away. Will delete it next time, after checking the md5sum matches!

		if ($r !== true) {
			print_r($r);
			if ($param['fatal'])
				exit;
		}
	}
}

#####################

if ($param['function'] == 'stats') {

	if (empty($param['path'])) {
		$param['path'] = 'backups/by-table/';
	}

	//this is only for testing, as gets whole bucket, very innefifient!

	// public static function getBucket($bucket, $prefix = null, $marker = null, $maxKeys = null, $delimiter = null, $returnCommonPrefixes = false, $v2 = true)

	$list = $r2->getBucket($param['bucket'], $param['path'], null, $param['quick']?1000:null);

	print count($list)." files found\n";
	//print_r($list);

	//backups/by-table/tag/shard50000-6/2024-05-29-12_tag.300000-349999.sql.xz
	//backups/by-table/blog/2024-05-29-12_blog.sql.xz

	$total = 0;
	$sizebyday = $countbyday = $tablesbyday = $tables = array();
	foreach ($list as $filename => $row) {
		/*
                if (!empty($row['prefix'])) { //a virtual directory!
                        printf("%s %10s %16s %16s %s\n",
                                 'd', '', '', '', $filename);
                } else { // else a file
                        printf("%s %10d %16s %16s %s   %s\n",
                                 '-', $row['size'], date('Y-m-d H:i:s',$row['time']), $row['hash'], $filename, $row['class']);
                }
		*/

		if (!empty($row['prefix'])) { //a virtual directory!

		} elseif (preg_match('/by-table\/(\w+)\/(shard\d+-\d+\/)?(\d+-\d+-\d+)-.*\.sql\./',$filename,$m)) { // else a file
			list($all,$table,$shard,$day) = $m;

			$total+=$row['size'];
			@$sizebyday[$day]+=$row['size'];
			@$countbyday[$day]++;
			@$tablesbyday[$day][$table]++;
			@$tables[$table]++;

			@$daysbytable[$table][$day]+=$row['size'];
		}

        }

	#################################

	foreach($daysbytable as $table => $days) {
		$count = count($days);
		if ($count > 1) {
			$sum = array_sum($days);
			if ($sum > 1000000) { //dont worry about small trival tables
				@$countbytable[$table] = $count;

				//find the average, but IGNORING the biggest day. (in theory tables ALWAYS have a big day - when first dumped, we wanting to find consistently big days!)
				$max = max($days);
				$avgbytable[$table] = ($sum-$max)/($count-1);
			}
		}
	}

	#################################

	print "\n\n";

	ksort($countbyday);
	foreach ($countbyday as $day => $count) {
		printf("%10s %16s bytes, %5d files, %5d tables\n", $day, number_format($sizebyday[$day],0), $count, count($tablesbyday[$day]));
	}
		printf("%10s %16s bytes, %5d files, %5d tables\n", "TOTAL", number_format($total,0), count($list), count($tables));

	print "\n\n";

	#################################

	print "Tables Sorted by days dumped - Most Dumped Only\n";
	arsort($countbytable);

	$last=0;
	foreach($countbytable as $table => $days) {
		printf("%50s  %4d days, %16s bytes, %15s avg\n", $table, $days, nf(array_sum($daysbytable[$table])), nf($avgbytable[$table]) );
		if ($last && $last != $days) //stop ones it decremnts!
			break;
		$last = $days;
	}
	print "(average is ignoring the biggest day - likly the day first dumped)\n";

	print "\n\n";

	#################################

	print "Tables Sorted by Daily Average (ignoring their biggest day!) - Top 10\n";
	arsort($avgbytable);

	$c=0;
	foreach($avgbytable as $table => $avg) {
		printf("%50s  %4d days, %15s avg\n", $table, $countbytable[$table]-1, nf($avg)); //-1 because the average ignores the biggest day!
		$c++;
		if ($c==10)
			break;
	}

	print "\n\n";
}



#####################

if ($param['function'] == 'matrix') {
	if (empty($param['path']))
		$param['path'] = 'backups/by-table/tag/';

	// public static function getBucket($bucket, $prefix = null, $marker = null, $maxKeys = null, $delimiter = null, $returnCommonPrefixes = false, $v2 = true)
	$list = $r2->getBucket($param['bucket'], $param['path'], null, $param['quick']?1000:null);
	print count($list)." files found\n";

	//backups/by-table/tag/shard50000-6/2024-05-29-12_tag.300000-349999.sql.xz
	$metrix = $days = array();
	foreach ($list as $filename => $row) {
		if (!empty($row['prefix'])) { //a virtual directory!

		} elseif (preg_match('/by-table\/(\w+)\/shard(\d+-\d+)\/(\d+-\d+-\d+)-.*\.sql\./',$filename,$m)) { // else a file
			list($all,$table,$shard,$day) = $m;
			@$matrix[$shard][$day] = $row['size'];
			@$days[$day]=1;
		}
        }

	ksort($matrix, SORT_NATURAL);
	ksort($days, SORT_NATURAL);

	printf("%15s",'');
	foreach ($days as $day => $count) {
		printf(" %10s",$day);
	}
	print "\n";

	foreach($matrix as $shard => $data) {
		printf("%15s",$shard);
		foreach ($days as $day => $count) {
			if (!empty($data[$day])) {
				printf(" %10d",$data[$day]);
			} else {
				printf(" %10s",'');
			}
		}
		print "\n";
	}

	printf("%15s",'');
	foreach ($days as $day => $count) {
		printf(" %10s",$day);
	}
	print "\n";
}



#####################
#####################

function nf($in) {
	return number_format($in,0);
}
