<?

chdir(__DIR__);

include "./database.inc.php";


$path = "/geograph_live/public_html/geophotos/04";
$lines = 10;
$all = false;

$self = $mounts['self']; //really mounts is a concat of all options.

foreach ($argv as $v) {
	if (preg_match('/-d(\d+)/',$v,$m)) {
		$lines = $m[1]+0;
	} elseif (preg_match('/-a/',$v,$m)) {
                $all = true;
        }
}



foreach($mounts as $replica => $mount) {
	if ((!$all && strpos($replica,$self) !== 0) || !is_dir("$mount$path") )
		continue;

	$list = explode("\n",trim(`ls -1t $mount$path | head -n$lines`));
	foreach ($list as $folder) {
		if ($folder) {
			$cmd = "python replicator.py -a walk -p $path/$folder -r $replica";
			print "$cmd\n";
		}
	}
}

