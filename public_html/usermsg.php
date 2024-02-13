<?php
/**
 * $Project: GeoGraph $
 * $Id: usermsg.php 8750 2018-04-09 18:28:42Z barry $
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

if (strpos(@$_SERVER['HTTP_USER_AGENT'], 'archive.org_bot')!==FALSE) {
	header('HTTP/1.0 403 Forbidden');
	exit;
}

if (empty($_REQUEST['to']) || !is_numeric($_REQUEST['to'])) {
	header('HTTP/1.0 451 Unavailable For Legal Reasons');
        exit;
}

if (!empty($_SERVER['PATH_INFO'])) {
	header('HTTP/1.0 400 Bad Request');
        exit;
}

require_once('geograph/global.inc.php');
init_session();

//gather what we need
$recipient=new GeographUser($_REQUEST['to']);

if (empty($recipient->realname)) {
	header('HTTP/1.0 451 Unavailable For Legal Reasons');
	print 1;
        exit;
}

rate_limiting('usermsg.php', 15, true);

if (!empty($_POST['from_email']) && preg_match('/@example.com$/',$_POST['from_email'])) {
        header('HTTP/1.0 451 Unavailable For Legal Reasons');
        print 2;
        exit;
}

###############################################################

$smarty = new GeographPage;
$template='usermsg.tpl';

$from_name= $_POST['from_name'] ?? $USER->realname ?? '';
$from_email=$_POST['from_email'] ?? $USER->email ?? '';
$sendcopy=$_POST['sendcopy'] ?? false;

$smarty->assign_by_ref('recipient', $recipient);
$smarty->assign_by_ref('from_name', $from_name);
$smarty->assign_by_ref('from_email', $from_email);
$smarty->assign_by_ref('sendcopy', $sendcopy);


$db=GeographDatabaseConnection(false);
if (empty($db)) die('Database connection failed');

$ip=getRemoteIP();

$user_id = "inet6_aton('{$ip}')";

$throttlenumber = 5;
if ($USER->hasPerm("ticketmod") || $USER->hasPerm("moderator")) {
	$throttlenumber = 30;
}

if ($db->getOne("select count(*) from throttle ".
		"where used > date_sub(now(), interval 1 hour) and ".
		"user_id=$user_id AND feature = 'usermsg'") > $throttlenumber) {
	$smarty->assign('throttle',1);
	$throttle = 1;
} elseif ($db->getOne("select count(*) from throttle " .
		"where used > date_sub(now(), interval 24 hour) and " .
		"user_id=$user_id AND feature = 'usermsg'") > $throttlenumber*6) {
	$smarty->assign('throttle',1);
	$throttle = 1;
} else {
	$throttle = 0;
}

if (rand(1,10) > 5) {
	$db->query("delete from throttle where used < date_sub(now(), interval 48 hour)");
}

//try and send?
if (isset($_POST['msg']))
{
	$ok=true;
	$msg=trim(stripslashes($_POST['msg']));
	
	$errors=array();
	if (!isValidEmailAddress($from_email))
	{
		$ok=false;
		$errors['from_email']='Please specify a valid email address';
	}
	if (!isValidRealName($from_name))
	{
		$ok=false;
		$errors['from_name']='Only letters A-Z, a-z, hyphens and apostrophes allowed';
	}
	if (strlen($msg)==0)
	{
		$ok=false;
		$errors['msg']="Please enter a message to send";
	}
	$smarty->assign_by_ref('errors', $errors);

	$smarty->assign_by_ref('msg', $msg);

	if (!empty($_POST['mention']))
		$smarty->assign_by_ref('mention', $_POST['mention']);

	if (isSpam($msg))
	{
		$ok=false;
		$errors['msg']="Sorry, this looks like spam";
	}

	if ($from_email == 'sample@email.tst') {
		$ok=false;
		$errors['msg']="Sorry, this looks like spam";
	}

	//if not logged in or they been busy - lets ask them if a person! (plus jump though a few hoops to make it harder to program a bot)
	if ($ok && ($USER->user_id == 0 || $throttle || empty($_SERVER['HTTP_REFERER']) )) {

		$verification = md5($CONF['register_confirmation_secret'].$msg.$from_email.$from_name);

		//check the verification code
		if (empty($_POST['verification']) || $_POST['verification'] != $verification || empty($_SESSION['verification']) || $_SESSION['verification'] != $verification) {
			$ok = false;
			$smarty->assign('verification', $verification);
		}
		$_SESSION['verification'] = $verification;

		//user has requested a emailed code
		if (!empty($_POST['sendcode'])) {
			$c = rand(1000,9999);

			$token=new Token;
			$token->setValue("v5", md5($c.$CONF['register_confirmation_secret']));

			$smarty->assign('encoded', $token->getToken());

			$message="This message is to confirm your email address, for spam prevention purposes.\n\n";
			$message.="Please enter the following code:\n\n";
			$message.="$c\n\n";
			$message.="into the the webpage where you clicked 'Request confirmation code by email'\n\n";
			$message.="Thank you,\n\n";
			$message.="The Geograph.org.uk Team\n\n";

			$message.="P.S. Please note that while your email address is sent to the contributor so they can reply, Geograph do not store it at all, and certainly won't use it for spam!";

			mail_wrapper($from_email, '[geograph] Confirm email address', $message,
			"From: Geograph Website <noreply@geograph.org.uk>", '-fnoreply@geograph.org.uk');

			$ok = false;
			$smarty->assign('verification', $verification);

		//need to verify the entered confirm code
		} elseif (isset($_POST['confirmcode'])) {
			$token=new Token;

			if ($token->parse($_POST['encoded']) && $token->hasValue("v5") && md5(trim($_POST['confirmcode']).$CONF['register_confirmation_secret']) == $token->getValue("v5")) {
				//who-ooo!
			} else {
				$ok = false;
				$smarty->assign('verification', $verification);
				$smarty->assign('error', "Confirmation code doesn't match");
			}

		//validate a recapatcha if enabled
		} elseif (!empty($CONF['recaptcha_publickey']) && isset($_POST["g-recaptcha-response"])) {
			require_once('3rdparty/recaptchalib.php');

			$resp = recaptcha_check_answer($CONF['recaptcha_privatekey'],getRemoteIP(),null,$_POST["g-recaptcha-response"]);

			if (!$resp->is_valid) {
				$ok = false;
				$smarty->assign('verification', $verification);

				$smarty->assign('recaptcha', recaptcha_get_html($CONF['recaptcha_publickey'], $resp->error));
				$smarty->assign('error', "Captcha Failed - see below");
			}

		//otherwise validate our own capatcha
		} elseif (!empty($_POST['verify'])) {
			define('CHECK_CAPTCHA',true);

			require("stuff/captcha.jpg.php");

			$ok = $ok && CAPTCHA_RESULT;

			if ($ok) {
				$_SESSION['verCount'] = (isset($_SESSION['verCount']))?$_SESSION['verCount']-2:-2;

			} else {
				if (isset($_SESSION['verCount']) && $_SESSION['verCount'] > 3) {
					$smarty->assign('error', "Too many failures, please try again later");
				} else {
					$smarty->assign('verification', $verification);
					$smarty->assign('error', "Please Try again");
				}
				$ok = false;
				$db->query("insert into throttle set user_id=$user_id,feature = 'usermsg'");

				$_SESSION['verCount'] = (isset($_SESSION['verCount']))?$_SESSION['verCount'] +1:1;
			}
		}

		if (!$ok && !empty($CONF['recaptcha_publickey']) && empty($resp)) {
			require_once('3rdparty/recaptchalib.php');
			$smarty->assign('recaptcha', recaptcha_get_html($CONF['recaptcha_publickey']));
		}
	}

	//still ok?
	if ($ok)
	{
		####################################################################
		//build message and send it...

		if (!empty($_POST['mention'])) {
			$ids = array_map('intval',$_POST['mention']);
			$ids = implode(',',$ids);

			$images = $db->getAll("SELECT gridimage_id,title,grid_reference,realname FROM gridimage_search WHERE gridimage_id IN ($ids) AND user_id = {$recipient->user_id} ORDER BY FIELD(gridimage_id,$ids) DESC LIMIT 4");

			if (!empty($images)) {
				$smarty->assign_by_ref('images', $images);
			}
		}

		$body=$smarty->fetch('email_usermsg.tpl');
		$subject="[Geograph] $from_name contacting you via {$_SERVER['HTTP_HOST']}";

		$hostname=trim(`hostname`);
		$received="Received: from [{$ip}]".
			" by {$hostname}.geograph.org.uk ".
			"with HTTP;".
			strftime("%d %b %Y %H:%M:%S -0000", time())."\n";

		//we create a 'fake' email address for From, so that email clients dont just set merge all emails to one contact!
		$crc = sprintf("%u", crc32($from_email));
		$fromheader = "From: $from_name via Geograph <noreply+$crc@geograph.org.uk>\nSender: noreply@geograph.org.uk\nReply-To: $from_name <$from_email>";

		if ($recipient->email == '' || strpos($recipient->rights,'dormant') !== FALSE) {
			$smarty->assign('invalid_email', 1);
			$email = $CONF['contact_email'];
			$body = "Sent as Geograph doesn't hold email address for this user [id {$recipient->user_id}]\n\n--\n\n".$body;
		} else {
			$email = $recipient->email;
		}
		if (trim(strtolower($from_email)) == "vincentronald2016@gmail.com")
		{
			//silently do nothing. Dont send the email. Can still send the 'copy'!
			$smarty->assign('sent', 1);
		}
		elseif (mail_wrapper($email, $subject, $body, $received.$fromheader, '-fnoreply@geograph.org.uk'))
		{
			$db->query("insert into throttle set user_id=$user_id,feature = 'usermsg'");
			$smarty->assign('sent', 1);
		}

		####################################################################

		else
		{
			debug_message('Mail Error Report from '.$_SERVER['HTTP_HOST'],
				@$GLOBALS['mailer_error']."\n\n".
				"Original Subject: $subject\n".
				"Original To: {$recipient->email}\n".
				"Original From: $from_name <$from_email>\n".
				"Original:\n\n$body");

			$smarty->assign('error', "<a href=\"/contact.php\">Please let us know</a>");
		}

		if ($sendcopy) {
			$subject="[Geograph] Copy of message sent to {$recipient->realname}";

			if (!mail_wrapper($from_email, $subject, $body, $fromheader, '-fnoreply@geograph.org.uk')) {

				debug_message('Mail Error Report from '.$_SERVER['HTTP_HOST'],
					@$GLOBALS['mailer_error']."\n\n".
					"Original Subject: $subject\n".
					"Original To: {$from_email}\n".
					"Original From: $from_name <$from_email>\n".
					"Copy of message sent to {$recipient->realname}\n".
					"Original:\n\n$body");

				$smarty->assign('error', "<a href=\"/contact.php\">Please let us know</a>");
			}
		}
	}
}
elseif (isset($_POST['init']))
{
	//initialise message

	$msg=trim(stripslashes($_POST['init']));
	$smarty->assign_by_ref('msg', $msg);
	
	customExpiresHeader(360,false,true);
}
elseif (isset($_GET['image']))
{
	//initialise message
	require_once('geograph/gridsquare.class.php');
	require_once('geograph/gridimage.class.php');

	$image=new GridImage();
	$image->loadFromId($_GET['image']);
	
	if (strpos($recipient->rights,'mod') !== FALSE) {
		$msg="Re: image for {$image->grid_reference} ({$image->title})\r\n{$CONF['SELF_HOST']}/editimage.php?id={$image->gridimage_id}\r\n";
	} else {
		$msg="Re: image for {$image->grid_reference} ({$image->title})\r\n{$CONF['SELF_HOST']}/photo/{$image->gridimage_id}\r\n";
	}
	$smarty->assign_by_ref('msg', $msg);
	
	$images = array(0=>array(
		'gridimage_id'=>$image->gridimage_id,
		'title'=>$image->title,
		'grid_reference'=>$image->grid_reference,
		'realname'=>$image->realname));
	$smarty->assign_by_ref('images', $images);

	customExpiresHeader(360,false,true);

} elseif (!empty($_SESSION['photos'])) {
	$ids = implode(',',array_keys($_SESSION['photos']));
	
	$db = GeographDatabaseConnection(true);

	$images = $db->getAll("SELECT gridimage_id,title,grid_reference,realname FROM gridimage_search WHERE gridimage_id IN ($ids) AND user_id = {$recipient->user_id} ORDER BY FIELD(gridimage_id,$ids) DESC LIMIT 4");
	
	$smarty->assign_by_ref('images', $images);
}

if ($recipient->email == '' || strpos($recipient->rights,'dormant') !== FALSE) {
	$smarty->assign('invalid_email', 1);
	if ($recipient->public_email) {
		$smarty->assign_by_ref('public_email', $recipient->public_email);
	}
}

if (!empty($_GET['dev'])) {
	$smarty->assign("dev",1);
}

$smarty->display($template);

