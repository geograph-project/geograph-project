<?php
/**
 * $Project: GeoGraph $
 * $Id: contact.php 6600 2010-04-05 14:17:46Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2011 Barry Hunter (geo@barryhunter.co.uk)
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

//you must be logged in to submit images
$USER->mustHavePerm("basic");

pageMustBeHTTPS();

dieIfReadOnly();

if (!empty($_POST['choose'])) {
	if (!empty($_POST['save'])) {
		$USER->setPreference('submit.mobile',$_POST['choose']);
	}
} else {
	$_POST['choose'] = $USER->getPreference('submit.mobile','',true);
}

if (!empty($_POST['choose']) && empty($_GET['redir'])) {
	switch($_POST['choose']) {
		//case 'single' ... falls though to below!
		case 'multi': header("Location: /submit-multi.php?tab=upload&mobile=1", false, 302); exit;
		case 'v1': header("Location: /submit.php?redir=false", false, 302); exit;
		case 'v2': header("Location: /submit2.php?display=mobile&redir=false", false, 302); exit;
	}

	$smarty = new GeographPage;
	if (!empty($_GET['id']))
		$smarty->assign('id', intval($_GET['id']));
	if (!empty($CONF['os_api_key']))
		$smarty->assign('os_api_key', $CONF['os_api_key']);

	if (!empty($CONF['submission_message'])) {
	        $smarty->assign("status_message",$CONF['submission_message']);
	}

	$smarty->display('submit_mobile.tpl');

} else {

	$smarty = new GeographPage;
	if (!empty($_GET['id']))
		$smarty->assign('id', intval($_GET['id']));
	$smarty->display('submit_mobile_chooser.tpl');
}
