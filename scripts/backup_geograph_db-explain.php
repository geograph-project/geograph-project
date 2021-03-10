<?
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
	'type'=>'',
	'size'=>0,
	'table'=>'',
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
php backup_geograph_db-new.php
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

//todo, use docker config
require('conf/'.$_SERVER['HTTP_HOST'].'.conf.php');

$db = mysqli_connect($CONF['db_connect'], $CONF['db_user'], $CONF['db_pwd'], $CONF['db_db']);

function mysqli_reconnect() {
	global $db,$CONF;
	if (!mysqli_ping($db) && !ini_get('mysqli.reconnect')) {
		mysqli_close($db);
		$db = mysqli_connect($CONF['db_connect'], $CONF['db_user'], $CONF['db_pwd'], $CONF['db_db']) or die(mysqli_error($db));
	}
}

##############################

$folder =  $param['dir'].'/backups/by-table/';

if ($param['dry'] && $param['dry'] !== '2') {
	$cred = "-h".preg_replace('/\..*/','...',$CONF['db_read_connect'])." ".escapeshellcmd($CONF['db_db']);
} elseif ($CONF['db_read_connect']) {
	#$cred = "-h".escapeshellcmd($CONF['db_read_connect'])." -u".escapeshellcmd($CONF['db_read_user'])." -p".escapeshellcmd($CONF['db_read_pwd'])." ".escapeshellcmd($CONF['db_read_db']);
	$cred = "-h".escapeshellcmd($CONF['db_read_connect'])." -u".escapeshellcmd($CONF['db_user'])." -p".escapeshellcmd($CONF['db_pwd'])." ".escapeshellcmd($CONF['db_read_db']);
} else {
	$cred = "-h".escapeshellcmd($CONF['db_connect'])." -u".escapeshellcmd($CONF['db_user'])." -p".escapeshellcmd($CONF['db_pwd'])." ".escapeshellcmd($CONF['db_db']);
}

##############################

$where = array();
$where[] = "method IS NOT NULL";
$where[] = "backup != 'N'";
$where[] = "TABLE_TYPE != 'VIEW'";
$where[] = "UPDATE_TIME > coalesce(DATE_ADD(backedup, INTERVAL maxage DAY), '0000-00-00 00:00:00') ";

if ($param['table'])
	$where[] = "tables.TABLE_NAME = '".mysqli_real_escape_string($db,$param['table'])."'";
elseif ($param['type'])
	$where[] = "type = '".mysqli_real_escape_string($db,$param['type'])."'";

if ($param['size'])
	$where[] = "DATA_LENGTH < ".intval($param['size']);

//TODO. Uses UPDATE_TIME, which not reliable on Innodb. Need to deal with that.
// note also using partition engine, now, which might need better heandling. Perhaps we could directly acess each shared, and backup seperatly?
// eg:  geograph_staging | queries

$sql = "SELECT TABLE_NAME AS table_name,TABLE_ROWS,DATA_LENGTH,AVG_ROW_LENGTH,UPDATE_TIME,AUTO_INCREMENT,`ENGINE`,
					type,shard,backup,backedup,backedup_key,method,`sensitive`
        FROM information_schema.tables
                LEFT JOIN _tables USING (table_name)
        WHERE table_schema = DATABASE()
                AND ".implode(' AND ',$where)."
        ORDER BY backedup
        ";

//print "$sql\n";

//todo, deal with special rules for 'type's
//eg rule for secondry, that we dont back them EVERY time. ?
//"UPDATE_TIME > backedup" takes care of never backed up automatically.


