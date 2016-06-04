<?

/*

Automated file checker, just stores erorrs found in file_check, for later fixing. Offers no actual fixes.

*/

chdir(__DIR__);

include "database.inc.php";

if (empty($mounts))
	die("unable to read config\n");

$host = trim(`hostname`);

$tables = '';
$where = "0";
$limit = 1000;

////////////////////////////////
// some special one off rules (best called by a werapper
if (!empty($argv[1]) && ($argv[1] == 'jam' || $argv[1] == 'one' || $argv[1] == 'oneb' || $argv[1] == 'empty' || $argv[1] == 'empty.gd') ) {
	switch($argv[1]) {
		case 'jam': $where_stat = $where_file = "replicas = 'jam'"; break;
		case 'empty': $where_stat = "bytes = 0"; $where_file = "size = 0"; break;
		case 'empty.gd': $where_stat = "class = 'thumb.gd'"; $where_file = "size = 0"; break; //the two mismatch I know!
		case 'one': $where_stat = $where_file = "replica_count=1 AND replica_target>1"; break;
		case 'oneb': $where_stat = $where_file = "backup_count<2 AND backup_target>1"; break;
	}
	if (!empty($argv[2]) && is_numeric($argv[2])) {
		$row = array('shard' => intval($argv[2]));
	} else {
		$row = getRow("SELECT shard FROM file_stat WHERE $where_stat ORDER BY rand() LIMIT 1");
	}
	if (empty($row))
		die("no shards!\n");

	$start = $row['shard']*10000;
	$end = $start+9999;

	$where = "file_id BETWEEN $start AND $end AND $where_file";

////////////////////////////////
} elseif (!empty($argv[1]) && $argv[1] == 'id0') {

	$tables = "INNER JOIN file_list_by_image USING (file_id)";

	$where = "gridimage_id = 0";

////////////////////////////////
//looks for recent resubmissions, and checks them
} elseif (!empty($argv[1]) && $argv[1] == 'auto') {

	$result = liveQuery("SELECT gridimage_id,user_id FROM gridimage_pending order by suggested desc limit 1000");
	$list = array();
	while ($row = mysql_fetch_assoc($result)) {
		$hash = substr(md5($row['gridimage_id'].$row['user_id'].$CONF['photo_hashing_secret']), 0, 8);
		$path = "/geograph_live/public_html/".getGeographPath($row['gridimage_id'],$hash,'orig');

		$list[] = $path;
	}

	$where = "file.filename IN ('".implode("','",$list)."')";

////////////////////////////////
// just check all recent files of original class
} elseif (!empty($argv[1]) && $argv[1] == 'original') {

	$where = "file.file_id > (SELECT MAX(file_id) FROM file) - 100000 AND class = 'original.jpg'";

////////////////////////////////
} elseif (!empty($argv[1]) && $argv[1] == 'unreplicated') {

	$where = "file.file_id > (SELECT MAX(file_id) FROM file) - 100000 AND replica_count < replica_target AND replica_count > 0";

////////////////////////////////
} elseif (!empty($argv[1]) && $argv[1] == 'failing') {

	$tables = "INNER JOIN tmp_photo_without_backups USING (file_id)";
	$where = 1; //the inner join filters!

////////////////////////////////
} elseif (!empty($argv[1]) && $argv[1] == 'task') {

	$task = getRow("SELECT * FROM test_task WHERE `executed` = '0000-00-00 00:00:00' LIMIT 1");

	if (empty($task))
		die("no tasks\n");

	$where = $task['clause'];

	$start = $task['shard']*10000;
	$end = $start+9999;
	$where .= " AND file_id BETWEEN $start AND $end";

	//mark it as started
	queryExecute("UPDATE test_task SET `executed` = '2000-01-01' WHERE task_id = {$task['task_id']}");

	$limit = 10010;

////////////////////////////////
// use file_stat to get a cross-section sample.
} else {
	// this section will be run hourly by cron, so make it efficent!

	$tables = "INNER JOIN file_stat ON (file_stat.example = file.filename)";
	$where = 1; //the inner join filters!
}

////////////////////////////////

$files = getAll("
	SELECT file.filename,file.size,file.md5sum,file.replicas
	FROM file
		$tables
		LEFT JOIN file_check ON(file_check.example = file.filename)
	WHERE file_check.example IS NULL
	AND $where
	LIMIT $limit
");

if (empty($files)) {
	if ($argv[1] != 'cron') {
		die("no files\n");
	} else {
		exit;
	}
}

////////////////////////////////

foreach ($files as $row) {

	$replicas = empty($row['replicas'])?array():explode(",",$row['replicas']);
	$errors = array();

	foreach ($mounts as $replica => $mount) {
		if (in_array($replica,$replicas))
			continue; //will be checked anyway
		$filename = $mounts[$replica].$row['filename'];
                if (file_exists($filename)) {
			$errors[] = "file exists on $replica, but metadata doesnt know";
			$replicas[] = $replica;
		}
	}
	foreach ($replicas as $replica) {
		if (!file_exists($mounts[$replica]."/geograph_live")) {
			$errors[] = "$replica NOT MOUNTED";
			continue;
		}
		if (empty($mounts[$replica])) {
			$errors[] = "unknkown replica $replica\n";
		} else {
			$filename = $mounts[$replica].$row['filename'];
			if (file_exists($filename)) {
				$size = filesize($filename);

				if ($size != $row['size']) {
					$errors[] = "$filename size = $size, but expected {$row['size']}\n";
				} else {
					if ($replica == 'amz') {
						if (!class_exists('S3')) {
							require_once '/var/s3/S3.php';

							define('AUTOCONNECT',true);
							require_once '/var/s3/S3-config.php';
						}

						//todo, this should be updated to use 'buckets' config from config.py
						if (strpos($row['filename'],"/geograph_live/public_html/") === 0) {
							$s3name = preg_replace('/^\/geograph_live\/public_html\//','',$row['filename']); //bucket already has the slash!
							$amazon = $s3->getObjectInfo($bucketName,$s3name);
						} else {
							$s3name = preg_replace('/^\/geograph_live\//','',$filename); //bucket already has the slash!
							$amazon = $s3->getObjectInfo("attic.geograph.org.uk",$s3name);
						}

						$md5 = $amazon['hash'];

					} else
						$md5 = md5_file($filename);
					if ($md5 != $row['md5sum']) {
						$errors[] = "$filename md5 = $md5, but expected {$row['md5sum']}\n";
					}
				}
			} else {
				$errors[] = "$filename MISSING\n";
			}
		}
	}
	if (empty($argv[1]) || $argv[1] != 'cron') {
		if (!empty($errors)) {
			//print_r($row);
			print_r($errors);
		} else {
			print "{$row['filename']}\n";
		}
	}
	$sql = "INSERT INTO file_check SET host='$host', example='{$row['filename']}', errors = '".mysql_real_escape_string(implode("\n",$errors))."'";
	mysql_query($sql) or die(mysql_error());

}

////////////////////////////////

if (!empty($task)) {
	//mark it as done
	queryExecute("UPDATE test_task SET `executed` = NOW() WHERE task_id = {$task['task_id']}");
}


