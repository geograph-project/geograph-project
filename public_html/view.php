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

if (isset($_GET['id']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'http://geourl.org/bot')!==FALSE) ) {
	//die as quickly as possible with the minimum html (with the approval of geourl owner)
	$db = NewADOConnection($GLOBALS['DSN']);

	$row =& $db->getRow("select gridimage_id,wgs84_lat,wgs84_long,title,grid_reference from gridimage_search where gridimage_id=".intval($_GET['id']) );

	if ($row['wgs84_lat']) {
		$title = htmlentities($row['title']."::".$row['grid_reference']);

		print "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\"/>\n";
		print "<title>$title</title>\n";
		print "<meta name=\"ICBM\" content=\"{$row['wgs84_lat']}, {$row['wgs84_long']}\"/>\n";
		print "<meta name=\"DC.title\" content=\"Geograph::$title\"/>\n";
		print "<a href=\"http://{$_SERVER['HTTP_HOST']}/photo/{$row['gridimage_id']}\">View image page</a>";
	} elseif ($row['gridimage_id']) {
		header("HTTP/1.0 500 Server Error");
		header("Status: 500 Server Error");
		print "<title>Lat/Long not available, try again later</title>";
		print "<a href=\"http://{$_SERVER['HTTP_HOST']}/photo/{$row['gridimage_id']}\">View image page</a>";
	} else {
		header("HTTP/1.0 404 Not Found");
		header("Status: 404 Not Found");
		print "<title>Image no longer available</title>";
	}
	exit;
} elseif ((strpos($_SERVER["REQUEST_URI"],'/photo/') === FALSE && isset($_GET['id'])) || strlen($_GET['id']) !== strlen(intval($_GET['id']))) {
	//keep urls nice and clean - esp. for search engines!
	header("HTTP/1.0 301 Moved Permanently");
	header("Status: 301 Moved Permanently");
	header("Location: /photo/".intval($_GET['id']));
	print "<a href=\"http://{$_SERVER['HTTP_HOST']}/photo/".intval($_GET['id'])."\">View image page</a>";
	exit;
}


require_once('geograph/gridimage.class.php');
require_once('geograph/gridsquare.class.php');
require_once('geograph/mapmosaic.class.php');
require_once('geograph/rastermap.class.php');

init_session();

if (isset($_GET['style'])) {
	$USER->getStyle();
	if (isset($_GET['id'])) {
		header("HTTP/1.0 301 Moved Permanently");
		header("Status: 301 Moved Permanently");
		header("Location: /photo/".intval($_GET['id']));
		exit;
	}
	header("Location: /");
	exit;
}

customGZipHandlerStart();

$smarty = new GeographPage;

$template='view.tpl';

$cacheid=0;

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*3; //3hour cache

$image=new GridImage;

if (isset($_GET['id']))
{
	$image->loadFromId($_GET['id']);
	$isowner=($image->user_id==$USER->user_id)?1:0;
	$ismoderator=$USER->hasPerm('moderator')?1:0;

	$ab=floor($_GET['id']/10000);

	$cacheid="img$ab|{$_GET['id']}|{$isowner}_{$ismoderator}";

	//is the image rejected? - only the owner and administrator should see it
	if ($image->moderation_status=='rejected')
	{
		if ($isowner||$ismoderator)
		{
			//ok, we'll let it lie...
		}
		else
		{
			//clear the image
			$image=new GridImage;
			$cacheid=0;
			$rejected = true;
		}
	}
}

