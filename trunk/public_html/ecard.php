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
$template='ecard.tpl';	

//you must be logged in to send e-cards
$USER->mustHavePerm("basic");

$db=NewADOConnection($GLOBALS['DSN']);
if (empty($db)) die('Database connection failed');

if ($db->getOne("select count(*) from throttle where ts > date_sub(now(), interval 1 hour) and user_id={$USER->user_id} AND feature = 'e'") > 8) {
	print "<H3>This feature is busy, please try again later.</h3>";
	exit;
}		
$db->query("insert into throttle set user_id={$USER->user_id},feature = 'e'");
if (rand(1,10) > 5) {
	$db->query("delete from throttle where ts < date_sub(now(), interval 48 hour)");
}

//gather what we need	
$recipient=new GeographUser($_REQUEST['to']);
$from_name=isset($_POST['from_name'])?stripslashes($_POST['from_name']):$USER->realname;
$from_email=isset($_POST['from_email'])?stripslashes($_POST['from_email']):$USER->email;

$to_name=isset($_POST['to_name'])?stripslashes($_POST['to_name']):'';
$to_email=isset($_POST['to_email'])?stripslashes($_POST['to_email']):'';


$smarty->assign_by_ref('from_name', $from_name);
$smarty->assign_by_ref('from_email', $from_email);

$smarty->assign_by_ref('to_name', $to_name);
$smarty->assign_by_ref('to_email', $to_email);

if (isset($_REQUEST['image']))
{
	//initialise message
	require_once('geograph/gridsquare.class.php');
    require_once('geograph/gridimage.class.php');

	$image=new GridImage();
	$image->loadFromId($_REQUEST['image']);
	
	if ($image->moderation_status=='rejected' || $image->moderation_status=='pending') {
		//clear the image
		$image=new GridImage;
	} else {
		$msg="Hi,\r\n\r\nI recently saw this image, and thought you might like to see it too.\r\n\r\nRegards,\r\n\r\n";

		$smarty->assign_by_ref('msg', $msg);
	}
	$smarty->assign_by_ref('image', $image);
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
	if (!isValidEmailAddress($to_email))
	{
		$ok=false;
		$errors['to_email']='Please specify a valid email address';
	}
	if (!isValidRealName($to_name))
	{
		$ok=false;
		$errors['to_name']='Only letters A-Z, a-z, hyphens and apostrophes allowed';
	}
	if (strlen($msg)==0)
	{
		$ok=false;
		$errors['msg']="Please enter a message to send";
	}
	$smarty->assign_by_ref('errors', $errors);

	$smarty->assign_by_ref('msg', $msg);

	
	//still ok?
	if ($ok)
	{
		//build message and send it...
		
		$smarty->assign_by_ref('htmlmsg', nl2br($msg));
		
		$body=$smarty->fetch('email_ecard.tpl');
		$subject="$from_name is sending you an e-Card from {$_SERVER['HTTP_HOST']}";
		
		@mail("$to_name <$to_email>", $subject, $body, 
			"From: $from_name <$from_email>\nContent-Type: multipart/alternative; boundary=\"----=_NextPart_000_00DF_01C5EB66.9313FF40\"");
		
		
		
		$smarty->assign('sent', 1);
	}
}


$smarty->display($template);

	
?>
