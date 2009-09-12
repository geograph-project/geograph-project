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

$USER->mustHavePerm("admin");

$smarty = new GeographPage;

ini_set("display_errors",1);
error_reporting(E_ALL ^ E_NOTICE);

$smarty->assign("page_title",'System Status');
$smarty->display('_std_begin.tpl');
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
	

print "<h2>Main Database Status ";
print "<tt>{$CONF['db_user']}@{$CONF['db_connect']}/{$CONF['db_db']}</tt></h2>";
$db = database_status($DSN);

print "<hr/>";


if (!empty($DSN_READ) && $DSN_READ != $DSN) {
	if ($db) {
	
		?>
		<div id="hidemaster" style="text-align:center"><a href="javascript:void(show_tree('master'));">Show Master Detail</a></div>
		<div id="showmaster" class="interestBox" style="display:none">
		<?
			print_r($row = $db->getRow("SHOW MASTER STATUS"));
		?>
		</div>
		<?
		
		$master = $row['File'].'|'.$row['Position'];
		
	}
	print "<hr/>";

	print "<h2>Slave Database Status ";
	print "<tt>{$CONF['db_read_user']}@{$CONF['db_read_connect']}/{$CONF['db_read_db']}</tt></h2>";
	if (database_status($DSN_READ)) {

		?>
		<div id="hideslave" style="text-align:center"><a href="javascript:void(show_tree('slave'));">Show Slave Detail</a></div>
		<div id="showslave" class="interestBox" style="display:none">
		<?
			print_r($row = $db->getRow("SHOW SLAVE STATUS"));
		?>
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
	print "<h2>Second Database Status</h2>";
	print "<tt>{$CONF['db_user2']}@{$CONF['db_connect2']}/{$CONF['db_db2']}</tt>";
	$db = database_status($DSN2);
		
} else {
	print "<h4 style='color:gray'>no second database</h4>";
}
print "<hr/>";


	
if ($memcache->valid) {
	print "<h2>Overview Memcache Statistics</h2>";
	memcache_status();
} else {
	print "<h1>Memcache NOT valid</h1>";
	print "<p>This might be deliberate but certainly not recommended</p>";
}
print "<hr/>";


if (!empty($CONF['sphinx_host'])) {
	print "<h2>Sphinx connection</h2>";
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
	print "Threads_connected: ".$data['Threads_connected'].", ";
	
	
	
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

?>
