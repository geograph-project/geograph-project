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

if (isset($_GET['preview'])) {
	session_cache_limiter('none');
} else {
	require_once('geograph/gridimage.class.php');
	require_once('geograph/gridsquare.class.php');
}

require_once('geograph/uploadmanager.class.php');

init_session();

$uploadmanager=new UploadManager;

//display preview image?
if (isset($_GET['preview']))
{
	$uploadmanager->outputPreviewImage($_GET['preview']);
	exit;
}

list($usec, $sec) = explode(' ',microtime());
$GLOBALS['STARTTIME'] = ((float)$usec + (float)$sec);

$square=new GridSquare;
$smarty = new GeographPage;

if (!$USER->hasPerm("basic")) {
	$smarty->display('static_submit_intro.tpl');
	exit;
}

if (!empty($_REQUEST['use_autocomplete'])) {
	$USER->use_autocomplete = 1;
}

if (isset($_SESSION['tab'])) {
	$selectedtab=$_SESSION['tab'];
} else {
	$selectedtab =1;
}

$step=isset($_POST['step'])?intval($_POST['step']):1;

if (!empty($_FILES['jpeg_exif']) && $_FILES['jpeg_exif']['error'] != UPLOAD_ERR_NO_FILE)
{
	//Submit Step 1a..

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

					list ($_POST['grid_reference'],$len) = $conv->national_to_gridref(intval($e),intval($n),0,$reference_index);
					
					$_POST['gridsquare'] = preg_replace('/^([A-Z]+).*$/','',$_POST['grid_reference']);
					
				
				} elseif (preg_match("/(_|\b)([a-zA-Z]{1,2})[ \._-]?(\d{2,5})[ \._-]?(\d{2,5})(\b|[A-Za-z_])/",$_FILES['jpeg_exif']['name'],$m)) {
					if (strlen($m[3]) != strlen($m[4])) {
						if (preg_match("/(_|\b)([a-zA-Z]{1,2})[ \._-]?(\d{4,10})(\b|[A-Za-z_])/",$_FILES['jpeg_exif']['name'],$m)) {
							$_POST['gridsquare'] = $m[2];
	                                                $_POST['grid_reference'] = $m[2].$m[3];
						}
					} else {
						$_POST['gridsquare'] = $m[2];
						$_POST['grid_reference'] = $m[2].$m[3].$m[4];
					}
		
				} elseif (!empty($exif['COMMENT']) && preg_match("/\b([a-zA-Z]{1,2})[ \._-]?(\d{2,5})[ \._-]?(\d{2,5})(\b|[A-Za-z_])/",implode(' ',$exif['COMMENT']),$m)) {
					$_POST['gridsquare'] = $m[1];
					$_POST['grid_reference'] = $m[1].$m[2].$m[3];
				}
				
				$_POST['eastings'] = '';
				$selectedtab =3;
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

		if (isset($_POST['picnik'])) {
			if ($_POST['picnik'] == 'return') {
				unset($_POST['picnik']);
				$smarty->assign('_post',$_POST);
				$smarty->display('submit_picnik.tpl');			
				exit;
			}
		
			$q = array();
			$q['_apikey'] = $CONF['picnik_api_key'];
			$q['_page'] = '/in/upload';
			$q['_export'] = "http://{$_SERVER['HTTP_HOST']}/submit.php";
			$q['_export_field'] = 'jpeg_url';
			$q['_export_agent'] = 'browser';
			$q['_export_method'] = 'POST';
			$q['_userid'] = md5($USER->user_id.$CONF['register_confirmation_secret']);
			$q['_export_title'] = 'Send to Geograph';
			$q['_host_name'] = 'Geograph';
			$q['setpos'] = 1;
			$q['grid_reference'] = $grid_reference;
			$q['gridsquare'] = $square->gridsquare;
			if (isset($_POST['photographer_gridref'])) {
				$q['photographer_gridref'] = $_POST['photographer_gridref'];
			}
			if (isset($_POST['view_direction']) && strlen($_POST['view_direction'])) {
				$q['view_direction'] = $_POST['view_direction'];
			} 
			if ($CONF['picnik_method'] == 'inabox' && !preg_match('/safari|msie 6/i',$_SERVER['HTTP_USER_AGENT'])) { 
				$q['picnik'] = 'return';
				$smarty->assign('picnik_url','http://www.picnik.com/service?'.http_build_query($q));
				$smarty->display('submit_picnik.tpl');
			} else {
				header('Location: http://www.picnik.com/service?'.http_build_query($q));
			}
			exit;
		}

		//preserve inputs in smarty
		$smarty->assign('gridsquare', $square->gridsquare);
		$smarty->assign('eastings', $square->eastings);
		$smarty->assign('northings', $square->northings);
		$smarty->assign('gridref', $square->grid_reference);
	
		//store other useful info about the square
		$smarty->assign('imagecount', $square->imagecount);
		
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
				$smarty->assign('imagetaken', stripslashes($_POST['imagetaken']));
				$smarty->assign('imageclass', stripslashes($_POST['imageclass']));
				$smarty->assign('user_status', stripslashes($_POST['user_status']));
			}
			$step=2;
		}
		elseif (isset($_POST['goback']))
		{
			$step=1;
		}			
		elseif (isset($_POST['transfer_id']))
		{
			//preserve the upload id
			if($uploadmanager->validUploadId($_POST['transfer_id'])) {
				$smarty->assign('upload_id', $_POST['transfer_id']);
				$uploadmanager->setUploadId($_POST['transfer_id']);
				$uploadmanager->reReadExifFile();
				
				//we ok to continue
				if (isset($_POST['photographer_gridref'])) {
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
					
					$smarty->assign('filename',basename(str_replace("\\",'/',$_FILES['jpeg']['name'])));

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
				$smarty->assign('comment', trim(stripslashes($_POST['comment'])));

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
				$uploadmanager->setTitle(stripslashes(trim($_POST['title'])));
				$uploadmanager->setComment(stripslashes(trim($_POST['comment'])));
				$uploadmanager->setTaken(stripslashes($_POST['imagetaken']));
				$uploadmanager->setClass(stripslashes(trim($_POST['imageclass'])));
				$uploadmanager->setViewpoint(stripslashes($_POST['photographer_gridref']));
				$uploadmanager->setDirection(stripslashes($_POST['view_direction']));
				$uploadmanager->setUse6fig(stripslashes($_POST['use6fig']));
				$uploadmanager->setUserStatus(stripslashes($_POST['user_status']));
				$uploadmanager->setLargestSize($_POST['largestsize']);
			
				if ($_POST['pattrib'] == 'other') {
					$uploadmanager->setCredit(stripslashes($_POST['pattrib_name']));
					$smarty->assign('credit_realname',$_POST['pattrib_name']);
				} elseif ($_POST['pattrib'] == 'self') {
					$uploadmanager->setCredit('');
				}
				if (!empty($_POST['pattrib_default'])) {
					$USER->setCreditDefault(($_POST['pattrib'] == 'other')?stripslashes($_POST['pattrib_name']):'');
				}
				
				
				$err = $uploadmanager->commit();
				
				if ($_POST['imagetaken'] != '0000-00-00') {
					$_SESSION['last_imagetaken'] = $_POST['imagetaken'];
				}
				
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
			
			//preserve stuff
			$smarty->assign('upload_id', $_POST['upload_id']);
			$smarty->assign('title', stripslashes($_POST['title']));
			$smarty->assign('comment', stripslashes($_POST['comment']));
			$smarty->assign('imagetaken', stripslashes($_POST['imagetaken']));
			$smarty->assign('imageclass', stripslashes($_POST['imageclass']));
			$smarty->assign('user_status', stripslashes($_POST['user_status']));
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
		
			//find a possible place within 25km
			$smarty->assign('place', $square->findNearestPlace(25000));

			$smarty->assign('use_autocomplete', $USER->use_autocomplete);

			$preview_url="/submit.php?preview=".$uploadmanager->upload_id;
			$smarty->assign('preview_url', $preview_url);
			$smarty->assign('preview_width', $uploadmanager->upload_width);
			$smarty->assign('preview_height', $uploadmanager->upload_height);
			
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
			
			if (isset($_POST['photographer_gridref'])) {
				$square2=new GridSquare;
				$ok= $square2->setByFullGridRef($_POST['photographer_gridref']);
				$rastermap->addViewpoint($square2->nateastings,$square2->natnorthings,$square2->natgrlen,$_POST['view_direction']);
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
			
			$dirs = array (-1 => '');
			$jump = 360/16; $jump2 = 360/32;
			for($q = 0; $q< 360; $q+=$jump) {
				$s = ($q%90==0)?strtoupper(heading_string($q)):ucwords(heading_string($q));
				$dirs[$q] = sprintf('%s : %03d deg (%03d > %03d)',
					str_pad($s,16,' '),
					$q,
					($q == 0?$q+360-$jump2:$q-$jump2),
					$q+$jump2);
			}
			$dirs['00'] = $dirs[0];
			$smarty->assign_by_ref('dirs', $dirs);
		}
		if (isset($_SESSION['last_imagetaken'])) {
			$smarty->assign('last_imagetaken', $_SESSION['last_imagetaken']);
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
			$smarty->assign('grid_reference', $square->grid_reference);
		}
	} elseif (!empty($_SESSION['gridsquare'])) {
		//just starting - use remembered values
		$smarty->assign('gridsquare', $_SESSION['gridsquare']);
		$smarty->assign('eastings', $_SESSION['eastings']);
		$smarty->assign('northings', $_SESSION['northings']);
		$smarty->assign('auto',1);
		$smarty->assign('gridref', $_SESSION['gridsquare'].' '.$_SESSION['eastings'].' '.$_SESSION['northings']);
		$smarty->assign('grid_reference', $_SESSION['gridsquare'].' '.$_SESSION['eastings'].' '.$_SESSION['northings']);
	}
	
	if ($step == 1) {
		//init smarty
		$smarty->assign('prefixes', $square->getGridPrefixes());
		$smarty->assign('kmlist', $square->getKMList());
	
		$USER->getStats();
	}
}

$smarty->assign('picnik_api_key', $CONF['picnik_api_key']);

if (strlen($uploadmanager->errormsg))
{
	$smarty->assign('errormsg', $uploadmanager->errormsg);
	$step=7;
}

$smarty->assign('tab', $selectedtab);
$_SESSION['tab'] = $selectedtab;

//which step to display?
$smarty->assign('step', $step);

$smarty->display('submit.tpl');

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
		$mkey = md5("{$square->gridsquare_id}:{$USER->user_id},,order by ftf desc,gridimage_id");
		$memcache->name_delete('gi',$mkey);
	}

}
	
?>
