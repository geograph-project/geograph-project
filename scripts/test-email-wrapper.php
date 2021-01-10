<?php
/**
 * $Project: GeoGraph $
 * $Id: recreate_maps.php 2996 2007-01-20 21:39:07Z barry $
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

############################################

$param = array('to'=>'', 'from'=>'', 'adv'=>false);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

/*
libs/geograph/functions.inc.php
1282:function mail_wrapper($email, $subject, $body, $headers = '', $param = '') {

*/

if (empty($param['to']))
	die("specify to\n");

$subject = "Test Message ".date('r');
$body = "test";
$email = $param['to'];



if (empty($param['adv'])) {

	$r = mail_wrapper($email, $subject, $body);

} else {
	if (empty($param['from']))
	        die("specify from\n");

	$ip = "localhost";
	$from_email = $param['from'];
	$from_name = "Testing";
	$subject .= " Full";

	//code stolen from usermsg.php

                $hostname=trim(`hostname`);
                $received="Received: from [{$ip}]".
                        " by {$hostname}.geograph.org.uk ".
                        "with HTTP;".
                        strftime("%d %b %Y %H:%M:%S -0000", time())."\n";

                //we create a 'fake' email address for From, so that email clients dont just set merge all emails to one contact!
                $crc = sprintf("%u", crc32($from_email));
                $fromheader = "From: $from_name via Geograph <noreply+$crc@geograph.org.uk>\nSender: noreply@geograph.org.uk\nReply-To: $from_name <$from_email>";

		/*
                if ($recipient->email == '' || strpos($recipient->rights,'dormant') !== FALSE) {
                        $smarty->assign('invalid_email', 1);
                        $email = $CONF['contact_email'];
                        $body = "Sent as Geograph doesn't hold email address for this user [id {$recipient->user_id}]\n\n--\n\n".$body;
                } else {
                        $email = $recipient->email;
                }

                if (@mail($email, $subject, $body, $received.$fromheader, '-fnoreply@geograph.org.uk'))
                {
                        $db->query("insert into throttle set user_id=$user_id,feature = 'usermsg'");
                        $smarty->assign('sent', 1);
                }*/


	$r = mail_wrapper($email, $subject, $body, $received.$fromheader, '-fnoreply@geograph.org.uk');
}
var_dump($r);
print "\n";

