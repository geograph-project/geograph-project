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
	
	$smarty->assign('msg', $msg);
	$smarty->assign('from', $from);
	
	//ensure we only got one from line
	if (isValidEmailAddress($from))
	{
		if (strlen($msg))
		{
			mail($CONF['contact_email'], 
				'Message from '.$_SERVER['HTTP_HOST'],
				$msg,
				'From:'.$from);	

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
	if ($USER->registered)
		$smarty->assign('from', $USER->email);
}

$smarty->display('contact.tpl');

	
?>
