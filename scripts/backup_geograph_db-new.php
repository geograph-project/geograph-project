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

if ($CONF['db_read_connect']) {
	#$cred = "-h".escapeshellcmd($CONF['db_read_connect'])." -u".escapeshellcmd($CONF['db_read_user'])." -p".escapeshellcmd($CONF['db_read_pwd'])." ".escapeshellcmd($CONF['db_read_db']);
	$cred = "-h".escapeshellcmd($CONF['db_read_connect'])." -u".escapeshellcmd($CONF['db_user'])." -p".escapeshellcmd($CONF['db_pwd'])." ".escapeshellcmd($CONF['db_read_db']);
} else {
	$cred = "-h".escapeshellcmd($CONF['db_connect'])." -u".escapeshellcmd($CONF['db_user'])." -p".escapeshellcmd($CONF['db_pwd'])." ".escapeshellcmd($CONF['db_db']);
}

##############################

//$mysqldump = "mysqldump --login-path=geograph_live -hdb-master-pvt geograph_live"; //--login-path avoids specifing a password n command line
#login-path setup via mysql_config_editor on TEA, see http://stackoverflow.com/questions/20751352/suppress-warning-messages-using-mysql-from-within-terminal-but-password-written


$mysqldump = "mysqldump $cred";



if ($count = getOne("SELECT COUNT(distinct table_name) FROM information_schema.tables LEFT JOIN _tables USING (table_name) WHERE table_schema = DATABASE() AND method IS NULL AND (backup != 'N' OR backup is null)")) {
	if ($param['dry']) {
		print "#WARNING: There are $count tables in {$CONF['db_db']}, not yet audited\n";
	} else {
		$r = print_r(getAll("SELECT TABLE_NAME,TABLE_ROWS,DATA_LENGTH,CREATE_TIME,UPDATE_TIME FROM information_schema.tables LEFT JOIN _tables USING (table_name) WHERE table_schema = DATABASE() AND method IS NULL AND (backup != 'N' OR backup is null)"),true);
		 mail('geobackup@barryhunter.co.uk',"[{$CONF['db_db']}] Tables not yet audited","There are $count tables in {$CONF['DB_DB']}, not yet audited; goto http://data.geograph.org.uk/bbackup/audit.php\n\n$r");
	}
}

$where = array();
$where[] = "method IS NOT NULL";
$where[] = "backup != 'N'";
$where[] = "TABLE_TYPE != 'VIEW'";
$where[] = "UPDATE_TIME > coalesce(DATE_ADD(backedup, INTERVAL maxage DAY), '0000-00-00 00:00:00') ";

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

print "$sql\n";

//todo, deal with special rules for 'type's
//eg rule for secondry, that we dont back them EVERY time. ?
//"UPDATE_TIME > backedup" takes care of never backed up automatically.