foreach(getAll($sql) as $row) {
	$table = $row['table_name'];
	$method = $row['method'];

	$filename = date('Y-m-d-H')."_$table.sql.xz";

#################################################################
# Special Shard mode, where we dont bother checking for updated shared, assume only last shard ever updated.

	if ($row['shard'] && preg_match('/append only shard using (\w+)/',$row['method'],$m)) {
		if (empty($row['TABLE_ROWS']))
			continue;

		$info['primary'] = $m[1];
		$shard = $row['shard'];

		//todo, as we assume is append only, we COULD perhaps do this??
		//$col = range(0,intval(get("SELECT MAX(id) FROM $table")/$shard),1)
		// in theory, a sparse array better with group by (could compare TABLE_ROWS vs AUTO_INCREMENT ??)
		// whereas a dense array, use use neive list.

		$sql = "SELECT {$info['primary']} DIV $shard AS shard FROM `$table` GROUP BY {$info['primary']} DIV $shard";

	if ($param['table'] == $table) {
		print "$sql;\n";
		$col = getCol($sql,true);

		$last = end($col);
		foreach ($col as $one) {
			$dir = "$folder$table/shard$shard-$one/";

			//only need shards NOT yet backed up, and the last always needs writing
			if (empty(glob("$dir/*$ext")) || $one == $last) { //todo. if there is a file, check its size (ie 'find -name "*$ext" -not -empty')
                                $start = ($one*$shard);
                                $end = (($one*$shard)+($shard-1));
                                $where = "--where='{$info['primary']} BETWEEN $start AND $end' --skip-comments --no-create-info";
				command("mysqldump $table $where");
			}
		}
		print "\n\n";
	}

#################################################################
# Special Shard mode, were we have a timestamp column!

	} else	if ($row['shard'] && preg_match('/^shard using (\w+) when (\w+)/',$row['method'],$m)) {
		if (empty($row['TABLE_ROWS']))
			continue;

		$info['primary'] = $m[1];
		$info['timestamp'] = $m[2];
		$shard = $row['shard'];

		$sql = "SELECT {$info['primary']} DIV $shard AS shard, MAX({$info['timestamp']}) AS updated FROM `$table` GROUP BY {$info['primary']} DIV $shard";

		if ($table == 'gridimage_tag' || $table == 'gridimage_snippet') {
			//exclude teh temporally rows, that have lots of little shards!
			$sql = str_replace('GROUP BY','WHERE gridimage_id < 4294967296 GROUP BY',$sql);
		}

	if ($param['table'] == $table) {
		print "$sql;\n";
                $rows = getAll($sql);
                foreach ($rows as $r) {
                        $one = $r['shard'];
                        if ($r['updated'] > $row['backedup']) {

                                $start = ($one*$shard);
                                $end = (($one*$shard)+($shard-1));
                                $where = "--where='{$info['primary']} BETWEEN $start AND $end' --skip-comments --no-create-info";

                                command("mysqldump $table $where");
                        }
                }
		print "\n\n";
	}

#################################################################
# Neive Shard mode, have to check against last backup to see if worth keeping!

	} else	if ($row['shard'] && $row['sensitive'] != 'Y' && preg_match('/^shard using (\w+)$/',$row['method'],$m)) {
		if (empty($row['TABLE_ROWS']))
			continue;

		$info['primary'] = $m[1];
		$shard = $row['shard'];

		$sql = "SELECT {$info['primary']} DIV $shard AS shard FROM `$table` GROUP BY {$info['primary']} DIV $shard";

	if ($param['table'] == $table) {
		print "$sql;\n";
                $rows = getAll($sql);

		foreach ($rows as $r) {
			$one = $r['shard'];
			$dir = "$folder$table/shard$shard-$one/";

			$start = ($one*$shard);
			$end = (($one*$shard)+($shard-1));
			$file = preg_replace('/\./',".$start-$end.",$filename,1);
			$where = "--where='{$info['primary']} BETWEEN $start AND $end' --skip-comments --no-create-info --dump-date=FALSE";

			if (file_exists($dir)) {
				$files = glob("$dir/*$ext");

				command("mysqldump $table $where > tmpfile");

				if (!empty($files)) {
					sort($files);
					$last = array_pop($files);
					print "if (md5sum($last) != md5sum(tmpfile)) { mv tmpfile $dir$file }\n";
				} else {
					command("mv $tmpfile $dir$file");
				}
			} else {
				command("mysqldump $table $where");
			}
		}
		print "\n\n";
	}

#################################################################
# Dump the whole table, simplz!

	} elseif ($row['method'] == 'full') {

		continue;

#################################################################

	} else {
		print "UNKNOWN $method! for $table\n";
		continue;
	}

############

	print "$sql;\n";
	dump_table("EXPLAIN $sql");

	print "\n";
}

print "all done\n";

#################################################################

function command($cmd,$debug = true) {
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

function dump_table($sql,$title = '') {
        global $db;
        if (is_string($sql)) {
                $sql = getAll($sql);
        }
        if (empty($sql))
                return;
        print "$title\n";
        print implode("\t",array_keys($sql[0]))."\n";
        foreach ($sql as $row)
                print implode("\t",$row)."\n";
        print "\n";
}

function getOne($query,$non_fatal = false) {
	global $db;
	mysqli_reconnect();

	$result = mysqli_query($db, $query) or $non_fatal or die("Error getOne: ".mysqli_error($db)."\n$query;\n");
	if ($result && mysqli_num_rows($result)) {
		$row = mysqli_fetch_row($result);
		return $row[0];
	} else {
		return FALSE;
	}
}

function getCol($query,$non_fatal = false) {
        global $db;
	mysqli_reconnect();

	$result = mysqli_query($db, $query) or $non_fatal or die('Error getCol: '.mysqli_error($db)."\n$query;\n");
        if (!$result || !mysqli_num_rows($result)) {
                return FALSE;
        }
        $a = array();
        while($row = mysqli_fetch_row($result)) {
                $a[] = $row[0];
        }
        return $a;
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

