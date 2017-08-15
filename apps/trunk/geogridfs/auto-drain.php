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
			$sql2 = "SELECT filename,replicas,backups FROM file WHERE {$row['clause']} AND file_id BETWEEN $start AND $end AND replicas LIKE '%{$row['target']}%' AND replica_count > 1 LIMIT 1";

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

/*
function write_replicate_task($target,$clause,$avoidover=true,$avoiddup=true,$order = 'NULL',$return_query=false) {

function write_drain_task($target,$clause,$where=NULL,$avoiddup=true,$order = 'NULL',$return_query=false) {
*/

print "Drain from Any-SSD where backup and over-replicated\n";
$sql = write_drain_task('%s_',"class = 'backup' AND replica_count > replica_target",
        null,true,'shard',$debug);
summarize($sql);

print "Drain from Any-HD where backup and over-replicated\n";
$sql = write_drain_task('%h_',"class = 'backup' AND replica_count > replica_target",
        null,true,'shard',$debug);
summarize($sql);

###############################

print "Drain from Any-HD where on multiple and over-replicated\n";
$sql = write_drain_task('%h_',"replicas RLIKE 'h[[:digit:]].*h[[:digit:]]' AND replica_count > replica_target",
        null,true,'shard',$debug);
summarize($sql);

print "Drain from Any-SSD where on multiple and over-replicated\n";
$sql = write_drain_task('%s_',"replicas RLIKE 's[[:digit:]].*s[[:digit:]]' AND replica_count > replica_target",
        null,true,'shard',$debug);
summarize($sql);

################################

print "Drain from Any-HD where on a SSD and over-replicated\n";
$sql = write_drain_task('%h_',"class IN ('full.jpg','thumb.jpg','thumb.gd') AND replicas RLIKE 's[[:digit:]]' AND replicas RLIKE 'h[[:digit:]]' AND replica_count > replica_target",
        null,true,'shard',$debug);
summarize($sql);

print "Drain from Any-SSD where on a HD and over-replicated\n";
$sql = write_drain_task('%s_',"class NOT IN ('full.jpg','thumb.jpg','thumb.gd') AND replicas RLIKE 's[[:digit:]]' AND replicas RLIKE 'h[[:digit:]]' AND replica_count > replica_target",
        null,true,'shard',$debug);
summarize($sql);

#################################

print "Drain from Any-HD where tiles already backed up to amazon\n";
$sql = write_drain_task('%h_',"class = 'tile.tif' AND replicas LIKE '%amz%' AND replica_count > replica_target",
        null,true,'shard',$debug);
summarize($sql);

#################################

print "Drain well backed up originals to amazon\n";
$sql = write_drain_task('%h_',"class = 'original.jpg' AND replicas LIKE '%tea%cake%amz%' AND replica_count > replica_target",
        null,true,'shard',$debug);
summarize($sql);

#################################

