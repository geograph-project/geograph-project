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


function failMessage($text, $um = null) {
	print "<p>".htmlentities($text)."</p>";
	if (!empty($um->existing)) {
		$existing = intval($um->existing);
		echo "<p>This image was already processed as ID: <strong>$existing</strong>.</p>";
	}
	exit;
}

	$um = new UploadManager();
	$gs = new GridSquare();


	$gs->setByFullGridRef($_POST['grid_reference']);
	if (!empty($gs->errormsg)) {
		failMessage($gs->errormsg);
	}

	$takendate = parseDate($_POST['imagetaken'] ?? '');
	if (!$takendate) {
	        failMessage("Invalid date format or out of range (must be > 1800)");
	} elseif ($takendate > date('Y-m-d')) {
		failMessage("Date taken in future");
	}

	// set up attributes from uploaded data

	$um->setSquare($gs);
	$um->setViewpoint($_POST['photographer_gridref']);
	if (!empty($_POST['use6fig']))
		$um->setUse6fig(stripslashes($_POST['use6fig']));
	$um->setDirection($_POST['view_direction']);
	$um->setTaken($takendate);
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

	if (!empty($_POST['jpeg_data'])) {
		$ok = $um->processDataURL($_POST['jpeg_data'], $_POST['jpeg_filename'] ?? null);
	} elseif (!empty($_POST['jpeg_url'])) {
		$ok = $um->processURL($_POST['jpeg_url']);
	} elseif (!empty($_FILES['jpeg_exif']['tmp_name'])) {
		$ok = $um->processUpload($_FILES['jpeg_exif']['tmp_name']);
	}

	if ($ok && isset($_POST['orientation']) && strlen($_POST['orientation'])) { //note it can be '0'!
		if ($_POST['orientation'] === '0') {
			//special flag to indicate just reset exif flag!
			$result = $um->resetOrientation($um->upload_id);
			//resetOrientation doesnt give a new id, updates in place!
		} else {
			$result = $um->rotateUpload($um->upload_id,intval($_POST['orientation']), 1);
			if (!empty($result['upload_id'])) {
				//returns the new upload_id, rotateUpload works like a static method!
				$um->upload_id = $result['upload_id'];
				//$uploadmanager->upload_width= ... commit doesnt NEED these!
			}
		}
	}

	// where there any errors back from the image processing?

	if (!empty($um->errormsg)) {
		failMessage($um->errormsg);
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
			failMessage($rc, $um);
		}
	}


