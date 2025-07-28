<?php
/**
 * $Project: GeoGraph $
 * $Id: submit.php 8842 2018-09-17 17:54:55Z barry $
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

foreach(array('setpos','setpos2','grid_reference','gridref','eastings','northings','gridsquare') as $key)
	if (!empty($_REQUEST[$key]) && !preg_match('/^[\w \.>]*$/',$_REQUEST[$key]) && !preg_match("/^\s*\b(-?\d+\.?\d*)\xB0?\s*[NS]*\s*[, ]+(-?\d+\.?\d*)\xB0?\s*([EW]*)\s*$/i",$_REQUEST[$key])) {	//specifcally allow lat/long in grid_reference.
	     header('HTTP/1.0 451 Unavailable For Legal Reasons');
	     exit;
	}

require_once('geograph/global.inc.php');

dieIfReadOnly();

if (empty($_POST) && empty($_GET)) { //specifially want to avoid ?redir=false, but actully GET used for other things, so avoid ALL
	//init_session will automatically redirect!
	$mobile_url = "https://{$_SERVER['HTTP_HOST']}/submit-mobile.php"; //speciically https, as we may be http
}

if (isset($_GET['preview'])) {
	session_cache_limiter('none');
} else {
	require_once('geograph/gridimage.class.php');
	require_once('geograph/gridsquare.class.php');
}

require_once('geograph/uploadmanager.class.php');
require_once('geograph/submissionhelper.class.php');

init_session();

if (empty($USER->registered) && $_SERVER['REQUEST_METHOD'] == 'POST') {
	ob_start();
	foreach ($_SERVER as $key => $value) {
                if (strpos($key,'CONF') === 0 || strpos($key,'SERVICE_') !== FALSE || strpos($key,'_PORT') !== FALSE || strpos($key,'SHOWCASE') === 0)
                        continue;
                print htmlentities($key).": ".htmlentities($value)."\n";
        }
	print_r($_POST);
	print_r($_COOKIE);
	print_r($_SESSION);
        $con = ob_get_clean();
        debug_message('[Geograph] Login Failure',$con);
}



$uploadmanager=new UploadManager;

if (isset($_GET['rotate']) && $uploadmanager->validUploadId($_GET['rotate']) && is_numeric($_GET['degrees'])) {
	$result = $uploadmanager->rotateUpload($_GET['rotate'],intval($_GET['degrees']),@intval($_GET['force']));
	outputJSON($result);
	exit;
}
//display preview image?
if (isset($_GET['preview']) && $uploadmanager->validUploadId($_GET['preview']))
{
        header("Content-Type: image/jpeg");
	$uploadmanager->outputPreviewImage($_GET['preview']);
	exit;
}

if (!empty($_GET['grid_reference']) && (!empty($_GET['transfer_id']) || !empty($_GET['setpos'])) && !empty($_GET['redir'])) {
	$_POST = $_GET;
}


list($usec, $sec) = explode(' ',microtime());
$GLOBALS['STARTTIME'] = ((float)$usec + (float)$sec);

$square=new GridSquare;
$smarty = new GeographPage;

if (!empty($mobile_browser))
	 $smarty->assign("mobile_browser", 1);

if (!empty($CONF['submission_message'])) {
        $smarty->assign("status_message",$CONF['submission_message']);
}

if (isset($_SERVER['HTTP_X_PSS_LOOP']) && $_SERVER['HTTP_X_PSS_LOOP'] == 'pagespeed_proxy') {
	$smarty->assign("status_message",'<div class="interestBox" style="background-color:yellow;border:6px solid red;padding:20px;margin:20px;font-size:1.1em;">geograph.org.uk is currently in reduced functionality mode - to deal with traffic levels. <b>The maximum filesize that can be uploaded is now 5Mb.</b> To upload a larger image, please use <a href="http://www.geograph.ie/submit2.php">www.geograph.ie</a> or <a href="http://schools.geograph.org.uk/submit2.php" onclick="location.host = \'schools.geograph.org.uk\'; return false">schools.geograph.org.uk</a> <small>(they upload to the same database)</small></div>');
	$smarty->assign("small_upload",1);
}

if (!$USER->hasPerm("basic")) {
	$smarty->assign("submit",1);
	$smarty->display('static_submit_intro.tpl');
	exit;
}

pageMustBeHTTPS();

if (isset($_SESSION['tab'])) {
	$selectedtab=$_SESSION['tab'];
} else {
	$selectedtab =1;
}

$step=isset($_POST['step'])?intval($_POST['step']):1;

if (!empty($_FILES['jpeg_exif']) && $_FILES['jpeg_exif']['error'] != UPLOAD_ERR_NO_FILE)
{
	//Submit Step 1a..

	$smarty = SubmissionHelper::handleFileUpload($_FILES['jpeg_exif']);
	if ($smarty->get_template_vars('error')) {
		// error handled
	}
	elseif ($uploadmanager->processUpload($_FILES['jpeg_exif']['tmp_name']))
	{
		$smarty->assign('upload_id', $uploadmanager->upload_id);
		$smarty->assign('transfer_id', $uploadmanager->upload_id);

		$smarty->assign('preview_url', "/submit.php?preview=".$uploadmanager->upload_id);
		$smarty->assign('preview_width', $uploadmanager->upload_width);
		$smarty->assign('preview_height', $uploadmanager->upload_height);

		$post = SubmissionHelper::processExifAndFilename($uploadmanager, $_FILES['jpeg_exif']['name']);
		$_POST = array_merge($_POST, $post);

		$_POST['eastings'] = '';
		$selectedtab =3;
	} else {
		$smarty->assign('error', $uploadmanager->errormsg);
		$uploadmanager->errormsg = '';
	}
}


//for every stage after step 1, we expect to get a
//grid reference posted...
if (isset($_POST['gridsquare']))
{
	if (isset($_POST['photographer_gridref'])) {
		$smarty->assign('photographer_gridref', $_POST['photographer_gridref']);
	}
	if (isset($_POST['view_direction']) && strlen($_POST['view_direction'])) {
		$smarty->assign('view_direction', $_POST['view_direction']);
	} else {
		$smarty->assign('view_direction', -1);
	}
	if (!empty($_POST['use6fig'])) {
		$smarty->assign('use6fig', $_POST['use6fig']);
	}

	//ensure the submitted reference is valid
	if (!empty($_POST['grid_reference']) && empty($_POST['setpos2']))
	{
		$ok= $square->setByFullGridRef($_POST['grid_reference']);
		if (!empty($_POST['setpos']) && empty($exif)) { //$exif will be set if processed a geotagged image
			$selectedtab =1;
		}

			$_POST['grid_reference'] = SubmissionHelper::normaliseGridReference($_POST['grid_reference']);

		if (!empty($_POST['location_type']) && $_POST['location_type'] == 'viewpoint') {
			//shift the location into phtographer! (kinda like we do with exif!
			$_POST['photographer_gridref'] = $_POST['grid_reference'];
			$_POST['grid_reference'] = ''; //could use $square->grid_reference, but we've already setup the square, so can leave the box blank
			$smarty->assign('photographer_gridref', $_POST['photographer_gridref']);
		}

		//preserve inputs in smarty
		$smarty->assign('grid_reference', $grid_reference = $_POST['grid_reference']);
	}
	else
	{
		$ok= $square->setGridPos($_POST['gridsquare'], $_POST['eastings'], $_POST['northings']);
		if ($ok)
		{
			//preserve inputs in smarty
			$smarty->assign('grid_reference', $grid_reference = $square->grid_reference);
			$selectedtab =2;
		}
	}
	if ($ok)
	{
		$uploadmanager->setSquare($square);

		$square->rememberInSession();

		//preserve inputs in smarty
		$smarty->assign('gridsquare', $square->gridsquare);
		$smarty->assign('eastings', $square->eastings);
		$smarty->assign('northings', $square->northings);
		$smarty->assign('gridref', $square->grid_reference);

		//store other useful info about the square
		$smarty->assign('imagecount', $square->imagecount);
		$smarty->assign('max_ftf', $square->max_ftf);

		//we're just setting up the position, move to step 2
		if (isset($_POST['setpos']) || isset($_POST['setpos2']))
		{
			//Submit Step 1...

			if (isset($_POST['jpeg_url'])) {
				$smarty->assign('jpeg_url', $_POST['jpeg_url']);
			}

			if (isset($_POST['title'])) {
				//preserve stuff
				$smarty->assign('title', stripslashes($_POST['title']));
				$smarty->assign('comment', stripslashes($_POST['comment']));
				$smarty->assign('imagetaken', stripslashes(@$_POST['imagetaken']));
				$smarty->assign('tags', stripslashes(@$_POST['tags']));
				$smarty->assign('subject', stripslashes(@$_POST['subject']));
				$smarty->assign('imageclass', stripslashes(@$_POST['imageclass']));
				$smarty->assign('user_status', stripslashes(@$_POST['user_status']));
			}
			$step=2;
		}
		elseif (isset($_POST['goback']))
		{
			$step=1;
		}
		elseif (isset($_POST['transfer_id']))
		{
			$smarty_vars = SubmissionHelper::handleTransferId($_POST['transfer_id']);
			if ($smarty_vars) {
				foreach ($smarty_vars as $key => $value) {
					$smarty->assign($key, $value);
				}
				//we ok to continue
				if (isset($_POST['photographer_gridref']) && isset($_POST['view_direction'])) {
					$step=3;
				} else {
					$step=2;
				}
			} else {
				$step=1;
			}
		}
		//see if we have an url to process?
		elseif (isset($_POST['jpeg_url']))
		{
			//Submit Step 2..

			$step=2;
			if ($uploadmanager->processURL($_POST['jpeg_url']))
			{
				$smarty->assign('upload_id', $uploadmanager->upload_id);
				//we ok to continue
				$step=3;
			} else {
				$smarty->assign('error', $uploadmanager->errormsg);
				$uploadmanager->errormsg = '';
			}
		}
		//see if we have an upload to process?
		elseif (isset($_FILES['jpeg']))
		{
			//Submit Step 2..

			$step=2;
			$smarty = SubmissionHelper::handleFileUpload($_FILES['jpeg']);
			if ($smarty->get_template_vars('error')) {
				// error handled
			}
			elseif ($uploadmanager->processUpload($_FILES['jpeg']['tmp_name']))
			{
				$smarty->assign('upload_id', $uploadmanager->upload_id);
				//we ok to continue
				$step=3;
			} else {
				$smarty->assign('error', $uploadmanager->errormsg);
				$uploadmanager->errormsg = '';
			}

			$smarty->assign('filename',basename(str_replace("\\",'/',$_FILES['jpeg']['name'])));
		}
		//user likes the image, lets have them agree to our terms
		elseif (isset($_POST['savedata']))
		{
			//Submit Step 3..

			if (isset($_POST['goback']))
			{
				$step=2;
			}
			else
			{
				//preserve the upload id
				if($uploadmanager->validUploadId($_POST['upload_id'])) {
					$smarty->assign('upload_id', $_POST['upload_id']);
					$uploadmanager->setUploadId($_POST['upload_id']);
				}

				$ok=true;

				//preserve the meta info
				$smarty->reassignPostedDate('imagetaken');
				if ($smarty->get_template_vars('imagetaken')=='0000-00-00')
				{
					$ok=false;
					$error['imagetaken']="Please specify a date for when the photo was taken (even approximate)";
				} elseif (datetimeToTimestamp($smarty->get_template_vars('imagetaken')) > datetimeToTimestamp(date("Y-m-d"))) {
					$ok=false;
					$error['imagetaken']="Time machines are not allowed on Planet Geograph";
				}

				if (!empty($_POST['tags'])) {
					if (is_array($_POST['tags'])) {
						$tags = stripslashes(implode('|',$_POST['tags']));
					} else {
						$tags = stripslashes($_POST['tags']);
					}
					$smarty->assign_by_ref('tags', $tags);
				}

				if (!empty($_POST['subject']))
					$smarty->assign('subject', trim(stripslashes($_POST['subject'])));

				if (!empty($_POST['imageclass'])) {
					if (($_POST['imageclass'] == 'Other' || empty($_POST['imageclass'])) && !empty($_POST['imageclassother'])) {
						$imageclass = stripslashes($_POST['imageclassother']);
					} else if ($_POST['imageclass'] != 'Other') {
						$imageclass = stripslashes($_POST['imageclass']);
					}
				} else {
					$imageclass='';
				}
				if (strlen($imageclass)==0) {
					if (empty($_POST['tags'])) {
						//only show an error, is BOTH empty, but need to report it on both, (if new submit, dont even see imageclass errors!)
						$ok=false;
						$error['tags']="Please choose at least one geographical context type";
						$error['imageclass']="Please choose a geographical feature";
					}
				} else {
					$smarty->assign_by_ref('imageclass', $imageclass);
				}

				$title=trim(stripslashes($_POST['title']));
				$title=strip_tags($title);
				if (strlen($title)==0)
				{
					$ok=false;
					$error['title']="Please specify an image title";
				}
				//preserve title and comment
				$smarty->assign('title', $title);
				$smarty->assign('comment', trim(stripslashes($_POST['comment'])));

				if (!empty($_POST['user_status']))
					$smarty->assign('user_status', stripslashes($_POST['user_status']));

				if ($ok) {
					$step=4;
				} else {
					$smarty->assign('errormsg', "Please provide information about this image, see messages below...");
					$smarty->assign_by_ref('error', $error);
					$step=3;
				}
			}
		}
		elseif (isset($_POST['finalise']))
		{
			//create the image record
			if($uploadmanager->setUploadId($_POST['upload_id']))
			{
				$err = SubmissionHelper::finaliseSubmission($uploadmanager, $_POST, $square);
				SubmissionHelper::setLastSubmissionDetails($_POST, $square);
				$clear_cache = 1;

				if (!$err)
					$smarty->assign('gridimage_id', $uploadmanager->gridimage_id);
			}

			$step=($err)?7:5;
		}
		elseif (isset($_POST['abandon']))
		{
			//delete the upload
			if($uploadmanager->setUploadId($_POST['upload_id']))
			{
				$uploadmanager->cleanUp();
			}

			$step=6;
		}
		elseif (isset($_POST['goback3']))
		{
			$uploadmanager->setUploadId($_POST['upload_id']);

			$smarty->assign('upload_id', $_POST['upload_id']);

			$step = 3;
		}


		if ($step == 1) {
			//init smarty
			$smarty->assign('prefixes', $square->getGridPrefixes());
			$smarty->assign('kmlist', $square->getKMList());

			$USER->getStats();
		} elseif ($step == 3) {
			$smarty->assign('title', stripslashes($_POST['title']));
			$smarty->assign('comment', stripslashes($_POST['comment']));
			$smarty->assign('imagetaken', stripslashes($_POST['imagetaken']));
			if (!empty($_POST['tags'])) {
				if (is_array($_POST['tags'])) {
					$tags = stripslashes(implode('|',$_POST['tags']));
				} else {
					$tags = stripslashes($_POST['tags']);
				}

				$tagarray = array();
				foreach (explode('|',$tags) as $tag) {
					$tagarray[str_replace('top:','',$tag)] = 1;
				}
				$smarty->assign_by_ref('tagarray', $tagarray);
			}
			if (!empty($_POST['imageclass']))
				$smarty->assign('imageclass', stripslashes($_POST['imageclass']));
			$smarty->assign('user_status', stripslashes($_POST['user_status']));

			list($usec, $sec) = explode(' ',microtime());
			$endtime = ((float)$usec + (float)$sec);
			$timetaken = $endtime - $STARTTIME;

			if ($timetaken > 15) {
				//mysql might of closed the connection in the meantime
				unset($square->db);
				//so get a new one...
				$square->_getDB();
			}

			$tags = new Tags;
			$tags->assignPrimarySmarty($smarty);
			$tags->assignSubjectSmarty($smarty);

			if (!empty($square)) {
	                        $sphinx = new sphinxwrapper();
		                foreach ($sphinx->countKeywords($square->grid_reference, 'snippet') as $row) {
			                $smarty->assign('snippets',$row['docs']);
				}
			}

			//find a possible place within 25km
			$smarty->assign('place', $square->findNearestPlace(25000));

			$preview_url="/submit.php?preview=".$uploadmanager->upload_id;
			$smarty->assign('preview_url', $preview_url);
			$smarty->assign('preview_width', $uploadmanager->upload_width);
			$smarty->assign('preview_height', $uploadmanager->upload_height);


			SubmissionHelper::fixOrientation($uploadmanager, $smarty);

			if (max($uploadmanager->upload_width,$uploadmanager->upload_height) < 500)
				$smarty->assign('smallimage', 1);

			$token=new Token;
			$token->setValue("g", !empty($_POST['grid_reference'])?$_POST['grid_reference']:$square->grid_reference);
			$token->setValue("p", $_POST['photographer_gridref']);
			$token->setValue("v", $_POST['view_direction']);
			$smarty->assign('reopenmaptoken', $token->getToken());


			if ($_POST['imagetaken'] && $_POST['imagetaken'] != '0000-00-00') {
				$smarty->assign('imagetaken', stripslashes($_POST['imagetaken']));
			} elseif ($smarty->get_template_vars('imagetaken')) {
				//already set
			} elseif (isset($uploadmanager->exifdate)) {
				$smarty->assign('imagetaken', $uploadmanager->exifdate);
				//$smarty->assign('imagetakenmessage', ' ('.$uploadmanager->exifdate.' stated in exif header)');
			} else {
				$smarty->assign('imagetaken', '--');
			}

			$smarty->assign('today_imagetaken', date("Y-m-d"));
		} elseif ($step == 4) {
			$USER->getStats();

			$preview_url="/submit.php?preview=".$uploadmanager->upload_id;
			$smarty->assign('preview_url', $preview_url);
			$smarty->assign('preview_width', $uploadmanager->upload_width);
			$smarty->assign('preview_height', $uploadmanager->upload_height);

			if ($uploadmanager->initOriginalUploadSize() && $uploadmanager->hasoriginal) {
				$smarty->assign('original_width', $uploadmanager->original_width);
				$smarty->assign('original_height', $uploadmanager->original_height);
			}

		} elseif ($step == 2) {
			require_once('geograph/rastermap.class.php');

			$rastermap = new RasterMap($square,true);
			if (empty($_REQUEST['service']) && !empty($_COOKIE['MapSrv'])) {
				$_REQUEST['service'] = $_COOKIE['MapSrv'];
			}
			if (isset($_REQUEST['service'])) {
				if ($_REQUEST['service'] == 'Leaflet') {
					$rastermap->setService('Leaflet');
				} elseif ($_REQUEST['service'] == 'Google') {
					$rastermap->setService('Google');
				} elseif ($_REQUEST['service'] == 'OS50k') {
					$rastermap->setService('OS50k');
				}
			}
			if (isset($_POST['photographer_gridref'])) {
				$square2=new GridSquare;
				$ok= $square2->setByFullGridRef($_POST['photographer_gridref']);
				$rastermap->addViewpoint($square2->nateastings,$square2->natnorthings,$square2->natgrlen,@$_POST['view_direction']);
			} elseif (isset($_POST['view_direction']) && strlen($_POST['view_direction']) && $_POST['view_direction'] != -1) {
				$rastermap->addViewDirection($_POST['view_direction']);
			}
			$smarty->assign_by_ref('rastermap', $rastermap);

			$smarty->assign_by_ref('square', $square);


			$smarty->assign('reference_index', $square->reference_index);

			require_once('geograph/conversions.class.php');
			$conv = new Conversions;
			list($lat,$long) = $conv->gridsquare_to_wgs84($square);
			$smarty->assign('lat', $lat);
			$smarty->assign('long', $long);

			$rastermap->addLatLong($lat,$long);

			$images=$square->getImages($USER->user_id,'',"order by submitted desc limit 6");
			$square->totalimagecount = count($images);

			$smarty->assign('shownimagecount', $square->totalimagecount);

			if ($square->totalimagecount == 6) {
				$square->totalimagecount = $square->getImageCount($USER->user_id);
			}

			$smarty->assign('totalimagecount', $square->totalimagecount);

			if ($square->totalimagecount > 0) {
				$smarty->assign_by_ref('images', $images);
			}

			$smarty->assign('dirs', SubmissionHelper::getDirections());
		} elseif ($step == 5) {
			$news = SubmissionHelper::getNews();
			if ($news) {
				$smarty->assign_by_ref('news', $news);
			}

		}
		$last_submission = SubmissionHelper::getLastSubmissionDetails();
		foreach ($last_submission as $key => $value) {
			$smarty->assign($key, $value);
		}
	}
	else
	{
		if (!empty($square->errormsg))
			$smarty->assign('errormsg', $square->errormsg);

		//we've rejected the gridsquare, but the inputs may be valid...
		if (!empty($_POST['gridsquare']) && $square->validGridPos($_POST['gridsquare'], $_POST['eastings'], $_POST['northings']))
		{
			$smarty->assign('gridsquare', $_POST['gridsquare']);
			$smarty->assign('eastings', $_POST['eastings']);
			$smarty->assign('northings', $_POST['northings']);
			$smarty->assign('gridref', sprintf("%s%02d%02d", $_POST['gridsquare'],$_POST['eastings'],$_POST['northings']));
		}

		if ($step == 1) {
			//init smarty
			$smarty->assign('prefixes', $square->getGridPrefixes());
			$smarty->assign('kmlist', $square->getKMList());

			$USER->getStats();
		}
	}
}
else
{
	if (!empty($_GET['gridreference']) && empty($_GET['grid_reference'])) {
		$_GET['grid_reference'] = $_GET['gridreference'];
	}
	if (!empty($_GET['grid_reference'])) {
		$ok= $square->setByFullGridRef($_GET['grid_reference']);

		//preserve inputs in smarty
		$smarty->assign('grid_reference', $_GET['grid_reference']);

		if ($ok) {
			$smarty->assign('gridsquare', $square->gridsquare);
			$smarty->assign('eastings', $square->eastings);
			$smarty->assign('northings', $square->northings);
			$smarty->assign('gridref', $square->grid_reference);

			$smarty->assign('grid_reference', SubmissionHelper::normaliseGridReference($_GET['grid_reference']));
		}
	} elseif (!empty($_SESSION['gridsquare'])) {
		//just starting - use remembered values
		$smarty->assign('gridsquare', $_SESSION['gridsquare']);
		$smarty->assign('eastings', $_SESSION['eastings']);
		$smarty->assign('northings', $_SESSION['northings']);
		$smarty->assign('auto',1);
		$smarty->assign('grid_reference', $grid_reference = $_SESSION['gridsquare'].' '.$_SESSION['eastings'].' '.$_SESSION['northings']);
		$smarty->assign('gridref', $grid_reference);

	} elseif ($square->loadMostRecentSubmission($USER->user_id)) {
		//else just lookup the most recent used square!

		$smarty->assign('gridsquare', $square->gridsquare);
		$smarty->assign('eastings', $square->eastings);
		$smarty->assign('northings', $square->northings);
		$smarty->assign('auto',1);
		$smarty->assign('grid_reference', $grid_reference = $square->gridsquare.' '.$square->eastings.' '.$square->northings);
		$smarty->assign('gridref', $grid_reference);
	}

	if ($step == 1) {
		if (isset($USER->submission_method) && !isset($_GET['redir'])) {
			//on mobile, honour the seperate mobile preference
			if (!empty($mobile_browser)) {
				//note, we COULD just redirect blindly and let submit-mobile do the redirect
				//But, may end up redirecting BACK here, so avoid back&fourth
				$choose = $USER->getPreference('submit.mobile','',true);

			        switch($_POST['choose']) {
			                case 'multi': $url = "/submit-multi.php?tab=upload&mobile=1"; break;
			                case 'v1': $smarty->assign("mobile_browser", 0); break; // just stay on this page! (doesn't have a mobile template anyway!)
				        case 'v2': $url = "/submit2.php?display=mobile&redir=false"; break;
					default: $url = "/submit-mobile.php"; break;
			        }

			//otherwise on desktop, check if choosen a method!
			} elseif ($USER->submission_method == 'submit2') {
				$url = "/submit2.php";
			} elseif ($USER->submission_method == 'submit2tabs') {
				$url = "/submit2.php?display=tabs";
			} elseif ($USER->submission_method == 'multi') {
				$url = "/submit-multi.php";
			} elseif ($USER->submission_method == 'mobile') {
				$url = "/submit-mobile.php";
			}
			if (!empty($url)) {
				if (!empty($grid_reference)) {
					$sep = strpos($url,'submit2')?'#':(strpos($url,'?')?'&':'?');
					$url .= "{$sep}gridref=$grid_reference";
				}
				header("Location: $url");
				print "<a href=\"$url\">Continue</a>";
				exit;
			}
		}

		//init smarty
		$smarty->assign('prefixes', $square->getGridPrefixes());
		$smarty->assign('kmlist', $square->getKMList());

		$USER->getStats();
	}
}

if (strlen($uploadmanager->errormsg))
{
	$smarty->assign('errormsg', $uploadmanager->errormsg);
	$step=7;
}


$smarty->assign('tab', $selectedtab);
$_SESSION['tab'] = $selectedtab;

//which step to display?
$smarty->assign('step', $step);

if (isset($USER->submission_new)) {
	$_SESSION['submit_new'] = intval($USER->submission_new);
}
if (!empty($_SESSION['submit_new'])) {
	$smarty->display('submit.tpl');
} else {
	$smarty->display('submit_old.tpl');
}

if (!empty($clear_cache)) {

	flush();

	//clear user profile
	$ab=floor($USER->user_id/10000);
	$smarty->clear_cache(null, "user$ab|{$USER->user_id}");

	if ($memcache->valid) {
		//the submit list
		$mkey = md5("{$square->gridsquare_id}:{$USER->user_id},,order by submitted desc limit 6");
		$memcache->name_delete('gi',$mkey);
		//the browse page for the user (to show pending)
		$mkey = md5("{$square->gridsquare_id}:{$USER->user_id},,order by if(ftf between 1 and 4,ftf,5),gridimage_id");
		$memcache->name_delete('gi',$mkey);
	}

	if (!$err)
		$uploadmanager->cleanUp();
}
