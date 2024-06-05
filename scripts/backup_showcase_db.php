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

//these are the arguments we expect
$param=array(
	'dir'=>'/var/www/geograph',		//base installation dir
	'config'=>'staging.geograph.org.uk', //effective config
	'factor'=>false,
	'dry'=>true,
	'help'=>0,		//show script help?
);

//very simple argument parser
for($i=1; $i<count($_SERVER['argv']); $i++) {
	$arg=$_SERVER['argv'][$i];
	if (substr($arg,0,2)=='--') {
		$arg=substr($arg,2);
		$bits=explode('=', $arg,2);
		if (isset($param[$bits[0]])) {
			//if we have a value, use it, else just flag as true
			$param[$bits[0]]=isset($bits[1])?$bits[1]:true;
		}
		else die("unknown argument --$arg\nTry --help\n");
	}
	else die("unexpected argument $arg - try --help\n");
}

if ($param['help']) {
	echo <<<ENDHELP
---------------------------------------------------------------------
php backup_geograph_db.php
    --dir=<dir>         : base directory (/home/geograph)
    --config=<domain>   : effective domain config (www.geograph.org.uk)
    --help              : show this message
---------------------------------------------------------------------

ENDHELP;
exit;
}

//set up  suitable environment
ini_set('include_path', $param['dir'].'/libs/');
$_SERVER['DOCUMENT_ROOT'] = $param['dir'].'/public_html/';
$_SERVER['HTTP_HOST'] = $param['config'];

$color = "\033[31m";
$white = "\033[0m";
$gray = "\033[32m"; //https://stackoverflow.com/questions/5947742/how-to-change-the-output-color-of-echo-in-linux

//--------------------------------------------
# here we connect manually, to avoid having to load adodb and global (to make this script as portable as possible!)

//public_html/showcase/includes/mysql-config.inc.php
include $_SERVER['DOCUMENT_ROOT']."showcase/includes/mysql-config.inc.php";


$folder =  $param['dir'].'/backups/by-table/';

$cred = "-h".escapeshellcmd($db_host)." -u".escapeshellcmd($db_user)." -p".escapeshellcmd($db_passwd)." ".escapeshellcmd($db_name);

$date = date('Y-m-d--H-i');

$ext = '.sql.*'; // we now have .sql.gz, .sql.gz.gpg and .sql.xz, .sql.xz.gpg !!

##############################

if (!empty($param['factor'])) {
	$sql = "UPDATE _tables SET factor=factor/2, updated=updated";
	if ($param['dry']) {
		print "$sql;\n";
	} else {
		mysqli_query($db, $sql) or print('<br>Error save: '.mysqli_error($db));
	}
}

##############################

$status = getAssoc("SHOW TABLE STATUS");

$tables = getAssoc("SELECT * FROM _tables ORDER BY table_name"); //useful trick to put _tables at the end :)

