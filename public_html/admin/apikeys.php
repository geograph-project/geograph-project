<?php
/**
 * $Project: GeoGraph $
 * $Id$
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

require_once('geograph/global.inc.php');
init_session();

$USER->mustHavePerm("admin");

$smarty = new GeographPage;

$db = NewADOConnection($GLOBALS['DSN']);
if (!$db) die('Database connection failed');  
	
	if ($_POST['submit'] && $_POST['apikey'] && $_POST['id']) {
		//we have a record to insert/update
		$arr = $db->GetRow("select id from apikeys where `apikey` = '{$_POST['apikey']}' and `id` != '{$_POST['id']}'");
		if ($arr['id']) {
			//if we found an id then it must already be in use!
			$arr = $_POST;
			$smarty->assign('message', "ERROR: Key '{$_POST['apikey']}' is already in use");
			$smarty->assign('id', $_POST['id']);
		} else {
			//can go ahead and add it
			
			if ($_POST['id'] == '-new-') {
				//this is a new key so its an insert
				$sql = "INSERT INTO";
				$message .= "<p>The following info has been added:</p><ul>";
				$updates[] = "`added_by` = {$USER->user_id}";
				$updates[] = "`crt_timestamp` = NOW()";
			} else {
				//otherwise we need an update
				$arr = $db->GetRow("select *,INET_NTOA(ip) as `ip_text` from `apikeys` where `id` = {$_POST['id']}");
				$sql = "UPDATE";
				$sql_where = " WHERE id = {$_POST['id']}";
				$message .= "<p>The following info has been updated:</p><ul>";
			}

			//these values shouldnt be added back
			unset($_POST['id']);
			unset($_POST['submit']);

			//ip address requires a special handler
			if ($_POST['ip_text'] != $arr['ip_text']) {
				$updates[] = "`ip` = INET_ATON('{$_POST['ip_text']}')";
				$message .="<li>IP</li>";
			}
			unset($_POST['ip_text']);

			//loop though all and create the update array
			foreach ($_POST as $key => $value) 
				if ($value != $arr[$key]) {
					$updates[] = '`'.$key.'` = '.$db->Quote($value);
					$message .="<li>$key</li>";
				}

			//only need to run the sql if something has updated
			if (count($updates)) {
				$updates[] = "`upd_timestamp` = NOW()";
				$sql .= ' apikeys SET '.implode(',',$updates).$sql_where;

				$db->Execute($sql);

				$message .= "</ul>";
				$smarty->assign('message',  $message);
			}

			//just need a list of all keys
			$arr = $db->GetAssoc("select * from apikeys");
		}
	} elseif ($_GET['id']) {
		//load the info for editing the record
		if ($_GET['id'] != '-new-') {
			$arr = $db->GetRow("select *,INET_NTOA(ip) as ip_text from apikeys where id = {$_GET['id']}");
		}
		$smarty->assign('id', $_GET['id']);
	} else {	
		//just need a list of all keys
		$arr = $db->GetAssoc("select * from apikeys");
	}

	$smarty->assign('arr', $arr);


$smarty->display('admin_apikeys.tpl');


	
?>
