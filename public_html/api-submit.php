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

require_once('geograph/global.inc.php');
require_once('geograph/uploadmanager.class.php');
require_once('geograph/gridsquare.class.php');

init_session();

$USER->mustHavePerm('basic');

$db = GeographDatabaseConnection(false);


function failMessage($text) {
	print "<p>$text</p>";
	exit;
}

	$um = new UploadManager();
	$gs = new GridSquare();


	$gs->setByFullGridRef($_POST['grid_reference']);
	if ($gs->errormsg != "") {
		failMessage($gs->errormsg);
	}

	$takendate = strtotime($_POST['imagetaken']);

	if ($takendate > time()) {
		failMessage("Date taken in future");
	}

	// set up attributes from uploaded data

	$um->setSquare($gs);
	$um->setViewpoint($_POST['photographer_gridref']);
	$um->setUse6fig(stripslashes($_POST['use6fig']));
	$um->setDirection($_POST['view_direction']);
	$um->setTaken(date('Y-m-d',$takendate));
	$um->setTitle($_POST['title']);
	$um->setComment($_POST['comment']);

	if (!empty($_POST['imageclass'])) {
		if (preg_match('/subject:(.*)/',$_POST['imageclass'],$m)) {
			$um->setSubject($m[1]);
		} else
			$um->setClass($_POST['imageclass']);
	}
	if (!empty($_POST['tags'])) {
		if (is_array($_POST['tags'])) {
			$um->setTags($_POST['tags']);
		} else {
			$um->setTags(preg_split('/\s*;\s*/',trim(utf8_decode($_POST['tags']))));
		}
	}
	if (!empty($_POST['contexts'])) {
		$um->setContexts($_POST['contexts']);
	}

	if ($_POST['pattrib'] == 'other') {
		$um->setCredit(stripslashes(utf8_decode($_POST['pattrib_name'])));
	} elseif ($_POST['pattrib'] == 'self') {
		$um->setCredit('');
	}

	$um->setLargestSize($_POST['largestsize']);

	if (!empty($_POST['jpeg_url'])) {
		$um->processURL($_POST['jpeg_url']);
	} elseif (!empty($_FILES['jpeg_exif']['tmp_name'])) {
		$um->processUpload($_FILES['jpeg_exif']['tmp_name']);
	}

	// where there any errors back from the image processing?
	// if so, JUppy needs to know...

	if ($um->error != "") {
		failMessage($um->error);
	} else {
		// so far so good... can we commit the submission?
		$method = 'api';
		if (!empty($_POST['method']) && preg_match('/^\w+$/',$_POST['method']))
			$method = $_POST['method'];
		$rc = $um->commit($method);
		if ($rc == "") {
			//clear user profile
			$ab=floor($USER->user_id/10000);
			$smarty = new GeographPage;
			$smarty->clear_cache(null, "user$ab|{$USER->user_id}");

			if (!empty($_GET['mobile'])) {
				header("Location: /submit-mobile.php?another=1&id={$um->gridimage_id}",true,302);
			}


			if (!empty($_GET['mobile'])) {
				print '<meta name="viewport" content="width=device-width, initial-scale=1">';
			}

			print "Submission Successful";
			print "<hr>";
			print "ID: <a href=\"http://www.geograph.org.uk/photo/{$um->gridimage_id}\">{$um->gridimage_id}</a>";

			if (!empty($_GET['mobile'])) {
				print "<hr>";
				print "<a href=/submit-mobile.php?another=1>Submit Another</a>";
			}

		} else {
			failMessage($rc);
		}
	}


