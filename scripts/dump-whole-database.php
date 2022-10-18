<?php

//output folder
$folder = "/mnt/efs/dump-whole-database";

include "public_html/showcase/includes/database.php";

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

##########################################

$bits = explode(".",$_SERVER['CONF_DB_CONNECT']);
$bits[0] = $argv[1];
$db_host = implode('.',$bits);
$db_user = $_SERVER['CONF_DB_USER'];
$db_passwd = $_SERVER['CONF_DB_PWD'];
$db_name = $_SERVER['CONF_DB_DB'];

$crit = "-h$db_host -u$db_user -p$db_passwd";

print "$crit\n";
$db = mysqli_connect($db_host, $db_user, $db_passwd, $db_name) or die("Unable to Connect to $db_host\n");

$progress = !posix_isatty(STDOUT);
$execute = in_array('-e',$argv);
$limit = 10;
if (preg_match('/-l\s*(\d+)/',implode(' ',$argv),$m))
	$limit = $m[1];

##########################################

$basedate = getOne("select date(max(UPDATE_TIME)) from `information_schema`.`tables` WHERE ENGINE in ('innodb','myisam') and table_schema = 'geograph_live'");

$where = array();
$where[] = "ENGINE IN ('InnoDB', 'MyISAM', 'Aria')"; //mainly want to exlucde views, mergeism etc. Dont think used Aria, but just in case! - final iS NULL is just to exclude derived tables!
$where[] = "TABLE_TYPE = 'BASE TABLE'";
$where[] = "TABLE_SCHEMA NOT IN ('mysql','information_schema','geograph_tmp')";

if (in_array('-d',$argv)) {
	//dont do this everytime but can sometimes do it!
	// the DATA_LENGTH is there, bewucase wanting to dump all the small tables in case, the large ones we know dont contain an 'unique' data.
	$where[] = "type = 'derivied'";
	$where[] = "DATA_LENGTH < 100000000";
	$filtered=1;
} else {
	$where[] = "_tables.table_name IS NULL"; //this excludes derived tables, because of the join condition
}

if (preg_match('/ -s([<>=]+\d+)/',implode(' ',$argv),$m)) {
        $where[] = "DATA_LENGTH {$m[1]}";
	$filtered=1;
}

if (preg_match('/ -t(\w+)/',implode(' ',$argv),$m)) {
        $where[] = "tables.table_name = '{$m[1]}'";
	$filtered=1;
}

##########################################

if (in_array('-n',$argv)) {
	$where = array("NOT (".implode(" AND ",$where).")"); //invert!
}

$query = "SELECT TABLE_SCHEMA,tables.TABLE_NAME,TABLE_TYPE,ENGINE,TABLE_ROWS,DATA_LENGTH,CREATE_TIME,UPDATE_TIME
	 FROM information_schema.tables
		LEFT JOIN geograph_live._tables ON (tables.TABLE_NAME = _tables.table_name AND type = 'derivied')
	 WHERE ".implode(" AND ",$where);

print "## ".implode(" AND ",$where)."\n;";

        $result = mysqli_query($db, $query) or print('<br>Error: '.mysqli_error($db));
        if (!mysqli_num_rows($result)) {
                die("$query;\nno rows\n");
        }
	print "## Found ".mysqli_num_rows($result)." tables to dump\n";

if (in_array('-n',$argv)) {
	$row = mysqli_fetch_assoc($result);
	print implode("\t",array_keys($row))."\n";
	do {
		print implode("\t",$row)."\n";

	} while($row = mysqli_fetch_assoc($result));
	exit;
}

##########################################################################

	$c = 1;
        while($row = mysqli_fetch_assoc($result)) {
		$date = $row['UPDATE_TIME'];
		/* if (empty($date) && $row['DATA_LENGTH'] > 1000000) { //might as well jump dump small ones!
			fwrite(STDERR, print_r($row,true));
			fwrite(STDERR, "Unable to dump\n");
			die();
		} */
		if (empty($date))
			$date = $basedate;

		$date = preg_replace('/[^\d]/','-',$date);
		$filename = "$folder/{$row['TABLE_SCHEMA']}/{$row['TABLE_NAME']}/{$date}-{$row['TABLE_NAME']}.mysql.xz";

		if (file_exists($filename) && filesize($filename) != 32) { //32 is failed/empty xz file
			print "# Skipping $filename\n";
			continue;
		}

		$dir = dirname($filename);
		if (!is_dir($dir))
			mkdir($dir, null, true);

		#####################################

		$db = escapeshellarg($row['TABLE_SCHEMA']);
		$table = escapeshellarg($row['TABLE_NAME']);
		$cmd = "mysqldump --opt --skip-comments $crit $db $table | xz > $filename";


		if ($execute || $progress)
			fwrite(STDERR, "Dumping.$c.. {$row['TABLE_SCHEMA']}/{$row['TABLE_NAME']}/$date [{$row['DATA_LENGTH']} bytes] ...\n");
		if ($execute) {
			$exit_code = null; //as passed by refernce need to initialze it
			passthru($cmd, $exit_code);
		} else
			print "$cmd\n";

		#####################################

		if (!empty($exit_code)) {
			print "# Error! mysqldump returned non-zero exit code: $exit_code\n";
			//todo, maybe should delete the file as it might be currupt or partial!
			//we at least dont 'touch' it, so that it a marker that it potentially currupt or partial
		} else {
			if (strlen($date) == 10)
				$date .="000000"; //add fake time (touch doesnt except just a date)
			$cmd = "touch $filename -t ".preg_replace('/(\d{2})$/','.\1',preg_replace('/[^\d]/','',$date));
			if ($execute)
				passthru($cmd);
			else
				print "$cmd\n\n";
		}

		#####################################
		if ($c==$limit)
			exit;
		if (!empty($killed))
                	die("##killed\n");
		$c++;
        }

##########################################################################

if (file_exists("fakedump/fakedump.php") && empty($filtered)) {
	$filename = "$folder/{$basedate}-tables.mysql.xz";

        if (!file_exists($filename)) {
		//fakedump needs to create a temporally table, so give it $db_name, rather than trying to create in information_schema
		$cmd = "php fakedump/fakedump.php $crit $db_name 'select * from information_schema.tables' tables";
		$cmd .= " | xz > $filename";

                if ($execute)
                        passthru($cmd);
                else
                        print "$cmd\n\n";
	}
}

if ($execute && empty($filtered)) {
	touch("$folder/$db_host.done");
}

print "find $folder -type f -name '*.xz' -size 32c\n";
print "# If any found delete them and repeat dump command!\n";

print "php scripts/send-to-s3-recursive.php --config=live --store=percona/\n";

print "php scripts/test-s3-glob.php /mnt/s3/backups/dump-whole-database/ --config=live --d=:\n";
