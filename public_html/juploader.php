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
		if($_POST['validation'] != md5($_POST['userid'].'#'.$CONF['register_confirmation_secret'])) {
			$xml['status'] = 'User not verified';
			returnXML();
		}
		
		if (!$USER->user_id || !$USER->hasPerm('basic')) {
			$xml['status'] = 'Not authorised to post';
			returnXML();
		}

	}
	
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
	$um->setClass($_POST['feature']);
	$um->setUserStatus($_POST['supplemental']);

	$um->processUpload($tmpfile);

	// where there any errors back from the image processing?
	// if so, JUppy needs to know...

	if ($um->error != "") {
		$xml['status'] = $um->error;
	} else {
		// so far so good... can we commit the submission?
		$rc = $um->commit('juploader'));
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
	global $username, $password;
	global $CONF;

	$dbusername = $db->Quote($username);
	$sql = "select password,realname,rights,user_id from user where nickname = $dbusername OR email = $dbusername LIMIT 1";
	
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

                if ($CONF['juppy_minimum_images']) {
                
                        // a user must have submitted a minimum number of images
                        
                        $sqlcnt = "select count(*) as icount from gridimage_search where user_id = '" . $rs->fields[3] . "'";
                        $icount = 0;
                        if ($rsimg = &$db->Execute($sqlcnt)) {
                                $icount = $rsimg->fields[0];
                        }
                        if ($icount < $CONF['juppy_minimum_images']) {
                                $xml['status'] = "You need to have submitted " . $CONF['juppy_minimum_images'] .
                                    " image(s) using web submission before you can use JUppy. Sorry";
                                    returnXML();
                        }
                }
                
		// let's assume they're OK to post
		$xml['status'] = 'OK';
		$xml['realname'] = $rs->fields[1];
		$xml['user_id'] = $rs->fields[3];
		
		//TODO: send validation hash?
		$xml['validation'] = md5($rs->fields[3].'#'.$CONF['register_confirmation_secret']);
		
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
