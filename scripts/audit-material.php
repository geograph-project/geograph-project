<?

//these are the arguments we expect
$param=array('explain'=>false,'tables'=>'','tree'=>false,'squares'=>false);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

	$db = GeographDatabaseConnection(false);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

############################################

if (!empty($param['squares'])) {

$tables = $db->getCol("SELECT table_name FROM material_view_column WHERE column_name='squares' AND table_name like '%_stat' AND table_name NOT like '%old'");

$data = $db->getAll("SELECT * FROM material_view_column WHERE table_name IN ('".implode("','",$tables)."') ORDER BY table_name,sort_order");

$matrix = array();
$tables = array();
foreach ($data as $row) {
	if ($row['grouped'])
		continue; //skip the actual grouped columns, will be differetnt!

	@$matrix[$row['column_name']][$row['table_name']] = $row;
	@$tables[$row['table_name']]++;
}

printf("%40s ",'');
foreach ($tables as $table_name => $dummy)
	print "$table_name\t";
print "\n";

foreach ($matrix as $column_name => $row) {
	printf("%40s ",$column_name);
	foreach ($tables as $table_name => $dummy) {
		if (!empty($row[$table_name])) {
			print "X\t";
		} else {
			print ".\t";
		}
	}
	print "\n";
}

exit;
}

############################################


$color = "\033[31m";
$white ="\033[0m";

if (!empty($param['tables'])) {
	$where = "table_name LIKE '".implode("%' OR table_name LIKE '",explode(',',$param['tables']))."%'";
	$tables = $db->getAll("SELECT * FROM material_view WHERE $where ORDER BY table_name");
} else {
	$tables = $db->getAll("SELECT * FROM material_view WHERE description != 'obsolete' ORDER BY table_name");
}

$dates = $db->getAssoc("
select table_name,count(*) cnt,COLUMN_KEY,group_concat(column_name order by length(definition) limit 1) AS column_name
 from material_view_column d inner join information_schema.columns using (table_name,column_name)
 where table_schema = DATABASE() AND DATA_TYPE in ('timestamp','datetime')
 and (definition like 'now()' OR definition like 'max%' OR definition = 'upd_timestamp')
 group by table_name");

printf("%-25s %19s             %12s %s %10s %s\n",
	'Table Name', 'Update_time', 'Rows', 'Engine', 'Schedule', 'Timestamp (if found)');

foreach ($tables as $row) {

	$status = $db->getRow("SHOW TABLE STATUS LIKE '{$row['table_name']}'");
	$hours = '?';
        if (!empty($status['Update_time']) ) { // && strtotime($status['Update_time']) > (time() - 60*60*12) && $status['Comment'] != 're$
                        $seconds = time() - strtotime($status['Update_time']);
                        $hours = ceil($seconds/60/60);
	}

	if ($hours > 26)
		$status['Update_time'] = "$color{$status['Update_time']}$white";
	if ($row['schedule'] == 'every_week')
		$row['schedule'] = "$color{$row['schedule']}$white";
	$dateupdated = '';
	if (!empty($dates[$row['table_name']]) && ($date = $dates[$row['table_name']])) {
		if ($status['Rows'] < 500000 || ($date['cnt'] == 1 && !empty($date['COLUMN_KEY'])) ) {
			$dateupdated = $db->getOne("SELECT MAX({$date['column_name']}) FROM {$row['table_name']}")." ({$date['column_name']})";
		}
	}

	printf("%-34s %19s (%3s hours) %12s %s %10s %s\n",
		"$color{$row['table_name']}$white", 
		$status['Update_time'],
		$hours,
		number_format($status['Rows'],0), 
		$status['Engine'],
		$row['schedule'],
		$dateupdated
		);

}

###############################################

$sql = "SELECT i.table_name,i.column_name
 FROM information_schema.columns i
 inner join material_view m using (table_name)
 left join material_view_column c using (table_name,column_name)
 where table_schema = DATABASE() and c.column_name is null";

dump_table($sql,"\n\nundefined columns...");

###############################################

if (empty($param['tree']))
	exit;

$tree = array();
foreach ($db->getAll("SELECT table_name, sql_from FROM material_view WHERE description != 'obsolete'") as $row) {
	if (preg_match_all('/(^|join )(\w+)/',$row['sql_from'],$m)) {
		foreach ($m[2] as $source)
			@$tree[$source][] = $row['table_name'];
	}
}

$done = array(); //easily be loops!
output(array_keys($tree),1);

function output($keys,$indent) {
	global $tree,$done;
	foreach ($keys as $key) {
		if (isset($done)) continue;
		$done[$key]=1;
		print str_repeat(' ',$indent).$key."\n";
	}
}



###############################################

function dump_table($sql,$title = '') {
        global $db;
        if (is_string($sql)) {
                $sql = $db->getAll($sql);
        }
        if (empty($sql))
                return;
	print "$title\n";
        print implode("\t",array_keys($sql[0]))."\n";
        foreach ($sql as $row)
                print implode("\t",$row)."\n";
        print "\n";
}




