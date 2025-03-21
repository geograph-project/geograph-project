<?

$config = "../system/docker/geograph/etc/nginx/sites-enabled/geograph";

if (!empty($argv[1])) {
	if ($argv[1] == '.')
		$argv[1] = tempnam('/tmp','ngi');

	if (basename($config) == basename($argv[1]))
		die("shouldnt write to the current file!\n");

	$out = fopen($argv[1], 'w');
} else {
	$out = STDOUT;
}

chdir(__DIR__); //because use glob("AS*.nginx")

########################################

if (!empty($argv[2])) {
	$today = date('Y-m-d');
	foreach(glob("AS*.nginx") as $file) {
		preg_match('/AS(\d+)/',$file,$m);
		$ident = $m[1];

		$date = date('Y-m-d', filemtime($file));
		if ($date == $today && filesize($file)) //check size, becayse 'touch' is a quick way to add a new file, and immidately fetch it!
			continue;

		$new = "$file.$date";
		$cmd = "mv $file $new";
		print "$cmd\n";
		if ($argv[2]>1)
			passthru($cmd);

		$cmd = "wget 'https://www.enjen.net/asn-blocklist/index.php?asn=$ident&type=nginx&api=1' --output-document=$file";
		print "$cmd\n";
		if ($argv[2]>1)
			passthru($cmd);

		$cmd = "diff -u $new $file";
		$cmd = "diff -u <(sort $new) <(sort $file) | colordiff"; //bash only!
		print "$cmd\n";
	}
	if ($argv[2]<3)
		exit;
}

########################################

/*
https://community.hetzner.com/tutorials/restrict-access-by-ip-or-password-in-nginx

https://nginx.org/en/docs/http/ngx_http_geo_module.html

geo $not_allowed {
    10.0.0.5  0;
    10.0.0.6  0;
    default   1;
}

server {
    listen 80;
    server_name example.com;
    location / {
        if ($not_allowed) {
            return 403 "You're not allowed to access. Your IP is $remote_addr";
        }
    }
}

*/

########################################
//first output a geo block, converting from old style deny lines to 'geo'

fwrite($out, 'geo $not_allowed {'."\n");
fwrite($out, "\tproxy 10.72.0.0/16;\n"); //needed to reroute

foreach(glob("AS*.nginx") as $file) {
	fwrite($out, "##$file\n");
	foreach(file($file) as $line) {
		if (preg_match('/^deny (.*);/',$line,$m)) {
			fwrite($out, "\t{$m[1]}\t1;\n");
//break;
		}
	}
}
fwrite($out, "\tdefault\t0;\n");
fwrite($out, '}'."\n");
fwrite($out, "# /geo\n");
fwrite($out, "\n");

########################################
// then output the old file

if (is_file($config)) {
	$inside = 0;
	foreach(file($config) as $line) {
		//if insdeo geo skip!
		if ($inside) {
			if ($line == "\n") //.. use hte new line, so dont just keep adding blank lines!
			//if ($line == "# /geo\n")
				$inside = 0;

		//dont output the OLD geo block!
		} elseif (preg_match('/^geo \$/', $line)) {
			$inside = 1;

	//here, we REMOVEING the old deny lines!
	//need to be careful to NOT remove 'deny all;' which is used by some other rules!
		} elseif (!preg_match('/^\s*deny ([a-f\d]+[:.]+.*);/',$line,$m)) {
			fwrite($out, $line);
		}
	}
}

########################################

if (!empty($argv[1])) {
	$cmd = "ls -l $config $argv[1]";
	print "$cmd\n";
		passthru($cmd);

	$cmd = "md5sum $config $argv[1]";
	print "$cmd\n";
		passthru($cmd);

	$cmd = "diff $config $argv[1] -u | colordiff | less -SR";
	print "$cmd\n";

	$cmd = "mv $argv[1] $config";
	print "$cmd\n";
}
