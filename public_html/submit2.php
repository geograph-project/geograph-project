<?php
/**
 * $Project: GeoGraph $
 * $Id: submit2.php 8537 2017-08-15 11:35:16Z barry $
 *
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2008 Barry Hunter (geo@barryhunter.co.uk)
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
 **/

require_once('geograph/global.inc.php');
require_once('geograph/submissionhelper.class.php');

if (isset($_POST['finalise'])) {
	//so the back button works.
	 session_cache_limiter('none');
}

if (empty($_POST) && empty($_GET)) {
	$_GET['mobile'] = 0; //otherwise init_session will automatically redirect!
        $mobile_url = "https://{$_SERVER['HTTP_HOST']}/submit-mobile.php"; //speciically https, as we may be http
}


init_session();

$smarty = new GeographPage;

dieIfReadOnly();

if (!empty($mobile_browser))
	 $smarty->assign("mobile_browser", 1);

if (!empty($CONF['submission_message'])) {
        $smarty->assign("status_message",$CONF['submission_message']);
}

//you must be logged in to submit images
$USER->mustHavePerm("basic");

pageMustBeHTTPS();

$template = 'submit2.tpl';

if (!empty($_GET['display']) && $_GET['display'] == 'tabs') {
	$template = 'submit2_tabs.tpl';
}
if (!empty($_GET['display']) && $_GET['display'] == 'mobile') {
	$template = 'submit2_mobile.tpl';
}

$cacheid='';

$clear_cache = array();

if (isset($_FILES['jpeg_exif']))
{
	$uploadmanager=new UploadManager;

	$smarty = SubmissionHelper::handleFileUpload($_FILES['jpeg_exif']);
	if ($smarty->get_template_vars('error')) {
		// error handled
	}
	elseif ($uploadmanager->processUpload($_FILES['jpeg_exif']['tmp_name']))
	{
		$upload_to_process=true;

		$smarty->assign('filename',basename(str_replace("\\",'/',$_FILES['jpeg_exif']['name'])));

		$smarty->assign('success', 1);
	} else {
		$smarty->assign('error', $uploadmanager->errormsg);
		$uploadmanager->errormsg = '';
	}

} elseif (!empty($_POST['jpeg_data'])) {
	$uploadmanager=new UploadManager;
	if ($uploadmanager->processDataURL($_POST['jpeg_data'])) {
		$upload_to_process=true;
		$smarty->assign('success', 1);

        } else {
                $smarty->assign('error', $uploadmanager->errormsg);
                $uploadmanager->errormsg = '';
        }

} elseif (!empty($_POST['jpeg_url'])) {
        $uploadmanager=new UploadManager;

	if ($uploadmanager->processURL($_POST['jpeg_url'])) {

		$upload_to_process=true;

		if (!empty($GLOBALS['http_response_header'])) {
			foreach ($GLOBALS['http_response_header'] as $header) {
				if (preg_match('/filename="(.*?)"/',$header,$m)) {
					$smarty->assign('filename',$m[1]);
				}
			}
		}

                $smarty->assign('success', 1);
        } else {
                 $smarty->assign('error', $uploadmanager->errormsg);
                 $uploadmanager->errormsg = '';
	}

} elseif (isset($_POST['finalise'])) {
	$status = array();
	$filenames = array();

	list($usec, $sec) = explode(' ',microtime());
	$GLOBALS['STARTTIME'] = ((float)$usec + (float)$sec);

	foreach ($_POST['upload_id'] as $key => $upload_id) {
		$uploadmanager = new UploadManager();
		$square = new GridSquare();

		$filenames[$key] = $_POST['title'][$key];

		$ok = $square->setByFullGridRef($grid_reference = $_POST['grid_reference'][$key]);
		if ($ok) {
			$postData = array(
				'title' => $_POST['title'][$key],
				'comment' => $_POST['comment'][$key],
				'imagetaken' => $_POST['imagetaken'][$key],
				'tags' => $_POST['tags'][$key],
				'subject' => $_POST['subject'][$key],
				'imageclass' => $_POST['imageclass'][$key],
				'photographer_gridref' => $_POST['photographer_gridref'][$key],
				'view_direction' => $_POST['view_direction'][$key],
				'use6fig' => $_POST['use6fig'][$key],
				'user_status' => $_POST['user_status'][$key],
				'largestsize' => $_POST['largestsize'][$key],
				'pattrib' => $_POST['pattrib'],
				'pattrib_name' => $_POST['pattrib_name'],
			);

			$ok = $uploadmanager->setUploadId($_POST['upload_id'][$key]);

			if ($ok) {
				$err = SubmissionHelper::finaliseSubmission($uploadmanager, $postData, $square);

				if (empty($err)) {
					$status[$key] = "ok:".$uploadmanager->gridimage_id;
				} else {
					$status[$key] = $err;
				}
			} else {
				$status[$key] = $uploadmanager->errormsg;
			}
		} else {
			$status[$key] = "Subject Grid Reference: ".$square->errormsg;
		}
		SubmissionHelper::setLastSubmissionDetails($_POST, $square);

                $smarty->assign_by_ref('uploadmanager',$uploadmanager);

		$clear_cache[$square->gridsquare_id] = 1;
	}

	$template='puploader_success.tpl';
	if (isset($_GET['nofrills']))
		$smarty->assign('nofrills', 1);
	if (isset($_REQUEST['display']) && $_REQUEST['display'] == 'tabs')
		$smarty->assign('display', 'tabs');
	if (isset($_REQUEST['display']) && $_REQUEST['display'] == 'mobile')
		$smarty->assign('display', 'mobile');
	$smarty->assign('submit2', 1);
	$smarty->assign('status', $status);
	$smarty->assign('filenames', $filenames);
	$smarty->assign('grid_reference', $grid_reference);

} elseif (isset($_GET['transfer_id'])) {
	$smarty_vars = SubmissionHelper::handleTransferId($_GET['transfer_id']);
	if ($smarty_vars) {
		foreach ($smarty_vars as $key => $value) {
			$smarty->assign($key, $value);
		}
		$upload_to_process = true;
		$smarty->assign('success', 1);
	} else {
		die("invalid id");
	}
}

