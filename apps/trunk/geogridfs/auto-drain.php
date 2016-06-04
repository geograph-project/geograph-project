<?

include "database.inc.php";

/*
function write_replicate_task($target,$clause,$avoidover=true,$avoiddup=true,$order = 'NULL',$return_query=false) {

function write_drain_task($target,$clause,$where=NULL,$avoiddup=true,$order = 'NULL',$return_query=false) {
*/

print "Drain from Tea-SSD where on another tea, and Cake-SSD\n";
print $sql = write_drain_task('teas_',"replicas LIKE '%tea%tea%' AND replicas LIKE '%cakes%' AND replica_count > replica_target",
	null,true,'shard',true);
$result = mysql_query($sql); $count = mysql_num_rows($result);
print ";\n($count rows)\n";

print "Drain from Cake-SSD where on another Cake, and Tea-SSD\n";
print $sql = write_drain_task('cakes_',"replicas LIKE '%cake%cake%' AND replicas LIKE '%teas%' AND replica_count > replica_target",
	null,true,'shard',true);
$result = mysql_query($sql); $count = mysql_num_rows($result);
print ";\n($count rows)\n";

print "Drain from Any-SSD where backup and over-replicated\n";
print $sql = write_drain_task('%s_',"class = 'backup' AND replica_count > replica_target",
        null,true,'shard',true);
$result = mysql_query($sql); $count = mysql_num_rows($result);
print ";\n($count rows)\n";

print "Drain from Any-HD where backup and over-replicated\n";
print $sql = write_drain_task('%h_',"class = 'backup' AND replica_count > replica_target",
        null,true,'shard',true);
$result = mysql_query($sql); $count = mysql_num_rows($result);
print ";\n($count rows)\n";

print "Drain from tiles already backed up to amazon\n";
print $sql = write_drain_task('%h_',"class = 'tile.tif' AND replicas LIKE '%amz%'",
        null,true,'shard',true);
$result = mysql_query($sql); $count = mysql_num_rows($result);
print ";\n($count rows)\n";


print "Drain well backed up originals to amazon\n";
print $sql = write_drain_task('%h_',"class = 'original.jpg' AND replicas LIKE '%tea%cake%amz%' AND replica_count > replica_target",
        null,true,'shard',true);
$result = mysql_query($sql); $count = mysql_num_rows($result);
print ";\n($count rows)\n";



