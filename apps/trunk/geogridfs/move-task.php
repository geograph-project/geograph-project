<?


chdir(__DIR__);

include __DIR__."/database.inc.php";

$_GET['debug'] = TRUE;

$_GET['source'] = 'teah1'; //where we want to drain FROM
$_GET['clause'] = "class = 'original.jpg' AND replicas LIKE '%{$_GET['source']}%'";
$_GET['order'] = "RAND()";
$_GET['limit'] = 10;

$_GET['target'] = 'teah2'; //where want to replicate them TO
$_GET['target'] = getOne("select mount,(available*1024)-coalesce(sum(bytes),0) as av from mounts left join replica_task on (target=mount and executed < 1)
				where mount like '%h_' AND mount != '{$_GET['source']}' group by mount order by av desc limit 1");

//COULD kinda use 'hard|empty', but do it outself, to make sure target!=source :) And so we KNOW the target for drain task too.

###################################
# CREATE REPLICATION TASKS

print "#Copying {$_GET['source']} > {$_GET['target']}\n";

write_replicate_task($_GET['target'],$_GET['clause'],FALSE,TRUE,"{$_GET['order']} LIMIT {$_GET['limit']}");

###################################
# FIND shards OF CREATED TASKS

$first = getOne("SELECT LAST_INSERT_ID()");
print "first = $first\n";

$ids = getCol("SELECT DISTINCT shard FROM replica_task WHERE task_id >= $first");

if (empty($ids))
	exit;

##############################################

# todo - ideall should add something to delay tuntil the replica task HAS run, eg add SET run_after = DATE_ADD(NOW(), interval 24 hour) or similar

if (true) {
	$tasks = getOne("SELECT COUNT(*) FROM replica_task WHERE task_id >= $first");
	$done = getOne("SELECT COUNT(*) FROM replica_task WHERE task_id >= $first AND shard IN (".implode(",",$ids).") AND executed > '1'");
	while ($done < $tasks) {
		print "$done < $tasks (".date('r').")\n";
		sleep(60);
		$done = getOne("SELECT COUNT(*) FROM replica_task WHERE task_id >= $first AND shard IN (".implode(",",$ids).") AND executed > '1'");
	}
}

##############################################
# CREATE drain TASKS
# also note that files/bytes, will be inaccurate, as file_stat is not updated yet, to take into account the new replications. But providing some files already on the target for the specific shard, it will create the drain rule at least. 

print "#Remove copied files from {$_GET['source']}\n";

write_drain_task($_GET['source'], $_GET['clause']." AND replicas LIKE '%{$_GET['target']}%'", //the caluse for the rule
				  $_GET['clause']." AND shard IN (".implode(",",$ids).")", //the actual where used to create rules (cant use target as files ARENT replicated yet... - at least in file_stat)
				TRUE,$_GET['order']);

##############################################
