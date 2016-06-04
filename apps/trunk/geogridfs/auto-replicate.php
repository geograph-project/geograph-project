<?

chdir(__DIR__);

include __DIR__."/database.inc.php";




//function write_replicate_task($target,$clause,$avoidover=true,$avoiddup=true,$order = 'NULL',$return_query=false) {

	//NOTE: write_replicate_task automatically adds "replica_count < replica_target"
	// and avoids creating the same task multiple times (unless told otherwise)
	// and of course avoids creating where the file already exists on the replica, or were there are no replicas (because replicator-task.py will do that anyway)

//new full that need copying to SSD

print $sql =     write_replicate_task("ssd|rand",	"class = 'full.jpg'",
        true,true,'shard',true);
$result = mysql_query($sql); $count = mysql_num_rows($result);
print ";\n($count rows)\n\n";

print $sql =     write_replicate_task("hard|rand",	"class = 'full.jpg'",
        true,true,'shard',true);
$result = mysql_query($sql); $count = mysql_num_rows($result);
print ";\n($count rows)\n\n";


print $sql =     write_replicate_task("cakes1",      "class = 'full.jpg' AND replica_count = 1 AND replicas NOT RLIKE 's[[:digit:]]' AND replicas NOT like '%cake%'",
        true,true,'shard',true);
$result = mysql_query($sql); $count = mysql_num_rows($result);
print ";\n($count rows)\n\n";

print $sql =     write_replicate_task("teas1",       "class = 'full.jpg' AND replica_count = 1 AND replicas NOT RLIKE 's[[:digit:]]' AND replicas NOT like '%tea%'",
        true,true,'shard',true);
$result = mysql_query($sql); $count = mysql_num_rows($result);
print ";\n($count rows)\n\n";


//new thumb to copy to SSD
print $sql =     write_replicate_task("ssd|rand",	"class = 'thumb.jpg'",
        true,true,'shard',true);
$result = mysql_query($sql); $count = mysql_num_rows($result);
print ";\n($count rows)\n\n";

print $sql =     write_replicate_task("ssd|rand",	"class = 'thumb.jpg' AND replica_count = 1", //give these a second chance
        true,true,'shard',true);
$result = mysql_query($sql); $count = mysql_num_rows($result);
print ";\n($count rows)\n\n";



//copy originals to the replica with the most space
print $sql =     write_replicate_task("hard|empty",	"class = 'original.jpg'",
        true,true,'shard',true);
$result = mysql_query($sql); $count = mysql_num_rows($result);
print ";\n($count rows)\n\n";

//use cream to mop up under-replicated files
print $sql =     write_replicate_task("cream",	"replicas NOT LIKE '%amz%'",
        true,true,'shard',true);
$result = mysql_query($sql); $count = mysql_num_rows($result);
print ";\n($count rows)\n\n";

//send files to amazon
print $sql =     write_replicate_task("amz",       "replica_count < replica_target AND class in ('full.jpg','original.jpg')", //need class filter to exclude backups
        true,true,'shard',true);
$result = mysql_query($sql); $count = mysql_num_rows($result);
print ";\n($count rows)\n\n";


