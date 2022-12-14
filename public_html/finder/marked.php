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


$template='finder_marked.tpl';
$cacheid='';

//what style should we use?
$style = $USER->getStyle();


$smarty->assign('maincontentclass', 'content_photo'.$style);

if (isset($_COOKIE['markedImages']) && !empty($_COOKIE['markedImages'])) {
	$imagelist = new ImageList();

	$plain = preg_replace('/[^,\d]+/','',$_COOKIE['markedImages']);
	$plain = trim(preg_replace('/,{2,}+/',',',$plain),',');

	$ids = explode(',',$plain);

	$smarty->assign('count',count($ids));

	if (count($ids) > 100)
		$ids = array_slice($ids,0,100);

	if ($ids) {
		$imagelist->getImagesByIdList($ids);
		$smarty->assign_by_ref('results', $imagelist->images);
		$smarty->assign_by_ref('ids', implode(',',$ids));
	}
}


$smarty->display($template, $cacheid);


