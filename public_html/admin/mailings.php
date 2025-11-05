<?php
/**
 * $Project: GeoGraph $
 * $Id: conversion.php 5502 2009-05-13 14:18:23Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 BArry Hunter (geo@barryhunter.co.uk)
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


$smarty = new GeographPage;

$USER->mustHavePerm("director");

$smarty->display('_std_begin.tpl');

$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

##############################

if (!empty($_GET['mail_id'])) {

	if (!empty($_POST['body'])) {
		if (!empty($db->readonly))
			$db = GeographDatabaseConnection(false);

                $updates = array();
                foreach ($_POST as $key => $value)
                        $updates[$key] = $value;

		if (is_numeric($_GET['mail_id'])) {
                        $db->Execute('UPDATE mail_message SET `'.implode('` = ?,`',array_keys($updates)).'` = ? WHERE mail_id = '.intval($_GET['mail_id']),array_values($updates));
                } else {
                        //$updates['user_id'] = $USER->user_id;
                        $db->Execute('INSERT INTO mail_message SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
                }
		header("Location: ?");
		exit;
	}

##############################

	if (is_numeric($_GET['mail_id'])) {
		$row = $db->getRow("SELECT * FROM mail_message WHERE mail_id = ".intval($_GET['mail_id']));

		if ($row['status'] == 'sent')
			$_GET['view'] = 1;
	} else {
		$row = array();
		$row['subject'] = "[Geograph] ...";
		$row['body'] = <<<END
Dear {realname},

.....

On behalf of the Geograph Board,

---------------------

Geograph Project Ltd is a Company Registered in England and Wales: number 7473967. The
registered office is Dept 1706, 43 Owston Road, Carcroft, Doncaster, South Yorkshire. DN6 8DA.
Geograph Project Limited is a charity registered in England and Wales: number 1145621.

P.S. this is a special message sent to all users who have recently contributed photos,
to opt out of further general emails from the Team, can unsubscribe via this link:
   {url}
END;

	}
	$keys = $db->getAssoc("DESCRIBE mail_message");

	print "<form method=post>";
	print "<table cellspacing=0 cellpadding=4 border=1 bordercolor=#eee>";
	foreach ($keys as $key => $data) {
		$value = htmlentities($row[$key] ?? '');
		print "<tr><th>$key";

		if (!empty($_GET['view'])) {
			print "<td style=\"white-space:pre;\">$value";
			continue;
		}

		print "<td>";

		$len = null;
		if (preg_match('/\((\d+)\)/',$data['Type'], $m))
			$len = intval($m[1]);

		switch($key) {
			case 'status':
				if (preg_match('/enum\((.+)\)/',$data['Type'], $m)) {
                                                print "<select name=\"$key\">";
                                                if ($value['Null'] === 'YES')
                                                        print "<option></option>";
                                                foreach(explode(',',$m[1]) as $v) { //for now assume no commas in values!
                                                        $v = htmlentities(trim($v,"'"));
                                                        printf('<option value="%s"%s>%s</option>',
                                                                $v,
                                                                $value==$v?' selected':'',
                                                                $v);
                                                }
                                                print "</select>";
                                }
			break;
			case 'subject':
			case 'criteria':
			case 'where_clause':
				$size = max(1,min(80,$len));
				print "<input type=text name=\"$key\" value=\"$value\" size=$size maxlength=$len>";
			break;
			case 'body':
				$len = 65535;
				print "<textarea rows=40 cols=80 wrap=none name=\"$key\" maxlength=$len>$value</textarea>";
				print "<br>You NEED to include <tt>{url}</tt> (probably towards the end) which will be replaced at runtime with the proper unsubscribe link. ";
				print "<tt>{realname}</tt> and <tt>{email}</tt> are valid placeholders too";
			break;
			default:
				print $value;
		}
	}
	print "</table>";
	print "<button type=submit>Submit</button>";
	print "</form>";

	print "<p>Please just use an <b>informal description</b> of who should receive the email in the 'Criteria' it will be formalized before sending into a database query";

} else {
	$data = $db->getAll("select mail_id,created,subject,status,criteria,number_sent,started,finished from mail_message");



	print "<h2>Emails</h2>";

	print "<p>This is an incomplete list of messages sent, we only formalized the sending in late 2025, older messages not yet shown";

	print "<p><a href=\"?mail_id=new\">Create new</a>";

	print "<table cellspacing=0 cellpadding=4 border=1 bordercolor=#eee>";
		print "<tr><th>".implode("</th><th>",array_map('htmlentities',array_keys($data[0])))."</th></tr>";
	foreach($data as $row) {
		//print "<tr><td>".implode("</td><td align=right>",array_map('htmlentities',$row))."</td>";
		print "<tr>";
		foreach($row as $key => $value) {
			if (is_numeric($value)) {
				print "<td align=right>".floatval($value);
			} else {
				print "<td>".htmlentities($value);
			}
		}

		if ($row['status'] == 'new' || $row['status'] == 'ready')
			print "<td><a href=?mail_id={$row['mail_id']}>Edit</a>";
		else
			print "<td><a href=?mail_id={$row['mail_id']}&view=1>View</a>";
	}
	print "</table>";

}



	$smarty->display('_std_end.tpl');
	exit;