//do we have a valid image?
if ($image->isValid())
{
	//what style should we use?
	$style = $USER->getStyle();
	$cacheid.=$style;


	//when this image was modified
	$mtime = strtotime($image->upd_timestamp);

	//page is unqiue per user (the profile and links)
	$hash = $cacheid.'.'.$USER->user_id;

	//can't use IF_MODIFIED_SINCE for logged in users as has no concept as uniqueness
	customCacheControl($mtime,$hash,($USER->user_id == 0));

	if (!$smarty->is_cached($template, $cacheid))
	{
		function smarty_function_hidekeywords($input) {
			return preg_replace('/(^|[\n\r\s]+)(Keywords?[\s:][^\n\r>]+)$/','<span class="keywords">$2</span>',$input);
		}
		$smarty->register_modifier("hidekeywords", "smarty_function_hidekeywords");

		$taken=$image->getFormattedTakenDate();

		//get the grid references
		$image->getSubjectGridref(true);
		$image->getPhotographerGridref(true);

		$smarty->assign('maincontentclass', 'content_photo'.$style);


		//remove grid reference from title
		$image->bigtitle=trim(preg_replace("/^{$image->grid_reference}/", '', $image->title));
		$image->bigtitle=preg_replace('/(?<![\.])\.$/', '', $image->bigtitle);

		$smarty->assign('page_title', $image->bigtitle.":: OS grid {$image->grid_reference}");

		$smarty->assign('image_taken', $taken);
		$smarty->assign('ismoderator', $ismoderator);
		$smarty->assign_by_ref('image', $image);

		//get a token to show a suroudding geograph map
		$mosaic=new GeographMapMosaic;
		$smarty->assign('map_token', $mosaic->getGridSquareToken($image->grid_square));


		//find a possible place within 25km
		$place = $image->grid_square->findNearestPlace(75000);
		$smarty->assign_by_ref('place', $place);

		if (empty($image->comment)) {
			$smarty->assign('meta_description', "{$image->grid_reference} :: {$image->bigtitle}, ".strip_tags(smarty_function_place(array('place'=>$place))) );
		} else {
			$smarty->assign('meta_description', $image->comment);
		}

		if ($CONF['forums']) {
			//let's find posts in the gridref discussion forum
			$image->grid_square->assignDiscussionToSmarty($smarty);
		}

		//count the number of photos in this square
		$smarty->assign('square_count', $image->grid_square->imagecount);

		//lets add an overview map too
		$overview=new GeographMapMosaic('largeoverview');
		$overview->setCentre($image->grid_square->x,$image->grid_square->y); //does call setAlignedOrigin
		$overview->assignToSmarty($smarty, 'overview');
		$smarty->assign('marker', $overview->getSquarePoint($image->grid_square));


		require_once('geograph/conversions.class.php');
		$conv = new Conversions;

		list($lat,$long) = $conv->gridsquare_to_wgs84($image->grid_square);
		$smarty->assign('lat', $lat);
		$smarty->assign('long', $long);

		list($latdm,$longdm) = $conv->wgs84_to_friendly($lat,$long);
		$smarty->assign('latdm', $latdm);
		$smarty->assign('longdm', $longdm);

		//lets add an rastermap too
		$rastermap = new RasterMap($image->grid_square,false);
		$rastermap->addLatLong($lat,$long);
		if (!empty($image->viewpoint_northings)) {
			$rastermap->addViewpoint($image->viewpoint_eastings,$image->viewpoint_northings,$image->viewpoint_grlen,$image->view_direction);
		} elseif (isset($image->view_direction) && strlen($image->view_direction) && $image->view_direction != -1) {
			$rastermap->addViewDirection($image->view_direction);
		}
		$smarty->assign_by_ref('rastermap', $rastermap);


		$smarty->assign('x', $image->grid_square->x);
		$smarty->assign('y', $image->grid_square->y);

		if ($image->view_direction > -1) {
			$smarty->assign('view_direction', ($image->view_direction%90==0)?strtoupper(heading_string($image->view_direction)):ucwords(heading_string($image->view_direction)) );
		}
		
		$level = ($image->grid_square->imagecount > 1)?6:5;
		$smarty->assign('sitemap',getSitemapFilepath($level,$image->grid_square)); 
	}
} elseif (!empty($rejected)) {
	header("HTTP/1.0 410 Gone");
	header("Status: 410 Gone");
} else {
	header("HTTP/1.0 404 Not Found");
	header("Status: 404 Not Found");
}



$smarty->display($template, $cacheid);


?>
