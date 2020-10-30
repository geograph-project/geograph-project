<?php
/**
 * $Project: GeoGraph $
 * $Id: faq.php 15 2005-02-16 12:23:35Z lordelph $
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
init_session();


$smarty = new GeographPage;
$template = 'teachers.tpl';
$cacheid = null;

//regenerate?
if (!$smarty->is_cached($template, $cacheid))
{
	$imagelist=new ImageList;
	$sql = "SELECT {$imagelist->cols} FROM gridimage_search WHERE gridimage_id IN (334016,3263388,1359367,227437,5669859,4588191) ORDER BY RAND()";

        $imagelist->_getImagesBySql($sql);

        if (count($imagelist->images)) {
		$smarty->assign_by_ref('images', $imagelist->images);
	}
}

$smarty->display($template, $cacheid);

