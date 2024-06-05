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

if (strlen(`whereis xz`) < 5) die("xz is not installed\n");
if (strlen(`whereis gpg`) < 6) die("gpg is not installed\n");
if (!preg_match('/Geograph <secure@geograph.org.uk>/',`gpg --list-keys`))
	die("gpg key not installed");
if (strlen(`whereis mysqldump`) < 12) die("mysqldump is not installed\n");
if (!extension_loaded('mysqli')) die("mysqli extension not available\n");



//these are the arguments we expect
$param=array(
	'dir'=>'/var/www/geograph',		//base installation dir
	'config'=>'staging.geograph.org.uk', //effective config

	'days'=>0,
	'type'=>'',
	'size'=>0,
	'table'=>'',

	'dry'=>true,
	'factor'=>false,
	's3'=>false,
	'r2'=>true,
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

if (stream_resolve_include_path('conf/'.$_SERVER['HTTP_HOST'].'.conf.php')) {
	//even if using CONF_PROFILE, there MAY be a specific config file to use
	require('conf/'.$_SERVER['HTTP_HOST'].'.conf.php'); //this file will STILL need to use CONF_PROFILE

} elseif (!empty($_SERVER['CONF_PROFILE'])) {
	require('conf/'.$_SERVER['CONF_PROFILE'].'.conf.php');
}

$db = mysqli_connect($CONF['db_connect'], $CONF['db_user'], $CONF['db_pwd'], $CONF['db_db']);

function mysqli_reconnect() {
	global $db,$CONF;
	if (!mysqli_ping($db) && !ini_get('mysqli.reconnect')) {
		mysqli_close($db);
		$db = mysqli_connect($CONF['db_connect'], $CONF['db_user'], $CONF['db_pwd'], $CONF['db_db']) or die(mysqli_error($db));
	}
}

        declare(ticks = 1);
        $killed = false;

        pcntl_signal(SIGINT, "signal_handler");

        function signal_handler($signal) {
                global $killed;
                if ($signal == SIGINT) {
                        print "Caught SIGINT\n";
                        $GLOBALS['killed']=1; // we dont exit here, rather let the script kill the script at the right moment, using $killed
                }
        }

##############################
// include the S3 class if possible
// need to use Geographs 'wrapper' as it manages getting a STS token

@include("geograph/filesystem.class.php");

if (class_exists('FileSystem')) {
	$filesystem = new FileSystem();

	//will have already loaded the S3 class!

	/* -- this doesn't work, S3 class uses singletons, so can't connect to two providers.

	if (!empty($CONF['r2_endpoint'])) {
		$r2 = new S3($CONF['r2_access_key'], $CONF['r2_secret_key'], true, str_replace('https://','',$CONF['r2_endpoint']), 'auto');

		if (method_exists($r2,'setSignatureVersion'))
		        $r2->setSignatureVersion('v4'); //r2 only supports v4
	} */
}

##############################
//setup creditals

$folder =  $param['dir'].'/backups/by-table/';

if ($param['dry'] && $param['dry'] !== '2') {
	$cred = "-h".preg_replace('/\..*/','...',$CONF['db_read_connect'] ?? $CONF['db_connect'])." ".escapeshellcmd($CONF['db_db']);
} elseif (!empty($CONF['db_read_connect'])) {
	#$cred = "-h".escapeshellcmd($CONF['db_read_connect'])." -u".escapeshellcmd($CONF['db_read_user'])." -p".escapeshellcmd($CONF['db_read_pwd'])." ".escapeshellcmd($CONF['db_read_db']);
	$cred = "-h".escapeshellcmd($CONF['db_read_connect'])." -u".escapeshellcmd($CONF['db_user'])." -p".escapeshellcmd($CONF['db_pwd'])." ".escapeshellcmd($CONF['db_read_db']);
} else {
	$cred = "-h".escapeshellcmd($CONF['db_connect'])." -u".escapeshellcmd($CONF['db_user'])." -p".escapeshellcmd($CONF['db_pwd'])." ".escapeshellcmd($CONF['db_db']);
}

//$cred = "--login-path=geograph_live -hdb-master-pvt geograph_live"; //--login-path avoids specifing a password n command line
#login-path setup via mysql_config_editor on TEA, see http://stackoverflow.com/questions/20751352/suppress-warning-messages-using-mysql-from-within-terminal-but-password-written

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
// save schema file

$dir = $param['dir'].'/backups/schema/'.date('Y/m/');
$file = date('Y-m-d')."-schema.sql.xz";

if (!file_exists($dir.$file)) {
         if (!is_dir($dir)) {
                  command("mkdir -p $dir");
         }
	//dump, all tables. Skipping Dump date, is incase it already uploaded, so that hash will still match (if no changes!)
         command(mysqldump("--no-data --dump-date=FALSE","$dir$file",'N'));
}

##############################
//workout list of tables

if ($count = getOne("SELECT COUNT(distinct table_name) FROM information_schema.tables LEFT JOIN _tables USING (table_name) WHERE table_schema = DATABASE() AND method IS NULL AND (backup != 'N' OR backup is null)")) {
	if ($param['dry']) {
		print "#WARNING: There are $count tables in {$CONF['db_db']}, not yet audited\n";
	} else {
		$r = print_r(getAll("SELECT TABLE_NAME,TABLE_ROWS,DATA_LENGTH,CREATE_TIME,UPDATE_TIME FROM information_schema.tables LEFT JOIN _tables USING (table_name) WHERE table_schema = DATABASE() AND method IS NULL AND (backup != 'N' OR backup is null)"),true);
		 mail('geobackup@barryhunter.co.uk',"[{$CONF['db_db']}] Tables not yet audited","There are $count tables in {$CONF['DB_DB']}, not yet audited; goto https://www.geograph.org.uk/admin/tables-audit.php\n\n$r");
	}
}

$where = array();
$where[] = "method IS NOT NULL";
$where[] = "backup != 'N'";
$where[] = "TABLE_TYPE != 'VIEW'";
$where[] = "TABLE_ROWS > 0";

if ($param['days'] > 0 && $param['size'])
	$where[] = "backedup < DATE_SUB(NOW(), INTERVAL {$param['days']} DAY)";
else
	$where[] = "coalesce(UPDATE_TIME,fake_updated) > coalesce(DATE_ADD(backedup, INTERVAL maxage DAY), '0000-00-00 00:00:00')";

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
print "## ".implode(' AND ',$where)."\n";

####################
// loop though rules

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

print "\n$gray$table --> $filename   [$method]$white\n";

#################################################################
# if Sharded, then need to save a 'schema' file :)

	if ($row['shard'] && empty(glob("$folder$table/"."*$ext"))) { //todo, also check if CREATE_TIME is later than schema file??
		//todo, we are going to have to check S3, the file - even if dumped before - wont be on local disk!
			$dir = "$folder$table/";
			if (!file_exists($dir))
				command("mkdir -p $dir");
			$file = $filename;
			command(mysqldump("$table --no-data","$dir$file",$row['sensitive']));

			//todo, also for sharded backups, might want to periodically, look for old shards that can be purged (exist on disk, but no longer in table), eg rolling log file, like event table. 
	}

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

		if ($row['DATA_LENGTH'] > 1000*1000*1000) {
			$sql = "SELECT MIN({$info['primary']}) AS min, MAX({$info['primary']}) AS max FROM `$table`";
			$rr = getRow($sql);
			$col = range(intval($rr['min']/$shard),intval($rr['max']/$shard),1);
		} else {
			$sql = "SELECT {$info['primary']} DIV $shard AS shard FROM `$table` GROUP BY {$info['primary']} DIV $shard";
			print "# $sql;\n";
			$col = getCol($sql,true);
		}

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
			if (!empty(glob("$dir/"."*$ext")))
				$lastwritten = $one;
		}
		if ($lastwritten>-1) {
			$dir = "$folder$table/shard$shard-$lastwritten/";
			command("rm $dir*$ext");
		}

		/////////////////////
		// now write any shards needed.

		$next = true; //has the effect of always writing the last one (first in the reverse list!)
		$curtail = false;
		foreach (array_reverse($col) as $one) {
			$dir = "$folder$table/shard$shard-$one/";

print "$dir ($next)\n";

			//only need shards NOT yet backed up, and the last always needs writing
			if ($next || (empty(glob("$dir/"."*$ext")) && !getS3Files("by-table/$table/shard$shard-$one/") )) {
						 //todo. if there is a file, check its size (ie 'find -name "*$ext" -not -empty')

				$start = ($one*$shard);
				$end = (($one*$shard)+($shard-1));
				$file = preg_replace('/\./',".$start-$end.",$filename,1);
				$where = "--where='{$info['primary']} BETWEEN $start AND $end' --skip-comments --no-create-info";

				if (file_exists($dir)) {
					//append only shard - we CAN delete the older backups
					command("rm $dir*$ext");
					$next = false; //as already dumped this shard, dont need the next one
				} else {
			/*
					if ($next && getS3Files("by-table/$table/shard$shard-$one/"))
						$next = false; //need this because if next set, and folder doesnt exist locally, then will never check remote!
						//importantly will still dump this shard (as it might be a partial shard)
						//but wont nessarelly bother with next
			*/
					command("mkdir -p $dir");
					//next remains true, the next shard will still need writing, as it might of been incomplete last time.
				}
				command(mysqldump("$table $where","$dir$file",$row['sensitive']));
			}


//TODO, if NOT doing a full dump?
			//actully once, found a 'remote' shard, while we still dumped THIS one, we can now ABORT!
			//if ($next && getS3Files("by-table/$table/shard$shard-$one/"))
			if (getS3Files("by-table/$table/shard$shard-$one/"))
				break;

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

if ($table == 'gridimage_tag' || $table == 'gridimage_snippet_real') {
	$sql = str_replace('GROUP BY','WHERE gridimage_id < 4294967296 GROUP BY',$sql);
}

		print "# $sql; && updated> {$row['backedup']}\n";
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
				command(mysqldump("$table $where","$dir$file",$row['sensitive']));
				$dumped++;
			}
		}
		if (!$dumped) {
			print "$gray#Note, No updated shards for $table$white\n";
			continue;
		}

