<?

//these are the arguments we expect
$param=array('execute'=>0,'purge'=>0,'schedule'=>'', 'scan'=>0, 'nl'=>0);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

if ($param['execute']) {
	$db = GeographDatabaseConnection(false);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
}

############################################

if ($param['scan']) {
	$db = GeographDatabaseConnection(false);

	chdir(__DIR__);

print_r(glob($param['scan']));
	foreach (glob($param['scan'].'*') as $filename) {
		if (is_file($filename)) {
			$content = file_get_contents($filename);
			if (preg_match('/create table `?(\w+)`?/i', $content,$m)) {
				$table = trim(preg_replace('/_tmp$/','',$m[1]));
				if (!$db->getOne("SELECT COUNT(*) FROM material_view WHERE table_name = '$table'")) {

					$cmd = "cat {$filename} | php {$argv[0]} --config={$param['config']} ";

					$b = basename($filename);
					$full = `find ../libs/event_handlers/ -name {$b}`;

					if (preg_match("/(\w+)\/$b/",$full,$m)) {
						$cmd .= " --schedule=$m[1]";
					}

					print "$cmd ##$table\n";
				}
			} else {
				print "#$filename unable to idenify table\n";
			}
		} else {
			print "#$filename not a file\n";
		}
	}
	exit;
}


############################################

$sql = '';
$h = fopen('php://stdin','r');
while (!feof($h))
	$sql .= fgets($h);

if (empty($sql))
	$sql = "CREATE TABLE user_quadsquare_tmp
                                (INDEX (user_id,`gridsquare_id`))
                                ENGINE=MyISAM
                                select gridsquare_id,user_id,count(distinct nateastings DIV 500, natnorthings DIV 500) as quadsquares
                                from gridimage
                                where nateastings > 0 and moderation_status = 'geograph'
                                group by gridsquare_id,user_id
                                having quadsquares = 4
                                order by null;";

############################################

$parsed = preg_match('/create table `?(\w+)`?'.  //1
			'(.*)'. //2  (ignored for now. can get the indexes+engine etc from real table!
			'select (.+?)'.  //3
			'from (.+?)'.   //4
			'(where .+?)?'.
			'(group by .+?)?'.
			'(having .+?)?'. //7
			'(order by .+?)?'.
			'(limit .+?)?'. //9
			'\s*;?\s*$/is', $sql,$m);

print_r($m);

$updates = array();
$updates['table_name'] = trim(preg_replace('/_tmp$/','',$m[1]));
$updates['schedule'] = trim($param['schedule']);
$updates['sql_select'] = trim($m[3]);
$updates['sql_from'] = trim($m[4]);
$updates['sql_where'] = trim(preg_replace('/^where /i','',@$m[5]));
$updates['sql_group'] = trim(preg_replace('/^group by /i','',@$m[6]));
$updates['sql_having'] = trim(preg_replace('/^having /i','',@$m[7]));
$updates['sql_order'] = trim(preg_replace('/^order by /i','',@$m[8]));
$updates['sql_limit'] = trim(preg_replace('/^limit /i','',@$m[9]));
//$updates[''] = trim(preg_replace('/^where /i','',$m[1]));

print_r($updates);

if ($param['execute']) {
	if ($param['purge']) {
		//$db->Execute("DELETE FROM material_view WHERE table_name = '{$updates['table_name']}'"); //we now 'update' in place
		$db->Execute("DELETE FROM material_view_column WHERE table_name = '{$updates['table_name']}'");

		$db->Execute('UPDATE material_view SET `'.implode('` = ?,`',array_keys($updates)).'` = ? WHERE table_name = '.$db->Quote($updates['table_name']),array_values($updates));
	} else {

		$db->Execute('INSERT INTO material_view SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
	}
}

############################################

if (strpos($updates['sql_select'],';;')) {
	//special format, where we converted to ;; manualkly
	$bits = explode(';;',$updates['sql_select']);
} elseif ($param['nl']) {
	//special format, where we KNOW there is a line after every column definition
	$bits = preg_split('/,\s*\n/',$updates['sql_select']);
} else {
	// todo, this doesnt work, if commas IN the columns!
	$bits = explode(',',$updates['sql_select']);

	// black magic from...
	//https://stackoverflow.com/questions/50820944/php-split-a-string-by-comma-but-ignoring-anything-inside-square-brackets
	$bits = preg_split($pattern = '/,|\([^)]+\)(*SKIP)(*FAIL)/',$updates['sql_select']);
}

$table = $updates['table_name'];
$group = $updates['sql_group'];

############################################

foreach ($bits as $idx => $bit) {
	$updates = array();

	if (preg_match('/(.*) as `?(\w+)`?\s*$/i',$bit,$m)) {
		$updates['definition'] = trim($m[1]);
		$updates['column_name'] = $m[2];
	} else {
		$updates['definition'] = trim($bit);
		$updates['column_name'] = trim(preg_replace('/^\s*\`?\w+\`?\./','',trim($bit)),' `');
	}
	if (strpos($group,$updates['definition']) !== FALSE || preg_match('/\b'.$updates['column_name'].'\b/',$group))
		$updates['grouped'] = 1; //basic version for now!

	if ($updates['column_name'] == 'hectad' && strpos($group,'(x) div 10,(y) div 10') !== FALSE)
		$updates['grouped'] = 1; //hectad columns used a optimized x,y grouping using origin (which is same as hectad) 

	//todo $updates['indexed'] =

	print_r($updates);

	$updates['table_name'] = $table;
	$updates['sort_order'] = $idx;
	if ($param['execute'])
		$db->Execute('INSERT INTO material_view_column SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
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

        unique key(table_name,column_name)
);



*/
