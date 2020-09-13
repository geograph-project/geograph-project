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

//$USER->hasPerm("moderator") || $USER->mustHavePerm("admin");

$smarty = new GeographPage;

$smarty->assign("page_title",'System Status');
$smarty->display('_std_begin.tpl',md5($_SERVER['PHP_SELF']));

if (function_exists('apc_store') && apc_fetch('lag_warning')) {
        print "<p>Warning: lag_warning flag is set currently (slave not used)</p>";
}

##################################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$data = array();
$data['master'] = array_merge($db->getRow("SHOW MASTER STATUS"), $db->getRow("SHOW SLAVE STATUS"));

$pos = $data['master']['Position'];

##################################################

if (!empty($DSN_READ) && $DSN_READ != $DSN) {
	$db=NewADOConnection($DSN_READ);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	$data['slave'] = array_merge($db->getRow("SHOW MASTER STATUS"), $db->getRow("SHOW SLAVE STATUS"));
	foreach($db->getAll("SHOW PROCESSLIST") as $row) {
		if ($row['User'] == 'system user' && !empty($row['db'])) { //the IO thread, doesnt select a DB, but the SQL thread (we interested in!) does
			$data['slave']['State'] = $row['State'];
			$data['slave']['Info'] = $row['Info'];
		}
	}

}

##################################################

if (!empty($CONF['db_read_connect2'])) {
	$DSN_READ = str_replace($CONF['db_read_connect'],$CONF['db_read_connect2'],$DSN_READ);

	$db=NewADOConnection($DSN_READ);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	$data['slave2'] = array_merge($db->getRow("SHOW MASTER STATUS"), $db->getRow("SHOW SLAVE STATUS"));
	foreach($db->getAll("SHOW PROCESSLIST") as $row) {
		if ($row['User'] == 'system user' && !empty($row['db'])) { //the IO thread, doesnt select a DB, but the SQL thread (we interested in!) does
			$data['slave2']['State'] = $row['State'];
			$data['slave2']['Info'] = $row['Info'];
		}
	}
}

##################################################

$keys = explode(" ", "File Position  ".
"Slave_IO_State Slave_IO_Running Master_Log_File Read_Master_Log_Pos  ".
"Slave_SQL_Running Seconds_Behind_Master Relay_Master_Log_File Exec_Master_Log_Pos Last_SQL_Error Last_SQL_Error_Timestamp State Info  ".
"Relay_Log_File Relay_Log_Pos  Last_Error ");

$desc = array(
	"Master_Log_File"=>"The name of the master binary log file from which the I/O thread is currently reading.",
	"Read_Master_Log_Pos"=>"The position in the current master binary log file up to which the I/O thread has read.",
	"Relay_Log_File"=>"The name of the relay log file from which the SQL thread is currently reading and executing.",
	"Relay_Log_Pos"=>"The position in the current relay log file up to which the SQL thread has read and executed.",
	"Relay_Master_Log_File"=>"The name of the master binary log file containing the most recent event executed by the SQL thread.",
	"Exec_Master_Log_Pos"=>"The position in the current master binary log file to which the SQL thread has read and executed, marking the start of the next transaction or event to be processed. ",
	"Seconds_Behind_Master"=>"Measures the time difference in seconds between the slave SQL thread and the slave I/O thread.",
);

##################################################

print "<table cellspacing=0 cellpadding=4 border=1>";

foreach ($keys as $key) {
	$style = ($key == 'Seconds_Behind_Master')?'background-color:yellow':'';
	print "<tr style=$style><th>$key</th>";

	foreach ($data as $server => $row) {
		if (isset($row[$key])) {
			$value = $row[$key];
			$color = '#eee';
			if ($key == 'Seconds_Behind_Master' && $value === '0') {
				$color = 'lightgreen';
			} elseif (preg_match('/_Pos$/',$key) && $value == $pos) {
				$color = 'lightgreen';
			}
			print "<td width=20% style=background-color:$color>".htmlentities($value);
		        if ($key == 'Seconds_Behind_Master' && $value > 0) {
		                if ($value > 3600) printf(',  %.1f Hours',$value/3600);
		                if ($value > 60 && $value < 3600) printf(',  %.1f Minutes',$value/60);
		        }

			print "</td>";
		} else {
			print "<td></td>";
		}
	}
	if (!empty($desc[$key])) {
		print "<td style=font-size:x-small>".htmlentities($desc[$key])."</td>";
	}
}

##################################################

if (!empty($DSN_READ) && strpos($DSN_READ,'rds.amazonaws.com') === FALSE) {
	print "<tr style=font-size:x-small><th>cmd to restart replication at the last successful execution (eg if the relay log, or the slave IO thread failed) </th>";
	$key = 'Relay_Master_Log_File';
        foreach ($data as $server => $row) {
                if (!empty($row[$key])) {
			$sql = "CHANGE MASTER TO MASTER_LOG_FILE = '{$row['Relay_Master_Log_File']}', MASTER_LOG_POS = {$row['Exec_Master_Log_Pos']}";

                        print "<td width=20% style=background-color:#eee>".htmlentities($sql)."</td>";
                } else {
                        print "<td></td>";
                }

	}
}

##################################################

print "</table>";



$smarty->display('_std_end.tpl');
