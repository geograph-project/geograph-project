<?
//this checks this server is 'online'. If this instance isnt functiona,, this should return NON 200-OK to tell load balanacer not to serve from this instance!


// should be optimized to be effient, eg this could be getting called every 10 seconds!

##############################

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
$wanted_list = [
        "apc",
        "bz2",
        "curl",
        "exif",
        "gd",
        "hash",
        "iconv",
        "json",
        "mbstring",
        "memcache",
        "mysqli",
        "pcre",
        "posix",
        "session",
        "zlib",
];
foreach ($wanted_list as $wanted) {
        if (!in_array($wanted,$list)) {
                header("HTTP/1.0 503 Unavailable");
                die("php-module:$wanted\n");
        }
}

##############################

//basic example, if photos/ folder was served via a networt/filesystem, would check the link is working!
if (!file_exists("photos/test-photo.jpg")) {
	header("HTTP/1.0 503 Unavailable");
        die("file system offline\n");
}

if (!is_writable("photos/")) {
	header("HTTP/1.0 503 Unavailable");
        die("file system read-only\n");
}

if (!is_writable("templates/basic/compiled/")) {
	header("HTTP/1.0 503 Unavailable");
        die("smarty compiled not writable\n");
}


?>.
