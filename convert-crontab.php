<?

$user = "geograph";
$input = "sudo -u $user crontab -l";

$output = "system/docker/geograph/etc/crontab-geograph-cron";

#########################################################
$done = array();

$h = fopen($output,'r');
while ($h && !feof($h)) {
        $line = trim(fgets($h));
        if (empty($line) || preg_match('/^\#/',$line) || preg_match('/^MAILTO/',$line))
                continue;

        $bits = preg_split('/\s+/',$line,6);
	$cmd= $bits[5];

	$done[$cmd]=1;

	$parts = preg_split('/\s+/',$cmd);
	$php = array_shift($parts);
	if ($php == '/usr/bin/php')
		$php = array_shift($parts);

	$new = str_replace('/var/www/geograph/','',$php);

	if (!file_exists($new)) {
		print "ERROR: $new MISSING\n";
	}
}
fclose($h);

print_r($done);

#########################################################

$h = popen($input,'r');
while ($h && !feof($h)) {
	$line = trim(fgets($h));
        if (empty($line) || preg_match('/^\#/',$line) || preg_match('/^MAILTO/',$line))
		continue;

	if (strpos($line,'run-archiver.php'))
		continue;

	//0	  1       2 3 4 5-
	//13      8-23    * * * /usr/bin/php /var/www/geograph_live/scripts/fire-event.php --event=every_hour --priority=75

	$bits = preg_split('/\s+/',$line,6);
	$cmd= $bits[5];

	$cmd2 = preg_replace('/www\/geograph(\w+)\//','www/geograph/',$cmd);

	if (!empty($done[$cmd2])) {
		print "#Done $cmd2\n";
		continue;
	}

	print "\nLINE: '$cmd2'\n";

	#############################

	$parts = preg_split('/\s+/',$cmd);
	$php = array_shift($parts);
	if ($php == '/usr/bin/php')
		$php = array_shift($parts);

	$new = preg_replace('/www\/geograph(\w+)\//','www/geograph/',$php);
	$new = str_replace('/var/www/geograph/','',$new);

	#############################

	if (file_exists($new)) {
		$command = "diff -u $new $php";
		if (strlen(`$command`) > 2) {
			$command .= " | colordiff";
			print "$command\n";
			passthru($command);
		}
	} else {
		$command = "cp /go/svn/$new $new";
		print "$command\n";
	        if (trim(readline('Run command?')) == 'y') {
        	        passthru($command);
	        }
	}

	#############################

	$command = "ack mysql_ $new";
	if (strlen(`$command`) > 2) {
		print "$command\n";
		passthru($command);
	}

	#############################

	$line = preg_replace('/www\/geograph(\w+)\//','www/geograph/',$line);
	$command = "echo ".escapeshellarg($line)." >> $output";

	print "$command\n";
	if (trim(readline('Run command?')) == 'y') {
		passthru($command);
	}
}

#########################################################
