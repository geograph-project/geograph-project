<?

include "database.inc.php";

$clear = "drop table file_list_by_image";
$build = "create table file_list_by_image engine=myisam";
$insert = "insert into file_list_by_image";
$sql = "select file_id,class,substring_index(filename,'/',-1)+0 as gridimage_id,size,replicas,replica_count,replica_target,backups,backup_count,backup_target,file_created
	from file where class in ('full.jpg','original.jpg')";

$max = getOne("SELECT MAX(file_id) FROM file");

$debug = 0;
$step = 50000;

for($start=1;$start<$max;$start+=$step) {
	$end = $start+$step-1;
	if ($start == 1) {
		queryExecute($clear,$debug);
		queryExecute("$build $sql AND file_id BETWEEN $start AND $end",$debug);
	} else {
		queryExecute("$insert $sql AND file_id BETWEEN $start AND $end",$debug);
	}
}



############


queryExecute("
insert into file_stat_by_image
select gridimage_id  div 10000 as shard10k, class,count(*) as files,
	sum(backups like '%dwo%') as dwo,sum(backups like '%dsp%') as dsp,sum(backups like '%and%') as `and`,sum(backups like '%uka%') as `uka`,sum(backups like '%ovh%') as `ovh`,
	avg(backup_count) as avg_backup, min(file_created) as first_date, now() as created
from file_list_by_image group by gridimage_id div 10000,class order by null
",true);



