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

customExpiresHeader(3600*6,false,true);


$template='stuff_daily.tpl';
$cacheid=!empty($_GET['gallery']);

//what style should we use?
$style = $USER->getStyle();


$smarty->assign('maincontentclass', 'content_photo'.$style);

$src = 'data-src';
if ((stripos($_SERVER['HTTP_USER_AGENT'], 'http')!==FALSE) ||
        (stripos($_SERVER['HTTP_USER_AGENT'], 'bot')!==FALSE)) {
        $src = 'src';//revert back to standard non lazy loading
}
$smarty->assign('src',$src);

if (!$smarty->is_cached($template, $cacheid)) {
	$imagelist = new ImageList();

	if (!empty($_GET['gallery'])) {
		$imagelist->_getImagesBySql("SELECT gridimage_id,showday,user_id,realname,title,grid_reference,credit_realname FROM gridimage_search inner join gallery_ids on (id=gridimage_id) WHERE showday <= date(now()) ORDER BY showday DESC limit 16");
		$smarty->assign('gallery',1);
	} else {
		$imagelist->_getImagesBySql("SELECT gridimage_id,showday,user_id,realname,title,grid_reference,credit_realname FROM gridimage_search inner join gridimage_daily using (gridimage_id) WHERE showday <= date(now()) ORDER BY showday DESC limit 16");
	}

	$smarty->assign_by_ref('results', $imagelist->images);
}


$smarty->display($template, $cacheid);


