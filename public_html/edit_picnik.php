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


$template='edit_picnik.tpl';
$cacheid='';


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
		
		if (isset($_POST['save'])) {
			
			
			if ($_POST['conf'] != md5($image->gridimage_id.$CONF['register_confirmation_secret']))
			{
				$smarty->assign('error', "There were problems processing your upload - please contact us");
			} 
			elseif (empty($_POST['updatenote']))
			{
				$smarty->assign('error', "Please supply a comment");
			} 
			elseif ($uploadmanager->processURL($_POST['jpeg_url']))
			{
				$uid = $uploadmanager->upload_id;
				$comment=stripslashes($_POST['updatenote']);
				
				$ticket=new GridImageTroubleTicket();
				$ticket->setSuggester($USER->user_id);				
				$ticket->setImage($image->gridimage_id);
				$ticket->setNotes("Auto-generated ticket as a result of Image Edit.\n Reason: $comment");
				$status=$ticket->commit('pending');
				
				$db = NewADOConnection($GLOBALS['DSN']);

				$sql = sprintf("insert into gridimage_pending (gridimage_id,upload_id,user_id,gridimage_ticket_id,suggested) ".
					"values (%s,%s,%s,%s,now())",
					$db->Quote($image->gridimage_id),
					$db->Quote($uploadmanager->upload_id),
					$db->Quote($USER->user_id),
					$db->Quote($ticket->gridimage_ticket_id));
					
				if ($db->Execute($sql) === false) 
				{
					$smarty->assign('error', 'error inserting: '.$db->ErrorMsg());
					$ok=false;
				}
				
				
				header("Location: /photo/{$_REQUEST['id']}");
				exit;
			} else {
				$smarty->assign('error', $uploadmanager->errormsg);
			
			}
			
			
			unset($_POST['comment']);
			$smarty->assign('_post',$_POST);
			$smarty->display('edit_picnik.tpl');			
			exit;
		}
		
		
		if (isset($_POST['picnik']) && $_POST['picnik'] == 'return') {
			unset($_POST['picnik']);
			$smarty->assign('_post',$_POST);
			$smarty->display('edit_picnik.tpl');			
			exit;
		}

		$q = array();
		$q['_apikey'] = $CONF['picnik_api_key'];
		$q['_page'] = '/in/upload';
		$q['_export'] = "http://{$_SERVER['HTTP_HOST']}/edit_picnik.php";
		$q['_export_field'] = 'jpeg_url';
		$q['_export_agent'] = 'browser';
		$q['_export_method'] = 'POST';
		$q['_userid'] = md5($USER->user_id.$CONF['register_confirmation_secret']);
		$q['_export_title'] = 'Send to Geograph';
		$q['_host_name'] = 'Geograph';
		$q['_imageid'] = $image->gridimage_id;
		$q['_replace'] = 'confirm';
		$q['id'] = $image->gridimage_id;
		$q['conf'] = md5($image->gridimage_id.$CONF['register_confirmation_secret']);
		
		$fullpath=$image->_getFullpath();
		$q['_import'] = "http://".$_SERVER['HTTP_HOST'].$fullpath;
		$details = $image->getThumbnail(213,160,2);
		$fullpath=$details['url'];
		$q['_original_thumb'] = "http://".$_SERVER['HTTP_HOST'].$fullpath;
		
		if ($CONF['picnik_method'] == 'inabox' && !preg_match('/safari|msie 6/i',$_SERVER['HTTP_USER_AGENT'])) { 
			$q['picnik'] = 'return';
			$smarty->assign('picnik_url','http://www.picnik.com/service?'.http_build_query($q));
			$smarty->display('edit_picnik.tpl');
		} else {
			header('Location: http://www.picnik.com/service?'.http_build_query($q));
		}
		exit;




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