$done =0;
foreach ($status as $table => $s) {
        $data = isset($tables[$table])?$tables[$table]:array('backedup' => 0, 'type'=>'unknown', 'backup'=>'?');

	$backup = false;
	if ($data['backup'] == 'N') {
		//actully we will backup these sometimes! But note we dont save history!
		if (rand(1,3) == 3) {
			if ($s['Update_time'] > $data['backedup']) { $backup = true; }
		}
	} else {
		switch($data['type']) {
			//backp these always!
			case 'primary_archive': //TODO, check a primary key and include a WHERE key > x onto the dump?
			case 'primary':	if ($s['Update_time'] > $data['backedup']) { $backup = true; } break;

			//only back these up once a week
			case 'secondary': if (date('N') == 7 && $s['Update_time'] > $data['backedup']) { $backup = true; } break;

			//only backup these once
			case 'derivied':
			case 'old':
			case 'temp': if (empty($data['backedup']) || $data['backedup'] < '1000-00-00') {	$backup = true; } break;

			//backup everything else as needed. (static rarely change anyway, and best to backup unknown in case)
			case 'static':
			case 'unknown':
			default: if ($s['Update_time'] > $data['backedup']) { $backup = true; } break;
		}
	}
	if ($s['Engine'] == 'InnoDB' && empty($s['Update_time']) && rand(1,3) == 3) //&& over 180 days??
		$backup = true;

	if ($backup) {
		//create the SQL before dumping the table, so updates happen after are caught in next backip
		$sql = "UPDATE `_tables` SET `factor` = `factor` + 1, `backedup` = '".date('Y-m-d H-i-s')."' WHERE `table_name` = '".mysqli_real_escape_string($db,$table)."'";

		if (!is_dir($folder.$table.'/'))
			mkdir($folder.$table.'/');


#################################################################
# if Sharded, then need to save a 'schema' file :)

		if (!empty($data['shard']) && empty(glob("$folder$table/"."*.schema.$ext"))) { //todo, also check if CREATE_TIME is later than schema file??
			//todo, we are going to have to check S3, the file - even if dumped before - wont be on local disk!
			$dir = "$folder$table/";
			$file = "$date.$table.schema.sql.xz";
			command(mysqldump("$table --no-data","$dir$file",false)); //not sensitive
		}

#################################################################
# Special Shard mode, were we have a timestamp column!

		if (!empty($data['shard']) && preg_match('/^shard using (\w+) when (\w+)/',$data['method'],$m)) {

			$info['primary'] = $m[1];
			$info['timestamp'] = $m[2];
			$shard = $data['shard'];

			//we get all shards here (rather than using HAVING updated> $backedup, because might need to dump a shard, never written!?
			$sql = "SELECT {$info['primary']} DIV $shard AS shard, MAX({$info['timestamp']}) AS updated FROM `$table` GROUP BY {$info['primary']} DIV $shard";
			print "# $sql; && updated> {$data['backedup']}\n";
			$rows = getAll($sql);

			$dumped = 0;
			foreach ($rows as $r) {
				$one = $r['shard'];
				$dir = "$folder$table/shard$shard-$one/";

				if ($r['updated'] > $data['backedup'] || !is_dir($dir)) {
					$start = ($one*$shard);
					$end = (($one*$shard)+($shard-1));
					$file = $date."_$table.$start-$end.sql.xz";
					$where = "--where='{$info['primary']} BETWEEN $start AND $end' --skip-comments --no-create-info";

					if (file_exists($dir)) {
						//todo - rotation, eg delete the 5th oldest backup?
					} else {
						command("mkdir -p $dir");
					}
					command(mysqldump("$table $where","$dir$file",$data['sensitive']));
					$dumped++;
				}
			}
			if (!$dumped) {
				print "$gray#Note, No updated shards for $table$white\n";
				continue;
			}

#################################################################
# else jsut do a full dump (or an 'append' dump!)

		} else {

			$desc = getAssoc("DESCRIBE $table");
			$primary_key = '';
			foreach($desc as $_column => $_row) {
				if ($_row['Key'] == 'PRI' && ($_row['Extra'] == 'auto_increment' || $_column == 'gridimage_id'))
					$primary_key = $_column;
			}
			if (isset($desc['upd_timestamp']) && rand(1,40)>2 && $data['backedup'] > 0) {
				$file = $folder.$table.'/'.$date."_$table.append.sql.gz";

				$where = "upd_timestamp >= \"{$data['backedup']}\"";

				$cmd = "mysqldump --opt --skip-comments $cred ".escapeshellarg($table)." --where '$where' --no-create-info";

			} elseif (!empty($primary_key) && !empty($data['backedup_key']) && rand(1,40)>2) {
				$file = $folder.$table.'/'.$date."_$table.append.sql.gz";

                                $where = "$primary_key > \"{$data['backedup_key']}\"";

                                $cmd = "mysqldump --opt --skip-comments $cred ".escapeshellarg($table)." --where '$where' --no-create-info";

				$bid = getOne("SELECT MAX($primary_key) FROM `$table`");
				$sql = str_replace(' SET '," SET backedup_key = $bid, ",$sql);

			} else {
				$file = $folder.$table.'/'.$date."_$table.sql.gz";

				$cmd = "mysqldump --opt --skip-comments $cred ".escapeshellarg($table);
			}
			if ($data['backup'] == 'N') {
				//backup=N just means dont store history, overwrite the latest copy (so remove date from the filename).

				$file = str_replace($date."_",'',$file);
			}
			if (false && $data['sensitive'] == 'Y') {
				$file .= '.gpg';
				$cmd .= " | gzip | gpg --encrypt --recipient 'Geograph' > $file";
			} else {
				$cmd .= " | gzip --rsyncable > $file";
			}
			print "$cmd\n";
			if (empty($param['dry']))
				print `$cmd`;
		}

#################################################################

		if (file_exists($file) && filesize($file) > 10) {
			if (!mysqli_ping($db) && !ini_get('mysqli.reconnect')) {
				//we reconnect, as the connection possibly died
				$db = mysqli_connect($db_host, $db_user, $db_passwd, $db_name) or die("Sorry the site is offline right now.");

			}
			if (empty($param['dry']))
				mysqli_query($db, $sql) or print('<br>Error save: '.mysqli_error($db));
			print "\n\n";
			$done++;
		} else {
			print "++++++++++ Error: FILE $file NOT FOUND +++++++++++++\n\n";
		}

		if (0) {
			//TODO, rotate and delete the oldest backups?
		}
	}
}

