<?php

if (extension_loaded('newrelic'))
	newrelic_ignore_transaction();

if (version_compare(PHP_VERSION, '5.6.0', '<')) {
        header("HTTP/1.0 503 Unavailable");
        die("php-version\n");
}

if(ini_get('default_charset') != "ISO-8859-1") {
        header("HTTP/1.0 503 Unavailable");
        die("charset\n");
}

$register_globals=strtolower(ini_get('register_globals'));
if ($register_globals=='on' || $register_globals=='1') {
	header("HTTP/1.0 503 Unavailable");
        die("register_globals\n");
}

$short_open_tag=strtolower(ini_get('short_open_tag'));
if ($short_open_tag!='on' && $short_open_tag!='1') {
	header("HTTP/1.0 503 Unavailable");
        die("short_open_tag\n");
}

$list = get_loaded_extensions();
foreach (explode(" ","redis ereg pcre zlib bz2 iconv mbstring session posix apache2handler gd exif json memcache mysql mysqli mhash apc curl") as $wanted) {
        if (!in_array($wanted,$list)) {
	        header("HTTP/1.0 503 Unavailable");
	        die("php-module:$wanted\n");
	}
}

/* s3fs is no longer mounted
if (!is_dir("photos/02/")) {
	header("HTTP/1.0 503 Unavailable");
	die("nofs-photos\n");
}
if (!is_dir("geophotos/02/")) {
	header("HTTP/1.0 503 Unavailable");
	die("nofs-geophotos\n");
}

if (!is_dir("../rastermaps/OS-250k/")) {
	header("HTTP/1.0 503 Unavailable");
	die("nofs-raster\n");
}
*/

if (!is_dir("../upload_tmp_dir/2")) {
        header("HTTP/1.0 503 Unavailable");
        die("nofs-upload\n");
}
if (!is_dir("/tmp") || !is_writable("/tmp") || disk_free_space("/tmp/") < 1024*1024*300) {
        header("HTTP/1.0 503 Unavailable");
        die("nofs-tmp\n");
}

if (!is_dir("templates/basic/compiled-mnt/") || !is_writable("templates/basic/compiled-mnt/")) {
	header("HTTP/1.0 503 Unavailable");
        die("nofs-basic-compiled\n");
}

if (isset($diequick))
	exit;

$_SERVER['HTTP_HOST'] = 'staging.geograph.org.uk';

//just so we do something...
require_once('geograph/global.inc.php');

?>.
