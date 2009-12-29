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

$db = NewADOConnection($GLOBALS['DSN']);


if (isset($_POST['submit'])) {

	$updates= $_POST;
	unset($updates['submit']);
	foreach ($updates as $key => $value) {
		if (is_array($value)) {
			$updates[$key] = implode(',',$value);
		}
	}

	if (empty($updates['user_id'])) {
		unset($updates['user_id']);
		$db->Execute('INSERT INTO user SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
		$i = $db->Insert_ID();
		if (!empty($updates['realname'])) {
			$db->Execute(sprintf("insert into user_change set 
				user_id = %d,
				field = 'realname',
				value = %s
				",
				$this->user_id,
				$db->Quote($updates['realname'])
				));
						
		}
		if (!empty($updates['nickname'])) {
			$db->Execute(sprintf("insert into user_change set 
				user_id = %d,
				field = 'nickname',
				value = %s
				",
				$this->user_id,
				$db->Quote($updates['nickname'])
				));
		}
		
	} else {
		$i = intval($updates['user_id']);
		unset($updates['user_id']);
		
		$old = $db->getRow("SELECT user_id,realname,nickname,email FROM user WHERE user_id = $i");
		
		$db->Execute('UPDATE user SET `'.implode('` = ?,`',array_keys($updates)).'` = ? WHERE user_id='.$i,array_values($updates));
		
		
		if ($old['email'] != $updates['email']) {

			$db->Execute("insert into user_emailchange ".
				"(user_id, oldemail,newemail,requested,status,completed)".
				"values(?,?,?,now(),'completed',now())",
			array($i, $old['email'], $updates['email']));
		}
		if ($old['realname'] != $updates['realname']) {
			$db->Execute(sprintf("insert into user_change set 
				user_id = %d,
				field = 'realname',
				value = %s",
				$i,
				$db->Quote($updates['realname'])
				));
				
			$db->Execute(sprintf("update gridimage_search set 
				realname = %s
				where user_id = %d
				and credit_realname = 0 ", //ensures specifically credited ones arent changed
				$db->Quote($updates['realname']),
				$i
				));	
			
		}
		if ($old['nickname'] != $updates['nickname']) {
			$db->Execute(sprintf("insert into user_change set 
				user_id = %d,
				field = 'nickname',
				value = %s",
				$i,
				$db->Quote($updates['nickname'])
				));
		}		
	}	
	
	$profile=new GeographUser($i);
	$profile->_forumUpdateProfile();
	
	$smarty->assign_by_ref("i",$i);
	
} else {
	$i = intval($_GET['id']);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$row = $db->getRow("SELECT * FROM user WHERE user_id = $i");
	$smarty->assign_by_ref("row",$row);
	$smarty->assign_by_ref("id",$i);
	
	$desc = $db->getAssoc("DESCRIBE user");
	
	
	foreach ($desc as $col => $data) {
		if (preg_match('/(set|enum)\(\'(.*)\'\)/',$data['Type'],$m)) {
			$desc[$col]['values'] = array_combine(explode("','",$m[2]),explode("','",$m[2]));
			$desc[$col]['multiple'] = ($m[1] == 'set')?' multiple':'';
			if ($m[1] == 'set') {
				$desc[$col]['multiple'] = ' multiple';
				$row[$col] = explode(',',$row[$col]);
			}
		} elseif (preg_match('/(char|int)\((\d+)\)/',$data['Type'],$m)) {
			$desc[$col]['size'] = min(60,$m[2]);
			$desc[$col]['maxlength'] = $m[2];
		}
	}

	$smarty->assign_by_ref("desc",$desc);
	
	$map = array();
	$smarty->assign_by_ref("map",$map);	
	
}



$smarty->display('admin_user.tpl');
exit;
?>