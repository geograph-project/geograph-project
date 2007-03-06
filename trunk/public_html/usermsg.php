<?php
/**
 * $Project: GeoGraph $
 * $Id$
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

init_session();

$smarty = new GeographPage;
$template='usermsg.tpl';	

//gather what we need	
$recipient=new GeographUser($_REQUEST['to']);
$from_name=isset($_POST['from_name'])?stripslashes($_POST['from_name']):$USER->realname;
$from_email=isset($_POST['from_email'])?stripslashes($_POST['from_email']):$USER->email;
$sendcopy=isset($_POST['sendcopy'])?stripslashes($_POST['sendcopy']):false;

$smarty->assign_by_ref('recipient', $recipient);
$smarty->assign_by_ref('from_name', $from_name);
$smarty->assign_by_ref('from_email', $from_email);
$smarty->assign_by_ref('sendcopy', $sendcopy);


$db=NewADOConnection($GLOBALS['DSN']);
if (empty($db)) die('Database connection failed');

$ip=getRemoteIP();

$user_id = "inet_aton('{$ip}')";

if (empty($CONF['usermsg_spam_trap'])) {
	$throttle = 0;
} elseif ($db->getOne("select count(*) from throttle ".
		"where used > date_sub(now(), interval 1 hour) and ".
		"user_id=$user_id AND feature = 'usermsg'") > 5) {
	$smarty->assign('throttle',1);
	$throttle = 1;
} elseif ($db->getOne("select count(*) from throttle " .
		"where used > date_sub(now(), interval 24 hour) and " .
		"user_id=$user_id AND feature = 'usermsg'") > 30) {
	$smarty->assign('throttle',1);
	$throttle = 1;
} else {
	$throttle = 0;
}

if (rand(1,10) > 5) {
	$db->query("delete from throttle where used < date_sub(now(), interval 48 hour)");
}
	
//try and send?
if (isset($_POST['msg']) && !$throttle)
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


	if (isSpam($msg))
	{
		$ok=false;
		$errors['msg']="Sorry, this looks like spam";
	}
	
	//still ok?
	if ($ok)
	{
		//build message and send it...
		
		$body=$smarty->fetch('email_usermsg.tpl');
		$subject="[Geograph] $from_name contacting you via {$_SERVER['HTTP_HOST']}";
		
		$hostname=trim(`hostname`);
		$received="Received: from [{$ip}]".
			" by {$hostname}.geograph.org.uk ".
			"with HTTP;".
			strftime("%d %b %Y %H:%M:%S -0000", time())."\n";

		if (@mail($recipient->email, $subject, $body, $received."From: $from_name <$from_email>")) 
		{
			$db->query("insert into throttle set user_id=$user_id,feature = 'usermsg'");
		
		
			$smarty->assign('sent', 1);
		}
		else 
		{
			@mail($CONF['contact_email'], 
				'Mail Error Report from '.$_SERVER['HTTP_HOST'],
				"Original Subject: $subject\n".
				"Original To: {$recipient->email}\n".
				"Original From: $from_name <$from_email>\n".
				"Original Subject:\n\n$body",
				'From:webserver@'.$_SERVER['HTTP_HOST']);	


			$smarty->assign('error', "<a href=\"/contact.php\">Please let us know</a>");
		}
		
		if ($sendcopy) {
			if (!@mail($from_email, $subject, "Copy of message sent to {$recipient->realname}\n----------------------\n\n".$body, "From: $from_name <$from_email>")) {
				@mail($CONF['contact_email'], 
					'Mail Error Report from '.$_SERVER['HTTP_HOST'],
					"Original Subject: $subject\n".
					"Original To: {$from_email}\n".
					"Original From: $from_name <$from_email>\n".
					"Copy of message sent to {$recipient->realname}\n".
					"Original Subject:\n\n$body",
					'From:webserver@'.$_SERVER['HTTP_HOST']);	


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
}
elseif (isset($_GET['image']))
{
	//initialise message
	require_once('geograph/gridsquare.class.php');
  require_once('geograph/gridimage.class.php');

	$image=new GridImage();
	$image->loadFromId($_GET['image']);
	
	if (strpos($recipient->rights,'mod') !== FALSE) {
		$msg="Re: image for {$image->grid_reference} ({$image->title})\r\nhttp://{$_SERVER['HTTP_HOST']}/editimage.php?id={$image->gridimage_id}\r\n";
	} else {
		$msg="Re: image for {$image->grid_reference} ({$image->title})\r\nhttp://{$_SERVER['HTTP_HOST']}/photo/{$image->gridimage_id}\r\n";
	}
	$smarty->assign_by_ref('msg', $msg);
}

$smarty->display($template);

	
?>
