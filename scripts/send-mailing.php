<?php
/**
 * $Project: GeoGraph $
 * $Id: notification-mailer.php 8717 2018-02-21 19:13:16Z barry $
 *
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2013 Barry Hunter (geo@barryhunter.co.uk)
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

//these are the arguments we expect
$param=array(
	'mail_id'=>0, //required!
        'action'=>'dummy',//dummy/test/send
	'limit'=>10,
	'sleep'=>0,
);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

	require_once "3rdparty/class.phpmailer.php";
	require_once "3rdparty/class.smtp.php";

	$mail = new PHPMailer;

	#########################
	if ($param['action'] == 'fake')
		$mail->SMTPDebug = 3;                               // Enable verbose debug output

	$mail->XMailer = 'x'; //used to SKIP the header

	if (!empty($CONF['smtp_host'])) {
		$mail->isSMTP();
		$mail->Host = $CONF['smtp_host'];
		if (!empty($CONF['smtp_user'])) {
			$mail->SMTPAuth = true;
			$mail->Username = $CONF['smtp_user'];
			$mail->Password = $CONF['smtp_pass'];
		}
		if ($CONF['smtp_port']> 25)
			$mail->SMTPSecure = 'tls';                    // Enable TLS encryption, `ssl` also accepted
		$mail->Port = $CONF['smtp_port'];                     // TCP port to connect to

		#########################

		$mail->setFrom($CONF['smtp_from'],'',true);//set sender too
	} else {
		$mail->setFrom($CONF['minibb_admin_email'],'',true);
	}

############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

############################################

if (empty($param['mail_id']))
	die("no mail id\n");

$message = $db->getRow("SELECT * FROM mail_message WHERE status IN ('ready','sending') AND mail_id = ".intval($param['mail_id']));

if (empty($message['mail_id']))
	die("invalid mail id\n");

$mail_id = $message['mail_id'];

if (strpos($message['body'], '{url}') === FALSE)
	die("missing url placeholder\n");


############################################

//most of filtering is done by mail_recipient  table!

//This NEEDS user_id, email, and realname  columns!
$results = $db->getAll($sql = "
SELECT user.user_id,user.email,user.realname
FROM user
 inner join mail_recipient using (user_id)
 left join user_preference p on (p.user_id = user.user_id AND pkey = 'mailing')
where
 mail_id = $mail_id
 AND {$message['where_clause']}
 AND email like '%@%'
 AND user.rights like '%basic%' and user.rights NOT like '%dormant%' and user.rights not like '%suspicious%' and deceased_date is null
 AND (p.value != 'no' OR p.value is NULL)
 AND email_sent IS NULL
ORDER BY RAND()
LIMIT {$param['limit']}");

print "$sql;\n";

############################################

if (!empty($results)) {
	foreach ($results as $row) {

		$to = $row['email'];

	        $token=new Token;
	        $token->setValue("id", intval( $row['user_id']));
	        $t= $token->getToken();

	        $url = "https://www.geograph.org.uk/unsubscribe.php?t=$t&sub=0";

	        $body = str_replace('{realname}',$row['realname'],$message['body']);
	        $body = str_replace('{url}',$url,$body);
	        $body = str_replace('{email}',$row['email'],$body);

		$update = "UPDATE mail_recipient SET email_sent = NOW() WHERE mail_id=$mail_id AND user_id = {$row['user_id']}";

		if ($param['action'] == 'dummy') {
			print "$update\n";
			print "TO: $to\n";
			print "$body\n";
			print "$update\n";

		} elseif ($param['action'] == 'test') {

			$mail->addAddress($CONF['contact_email']);

			$mail->Subject = $message['subject'];

			$mail->IsHTML(false);
			$mail->Body = $body; //if using isHTML will be the HTML verson, AltBody, will be plain text!

			$mail->send();

			print "Sent test message to ".$CONF['contact_email']."\n";

		} elseif ($param['action'] == 'send') {

			$mail->addAddress($to);

			$mail->Subject = $message['subject'];

			$mail->IsHTML(false);
			$mail->Body = $body; //if using isHTML will be the HTML verson, AltBody, will be plain text!

			$mail->send();

			$mail->clearAllRecipients(); //because more added next time

			print "Sent to $to\n";
			$db->Execute($update);
		}
	        if ($param['action'] != 'send')
	                exit;

		if ($param['sleep'])
	 		sleep($param['sleep']);
	}
	if ($row['status'] == 'ready' && $param['action'] == 'send')
		$db->Execute("UPDATE mail_message SET status = 'sending' WHERE mail_id = ".intval($param['mail_id']));
} else {
	print "no mail_recipients left\n";
	if ($row['status'] == 'sending' && $param['action'] == 'send')
		$db->Execute("UPDATE mail_message SET status = 'sent' WHERE mail_id = ".intval($param['mail_id']));

}
