<?php
/**
 * $Project: GeoGraph $
 * $Id: recreate_maps.php 2996 2007-01-20 21:39:07Z barry $
 *
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Barry Hunter (geo@barryhunter.co.uk)
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

############################################

//these are the arguments we expect
$param=array(
	'string'=>'typo', //table name to search for!
	'host'=>'', //override host
	'col'=>false, //search for columns
	'group'=>false, //show a preview group of this column (if one table!)
	'plus'=>true, //use 'show table status' rather than just names
);


//normal parser doesnt support arguments as seperate (because doesnt know which accept them)
// this allows us to do [[ alias access_grep="php scripts/loki.php --stream=stdout --string" ]] to allow `access_grep busyday`
if (in_array('--string',$_SERVER['argv'])) {
        $idx = array_search('--string',$_SERVER['argv']);
        if (isset($_SERVER['argv'][$idx+1])) {
                $_SERVER['argv'][$idx] = $_SERVER['argv'][$idx].'='.$_SERVER['argv'][$idx+1];
                unset($_SERVER['argv'][$idx+1]);

                $_SERVER['argv'] = array_values($_SERVER['argv']); //reset the keys, so count matches
        }
}


chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$host = $CONF['db_connect'];
if ($param['host']) {
    $host = $param['host'];
}
print(date('H:i:s')."\tUsing server: $host\n");
$DSN = str_replace($CONF['db_connect'],$host,$DSN);

//uses $GLOBALS['DSN']
$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

print "database: ".$db->getOne("SELECT DATABASE()")."\n";

############################################

if (!empty($param['col'])) {
	$sql = "SELECT * FROM columns WHERE column_name LIKE ".$db->Quote('%'.$param['string'].'%')." ORDER BY table_name,pos,column_name";
print "$sql;\n";
	$c = dump_rows($sql);
	print "# $c Rows\n";
	exit;
}

############################################

$test = $db->getCol("SHOW TABLES LIKE ".$db->Quote('%'.$param['string'].'%'));

if (!empty($test)) {
	if (count($test) > 1) {
		foreach ($test as $table)
			if ($table == $param['string']) //still allow for an exact match!
				dump_table($table);

		if (!empty($param['plus'])) {
			$status = $db->getAll("SHOW TABLE STATUS LIKE ".$db->Quote('%'.$param['string'].'%'));
			printf("%-30s %10s %12s %12s %22s %22s \n", 'Name', 'Engine', 'Rows', 'Data_length', 'Create_time', 'Update_time');
			print str_repeat('-',114)."\n";
			foreach($status as $row)
				printf("%-30s %10s %12s %12s %22s %22s \n", $row['Name'], $row['Engine'] ?? 'view', number_format($row['Rows'],0), number_format($row['Data_length'],0), $row['Create_time'], $row['Update_time']);
			print str_repeat('-',114)."\n";

		} else {
			print "TABLES: [".count($test)."]\n";
			foreach ($test as $table)
				print "* $table\n";
		}
	} else {
		dump_table($test[0]);
	}
} else {
	print "No Tables Found\n";
}

function dump_table($table) {

################################

	global $db, $param;
	$create = $db->getRow("SHOW CREATE TABLE `$table`");
	if (!empty($create['Create View'])) {
		print "\n".preg_replace('/\b(select|from|where|group by|order by|limit) /',"\n$1 ",
		preg_replace('/ AS `(\w+)`,/'," AS \t`$1`,\n",$create['Create View'])).";\n\n";

		//in practice, show table status isnt useful for a view!
	} else {
		print "\n".array_pop($create).";\n\n";

		$rows = $db->getAll("SHOW TABLE STATUS LIKE ".$db->Quote($table));
		//print_r($rows[0]);
		foreach($rows[0] as $key=>$value)
			printf("%20s: %s\n",$key,$value);
		print "\n\n";
	}

################################

	$cols = $db->getAssoc("DESCRIBE `$table`");
	$o = array();
	foreach($cols as $col => $data) {
/* Array
(
    [Type] => int(10) unsigned
    [Null] => NO
    [Key] => PRI
    [Default] =>
    [Extra] => auto_increment
)*/
		if ($col == 'ip' || $col == 'ipaddr') {
			//native is a messy binary string
			$o[] = "INET6_NTOA($col) as `$col`";
		} elseif ($data['Type'] == 'text' || $data['Type'] == 'longtext') {
			$o[] = "substring($col,1,32) as `$col`";
		} elseif (preg_match('/char\((\d+)\)/',$data['Type'],$m) && $m[1] > 32) {
			$o[] = "substring($col,1,32) as `$col`";
		} elseif (preg_match('/^(multi|)(polygon|linestring|geometry|point)(collection|)$/i',$data['Type'])) {
			//native is a messy binary string
			$o[] = "substring(ST_ASTEXT(`$col`),1,32) as `$col`";
		} else {
			$o[] = "`$col`";
		}
		//todo, unix timestamp???
	}

################################

	$sql = "SELECT ".implode(', ',$o)." FROM `$table` LIMIT 10";
//todo, if ($param['where']) $sql .= "WHERE...";
//todo, if ($param['latest']) $sql .= "ORDER BY pkey DESC"; //or updated/last_update - byt only if INDEXED (or rows < 1000 ?)
	print_r("$sql;\n");
	dump_rows($sql);
	print "\n";

################################

	if (!empty($param['group'])) {
		$sql = "SELECT `{$param['group']}`,COUNT(*) FROM `$table` GROUP BY `{$param['group']}` WITH ROLLUP";
//todo, run 'explain' and break out if high number of rows??
		print_r("$sql;\n");
		dump_rows($sql);
	}

################################
}

function dump_rows($rows) {
	global $db;
	if (is_string($rows))
		$rows = $db->getAll($rows);

	if (!empty($rows)) {
		$v = array();
		foreach ($rows[0] as $key=>$value) {
			$l = strlen($key);
			foreach ($rows as $row) {
				$d = strlen($row[$key]);
				if ($d > $l) {$l = $d; }
			}
			$v[] = "%-".$l."s"; //note, dont auto-select 'd' (for example) as would mean null gets output as 0 (also it used for header row too!)
		}
		$v =  "| " . implode(" | ",$v) . " |\n";
		$h = vsprintf($v, array_keys($rows[0]));
                print str_repeat('-',strlen($h)-1)."\n";
                print $h;
                print str_repeat('-',strlen($h)-1)."\n";
                foreach ($rows as $row) {
	                vprintf($v, array_values($row));
                }
                print str_repeat('-',strlen($h)-1)."\n";
	}
	return count($rows);
}