#################################################################
# Neive Shard mode, have to check against last backup to see if worth keeping!

	} else	if ($row['shard'] && $row['sensitive'] != 'Y' && preg_match('/^shard using (\w+)$/',$row['method'],$m)) {
		if (empty($row['TABLE_ROWS']))
			continue;

		$info['primary'] = $m[1];
		$shard = $row['shard'];

		$sql = "SELECT {$info['primary']} DIV $shard AS shard FROM `$table` GROUP BY {$info['primary']} DIV $shard";
		print "# $sql; && md5 diff\n";
		$rows = getAll($sql);

		foreach ($rows as $r) {
			$one = $r['shard'];
			$dir = "$folder$table/shard$shard-$one/";

			$start = ($one*$shard);
			$end = (($one*$shard)+($shard-1));
			$file = preg_replace('/\./',".$start-$end.",$filename,1);
			$where = "--where='{$info['primary']} BETWEEN $start AND $end' --skip-comments --no-create-info --dump-date=FALSE";

			$latest = "{$dir}latest.md5"; //need to be careful that wouldnt match $ext!

			if (file_exists($dir)) {
				//todo - rotation, eg delete the 5th oldest backup?

				if (file_exists($latest)) {
					$tmpfile = tempnam('/tmp/',$table.$one);
                                        command(mysqldump("$table $where",$tmpfile,$row['sensitive']));

					list($hash,$other) = preg_split("/\s+/",file_get_contents($latest));
					if ($param['dry']) {
                                                print "if ($hash != md5sum($tmpfile)) { mv $tmpfile $dir$file }\n";
                                        } elseif ($hash == trim(md5_file($tmpfile))) {
                                                command("unlink $tmpfile");
                                        } else {
                                                command("mv $tmpfile $dir$file");
                                                command("md5sum $dir$file > $latest");
                                        }
				} else {
					$files = glob("$dir/"."*$ext");

					$tmpfile = tempnam('/tmp/',$table.$one);
					command(mysqldump("$table $where",$tmpfile,$row['sensitive']));

					if (!empty($files)) {
						sort($files);
						$last = array_pop($files);
						if ($param['dry']) {
							print "if (md5sum($last) != md5sum($tmpfile)) { mv $tmpfile $dir$file }\n";
						} elseif (md5_file($last) == md5_file($tmpfile)) {
							command("unlink $tmpfile");
						} else {
							command("mv $tmpfile $dir$file");
							command("md5sum $dir$file > $latest");
						}
					} else {
						command("mv $tmpfile $dir$file");
						command("md5sum $dir$file > $latest");
					}
				}

			} else {
				command("mkdir -p $dir");

				command(mysqldump("$table $where","$dir$file",$row['sensitive']));
				command("md5sum $dir$file > $latest");
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

		command(mysqldump("$table","$dir$file",$row['sensitive']));

#################################################################

	} else {
		print "UNKNOWN $method! for $table\n";
		continue;
	}

############

	if ($param['dry']) {
		print "$gray#WARNING: Integrity of $file NOT checked!$white\n";
	} else {
		$dir = "$folder$table/";

		$files = `find $dir -mtime -1 -type f`;

		$ok = $bad = $bytes = 0;
		$badoutput = '';
		foreach (explode("\n",$files) as $file) {
			if (empty($file))
				continue;
			$extension = pathinfo($file, PATHINFO_EXTENSION);
			if ($extension == 'md5')
				continue; //ignore the latest.md5 file!
			print "$file ---> $extension  [".filesize($file)." bytes]\n";
			$output = -1;
			switch($extension) {
				case 'gpg': $output = `gpg --verify-files $file 2>&1`; break; //todo, only verifes the encrypted file, not the contents!
				case 'gz': $output = `gunzip -t $file 2>&1`;	 break;
				case 'xz': $output = `unxz -t $file 2>&1`;	 break;
			}
			if ($output == '') {
				$ok++; $bytes += filesize($file);
			} else {
				$bad++; $badoutput .= "$output\n";
			}
		}

		print "{$gray}OK=$ok, BAD=$bad, bytes=$bytes   [{$row['method']}]{$white}\n";

		if ($ok > 0 && $bad == 0 && $bytes > 50) {
			$sql = "UPDATE `_tables` SET `factor` = `factor` + 1, `backedup` = '$backedup', bytes_written = $bytes WHERE `table_name` = '".mysqli_real_escape_string($db,$table)."'";

			mysqli_query($db, $sql) or print('<br>Error save: '.mysqli_error($db));

		} elseif (!$param['dry']) {
			$files .= "\n\n".$badoutput;
                        $files .= "\n\n".`find $dir -mtime -1 -type f -ls`;
                        $files .= "\n\n".`find $dir -mtime -1 -type f | xargs md5sum`;
                       	mail('geobackup@barryhunter.co.uk',"[{$CONF['db_db']}] Backup Failure for $table","OK=$ok, BAD=$bad, bytes=$bytes\n".print_r($files,1) );
		}



		if (!empty($_SERVER['BASE_DIR']) && file_exists($_SERVER['BASE_DIR'].'/shutdown-sential')) {
 		   break; //break, not exit, sowe can still try sending to S3 (otherwise the backups might be lost!)
		}
	}

############

	command(""); //just to output a newline!

        if (!empty($killed))
                 die("killed\n");
}

#################################################################
//Send to R2

//note, we send to r2 FIRSTR, as S3 upload uses 'move'.
// whereas r2-tool, only delets local file once found (ie deletes it the following day!)

if (!empty($table) && !empty($CONF['r2_endpoint']) && $param['r2']) {
        $bucket = $CONF['r2_backup_bucket'];
	$destination = "backups/by-table/"; //used both as a local path, and bucket prefix

	//r2-tool uses 'find' to upload files 'recusively'

	//already hardcoded to use '*.sql.*'
        $cmd = "php scripts/r2-tool.php --config={$param['config']} --function=upload --bucket=$bucket --path=".escapeshellarg($destination);
	command($cmd);
}

#################################################################
//Send to S3

if (!empty($table) && !empty($CONF['s3_backups_bucket_path']) && $param['s3']) {

	$ext = '.sql.*'; // we now have .sql.gz, .sql.gz.gpg and .sql.xz, .sql.xz.gpg !!

	$folder = $param['dir'].'/backups/';
	//send-to-s3 cant do recursive, so we need to find each folder!
	//specify by-table, schema directly, because may be other clutter in backups folder, AND might be a symlink to external storage
	//.. atternative could enabling folling symlinks, and 'grep -v staging-' ??
	$h = popen("find {$folder}by-table/ {$folder}schema/ -type f -printf '%h\\n' | uniq", 'r');
	while ($h && !feof($h)) {
		$line = trim(fgets($h));
		if (empty($line))
			continue;

		$dest = str_replace($folder,'/mnt/s3/backups/', $line); //this fake folder is known by the filesystem class!

		$cmd = "php ".__DIR__."/send-to-s3.php --src=$line/ --dst=$dest/ --include=\"*$ext\" --config={$param['config']} --move=1 --dry=".$param['dry'];
		command($cmd);
	}
}

#################################################################
//stats

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

print "## all done\n";

#################################################################
#################################################################

//note this will fethc ALL files under a path - in a loop - so make sure specify a deep path!!)
function getS3Files($path) {
	global $filesystem,$r2,$CONF,$param;
	$list = false;

	if (!empty($filesystem) && !empty($filesystem->buckets['/mnt/s3/backups/'])) {

		list($bucket, $destination) = $filesystem->getBucketPath("/mnt/s3/backups/$path");
//		print "list($bucket, $destination)\n";
		$list = $filesystem->getBucket($bucket, $destination, null, null);
	}

	/*
	if (empty($list) && !empty($r2) && !empty($destination)) { //can just reuse the same path!
		$bucket = $CONF['r2_backup_bucket'];

		print "list($bucket, $destination)\n";
		$list = $r2->getBucket($bucket, $destination, null, null);
S3::$securityToken = $bef;
	} */

	if (empty($list) && !empty($CONF['r2_endpoint'])) {
		$bucket = $CONF['r2_backup_bucket'];

		//as $filesystem, and $r2 doesnt play well together, do the r2 request in seperate process
		//best to to r2, sa the real S3 uses tokens, so needs state between requests. 
		//print "list($bucket, $destination)\n";
		$cmd = "php scripts/r2-tool.php --config={$param['config']} --function=getbucket --bucket=$bucket --path=".escapeshellarg($destination);
		$list = json_decode(`$cmd`);
	}

	return $list;
}

#################################################################

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

function getRow($query,$non_fatal = false) {
	global $db;
	mysqli_reconnect();

	$result = mysqli_query($db, $query) or $non_fatal or die("Error getRow: ".mysqli_error($db)."\n$query;\n");
	if ($result && mysqli_num_rows($result)) {
		return mysqli_fetch_assoc($result);
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

