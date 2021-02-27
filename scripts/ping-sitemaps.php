<?

##ping ALL recently updated sitemaps!



############################################

//these are the arguments we expect
$param=array();

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);

//insert a FAKE log (just so we can plot on a graph ;)
$db->Execute("INSERT INTO event_log SET
        event_id = 0,
        logtime = NOW(),
        verbosity = 'trace',
        log = 'running event_handlers/every_day/".basename($argv[0])."',
        pid = 33");

############################################



$files = "{$param['dir']}/public_html/sitemap/root/*.xml";

$h = popen("find $files -mtime -1 -type f",'r');
while ($h && !feof($h)) {
	$file = trim(fgets($h));
	if(empty($file))
		continue;

	//get the domain from the file, so it matches the right version (http/https, and .org.uk/.ie etc)
	$line = `grep -P '<loc>https?://www.\w+[\w.]*\w/' $file -o -m 1`;
	$slug = str_replace('<loc>','',$line);

        if (empty($slug))
                continue;

	$base = basename($file);

#########################

	$url = "https://www.google.com/webmasters/tools/ping?sitemap=".urlencode($slug.$base);

	if (empty($argv[1])) {
		print "$url\n";
	} else {
		file_get_contents($url);
		sleep(4);
	}

##########################

	if (strpos($base,'google') === false) {
		$url = "http://www.bing.com/ping?sitemap=".urlencode($slug.$base);

		if (empty($argv[1])) {
			print "$url\n";
		} else {
			file_get_contents($url);
			sleep(4);
		}
	}
}
