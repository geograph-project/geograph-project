<?php
/**
 * $Project: GeoGraph $
 * $Id: view.php 5653 2009-08-10 18:43:17Z hansjorg $
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

if (!isset($_POST['action'])) {
	header("HTTP/1.0 404 Not Found");
	header("Status: 404 Not Found");
	print "<html><head><title>Invalid request</title></head><body><h1>Invalid request</h1></body></html>";
	exit;
}

// on success, return status + ':' + optional additional information (status >= 0)
// on error, return status + ':' + additional information + ':' + message (status < 0)

if ($_POST['action'] === 'CSRF_token') {
// status:
//    -1: invalid request
//     0: success, additional information = CSRF_token
	print '0:' . $_SESSION['CSRF_token'];
	exit;

} elseif ($_POST['action'] === 'login') {
// status:
//    -5: invalid csfr token
//    -4: authentication failed, additional information = $lock_seconds
//    -3: invalid parameter given by user
//    -2: invalid parameter
//    -1: invalid request
//     0: success

	if (!isset($_POST['u']) || !preg_match('/^\s*[1-9][0-9]*\s*$/', $_POST['u'])) {
		print "-2:0:invalid user id";
		exit;
	}
	$uid = intval($_POST['u']);
	if (!isset($_POST['password'])) {
		print "-2:0:no password given";
		exit;
	}
	$oldsu = mb_substitute_character();
	//if (!mb_substitute_character(0)) { // php only allows 0x0001...0xffff
	if (!mb_substitute_character(1)) {
		trigger_error("could not change substitute character", E_USER_WARNING);
	}
	$_POST['password'] = mb_convert_encoding($_POST['password'], 'Windows-1252', 'UTF-8'); // use Windows-1252 despite declaring latin1, see http://www.w3.org/TR/2009/WD-html5-20090423/infrastructure.html#character-encodings-0 or http://dev.w3.org/html5/spec/parsing.html#character-encodings-0
	mb_substitute_character($oldsu);
	//if (strpos($ticketnote, chr(0)) !== false) {
	if (strpos($_POST['password'], chr(1)) !== false) {
		trigger_error("invialid password", E_USER_WARNING);
		print "-3:0:could not convert character in password";
		exit;
	}
	$errors = $USER->login(true, $uid, true, true);
	if (!count($errors)) {
		print "0:success";
		exit;
	}
	if (isset($errors['csrf'])) {
		print "-5:0:invalid csfr token, please try again";
		exit;
	} elseif (isset($errors['password'])) {
		print "-4:".$USER->lock_seconds.":authentication failed, login blocked for ".$USER->lock_seconds.' seconds';
		exit;
	} else {
		print "-2:0:invalid user or unknown error";
		exit;
	}

} else {
	trigger_error("invalid request '{$_POST['action']}'", E_USER_WARNING);
	print "-1::invalid request";
	exit;
}

header("HTTP/1.0 500 Server Error");
header("Status: 500 Server Error");

?>
