<?

$dir = "/mnt/efs/data/geograph_visiondata028/";

if (!empty($argv[1]))
	$dir = $argv[1];

if (is_dir($dir)) {
	chdir($dir);
	if (!file_exists('inodes.txt') || !empty($argv[2])) {
		if (file_exists('files.txt')) {
			# /mnt/efs/data/geograph_visiondata020/gatepoststone/6644661.jpg
			#1  2   3   4     5                     6
			`cut -d/ -f 6 files.txt | sort | uniq -c > inodes.txt`;
		} else {
			`du --inodes --count-links > inodes.txt`;
		}
	}

	$stat = array();
	$h = fopen('inodes.txt','r');
	while($h && !feof($h)) {
		$l = trim(fgets($h));
		if ($l) {
			$b = preg_split('/\s+/',$l,2);
			$n = $b[0];
			$k = floor(sqrt($n));
			if (isset($stat[$k])) {
				$stat[$k][0]++;
				if ($n < $stat[$k][1]) $stat[$k][1] = $n;
				if ($n > $stat[$k][2]) $stat[$k][2] = $n;
			} else {
				$stat[$k] = array(1,$n,$n,$b[1]);
			}
		}
	}


	usort($stat, 'cmp1'); //sort by miniimum

	foreach ($stat as $k => $row) {
		printf("%5d..%5d\t%7d %10s\t%s\n", $row[1],$row[2], $row[0], str_repeat('*',sqrt($row[0])), $row[3]);
	}
}


function cmp1($a, $b) {
    if ($a[1] == $b[1]) {
        return 0;
    }
    return ($a[1] < $b[1]) ? -1 : 1;
}
