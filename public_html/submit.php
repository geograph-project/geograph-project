<?php
/**
 * $Project: GeoGraph $
 * $Id$
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
require_once('geograph/gridimage.class.php');
require_once('geograph/gridsquare.class.php');
require_once('geograph/uploadmanager.class.php');

init_session();
$USER->mustHavePerm("basic");


$uploadmanager=new UploadManager;
$square=new GridSquare;
$smarty = new GeographPage;


//display preview image?
if (isset($_GET['preview']))
{
	$uploadmanager->outputPreviewImage($_GET['preview']);
	exit;
}



$step=isset($_POST['step'])?intval($_POST['step']):1;

if ($step == 1) {
	//init smarty
	$smarty->assign('prefixes', $square->getGridPrefixes());
	$smarty->assign('kmlist', $square->getKMList());
}
//for every stage after step 1, we expect to get a
//grid reference posted...
if (isset($_POST['gridsquare']))
{
	if (isset($_POST['viewpoint_gridreference']))
		$smarty->assign('viewpoint_gridreference', $_POST['viewpoint_gridreference']);
	if (isset($_POST['view_direction']) && strlen($_POST['view_direction'])) {
		$smarty->assign('view_direction', $_POST['view_direction']);
	} else {
		$smarty->assign('view_direction', -1);
	}
		

	//ensure the submitted reference is valid
	if (!empty($_POST['gridreference'])) 
	{
		$ok= $square->setByFullGridRef($_POST['gridreference']);
		
		//preserve inputs in smarty
		$smarty->assign('gridreference', $_POST['gridreference']);	
	} 
	else 
	{
		$ok= $square->setGridPos($_POST['gridsquare'], $_POST['eastings'], $_POST['northings']);
		if ($ok)
		{
			//preserve inputs in smarty
			$smarty->assign('gridreference', $square->grid_reference);	
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
		
		//we're just setting up the position, move to step 2
		if (isset($_POST['setpos']))
		{
			//Submit Step 1...

			$step=2;
		}
		//see if we have an upload to process?
		elseif (isset($_FILES['jpeg']))
		{
			//Submit Step 2..
			
			if (isset($_POST['goback']))
			{
				$step=1;
			}
			else
			{
				//assume step 2
				$step=2;

				switch($_FILES['jpeg']['error'])
				{
					case 0:
						if (!filesize($_FILES['jpeg']['tmp_name'])) 
						{
							$smarty->assign('error', 'Sorry, no file was received - please try again');
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
						break;
					case UPLOAD_ERR_INI_SIZE:
					case UPLOAD_ERR_FORM_SIZE:
						$smarty->assign('error', 'Sorry, that file exceeds our maximum upload size of 8Mb - please resize the image and try again');
						break;
					case UPLOAD_ERR_PARTIAL:
						$smarty->assign('error', 'Your file was only partially uploaded - please try again');
						break;
					default:
						$smarty->assign('error', 'We were unable to process your upload - please try again');
						break;
				}
			}
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
				
				if (($_POST['imageclass'] == 'Other' || empty($_POST['imageclass'])) && !empty($_POST['imageclassother'])) {
					$imageclass = stripslashes($_POST['imageclassother']);
				} else if ($_POST['imageclass'] != 'Other') {
					$imageclass =  stripslashes($_POST['imageclass']);
				}
				if (strlen($imageclass)==0) {
					$ok=false;
					$error['imageclass']="Please choose a geographical feature";	
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
				$smarty->assign('comment', stripslashes($_POST['comment']));

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
				$uploadmanager->setTitle(stripslashes($_POST['title']));
				$uploadmanager->setComment(stripslashes($_POST['comment']));
				$uploadmanager->setTaken(stripslashes($_POST['imagetaken']));
				$uploadmanager->setClass(stripslashes($_POST['imageclass']));
				$uploadmanager->setViewpoint(stripslashes($_POST['viewpoint_gridreference']));
				$uploadmanager->setDirection(stripslashes($_POST['view_direction']));
				$uploadmanager->setUserStatus(stripslashes($_POST['user_status']));
				
				$err = $uploadmanager->commit();
				
				if ($_POST['imagetaken'] != '0000-00-00') {
					$_SESSION['last_imagetaken'] = $_POST['imagetaken'];
				}
				
				//clear user profile
				$smarty->clear_cache(null, "user{$USER->user_id}");
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
			
			//preserve stuff
			$smarty->assign('upload_id', $_POST['upload_id']);
			$smarty->assign('title', stripslashes($_POST['title']));
			$smarty->assign('comment', stripslashes($_POST['comment']));
			$smarty->assign('imagetaken', stripslashes($_POST['imagetaken']));
			$smarty->assign('imageclass', stripslashes($_POST['imageclass']));
			$smarty->assign('user_status', stripslashes($_POST['user_status']));
			$step = 3;
		}
		
		if ($step == 3) {
			//find a possible place within 25km
			$smarty->assign('place', $square->findNearestPlace(25000));
			
			
			$preview_url="/submit.php?preview=".$uploadmanager->upload_id;
			$smarty->assign('preview_url', $preview_url);
			$smarty->assign('preview_width', $uploadmanager->upload_width);
			$smarty->assign('preview_height', $uploadmanager->upload_height);
			
		
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

			if (isset($_SESSION['last_imagetaken'])) {
				$smarty->assign('last_imagetaken', $_SESSION['last_imagetaken']);
			}
			$smarty->assign('today_imagetaken', date("Y-m-d"));
		}
		if ($step == 2) {
			require_once('geograph/rastermap.class.php');

			$rastermap = new RasterMap($square);
			$smarty->assign_by_ref('rastermap', $rastermap);

			$smarty->assign_by_ref('square', $square);


			$smarty->assign('reference_index', $square->reference_index);

			require_once('geograph/conversions.class.php');
			$conv = new Conversions;
			list($lat,$long) = $conv->gridsquare_to_wgs84($square);
			$smarty->assign('lat', $lat);
			$smarty->assign('long', $long);

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
			
			require_once('geograph/searchengine.class.php');
			$search = new SearchEngine('');
			$dirs = array (-1 => '');
			$jump = 360/16; $jump2 = 360/32;
			for($q = 0; $q< 360; $q+=$jump) {
				$s = ($q%90==0)?strtoupper($search->heading_string($q)):ucwords($search->heading_string($q));
				$dirs[$q] = sprintf('%s : %03d deg (%03d > %03d)',
					str_pad($s,16,' '),
					$q,
					($q == 0?$q+360-$jump2:$q-$jump2),
					$q+$jump2);
			}
			$dirs['00'] = $dirs[0];
			$smarty->assign_by_ref('dirs', $dirs);
			
		}
	}
	else
	{
		$smarty->assign('errormsg', $square->errormsg);
		
		//we've rejected the gridsquare, but the inputs may be valid...
		if ($square->validGridPos($_POST['gridsquare'], $_POST['eastings'], $_POST['northings']))
		{
			$smarty->assign('gridsquare', $_POST['gridsquare']);
			$smarty->assign('eastings', $_POST['eastings']);
			$smarty->assign('northings', $_POST['northings']);
			$smarty->assign('gridref', sprintf("%s%02d%02d", $_POST['gridsquare'],$_POST['eastings'],$_POST['northings']));
		}
		
	
	}
}
else
{
	//just starting - use remembered values
	$smarty->assign('gridsquare', $_SESSION['gridsquare']);
	$smarty->assign('eastings', $_SESSION['eastings']);
	$smarty->assign('northings', $_SESSION['northings']);
	if ($_GET['gridreference']) {
		$ok= $square->setByFullGridRef($_GET['gridreference']);
		
		//preserve inputs in smarty
		$smarty->assign('gridreference', $_GET['gridreference']);	
	
		if ($ok) {
			$smarty->assign('gridsquare', $square->gridsquare);
			$smarty->assign('eastings', $square->eastings);
			$smarty->assign('northings', $square->northings);
			$smarty->assign('gridref', $square->grid_reference);
		}
	}
}


if (strlen($uploadmanager->errormsg))
{
	$smarty->assign('errormsg', $uploadmanager->errormsg);
	$step=7;
}

//which step to display?
$smarty->assign('step', $step);

$smarty->display('submit.tpl');

	
?>
