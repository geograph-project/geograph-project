<?php
/**
 * $Project: GeoGraph $
 * $Id: view.php 7564 2012-02-07 19:26:00Z barry $
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
require_once('geograph/mapmosaic.class.php');
require_once('geograph/rastermap.class.php');


customGZipHandlerStart();

$smarty = new GeographPage;

$template='tile_info.tpl';

$cacheid=0;

if ($smarty->caching) {
	$smarty->caching = 2; // lifetime is per cache
	$smarty->cache_lifetime = 3600*24; //24hour cache
}

$image=new GridImage;

if (isset($_GET['id']))
{
	$image->loadFromId(intval($_GET['id']));

	$ab=floor($_GET['id']/10000);

	$cacheid="img$ab|{$_GET['id']}";

	//is the image rejected? - only the owner and administrator should see it
	if ($image->moderation_status=='rejected')
	{
			$db = GeographDatabaseConnection(true);
			if ($to = $db->getOne("SELECT destination FROM gridimage_redirect WHERE gridimage_id = ".intval($_GET['id']))) {
		                header("HTTP/1.0 301 Moved Permanently");
                		header("Status: 301 Moved Permanently");
		                header("Location: /tile-info.php?id=".intval($to));
                		exit;
			}

			//clear the image
			$image=new GridImage;
			$cacheid=0;
			$rejected = true;
	}
}

//do we have a valid image?
if ($image->isValid())
{
	//when this image was modified
	$mtime = strtotime($image->upd_timestamp);

	//page is unqiue per user (the profile and links)
	$hash = $cacheid.'.'.$mtime;

		customCacheControl($mtime,$hash);


	if (!$smarty->is_cached($template, $cacheid))
	{
		$image->assignToSmarty($smarty);

	} else {
		$smarty->assign_by_ref("image",$image); //we dont need the full assignToSmarty
	}

} elseif (!empty($rejected)) {
	header("HTTP/1.0 410 Gone");
	header("Status: 410 Gone");
	$template = "static_404.tpl";
} else {
	header("HTTP/1.0 404 Not Found");
	header("Status: 404 Not Found");
	$template = "static_404.tpl";
}

$smarty->display($template, $cacheid);


