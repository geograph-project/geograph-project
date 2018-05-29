<?

include "database.inc.php";
$debug = 1;

$clear = "drop table file_list_by_image";
$build = "create table file_list_by_image engine=myisam";
$insert = "insert into file_list_by_image";
$sql = "select class,substring_index(filename,'/',-1)+0 as gridimage_id,replicas,backups,backup_count,file_created
	from file where class in ('full.jpg','original.jpg')";

$min = 1;
$max = getOne("SELECT MAX(file_id) FROM file")+100;
$step = 50000;

print "Start = $min; End = $max\n";

############

for($start=$min;$start<$max;$start+=$step) {
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
select gridimage_id div 10000 as shard10k, class,count(*) as files,
	sum(backups like '%dwo%') as dwo,
	sum(backups like '%dsp%') as dsp,
	sum(backups like '%and%') as `and`,
	sum(backups like '%uka%') as `uka`,
	sum(backups like '%ovh%') as `ovh`,
	sum(backups like '%adc%') as `adc`,
	sum(replicas like '%amz%') as `amz`,
	avg(backup_count+(replicas like '%amz%')) as avg_backup, min(file_created) as first_date, now() as created
from file_list_by_image group by gridimage_id div 10000,class order by null
",true);


