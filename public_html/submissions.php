<?php
/**
 * $Project: GeoGraph $
 * $Id: submissions.php 8140 2014-06-10 21:15:37Z geograph $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2007 Barry Hunter (geo@barryhunter.co.uk)
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
require_once('geograph/imagelist.class.php');
init_session();


$smarty = new GeographPage;

dieIfReadOnly();

customGZipHandlerStart();

$USER->mustHavePerm("basic");


//temp as edit page doesnt work on https (mainly maps!)
pageMustBeHTTP();



$template='submissions.tpl';

$max_gridimage_id = 0;
$count = 0;
if (!empty($_GET['next'])) {
	$token=new Token;

	if ($token->parse($_GET['next']) && $token->hasValue("id")) {
		$max_gridimage_id = intval($token->getValue("id"));
		$count = intval($token->getValue("c"));
	} else {
		die("invalid token");
	}
}

$ab=floor($USER->user_id/10000);

$cacheid="user$ab|{$USER->user_id}|{$max_gridimage_id}";

$src = 'data-src';
if ((stripos($_SERVER['HTTP_USER_AGENT'], 'http')!==FALSE) ||
        (stripos($_SERVER['HTTP_USER_AGENT'], 'bot')!==FALSE)) {
  $src = 'src';//revert back to standard non lazy loading
}

$src = 'loading="lazy" src'; //experimenting with moving to it permentanty!

$cacheid .=".$src";

if (!empty($_GET['inner'])) {
	$smarty->assign('inner', 1);
	$cacheid .=".inner";
}

//what style should we use?
$style = $USER->getStyle();

if ($smarty->caching) {
	$smarty->caching = 2; // lifetime is per cache
	$smarty->cache_lifetime = 300;
	customExpiresHeader(300,false,true);
}

$smarty->assign('maincontentclass', 'content_photo'.$style);

//regenerate?
if (!$smarty->is_cached($template, $cacheid))
{
	$imagelist=new ImageList;
	if (!empty($_SESSION['last_grid_reference'])) { //appears have been uploading recently!
		$db = $imagelist->_getDB(60); // we 'initialize' the contained db, as one that allows only some lag.
		header('X-Reason: '.@$db->failover_reason); //just for interest output this!
	}

	$sql="select gi.*,grid_reference ".
		"from gridimage as gi ".
		"inner join gridsquare as gs using(gridsquare_id) ".
		"where moderation_status != 'rejected' ".
		"and gi.user_id={$USER->user_id} ".
		($max_gridimage_id?" and gridimage_id < $max_gridimage_id ":'').
		"order by gridimage_id desc limit 20";

	$imagelist->_getImagesBySql($sql);

	if (count($imagelist->images)) {
		foreach ($imagelist->images as $i => $image)
			$imagelist->images[$i]->imagetakenString = getFormattedDate($image->imagetaken);

		$smarty->assign_by_ref('images', $imagelist->images);

		$first = $imagelist->images[0];

		$smarty->assign('criteria', $first->submitted);

		$last = $imagelist->images[count($imagelist->images)-1];

		$max_gridimage_id = $last->gridimage_id;
		$count++;

		if ($count < 10 && count($imagelist->images) == 20) {
			$token=new Token;
			$token->setValue("id", intval($max_gridimage_id));
			$token->setValue("c", intval($count));

			$smarty->assign('next', $token->getToken());
		}
	}

	if ($max_gridimage_id && isset($_SERVER['HTTP_REFERER'])) {
		$ref = @parse_url($_SERVER['HTTP_REFERER']);
		if (!empty($ref['query'])) {
			$ref_query = array();
			parse_str($ref['query'], $ref_query);
			if (!empty($ref_query['next'])) {
				$smarty->assign('prev', $ref_query['next']);
			}
		} elseif ($ref['path'] == '/submissions.php') {
			$smarty->assign('prev', 1);
		}
	}

	if (empty($USER->db)) {
		$USER->db = $imagelist->db;
	}
	$USER->getStats();
}

$smarty->assign("src",$src);
$smarty->display($template, $cacheid);


