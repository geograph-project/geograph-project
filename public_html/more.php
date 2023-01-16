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

if (strpos($_SERVER['HTTP_USER_AGENT'],'ms-office') !== FALSE) {
	header("HTTP/1.0 429 Too Many Requests");
	print "Too Many Requests.";
	exit;
}

require_once('geograph/global.inc.php');

init_session();

rate_limiting('more.php', 5, true);

$smarty = new GeographPage;
$template='more.tpl';

if (isset($_REQUEST['id']))
{
	//initialise message
	require_once('geograph/gridsquare.class.php');
	require_once('geograph/gridimage.class.php');

	$image=new GridImage();
	$ok = $image->loadFromId($_REQUEST['id'],true);

	if (!$ok || $image->moderation_status=='rejected') {
		//clear the image
		$image=new GridImage;
		header("HTTP/1.0 410 Gone");
		header("Status: 410 Gone");
		$template = "static_404.tpl";
	} else {
		if (strpos($image->tags,"panorama") !== FALSE) {
			foreach (explode('?',$image->tags)  as $str) {
				list($prefix,$tag) = explode(':',$str,2);
				if ($prefix == 'panorama') { //only wooried about this one prefix!
					if (!is_array($image->tags))
						$image->tags = array();
					$image->tags[] = array('prefix'=>$prefix,'tag'=>$tag);
					@$image->tag_prefix_stat[$prefix]++;
				}
			}
		}

		$image->altUrl = $image->_getOriginalpath(true,true,'_640x640');

		$image->originalPath = $image->_getOriginalpath(true,false);

		$filesystem = GeographFileSystem();

		$image->originalSize = $filesystem->filesize($_SERVER['DOCUMENT_ROOT'].$image->originalPath);

		$style = $USER->getStyle();
		$smarty->assign('maincontentclass', 'content_photo'.$style);
	}
	$smarty->assign_by_ref('image', $image);
	$smarty->assign('tile_host', $CONF['TILE_HOST']);

} else {
	header("HTTP/1.0 404 Not Found");
	header("Status: 404 Not Found");
	$template = "static_404.tpl";
}


$smarty->display($template);

