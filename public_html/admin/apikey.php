<?php
/**
 * $Project: GeoGraph $
 * $Id: apikeys.php 945 2005-06-29 22:22:57Z barryhunter $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Barry Hunter (geo@barryhunter.co.uk)
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

$db = NewADOConnection($GLOBALS['DSN']);
if (!$db) die('Database connection failed');

$arr= array();

	if (!empty($_POST['submit'])) {

		if (empty($_POST['email']) || !isValidEmailAddress($_POST['email'])) {
			$arr = $_POST;
			$smarty->assign('message', "ERROR: Please enter your email address");
		} else {
			//can go ahead and add it
			$updates = array();

			$updates[] = "`added_by` = {$USER->user_id}";
			$updates[] = "`crt_timestamp` = NOW()";

			//loop though all and create the update array
			foreach (array('homepage_url','comments','email','type') as $key)
				if (!empty($_POST[$key])) {
					$updates[] = '`'.$key.'` = '.$db->Quote($_POST[$key]);
				}

			$key = substr(md5(uniqid('geograph',true)),1,10);
			while ($db->getOne("SELECT COUNT(*) FROM apikeys WHERE apikey = '$key'")) {
				$key = substr(md5(uniqid('geograph',true)),1,10);
			}
			 $updates[] = "apikey = '$key'";

			$db->Execute($sql = 'INSERT INTO apikeys SET '.implode(',',$updates));

			$smarty->assign('message', "Thank you for requesting a key. Here it is: <b><tt>$key</tt></b> - please let us know if you have any questions. <a href='/help/api'>Back to documentation</a>");


mail("barry@barryhunter.co.uk","[Geograph] New API Key Issued",
"New key: $key, issued to {$_POST['name']} \n\n{$_POST['homepage_url']}\n\n {$_POST['comments']}\n\nType: {$_POST['type']}",
"From: Geograph Website <noreply@geograph.org.uk>");

mail($_POST['email'],"[Geograph] New API Key Issued",
"Thank you for requesting a API for geograph.org.uk. Please find below the key for your records:\n\n$key\n\n Thank you, Geograph Team.\n\n PS. Link to documentation: http://www.geograph.org.uk/help/api \n\nand if you have any questions, please get in http://www.geograph.org.uk/contact.php",
"From: Geograph Website <noreply@geograph.org.uk>");


		}

	}

	$smarty->assign('arr', $arr);


$smarty->display('admin_apikey.tpl');


