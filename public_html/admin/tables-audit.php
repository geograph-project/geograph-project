<?

require_once('geograph/global.inc.php');
init_session();

$USER->mustHavePerm("admin");

$smarty = new GeographPage;

$db = NewADOConnection($GLOBALS['DSN']);
if (!$db) die('Database connection failed');
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


ini_set("display_errors",1);

$folder = "/mnt/combined/geograph_live/public_html/backups/by-table/";

if (!empty($_POST) && !empty($_POST['table_name'])) {
	$updates = $_POST;

	$table = "_tables";
	$sql = updates_to_insertwithdup($table,$updates);
	print "<p>$sql</p>";
	$db->Execute($sql);
}

if (!empty($_GET['table'])) {
	$sql = "SELECT TABLE_NAME AS table_name,TABLE_ROWS,DATA_LENGTH,AVG_ROW_LENGTH,UPDATE_TIME,AUTO_INCREMENT,ENGINE,type,updated,shard,backup,backedup,backedup_key,method
        FROM information_schema.tables
                LEFT JOIN _tables USING (table_name)
        WHERE table_schema = DATABASE()
                AND TABLE_NAME = ".$db->Quote($_GET['table'])."
        LIMIT 1";
} else {
$sql = "SELECT TABLE_NAME AS table_name,TABLE_ROWS,DATA_LENGTH,AVG_ROW_LENGTH,UPDATE_TIME,AUTO_INCREMENT,ENGINE,type,updated,shard,backup,backedup,backedup_key,method
        FROM information_schema.tables
                LEFT JOIN _tables USING (table_name)
        WHERE table_schema = DATABASE()
                AND backup != 'N' AND method IS NULL
        LIMIT 1";
}

$tablerow = $db->getRow($sql);

if (empty($tablerow))
	die("no tables left, but do check http://www.geograph.org.uk/admin/tables.php?next=type !!\n");


$table = $tablerow['table_name'];


print "<div style=\"width:300px;float:left;margin-right:10px;\">";
//dump_table(array($tablerow));
dump_row($tablerow);
print "</div>";

$rows = $db->getAll("DESCRIBE $table");
dump_table($rows);

print "<br style=clear:both>";

$info = array();
foreach ($rows as $row) {
	if ($row['Type'] == 'timestamp' && $row['Default'] == 'CURRENT_TIMESTAMP' && stripos($row['Extra'],'CURRENT_TIMESTAMP') !== FALSE) {
		print "TIMESTAMP: {$row['Field']}<br>";
		$info['timestamp'] = $row['Field'];
	} elseif ($row['Field'] == 'created' && $row['Type'] == 'timestamp' && $row['Default'] == 'CURRENT_TIMESTAMP') {
		print "CREATED: {$row['Field']}<br>";
		$info['timestamp'] = $row['Field'];
	} elseif ($row['Key'] == 'PRI') {
		print "PRIMARY: {$row['Field']}<br>";
		$info['primary'] = $row['Field'];

		if ($row['Extra'] == 'auto_increment') {
			print "Is auto-increment? <input type=button value='Append Only' onclick=\"document.forms['theform'].elements['method'].value = 'append only shard using {$row['Field']}'\"><br>";
		}
	}
}

$filename = date('Y-m-d-H')."_$table.sql.gz"; //todo, encypt sensitive?

