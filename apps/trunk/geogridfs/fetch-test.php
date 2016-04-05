<?

chdir(__DIR__);

include "database.inc.php";

if (empty($mounts))
	die("unable to read config\n");

$rows = getAll("DESCRIBE file");

foreach ($rows as $row) {
	if ($row['Field'] == 'replicas') {
		$replicas = explode(",",preg_replace('/[^\w,]+/','',$row['Type']));
		array_shift($replicas); //remove 'set' from the start.
		break;
	}
}

$classes = array('thumb.jpg','thumb.jpg','full.jpg','original.jpg','tile.png');
shuffle($classes);
$class= $classes[0];

print "#$class\n";

$host = trim(`hostname`);
foreach ($replicas as $replica) {
	if (!isset($mounts[$replica]) || !file_exists($mounts[$replica]."/geograph_live"))
		continue;

	$sql = "SELECT example FROM file_stat WHERE replicas LIKE '%$replica%' AND class='$class' ORDER BY RAND() LIMIT 100";
	$rows = getAll($sql);

	$count = count($rows);
	if ($count > 1) {
		//print "$replica => ".count($rows)."\n";
		$errors = 0;
		$start = microtime(true);
		foreach ($rows as $row) {
			$str = file_get_contents($mounts[$replica].$row['example']);
			if (empty($str))
				$errors++;
		}
		$end = microtime(true);
		$time = $end-$start;
		printf("%12s : %3.3f   [%d]\n",$replica,$time,$count);

		$sql = "INSERT INTO speed_test SET host='$host', replica='$replica', files=$count, speed=$time, errors = $errors, class='$class'";
		mysql_query($sql) or die(mysql_error());
	} else {
		print "Not enough files  on $replica\n";
	}
}
