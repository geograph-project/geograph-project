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
	require_once('geograph/gridimagetroubleticket.class.php');
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

$smarty = new GeographPage;

//you must be logged in to request changes
$USER->mustHavePerm("basic");


$template='resubmit.tpl';
$cacheid='';

ini_set("display_errors",1);

$image=new GridImage;	

if (isset($_REQUEST['id']))
{
	$image->loadFromId($_REQUEST['id']);
	$isowner=($image->user_id==$USER->user_id)?1:0;
	$isadmin=$USER->hasPerm('ticketmod')?1:0;

	if ($image->isValid())
	{
		if ($isowner||$isadmin)
		{
			//ok, we'll let it lie...
		}
		else
		{
			header("Location: /photo/{$_REQUEST['id']}");
			exit;
		}
		
		$smarty->assign_by_ref('image',$image);
		
		if (isset($_POST['finalise']))
		{
			if($uploadmanager->setUploadId($_POST['upload_id']))
			{
				$uploadmanager->setLargestSize($_POST['largestsize']);
				
				$uploadmanager->addOriginal($image);
			
				$smarty->assign('step',4);
			
			} else {
				$smarty->assign('step',-1);
			}
			
		} elseif (isset($_POST['abandon'])) {
		
			if($uploadmanager->setUploadId($_POST['upload_id']))
			{
				$uploadmanager->cleanUp();
			}
			
			$smarty->assign('step',-1);
			
		} elseif (isset($_POST['next'])) {
			
			
			if (!filesize($_FILES['jpeg']['tmp_name'])) 
			{
				$smarty->assign('error', 'Sorry, no file was received - please try again');
			} 
			elseif ($uploadmanager->processUpload($_FILES['jpeg']['tmp_name']))
			{
				$smarty->assign('upload_id', $uploadmanager->upload_id);
				$smarty->assign('transfer_id', $uploadmanager->upload_id);
				if ($uploadmanager->hasoriginal) {
					$smarty->assign('original_width', $uploadmanager->original_width);
					$smarty->assign('original_height', $uploadmanager->original_height);
				}

				$smarty->assign('preview_url', "/resubmit.php?preview=".$uploadmanager->upload_id);
				$smarty->assign('preview_width', $uploadmanager->upload_width);
				$smarty->assign('preview_height', $uploadmanager->upload_height);
				
			} else {
				$smarty->assign('error', $uploadmanager->errormsg);
			
			}
			
			
			$smarty->assign('step',2);
			
		} else {
			
			$db=NewADOConnection($GLOBALS['DSN']);
					
			$exif = $db->getOne("SELECT exif FROM gridimage_exif WHERE gridimage_id = ".$image->gridimage_id);
			if (empty($exif)) {
				$exif = $db->getOne("SELECT exif FROM gridimage_exif1 WHERE gridimage_id = ".$image->gridimage_id);
			}
			if (empty($exif)) {
				$exif = $db->getOne("SELECT exif FROM gridimage_exif2 WHERE gridimage_id = ".$image->gridimage_id);
			}

			if (!empty($exif)) {
				$exif = unserialize($exif);
				$exif2 = array();
				
				if (preg_match('/(\w+\.jpg)/i',$exif['EXIF']['MakerNote'],$m)) {
					$exif2['filename'] = $m[1];
				}
				if (!empty($exif['IFD0']['DocumentName'])) {
					$exif2['filename'] = $exif['IFD0']['DocumentName'];
				}
				if (($date = $exif['EXIF']['DateTimeOriginal']) ||
				    ($date = $exif['EXIF']['DateTimeDigitized']) ||
				    ($date = $exif['IFD0']['DateTime']) ) {
				
					$exif2['datetime'] = $date;
				}
				if (!empty($exif['IFD0']['Model'])) {
					$exif2['model'] = $exif['IFD0']['Model'];
				}
				if ((is_integer($size = $exif['IFD0']['UndefinedTag:0x1001'])) || 
				    ($size = $exif['EXIF']['ExifImageWidth']) ||
				    ($size = $exif['COMPUTED']['Width']) ) {

					$exif2['width'] = $size;
				}
				if ((is_integer($size = $exif['IFD0']['UndefinedTag:0x1002'])) ||
				    ($size = $exif['EXIF']['ExifImageLength']) ||
				    ($size = $exif['COMPUTED']['Height']) ) {

					$exif2['height'] = $size;
				}
				if ((is_integer($size = $exif['FILE']['FileSize'])) ) {

					$exif2['filesize'] = $size;
				}
				
				$smarty->assign_by_ref('exif',$exif2);
			}
;			$smarty->assign('step',1);
		}




	}
	else
	{
		$smarty->assign('error', 'Invalid image id specified');
	}

}
else
{
	$smarty->assign('error', 'No image id specified');
}

$smarty->display($template, $cacheid);


?>
