<?

$dir = "/mnt/efs/data/geograph_visiondata0*";

if (!empty($argv[1]))
	$dir = rtrim($argv[1],'/');

//tood, if there is a 'inodes.txt' - use that!
$h = popen("ls -1d $dir/*/",'r');
while ($h && !feof($h)) {
        $count=0;
	$dir = trim(fgets($h));
	if ($dir && is_dir($dir)) {
	    if ($dh = opendir($dir)) {
	        while (($file = readdir($dh)) !== false) {
			if($file != "." && $file != ".."){
				$count++;
                                if ($count>20)
					break; //the inside loop
			}
	        }
	        closedir($dh);
	    }
            if ($count>20)
		continue; //the outer loop

	    //remember over 20, count wont be complete! We stopped counting!

  	    print "## $dir only has $count files\n";

		if (in_array('-d',$argv)) {
			$cmd = "rm -Rf $dir";
			print "$cmd\n";
			if (in_array('-e',$argv)) {
				passthru($cmd);
			}

		} elseif (in_array('-17',$argv)) {

	                $base = escapeshellarg(basename($dir));

			//this is harcoded, and would need figuring out how to make dynamic!
        	        $cmd = "php scripts/bulk-dump-vision-lobe.php --filename=geograph_visiondata017 --config=live --n=50 --source=curated1 --group=label --limit=100 --minimum=20 --query=$base --debug=0";
                	print "$cmd\n";

			if (in_array('-e',$argv)) {
				passthru($cmd);
			}

		} elseif (in_array('-18',$argv)) {

			$base = escapeshellarg('@groups "'.basename($dir).'"'); //todo, migh needs spaces adding!!!

			$cmd = "php scripts/bulk-dump-vision-lobe.php --filename=geograph_visiondata018 --config=live --n=50 --group=group_ids --limit=100 --minimum=20 --query=$base --debug=0";
			print "$cmd\n";

			if (in_array('-e',$argv))
				passthru($cmd);
		}
	}
}
