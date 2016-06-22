<?

chdir(__DIR__);

include __DIR__."/database.inc.php";

if (empty($argv[1])) {
        die("Please specify mode\n");
}

$size = 10000; //if =10000 then does one at a time, multiples does more
$check = false;

#this needs to be updated if you change the schema of the table!
$grouper = 'select file_id div 10000 as shard,class,replicas,replica_count,replica_target,backups,backup_count,backup_target,filename as example,count(*) as count,sum(size) as bytes,now() as updated, file_created'
	.' from file where $where group by shard,class,replicas,replica_count,replica_target,backups,backup_count,backup_target order by null';

if ($argv[1] == 'full') {

	//just a convenient place to put this. Auto degrade these. so they cna be drained at some point. Run here, so BEFORE file_stat is updated.
	//queryExecute("create temporary table latest_backup select folder,folder_id,max(file_id) as last_file_id from folder inner join file using (folder_id) where folder like '/geograph_live/public_html/backups/by-table/%' group by folder_id having count(*) > 1");
	queryExecute("create temporary table latest_backup select folder.folder_id,max(file_id) as last_file_id from folder STRAIGHT_JOIN  file on (folder.folder_id = file.folder_id) where folder like '/geograph_live/public_html/backups/by-table/%' AND replica_count > 0 group by folder_id having count(*) > 1");
	queryExecute("update file inner join latest_backup on (file.folder_id = latest_backup.folder_id and file_id != last_file_id) set replica_target = 1");


	$cutoff = getOne("SELECT NOW()");
	sleep(2);

	$size = $size*10;

	$begin = 0;
	$final = getOne("SELECT MAX(file_id) FROM file");

} elseif ($argv[1] == 'continue') {

	$size = $size*10;

	$begin = getOne("SELECT MAX(shard) FROM file_stat")*10000;
	$final = getOne("SELECT MAX(file_id) FROM file");

} elseif ($argv[1] == 'missing') {
	$check = true;
        $check_recent = false;
        $skip = true;

        $begin = 0;
        $final = getOne("SELECT MAX(file_id) FROM file");

} elseif ($argv[1] == 'update') {
	$check = true;
	$check_recent = true;
	$skip = false; #false=update them!

        $begin = 0;
        $final = getOne("SELECT MAX(file_id) FROM file");

} else {
        $check = false; #true/false check exist
        $check_recent = true;
        $skip = true; # set to false to UPDATE found shards

        $begin = 0;
        $final = $size-1;
}

$sleep = 1;


#####################################
# Main Update Loop

for($start = $begin; $start < $final; $start+=$size) {
##foreach(range($begin,$final,$size) as $start) {

	$end = $start+$size-1;
	$where = "file_id BETWEEN $start AND $end";
	$shard = intval($start/10000);

	if ($check && getOne("SELECT replica_count FROM file_stat WHERE shard = $shard".($check_recent?" AND updated > date_sub(NOW(),INTERVAL 7 DAY)":"")." LIMIT 1")) {
		if ($skip) {
			continue;
		} else {
			queryExecute("DELETE FROM file_stat WHERE shard = $shard");
		}
	}

	$sql = "REPLACE INTO file_stat ".str_replace('$where',$where,$grouper);

	print "$where ... ";

	queryExecute($sql);
	print "# ".mysql_affected_rows()."\n";

	if ($sleep)
		sleep($sleep);

}

if ($argv[1] == 'full') {
	print "delete from file_stat where updated < '$cutoff'\n";
	queryExecute("delete from file_stat where updated < '$cutoff'");
}


####################################
# Update Stat table

    $make_columns = "sum(count) as files, sum(bytes) as bytes, example,updated ";
    $table = "file_stat";

    queryExecute("REPLACE INTO `stat` SELECT CONCAT('r:',replicas,'-',DATE(NOW())) AS id, replicas AS value, ".$make_columns.
                "FROM ".$table." GROUP BY replicas+0 ORDER BY NULL");

    queryExecute("REPLACE INTO `stat` SELECT CONCAT('t:',class,',',replica_target,replica_count,'-',DATE(NOW())) AS id, CONCAT(class,' ',replica_count,'/',replica_target) AS value, ".$make_columns.
                "FROM ".$table." GROUP BY class,replica_target,replica_count ORDER BY NULL");

    queryExecute("REPLACE INTO `stat` SELECT CONCAT('b:',backups,'-',DATE(NOW())) AS id, backups AS value, ".$make_columns.
                "FROM ".$table." WHERE backup_target > 0 AND replica_count > 0 GROUP BY backups+0 ORDER BY NULL");

    queryExecute("REPLACE INTO `stat` SELECT CONCAT('y:',class,',',backup_target,backup_count,'-',DATE(NOW())) AS id, CONCAT(class,' ',backup_count,'/',backup_target) AS value, ".$make_columns.
                "FROM ".$table." WHERE backup_target > 0 AND replica_count > 0 GROUP BY class,backup_target,backup_count ORDER BY NULL");

    queryExecute("REPLACE INTO `stat` SELECT CONCAT('c:',class,'-',DATE(NOW())) AS id, class AS value, ".$make_columns.
                "FROM ".$table." WHERE replica_count > 0 GROUP BY class ORDER BY NULL");

    queryExecute("REPLACE INTO `stat` SELECT CONCAT('s:',substring_index(filename,'_',-1),'-',DATE(NOW())) AS id, substring_index(filename,'_',-1) AS value,
		count(*) AS files, sum(size) as bytes, filename as example, now() as updated
		FROM thumb_md5 GROUP BY substring_index(filename,'_',-1) ORDER BY NULL");


###################################
# Write Tasks

if ($argv[1] == 'full') {


        # call these here, rather than a seperate cronjob, so the functions are only called AFTER file_stat has been updated!

        include __DIR__."/auto-replicate.php";

        //include __DIR__."/auto-drain.php";

}

