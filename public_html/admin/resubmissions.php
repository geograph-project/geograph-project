<?php
/**
 * $Project: GeoGraph $
 * $Id: resubmissions.php 8587 2017-09-02 12:36:34Z barry $
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

dieIfReadOnly();


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

$db = NewADOConnection($GLOBALS['DSN']);
if (!$db) die('Database connection failed');

$smarty = new GeographPage;

//doing some moderating?
if (isset($_POST['gridimage_id']))
{
	$gridimage_id=intval($_POST['gridimage_id']);
	$pending_id=intval($_POST['pending_id']);

	$image=new GridImage;
	if ($image->loadFromId($gridimage_id))
	{
		//we really need this not be interupted
		ignore_user_abort(TRUE);
		set_time_limit(3600);

		$smarty->assign('message', 'Verification saved - thank you');

		if (!empty($_POST['broken'])) {

			$row = $db->getRow("SELECT * FROM gridimage_pending WHERE gridimage_id = {$gridimage_id} ");

			//email me if we lag, but once gets big no point continuing to notify!
			ob_start();
			print "\n\nHost: ".`hostname`."\n\n";
			print "\n\nView: {$CONF['SELF_HOST']}/admin/resubmissions.php?review=$gridimage_id\n\n";
			print_r($row);
			$con = ob_get_clean();
			mail('geograph@barryhunter.co.uk','[Geograph] Resubmission failure!',$con);

			//unclog the queue!
			$status = 'rejected';

		} elseif (!empty($_POST['confirm']) || !empty($_POST['similar'])) {

			$image->originalUrl = 	$image->_getOriginalpath(true,false,'_original');
			$image->previewUrl = 	$image->_getOriginalpath(true,false,'_preview');
			$image->pendingUrl = 	$image->_getOriginalpath(true,false,'_pending');

			//we actually have a file to move!
			if ($image->pendingUrl != "/photos/error.jpg") {

				$filesystem = new FileSystem();

				//delete the current original file if any
				if ($image->originalUrl != "/photos/error.jpg") {
					$filesystem->unlink($_SERVER['DOCUMENT_ROOT'].$image->originalUrl, true); //setting invalidate=true, as will invalidate in CloudFront

					//todo  (greatest(withd,height) <= 1024) - the original would only be cached in varnish if was used on the photo page!
				}

				//delete the _640x640 too! (incase its no longer relevent) - if needbe, will be recrated from previewUrl below
				foreach (array(640,800,1024,1600) as $size) {
					$thumbnail = $image->_getOriginalpath(true,false,"_{$size}x{$size}");
					if (basename($thumbnail) != "error.jpg") {
						//delete teh actual file
						$filesystem->unlink($_SERVER['DOCUMENT_ROOT'].$thumbnail, true);

						//delete the memcache copy
						$mkey = "{$gridimage_id}:{$size}x{$size}";
						$memcache->name_delete('is',$mkey);

						//delete the database copy
						$db->Execute("DELETE FROM gridimage_thumbsize WHERE gridimage_id = $gridimage_id AND maxw = $size");
					}
				}

				//save the pending as original
				$image->storeOriginal($_SERVER['DOCUMENT_ROOT'].$image->pendingUrl);

				//send a copy to Amazon (only used if GeoGridFS not used)
				if (!empty($CONF['awsAccessKey'])) {

					$image->originalUrl = 	$image->_getOriginalpath(true,false,'_original');

					require_once("3rdparty/S3.php");

					$s3 = new S3($CONF['awsAccessKey'], $CONF['awsSecretKey'], false);
					$ok = $s3->putObjectFile($_SERVER['DOCUMENT_ROOT'].$image->originalUrl, $CONF['awsS3Bucket'], preg_replace("/^\//",'',$image->originalUrl), S3::ACL_PRIVATE);
				}

				//refresh the preview image
				if ($image->previewUrl != "/photos/error.jpg") {
					if (!empty($_POST['confirm'])) {
						//delete the preview - we dont need it
						$filesystem->unlink($_SERVER['DOCUMENT_ROOT'].$image->previewUrl);
					} else {
						//store the preview as an alterantive fullsize
						$image->storeImage($_SERVER['DOCUMENT_ROOT'].$image->previewUrl,true,'_640x640');
					}
				}

				//clear caches involving the image
				$ab=floor($gridimage_id/10000);
				$smarty->clear_cache('', "img$ab|{$gridimage_id}|");

				//clear memcache
				$mkey = "{$gridimage_id}:F";
				$memcache->name_delete('is',$mkey);

				//delete the cache
				$db->Execute("DELETE FROM gridimage_size WHERE gridimage_id = $gridimage_id"); // could populate this now, but easier to delete, and let it autorecreate


				$status = empty($_POST['confirm'])?'accepted':'confirmed';

			} else {
				$smarty->assign('message', 'Verification failed - please let us know!');
			}
		} else {
			$status = 'rejected';

		}
		if (!empty($status))
			$db->Execute("UPDATE gridimage_pending gp SET status = '$status' WHERE gridimage_id = {$gridimage_id} AND (pending_id = {$pending_id} OR status IN ('new','open'))");

		$smarty->assign("last_id", $gridimage_id);
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

	$sql = "SELECT gi.*, pending_id
		FROM gridimage_pending gp INNER JOIN gridimage gi USING (gridimage_id)
		WHERE (gp.status = 'new' OR (gp.status = 'open' AND updated < DATE_SUB(NOW(),INTERVAL 1 HOUR) ) )
		AND type = 'original'
		LIMIT 1";
} else {
	$id = intval($_GET['review']);
	$sql = "SELECT gi.*, pending_id, status
		FROM gridimage_pending gp INNER JOIN gridimage gi USING (gridimage_id)
		WHERE gridimage_id = $id
		LIMIT 1";
}


#############################
# fetch the list of images...

$data = $db->getRow($sql);

if ($data && empty($_GET['review'])) {
	$db->Execute("UPDATE gridimage_pending gp SET updated = NOW() WHERE gridimage_id = {$data['gridimage_id']} AND status = 'open'"); //if already open 'lock' it again.
	$db->Execute("UPDATE gridimage_pending gp SET status = 'open' WHERE gridimage_id = {$data['gridimage_id']} AND status = 'new'"); //dont affect historic reports!
}

#############################

$db->Execute("UNLOCK TABLES");

#############################

if ($data) {
	$image = new GridImage;
	$image->_initFromArray($data);

	$image->pendingUrl = $image->_getOriginalpath(true,false,'_pending');
	$image->previewUrl = $image->_getOriginalpath(true,false,'_preview');


	if ($image->previewUrl == "/photos/error.jpg" && !empty($_GET['review'])) {
		//deal when its already approved!
		// ... although in this case, ther MAY not be a '640px' thumb, in which case, it SHOULD be idential!

		$image->pendingUrl = $image->_getOriginalpath(true,false,'_original');

		$size = 640;
		$thumbnail = $image->_getOriginalpath(true,false,"_{$size}x{$size}");
		$image->previewUrl = $thumbnail;
	}

	$filesystem = new FileSystem();

	$image->pendingSize = $filesystem->filesize($_SERVER['DOCUMENT_ROOT'].$image->pendingUrl);

	$smarty->assign_by_ref('image', $image);
}


//what style should we use?
$style = $USER->getStyle();
$smarty->assign('maincontentclass', 'content_photo'.$style);

$smarty->display('admin_resubmissions.tpl',$style);

