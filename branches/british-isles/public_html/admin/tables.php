<?php
/**
 * $Project: GeoGraph $
 * $Id: apikeys.php 939 2005-06-29 22:22:57Z barryhunter $
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
	
	if (isset($_POST['submit'])) {
		

		$table = $db->Quote($_POST['table']);
		$type = $db->Quote($_POST['type']);
		$backup = $db->Quote($_POST['backup']);
		$description = $db->Quote($_POST['description']);
		$sql = "INSERT INTO _tables SET created = NOW(),
			table_name = $table,
			type = $type,
			`backup` = $backup,
			description = $description
			ON DUPLICATE KEY UPDATE
			type = $type,
			description = $description";

		$db->Execute($sql);
			
	}
	
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	
	//just need a list of all keys
	$arr = $db->GetAssoc("select * from _tables");
#print "<pre>";
#print_r($arr);
	$arr2 = $db->GetAssoc("show table status");

	foreach ($arr2 as $key => $row) {
		if (isset($arr[$key])) {
			$arr[$key] = $arr[$key]+$row;
		} else {
			$arr[$key] = $row;
		}
	}

	$contents = file_get_contents("../../scripts/backup_geograph_db");
	preg_match_all('/--ignore-table=geograph_live.(\w+)/',$contents,$m);
	foreach ($m[1] as $t) {
		$arr[$t]['skipped'] = 1;
	}

	if (!empty($_REQUEST['next'])) {
		$smarty->assign('next', $_REQUEST['next']);

		foreach ($arr as $key => $row) {
			if (empty($row['type'])) {
				$_GET['table'] = $key;
				break;
			}
		}
	}
	

#print_r($arr);	exit;
	if (isset($_GET['table'])) {
		$smarty->assign('table', $_GET['table']);
		
		$row = $db->getRow("SHOW COLUMNS FROM _tables LIKE 'type'");
		preg_match('/\((.*)\)/',$row['Type'],$m);
		$values= explode(',',str_replace("'",'',$m[1]));
		$smarty->assign('types', array_combine($values,$values));
		
		$row = $db->getRow("SHOW COLUMNS FROM _tables LIKE 'backup'");
		preg_match('/\((.*)\)/',$row['Type'],$m);
		$values= explode(',',str_replace("'",'',$m[1]));
		$smarty->assign('backups', array_combine($values,$values));
		
		$smarty->assign_by_ref('arr', $arr[$_GET['table']]);
	} else {
		$smarty->assign_by_ref('arr', $arr);
	}


$smarty->display('admin_tables.tpl');

