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

if (isset($_GET['confirm']))
{
	if ($USER->verifyRegistration($_GET['u'], $_GET['confirm']))
	{
		$smarty->assign('confirmation_ok', true);
		$smarty->assign("user", $GLOBALS['USER']);
	}
	else
	{
		$smarty->assign('confirmation_failed', true);
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


$smarty->display('register.tpl');

?>
