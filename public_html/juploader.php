<?php

/**
*
* This routine handles all the initial comms with the uploader
* java applet.
*
* Most of the returns are in XML form which makes it easy to test
* and teh input comes in as POST methods. All posts should contain
* the username and password to enable authentication.
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
$username = isset($_GET['username']) ? $_GET['username'] : "";
$password = isset($_GET['password']) ? $_GET['password'] : "";

// echo $action . " " . $username . " " . $password . "\n";

$xml = "";
$db = NewADOConnection($GLOBALS['DSN']);
if (empty($db)) {
	$xml['status'] = 'Error connecting to database';
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

function UploadPicture() {
	global $_POST;
	global $_FILES;
	global $xml;
	global $USER;

	$tmpfile = $_FILES['uploadfile']['tmp_name'];

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

	// set up attributes from uploaded data

	$um->setSquare($gs);
	$um->setViewpoint($_POST['photographer']);
	$um->setDirection($_POST['direction']);
	$um->setTaken($_POST['date']);
	$um->setTitle($_POST['title']);
	$um->setComment($_POST['comments']);
	$um->setClass($_POST['feature']);
	$um->setUserStatus($_POST['supplemental']);

	// TODO: at some point, we need to make sure $USER is set

	$USER->user_id = $_POST['userid']; 

	$um->processUpload($tmpfile);

	// where there any errors back from the image processing?
	// if so, JUppy needs to know...

	if ($um->error != "") {
		$xml['status'] = $um->error;
	} else {
		// so far so good... can we commit the submission?
		$rc = $um->commit();
		if ($rc == "") {
			$xml['status'] = "OK";
		} else {
			$xml['status'] = $rc;
		}
	}

	returnXML();
}

function AuthenticateUser() {
	global $db, $xml;
	global $username, $password;

	$sql = "select password,realname,rights,user_id from user where nickname = '$username' LIMIT 1";
	
	if ($rs = &$db->Execute($sql)) {
		if ($password != $rs->fields[0]) {

			// oops - user specified invlaid password

			$xml['status'] = 'Invalid password';

			returnXML();
		}

		// user must have some rights - I think any will do

		if ($rs->fields[2] == "") {
			$xml['status'] = 'Not authorised to post';
			returnXML();
		}

		// let's assume they're OK to post
		$xml['status'] = 'OK';
		$xml['realname'] = $rs->fields[1];
		$xml['user_id'] = $rs->fields[3];
		returnXML();
	}
}

function GetImageClassList() {

	global $db;
	global $xml;

	$sql = "select imageclass,count(*) as cnt from gridimage_search "
		. "group by imageclass having cnt > 5 and "
		. "length(imageclass) > 0";

	$classlist = "";

	if($rs = &$db->Execute($sql)) {
		while(!$rs->EOF) {
			if ($classlist == "") {
				$classlist = $rs->fields[0];
			} else {
				$classlist .= "}" . $rs->fields[0];
			}
			$rs->moveNext();
		}
	}
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
?>
