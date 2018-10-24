<?php

##################################

chdir(__DIR__."/../");

@include "libs/conf/channel-islands.geographs.org.conf.php";

chdir("public_html/dumps/");

$database = "-h".escapeshellarg($CONF['db_connect'])." -u".escapeshellarg($CONF['db_user'])." -p".escapeshellarg($CONF['db_pwd'])." ".escapeshellarg($CONF['db_db']);

##################################

//just a check to make sure really do want to run them commands
$execute = !empty($argv[1]);

$date = time() - (3600*24); //if file is already more recent than this, its skipped

//if run in interactive terminal print some debugging
if (posix_isatty(STDOUT)) {
	$STDERR = fopen('php://stderr', 'w+');
} else {
	$STDERR = false;
}

##################################
# lookup last modeification date of tables (although only works if using MyISAM tables!) 

$stat = array();
foreach(explode("\n",`echo "select table_name,update_time from information_schema.tables where TABLE_SCHEMA = DATABASE()" | mysql $database`) as $line) {
	$bits = explode("\t",$line);
	if (!empty($bits[1]) && $bits[1] != 'NULL')
		$stat[$bits[0]] = $bits[1];
}

##################################
# the dump file definitions

$data = array();

$data[] = array(
        'create' => 'CREATE TABLE user_dev (user_id INT UNSIGNED PRIMARY KEY) SELECT user_id,realname,nickname,date(signup_date) as signup_date,home_gridsquare,deceased_date,rights FROM user INNER JOIN user_stat USING(user_id)'
);

$data[] = array(
        'create' => 'CREATE TABLE gridimage_base (gridimage_id INT UNSIGNED PRIMARY KEY) SELECT gridimage_id,user_id,realname,title,moderation_status,imagetaken,grid_reference,x,y,wgs84_lat,wgs84_long,reference_index FROM gridimage_search',
        'tsv' => true
);

$data[] = array(
        'create' => 'CREATE TABLE gridimage_extra (gridimage_id INT UNSIGNED PRIMARY KEY) SELECT gridimage_id,ftf,submitted,upd_timestamp,credit_realname,seq_no FROM gridimage_search',
        'tsv' => true
);

$data[] = array(
        'create' => "CREATE TABLE gridimage_geo (gridimage_id INT UNSIGNED PRIMARY KEY) SELECT gridimage_id,nateastings,natnorthings,natgrlen,viewpoint_eastings,viewpoint_northings,viewpoint_grlen,view_direction,use6fig FROM gridimage WHERE moderation_status IN ('geograph','accepted')",
        'tsv' => true
);

$data[] = array(
        'create' => "CREATE TABLE gridimage_text (gridimage_id INT UNSIGNED PRIMARY KEY) SELECT gridimage_id,imageclass,comment FROM gridimage_search",
        'tsv' => true
);

$data[] = array(
        'create' => "CREATE TABLE gridimage_hash (gridimage_id INT UNSIGNED PRIMARY KEY) SELECT gridimage_id,SUBSTRING(MD5(CONCAT(gridimage_id,user_id,'".$CONF['photo_hashing_secret']."')),1,8) AS hash FROM gridimage_search",
        'file' => '.gridimage_hash.'.md5($CONF['register_confirmation_secret']).'.mysql.gz'
);

$data[] = array(
        'tables' => 'user_stat',
        'tsv' => true
);

$data[] = array(
        'tables' => 'gridsquare'
);

$data[] = array(
        'tables' => 'gridimage_search'
);

$data[] = array(
        'tables' => 'gridprefix'
);

$data[] = array(
        'tables' => 'category_stat',
        'tsv' => true
);

$data[] = array(
        'tables' => 'hectad_complete'
);

$data[] = array(
        'tables' => 'gridimage_size'
);

##################################

function cmd($cmd) {
	global $STDERR,$execute;
	if ($STDERR) fwrite($STDERR, "$cmd\n");
	if ($execute) passthru($cmd);
}

