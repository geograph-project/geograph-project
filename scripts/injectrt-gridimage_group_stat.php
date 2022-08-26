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

$cwd = getcwd();

############################################
//these are the arguments we expect

$param=array(
    'host'=>false, //override mysql host
    'cluster'=>'manticore',
    'tmpfile'=>'gridimage_group_stat.rt',
    'execute'=>false,
);

$ABORT_GLOBAL_EARLY=1; //avoids global.inc.php auto connecteding to redis to with "$memcache" variable

chdir(__DIR__);
require "./_scripts.inc.php";

############################################
//connect first to the REAL primary

$db_primary = GeographDatabaseConnection(false);

############################################
// then connect to whatever replica we can

$host = empty($CONF['db_read_connect'])?$CONF['db_connect']:$CONF['db_read_connect'];
if ($param['host']) {
    $host = $param['host'];
}
fwrite(STDERR,date('H:i:s')."\tUsing db server: $host\n");
$DSN_READ = str_replace($CONF['db_connect'],$host,$DSN);

//we've setup $DSN_READ, using $param[host] even if isn't a db_read_connect
$db = GeographDatabaseConnection(true);

$crit = "-h$host -u{$CONF['db_user']} -p{$CONF['db_pwd']} {$CONF['db_db']}";

############################################


/*
		$sql = '
		select null as gridimage_group_stat_id, grid_reference, label
			, count(*) as images, count(distinct user_id) as users
			, count(distinct imagetaken) as days, count(distinct year(imagetaken)) as years, count(distinct substring(imagetaken,1,3)) as decades
			, min(submitted) as created, max(submitted) as updated, gridimage_id
			, SUBSTRING_INDEX(SUBSTRING_INDEX(GROUP_CONCAT(submitted ORDER BY submitted),\',\',2),\',\',-1) AS `second`
			, avg(wgs84_lat) as wgs84_lat, avg(wgs84_long) as wgs84_long
		from gridimage_group inner join gridimage_search using (gridimage_id)
		where label not in (\'(other)\',\'Other Topics\') and grid_reference like \'{$prefix}%\' and reference_index = {$reference_index}
		group by grid_reference, label having images > 1 order by null';


		//may as well just create the table fresh - incase schema changed!
		if (false && $db->getCol("SHOW TABLES LIKE 'gridimage_group_stat'")) {
			$this->Execute("CREATE TABLE IF NOT EXISTS gridimage_group_stat_tmp LIKE gridimage_group_stat");
			$this->Execute("TRUNCATE TABLE gridimage_group_stat_tmp");
		} else {
			$this->Execute("DROP TABLE IF EXISTS `gridimage_group_stat_tmp`");
			$prefix = array('prefix'=>'XX','reference_index'=>999); //will never match anything!
			$this->Execute('create table gridimage_group_stat_tmp ( gridimage_group_stat_id int unsigned auto_increment primary key, index(grid_reference) ) '.
				preg_replace_callback('/\{\$(\w+)\}/', function($m) use ($prefix) { return $prefix[$m[1]]; }, $sql)) or die($db->ErrorMsg());
		}

		$prefixes = $db->GetAll("select prefix,reference_index from gridprefix where landcount > 0 ");
		foreach ($prefixes as $prefix) {
			$this->Execute('insert into gridimage_group_stat_tmp '.preg_replace_callback('/\{\$(\w+)\}/', function($m) use ($prefix) { return $prefix[$m[1]]; }, $sql));
		}

php fakedump/fakedump.php ... geograph_staging "select '' as id,grid_reference,label,count(*) as images,group_concat(gridimage_id) as image_ids from gridimage_group inner join gridimage_search using (gridimage_id) where label != 'Other Topics' group by grid_reference,label" 
gridimage_group_stat --schema=rt > gridimage_group_stat.rt


*/


		$sql = '
		select \'\' as id, grid_reference, label
			, count(*) as images, count(distinct user_id) as users
			, group_concat(gridimage_id) as image_ids
		from gridimage_group inner join gridimage_search using (gridimage_id)
		where label not in (\'(other)\',\'Other Topics\') and grid_reference like \'{$prefix}%\' and reference_index = {$reference_index}
		group by grid_reference, label having images > 1 order by null';

############################################
//setup the schema

chdir($cwd);

if (file_exists("{$param['tmpfile']}.schema")) {
	$cmd = "cp {$param['tmpfile']}.schema {$param['tmpfile']}";
} else {
	//not perfect, is creating a mysql schema, rather than index
	$prefix = array('prefix'=>'XX','reference_index'=>999); //will never match anything!

	$query = preg_replace_callback('/\{\$(\w+)\}/', function($m) use ($prefix) { return $prefix[$m[1]]; }, $sql);

	$cmd = "php fakedump/fakedump.php $crit ".escapeshellarg(trim(preg_replace('/\s+/',' ',$query)))." gridimage_group_stat --schema=rt > {$param['tmpfile']}";
}
$cmd = "echo '' > {$param['tmpfile']}"; //... using injectrt.php can directly create the schema!
print "$cmd\n\n";
if ($param['execute'])
         passthru($cmd);

############################################
//dump the data

$prefixes = $db->GetAll("select prefix,reference_index from gridprefix where landcount > 0");
foreach ($prefixes as $idx => $prefix) {
	$query = preg_replace_callback('/\{\$(\w+)\}/', function($m) use ($prefix) { return $prefix[$m[1]]; }, $sql);

	//$cmd = "php fakedump/fakedump.php $crit ".escapeshellarg(trim(preg_replace('/\s+/',' ',$query)))." gridimage_group_stat --schema=0 --extended=1 --complete=1 >> {$param['tmpfile']}";

	$schema = (!$idx)+0; //only on first!
	$limit = 100000000; //use really high limit, rather than relying on =0 as unlimited, as that runs piecemeal, that wont work with this group by query!
	$cmd = "php scripts/injectrt.php --config={$param['config']} --host=$host --select=".escapeshellarg(trim(preg_replace('/\s+/',' ',$query)))." --index=gridimage_group_stat --schema=$schema --limit=$limit --cluster=0 --drop >> {$param['tmpfile']}"; //will add to cluster at end!

	print "$cmd\n\n";
        if ($param['execute'])
                passthru($cmd);
}

if (!empty($param['cluster'])) {
	$cmd = 'echo "ALTER CLUSTER '.$param['cluster'].' ADD gridimage_group_stat;" >> '.$param['tmpfile'];
	print "$cmd\n\n";
        if ($param['execute'])
                passthru($cmd);
}

############################################

//get the last date from database we connected to... (eg it could be a lagging replica!)
$row = $db->getRow("SELECT grid_reference, last_grouped FROM gridsquare ORDER BY last_grouped desc LIMIT 1");

$bits = explode('.',$CONF['manticorert_host']);
$sql = "REPLACE INTO sph_server_index SET index_name = 'gridimage_group_stat', server_id = '{$bits[0]}', last_indexed = '{$row['last_grouped']}', updated=NOW()";
fwrite(STDERR, "\nRun this on the database: $sql;\n");
//we dont run it here, as it probably should be run on the primary, not the slave read above!

if ($param['execute'] > 1)
        $db_primary->Execute($sql);

############################################

//use stderr, so if piping output to sh to execute above commands, stil doesnt run this one!
$cmd = "cat {$param['tmpfile']} | sprt";
fwrite(STDERR, "$cmd\n");

if ($param['execute'] > 1)
         passthru($cmd);

############################################
