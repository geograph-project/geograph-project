<?php
/**
 * $Project: GeoGraph $
 * $Id: resubmissions.php 6789 2010-07-12 21:43:45Z barry $
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

if (isset($CONF['curtail_level']) && $CONF['curtail_level'] > 5 && strpos($_SERVER['HTTP_REFERER'],'editimage') === FALSE) {
	header("HTTP/1.1 503 Service Unavailable");
	die("the servers are currently very busy - moderation is disabled to allow things to catch up, will be automatically re-enabled when load returns to normal");
}

require_once('geograph/gridimage.class.php');
require_once('geograph/gridsquare.class.php');
require_once('geograph/imagelist.class.php');

init_session();

$USER->mustHavePerm("moderator");


if (!empty($_GET['style'])) {
	$USER->getStyle();
	if (!empty($_SERVER['QUERY_STRING'])) {
		$query = preg_replace('/style=(\w+)/','',$_SERVER['QUERY_STRING']);
		header("HTTP/1.0 301 Moved Permanently");
		header("Status: 301 Moved Permanently");
		header("Location: /admin/moderation.php?".$query);
		exit;
	}
	header("Location: /admin/moderation.php");
	exit;
}

customGZipHandlerStart();

$db = GeographDatabaseConnection();

$smarty = new GeographPage;

$altimg = !empty($_POST['altimg']) || !empty($_GET['altimg']);

//doing some moderating?
if (isset($_POST['gridimage_id']))
{
	
	$gridimage_id=intval($_POST['gridimage_id']);

	$image=new GridImage;
	if ($image->loadFromId($gridimage_id))
	{
		//we really need this not be interupted
		ignore_user_abort(TRUE);
		set_time_limit(3600);

		$smarty->assign('message', 'Verification saved - thank you');
		$wheretype =  " AND type ".($altimg ? '=' : '!=')." 'altimg'";

		if (!empty($_POST['broken'])) {
			
			$row = $db->getRow("SELECT * FROM gridimage_pending WHERE gridimage_id = {$gridimage_id}".$wheretype);
			
			//email me if we lag, but once gets big no point continuing to notify!
			ob_start();
			print "\n\nHost: ".`hostname -f`."\n\n";
			if ($altimg)
				print "\n\nView: http://{$_SERVER['HTTP_HOST']}/admin/resubmissions.php?altimg=1&review=$gridimage_id\n\n";
			else
				print "\n\nView: http://{$_SERVER['HTTP_HOST']}/admin/resubmissions.php?review=$gridimage_id\n\n";
			print_r($row);
			print_r($_SERVER);
			$con = ob_get_clean();
			$geofrom = "From: Geograph <{$CONF['mail_from']}>";
			$envfrom = is_null($CONF['mail_envelopefrom'])?null:"-f {$CONF['mail_envelopefrom']}";
			mail($CONF['admin_email'], '[Geograph] Resubmission failure!', $con, $geofrom, $envfrom);
			
			//unclog the queue!
			$db->Execute("UPDATE gridimage_pending gp SET status = 'rejected' WHERE gridimage_id = {$gridimage_id}".$wheretype);
			
		} elseif (!empty($_POST['confirm']) || !empty($_POST['similar'])) {
			$suffix = $altimg ? '_altimg' : '';

			$image->originalUrl = 	$image->_getOriginalpath(true, false, $altimg ? '_altimg' : '_original');
			$image->previewUrl = 	$image->_getOriginalpath(true, false, '_preview'.$suffix);
			$image->pendingUrl = 	$image->_getOriginalpath(true, false, '_pending'.$suffix);

			//we actually hav a file to move!
			if ($image->pendingUrl != "/photos/error.jpg") {
			
				//delete the current original file if any
				if ($image->originalUrl != "/photos/error.jpg") {
					unlink($_SERVER['DOCUMENT_ROOT'].$image->originalUrl);
				}
			
				//save the pending as original
				$image->storeOriginal($_SERVER['DOCUMENT_ROOT'].$image->pendingUrl,true,$altimg);
			
				if (!empty($CONF['awsAccessKey'])) {
					
					$image->originalUrl = 	$image->_getOriginalpath(true,false,'_original');
					
					require_once("3rdparty/S3.php");

					$s3 = new S3($CONF['awsAccessKey'], $CONF['awsSecretKey']);
					
					$ok = $s3->putObjectFile($_SERVER['DOCUMENT_ROOT'].$image->originalUrl, $CONF['awsS3Bucket'], preg_replace("/^\//",'',$image->originalUrl), S3::ACL_PRIVATE);
				}
			
				
				if ($image->previewUrl != "/photos/error.jpg") {
					if (!empty($_POST['confirm'])) {
						//delete the preview - we dont need it
						unlink($_SERVER['DOCUMENT_ROOT'].$image->previewUrl);
					} else {
						//store the preview as an alterantive fullsize
						$image->storeImage($_SERVER['DOCUMENT_ROOT'].$image->previewUrl,true,$suffix.'_640x640');# FIXME?
					}
				}


				//clear caches involving the image
				$ab=floor($gridimage_id/10000);
				$smarty->clear_cache('', "img$ab|{$gridimage_id}|");

				$mkey = "{$gridimage_id}:F";
				$memcache->name_delete('is',$mkey);

				if (!$altimg) {
					$db->Execute("DELETE FROM gridimage_size WHERE gridimage_id = $gridimage_id"); #FIXME delete files?
				}
				#FIXME altimg: size -> database?

				if (!empty($_POST['confirm'])) {
					$db->Execute("UPDATE gridimage_pending gp SET status = 'confirmed' WHERE gridimage_id = {$gridimage_id}".$wheretype);
				} else {
					$db->Execute("UPDATE gridimage_pending gp SET status = 'accepted' WHERE gridimage_id = {$gridimage_id}".$wheretype);
				}
			} else {
				$smarty->assign('message', 'Verification failed - please let us know!');
			}
		} else {
			$db->Execute("UPDATE gridimage_pending gp SET status = 'rejected' WHERE gridimage_id = {$gridimage_id}".$wheretype);

		} 


		$smarty->assign("last_id", $gridimage_id);
		$smarty->assign("altimg", $altimg);
	}
	else
	{
		echo "FAIL";
		exit;
	}
	

}

#############################

//lock the table so nothing can happen in between! (leave others as READ so they dont get totally locked)
$db->Execute("LOCK TABLES 
gridimage_pending gp WRITE,
gridimage gi READ
");

#############################
# define the images to moderate

if (empty($_GET['review'])) {
	$wheretype =  " AND type = ".($altimg ? "'altimg'" : "'original'");
$sql = "SELECT gi.*
FROM gridimage_pending gp INNER JOIN gridimage gi USING (gridimage_id)
WHERE (gp.status = 'new' OR (gp.status = 'open' AND updated < DATE_SUB(NOW(),INTERVAL 1 HOUR) ) )
$wheretype
LIMIT 1"; 
} else {
	$wheretype =  " AND type ".($altimg ? '=' : '!=')." 'altimg'";
	$id = intval($_GET['review']);
$sql = "SELECT gi.*
FROM gridimage_pending gp INNER JOIN gridimage gi USING (gridimage_id)
WHERE gridimage_id = $id $wheretype
LIMIT 1"; 
}


#############################
# fetch the list of images...

$data = $db->getRow($sql);

if ($data && empty($_GET['review'])) {
	$wheretype =  " AND type ".($altimg ? '=' : '!=')." 'altimg'";
	$db->Execute("UPDATE gridimage_pending gp SET status = 'open' WHERE gridimage_id = {$data['gridimage_id']}".$wheretype);
}

#############################

$db->Execute("UNLOCK TABLES");

#############################

if ($data) {
	$suffix = $altimg ? '_altimg' : '';

	$image = new GridImage;
	$image->_initFromArray($data);

	$image->pendingUrl = $image->_getOriginalpath(true,false,'_pending'.$suffix);
	$image->previewUrl = $image->_getOriginalpath(true,false,'_preview'.$suffix);

	$image->pendingSize = filesize($_SERVER['DOCUMENT_ROOT'].$image->pendingUrl);

	$smarty->assign_by_ref('image', $image);
	$smarty->assign('altimg', $altimg);
}


//what style should we use?
$style = $USER->getStyle();
$smarty->assign('maincontentclass', 'content_photo'.$style);
		
$smarty->display('admin_resubmissions.tpl',$style);
	
?>
