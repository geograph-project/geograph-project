<?php
/**
 * $Project: GeoGraph $
 * $Id: juploader.php 8210 2014-11-29 21:52:36Z geograph $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2008 David Morris 
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
 
 
 
/**
*
* This routine handles all the initial comms with the uploader
* java applet.
*
* Most of the returns are in XML form which makes it easy to test
* and teh input comes in as POST methods. 
*
* We always return a 'status' element which will be 'OK' if everything
* is going to plan or contains the error message if it went belly up.
*
* All the returns go via 'returnXML()' which never returns.
*
**/

require_once('geograph/global.inc.php');
require_once('geograph/uploadmanager.class.php');
require_once('geograph/gridsquare.class.php');

init_session();

$action = isset($_GET['action']) ? $_GET['action'] : "";

$xml = array();
$db = GeographDatabaseConnection(true);
if (empty($db)) {
	$xml['status'] = 'Server Error: Unable to connect to database';
	returnXML();
}

switch($action) {

case "getclass":
	GetImageClassList();
	break;
case "login":
	AuthenticateUser();
	break;

case "upload":
	UploadPicture();
	break;
}

function strtotime_uk($str) {
    $str = preg_replace("/^\s*([0-9]{1,2})[\/\. -]+([0-9]{1,2})[\/\. -]*([0-9]{0,4})/", "\\2/\\1/\\3", $str);
    return strtotime(trim($str,'/'));
}

function UploadPicture() {
	global $CONF;
	global $xml;
	global $USER;

	if (empty($_POST['userid']) || !intval($_POST['userid'])) {
		$xml['status'] = 'Not Logged In';
		returnXML();
	} else {
		$USER = new GeographUser(intval($_POST['userid']));
		
		//TODO: check validation hash?
		if($_POST['validation'] != hash_hmac('md5',$USER->salt,$CONF['register_confirmation_secret'])) {
			$xml['status'] = 'User not verified';
			returnXML();
		}
		
		if (!$USER->registered || !$USER->hasPerm('basic')) {
			$xml['status'] = 'Not authorised to post';
			returnXML();
		}

	}
	
	$um = new UploadManager();
	$gs = new GridSquare();

	// this is the check that we like the client and any image has
	// come in with the appropriate cc licence

	$ccl = $_POST['cclicence'];
	if ($ccl != "I grant you the permission to use this submission " . 
		"under the terms of the Creative Commons by-sa-2.0 licence") {
		$xml['status'] = 'Bad client submission';
		returnXML();
	}

	// validate the grid square - we may be going back to the user
	// quickly here :-)

	$gs->setByFullGridRef($_POST['subject']);
	if ($gs->errormsg != "") {
		$xml['status'] = $gs->errormsg;
		returnXML();
	}

	$takendate = strtotime_uk($_POST['date']);
	
	if ($takendate > time()) {
		$xml['status'] = "Date taken in future";
		returnXML();
	}
	
	// set up attributes from uploaded data

	$um->setSquare($gs);
	$um->setViewpoint($_POST['photographer']);
	$um->setDirection($_POST['direction']);
	$um->setTaken(date('Y-m-d',$takendate));
	$um->setTitle($_POST['title']);
	$um->setComment($_POST['comments']);

	if (preg_match('/subject:(.*)/',$_POST['feature'],$m)) {
		$um->setSubject($m[1]);
	} else
		$um->setClass($_POST['feature']);
	
	$um->setUserStatus($_POST['supplemental']);
	
	if (!empty($_POST['largestsize'])) { //juppy doesnt send this, but allow for the possiblity.
		$um->setLargestSize(intval($_POST['largestsize']));
	} elseif (!empty($USER->upload_size)) {
		$um->setLargestSize($USER->upload_size);
	} else {
		$um->setLargestSize(1024); //other submissions methods implement a default of 1024 now. 
	}

	$um->processUpload($_FILES['uploadfile']['tmp_name']);

	// where there any errors back from the image processing?
	// if so, JUppy needs to know...

	if ($um->error != "") {
		$xml['status'] = $um->error;
	} else {
		// so far so good... can we commit the submission?
		$rc = $um->commit('juploader');
		if ($rc == "") {
			//clear user profile
			$ab=floor($USER->user_id/10000);
			$smarty = new GeographPage;
			$smarty->clear_cache(null, "user$ab|{$USER->user_id}");
		
			$xml['status'] = "OK";
		} else {
			$xml['status'] = $rc;
		}
	}

	returnXML();
}

function AuthenticateUser() {
	global $db, $xml;
	global $CONF, $USER;

	if (empty($_GET['username']) || empty($_GET['password'])) {
		$xml['status'] = 'Invalid';
		returnXML();
	}

	//use the basic auth function to validate username/password (rather than doing it outselfs)
	$_SERVER['PHP_AUTH_USER'] = $_GET['username'];
	$_SERVER['PHP_AUTH_PW'] = $_GET['password'];

	$USER->basicAuthLogin();
	//this actully never returns if can't login!

	if ($USER->registered && $USER->user_id) {

                if (!empty($CONF['juppy_minimum_images'])) {

                        // a user must have submitted a minimum number of images

                        $icount = $db->getOne("select images from user_stat where user_id = " .intval($USER->user_id));
                        if ($icount < $CONF['juppy_minimum_images']) {
                                $xml['status'] = "You need to have submitted " . $CONF['juppy_minimum_images'] .
                                    " image(s) using web submission before you can use JUppy. Sorry";
                                    returnXML();
                        }
                }

		// let's assume they're OK to post
		$xml['status'] = 'OK';
		$xml['realname'] = $USER->realname;
		$xml['user_id'] = $USER->user_id;

		//TODO: send validation hash?
		$xml['validation'] = hash_hmac('md5',$USER->salt,$CONF['register_confirmation_secret']);

		returnXML();
	}
}

function GetImageClassList() {

	global $db;
	global $xml;

	$sql = "select subject from subjects order by subject";

	$classlist = "";

	$prev_fetch_mode = $db->SetFetchMode(ADODB_FETCH_NUM);
	if($rs = &$db->Execute($sql)) {
		while(!$rs->EOF) {
			if ($classlist == "") {
				$classlist = "subject:" . $rs->fields[0];
			} else {
				$classlist .= "}subject:" . $rs->fields[0];
			}
			$rs->moveNext();
		}
	}
	$db->SetFetchMode($prev_fetch_mode);
	$xml['status'] = 'OK';
	$xml['classlist'] = $classlist;
	returnXML();
}

function returnXML() {
	global $xml;

	$xmlstring = "<document>";
	foreach($xml as $tag => $value) {
		$xmlstring .= "<$tag>$value</$tag>\n";
	}
	$xmlstring .= "</document>";
	echo $xmlstring;
	exit;
}

