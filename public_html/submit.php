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


//init smarty
$smarty->assign('prefixes', $square->getGridPrefixes());
$smarty->assign('kmlist', $square->getKMList());

//for every stage after step 1, we expect to get a
//grid reference posted...
if (isset($_POST['gridsquare']))
{
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
			$step=2;
		}
		//see if we have an upload to process?
		elseif (isset($_FILES['jpeg']))
		{
			//assume step 2
			$step=2;
			
			switch($_FILES['jpeg']['error'])
			{
				case 0:
					if ($uploadmanager->processUpload($_FILES['jpeg']['tmp_name']))
					{
						//we got a suitable image, we need to show it to the user
						$preview_url="/submit.php?preview=".$uploadmanager->upload_id;
						$smarty->assign('preview_url', $preview_url);
						$smarty->assign('preview_width', $uploadmanager->upload_width);
						$smarty->assign('preview_height', $uploadmanager->upload_height);
						$smarty->assign('upload_id', $uploadmanager->upload_id);

						$step=3;
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
		//user likes the image, lets have them agree to our terms
		elseif (isset($_POST['agreeterms']))
		{
			//preserve the upload id
			if($uploadmanager->validUploadId($_POST['upload_id']))
				$smarty->assign('upload_id', $_POST['upload_id']);
		
			//preserve title and comment
			$smarty->assign('title', stripslashes($_POST['title']));
			$smarty->assign('comment', stripslashes($_POST['comment']));
		
		
			$step=4;
		}
		elseif (isset($_POST['finalise']))
		{
			//create the image record
			if($uploadmanager->setUploadId($_POST['upload_id']))
			{
				$uploadmanager->setTitle(stripslashes($_POST['title']));
				$uploadmanager->setComment(stripslashes($_POST['comment']));
				
				$uploadmanager->commit();
			}
			
			$step=5;
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
