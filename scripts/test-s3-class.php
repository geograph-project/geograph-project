<?php
/**
 * $Project: GeoGraph $
 * $Id: recreate_maps.php 2996 2007-01-20 21:39:07Z barry $
 *
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Barry Hunter (geo@barryhunter.co.uk)
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

############################################

$param = array(
	'execute'=>0,'log'=>1,'verbose'=>0,'read'=>0,'fulltest'=>0,'sizes'=>0,'size'=>0,
	'src' => "/tmp/197537_f6908b09_213x160.jpg",
	'dst' => "photos/19/75/197537_f6908b09_213x160.jpg.test.jpg", //a path 'relative' to document-root!
);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

if (empty($filesystem)) //eventually gloabl will do this!
	$filesystem = new FileSystem(); //sets up configuation automagically

if (!empty($param['log']))
	$filesystem->log = true;

if (!empty($param['verbose'])) {
	$GLOBALS['curl_verbose'] = $param['verbose'];
	print_r($filesystem);
}

############################################

$local = $param['src'];
$destination = $_SERVER['DOCUMENT_ROOT']."/".$param['dst'];

$r = $filesystem->getBucketPath($destination);

print_r($r);

############################################

if (!empty($param['read'])) {

	if ($param['read'] == -1)
		$destination .="404";
	elseif (strlen($param['read']) > 10)
		$destination=$_SERVER['DOCUMENT_ROOT']."/".$param['read'];

	list($bucket, $uri) = $filesystem->getBucketPath($destination);

	print "($bucket, $uri)\n";

	if (empty($bucket))
		die("unknown bucket for $destination\n");

	if ($param['read'] == 2)
		$r = $filesystem->getObjectInfo($bucket, $uri);
	else
		$r = $filesystem->getObject($bucket, $uri);

	if (!empty($r) && !empty($r->body))
		$r->body = "string(".strlen($r->body)." bytes)";
	if (is_array($r))
		foreach ($r as $key=>$value)
			if (preg_match('/(date|time)$/',$key))
				$r[$key] = $value." - ".date('r',$value);
	var_dump($r);
	exit;
}


############################################
//this is the original basic 'write' test

if (!empty($param['execute'])) {

	//$r = $filesystem->copy($local,$destination);
	$r = 'skiped';

	print "COPY: $local -> $destination :";
	print_r($r);
	print "\n";

	############################################

	$destination = str_replace('213x160',"120x120",$destination);

	$r = $filesystem->execute($cmd = "convert %s -resize 120x120 jpg:%d",$local,$destination);

	print "$cmd: $local -> $destination :";
	print_r($r);
	print "\n";
}

############################################

if (!empty($param['sizes'])) {

	//ls -al /mnt/s3-photos-staging/photos/19/75/197537_f6908b09*.jpg
	$glob = dirname($destination)."/197537_f6908b09*.jpg";
	foreach(glob($glob) as $filename) {
		print basename($filename).": ";
		$r = $filesystem->getimagesize($filename,$param['sizes']-1);
		print preg_replace('/\s+/',' ',print_r($r,true));
		print "\n";
	}

	exit;
}

############################################
//special tester for resting ranged query, and IF works for sizeing jpgs!

if (!empty($param['size'])) {
	$filename = dirname($destination)."/197537_f6908b09_original.jpg";

	list($bucket, $filename) = $filesystem->getBucketPath($filename);

	$range = null;
	if (preg_match('/_original/',$filename))
	        $range = "bytes=0-".intval($param['size']);

	$tmpfname = tempnam("/tmp", "r".getmypid());

        $r = $filesystem->getObject($bucket, $filename, $tmpfname, $range);

	print "size: ".filesize($tmpfname)."\n";

	var_dump(getimagesize($tmpfname));

	unlink($tmpfname);
	exit;
}


############################################

if (empty($param['fulltest']))
	exit;

function test_file($destination) {
	global $filesystem;

sleep(2);

	list($bucket, $uri) = $filesystem->getBucketPath($destination);

	print $uri;
        $r = $filesystem->getObjectInfo($bucket, $uri);
        if (is_array($r))
                foreach ($r as $key=>$value)
                        if (preg_match('/(date|time)$/',$key))
                                print ", $key: ".date('H:i:s',$value);
	print "\n";
}

############################################

$data = file_get_contents($local);
$img = imagecreatefromjpeg($local);
$copied = $local.".copy.jpg"; copy($local,$copied);

$dir = dirname($destination);

//	function __construct() {
//	function getBucketPath($filename) {
//	function copy($local, $destination, $acl = self::ACL_FULL_CONTROL, $storage = null) {

//	function file_put_contents($destination, &$data) {
print "file_put_contents: ";
##$r = $filesystem->file_put_contents($destination, $data);
print_r($r);
print "\n"; test_file($destination);

sleep(2);

//	function rename($local, $destination, $acl = self::ACL_FULL_CONTROL, $storage = null) {
print "rename: ";
$r = $filesystem->rename($copied, $destination);
print_r($r);
print "\n";  test_file($destination);

//	function move_uploaded_file($local, $destination, $acl = self::ACL_FULL_CONTROL, $storage = null) {
//	function execute2($cmd, $destination, $acl = self::ACL_FULL_CONTROL, $storage = null) {
//	function execute($cmd, $local, $destination, $acl = self::ACL_FULL_CONTROL, $storage = null) {
//	function _get_remote_as_tempfile($bucket, $filename) {
//	function shutdown_function() {
//	function register_shutdown_function() {
//	function file_exists($filename, $use_get = false) {
print "file_exists: ";
$r = $filesystem->file_exists($destination);
print_r($r);
print "\n";


//	function stat($filename, $use_get = false) {
print "stat: ";
$r = $filesystem->stat($destination);
print_r($r);
print "\n";

//	function clearstatcache($clear_realpath_cache = FALSE, $filename = FALSE) {
print "clearstatcache ";
$filesystem->clearstatcache(false,$destination);
print "\n";

//	function filemtime($filename, $use_get = false) {
print "filemtime: ";
$r = $filesystem->filemtime($destination, true);
print_r($r);
print "\n";

//	function fileatime($filename, $use_get = false) {
print "fileatime: ";
$r = $filesystem->fileatime($destination);
print_r($r);
print "\n";

//	function filectime($filename, $use_get = false) {
print "filectime: ";
$r = $filesystem->filectime($destination);
print_r($r);
print "\n";

//	function filesize($filename, $use_get = false) {
print "filesize: ";
$r = $filesystem->filesize($destination);
print_r($r);
print "\n";

//	function md5_file($filename, $use_get = false) {
print "md5_file: ";
$r = $filesystem->md5_file($destination);
print_r($r);
print "\n";

//	function is_dir($filename) {
print "is_dir: ";
$r = $filesystem->is_dir($dir);
print_r($r);
print "\n";

//	function mkdir($filename, $mode = 0777, $recursive = FALSE) {
print "mkdir: ";
$r = $filesystem->mkdir($dir);
print_r($r);
print "\n";

//	function rmdir($filename) {
print "rmdir: ";
$r = $filesystem->rmdir($dir);
print_r($r);
print "\n";

//	function getimagesize($filename) {
print "getimagesize: ";
$r = $filesystem->getimagesize($destination);
print preg_replace('/\s+/',' ',print_r($r,true));
print "\n";

//	function file_get_contents($filename) {
print "file_get_contents: ";
$r = $filesystem->file_get_contents($destination);
print strlen($r)." bytes";
print "\n";

//	function file($filename) {
print "file: ";
$r = $filesystem->file($destination);
print count($r)." lines";
print "\n";

//	function readfile($filename) {

//	function imagecreatefromjpeg($filename) {
print "imagecreatefromjpeg: ";
$img2 = $filesystem->imagecreatefromjpeg($destination);
print "x:".imagesx($img2).", y:".imagesy($img2);
print "\n";

//	function imagecreatefrompng($filename) {
$png = $_SERVER['DOCUMENT_ROOT']."/img/80x15.png";
print "imagecreatefrompng: ";
$img2 = $filesystem->imagecreatefrompng($png);
print "x:".imagesx($img2).", y:".imagesy($img2);
print "\n";

//	function imagecreatefromgd($filename) {
$gd = $_SERVER['DOCUMENT_ROOT']."/photos/00/07/000767_4a09ef97_40x40.gd";
print "imagecreatefromgd: ";
$img2 = $filesystem->imagecreatefromgd($gd);
print "x:".imagesx($img2).", y:".imagesy($img2);
print "\n";

//	function imagecreatefromgif($filename) {
$gif = $_SERVER['DOCUMENT_ROOT']."/img/crosshairs.gif";
print "imagecreatefromgif: ";
$img2 = $filesystem->imagecreatefromgif($gif);
print "x:".imagesx($img2).", y:".imagesy($img2);
print "\n";

//	function exif_read_data($filename, $sections = NULL, $arrays = FALSE, $thumbnail = FALSE ) {
print "exif_read_data: ";
$r = $filesystem->exif_read_data($destination);
print preg_replace('/\s+/',' ',print_r($r,true));
print "\n";

//	function read_exif_data($filename, $sections = NULL, $arrays = FALSE, $thumbnail = FALSE ) {
//	function imagepng(&$img, $filename = null, $quality = -1, $filter = -1, $function = 'imagepng') {
print "imagepng: ";
$r = $filesystem->imagepng($img,"$destination.png");
print "\n"; test_file("$destination.png");

//	function imagejpeg(&$img, $filename = null, $quality = 87) {
print "imagejpeg: ";
$r = $filesystem->imagejpeg($img,"$destination.jpg");
print "\n"; test_file("$destination.jpg");

//	function imagegd(&$img, $filename = null) {
print "imagegd: ";
$r = $filesystem->imagegd($img,"$destination.gd");
print "\n"; test_file("$destination.gd");

