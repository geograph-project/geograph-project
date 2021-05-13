<?

if (empty($argv[1])) {
	die("specify input\n");
} elseif ($argv[1] == '-') {
        $h = fopen('php://stdin', 'r');
} else {
        $h = fopen($argv[1], 'r');
}



//"PHP message: PHP Notice:  Only variables should be assigned by reference in /var/www/geograph/libs/geograph/user.class.php on line 84

$str = array();
$src = array();

while ($h && !feof($h)) {
	$input = fgets($h);
	if (preg_match_all('/PHP message: (.+?) in (.+?) on line (\d+)/',$input,$m)) {
	        foreach ($m[2] as $key => $file) {
        	        $uni = $file." ".$m[3][$key];
	                $str[$uni] = $m[1][$key];
			if (empty($src[$uni]) && preg_match('/request: "(.+?)"/',$input,$mm))
				$src[$uni] = $mm[1];
	        }
	}
}

$color = "\033[31m";
$white ="\033[0m";


foreach ($str as $uni => $message) {
        print "$color$message\n";
	@$request = $src[$uni];

	$uni = str_replace("/var/www/geograph/",' ',$uni);

        print "#     $uni$white  {$request}\n";

        //print `src $uni`;
	list($dummy,$file,$line) = explode(" ",$uni);
	passthru("cat -n $file | grep -C5 -P '^\s*{$line}\b' --color=always");

        print "\n\n";
}