if (!empty($upload_to_process) && !empty($uploadmanager) && $uploadmanager->upload_id) {

	$post = SubmissionHelper::processExifAndFilename($uploadmanager, $_FILES['jpeg_exif']['name']);
	foreach ($post as $key => $value) {
		$smarty->assign($key, $value);
	}

	SubmissionHelper::fixOrientation($uploadmanager, $smarty);

	if (isset($uploadmanager->exifdate)) {
		$smarty->assign('imagetaken', $uploadmanager->exifdate);
	}
}

$step = null;
if (isset($_REQUEST['inner'])) {
	$template='submit2_inner.tpl';

	if (!empty($_REQUEST['grid_reference']))
	{
		$step = 2;
		$square=new GridSquare;

		$ok= $square->setByFullGridRef($_REQUEST['grid_reference']);

		if ($ok) {
			$_REQUEST['grid_reference'] = SubmissionHelper::normaliseGridReference($_REQUEST['grid_reference']);

			$smarty->assign('grid_reference', $grid_reference = $_REQUEST['grid_reference']);

			$smarty->assign('success', 1);
		} else {
			$smarty->assign('errormsg', $square->errormsg);
		}
	} elseif (isset($_GET['step']) && $_GET['step'] == 0) {
		$step = 0;

		$uploadmanager=new UploadManager;

	        if (!empty($_GET['delete']) && $uploadmanager->validUploadId($_GET['delete']) ) {
			$uploadmanager->setUploadId($_GET['delete'],false);
		        $uploadmanager->cleanUp();
	        }
		$data = $uploadmanager->getUploadedFiles();

		if (!empty($_GET['one'])) {
			$smarty->assign('one', 1);
			 $smarty->assign('item',array_shift($data));
		}

		$smarty->assign_by_ref('data',$data);
	} else {
		$step = 1;
	}

	$smarty->assign('step', $step);

	if (!empty($_REQUEST['container'])) {
		$smarty->assign('container', $_REQUEST['container']);
	}
}

if ($_SERVER['REQUEST_METHOD'] == 'GET' && $step !== 0 && empty($CONF['submission_message'])) {
	customExpiresHeader(900,false,true);
}

if (!empty($_REQUEST['multi'])) {
	$smarty->assign('multi', 1);
}

if ($template=='puploader_success.tpl' && !$smarty->is_cached($template, $cacheid)) {
	$news = SubmissionHelper::getNews();
	if ($news) {
		$smarty->assign_by_ref('news', $news);
	}
}


$smarty->display($template, $cacheid);

flush();

//things that can be done 'lazy' ie after given confirmation to the user...

if (!empty($_POST['pattrib_default'])) {
	$USER->setCreditDefault(($_POST['pattrib'] == 'other')?stripslashes($_POST['pattrib_name']):'');
}

if (!empty($clear_cache) && count($clear_cache)) {

	foreach ($clear_cache as $gridsquare_id => $dummy) {
		if ($memcache->valid) {
			//the submit list
			$mkey = md5("{$gridsquare_id}:{$USER->user_id},,order by submitted desc limit 6");
			$memcache->name_delete('gi',$mkey);
			//the browse page for the user (to show pending)
			$mkey = md5("{$gridsquare_id}:{$USER->user_id},,order by if(ftf between 1 and 4,ftf,5),gridimage_id");
			$memcache->name_delete('gi',$mkey);
		}
	}

	//clear user profile
	$ab=floor($USER->user_id/10000);
	$smarty->clear_cache(null, "user$ab|{$USER->user_id}");


}
