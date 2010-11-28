<?php
/**
 * $Project: GeoGraph $
 * $Id: faq.php 15 2005-02-16 12:23:35Z lordelph $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2006 Barry Hunter (geo@barryhunter.co.uk)
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

if (empty($_GET['id']) || preg_match('/[^\d]/',$_GET['id'])) {
	$smarty->display('static_404.tpl');
	exit;
}

$blog_id = intval($_GET['id']);

$db=NewADOConnection($GLOBALS['DSN']);

$isadmin=$USER->hasPerm('moderator')?1:0;




$template = 'blog_entry.tpl';
$cacheid = $blog_id;


$sql_where = " blog_id = ".$db->Quote($blog_id);

$page = $db->getRow("
select blog.*,
realname,gs.gridsquare_id,gs.grid_reference
from blog 
	left join user using (user_id)
	left join gridsquare gs on (blog.gridsquare_id = gs.gridsquare_id)
where $sql_where
limit 1");

if (count($page)) {
	
	if ($page['approved'] == -1 && !$USER->hasPerm('moderator')) {
		header("HTTP/1.0 403 Forbidden");
		header("Status: 403 Forbidden");
		$template = "static_404.tpl";
	}

	if ($page['user_id'] == $USER->user_id) {
		$cacheid .= '|'.$USER->user_id;
	}
	
	//when this page was modified
	$mtime = strtotime($page['updated']);
		
	//can't use IF_MODIFIED_SINCE for logged in users as has no concept as uniqueness
	customCacheControl($mtime,$cacheid,($USER->user_id == 0));

}

if (!$smarty->is_cached($template, $cacheid))
{
	if (count($page)) {
		$smarty->assign('google_maps_api_key',$CONF['google_maps_api_key']);
		
		$smarty->assign($page);
		if (!empty($page['extract'])) {
			$smarty->assign('meta_description', $page['description']);
		}
		
		if (!empty($page['gridsquare_id'])) {
			$square=new GridSquare;
			$square->loadFromId($page['gridsquare_id']);
			$smarty->assign('grid_reference', $square->grid_reference);
			
			require_once('geograph/conversions.class.php');
			$conv = new Conversions;
		
			list($lat,$long) = $conv->gridsquare_to_wgs84($square);
			$smarty->assign('lat', $lat);
			$smarty->assign('long', $long);
		}
		if (!empty($page['gridimage_id'])) {
			
			$image=new GridImage();
			$image->loadFromId($page['gridimage_id']);

			if ($image->moderation_status=='rejected' || $image->moderation_status=='pending') {
				//clear the image
				$image= false;
			} 
			$smarty->assign_by_ref('image', $image);
		}
	} else {
		$template = 'static_404.tpl';
		header("HTTP/1.0 404 Not Found");
		header("Status: 404 Not Found");
	}
} else {
	$smarty->assign('user_id', $page['user_id']);
}


$smarty->assign('blog_id', $page['blog_id']);

$smarty->display($template, $cacheid);
