<?

chdir(__DIR__);

include __DIR__."/database.inc.php";


$size = 10000; //if =10000 then does one at a time, multiples does more
$check = false;

#this needs to be updated if you change the schema of the table!
$grouper = 'select file_id div 10000 as shard,class,replicas,replica_count,replica_target,backups,backup_count,backup_target,filename as example,count(*) as count,sum(size) as bytes,now() as updated,0 as jam2milk,0 as jam2cream from file where $where group by shard,class,replicas,replica_count,replica_target,backups,backup_count,backup_target order by null';

if ($argv[1] == 'full') {
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

} elseif ($argv[1] == 'jam2milk' || $argv[1] == 'jam2cream') {
	$check = true;
	$check_recent = false;
	$skip = false;

	$begin = getOne("SELECT shard FROM file_stat ORDER BY ".$argv[1]." DESC LIMIT 1")*10000;
	$final = $begin+10000-1;

	print "range($begin,$final,$size)\n";

} else {
        $check = false; #true/false check exist
        $check_recent = true;
        $skip = true; # set to false to UPDATE found shards

        $begin = 0;
        $final = $size-1;
}


$sleep = 1;



###############

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
	print "delete from file_stat where updated < '$cutoff'";
	queryExecute("delete from file_stat where updated < '$cutoff'");
}

    $make_columns = "sum(count) as files, sum(bytes) as total_bytes, example,updated ";
    $table = "file_stat";

    queryExecute("REPLACE INTO `stat` SELECT CONCAT('r:',replicas) AS id, replicas AS value, ".$make_columns.
                "FROM ".$table." GROUP BY replicas+0 ORDER BY NULL");

    queryExecute("REPLACE INTO `stat` SELECT CONCAT('t:',class,',',replica_target,replica_count) AS id, CONCAT(class,' ',replica_count,'/',replica_target) AS value, ".$make_columns.
                "FROM ".$table." GROUP BY class,replica_target,replica_count ORDER BY NULL");

    queryExecute("REPLACE INTO `stat` SELECT CONCAT('b:',backups) AS id, backups AS value, ".$make_columns.
                "FROM ".$table." WHERE backup_target > 0 GROUP BY backups+0 ORDER BY NULL");

    queryExecute("REPLACE INTO `stat` SELECT CONCAT('y:',class,',',backup_target,backup_count) AS id, CONCAT(class,' ',backup_count,'/',backup_target) AS value, ".$make_columns.
                "FROM ".$table." WHERE backup_target > 0 GROUP BY class,backup_target,backup_count ORDER BY NULL");

    queryExecute("REPLACE INTO `stat` SELECT CONCAT('c:',class) AS id, class AS value, ".$make_columns.
                "FROM ".$table." GROUP BY class ORDER BY NULL");

    queryExecute("REPLACE INTO `stat` SELECT CONCAT('s:',substring_index(filename,'_',-1)) AS id, substring_index(filename,'_',-1) AS value,
		count(*) AS files, sum(size) as total_bytes, filename as example, now() as updated
		FROM thumb_md5 GROUP BY substring_index(filename,'_',-1) ORDER BY NULL");


//new full that need copying to SSD
    queryExecute("INSERT INTO replica_task
	SELECT NULL,shard,SUM(`count`) AS files,SUM(`bytes`) AS bytes,'class = \'full.jpg\' AND replica_count < replica_target AND replicas NOT RLIKE \'s[[:digit:]]\'' AS `clause`,CONCAT(IF(RAND()>0.5,'tea','cake'),'s',IF(RAND()>0.5,'1','2')) AS target,NOW() as created,0 AS `executed`
	FROM file_stat
	WHERE class = 'full.jpg' AND replica_count < replica_target AND replicas NOT LIKE '%s1%' AND replicas NOT LIKE '%s2%' AND replica_count > 0
	AND shard NOT IN(select distinct shard from replica_task where clause = 'class = \'full.jpg\' AND replica_count < replica_target AND replicas NOT RLIKE \'s[[:digit:]]\'')
	GROUP BY shard ORDER BY NULL");


//new thumb to copy to SSD
    queryExecute("INSERT INTO replica_task
	SELECT NULL,shard,SUM(`count`) AS files,SUM(`bytes`) as bytes,'class = \'thumb.jpg\' AND replica_count < replica_target AND replicas NOT RLIKE \'s[[:digit:]]\'' AS `clause`,CONCAT(IF(RAND()>0.5,'tea','cake'),'s',IF(RAND()>0.5,'1','2')) AS target,NOW() as created,0 AS `executed`
	FROM file_stat
	WHERE class = 'thumb.jpg' AND replica_count < replica_target AND replicas NOT LIKE '%s1%' AND replicas NOT LIKE '%s2%' AND replica_count > 0
	AND shard NOT IN(select distinct shard from replica_task where clause = 'class = \'thumb.jpg\' AND replica_count < replica_target AND replicas NOT RLIKE \'s[[:digit:]]\'')
	GROUP BY shard ORDER BY NULL");


//files not stored on jam
    queryExecute("INSERT INTO replica_task
	SELECT NULL,shard,SUM(`count`) AS files,SUM(`bytes`) as bytes,'replicas NOT LIKE \'%jam%\' AND replica_count < replica_target' AS `clause`,'jam' AS target,NOW() as created,0 AS `executed`
	FROM file_stat
	WHERE replicas NOT LIKE '%jam%' AND replicas NOT LIKE '%jam%' AND replica_count < replica_target AND replica_count > 0
	AND shard NOT IN(select distinct shard from replica_task where clause = 'replicas NOT LIKE \'%jam%\' AND replica_count < replica_target')
	GROUP BY shard ORDER BY NULL");


//copy originals to the replica with the most space
    $target = getOne("select mount,(available*1024)-coalesce(sum(bytes),0) as av from mounts left join replica_task on (target=mount and executed < 1) where mount like '%h_' group by mount order by av desc limit 1");
    queryExecute("INSERT INTO replica_task
	SELECT NULL,shard,SUM(`count`) AS files,SUM(`bytes`) as bytes,'class = \'original.jpg\' AND replica_count < replica_target' AS `clause`,'$target' AS target,NOW() as created,0 AS `executed`
	FROM file_stat
	WHERE class = 'original.jpg' AND replica_count < replica_target AND replica_count > 0 AND replicas NOT LIKE '%$target%'
	GROUP BY shard ORDER BY RAND()");
