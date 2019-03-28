<?


chdir(__DIR__);

include __DIR__."/database.inc.php";

$debug = true;

if (!empty($argv[1]) && $argv[1] == 'full')
	$debug = false;

$verbose = true;
if (!empty($argv[1]) && $argv[1] == 'verbose')
	$verbose = true;
if (!empty($argv[2]) && $argv[2] == 'verbose')
	$verbose = true;

$interval = $debug?'1 minute':'1 month';

$mounts = getAll("SELECT * FROM mounts WHERE capacity > 90 AND mount RLIKE '[[:digit:]]$' AND last_balance < DATE_SUB(NOW(),INTERVAL $interval)");

if (empty($mounts))
	die($debug?"none\n":'');

$defer = getOne("SELECT DATE_ADD(NOW(),INTERVAL 7 DAY)");

foreach ($mounts as $row) {
	$source = $row['mount'];

	$want = 12; //%
	$bytes = $row['total'] * $want/100; //how much WANT available

	$todelete = $bytes-$row['available']; //remove what is avaialble already

	$todelete *= 1024; //convert to actaul bytes.


	$like = preg_replace('/\d/','_',$source);
	$target = getOne($sql = "SELECT mount FROM mounts WHERE mount != '$source' AND mount LIKE '$like' AND capacity < 80 ORDER BY capacity");
	if (empty($target)) {
		if ($debug)
			print "No destination for $source\n";
		continue; //no desktation!
	}

if ($debug || $verbose)
	print "$source => $target (".number_format($todelete,0)." bytes) => ";

	$stats = getAll("SELECT shard,class,SUM(count) as count,SUM(bytes) as bytes FROM file_stat
		WHERE replicas LIKE '%$source%' AND  replicas NOT LIKE '%$target%'
		GROUP BY shard,class
		ORDER BY bytes DESC");
	$done = $count = 0;
	foreach ($stats as $row) {
		write_task($source,$target, $row);
		$done += $row['bytes'];
		$count++;
		if ($done > $todelete)
			break;
	}

if ($debug || $verbose)
	print "(deleted ".number_format($done,0)." in $count tasks)\n";

	if (!$debug)
		queryExecute("UPDATE mounts SET last_balance = NOW(),updated=updated WHERE mount = '$source'");
}



function  write_task($source, $target, $row) {
	global $defer,$debug,$verbose;
 //todo
	$replica = array();
	$replica['shard'] = $row['shard'];
	$replica['files'] = $row['count'];
	$replica['bytes'] = $row['bytes'];
	$replica['clause'] = "class='{$row['class']}' AND replicas LIKE '%$source%' AND replicas NOT LIKE '%$target%'";
	$replica['target'] = $target;
	$replica['executed'] = 0;
	$sql = updates_to_insert('replica_task',$replica);
	if ($debug) {
		if ($verbose)
			print "$sql;\n";
	} else {
		queryExecute($sql);
		if ($verbose) {
			$rows = mysql_affected_rows();
			print "$source => $target ({$row['class']}) --> $rows\n";
		}
	}

	$drain = array();
	$drain['shard'] = $row['shard'];
	$drain['files'] = $row['count'];
	$drain['bytes'] = $row['bytes'];
	$drain['clause'] = "class='{$row['class']}' AND replicas LIKE '%$source%' AND replicas LIKE '%$target%'";
	$drain['target'] = $source;
	$drain['executed'] = 0;
	$drain['defer_until'] = $defer;
	$sql = updates_to_insert('drain_task',$drain);
	if ($debug) {
		if ($verbose)
			print "$sql;\n";
	} else {
		queryExecute($sql);
		if ($verbose) {
			$rows = mysql_affected_rows();
			print "$source => $target ({$row['class']}) --> $rows\n";
		}
	}
}


function updates_to_a(&$updates) {
        $a = array();
        foreach ($updates as $key => $value) {
                //NULL
                if (is_null($value)) {
                        $a[] = "`$key`=NULL";
                } else {
                        //converts uk dates to mysql format (mostly) - better than strtotime as it might not deal with uk dates
                        if (preg_match('/^(\d{2})[ \/\.-]{1}(\d{2})[ \/\.-]{1}(\d{4})$/',$value,$m)) {
                                $value = "{$m[3]}-{$m[2]}-{$m[1]}";
                        }
                        //numbers and functions, eg NOW()
                        //strlen, is because md5sum, like 229e1934277908240008373895822366 actully says 'is_numeric'!
                        if ((strlen($value) < 20 && is_numeric($value)) || preg_match('/^\w+\(\)$/',$value)) {
                                $a[] = "`$key`=$value";
                        } else {
                                $a[] = "`$key`='".mysql_real_escape_string($value)."'";
                        }
                }
        }
        return $a;
}

function updates_to_insert($table,$updates) {
        $a = updates_to_a($updates);
        return "INSERT INTO $table SET ".join(',',$a);
}

