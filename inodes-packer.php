<?

$dir = "/mnt/efs/data/geograph_visiondata020/";

if (!empty($argv[1]))
	$dir = $argv[1];

$maximum = 100000;

$number = intval(2400000/100000);
$number = 10;

if (is_dir($dir)) {
	chdir($dir);
	if (!file_exists('inodes.txt') || !empty($argv[2]))
		`du --inodes --count-links > inodes.txt`;

	$stat = array();
	$shards = array();
	$h = fopen('inodes.txt','r');
	while($h && !feof($h)) {
		$l = trim(fgets($h));
		if ($l) {
			$b = preg_split('/\s+/',$l,2);
			$n = $b[0];
			$f = $b[1];
			if ($f == '.')
				continue;

			######################
			//crc based consistent sharding (ignores maximum!)

			$value = basename($f);
			$crc = sprintf("%u", crc32($value));

			$shard = ($crc%$number);

if ($n <=100)
	print "WARNING: $value in shard$shard only has $n\n";


			@$shards[$shard][$f] = $n;
			continue;

			######################
			//random sharding within a set maximum!

			$k = array();
			if (count($shards)> 5) //garnetees first 5 go into seperate shards.
				foreach($shards as $key => $rows) {
					$total = array_sum($rows);
					if ($total < $maximum)
						$k[] = $key;
				}

			if (empty($k)) {
				//create new shard
				$shards[] = array($f=>$n);
			} else {
				$rand = array_rand($k);
				$shards[$rand][$f] = $n;
			}
		}
	}

//	print_r($shards);

	$total=0;
	foreach ($shards as $k => $rows) {
		printf("%2s => %7d [%3d]  %s\n",
			$k,
			$sum = array_sum($rows),
			$count = count($rows),
			implode(', ',array_slice(array_keys($rows),0,10))
		);
		$total += $sum;

		$uploaded = getDatasetValue($k,'src_size');

                if ($sum > 150000 && !$uploaded) {
                        $files = implode(' ',array_map('basename',array_keys($rows)));
			//$cmd = "du -shc $files";
			//print "$cmd\n";
			$cmd = "zip -qr shard$k.zip LICENCE *.csv $files";
			print "$cmd\n";

			$unique = array_unique(array_map('strtolower',array_keys($rows)));
			if (count($unique) != count($rows))
				print "WARNING, ther appear to be DUPLICATE folders\n";

			if (file_exists("{$dir}shard$k.zip")) {
				//./aws/dist/aws s3 mv --storage-class INTELLIGENT_TIERING --acl public-read 

				$size = filesize("{$dir}shard$k.zip");
				$time = filemtime("{$dir}shard$k.zip");

				$sql = "UPDATE dataset SET images = $sum, labels = $count, src_size = $size, src_time=FROM_UNIXTIME($time) WHERE query = '@tags _SEP_ / shard$k'";
				print "$sql\n\n";

				$folder = getDatasetValue($k,'folder');
				print "./aws/dist/aws s3 mv --storage-class INTELLIGENT_TIERING --acl public-read {$dir}shard$k.zip s3://data.geograph.org.uk/datasets/$folder.zip\n";
				exit;
			}
                }


	}
	printf("Total %7d\n", $total);
}

function getDatasetValue($k,$field = 'folder') {
	static $db;
	if (empty($db))
		$db = mysqli_connect($_SERVER['CONF_DB_CONNECT'], $_SERVER['CONF_DB_USER'], $_SERVER['CONF_DB_PWD'], $_SERVER['CONF_DB_DB']);

	$query = "SELECT $field FROM dataset WHERE query = '@tags _SEP_ / shard$k'";

	$result = mysqli_query($db, $query) or print("<br>Error getOne [[ $query ]] : ".mysqli_error($db));
        if (mysqli_num_rows($result)) {
                $row = mysqli_fetch_row($result);
		return $row[0];
	}
}


