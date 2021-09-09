<?php
/**
 * $Project: GeoGraph $
 * $Id: record_vote.php 6944 2010-12-03 21:44:38Z barry $
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


$db=NewADOConnection($GLOBALS['DSN']);
if (!$db) failed('Database connection failed');


store_everything('paypal_return', $_POST);


$smarty = new GeographPage;
$smarty->display('_std_begin.tpl');

?>
<h2>Thank you for your payment.</h2>
<?

//this needs converting to IGN. The return URL doesnt contain in any identifiers
if (!empty($_SESSION['calendar_id'])) { ?>
	 Your transaction has completed, and we are sending you an email containing the receipt for your purchase.
<?
	$calendar_id = intval($_SESSION['calendar_id']);

        $db->Execute("UPDATE calendar SET status = 'paid', paid=now() WHERE calendar_id = $calendar_id AND user_id = {$USER->user_id}");

	unset($_SESSION['calendar_id']);

	$row = $db->getRow("SELECT * FROM calendar WHERE calendar_id = $calendar_id");

	if (empty($row) || $row['user_id'] != $USER->user_id)
        	die("Calendar not found");


	$to = $USER->email;
	$subject = "[Geograph] Calendar Order {$row['user_id']}{$row['alpha']} - Thank you";
	$body = "Thank you for your order.

This message is to confirm, we have received your order. We will be processing the order shortly, and will let you know when ready to dispatch.

In the meantime you can still edit the order (to rearrange the images, or if you need to update the delivery address).
https://www.geograph.org.uk/calendar/edit.php?id={$row['calendar_id']}

But once processed, will no longer be able to edit.

If you have any questions, contact us online:
	https://www.geograph.org.uk/contact.php
	(we wont see email replies to this message)

Regards,

The Calendar Team at Geograph Towers.

";


	mail_wrapper($to, $subject, $body, "From: Geograph NoReply <noreply@geograph.org.uk>");
} else {
	print "<a href=/>Continue to Homepage</a>";
}


$smarty->display('_std_end.tpl');





function store_everything($table,$values) {
	global $db;

        $values['REQUEST_TIME'] = $_SERVER['REQUEST_TIME']; //our own local timestamp!

        $keys = array_keys($values);

        if ($db->getOne("SHOW TABLES LIKE '$table'")) {
	        $exist = $db->getAssoc("DESCRIBE `$table`");
                $sql = "ALTER TABLE `$table`"; $sep = '';
                foreach ($keys as $key) {
                        $key = preg_replace('/[^\w]+/','',trim($key));
                        if (isset($exist[$key]))
                                continue;
                        $type = 'VARCHAR(255)';
                        $sql .= " $sep ADD `$key` $type DEFAULT NULL"; $sep = ",";
                }
        } else {
                $sql = "CREATE TABLE `$table` (";
                foreach ($keys as $key) {
                        $key = preg_replace('/[^\w]+/','',trim($key));
                        $type = 'VARCHAR(255)';
                        $sql .= "`$key` $type DEFAULT NULL,";
                }
                $sql .= " KEY(`REQUEST_TIME`) ) ENGINE=myisam"; $sep = ",";
        }
        if (!empty($sep)) //at least one column added!
	        $db->Execute($sql) or failed("$sql;\n<hr>\n".$db->ErrorMsg()."\n");



        $sql = "INSERT INTO `$table` SET "; $sep = '';
        foreach ($keys as $key) {
                $value = $values[$key];

                $key = preg_replace('/[^\w]+/','',trim($key));
                $value = is_numeric($value)?$value:$db->Quote($value);
                $sql .= " $sep `$key` = $value";  $sep = ",";
        }
        $db->Execute($sql) or failed("$sql;\n<hr>\n".$db->ErrorMsg()."\n");
}

