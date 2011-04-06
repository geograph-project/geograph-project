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

customGZipHandlerStart();

$template='admin_watchlist.tpl';	
$cacheid="";

//what style should we use?
$style = $USER->getStyle();

$smarty->assign('maincontentclass', 'content_photo'.$style);
				
	$imagelist=new ImageList;
	
	$sql="	select gridimage_id,title,realname,user_id,comment,imageclass,moderation_status,grid_reference,submitted,upd_timestamp,word
		from gridimage_typo inner join gridimage_search using (gridimage_id)
		where muted < upd_timestamp
		order by updated desc
		limit 50";
	
	$imagelist->_getImagesBySql($sql);
	
	if (count($imagelist->images)) {
		foreach ($imagelist->images as $i => $image) 
			$imagelist->images[$i]->imagetakenString = getFormattedDate($image->imagetaken);
	
		$smarty->assign_by_ref('images', $imagelist->images);
	}


$smarty->display($template, $cacheid);


