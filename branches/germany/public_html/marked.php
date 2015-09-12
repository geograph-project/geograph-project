<?php
/**
 * $Project: GeoGraph $
 * $Id: submissions.php 6417 2010-03-04 22:14:53Z barry $
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


#$USER->mustHavePerm("basic");


$template='marked.tpl';



#$ab=floor($USER->user_id/10000);
	
$cacheid=0; #FIXME?

//what style should we use?
#$style = $USER->getStyle();

#$smarty->assign('maincontentclass', 'content_photo'.$style);
	
//regenerate?
#if (!$smarty->is_cached($template, $cacheid))
{

	$imagelist=new ImageList;

	#FIXME submissions.php: what does the SERVER['REFERRER'] part?

	$gids = array();
	foreach (explode(',',$_COOKIE['markedImages']) as $id) {
		# FIXME validate, otherwise map intval to array
		$gids[] = intval($id);
	}
	$imagelist->getImagesByIdList($gids); /* no pending and rejected images */
	
	if (count($imagelist->images)) {
		#foreach ($imagelist->images as $i => $image) 
		#	$imagelist->images[$i]->imagetakenString = getFormattedDate($image->imagetaken);
	
		$smarty->assign_by_ref('images', $imagelist->images);
	}
	$smarty->assign('imagecount', count($imagelist->images));
}

$smarty->display($template, $cacheid);

?>