foreach ($data as $row) {

###########
# dump virtual tables, that are created via mysql statement
#  todo, can also be rewritten to use https://github.com/barryhunter/fakedump rather than creating a temporally table.

        if (!empty($row['create'])) {
                if (preg_match('/CREATE TABLE (\w+) /',$row['create'],$m)) {
                        $table = $m[1];
                        if (empty($row['file'])) {
                                $row['file'] = "$table.mysql.gz";
                        }
                        $file = $row['file'];
			
			$source='';
			if (preg_match('/FROM (\w+)\b/',$row['create'],$m))
                                $source = $m[1];

			if (file_exists($file)) {
				if (!empty($source) && !empty($stat[$source]) && date('Y-m-d H:i:s',filemtime($file)) >= $stat[$source])
        		                continue;
	
	                        if (@filemtime($file) > $date)
        	                        continue;
			}

                        if ($STDERR) fwrite($STDERR, "# running create statement\n");
                        cmd("echo \"".str_replace('!','\\!',$row['create'])."\" | mysql $database");
                        cmd("mysqldump $database $table --skip-comments | gzip --rsyncable > $file");

			if (!empty($source) && !empty($stat[$source]))
				cmd("touch $file -t ".date('YmdHi.s',strtotime($stat[$source])));

                        if (!empty($row['tsv'])) {
                                if ($STDERR) fwrite($STDERR, "# dumping tsv\n");
                                cmd("echo \"SELECT * FROM $table\" | mysql $database | gzip --rsyncable > $table.tsv.gz");

				if (!empty($source) && !empty($stat[$source]))
					cmd("touch $table.tsv.gz -t ".date('YmdHi.s',strtotime($stat[$source])));
                        }
                        if ($STDERR) fwrite($STDERR, "# dropping table\n");
                        cmd("echo \"DROP TABLE $table\" | mysql $database");


                } else {
                        die("unable to find table {$row['create']}\n\n");
                }

###########
# simple dump of complete tables

        } elseif (!empty($row['tables'])) {
                $tables = $row['tables'];
                $bits = explode(" ",$row['tables']);
                $table = $bits[0];
                if (empty($row['file'])) {
                        $row['file'] = "$table.mysql.gz";
                }
                $file = $row['file'];

		if (file_exists($file)) {
			if (isset($stat[$table]) && date('Y-m-d H:i:s',filemtime($file)) >= $stat[$table])
				continue;
	                if (filemtime($file) > $date)
        	                continue;
		}

                cmd("mysqldump $database $tables --skip-comments | gzip --rsyncable > $file");

		if (!empty($stat[$table]))
			cmd("touch $file -t ".date('YmdHi.s',strtotime($stat[$table])));

                if (!empty($row['tsv']) && count($bits) == 1) {
                        if ($STDERR) fwrite($STDERR, "# dumping tsv\n");
                        cmd("echo \"SELECT * FROM $table\" | mysql $database | gzip --rsyncable > $table.tsv.gz");

			if (!empty($stat[$table]))
				cmd("touch $table.tsv.gz -t ".date('YmdHi.s',strtotime($stat[$table])));
                }


#######
#dump multiple tables into one file (eg if have 'relation' tables, like tags)

        } elseif (!empty($row['dump1'])) {
                if (empty($row['file']))
                        die("no file for {$row['create']}\n\n");
                $file = $row['file'];

                if (@filemtime($file) > $date)
                        continue;

                $file = str_replace('.gz','',$file);
                $c = 1;
                while(!empty($row['dump'.$c])) {
                        $tables = $row['dump'.$c];
                        cmd("mysqldump $database $tables --skip-comments >> $file");
                        $c++;
                }
                if ($STDERR) fwrite($STDERR, "# gzipping file\n");
                cmd("gzip --rsyncable $file -f");

#######
        } else {
                die("unknown profile\n\n");
        }

	if ($STDERR) fwrite($STDERR, "\n");
}

if ($STDERR) fwrite($STDERR, "# All done!\n\n");

