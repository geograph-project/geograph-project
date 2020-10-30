<?php
/**
 * $Project: GeoGraph $
 * $Id: submissions.php 6368 2010-02-13 19:45:59Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2011 Barry Hunter (geo@barryhunter.co.uk)
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

if (!empty($_GET['id']) && $USER->user_id) {
	header("HTTP/1.0 204 No Content");
	header("Status: 204 No Content");
	header("Content-Length: 0");
	flush();

	$db = GeographDatabaseConnection(false);

	$sql = "UPDATE gridimage_typo SET
		muted = NOW(),
		moderator = ".intval($USER->user_id)."
		WHERE gridimage_id = ".intval($_GET['id']);

	$db->Execute($sql);
	exit;
}

$smarty = new GeographPage;

 $USER->mustHavePerm("moderator");

customGZipHandlerStart();

$template='admin_watchlist.tpl';
$cacheid="";

//what style should we use?
$style = $USER->getStyle();

$smarty->assign('maincontentclass', 'content_photo'.$style);

	$imagelist=new ImageList;
	$db = $imagelist->_getDB();


	$values = $db->getCol("SELECT distinct type FROM gridimage_typo");
	array_unshift($values,'');
	$smarty->assign('types', array_combine($values,$values));
	$smarty->assign_by_ref('get', $_GET);

	$where = array();
	$join = '';
	if (!empty($_GET['type']))
		$where[] = "type = ".$db->Quote($_GET['type']);

	if (!empty($_GET['u']) && preg_match('/^\d+(\,\d+)*$/',$_GET['u']))
		$where[] = "gi.user_id IN ({$_GET['u']})"; //preg above avoids sql injecion

	if (!empty($_GET['e']) && preg_match('/^\d+(\,\d+)*$/',$_GET['e']))
		$where[] = "gi.user_id NOT IN ({$_GET['e']})"; //preg above avoids sql injecion

	if (!empty($_GET['i']))
		$where[] = "word NOT LIKE ".$db->Quote('%'.$_GET['i'].'%');

	if (!empty($_GET['current'])) {
		$last = $db->getOne("SELECT MAX(gridimage_id) FROM gridimage");
		$join = " INNER JOIN user_stat USING (user_id)";
		$where[] = "last > ".($last-14000);
	}
	$where[] = "muted < upd_timestamp"; //only images updated since been muted!

	$where[] = "updated > date_sub(now(),interval 48 hour)"; //only show images found matching recently, so images no longer match the trigger, 'fall' away!

	$where = implode(' AND ',$where);

	$sql="	select gridimage_id,title,realname,user_id,comment,imageclass,moderation_status,grid_reference,submitted,upd_timestamp,word,updated
		from gridimage_typo inner join gridimage_search gi using (gridimage_id) $join
		where $where
		order by updated desc
		limit 50";

	$imagelist->_getImagesBySql($sql);

	if (count($imagelist->images)) {
		foreach ($imagelist->images as $i => $image) {
			$imagelist->images[$i]->imagetakenString = getFormattedDate($image->imagetaken);
			//if (preg_match('/^\w/',$image->word)) {
			//	$image->word = ''; //snippet builder just uses the start of the title if
			//}


			$imagelist->images[$i]->title_html = htmlspecialchars2($image->title);
			if (!empty($image->word)) {
				//$search = '/('.preg_replace('/\s*\\\.\\\.\\\.\s*/','',preg_quote($image->word,'/')).')/i';
				$search = '/('.preg_quote(preg_replace('/\s*\.\.\.\s*/','',$image->word),'/').')/i';
				//todo if word is itself a regex could just run it, but it is mysql format regex, not preg, so slightly different
				$replace = '<b style=background-color:yellow;>$1</b>';
				$imagelist->images[$i]->title_html = preg_replace($search, $replace, $imagelist->images[$i]->title_html);
			}

			if (!empty($image->comment)) {
				//we do here, so can do some highliting!
				$imagelist->images[$i]->comment_html = GeographLinks(nl2br(htmlspecialchars2($image->comment)));
				if (!empty($image->word)) {
					$imagelist->images[$i]->comment_html = preg_replace($search, $replace, $imagelist->images[$i]->comment_html);
				}
			}
		}

		$smarty->assign_by_ref('images', $imagelist->images);
	}


$smarty->display($template, $cacheid);


