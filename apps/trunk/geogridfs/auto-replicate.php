<?

if (empty($mounts))
	include __DIR__."/database.inc.php";

if (!empty($argv[1]) && $argv[1] == 'full') {
	$debug = false;
} else {
	$debug = true;
}

function summarize($sql,$title='') {
	global $debug;
	if (!$debug)
		return;

	$result = mysql_query($sql); $count = mysql_num_rows($result);
	print "#$title\n";
	//print "$sql;\n";
	print "($count rows)\n";
	if ($count) {
		$c = 0;
		while($row = mysql_fetch_assoc($result)) {

		        $start = $row['shard']*10000;
		        $end = $start+9999;
			$sql2 = "SELECT filename,replicas,backups FROM file WHERE {$row['clause']} AND file_id BETWEEN $start AND $end AND replicas NOT LIKE '%{$row['target']}%' AND replica_count > 0 LIMIT 1";

			if ($c == 0)
				print "\t{$row['clause']}\n";
			$row['clause'] = "...";
			print implode("\t",$row)."\n";

			##print "$sql2;\n";
			$result2 = mysql_query($sql2);
			if ($row2 = mysql_fetch_assoc($result2)) {
				print "\t\t".implode("\t",$row2)."\n";
			}

			if ($c > 2)
				break;
			$c++;
		}
	}
	print "\n";
}


	//function write_replicate_task($target,$clause,$avoidover=true,$avoiddup=true,$order = 'NULL',$return_query=false) {

	//NOTE: write_replicate_task automatically adds "replica_count < replica_target"
	// and avoids creating the same task multiple times (unless told otherwise)
	// and of course avoids creating where the file already exists on the replica, or were there are no replicas (because replicator-task.py will do that anyway)

//new full that need copying to SSD
$sql = write_replicate_task("ssd|rand",		"class IN ('full.jpg','thumb.jpg')",
	true,true,'NULL',$debug);
summarize($sql,'new full that need copying to SSD');

$sql = write_replicate_task("hard|rand",	"class IN ('full.jpg','thumb.jpg')",
	true,true,'NULL',$debug);
summarize($sql,'new full that need copying to HD');

$sql = write_replicate_task("cakes1",      "class IN ('full.jpg','thumb.jpg') AND replica_count = 1 AND replicas NOT RLIKE 's[[:digit:]]' AND replicas NOT like '%cake%'",
	true,true,'NULL',$debug);
summarize($sql,'new full that need copying to Cake');

$sql = write_replicate_task("teas1",       "class IN ('full.jpg','thumb.jpg') AND replica_count = 1 AND replicas NOT RLIKE 's[[:digit:]]' AND replicas NOT like '%tea%'",
	true,true,'NULL',$debug);
summarize($sql,'new full that need copying to Tea');



$sql = write_replicate_task("ssd|rand",	"class = 'thumb.jpg' AND replica_count = 1", //give these a second chance
	true,true,'NULL',$debug);
summarize($sql,'new thumb to copy to SSD');




//copy originals to the replica with the most space
$sql = write_replicate_task("hard|empty",	"1",
	true,true,'NULL',$debug);
summarize($sql,'copy originals to the replica with the most space');


//ANY files not stored on jam
//$sql = write_replicate_task("jam",		"replicas NOT LIKE '%amz%'", //replicas NOT LIKE '%jam%' is /automatic/
//	true,true,'NULL',$debug);
//summarize($sql,'ANY files not stored on jam');


//use cream to mop up under-replicated files
$sql = write_replicate_task("cream",	"1",
	true,true,'NULL',$debug);
summarize($sql,'ANY files not stored on cream');


//send files to amazon
$sql = write_replicate_task("amz",       "backup_target>0", //need class filter to exclude backups
	true,true,'NULL',$debug);
summarize($sql, 'send files to amazon');