foreach(getAll($sql) as $row) {
	$table = $row['table_name'];
	$method = $row['method'];

	$backedup = getOne("SELECT NOW()");
	//create the date before dumping the table, so updates happen after are caught in next backip

	//$ext = ($row['sensitive'] == 'Y')?'.sql.gz.gpg':'.sql.gz';

	$ext = '.sql.*'; // we now have .sql.gz, .sql.gz.gpg and .sql.xz, .sql.xz.gpg !!

	$filename = date('Y-m-d-H')."_$table.sql.xz";

#################################################################
# if Sharded, then need to save a 'schema' file :)

	if ($row['shard'] && empty(glob("$folder$table/*$ext"))) { //todo, also check if CREATE_TIME is later than schema file??
			$dir = "$folder$table/";
			if (!file_exists($dir))
				command("mkdir -p $dir");
			$file = $filename;
			command(dump_command("$mysqldump $table --no-data","$dir$file",$row['sensitive']));

			//todo, also for sharded backups, might want to periodically, look for old shards that can be purged (exist on disk, but no longer in table), eg rolling log file, like event table. 
	}

#################################################################
# Special Shard mode, where we dont bother checking for updated shared, assume only last shard ever updated.

	if ($row['shard'] && preg_match('/append only shard using (\w+)/',$row['method'],$m)) {

		$info['primary'] = $m[1];
		$shard = $row['shard'];

		//todo, as we assume is append only, we COULD perhaps do this??
		//$col = range(0,intval(get("SELECT MAX(id) FROM $table")/$shard),1)
		// in theory, a sparse array better with group by (could compare TABLE_ROWS vs AUTO_INCREMENT ??)
		// whereas a dense array, use use neive list.

		$sql = "SELECT {$info['primary']} DIV $shard AS shard FROM `$table` GROUP BY {$info['primary']} DIV $shard";
		$col = getCol($sql,true);
		if (empty($col)) {
			if (!$param['dry']) {
	                        mail('geobackup@barryhunter.co.uk',"[{$CONF['db_db']}] Backup Failure for $table", "Failed to get Shards $sql" );
        	        }
			print "ERROR: Failed to get Shards $sql\n";
			continue; //skip rest of interation
		}

		/////////////////////
		// we first need to find the last written folder, and empty it, because it could be incomplete. if same as last, will be rewritten anyway!

		$lastwritten = -1;
		foreach ($col as $one) {
			$dir = "$folder$table/shard$shard-$one/";
			if (!empty(glob("$dir/*$ext")))
				$lastwritten = $one;
		}
		if ($lastwritten>-1) {
			$dir = "$folder$table/shard$shard-$lastwritten/";
			command("rm $dir*$ext");
		}

		/////////////////////
		// now write any shards needed.

		$last = end($col);
		foreach ($col as $one) {
			$dir = "$folder$table/shard$shard-$one/";

			//only need shards NOT yet backed up, and the last always needs writing
			if (empty(glob("$dir/*$ext")) || $one == $last) { //todo. if there is a file, check its size (ie 'find -name "*$ext" -not -empty')

				$start = ($one*$shard);
				$end = (($one*$shard)+($shard-1));
				$file = preg_replace('/\./',".$start-$end.",$filename,1);
				$where = "--where='{$info['primary']} BETWEEN $start AND $end' --skip-comments --no-create-info";

				if (file_exists($dir)) {
					//append only shard - we CAN delete the older backups
					command("rm $dir*$ext");
				} else {
					command("mkdir -p $dir");
				}
				command(dump_command("$mysqldump $table $where","$dir$file",$row['sensitive']));
			}
		}

#################################################################
# Special Shard mode, were we have a timestamp column!

	} else	if ($row['shard'] && preg_match('/^shard using (\w+) when (\w+)/',$row['method'],$m)) {

		$info['primary'] = $m[1];
		$info['timestamp'] = $m[2];
		$shard = $row['shard'];

		$sql = "SELECT {$info['primary']} DIV $shard AS shard, MAX({$info['timestamp']}) AS updated FROM `$table` GROUP BY {$info['primary']} DIV $shard";
		$rows = getAll($sql);

		$dumped = 0;
		foreach ($rows as $r) {
			$one = $r['shard'];
			$dir = "$folder$table/shard$shard-$one/";

			if ($r['updated'] > $row['backedup']) {

				$start = ($one*$shard);
				$end = (($one*$shard)+($shard-1));
				$file = preg_replace('/\./',".$start-$end.",$filename,1);
				$where = "--where='{$info['primary']} BETWEEN $start AND $end' --skip-comments --no-create-info";

				if (file_exists($dir)) {
					//todo - rotation, eg delete the 5th oldest backup? 
				} else {
					command("mkdir -p $dir");
				}
				command(dump_command("$mysqldump $table $where","$dir$file",$row['sensitive']));
				$dumped++;
			}
		}
		if (!$dumped) {
			print "#Note, No updated shards for $table\n";
			continue;
		}

#################################################################
# Neive Shard mode, have to check against last backup to see if worth keeping!

	} else	if ($row['shard'] && $row['sensitive'] != 'Y' && preg_match('/^shard using (\w+)$/',$row['method'],$m)) {

		$info['primary'] = $m[1];
		$shard = $row['shard'];

		$sql = "SELECT {$info['primary']} DIV $shard AS shard FROM `$table` GROUP BY {$info['primary']} DIV $shard";
		$rows = getAll($sql);

		foreach ($rows as $r) {
			$one = $r['shard'];
			$dir = "$folder$table/shard$shard-$one/";

			$start = ($one*$shard);
			$end = (($one*$shard)+($shard-1));
			$file = preg_replace('/\./',".$start-$end.",$filename,1);
			$where = "--where='{$info['primary']} BETWEEN $start AND $end' --skip-comments --no-create-info --dump-date=FALSE";

			if (file_exists($dir)) {
				//todo - rotation, eg delete the 5th oldest backup?

				$files = glob("$dir/*$ext");

				$tmpfile = tempnam('/tmp/',$table.$one);
				command(dump_command("$mysqldump $table $where",$tmpfile,$row['sensitive']));

				if (!empty($files)) {
					sort($files);
					$last = array_pop($files);
					if ($param['dry']) {
						print "if (md5sum($last) != md5sum($tmpfile)) { mv $tmpfile $dir$file }\n";
					} elseif (md5_file($last) == md5_file($tmpfile)) {
						command("unlink $tmpfile");
					} else {
						command("mv $tmpfile $dir$file");
					}
				} else {
					command("mv $tmpfile $dir$file");
				}

			} else {
				command("mkdir -p $dir");

				command(dump_command("$mysqldump $table $where","$dir$file",$row['sensitive']));
			}
		}

#################################################################
# Dump the whole table, simplz!

	} elseif ($row['method'] == 'full') {
		//todo - rotation, eg delete the 5th oldest backup?

		$dir = "$folder$table/";
		if (!file_exists($dir))
			command("mkdir -p $dir");

		$file = $filename;

		command(dump_command("$mysqldump $table","$dir$file",$row['sensitive']));

#################################################################

	} else {
		die("UNKNOWN $method!\n");
	}

############

	if ($param['dry']) {
		print "#WARNING: Integrity of $file NOT checked!\n";
	} else {

		$dir = "$folder$table/";

		$files = `find $dir -mtime -1 -type f`;

		$ok = $bad = $bytes = 0;
		$badoutput = '';
		foreach (explode("\n",$files) as $file) {
			if (empty($file))
				continue;
			$extension = pathinfo($file, PATHINFO_EXTENSION);
			print "$file ---> $extension  [".filesize($file)." bytes]\n";
			$output = -1;
			switch($extension) {
				case 'gpg': $output = `gpg --verify-files $file 2>&1`; break;
				case 'gz': $output = `gunzip -t $file 2>&1`;	 break;
				case 'xz': $output = `unxz -t $file 2>&1`;	 break;
			}
			if ($output == '') {
				$ok++; $bytes += filesize($file);
			} else {
				$bad++; $badoutput .= "$output\n";
			}
		}

		print "OK=$ok, BAD=$bad, bytes=$bytes   [{$row['method']}]\n";

		if ($ok > 0 && $bad == 0 && $bytes > 50) {
			$sql = "UPDATE `_tables` SET `backedup` = '$backedup', bytes_written = $bytes WHERE `table_name` = '".mysqli_real_escape_string($db,$table)."'";

			mysqli_query($db, $sql) or print('<br>Error save: '.mysqli_error($db));

		} elseif (!$param['dry']) {
			$files .= "\n\n".$badoutput;
                        $files .= "\n\n".`find $dir -mtime -1 -type f -ls`;
                        $files .= "\n\n".`find $dir -mtime -1 -type f | xargs md5sum`;
                       	mail('geobackup@barryhunter.co.uk',"[{$CONF['db_db']}] Backup Failure for $table","OK=$ok, BAD=$bad, bytes=$bytes\n".print_r($files,1) );
		}
	}

############

	command(""); //just to output a newline!
}

