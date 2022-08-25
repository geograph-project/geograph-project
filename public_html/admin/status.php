<?php
/**
 * $Project: GeoGraph $
 * $Id: viewsearches.php,v 1.5 2005/08/06 12:35:01 barryhunter Exp $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2008 Barry Hunter (geo@barryhunter.co.uk)
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

require_once('geograph/global.inc.php');
init_session();

$USER->hasPerm("moderator") || $USER->mustHavePerm("admin");

$smarty = new GeographPage;

//ini_set("display_errors",1);
//error_reporting(E_ALL ^ E_NOTICE);

if (function_exists('apc_store') && !empty($_GET['clear'])) {
	print apc_delete('lag_warning');
}

if (function_exists('apc_store') && isset($_GET['cool'])) {
	if (empty($_GET['cool'])) {
		print apc_delete('lag_cooloff');
	} else {
		print apc_store('lag_cooloff',1,intval($_GET['cool']));
	}
	if (!empty($_GET['q'])) {
		$hostname=trim(`hostname`);
		print ". Host = $hostname";
		exit;
	}
}


$smarty->assign("page_title",'System Status');
$smarty->display('_std_begin.tpl',md5($_SERVER['PHP_SELF']));
?>
<style type="text/css">
	#maincontent h1 {
		background-color:pink;
		color:black;
		padding:10px;
		border:4px solid yellow;
	}
	pre {
		border:1px solid red;
		padding:4px;
		background-color:silver;
	}
	h2 tt {
		font-size:0.6em;
		font-weight:normal;
	}
</style>
<?
flush();

$hostname=trim(`hostname`);
print "Host = $hostname";

print "<hr/>";

/* todo - needs converting to report on filesystem - check AWS creds?
print "<h2>Folders</h2>";

$folders = array();
$folders[] = $_SERVER['DOCUMENT_ROOT']."/geophotos/";
$folders[] = $_SERVER['DOCUMENT_ROOT']."/photos/";
$folders[] = $_SERVER['DOCUMENT_ROOT']."/maps/";
$folders[] = $_SERVER['DOCUMENT_ROOT']."/../rastermaps/";

foreach ($folders as $folder) {
	print "<b>$folder</b>: <tt>";
	print `ls -l $folder | head -1`;
	print "</tt><br/>";
}

print "<hr/>";
*/

$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


print "<h2>Main Database Status ";
print "<tt>{$CONF['db_user']}@{$CONF['db_connect']}/{$CONF['db_db']}</tt></h2>";
$db = database_status($DSN);



if (!empty($DSN_READ) && $DSN_READ != $DSN) {
	if ($db) {

		?>
		<div id="hidemaster" style="text-align:center"><a href="javascript:void(show_tree('master'));">Show Master Detail</a></div>
		<div id="showmaster" class="interestBox" style="display:none"><pre>
		<?
			print_r($row = $db->getRow("SHOW MASTER STATUS"));
		?></pre>
		</div>
		<?

		$master = $row['File'].'|'.$row['Position'];
	}
	print "<hr/>";

	print "<h2>Slave Database Status ";
	print "<tt>{$CONF['db_read_user']}@{$CONF['db_read_connect']}/{$CONF['db_read_db']}</tt></h2>";

if (function_exists('apc_store')) {
	print "lag_warning:".apc_fetch('lag_warning')."<br>";
	print "lag_cooloff:".apc_fetch('lag_cooloff')."<br>";
}
	if ($db = database_status($DSN_READ)) {

		?>
		<div id="hideslave" style="text-align:center"><a href="javascript:void(show_tree('slave'));">Show Slave Detail</a></div>
		<div id="showslave" class="interestBox" style="display:none"><pre>
		<?
			print_r($row = $db->getRow("SHOW SLAVE STATUS"));
		?></pre>
		</div>
		<?

		$slave = $row['Master_Log_File'].'|'.$row['Read_Master_Log_Pos'];
		if ($slave != $master) {
			print "<h1>Slave Read Failure?</h1>";
			print "<pre>Master: $master\nSlave: $slave</pre>";
		}

		$slave = $row['Master_Log_File'].'|'.$row['Exec_Master_Log_Pos'];
		if ($slave != $master) {
			print "<h1>Slave Execute Failure?</h1>";
			print "<pre>Master: $master\nSlave: $slave</pre>";
		}
		if ($row['Last_Error']) {
			print "<pre>Last Error: {$row['Last_Error']}</pre>";
		}
		if ($row['Seconds_Behind_Master']) {
			print "<h1>Seconds Behind Master: {$row['Seconds_Behind_Master']}</h1>";
		}
	}
} else {
	print "<h4 style='color:gray'>no slave database</h4>";
}
print "<hr/>";


