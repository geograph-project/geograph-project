<?php
/**
 * $Project: GeoGraph $
 * $Id: faq.php 15 2005-02-16 12:23:35Z lordelph $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2007 Barry Hunter (geo@barryhunter.co.uk)
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

$USER->mustHavePerm("basic");

$template = 'mapper_captcha.tpl';

$cacheid='mapper';



$ok = true;

$verification = md5($CONF['register_confirmation_secret'].$USER->user_id);
		

if (!isset($_POST['verify']) || empty($_POST['verification']) || $_POST['verification'] != $verification || empty($_SESSION['verification'])  || $_SESSION['verification'] != $verification) {
	$ok = false;
	$smarty->assign('verification', $verification);
} else {
	define('CHECK_CAPTCHA',true);

	require("../stuff/captcha.jpg.php");

	$ok = $ok && CAPTCHA_RESULT;

	if ($ok) {
		$_SESSION['verCount'] = (isset($_SESSION['verCount']))?$_SESSION['verCount']-2:-2;

	} else {
		if (isset($_SESSION['verCount']) && $_SESSION['verCount'] > 3) {
			$smarty->assign('error', "Too many failures please try again later");
		} else {
			$smarty->assign('verification', $verification);
			$smarty->assign('error', "Please Try again");
		}
		$ok = false;
		
		$_SESSION['verCount'] = (isset($_SESSION['verCount']))?$_SESSION['verCount'] +1:1;
	} 
}

if ($ok) {

	if (isset($_SESSION['maptt'])) 
		unset($_SESSION['maptt']);
	
	header("Location: /mapper/".((!empty($_REQUEST['token']))?"?t={$_REQUEST['token']}":'') );
}

$_SESSION['verification'] = $verification;

if (!empty($_REQUEST['token'])) {
	$smarty->assign('token',$_REQUEST['token']);
}

$smarty->display($template, $cacheid);

	
?>
