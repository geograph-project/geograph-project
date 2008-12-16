<?php
/**
 * $Project: GeoGraph $
 * $Id: index.php,v 1.2 2006/04/19 10:00:00 barryhunter Exp $
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

$smarty = new GeographPage;


$db=NewADOConnection($GLOBALS['DSN']);

if (empty($_REQUEST['key']) || preg_match("/[^\w\.]/",$_REQUEST['key']) )
	die("ERROR: no api key");
	
$sql = "SELECT * FROM `apikeys` WHERE `apikey` = ".$db->Quote($_REQUEST['key'])." AND (`ip` = INET_ATON('{$_SERVER['REMOTE_ADDR']}') OR `ip` = 0) AND `enabled` = 'Y'";

$profile = $db->GetRow($sql);

if (empty($profile['apikey'])) {
	die("ERROR: invalid api key. please contact us");

} 


if (empty($_REQUEST['to'])) {
	if (!empty($_REQUEST['image'])) {
		//initialise message
		require_once('geograph/gridsquare.class.php');
		require_once('geograph/gridimage.class.php');
	
		$image=new GridImage();
		$image->loadFromId($_REQUEST['image']);
		
		
		$msg="Re: image for {$image->grid_reference} ({$image->title})\r\nhttp://{$_SERVER['HTTP_HOST']}/photo/{$image->gridimage_id}\r\n";
	
		
		$_REQUEST['to'] = $image->user_id;
	} else {
		die("ERROR: no image/user specified");
	}
} else {
	$msg='';
}


$recipient=new GeographUser($_REQUEST['to']);
$from_name=!empty($_REQUEST['from_name'])?stripslashes($_REQUEST['from_name']):die('ERROR: no name');
$from_email=!empty($_REQUEST['from_email'])?stripslashes($_REQUEST['from_email']):die('ERROR: no email');

$domain = (!empty($_REQUEST['domain']) && preg_match('/^[\w.]+$/',$_REQUEST['domain']))?stripslashes($_REQUEST['domain']):die('ERROR: no domain');

$msg=!empty($_REQUEST['message'])?$msg.stripslashes($_REQUEST['message']):die('ERROR: no message');

#$msg = preg_replace("/[^\r]\n/","\r\n",$msg);

$ok = true;
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

if (isSpam($msg))
{
	$ok=false;
	$errors['msg']="Sorry, this looks like spam";
}
if (!$ok) {
	die("ERROR: ".implode('. ',$errors));
}

$smarty->assign_by_ref('msg', $msg);

$smarty->assign('http_host', "{$_SERVER['HTTP_HOST']} on behalf of {$domain}");
$body=$smarty->fetch('email_usermsg.tpl');
$subject="[Geograph] $from_name contacting you via {$domain}";

$ip=getRemoteIP();
$hostname=trim(`hostname`);
$received="Received: from [{$ip}]".
	" by {$hostname}.geograph.org.uk ".
	"with HTTP;".
	strftime("%d %b %Y %H:%M:%S -0000", time())."\n";

if (!empty($_REQUEST['client_ip']) && preg_match("/^[\w\.]+$/",$_REQUEST['client_ip']) ) {
	$received.="\nReceived: from [{$_REQUEST['client_ip']}]".
	" by [{$id}] ".
	"with HTTP;".
	strftime("%d %b %Y %H:%M:%S -0000", empty($_REQUEST['timestamp'])?time():intval($_REQUEST['timestamp']))."\n";


}


if (preg_match('/(DORMANT|geograph\.org\.uk|geograph\.co\.uk|dev\.null|deleted|localhost|127\.0\.0\.1)/',$recipient->email)) {

	$email = $CONF['contact_email'];

	$body = "Sent as Geograph doesn't hold email address for this user [id {$recipient->user_id}]\n\n--\n\n".$body;
} else {
	$email = $recipient->email;
}
if (@mail($email, $subject, $body, $received."From: $from_name <$from_email>")) 
{


	print "Success";
	exit;
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

	die("ERROR: fatal error, Please let us know");
}

?>
