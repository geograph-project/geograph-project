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

$smarty->assign("page_title",'System Status');
$smarty->display('_std_begin.tpl');
flush();
	
print "<hr/>";
	

print "<h2>Main Database Status</h2>";
print "<tt>{$CONF['db_user']}@{$CONF['db_connect']}/{$CONF['db_db']}</tt>";
database_status($DSN);
print "<hr/>";


if (!empty($DSN_READ) && $DSN_READ != $DSN) {
	print "<h2>Slave Database Status</h2>";
	print "<tt>{$CONF['db_read_user']}@{$CONF['db_read_connect']}/{$CONF['db_read_db']}</tt>";
	database_status($DSN_READ);
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
	#memcache_status();
} else {
	print "<h1>Memcache NOT valid</h1>";
}
print "<hr/>";


if (!empty($CONF['sphinx_host'])) {
	print "<h2>Sphinx connection</h2>";
	$sphinx = new sphinxwrapper('test');
	
	$ids = $sphinx->returnIds(1,'gaz');
	
	print "<p>Ids returned: ".count($ids)."</p>";
	
} else {
	print "<h4 style='color:gray'>Sphinx not enabled</h4>";
}
print "<hr/>";



$smarty->display('_std_end.tpl');
exit;

####################################

function database_status($DSN) {
	global $CONF;
	
	$db=NewADOConnection($DSN);
	
	if (!$db) {
		print "<h1>Unable to connect</h1>";
		print "<p>".$db->ErrorMsg()."</p>";
	}
	
	$data = $db->getAssoc("SHOW STATUS");
	
	if ($db->ErrorNo()) {
		print "<h1>Error</h1>";
		print "<p>".$db->ErrorMsg()."</p>";
	}
	
	
	#print_r($data);
	print "<p>Uptime: ".$data['Uptime']."</p>";
	print "<p>Threads_connected: ".$data['Threads_connected']."</p>";
	
	
	
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

		print "<p>Created temp table.</p>";
	}
	
	
	return $db;
}

function memcache_status() {
	global $memcache;
	
	$a = $memcache->getExtendedStats();
	$a = array_reverse($a);
	print "<table border=1 cellspacing=0>";
		print "<tr>";
		print "<th>server</th>";
		foreach ($a as $name => $row) {
			print "<th>{$name}</th>";
		}
		print "</tr>";
		$keys = array_keys($a);
		$keys = array_keys($a[$keys[0]]);
		foreach ($keys as $id => $column) {
			print "<tr>";
			print "<th>$column</th>";
			foreach ($a as $name => $row) {
				print "<td align=\"right\">{$a[$name][$column]}</td>";
			}
			print "</tr>";
		}
	print "</table>";
}

####################################

?>
