<?php
/**
 * $Project: GeoGraph $
 * $Id: preview.php 8533 2017-08-14 19:16:51Z barry $
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

init_session();

customGZipHandlerStart();

$smarty = new GeographPage;

$template='view.tpl';
$cacheid=0;

$smarty->caching = 0;



//do we have a valid image?
if (!empty($_POST))
{
	$image=new GridImage;

	if (!empty($_POST['id'])) {
		$image->loadFromId(intval($_POST['id']));
	} else {
		$image->gridimage_id = 0;
		$image->moderation_status = 'pending';
		$image->submitted = time();
		$image->user_id = $USER->user_id;
		$image->realname = $USER->realname;
		$image->profile_link = "/profile/{$image->user_id}";

		if (!empty($_POST['pattrib']) && $_POST['pattrib'] == 'other') {
			$image->realname = strip_tags(trim(stripslashes($_POST['pattrib_name'])));
			$image->profile_link .= "?a=".urlencode($_POST['pattrib_name']);
		}
	}

	$image->title = strip_tags(trim(stripslashes($_POST['title'])));
	$image->comment = strip_tags(trim(stripslashes($_POST['comment'])));

	$image->imageclass=strip_tags(trim(stripslashes($_POST['imageclass'])));

	if ($image->imageclass=="Other") {
		$image->imageclass = strip_tags(trim(stripslashes($_POST['imageclassother'])));
	}

	if (isset($_POST['imagetakenYear'])) {
		$image->imagetaken=sprintf("%04d-%02d-%02d",$_POST['imagetakenYear'],$_POST['imagetakenMonth'],$_POST['imagetakenDay']);
	}
	$image->use6fig = !empty($_POST['use6fig']);

	if (!empty($_POST['grid_reference'])) {
		$image->grid_square = new GridSquare();
		$image->grid_square->setByFullGridRef($_POST['grid_reference']);

		$image->grid_reference=$image->grid_square->grid_reference;
		$image->natgrlen=$image->grid_square->natgrlen;
		$image->nateastings=$image->grid_square->nateastings;
		$image->natnorthings=$image->grid_square->natnorthings;
	}

	if (!empty($_POST['photographer_gridref'])) {
		$viewpoint = new GridSquare;
		$ok= $viewpoint->setByFullGridRef($_POST['photographer_gridref'],true);

		$image->viewpoint_eastings = $viewpoint->nateastings;
		$image->viewpoint_northings = $viewpoint->natnorthings;
		$image->viewpoint_grlen = $viewpoint->natgrlen;
	}

	if (isset($_POST['view_direction']))
		$image->view_direction = intval(strip_tags(trim(stripslashes($_POST['view_direction']))));

	if (!empty($_POST['upload_id']))
		$image->fullpath = "/submit.php?preview=".strip_tags(trim(stripslashes($_POST['upload_id'])));




	//what style should we use?
	$style = $USER->getStyle();

	$smarty->assign('maincontentclass', 'content_photo'.$style);

	#if (!$smarty->is_cached($template, $cacheid))
	#{
		$image->assignToSmarty($smarty);

		if (!empty($_POST['upload_id'])) {
			$gid = crc32($_POST['upload_id'])+4294967296;
			$gid += $USER->user_id * 4294967296;
			$gid = sprintf('%0.0f',$gid);

			$image->loadSnippets($gid);
		} elseif (!empty($_POST['id'])) {
			$image->loadSnippets();
		}
	#}
} else {
	header("HTTP/1.0 404 Not Found");
	header("Status: 404 Not Found");
	$template='static_404.tpl';
}



$smarty->display($template, $cacheid);


