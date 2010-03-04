<?php
/**
 * $Project: GeoGraph $
 * $Id: submissions.php 6368 2010-02-13 19:45:59Z barry $
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

customGZipHandlerStart();


$USER->mustHavePerm("basic");


$template='thumbed.tpl';

$max_vote_id = 0;
$count = 0;
if (!empty($_GET['next'])) {
	$token=new Token;
	
	if ($token->parse($_GET['next']) && $token->hasValue("id")) {
		$max_vote_id = intval($token->getValue("id"));
		$count = intval($token->getValue("c"));
	} else {
		die("invalid token");
	}
}

$type = (isset($_GET['type']) && preg_match('/^\w+$/' , $_GET['type']))?$_GET['type']:'';

$ab=floor($USER->user_id/10000);
	
$cacheid="user$ab|{$USER->user_id}|{$max_gridimage_id}|$type";

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
	$types = array(''=>'Either','img'=>'Image','desc'=>'Description');
	$smarty->assign_by_ref('types', $types);
	$smarty->assign_by_ref('type', $type);
	
	$imagelist=new ImageList;

	if ($type == 'desc' || $type =='img') {
		$where = "type = '$type'";
	} else {
		$where = "type in ('img','desc')";
	}
	
	$sql="select gi.*,type,vote_id,ts ".
		"from vote_log as vl ".
		"inner join gridimage_search as gi on (vl.id = gi.gridimage_id and vl.user_id = gi.user_id) ".
		"where $where ".
		"and vl.user_id={$USER->user_id} ".
		($max_vote_id?" and vote_id < $max_vote_id ":'').
		"group by gridimage_id ".
		"order by vote_id desc limit 20";
			
	$imagelist->_getImagesBySql($sql);
	
	if (count($imagelist->images)) {
		foreach ($imagelist->images as $i => $image) 
			$imagelist->images[$i]->imagetakenString = getFormattedDate($image->imagetaken);
	
		$smarty->assign_by_ref('images', $imagelist->images);

		$first = $imagelist->images[0];
		
		$smarty->assign('criteria', $first->ts);

		$last = $imagelist->images[count($imagelist->images)-1];

		$max_vote_id = $last->vote_id;
		$count++;

		if ($count < 10 && count($imagelist->images) == 20) {
			$token=new Token;
			$token->setValue("id", intval($max_vote_id));
			$token->setValue("c", intval($count));

			$smarty->assign('next', $token->getToken());
		}
	}
	
	if ($max_vote_id && isset($_SERVER['HTTP_REFERER'])) {
		$ref = @parse_url($_SERVER['HTTP_REFERER']);
		if (!empty($ref['query'])) {
			$ref_query = array();
			parse_str($ref['query'], $ref_query);
			if (!empty($ref_query['next'])) {
				$smarty->assign('prev', $ref_query['next']);
			}
		} elseif ($ref['path'] == '/thumbed.php') {
			$smarty->assign('prev', 1);
		}
	}
}


$smarty->display($template, $cacheid);