if ($DSN2 != $DSN) {
	print "<h2>Second Database Status ";
	print "<tt>{$CONF['db_user2']}@{$CONF['db_connect2']}/{$CONF['db_db2']}</tt></h2>";
	$db = database_status($DSN2);
	print "<hr/>";
}


if (!empty($CONF['redis_host'])) {
	print "<h2>Redis ";
	print "<tt>{$CONF['redis_host']}</tt></h2>";

	if (!empty($memcache->redis)) {
		//redis can be used to power the memcache interface, and so has a connection already
		$info = $memcache->redis->info();
	} else {
		//cope with redis being active, but not used for memcache
		$redis_handler = new Redis();
                $success = $redis_handler->connect($CONF['redis_host'], $CONF['redis_port']);
		$info = $redis_handler->info();
	}

	print "Uptime: {$info['uptime_in_days']} Days, Role: {$info['role']}, Slaves: {$info['connected_slaves']}, Clients: {$info['connected_clients']}, Used Memory: {$info['used_memory_human']}";

	if (!empty($info)) {
		?>
		<div id="hideredis" style="text-align:center"><a href="javascript:void(show_tree('redis'));">Show Redis Detail</a></div>
		<div id="showredis" class="interestBox" style="display:none">
		<?

		print "<table border=1 cellspacing=0>";

		foreach ($info as $key => $value) {
			print "<tr>";
				print "<th>{$key}</th>";
				print "<td>{$value}</td>";
			print "</tr>";
		}
		print "</table>";

		print "</div>";
	}
}

if (!empty($memcache->redis)) {
	print "<small style=color:gray>Redis is powering the memcache interface</small><br>";

	//just add these to the CONF variable for use in the foreach loop below!
	if (!empty($CONF['redis_session_db']))
		@$CONF['memcache']['session']['redis'] = $CONF['redis_session_db'];
	if (!empty($CONF['redis_api_db']))
		@$CONF['memcache']['api']['redis'] = $CONF['redis_api_db'];
	foreach ($CONF['memcache'] as $key => $value) {
		if (isset($value['redis'])) { //has a db!
			$k = "db".$value['redis'];
			if (isset($info[$k]))
				print "&nbsp;$key($k):: {$info[$k]}<br>";
		}
	}

} elseif ($memcache->valid) {
	print "<h2>Overview Memcache Statistics</h2>";
	memcache_status();
} else {
	print "<h1>Memcache NOT valid</h1>";
	print "<p>This might be deliberate but certainly not recommended</p>";
}
print "<hr/>";


if (!empty($CONF['manticorert_host'])) {
        print "<h2>ManticoreRT connection ";
        print "<tt>{$CONF['manticorert_host']}</tt></h2>";
        $rt = GeographSphinxConnection('manticorert');

	$tables = $rt->getCol("SHOW TABLES");
	print "<p>Tables(".count($tables)."): ".implode(", ",$tables);
	$ids = $rt->getCol("SELECT id FROM gaz WHERE MATCH('test')");
	if (!empty($ids) && count($ids)) {
		print "<p>Ids returned: ".count($ids)."</p>";

		$info = array();
		foreach ($rt->getAll("SHOW STATUS") as $row)
			$info[$row['Counter']] = $row['Value'];

		print "<p>Cluster Status: ".$info['cluster_manticore_status'];
		print ", Cluster Indexes: ".$info['cluster_manticore_indexes'];

		?>
		<div id="hidemantrt" style="text-align:center"><a href="javascript:void(show_tree('mantrt'));">Show RT Detail</a></div>
		<div id="showmantrt" class="interestBox" style="display:none">
		<?

		print "<table border=1 cellspacing=0>";
		foreach ($info as $key => $value) {
			print "<tr>";
				print "<th>{$key}</th>";
				print "<td>{$value}</td>";
			print "</tr>";
		}
		print "</table>";

		print "</div>";
	}

	print "<hr/>";
}