if ($tablerow['ENGINE'] != 'MyISAM') {

	print "NOT MyISAM, so skipping!";

} elseif ($tablerow['DATA_LENGTH'] < units(10,'mb')) {
	$method = 'full';
	print "Size under 10Mb, so just use $method";
} else {
	if (isset($info['primary'])) {// todo check numberic!
		if ($tablerow['AUTO_INCREMENT'])
			print "auto_inc/rows = ".($tablerow['AUTO_INCREMENT']/$tablerow['TABLE_ROWS'])."<br>";
		$shards = ceil($tablerow['DATA_LENGTH']/units(10,'mb'));
		print "Shards= $shards;<br>";
		print "Ideal = ".floor($tablerow['TABLE_ROWS']/$shards)."<br>";
		$shard = ceil( floor($tablerow['TABLE_ROWS']/$shards) /100)*100;
		print "Shard= $shard;<br>";
		print "Estimated = ".($shard*$tablerow['AVG_ROW_LENGTH'])." bytes per shard<br>";

		if (!empty($info['timestamp'])) {
			$sql = "SELECT {$info['primary']} DIV $shard AS shard,MAX({$info['timestamp']}) AS updated FROM `$table` GROUP BY {$info['primary']} DIV $shard";

			$method = 'shard using '.$info['primary'].' when '.$info['timestamp'];

		} else {
			$sql = "SELECT {$info['primary']} DIV $shard AS shard FROM `$table` GROUP BY {$info['primary']} DIV $shard";

			$method = 'append only shard using '.$info['primary'];
		}

		$col = range(0,intval($db->getOne("SELECT MAX(`{$info['primary']}`) FROM `$table`")/$shard),1);
		print "Shards: ".implode(',',$col)."<br>";

		print "<p>$sql</p>";
		dump_table("EXPLAIN $sql");

		$rows = $db->getAll($sql);
		print "Count = ".count($rows)."<br>";

		if (!empty($info['timestamp'])) {
			print "<div style=\"width:200px;float:left;margin-right:10px;\">";
			//todo, this could just be added to the sql query??
			foreach ($rows as $idx => $r) {
				$rows[$idx]['E'] = ($r['updated'] > $tablerow['backedup'])?'Y':'';
			}
			dump_table($rows);
			print "</div>";
		}

		/////////////////////
		// we first need to find the last written folder, and empty it, because it could be incomplete. if same as last, will be rewritten anyway!
		$lastwritten = -1;
		foreach ($rows as $r) {
			$one = $r['shard'];
			$dir = "$folder$table/shard$shard-$one/";
			if (!empty(glob("$dir/*.gz")))
				$lastwritten = $one;
			$last = $one;
		}
		if ($lastwritten>-1) {
			$dir = "$folder$table/shard$shard-$lastwritten/";
			print "rm $dir*.gz<br>"; //todo sensitive
		}

		/////////////////////
		// now write any shards needed.

		foreach ($rows as $r) {
			$one = $r['shard'];
			$dir = "$folder$table/shard$shard-$one/";

			//only need shards NOT yet backed up, and the last always needs writing
			if (empty(glob("$dir/*.gz")) || $one == $last) {

				$start = ($one*$shard);
				$end = (($one*$shard)+($shard-1));
				$file = preg_replace('/\./',".$start-$end.",$filename,1);
				$where = "--where='{$info['primary']} BETWEEN $start AND $end'";

				if (file_exists($dir)) {
					//append only shard - we CAN delete the older backups
					print "rm $dir/*.gz<br>"; //todo sensitive
				} else {
					print "mkdir $dir<br>";
				}
				//print "Would write: ".basename($file)." $where<BR>";
				print "mysqldump $table $where > ".basename($file)."<br>";
			}
		}
		$tablerow['shard'] = $shard;
		//$method = 'append only shard using '.$info['primary'];
	}

}
if (!empty($method))
	$tablerow['method'] = $method;










print "<form method=post name=theform>";
print "<h3>Update</h3>";


foreach (array('table_name','shard','type','backedup_key','backup','method') as $key)
	print "$key:<input type=text name=$key value=\"".htmlentities($tablerow[$key])."\" size=50><br>";

print "<input type=submit value=Update>";

print "</table>";

dump_table($db->getAll("select method,count(*) from _tables group by method like '%shard%',method like '%append%',method like '%when%'"));



//=====================

function units($value,$unit) {
	switch($unit) {
		case 'gb': return $value*1048576*1024;
		case 'mb': return $value*1048576;
		case 'kb': return $value*1024;
		case 'b': return $value;
	}
}


function dump_row($row) {
	print "<table cellspacing=0 cellpadding=3 border=1>";
	foreach ($row as $key => $value) {
		print "<tr><td>$key</td><td>".htmlentities($value)."</td></tr>";
	}
	print "</table>";
}

function dump_table($rows) {
	if (is_string($rows))
		$rows = getAll($rows);
	print "<table cellspacing=0 cellpadding=3 border=1>";
	$row = reset($rows);
	print "<tr><th>".implode("</th><th>",array_map('htmlentities',array_keys($row)))."</th></tr>";
	foreach ($rows as $row)
		print "<tr><td>".implode("</td><td>",array_map('htmlentities',$row))."</td></tr>";

	print "</table>";
}


function updates_to_a(&$updates) {
	global $db;
        $a = array();
        foreach ($updates as $key => $value) {
                //NULL
                if (is_null($value)) {
                        $a[] = "`$key`=NULL";
                } else {
                        //converts uk dates to mysql format (mostly) - better than strtotime as it might not deal with uk dates
                        if (preg_match('/^(\d{2})[ \/\.-]{1}(\d{2})[ \/\.-]{1}(\d{4})$/',$value,$m)) {
                                $value = "{$m[3]}-{$m[2]}-{$m[1]}";
                        }
                        //numbers and functions, eg NOW()
                        if (is_numeric($value) || preg_match('/^\w+\(\)$/',$value)) {
                                $a[] = "`$key`=$value";
                        } else {
                                $a[] = "`$key`=".$db->Quote($value);
                        }
                }
        }
        return $a;
}

function updates_to_insert($table,$updates) {
        $a = updates_to_a($updates);
        return "INSERT INTO $table SET ".join(',',$a);
}

function updates_to_replace($table,$updates) {
        $a = updates_to_a($updates);
        return "REPLACE INTO $table SET ".join(',',$a);
}

function updates_to_update($table,$updates,$primarykey,$primaryvalue) {
	global $db;
        $a = updates_to_a($updates);
        if (!is_numeric($primaryvalue)) {
                $primaryvalue = $db->Quote($primaryvalue);
        }
        return "UPDATE $table SET ".join(',',$a)." WHERE `$primarykey` = $primaryvalue";
}

function updates_to_insertwithdup($table,$updates) {
        $a = updates_to_a($updates);
        return "INSERT INTO $table SET ".join(',',$a).",`created` = NOW() ON DUPLICATE KEY UPDATE ".join(',',$a);
}


