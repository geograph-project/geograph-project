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

if (isset($_GET['u'])) {
	$c = substr($_GET['u'], 0, 1);
} elseif (isset($_POST['u'])) {
	$c = substr($_POST['u'], 0, 1);
} else {
	$c = '';
}

if ($USER->hasPerm("basic") && $c != 'm' && $c != 'p') {
	header("Location: /login.php");
	exit;
}

$smarty = new GeographPage;
$template='register.tpl';

if (isset($_GET['confirm']))
{
	/* put all the GET stuff into hidden parameters of a form and ask for password */
	$c = substr($_GET['u'], 0, 1);
	$confirmpass = $c === 'p';
	$confirmmail = $c === 'm';
	$confirmreg  = !$confirmpass && !$confirmmail;
	$smarty->assign('query_confirm', stripslashes(trim($_GET['confirm'])));
	$smarty->assign('query_u', stripslashes(trim($_GET['u'])));
	$smarty->assign('confirmpass', $confirmpass);
	$smarty->assign('confirmmail', $confirmmail);
	$smarty->assign('confirmreg', $confirmreg);
	$template='register_confirm.tpl';
}
elseif (isset($_POST['confirm']))
{
	$c = substr($_POST['u'], 0, 1);
	$confirmpass = $c === 'p';
	$confirmmail = $c === 'm';
	$confirmreg  = !$confirmpass && !$confirmmail;
	$pass = stripslashes(trim($_POST['password']));

	if ($confirmmail) {
		$template='profile_emailupdate.tpl';
	} elseif ($confirmpass) {
		$template='profile_passwordupdate.tpl';
	}

	if (!isset($_POST['CSRF_token']) || $_POST['CSRF_token'] !== $_SESSION['CSRF_token']) {
		$confirmation_status='csrf';
	} else {
		if ($confirmmail) {
			//we are confirming an email address change...
			$confirmation_status = $USER->verifyEmailChange($_POST['u'], $_POST['confirm'], $pass);
		} elseif ($confirmpass) {
			//we are confirming an password change...
			$confirmation_status = $USER->verifyPasswordChange($_POST['u'], $_POST['confirm'], $pass);
		} else {
			$confirmation_status = $USER->verifyRegistration($_POST['u'], $_POST['confirm'], $pass);
		}
		if ($confirmation_status === "ok") {
			$smarty->assign("user", $GLOBALS['USER']);
			# FIXME caching okay? after successful password change, the "success" page does not show the admin menu
		}
	}
	if ($confirmation_status === 'auth' || $confirmation_status === 'csrf') {
		/* give user a chance to try again */
		$smarty->assign('query_confirm', stripslashes(trim($_POST['confirm'])));
		$smarty->assign('query_u', stripslashes(trim($_POST['u'])));
		$smarty->assign('query_pass', stripslashes(trim($_POST['password'])));
		$smarty->assign('confirmpass', $confirmpass);
		$smarty->assign('confirmmail', $confirmmail);
		$smarty->assign('confirmreg', $confirmreg);
		if ($confirmation_status === 'auth') {
			$smarty->assign('lock_seconds', $USER->lock_seconds);
		}
		$template = 'register_confirm.tpl';
	}
	$smarty->assign('confirmation_status', $confirmation_status);
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


$smarty->display($template);

?>
