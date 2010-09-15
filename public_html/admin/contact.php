<?php
/**
 * $Project: GeoGraph $
 * $Id: resubmissions.php 6285 2010-01-10 18:57:32Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Paul Dixon (paul@elphin.com)
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

if (isset($CONF['curtail_level']) && $CONF['curtail_level'] > 5 && strpos($_SERVER['HTTP_REFERER'],'editimage') === FALSE) {
	header("HTTP/1.1 503 Service Unavailable");
	die("the servers are currently very busy - moderation is disabled to allow things to catch up, will be automatically re-enabled when load returns to normal");
}

require_once('geograph/gridimage.class.php');
require_once('geograph/gridsquare.class.php');
require_once('geograph/imagelist.class.php');

init_session();

customGZipHandlerStart();

$USER->mustHavePerm("ticketmod");



$db = NewADOConnection($GLOBALS['DSN']);
if (!$db) die('Database connection failed');   

$smarty = new GeographPage;

if (isset($_REQUEST['inject'])) {
	$USER->mustHavePerm("admin");

	if (!empty($_POST['msg'])) {
	
	
		$updates = array();
		
		$msg=stripslashes(trim($_POST['msg']));
		$msg = str_replace("\r",'',$msg);
		
		
		if (preg_match("/^from\s+([a-zA-Z0-9])+([a-zA-Z0-9\._\-\+])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+/",$msg,$m)) {
			$updates['from'] = preg_replace("/^from\s+/",'',$m[0]);
			$msg = preg_replace("/^from\s+([a-zA-Z0-9])+([a-zA-Z0-9\._\-\+])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+\n/",'',$msg);
		}
		
		$msg = preg_replace("/^to\s+([\w\.@]+[,\n]+)+/m",'',$msg);
		
		if (preg_match("/^date\s+([\w :\.]+)\n/m",$msg,$m)) {
			$updates['created'] = date("Y-m-d H:i:s",strtotime($m[1]));
			$msg = preg_replace("/^date\s+([\w :\.])+\n/m",'',$msg);
		}
		
		if (preg_match("/^subject\s+(.*?)\n/m",$msg,$m)) {
			$updates['subject'] = trim(str_replace('[Geograph]','',$m[1]));
			$msg = preg_replace("/^subject\s+(.*?)\n/m",'',$msg);
		}
		
		$msg = preg_replace("/^hide details\s+(.*?)\n/m",'',$msg);	
		
		if (preg_match('/^Referring page:\s+(.*?)$/m',$msg,$m)) {
			$updates['referring_page'] = $m[1];
			$msg = preg_replace('/^Referring page:\s+(.*?)$/m','',$msg);
		}
		
		if (preg_match('/^User profile:\s+.*\/(\d+)$/m',$msg,$m)) {
			$updates['user_id'] = intval($m[1]);
			$msg = preg_replace('/^User profile:\s+(.*?)$/m','',$msg);
		}
		
		if (preg_match('/^Browser:\s+(.*?)$/m',$msg,$m)) {
			$updates['user_agent'] = $m[1];
			$msg = preg_replace('/^Browser:\s+(.*?)$/m','',$msg);
		}
		
		$msg = preg_replace('/^-{6,}\n*$/m','',$msg);
		
		$updates['msg'] = $msg;

		if (!empty($_POST['open'])) {
			 $updates['status']='open';
		}
		
		$db->Execute('INSERT INTO contactform SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
			
		$smarty->assign("saved",1);
	}

	$smarty->assign("inject",1);

} elseif (!empty($_REQUEST['id']) || !empty($_GET['t'])) {


	if (!empty($_GET['t'])) {
		$ok=false;
		$token=new Token;

		if ($token->parse($_GET['t']) && $token->hasValue("id")) {
			$i = $token->getValue("id");
		}
		if (empty($i)) {
			die("Invalid");
		}
	} else {
		$i = intval($_REQUEST['id']);
	}

	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$row = $db->getRow("SELECT * FROM contactform WHERE contact_id = $i");
	
	if (empty($row)) {
		die("invalid");
	}
	
	if (!empty($_POST['close'])) {
	
		$db->getRow("UPDATE contactform SET status='delt',moderator_id = {$USER->user_id} WHERE contact_id = $i");
		
		header("Location: /admin/contact.php");
		exit;
		
	} elseif (!empty($_POST['open'])) {
		$USER->mustHavePerm("admin");
		
		$db->getRow("UPDATE contactform SET status='open' WHERE contact_id = $i");
		
		header("Location: /admin/contact.php");
		exit;
		
	} elseif (!empty($_POST['dealing'])) {
		
		$db->getRow("UPDATE contactform SET moderator_id = {$USER->user_id} WHERE contact_id = $i");
		
		header("Location: /admin/contact.php");
		exit;
		
	} #elseif ($row['status'] != 'open') {
		$USER->mustHavePerm("admin");
	#}
	
	$smarty->assign_by_ref("row",$row);
	$smarty->assign_by_ref("id",$i);
	
	if (isset($_POST['submit'])) {
	
		$updates= $_POST;
		unset($updates['submit']);
	
		unset($updates['contact_id']);

		$db->Execute('UPDATE contactform SET `'.implode('` = ?,`',array_keys($updates)).'` = ? WHERE contact_id='.$i, array_values($updates));
			
		$smarty->assign("saved",1);
	} else {
	
	
		$desc = $db->getAssoc("DESCRIBE contactform");


		foreach ($desc as $col => $data) {
			if (preg_match('/(set|enum)\(\'(.*)\'\)/',$data['Type'],$m)) {
				$desc[$col]['values'] = array_combine(explode("','",$m[2]),explode("','",$m[2]));
				$desc[$col]['multiple'] = ($m[1] == 'set')?' multiple':'';
			} elseif (preg_match('/(char|int)\((\d+)\)/',$data['Type'],$m)) {
				$desc[$col]['size'] = min(60,$m[2]);
				$desc[$col]['maxlength'] = $m[2];
			}
		}
		$smarty->assign_by_ref("desc",$desc);
	}

} else {
	
	
	if ($USER->hasPerm("admin")) {
	
		$where = "status IN ('new','open')";
		
		
	} else {
		$where = "status = 'open' AND moderator_id IN (0,{$USER->user_id})";
	}
	
	$data = $db->getAll("SELECT * FROM contactform WHERE $where ORDER BY contact_id DESC LIMIT 40");
	
	$smarty->assign_by_ref("data",$data);
}



		
		
		
$smarty->display('admin_contact.tpl',$style);
	
?>
