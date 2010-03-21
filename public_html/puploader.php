<?php
/**
 * $Project: GeoGraph $
 * $Id$
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

require_once("3rdparty/xmlHandler.class.php");

$smarty = new GeographPage;

//you must be logged in to request changes
$USER->mustHavePerm("basic");

$template='puploader.tpl';
$cacheid='';

if (!empty($_REQUEST['use_autocomplete'])) {
	$USER->use_autocomplete = 1;
}

if (isset($_REQUEST['submit2'])) {
	$cacheid .= 'submit2';
	$smarty->assign('submit2',1);
	
	if (isset($_REQUEST['upload_id'])) {
		$smarty->assign('upload_id',$_REQUEST['upload_id']);
	}
}

if (isset($_GET['success'])) {  
	$token=new Token;
	if ($token->parse($_GET['t'])) {
		$template='puploader_success.tpl';
		$smarty->assign('status', unserialize($token->getValueBinary("s")));
		$smarty->assign('filenames', unserialize($token->getValueBinary("f")));
	}
	
} elseif (isset($_POST['selected'])) {  //we dont get the button :(
	$status = array();
	$filenames = array();
	
	foreach ($_POST['field'] as $key => $value) {
		$uploadmanager = new UploadManager();
		$square = new GridSquare();

		$files_key = str_replace('.','_',$value);
		
		$filenames[$key] = $_FILES[$files_key]['name'];

		$ok = $square->setByFullGridRef($_POST['grid_reference'][$key]);
		if ($ok) {
			// set up attributes from uploaded data
			$uploadmanager->setSquare($square);
			$uploadmanager->setViewpoint($_POST['photographer_gridref'][$key]);
			$uploadmanager->setDirection($_POST['view_direction'][$key]);
			$uploadmanager->setUse6fig(stripslashes($_POST['use6fig'][$key]));
			$uploadmanager->setTaken($_POST['imagetaken'][$key]);
			$uploadmanager->setTitle(utf8_decode($_POST['title'][$key]));
			if ($_POST['comment'][$key] != "comment[$key]") {
				//bug? in Picasa sends the name in the value if blank, useful! (but only seems to apply to textareas)
				$uploadmanager->setComment(utf8_decode($_POST['comment'][$key]));
			}
			
			if (($_POST['imageclass'][$key] == 'Other' || empty($_POST['imageclass'][$key])) && !empty($_POST['imageclassother'][$key])) {
				$imageclass = stripslashes($_POST['imageclassother'][$key]);
			} else if ($_POST['imageclass'] != 'Other') {
				$imageclass =  stripslashes($_POST['imageclass'][$key]);
			}			
			$uploadmanager->setClass(utf8_decode($imageclass));

			if ($_POST['pattrib'] == 'other') {
				$uploadmanager->setCredit(stripslashes(utf8_decode($_POST['pattrib_name'])));
				$smarty->assign('credit_realname',utf8_decode($_POST['pattrib_name']));
			} elseif ($_POST['pattrib'] == 'self') {
				$uploadmanager->setCredit('');
			}

			
			$ok = $uploadmanager->processUpload($_FILES[$files_key]['tmp_name']);

			if ($ok) {
				$err = $uploadmanager->commit('puploader');

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
				
		if ($memcache->valid) {
			//the submit list
			$mkey = md5("{$square->gridsquare_id}:{$USER->user_id},,order by submitted desc limit 6");
			$memcache->name_delete('gi',$mkey);
			//the browse page for the user (to show pending)
			$mkey = md5("{$square->gridsquare_id}:{$USER->user_id},,order by between 1 and 4 desc,ftf,gridimage_id");
			$memcache->name_delete('gi',$mkey);
		}	
	}
	if (!empty($_POST['pattrib_default'])) {
		$USER->setCreditDefault(($_POST['pattrib'] == 'other')?stripslashes($_POST['pattrib_name']):'');
	}
	//clear user profile
	$ab=floor($USER->user_id/10000);
	$smarty->clear_cache(null, "user$ab|{$USER->user_id}");

	$token=new Token;
		
	$token->setValueBinary("s", serialize($status));
	$token->setValueBinary("f", serialize($filenames));
				
	$t = $token->getToken($expiry);
	
	print "http://{$_SERVER['HTTP_HOST']}/puploader.php?success&t=$t";
	exit;
} elseif (isset($_REQUEST['inner'])) {
	#print_r($_REQUEST);
	$template='puploader_inner.tpl';
	$step = 1;
	
	$square=new GridSquare;
	
	if (!empty($_REQUEST['photographer_gridref']) && empty($_REQUEST['grid_reference'])) 
	{
		$_REQUEST['grid_reference'] = $_REQUEST['photographer_gridref'];
	}
	
	if (!empty($_REQUEST['grid_reference'])) 
	{
		$ok= $square->setByFullGridRef($_REQUEST['grid_reference']);

		if ($ok) {
			//preserve inputs in smarty
			$smarty->assign('grid_reference', $grid_reference = $_REQUEST['grid_reference']);
			$step = 2; 

			if (!empty($_REQUEST['photographer_gridref'])) 
			{
				//preserve inputs in smarty
				$smarty->assign('photographer_gridref', $photographer_gridref = $_REQUEST['photographer_gridref']);
				$step = 3; 
			} 
		} else {
			$smarty->assign('errormsg', $square->errormsg);	
		}
	} 
	if (!empty($_REQUEST['step'])) {
		$step = intval($_REQUEST['step']);
	}
	if (empty($_REQUEST['grid_reference']) && $step == 2) 
		$step = 1;
		
	if (isset($_REQUEST['service']) && $_REQUEST['service'] == 'Google') {
		$smarty->assign('service', 'Google');
	}
	
	if ($step == 2) {
		require_once('geograph/rastermap.class.php');

		$rastermap = new RasterMap($square,true);
		if (isset($_REQUEST['service']) && $_REQUEST['service'] == 'Google') {
			$rastermap->service = 'Google';
		}

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
	} elseif ($step == 3) {

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
		
		if (!empty($_REQUEST['grid_reference'])) {
			$token=new Token;
			$token->setValue("g", !empty($_REQUEST['grid_reference'])?$_REQUEST['grid_reference']:$square->grid_reference);
			$token->setValue("p", $_REQUEST['photographer_gridref']);
			$token->setValue("v", $_REQUEST['view_direction']);
			$smarty->assign('reopenmaptoken', $token->getToken());
			
			$smarty->assign_by_ref('square', $square);
		}
		
		if ($_REQUEST['imagetaken'] && $_REQUEST['imagetaken'] != '0000-00-00') {
			$smarty->assign('imagetaken', stripslashes($_REQUEST['imagetaken']));
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
	
	
	//which step to display?
	$smarty->assign('step', $step);
	
	if ($_SERVER['REQUEST_METHOD'] == 'GET') {
		customExpiresHeader(3600,false,true);
	}

	
} elseif(!empty($_POST['rss'])) {
	$xh = new xmlHandler();
	$nodeNames = array("PHOTO:THUMBNAIL", "PHOTO:IMGSRC", "TITLE");
	$xh->setElementNames($nodeNames);
	$xh->setStartTag("ITEM");
	$xh->setVarsDefault();
	$xh->setXmlParser();
	$xh->setXmlData(stripslashes($_POST['rss']));
	$pData = $xh->xmlParse();
	
	$smarty->assign_by_ref('pData', array_slice($pData,0,10));
	
} else {
	$template = "puploader_login.tpl";
}

$smarty->display($template, $cacheid);

?>