if (!empty($CONF['sphinx_host'])) {
	print "<h2>Sphinx/Manticore connection ";
	print "<tt>{$CONF['sphinx_host']}</tt></h2>";
	$sphinx = new sphinxwrapper('test');

	$ids = $sphinx->returnIds(1,'gaz');

	$cl = $sphinx->_getClient();

	if (!empty($ids) && count($ids)) {
		print "<p>Ids returned: ".count($ids)."</p>";
		?>
		<div id="hidesphinx" style="text-align:center"><a href="javascript:void(show_tree('sphinx'));">Show Sphinx Detail</a></div>
		<div id="showsphinx" class="interestBox" style="display:none">
		<?

		print "<table border=1 cellspacing=0>";
		print "<tr>";
		print "<th>server</th>";
			print "<th>{$CONF['sphinx_host']}:{$CONF['sphinx_port']}</th>";
		print "</tr>";
		foreach ($cl->Status() as $row) {
			print "<tr>";
			foreach ($row as $i => $data) {
				print "<td align=\"right\">{$data}</td>";
			}
			print "</tr>";
		}
		print "</table>";

		print "</div>";
	} else {
		print "<h1>".$cl->GetLastError()."</h1>";
	}
} else {
	print "<h4 style='color:gray'>Sphinx not enabled</h4>";
}
print "<hr/>";



$smarty->display('_std_end.tpl');
exit;

####################################

function database_status($DSN) {
	global $CONF;
	static $counter = 1;

	$db=NewADOConnection($DSN);

	if (!$db) {
		print "<h1>Unable to connect</h1>";
		return;
	}

	$data = $db->getAssoc("SHOW STATUS");

	if ($db->ErrorNo()) {
		print "<h1>Error</h1>";
		print "<p>".$db->ErrorMsg()."</p>";
		return;
	}

	#print_r($data);
	print "<p>Uptime: ".$data['Uptime'].", ";
	print "Connections: ".$data['Threads_connected'].", ";

	if ($db && $CONF['db_tempdb']) {
		$table = $CONF['db_tempdb'].".mytmp".uniqid();
		$sql="CREATE TEMPORARY TABLE $table ENGINE HEAP SELECT 1";
		if (!$db->Execute($sql)) {
			print "<h1>Error - unable to create table</h1>";
			print "<p>".$db->ErrorMsg()."</p>";
			return;
		}

		if (!$db->getOne("SELECT * FROM $table")) {
			print "<h1>Error - unable to read back result</h1>";
			print "<p>".$db->ErrorMsg()."</p>";
			return;
		}

		print " Created temp table.";
	}
	print "</p>";


	?>
	<div id="hide<? echo $counter; ?>" style="text-align:center"><a href="javascript:void(show_tree('<? echo $counter; ?>'));">Show Database <? echo $counter; ?> Detail</a></div>
	<div id="show<? echo $counter; ?>" class="interestBox" style="display:none">
	<?

	print "<table border=1 cellspacing=0>";
	foreach ($data as $name => $value) {
		print "<tr>";
		print "<th align=\"left\">{$name}</th>";
		print "<td align=\"right\">{$value}</td>";
		print "</tr>";
	}
	print "</table>";

	print "</div>";
	$counter++;
	return $db;
}

function memcache_status() {
	global $memcache;

	$a = $memcache->getExtendedStats();
	$a = array_reverse($a);
	$keys = array_keys($a);
	$keys = array_keys($a[$keys[0]]);
	?>
		<div id="hidememcache" style="text-align:center">
	<?
	print "<table border=1 cellspacing=0>";
	print "<tr>";
	print "<th>server</th>";
	foreach ($a as $name => $row) {
		print "<th>{$name}</th>";
	}
	print "</tr>";
	$column = 'uptime';
	print "<tr>";
	print "<th>$column</th>";
	foreach ($a as $name => $row) {
		print "<td align=\"right\">{$a[$name][$column]}</td>";
	}
	print "</tr>";
	print "</table>";
	?>
		<p><a href="javascript:void(show_tree('memcache'));">Show Memcache Detail</a></p></div>
		<div id="showmemcache" class="interestBox" style="display:none">
	<?
	print "<table border=1 cellspacing=0>";
		print "<tr>";
		print "<th>server</th>";
		foreach ($a as $name => $row) {
			print "<th>{$name}</th>";
		}
		print "</tr>";

		foreach ($keys as $id => $column) {
			print "<tr>";
			print "<th>$column</th>";
			foreach ($a as $name => $row) {
				print "<td align=\"right\">{$a[$name][$column]}</td>";
			}
			print "</tr>";
		}
	print "</table>";

	print "</div>";
}

####################################

