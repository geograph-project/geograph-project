<?

//these are the arguments we expect
$param=array('explain'=>false,'tables'=>'','from'=>'');

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

	$db = GeographDatabaseConnection(false);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

############################################

print "# Note these are 'idealized' recreated SQL queries to built the the whole table\n";
print "#   the real script that creates these tables, typically use some scheme to create them piecemeal, and/or incrementally\n";
print "#   ... note that does not currently add any indexes to the table (the real views probably have indexes)\n\n";

print "#   Also note, some tables appear to use themselves, as a source, because in reality there they are only 'part' material.\n";
print "#   Where the a column definition uses \$ as part of definition, its in reality computed in PHP, not mysql\n\n";

$color = "\033[31m";
$white ="\033[0m";


print "$color# DO NOT JUST DROP THE ANY OF THESE TABLES AND BLINDLY EXPECT THESE QUERIES TO SUCCESSFULLY RECREATE THE TABLE$white\n";

if (!empty($param['from'])) {
	$where = "sql_from REGEXP ".$db->Quote("\b".$param['from']."\b");
	$where .= " AND description != 'obsolete'";
	$tables = $db->getAll("SELECT * FROM material_view WHERE $where ORDER BY table_name");
} elseif (!empty($param['tables'])) {
	$where = "table_name LIKE '".implode("%' OR table_name LIKE '",explode(',',$param['tables']))."%'";
	$tables = $db->getAll("SELECT * FROM material_view WHERE $where ORDER BY table_name");
} else {
	$tables = $db->getAll("SELECT * FROM material_view WHERE schedule != 'disabled' ORDER BY table_name");
}

foreach ($tables as $row) {
	//recreate from parsed
	$columns = $db->getAll("SELECT * FROM material_view_column WHERE table_name = '{$row['table_name']}' ORDER BY sort_order,column_id");
	$bits = array(); $dynamic=false;
	foreach ($columns as $col) {
		if ($col['definition'] === '0' || preg_match('/\$(\w+)/',$col['definition'])) { //these are just placeholders, the real table must have some PHP to set these!
			$bits[] = "{$color}{$col['definition']} AS `{$col['column_name']}`{$white}";
			$dynamic=true;
		} elseif ($col['definition'] != $col['column_name'])
			$bits[] = "{$col['definition']} AS `{$col['column_name']}`";
		else
			$bits[] = $col['column_name'];
	}
	$row['sql_select'] = implode(", ",$bits);
	if (strlen($row['sql_select']) > 80)
		$row['sql_select'] = implode(",\n\t",$bits);

	$row['sql_from'] = trim(preg_replace('/\s+/',' ',$row['sql_from']));
	$row['sql_from'] = preg_replace('/\b(INNER |LEFT |OUTER |STRAIGHT_)JOIN\b/i',"\n\t\${1}JOIN",$row['sql_from']);


	$sql  = " SELECT {$row['sql_select']}\n";
        $sql .= " FROM {$row['sql_from']}";
	if (!empty($row['sql_where']))
		$sql .= "\n WHERE {$row['sql_where']}";
	if (!empty($row['sql_group']))
		$sql .= "\n GROUP BY {$row['sql_group']}";
	if (!empty($row['sql_having']))
		$sql .= "\n HAVING {$row['sql_having']}";
	if (!empty($row['sql_order']))
		$sql .= "\n ORDER BY {$row['sql_order']}";
	if (!empty($row['sql_limit']))
		$sql .= "\n LIMIT {$row['sql_limit']}";

	if ($dynamic)
		print "{$color}--{$row['table_name']} can't be re-created with this query, as the real material view is created in PHP \n";
	elseif (preg_match('/\b'.preg_quote($row['table_name'],'/').'\b/',$row['sql_from']))
		print "{$color}--{$row['table_name']} can't be created as view, as it actully a real table\n";
	else
		print "{$color}CREATE TABLE `{$row['table_name']}` {$white}\n";

	print "$sql;\n\n";

	if ($param['explain'])
		dump_table("EXPLAIN $sql");

}


############################################


/*

create table material_view (
	table_id int unsigned not null auto_increment primary key,
	table_name varchar(64) not null,

	description varchar(1024) not null,
	`schedule` varchar(64) null null,

	sql_select text not null,
	sql_from text not null,
	sql_where varchar(1024) not null,
	sql_group varchar(1024) not null,
	sql_having varchar(1024) not null,
	sql_order varchar(1024) not null,
	sql_limit varchar(1024) not null,

	unique key(table_name)
);

create table material_view_column (
	column_id int unsigned not null auto_increment primary key,
        table_name varchar(64) not null,
        column_name varchar(128) not null,

	definition varchar(1024) not null,

	description varchar(1024) not null,
	grouped tinyint unsigned default null,
	indexed tinyint unsigned default null,

	sort_order tinyint unsigned not null,
        unique key(table_name,column_name)
);



*/

function dump_table($sql) {
	global $db;
	if (is_string($sql)) {
		$sql = str_replace('$wm',1,$sql);
		$sql = str_replace('$avg',3.2,$sql);
		$sql = $db->getAll($sql);
	}
	if (empty($sql))
		return;
	print implode("\t",array_keys($sql[0]))."\n";
	foreach ($sql as $row)
		print implode("\t",$row)."\n";
	print "\n";
}
