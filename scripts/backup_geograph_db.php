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
	'dir'=>'/var/www/geograph_svn',		//base installation dir
	'config'=>'staging.geograph.org.uk', //effective config
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

//--------------------------------------------
# here we connect manually, to avoid having to load adodb and global (to make this script as portable as possible!)

//todo, use docker config
require('conf/'.$_SERVER['HTTP_HOST'].'.conf.php');

$db = mysqli_connect($CONF['db_connect'], $CONF['db_user'], $CONF['db_pwd'], $CONF['db_db']);



$folder =  $param['dir'].'/backups/by-table/';

if ($CONF['db_read_connect']) {
	#$cred = "-h".escapeshellcmd($CONF['db_read_connect'])." -u".escapeshellcmd($CONF['db_read_user'])." -p".escapeshellcmd($CONF['db_read_pwd'])." ".escapeshellcmd($CONF['db_read_db']);
	$cred = "-h".escapeshellcmd($CONF['db_read_connect'])." -u".escapeshellcmd($CONF['db_user'])." -p".escapeshellcmd($CONF['db_pwd'])." ".escapeshellcmd($CONF['db_read_db']);
} else {
	$cred = "-h".escapeshellcmd($CONF['db_connect'])." -u".escapeshellcmd($CONF['db_user'])." -p".escapeshellcmd($CONF['db_pwd'])." ".escapeshellcmd($CONF['db_db']);
}

$date = date('Y-m-d--H-i');



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

	if ($backup) {
		//create the SQL before dumping the table, so updates happen after are caught in next backip
		$sql = "UPDATE `_tables` SET `factor` = `factor` + 1, `backedup` = '".date('Y-m-d H-i-s')."' WHERE `table_name` = '".mysqli_real_escape_string($db,$table)."'";

		if (!is_dir($folder.$table.'/'))
			mkdir($folder.$table.'/');

		if (false && $data['shard']) {
			//shard feature - this was only beta quality and not used any more!

			$desc = getAssoc("DESCRIBE $table");
			foreach ($desc as $column => $info) {
				if ($info['Key'] == 'PRI') {
					$key = $column;
				}
			}

			$mm = getAssoc("SELECT 1 as d,MIN($key) AS `min`,MAX($key) AS `max` FROM $table");
			$mm = array_pop($mm);

			for($q = floor($mm['min']/$data['shard'])*$data['shard'];$q < $mm['max'];$q+=$data['shard']) {
				if ($data['shard'] <= 10000) {
					$shard = sprintf('_%04d',$q/$data['shard']);
				} else {
					$shard = sprintf('_%03d',$q/$data['shard']);
				}
				$file = $folder.$table.'/'.$date."_$table$shard.sql.gz";

				$where = "$key BETWEEN $q AND ".($q+$data['shard']-1);

				$cmd = "mysqldump --opt --skip-comments --no-create-info $cred ".escapeshellarg($table)." --where '$where'";

				if (false && $data['sensitive'] == 'Y') { //this server doesnt have encyptiuon setup
					$file .= '.gpg';
					$cmd .= " | gzip | gpg --encrypt --recipient 'Geograph' > $file";
				} else {
					$cmd .= " | gzip --no-name --rsyncable > $file";
				}
				print "$cmd\n";
				if (empty($param['dry']))
					print `$cmd`;

				### ls -1t /var/www/backups/by-table/gridimage_exif/*_002* | head -n2 | xargs md5sum | cut -d' ' -f1 | uniq -c | cut -d' ' -f7

				if (file_exists($file) && filesize($file) > 10) {

					$cmd = "ls -1t $folder$table/*$shard.* | head -n2 | xargs md5sum | cut -d' ' -f1 | uniq -c | cut -d' ' -f7";

					if (trim(`$cmd`) == 2) {
						print "delete $file\n";
						if (empty($param['dry']))
							unlink($file);
					}
				}
			}
			//TODO - run hardlink & delete!

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

		if (file_exists($file) && filesize($file) > 10) {
			if (!mysqli_ping($db) && !ini_get('mysqli.reconnect')) {
				//we reconnect, as the connection possibly died
				$db = mysqli_connect($CONF['db_connect'], $CONF['db_user'], $CONF['db_pwd'], $CONF['db_db']);
			}
print "$sql;";
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


print "done=$done\n";
###


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
