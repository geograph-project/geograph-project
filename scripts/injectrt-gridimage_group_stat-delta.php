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
    'host'=>false, //override mysql host
    'date'=>false, //date filter
    'cluster'=>'manticore',
    'tmpfile'=>'/tmp/gridimage_group_stat-delta.rt',
    'execute'=>false,
);

$ABORT_GLOBAL_EARLY=1; //avoids global.inc.php auto connecteding to redis to with "$memcache" variable

chdir(__DIR__);
require "./_scripts.inc.php";

if (empty($CONF['manticorert_host']))
	die("No manticorert host\n");

############################################
//connect first to the REAL primary

$db_primary = GeographDatabaseConnection(false);

//can be running in many containers, so make sure only one running at a time
                if (!$db_primary->getOne("SELECT GET_LOCK('".basename($argv[0])."',5)")) {
			die("unable to get lock\n");
                }

if ($param['execute'] > 1) {
	//insert a FAKE log (just so we can plot on a graph ;)
	$db_primary->Execute("INSERT INTO event_log SET
        event_id = 0,
        logtime = NOW(),
        verbosity = 'trace',
        log = 'running scripts/".basename($argv[0])."',
        pid = 33");
}

if (empty($param['date'])) {
	$bits = explode('.',$CONF['manticorert_host']);
	$param['date'] = $db_primary->getOne($sql = "SELECT last_indexed FROM sph_server_index WHERE index_name = 'gridimage_group_stat' AND server_id = '{$bits[0]}'");

	if (empty($param['date'])) {
		die("#ERROR: unable to find last index date\n");
	}
}

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

		$sql = '
		select \'\' as id, grid_reference, label
			, count(*) as images, count(distinct user_id) as users
			, group_concat(gridimage_id) as image_ids
		from gridimage_group inner join gridimage_search using (gridimage_id) inner join gridsquare using (grid_reference)
		where label not in (\'(other)\',\'Other Topics\') and last_grouped > \'{$date}\'
		group by grid_reference, label having images > 1 order by null';


		//the opitimzer is choosig to run gridimage_group first, but its where caluse is not very selective. Its more efficent to full scan gridsquare!
			//for 21k rows, takes just over 2 minutes for the above query, using STRAIGHT_JOIN about 12 seconds!
                $sql = '
                select \'\' as id, grid_reference, label
                        , count(*) as images, count(distinct user_id) as users
                        , group_concat(gridimage_id) as image_ids
                from gridsquare STRAIGHT_JOIN gridimage_search USING (grid_reference) STRAIGHT_JOIN gridimage_group using (gridimage_id)
                where label not in (\'(other)\',\'Other Topics\') and last_grouped > \'{$date}\'
                group by grid_reference, label having images > 1 order by null';

############################################
// delete the old data

print "# Checking for records since {$param['date']}\n";
$recordSet = $db->Execute("select grid_reference from gridsquare where last_grouped > '{$param['date']}'");
if (!$recordSet->RecordCount()) {
	die("# Nothing to do. No new squares available\n");
}
if ($param['execute']) {
	$c = 0;
	$h = fopen($param['tmpfile'], 'w');
	while (!$recordSet->EOF) {
        	$row =& $recordSet->fields;
		if (!($c%100)) {
			if ($c)
				fwrite($h, ");\n");
			$sep = "DELETE FROM ".($param['cluster']?"{$param['cluster']}:":'')."gridimage_group_stat WHERE grid_reference IN (";
		}
		fwrite($h, $sep.$db->Quote($recordSet->fields['grid_reference']));

        	$recordSet->MoveNext();
		$sep = ",";
		$c++;
	}
	fwrite($h, ");\n\n");
	fclose($h);
} else {
	print "#would write DELETE commands to {$param['tmpfile']}, deleting ".$recordSet->RecordCount()." squares\n\n";
}
$recordSet->Close();

############################################
//dump the data

	$query = preg_replace_callback('/\{\$(\w+)\}/', function($m) use ($param) { return $param[$m[1]]; }, $sql);

	//$cmd = "php fakedump/fakedump.php $crit ".escapeshellarg(trim(preg_replace('/\s+/',' ',$query)))." gridimage_group_stat --schema=0 --extended=1 --complete=1 >> {$param['tmpfile']}";

	$limit = 100000000; //use really high limit, rather than relying on =0 as unlimited, as that runs piecemeal, that wont work with this group by query!
	$cmd = "php ".__DIR__."/injectrt.php --config={$param['config']} --host=$host --cluster={$param['cluster']} --select=".escapeshellarg(trim(preg_replace('/\s+/',' ',$query)))." --index=gridimage_group_stat --schema=0 --limit=$limit >> {$param['tmpfile']}";

	print "$cmd\n";
	if ($param['execute'])
		passthru($cmd);

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
$cmd = "cat {$param['tmpfile']} | mysql  -h{$CONF['manticorert_host']} -P{$CONF['sphinx_portql']} --default-character-set=utf8 -A";
fwrite(STDERR, "$cmd\n");

if ($param['execute'] > 1) {
	if (strlen(`whereis mysql`) > 1000)
		 passthru($cmd);
	else {
		//alas, the production pods dont have the 'mysql' binary installed (and injectrt.php can't run the commands directly yet!)
		//luckly, we know the files have a fairly strict format. dont have to worry about ';\n' the middle of strings (as we know all encoded), and dont have to worry about comments etc in the script file!
		$rt = GeographSphinxConnection('manticorert');

		$h = fopen($param['tmpfile'],'r');
		$buffer = '';
		while($h && !feof($h)) {
			$line = fgets($h);
			if (substr_compare($line, ";\n", -2) ===0) {
				$buffer .= rtrim($line,";\n");
				//print "EXECUTE: ".preg_replace('/\s+/',' ',$buffer)."\n\n";
				$rt->Execute($buffer);
				//print "affected: ".$rt->Affected_Rows()."\n";
				$buffer = '';
			} else {
				$buffer .= rtrim($line,"\n");
			}
		}
		fclose($h);
		if (!empty($buffer)) {
			//print "EXECUTE: ".preg_replace('/\s+/',' ',$buffer)."\n\n";
			$rt->Execute($buffer);
			//print "affected: ".$rt->Affected_Rows()."\n";
		}
	}
}

############################################

$db_primary->Execute("DO RELEASE_LOCK('".basename($argv[0])."')");


