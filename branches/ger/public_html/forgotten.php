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

//pick up email from url? login form can send this to us...
if (isset($_GET['email']))
{

	

	$smarty->assign('email', stripslashes(trim($_GET['email'])));
}

//process reminder?
if (isset($_POST['reminder']))
{
	$smarty->assign('email', stripslashes(trim($_POST['reminder'])));
	$smarty->assign('password1', stripslashes(trim($_POST['password1'])));
	$smarty->assign('password2', stripslashes(trim($_POST['password2'])));
	
	$errors=array();
	$ok=$USER->sendReminder($_POST['reminder'], $_POST['password1'], $_POST['password2'], $errors);
	if ($ok)
	{
		$smarty->assign('sent', true);
	}
	else
	{
		$smarty->assign('errors', $errors);
	}
	
}
	
$smarty->display('forgotten.tpl');

	
?>