#################################################################

print "done=$done\n";
###


function mysqli_reconnect() {
	global $db,$CONF;
	if (!mysqli_ping($db) && !ini_get('mysqli.reconnect')) {
		mysqli_close($db);
		$db = mysqli_connect($CONF['db_connect'], $CONF['db_user'], $CONF['db_pwd'], $CONF['db_db']) or die(mysqli_error($db));
	}
}

function command($cmd,$debug = false) {
	global $param,$cred,$folder, $color,$white;

	if ($param['dry'] || $debug) {
		if (posix_isatty(STDOUT) && strpos($cmd,' | ')) {
			if ($param['dry'] !== '2')
				$cmd = str_replace($folder,'',$cmd);
			$cmd = "$color".str_replace(' | ',$white.' | ',$cmd);
		}
		print "$cmd\n";
	} elseif ($cmd) {
		print str_replace($folder,'',str_replace($cred,'...',$cmd))."\n";
		print `$cmd`;
	}
}

function mysqldump($dump,$file,$sensitive = null) {
	global $cred;
	//we now use xz rather than gzip, because files are about half the size!!
	if ($sensitive == 'Y') {
		return "mysqldump $cred $dump | xz | gpg --trust-model always --encrypt --recipient 'Geograph' > $file.gpg";
	} else {
		return "mysqldump $cred $dump | xz > $file";
	}
}


function getAssoc($query) {
        global $db;
        $result = mysqli_query($db, $query) or print('<br>Error getAssoc: '.mysqli_error($db));
        if (!mysqli_num_rows($result)) {
                return FALSE;
        }
        $a = array();
        $row = mysqli_fetch_assoc($result);

        if (count($row) > 2) {
                do {
                        $i = array_shift($row);
                        $a[$i] = $row;
                } while($row = mysqli_fetch_assoc($result));
        } else {
                $row = array_values($row);
                do {
                        $a[$row[0]] = $row[1];
                } while($row = mysqli_fetch_row($result));
        }
        return $a;
}

function getOne($query) {
	global $db;
	$result = mysqli_query($db, $query) or print("<br>Error getOne [[ $query ]] : ".mysqli_error($db));
	if (mysqli_num_rows($result)) {
		return mysqli_result($result,0,0);
	} else {
		return FALSE;
	}
}

function getAll($query,$non_fatal = false) {
	global $db;
	mysqli_reconnect();

	$result = mysqli_query($db, $query) or $non_fatal or print('<br>Error getAll: '.mysqli_error($db)."\n$query;\n");
	if (!$result || !mysqli_num_rows($result)) {
		return FALSE;
	}
	$a = array();
	while($row = mysqli_fetch_assoc($result)) {
		$a[] = $row;
	}
	return $a;
}

