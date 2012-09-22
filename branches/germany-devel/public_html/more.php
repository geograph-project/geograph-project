<?php
/**
 * $Project: GeoGraph $
 * $Id: ecard.php 3886 2007-11-02 20:14:19Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Barry Hunter (geo@barryhunter.co.uk)
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
require_once('geograph/uploadmanager.class.php');

init_session();

$smarty = new GeographPage;
$template='more.tpl';	

$USER->mustHavePerm("basic");



if (isset($_REQUEST['id']))
{
	//initialise message
	require_once('geograph/gridsquare.class.php');
	require_once('geograph/gridimage.class.php');

	$image=new GridImage();
	$ok = $image->loadFromId($_REQUEST['id']);
	
	if (!$ok || $image->moderation_status=='rejected') {
		//clear the image
		$image=new GridImage;
		header("HTTP/1.0 410 Gone");
		header("Status: 410 Gone");
		$template = "static_404.tpl";
	} else {
		
		$image->altUrl = $image->_getOriginalpath(true,false,'_640x640');
		
		$image->originalUrl = $image->_getOriginalpath(true,false);
		$image->originalSize = filesize($_SERVER['DOCUMENT_ROOT'].$image->originalUrl);
		
		$style = $USER->getStyle();
		$smarty->assign('maincontentclass', 'content_photo'.$style);

		$imagesize = $image->_getFullSize();

		$sizes = array();
		$widths = array();
		$heights = array();
		$showorig = false;
		if ($image->original_width) {
			$smarty->assign('original_width', $image->original_width);
			$smarty->assign('original_height', $image->original_height);
			$uploadmanager=new UploadManager;
			list($destwidth, $destheight, $maxdim, $changedim) = $uploadmanager->_new_size($image->original_width, $image->original_height);
			if ($changedim) {
				$showorig = true;
				foreach ($CONF['show_sizes'] as $cursize) {
					list($destwidth, $destheight, $destdim, $changedim) = $uploadmanager->_new_size($image->original_width, $image->original_height, $cursize);
					if (!$changedim)
						break;
					$sizes[] = $cursize;
					$widths[] = $destwidth;
					$heights[] = $destheight;
					$maxdim = $destdim;
				}
				$maxdim = max($image->original_width, $image->original_height);
			}
		} else {
			$maxdim = max($imagesize[0], $imagesize[1]);
		}
		$smarty->assign('sizes', $sizes);
		$smarty->assign('widths', $widths);
		$smarty->assign('heights', $heights);
		$smarty->assign('stdsize', $CONF['img_max_size']);
		$smarty->assign('showorig', $showorig);
		$smarty->assign('ratio', $maxdim/$CONF['prev_size']);
		$smarty->assign('preview_width', $imagesize[0]);
		$smarty->assign('preview_height', $imagesize[1]);
		$smarty->assign('preview_url', $image->_getFullpath());
	}
	$smarty->assign_by_ref('image', $image);
} else {
	header("HTTP/1.0 404 Not Found");
	header("Status: 404 Not Found");
	$template = "static_404.tpl";
}


$smarty->display($template);

?>
