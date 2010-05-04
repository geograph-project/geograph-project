<?php
/**
 * $Project: GeoGraph $
 * $Id: submit2.php 6378 2010-02-20 19:02:56Z barry $
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
init_session();

$smarty = new GeographPage;

//you must be logged in to submit images
$USER->mustHavePerm("basic");

$template='submit2.tpl';
$cacheid='';

if (!empty($_REQUEST['use_autocomplete'])) {
	$USER->use_autocomplete = 1;
}

$clear_cache = array();

if (isset($_FILES['jpeg_exif']))
{
	$uploadmanager=new UploadManager;

	switch($_FILES['jpeg_exif']['error'])
	{
		case 0:
			if (!filesize($_FILES['jpeg_exif']['tmp_name'])) 
			{
				$smarty->assign('error', 'Sorry, no file was received - please try again');
			} 
			elseif ($uploadmanager->processUpload($_FILES['jpeg_exif']['tmp_name']))
			{
				$smarty->assign('upload_id', $uploadmanager->upload_id);
				$smarty->assign('transfer_id', $uploadmanager->upload_id);
				if ($uploadmanager->hasoriginal) {
					$smarty->assign('original_width', $uploadmanager->original_width);
					$smarty->assign('original_height', $uploadmanager->original_height);
				}
				
				$smarty->assign('preview_url', "/submit.php?preview=".$uploadmanager->upload_id);
				$smarty->assign('preview_width', $uploadmanager->upload_width);
				$smarty->assign('preview_height', $uploadmanager->upload_height);
				
				$exif = $uploadmanager->rawExifData;
				
				if (!empty($exif['GPS'])) {
					$conv = new Conversions;
					
					if (is_array($exif['GPS']['GPSLatitude'])) {
						$deg = FractionToDecimal($exif['GPS']['GPSLatitude'][0]);
						$min = FractionToDecimal($exif['GPS']['GPSLatitude'][1]);
						$sec = FractionToDecimal($exif['GPS']['GPSLatitude'][2]);
						$lat = ExifConvertDegMinSecToDD($deg, $min, $sec);
					} else {
						//not sure if this will ever happen but it could?
						$lat = $exif['GPS']['GPSLatitude'];
					}

					if ($exif['GPS']['GPSLatitudeRef'] == 'S') 
						$lat *= -1;

					if (is_array($exif['GPS']['GPSLongitude'])) {
						$deg = FractionToDecimal($exif['GPS']['GPSLongitude'][0]);
						$min = FractionToDecimal($exif['GPS']['GPSLongitude'][1]);
						$sec = FractionToDecimal($exif['GPS']['GPSLongitude'][2]);
						$long = ExifConvertDegMinSecToDD($deg, $min, $sec);
					} else {
						//not sure if this will ever happen but it could?
						$long = $exif['GPS']['GPSLongitude'];
					}

					if ($exif['GPS']['GPSLongitudeRef'] == 'W') 
						$long *= -1;


					list($e,$n,$reference_index) = $conv->wgs84_to_national($lat,$long);

					list ($grid_reference,$len) = $conv->national_to_gridref(intval($e),intval($n),0,$reference_index);
					
					$smarty->assign('photographer_gridref',$grid_reference); 
					
					list ($grid_reference,$len) = $conv->national_to_gridref(intval($e),intval($n),4,$reference_index);
					
					$smarty->assign('grid_reference', $grid_reference);
				} 
				
				if (preg_match("/(_|\b)([B-DF-JL-OQ-TV-X]|[HNST][A-Z]|MC|OV|[A-Za-z]{3})[ \._-]?(\d{2,5})[ \._-]?(\d{2,5})(\b|[A-Za-z_])/i",$_FILES['jpeg_exif']['name'],$m)) {
					if (strlen($m[3]) != strlen($m[4])) {
						if (preg_match("/(_|\b)([B-DF-JL-OQ-TV-X]|[HNST][A-Z]|MC|OV|[A-Za-z]{3})[ \._-]?(\d{4,10})(\b|[A-Za-z_])/i",$_FILES['jpeg_exif']['name'],$m)) {
							if (strlen($m[3])%2==0) {
								$smarty->assign('grid_reference', $grid_reference = $m[2].$m[3]); 
							}
						}
					} else {
						$smarty->assign('grid_reference', $grid_reference = $m[2].$m[3].$m[4]); 
					}
		
				} elseif (!empty($exif['COMMENT']) && preg_match("/\b([B-DF-JL-OQ-TV-X]|[HNST][A-Z]|MC|OV|[A-Za-z]{3})[ \._-]?(\d{2,5})[ \._-]?(\d{2,5})(\b|[A-Za-z_])/i",implode(' ',$exif['COMMENT']),$m)) {
					if (strlen($m[2]) == strlen($m[3]) || (strlen($m[2])+strlen($m[3]))%2==0) {
						$smarty->assign('grid_reference', $grid_reference = $m[1].$m[2].$m[3]); 
					}
				}
				
				if (isset($uploadmanager->exifdate)) {
					$smarty->assign('imagetaken', $uploadmanager->exifdate);
				}
				
				$smarty->assign('filename',basename(str_replace("\\",'/',$_FILES['jpeg_exif']['name'])));

				
				$smarty->assign('success', 1);
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
		case UPLOAD_ERR_NO_FILE:
			$smarty->assign('error', 'No file was uploaded - please try again');
			break;
		case UPLOAD_ERR_NO_TMP_DIR:
			$smarty->assign('error', 'System Error: Folder missing - please let us know');
			break;
		case UPLOAD_ERR_CANT_WRITE:
			$smarty->assign('error', 'System Error: Can not write file - please let us know');
			break;
		case UPLOAD_ERR_EXTENSION:
			$smarty->assign('error', 'System Error: Upload Blocked - please let us know');
			break;
		default:
			$smarty->assign('error', 'We were unable to process your upload - please try again');
			break;
	}
	
	
	
} elseif (isset($_POST['finalise'])) {  
	$status = array();
	$filenames = array();
	
	foreach ($_POST['upload_id'] as $key => $upload_id) {
		$uploadmanager = new UploadManager();
		$square = new GridSquare();

		$filenames[$key] = $_POST['title'][$key];

		$ok = $square->setByFullGridRef($grid_reference = $_POST['grid_reference'][$key]);
		if ($ok) {
			// set up attributes from uploaded data
			$uploadmanager->setSquare($square);
			$uploadmanager->setViewpoint($_POST['photographer_gridref'][$key]);
			$uploadmanager->setDirection($_POST['view_direction'][$key]);
			$uploadmanager->setUse6fig(stripslashes($_POST['use6fig'][$key]));
			$uploadmanager->setTaken($_POST['imagetaken'][$key]);
			$uploadmanager->setTitle($_POST['title'][$key]);
			$uploadmanager->setTitle2($_POST['title2'][$key]);
			$uploadmanager->setLargestSize($_POST['largestsize'][$key]);
			$uploadmanager->setClearExif($_POST['clearexif']);
			//$uploadmanager->setClearExif($_POST['clearexif'][$key]);
			if ($_POST['comment'][$key] != "comment[$key]") {
				//bug? in Picasa sends the name in the value if blank, useful! (but only seems to apply to textareas)
				$uploadmanager->setComment($_POST['comment'][$key]);
			}
			if ($_POST['comment2'][$key] != "comment2[$key]") {
				//bug? in Picasa sends the name in the value if blank, useful! (but only seems to apply to textareas)
				$uploadmanager->setComment2($_POST['comment2'][$key]);
			}
			
			if (($_POST['imageclass'][$key] == 'Other' || empty($_POST['imageclass'][$key])) && !empty($_POST['imageclassother'][$key])) {
				$imageclass = stripslashes($_POST['imageclassother'][$key]);
			} else if ($_POST['imageclass'] != 'Other') {
				$imageclass =  stripslashes($_POST['imageclass'][$key]);
			}			
			$uploadmanager->setClass($imageclass);

			if ($_POST['pattrib'] == 'other') {
				$uploadmanager->setCredit(stripslashes($_POST['pattrib_name']));
				$smarty->assign('credit_realname',$_POST['pattrib_name']);
			} elseif ($_POST['pattrib'] == 'self') {
				$uploadmanager->setCredit('');
			}

			$ok = $uploadmanager->setUploadId($_POST['upload_id'][$key]);

			if ($ok) {
				$err = $uploadmanager->commit(isset($_GET['nofrills'])?'nofrills':'submit2');

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
		if ($_POST['imagetaken'][$key] != '0000-00-00') {
			$_SESSION['last_imagetaken'] = $_POST['imagetaken'][$key];
		}
		if (!empty($_POST['grid_reference']) && $square->natgrlen > 4) {
			$_SESSION['last_grid_reference'] = $_POST['grid_reference'];
		}
		if (!empty($_POST['photographer_gridref'])) {
			$_SESSION['last_photographer_gridref'] = $_POST['photographer_gridref'];
		}
						
				
		
		$clear_cache[$square->gridsquare_id] = 1;
	}

	$template='puploader_success.tpl';
	if (isset($_GET['nofrills']))
		$smarty->assign('nofrills', 1);
	$smarty->assign('submit2', 1);
	$smarty->assign('status', $status);
	$smarty->assign('filenames', $filenames);
	$smarty->assign('grid_reference', $grid_reference);

} 

if (isset($_REQUEST['inner'])) {
	$template='submit2_inner.tpl';
	$step = 1;
	
	if (!empty($_REQUEST['grid_reference'])) 
	{
		$step = 2; 
		$square=new GridSquare;
		
		$ok= $square->setByFullGridRef($_REQUEST['grid_reference']);

		if ($ok) {
			$smarty->assign('grid_reference', $grid_reference = $_REQUEST['grid_reference']);
			
			$smarty->assign('success', 1);
		} else {
			$smarty->assign('errormsg', $square->errormsg);	
		}
	} 
	
	$smarty->assign('step', $step);
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
	customExpiresHeader(3600,false,true);
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
			$mkey = md5("{$gridsquare_id}:{$USER->user_id},,order by ftf desc,gridimage_id");
			$memcache->name_delete('gi',$mkey);
		}
	}
	
	//clear user profile
	$ab=floor($USER->user_id/10000);
	$smarty->clear_cache(null, "user$ab|{$USER->user_id}");

	
}


?>
