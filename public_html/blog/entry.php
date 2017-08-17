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

if (empty($CONF['forums'])) {
        header("HTTP/1.0 404 Not Found");

	$smarty = new GeographPage;
        $smarty->display('static_404.tpl');
        exit;
}

if ((!preg_match('/\/blog\/\d+/',$_SERVER["REQUEST_URI"]) && isset($_GET['id'])) || strlen($_GET['id']) !== strlen(intval($_GET['id']))) {
        //keep urls nice and clean - esp. for search engines!
        header("HTTP/1.0 301 Moved Permanently");
        header("Status: 301 Moved Permanently");
        header("Location: /blog/".intval($_GET['id']));
        print "<a href=\"{$CONF['SELF_HOST']}/blog/".intval($_GET['id'])."\">View blog entry</a>";
        exit;
}

init_session();

$smarty = new GeographPage;

if (empty($_GET['id']) || preg_match('/[^\d]/',$_GET['id'])) {
        header("HTTP/1.0 404 Not Found");
	$smarty->display('static_404.tpl');
	exit;
}

$blog_id = intval($_GET['id']);

if ($blog_id > 10000000) {
        header("HTTP/1.0 404 Not Found");
	print "404 - not found";
	exit;
}


$db=GeographDatabaseConnection(false);

$isadmin=$USER->hasPerm('moderator')?1:0;




$template = 'blog_entry.tpl';
$cacheid = $blog_id;

        if (!function_exists('smarty_modifier_truncate')) {
                require_once("smarty/libs/plugins/modifier.truncate.php");
        }


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
		header("HTTP/1.0 410 Gone");
		header("Status: 410 Gone");
		$template = "static_404.tpl";
	}

	if ($page['user_id'] == $USER->user_id) {
		$cacheid .= '|'.$USER->user_id;
	}

	//when this page was modified
	$mtime = strtotime($page['updated']);

	//can't use IF_MODIFIED_SINCE for logged in users as has no concept as uniqueness
	customCacheControl($mtime,$cacheid,($USER->user_id == 0));

                if (!isset($_GET['dontcount']) && $CONF['template']!='archive'
                        && (stripos($_SERVER['HTTP_USER_AGENT'], 'http')===FALSE)
                        && (stripos($_SERVER['HTTP_USER_AGENT'], 'bot')===FALSE)
                        && (strpos($_SERVER['HTTP_USER_AGENT'], 'Web Preview')===FALSE)
                        ) {
                        $db->Execute("UPDATE LOW_PRIORITY blog SET views=views+1,updated=updated WHERE blog_id = ".$page['blog_id']);
                }

	if ($page['template'] != 1) {
		$template = 'blog_entry'.intval($page['template']).'.tpl';
	}
	if (!empty($_GET['t'])) {
		if ($_GET['t'] == 2) {
			$template = 'blog_entry2.tpl';
		} elseif ($_GET['t'] == 3) {
			$template = 'blog_entry3.tpl';
		} elseif ($_GET['t'] == 4) {
			$template = 'blog_entry4.tpl';
		} elseif ($_GET['t'] == 1) {
			$template = 'blog_entry.tpl';
		}
	}
}

if (!$smarty->is_cached($template, $cacheid))
{
	if (count($page)) {
		$smarty->assign('google_maps_api_key',$CONF['google_maps_api_key']);
		$extra_meta = array();
		$extra_meta[] = "<link rel=\"canonical\" href=\"{$CONF['CONTENT_HOST']}/blog/{$page['blog_id']}\" />";
                $extra_meta[] = "<meta name=\"twitter:card\" content=\"photo\">"; //or summary_large_image
                $extra_meta[] = "<meta name=\"twitter:site\" content=\"@geograph_bi\">";
                $extra_meta[] = "<meta name=\"og:title\" content=\"".htmlentities($page['title'])."\">";


		$smarty->assign($page);
		if (!empty($page['content'])) {
			$extract = smarty_modifier_truncate($page['content'],140,"...");

			$smarty->assign('meta_description', $extract);
			$extra_meta[] = "<meta name=\"og:description\" content=\"".htmlentities($extract)."\">"; //shame doesnt fall back and actully use metadescruption
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
			} else {
				$smarty->assign_by_ref('image', $image);
				$imageurl = $image->_getFullpath(false,true);

		                $extra_meta[] = "<meta name=\"og:image\" content=\"{$CONF['TILE_HOST']}/stamped/".basename($imageurl)."\">";
			}
		}
		$smarty->assign('extra_meta', implode("\n",$extra_meta));
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
