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



if ($USER->hasPerm("basic") && substr($_GET['u'],0,1)!='m' && substr($_GET['u'],0,1)!='p') {
	header("Location: /login.php");
	exit;
}

$smarty = new GeographPage;
$template='register.tpl';

if (isset($_GET['confirm']))
{
	if (substr($_GET['u'],0,1)=='m')
	{
		$template='profile_emailupdate.tpl';
				
		//we are confirming an email address change...
		$confirmation_status = $USER->verifyEmailChange($_GET['u'], $_GET['confirm']);
		if ($confirmation_status=="ok")
			$smarty->assign("user", $GLOBALS['USER']);
			
		$smarty->assign('confirmation_status', $confirmation_status);
		
	}
	elseif (substr($_GET['u'],0,1)=='p')
	{
		$template='profile_passwordupdate.tpl';
				
		//we are confirming an password change...
		$confirmation_status = $USER->verifyPasswordChange($_GET['u'], $_GET['confirm']);
		if ($confirmation_status=="ok")
			$smarty->assign("user", $GLOBALS['USER']);
			
		$smarty->assign('confirmation_status', $confirmation_status);
		
	}
	else
	{
		$confirmation_status = $USER->verifyRegistration($_GET['u'], $_GET['confirm']);
		if ($confirmation_status=="ok")
			$smarty->assign("user", $GLOBALS['USER']);
		
		$smarty->assign('confirmation_status', $confirmation_status);
	}
	
}
elseif (isset($_POST['name']))
{
	$errors=array();
	$ok=$USER->register($_POST, $errors);
	
	//store registration errors and error errors
	$smarty->assign('registration_ok', $ok);
	if (!$ok)
	{
		$smarty->assign('name', stripslashes(trim($_POST['name'])));
		$smarty->assign('email', stripslashes(trim($_POST['email'])));
		$smarty->assign('password1', stripslashes(trim($_POST['password1'])));
		$smarty->assign('password2', stripslashes(trim($_POST['password2'])));
		$smarty->assign('errors', $errors);
	}
}

if (empty($_SERVER['HTTP_REFERER'])) {
	$smarty->assign('empty_referer',1);
}


$smarty->display($template);



