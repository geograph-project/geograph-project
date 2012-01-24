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
require_once('geograph/security.inc.php');
init_session();

$smarty = new GeographPage;




if (isset($_POST['msg']))
{
	//get the inputs
	$msg=stripslashes(trim($_POST['msg']));
	$from=stripslashes(trim($_POST['from']));
	$subject=stripslashes(trim($_POST['subject']));
	
	$smarty->assign('msg', $msg);
	$smarty->assign('from', $from);
	$smarty->assign('subject', $subject);
	
	//ensure we only got one from line
	if (isValidEmailAddress($from))
	{
		if (strlen($msg))
		{
			if (strlen($subject)==0)
				$subject='Re: '.$_SERVER['HTTP_HOST'];
			
			$msg.="\n\n-------------------------------\n";
			$msg.="Referring page: ".$_POST['referring_page']."\n";
			if ($_SESSION['user']->user_id)
				$msg.="User profile: http://{$_SERVER['HTTP_HOST']}/profile/{$_SESSION['user']->user_id}\n";
			$msg.="Browser: ".$_SERVER['HTTP_USER_AGENT']."\n";

			$envfrom = is_null($CONF['mail_envelopefrom'])?null:"-f {$CONF['mail_envelopefrom']}";
			$encsubject=mb_encode_mimeheader($CONF['mail_subjectprefix'].$subject, $CONF['mail_charset'], $CONF['mail_transferencoding']);
			$mime = "MIME-Version: 1.0\n".
				"Content-Type: text/plain; {$CONF['mail_charset']}\n".
				"Content-Disposition: inline\n".
				"Content-Transfer-Encoding: 8bit";

			mail($CONF['contact_email'],
				$encsubject,
				$msg,
				'From: '.$from."\n".$mime, $envfrom);

			$smarty->assign('message_sent', true);
		}
		else
		{
			if ($CONF['lang'] == 'de')
				$smarty->assign('msg_error', 'Bitte Nachricht eingeben!');
			else
				$smarty->assign('msg_error', 'Please enter your message to us above');
		}
	}
	else
	{
		if ($CONF['lang'] == 'de')
			$smarty->assign('from_error', 'Bitte gültige E-Mail-Adresse eingeben!');
		else
			$smarty->assign('from_error', 'Invalid email address');
	}
}
else
{
	if ($USER->registered)
		$smarty->assign('from', $USER->email);
}

//get referring page from form if submitted, otherwise pick it up from server
$referring_page="n/a";
if (isset($_REQUEST['referring_page']))
	$referring_page=$_REQUEST['referring_page'];
elseif (isset($_SERVER['HTTP_REFERER']))
	$referring_page=$_SERVER['HTTP_REFERER'];
	
$smarty->assign('referring_page',$referring_page);
	
$smarty->display('contact.tpl');

	
?>