print "all done\n";

if (false && !$param['dry']) {
	//now loop though and find any updated recently but NOT backed up (ie failures!)

	$data = getAll("SELECT TABLE_NAME AS table_name,TABLE_ROWS,DATA_LENGTH,AVG_ROW_LENGTH,UPDATE_TIME,AUTO_INCREMENT,`ENGINE`,
						type,shard,backup,backedup,backedup_key,method,`sensitive`
					FROM information_schema.tables
									LEFT JOIN _tables USING (table_name)
					WHERE table_schema = DATABASE()
					AND method IS NOT NULL
					AND TABLE_ROWS > 0
					AND backup != 'N' AND UPDATE_TIME > backedup
					AND backedup < DATE_SUB(NOW(),INTERVAL 1 HOUR) ");

	if (!empty($data)) {
		 mail('geograph@barryhunter.co.uk',"[{$CONF['db_db']}] Backup Failures",print_r($data,1) );
	}
}

#################################################################

function command($cmd,$debug = false) {
	global $param,$cred;
	if ($param['dry'] || $debug) {
		print "$cmd\n";
		//print str_replace($cred,'...',$cmd)."\n";
	} elseif ($cmd) {
		print "#$cmd\n";
		print `$cmd`;
	}
}

function dump_command($dump,$file,$sensitive = null) {
	//we now use xz rather than gzip, because files are about half the size!!
	if (false && $sensitive == 'Y') { //this server doesnt have gpg setup
		return "$dump | xz | gpg --trust-model always --encrypt --recipient 'Geograph' > $file.gpg";
	} else {
  		return "$dump | xz > $file";
	}
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

