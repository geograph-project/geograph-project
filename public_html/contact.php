<?php
/**
 * $Project: GeoGraph $
 * $Id: contact.php 8519 2017-08-13 18:59:40Z barry $
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
require_once('geograph/security.inc.php');
init_session();

$smarty = new GeographPage;




if (isset($_POST['msg']))
{
	//get the inputs
	$msg=stripslashes(trim($_POST['msg']));
	$from=stripslashes(trim($_POST['from']));
	$subject=stripslashes(trim($_POST['subject']));

	$subject = str_replace('[Geograph]','',$subject);
	$subject = trim(preg_replace('/\s+/',' ',$subject));

	$smarty->assign('msg', $msg);
	$smarty->assign('from', $from);
	$smarty->assign('subject', $subject);

	//ensure we only got one from line
	if (isValidEmailAddress($from))
	{
		if (strlen($msg))
		{
			if ($_POST['referring_page'] == 'n/a' && !empty($_POST['name'])) {
				die("Spam, Spam, Eggs, Spam, Cheese and Spam!");
			}

			$db = GeographDatabaseConnection(false);

			$updates = array();

			$updates['referring_page'] = $_POST['referring_page'];
			$updates['from'] = $from;
			$updates['subject'] = $subject;
			$updates['msg'] = $msg;
			$updates['user_id'] = $USER->user_id;
			$updates['user_agent'] = $_SERVER['HTTP_USER_AGENT'];

			$db->Execute('INSERT INTO contactform SET created=NOW(),`'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));

			if (strlen($subject)==0)
				$subject='Re: '.$_SERVER['HTTP_HOST'];

			$msg.="\n\n-------------------------------\n";
			$msg.="Referring page: ".$_POST['referring_page']."\n";
			if ($_SESSION['user']->user_id)
				$msg.="User profile: {$CONF['SELF_HOST']}/profile/{$_SESSION['user']->user_id}\n";
			$msg.="Browser: ".$_SERVER['HTTP_USER_AGENT']."\n";

			$token=new Token;
			$token->setValue("id", intval($db->Insert_ID()));
			$msg.="Admin: {$CONF['SELF_HOST']}/admin/contact.php?t=".$token->getToken()."\n";

			$crc = sprintf("%u", crc32($from));
	                $fromheader = "From: $from via Geograph <noreply+$crc@geograph.org.uk>\nSender: noreply@geograph.org.uk\nReply-To: $from_name <$from_email>";

			mail_wrapper($CONF['contact_email'],
				'[Geograph] '.$subject,
				$msg,
				$fromheader);

			$smarty->assign('message_sent', true);
		}
		else
		{
			$smarty->assign('msg_error', 'Please enter your message to us above');
		}
	}
	else
	{
		$smarty->assign('from_error', 'Invalid email address');
	}
}
else
{
	if ($USER->registered) {
		$smarty->assign('from', $USER->email);
		$smarty->assign('t', hash_hmac('md5', $USER->user_id, $CONF['token_secret']));
	}
}

//get referring page from form if submitted, otherwise pick it up from server
$referring_page="n/a";
if (isset($_REQUEST['referring_page']))
	$referring_page=$_REQUEST['referring_page'];
elseif (isset($_SERVER['HTTP_REFERER']))
	$referring_page=$_SERVER['HTTP_REFERER'];

if (preg_match("/photo\/(\d+)/",$referring_page,$m)) {

	require_once('geograph/gridsquare.class.php');
	require_once('geograph/gridimage.class.php');

	$image=new GridImage();
	$image->loadFromId($m[1]);

	$smarty->assign_by_ref('image', $image);
	if (!isset($_POST['msg']))
		$smarty->assign('subject', '[Geograph] ');

	$db = GeographDatabaseConnection(true);
	$stats= $db->GetRow("select images from user_stat where user_id = 0");
	$stats['millions'] = sprintf("%.1f",$stats['images']/1000000);
	$smarty->assign_by_ref('stats', $stats);
}

$smarty->assign('referring_page',$referring_page);

if (1) {
	$smarty->display('contact_osticket.tpl');
} else {
	$smarty->display('contact.tpl');
}

