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

($USER->user_id == 2520) || $USER->mustHavePerm("admin");

$smarty = new GeographPage;

$db = NewADOConnection($GLOBALS['DSN']);
if (!$db) die('Database connection failed');

if (!empty($_GET['break'])) {
	$smarty->display('_std_begin.tpl');


	$rows = $db->getAll("select table_name,type,backup,TABLE_ROWS,DATA_LENGTH from _tables inner join information_schema.tables using (table_name) WHERE TABLE_SCHEMA = DATABASE() order by (backup = 'N'), type+0 asc,table_name");

	$last = '';
	foreach($rows as $row) {
		$key= $row['type'].($row['backup']=='N');
		if ($key != $last) {
			if ($last) {
				print "</div>";
			}
			$style= ($row['backup'] =='N')?'background-color:#eee;':'background-color:lightgreen;';

			print "<div style='float:left;width:300px;padding:4px;margin:5px;$style'>";
			print "<b>{$row['type']}</b>";
			if ($row['backup'] =='N')
				print " - not backed up";
			print "<small><br><br></small>";
		}
		$last = $key;
		print "<div style='float:right;text-align:right;color:gray'>".number_format($row['DATA_LENGTH'],0)."</div>";
		print "<tt>{$row['table_name']}</tt><br>";
	}
	print "</div>";
	print "<br style=clear:both>";
	$smarty->display('_std_end.tpl');

	exit;
}


	if (isset($_POST['submit'])) {

		$table = $db->Quote($_POST['table']);
		$type = $db->Quote($_POST['type']);
		$backup = $db->Quote($_POST['backup']);
		$sensitive = $db->Quote($_POST['sensitive']);
		$title = $db->Quote($_POST['title']);
		$description = $db->Quote($_POST['description']);
		$sql = "INSERT INTO _tables SET created = NOW(),
			table_name = $table,
			type = $type,
			`backup` = $backup,
			`sensitive` = $sensitive,
			title = $title,
			description = $description
			ON DUPLICATE KEY UPDATE
			type = $type,
			`backup` = $backup,
			`sensitive` = $sensitive,
			title = $title,
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

	if (!empty($_REQUEST['next'])) {
		$smarty->assign('next', $_REQUEST['next']);

		foreach ($arr as $key => $row) {
			if (empty($row[$_REQUEST['next']]) && ($_REQUEST['next'] != 'title' || $row['type'] == 'primary')) {
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

		$row = $db->getRow("SHOW COLUMNS FROM _tables LIKE 'sensitive'");
		preg_match('/\((.*)\)/',$row['Type'],$m);
		$values= explode(',',str_replace("'",'',$m[1]));
		$smarty->assign('sensitives', array_combine($values,$values));

		$smarty->assign_by_ref('arr', $arr[$_GET['table']]);
	} else {
		$smarty->assign_by_ref('arr', $arr);
	}


$smarty->display('admin_tables.tpl');


