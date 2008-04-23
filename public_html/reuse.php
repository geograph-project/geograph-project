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

init_session();

$smarty = new GeographPage;
$template='reuse.tpl';	



if (isset($_REQUEST['id']))
{
	//initialise message
	require_once('geograph/gridsquare.class.php');
	require_once('geograph/gridimage.class.php');

	$image=new GridImage();
	$image->loadFromId($_REQUEST['id']);
	
	if ($image->moderation_status=='rejected') {
		//clear the image
		$image=new GridImage;
		header("HTTP/1.0 410 Gone");
		header("Status: 410 Gone");
		$template = "view.tpl";
	} else {
		if (isset($_REQUEST['download']) && $_REQUEST['download'] == $image->_getAntiLeechHash()) {
			$filepath = $image->_getFullpath();
			$filename = basename($filepath);
			$filename = preg_replace('/(\.jpg)/'," by {$image->realname}\$1",$filename);
			$filename = preg_replace('/ /','-',trim($filename));
			$filename = preg_replace('/[^\w-\.,]+/','',$filename);
			$lastmod = filemtime($_SERVER['DOCUMENT_ROOT'].$filepath);
			
			header("Content-Type: image/jpeg");
			header("Content-Disposition: attachment; filename=\"$filename\"");
			
			customExpiresHeader(86400,true);
			customCacheControl($lastmod,$image->gridimage_id);
			
			readfile($_SERVER['DOCUMENT_ROOT'].$filepath);
			exit;
		}

		$smarty->assign_by_ref('msg', $msg);


		require_once('geograph/conversions.class.php');
		$conv = new Conversions;

		list($lat,$long) = $conv->gridsquare_to_wgs84($image->grid_square);
		$smarty->assign('lat', $lat);
		$smarty->assign('long', $long);

		list($latdm,$longdm) = $conv->wgs84_to_friendly($lat,$long);
		$smarty->assign('latdm', $latdm);
		$smarty->assign('longdm', $longdm);
		
		if (!empty($image->viewpoint_northings)) {
			list($lat,$long) = $conv->national_to_wgs84($image->viewpoint_eastings,$image->viewpoint_northings,$image->grid_square->reference_index);
			$smarty->assign('photographer_lat', $lat);
			$smarty->assign('photographer_long', $long);
		}
	}
	$smarty->assign_by_ref('image', $image);
} else {
	header("HTTP/1.0 404 Not Found");
	header("Status: 404 Not Found");
	$template = "view.tpl";
}


$smarty->display($template);

?>
